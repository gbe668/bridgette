<?php
require_once __DIR__.'/security_android.php';
require_once __DIR__.'/club_prefix.php';
require_once __DIR__.'/security_csrf.php';
require_once __DIR__.'/security_mutations.php';
require_once __DIR__.'/security_passwords.php';
// Shared access checks. A launcher token selects a club; it never grants a role.
function securityDeny($message = 'Accès refusé.', $status = 403) {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    exit($message);
}

function securityQuery($db, $sql, $parameters) {
    $query = $db->prepare($sql);
    $query->execute($parameters);
    return $query;
}

function securityClub($club) {
    if (!is_array($club) || !isset($club['prefix'], $club['timex']) ||
        !bridgetteValidClubPrefix($club['prefix']) ||
        !is_numeric($club['timex'])) {
        securityDeny('Sélection du club incorrecte.', 400);
    }
    return $club;
}

function securitySelectClub($club) {
    $club = securityClub($club);
    if (($_SESSION['prefix'] ?? null) !== $club['prefix']) {
        // Also discard player identity, tournament code and navigation state.
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    }
    $_SESSION['prefix'] = $club['prefix'];
    $_SESSION['timex'] = (int)$club['timex'];
}

function securityRole($role) {
    return isset($_SESSION['pseudo']) && ($_SESSION['fonction'] ?? '') === $role &&
        ($_SESSION['auth_club'] ?? null) === ($_SESSION['prefix'] ?? '') &&
        time() - ($_SESSION['ttlast'] ?? 0) <= 1440;
}

function securityRequireRole($role) {
    if (!securityRole($role)) securityDeny();
}

function securityPlayerId() {
    $ident = $_SESSION['player_identity'] ?? [];
    if (($ident['club'] ?? null) !== ($_SESSION['prefix'] ?? '') ||
        time() - ($ident['time'] ?? 0) > 30 * 86400) return 0;
    return max(0, (int)($ident['id'] ?? 0));
}

function securityRememberPlayer() {
    if (!securityPlayerId()) return;
    $_SESSION['player_identity']['time'] = time();
    // Persist only the opaque session cookie, never a client-supplied player ID.
    if (session_status() === PHP_SESSION_ACTIVE) {
        $cookie = session_get_cookie_params();
        unset($cookie['lifetime']);
        $cookie['expires'] = time() + 30 * 86400;
        setcookie(session_name(), session_id(), $cookie);
    }
}

function securityPlayerMode(): int {
    global $parametres;
    $mode = (string)($parametres['checkuser'] ?? '0');
    if (!in_array($mode, ['0', '1', '2'], true)) securityDeny('Paramétrage joueur incorrect.', 500);
    return (int)$mode;
}

function securityNativePlayer(): bool {
    // Established only after consuming a native login ticket, never from User-Agent.
    $hash = $_SESSION['android_ticket_hash'] ?? null;
    return is_string($hash) && preg_match('/\A[a-f0-9]{64}\z/D', $hash) === 1 && securityPlayerId() > 0;
}

function securityRequirePlayer() {
    $id = securityPlayerId();
    if (!$id) securityDeny('Identifiez-vous avant de modifier des données.', 401);
    global $tab_joueurs;
    $db = connectBDD();
    $q = $db->prepare("SELECT id FROM `$tab_joueurs` WHERE id = ? AND datesupp = 0");
    $q->execute([$id]);
    if (!$q->fetchColumn()) securityDeny('Compte joueur indisponible.', 401);
    return $id;
}

function securityInt($name, $min = 1, $max = 2147483647) {
    $value = $_GET[$name] ?? null;
    if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int)$value < $min || (int)$value > $max) securityDeny('Paramètre incorrect : '.$name, 400);
    $_GET[$name] = (int)$value;
    return (int)$value;
}

// Supplemental Origin check, after the POST/session-token check.
function securitySameOrigin() {
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $expected = strtolower($scheme.'://'.($_SERVER['HTTP_HOST'] ?? ''));
    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $parts = parse_url($source);
    if (!$parts || !isset($parts['scheme'], $parts['host'])) securityDeny('Origine de la requête absente. Rechargez la page du club.');
    $origin = strtolower($parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : ''));
    if ($origin !== $expected) securityDeny('Origine de la requête refusée.');
    header('Cache-Control: no-store');
}

function securityOwnPlayer($parameter) {
    $id = securityInt($parameter);
    if (!securityRole('directeur') && securityRequirePlayer() !== $id) securityDeny();
}

function securityTournament($id, $ns = null, $eo = null, $allowFinished = false) {
    global $tab_pairesNS, $tab_pairesEO, $st_phase_jeu, $st_phase_fini, $t_howell;
    if (securityRole('directeur')) return;
    $t = readTournoi($id);
    $native = securityNativePlayer();
    $allowedStates = $allowFinished ? [$st_phase_jeu, $st_phase_fini] : [$st_phase_jeu];
    if (!$t || !in_array((int)$t['etat'], $allowedStates, true) || (!$native && !isCheckedJoueur($id))) securityDeny('Tournoi inaccessible.');
    // Non: no login. M: any active club member (including a borrowed phone).
    // P: an active member registered in this tournament, not necessarily this pair.
    // Installed Android clients join through their registered player's pair.
    $mode = $native ? 2 : securityPlayerMode();
    $player = $mode > 0 ? securityRequirePlayer() : 0;
    if ($ns !== null || $eo !== null) {
        $maxNS = (int)$t['pairesNS'];
        $maxEO = (int)$t['genre'] === $t_howell ? $maxNS : (int)$t['pairesEO'];
        if (($ns ?? 0) < 0 || ($ns ?? 0) > $maxNS || ($eo ?? 0) < 0 || ($eo ?? 0) > $maxEO ||
            (($ns ?? 0) === 0 && ($eo ?? 0) === 0)) securityDeny('Paire incorrecte.', 400);
    }
    if ($mode < 2) return;
    $db = connectBDD();
    // In Howell both pairs are stored in pairesNS.
    $tables = [[$tab_pairesNS, 'idj1', 'idj3'],
        [(int)$t['genre'] === $t_howell ? $tab_pairesNS : $tab_pairesEO,
         (int)$t['genre'] === $t_howell ? 'idj1' : 'idj2',
         (int)$t['genre'] === $t_howell ? 'idj3' : 'idj4']];
    foreach ($tables as [$table, $a, $b]) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE idtournoi = ? AND ($a = ? OR $b = ?)";
        $args = [$id, $player, $player];
        $q = $db->prepare($sql);
        $q->execute($args);
        if ($q->fetchColumn() > 0) return;
    }
    securityDeny('Vous ne faites pas partie des joueurs inscrits à ce tournoi.');
}

function securityAppPath($page) {
    // Locate the executing script, not the trailing PATH_INFO used by Android.
    // Keep the installation directory for clubs served under a subdirectory.
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $name = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
    $position = $name === '' ? false : strrpos($script, '/'.$name);
    if ($position === false) securityDeny('Chemin de l’application incorrect.', 500);
    return '/'.ltrim(substr($script, 0, $position).'/'.$page, '/');
}

function securityWebLogin() {
    if (securityPlayerId() || securityRole('directeur')) return;
    // Only local, server-generated destinations, including Android PATH_INFO.
    $name = basename($_SERVER['SCRIPT_FILENAME']);
    $query = $_GET;
    unset($query['token']);
    $_SESSION['login_return'] = securityAppPath($name).'?'.http_build_query($query);
    header('Location: '.securityAppPath('loguserin.php'));
    exit;
}
