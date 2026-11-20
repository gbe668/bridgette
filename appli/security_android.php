<?php
require_once __DIR__.'/security_android_devices.php';
// Compatibility bridge: the installed APK reads userid as an opaque String and
// passes it to its first WebView. Never use a numeric URL ID as authentication.
function securityAndroidTicketKey($ticket) {
    // '_' is not a valid PHP session-ID character: tickets cannot be loaded as
    // ordinary sessions. The existing sessions.id column is VARCHAR(32).
    return 'apk_'.substr(hash('sha256', $ticket), 0, 28);
}

function securityAndroidIssue($db, $player, $club) {
    $ticket = 'apk1.'.bin2hex(random_bytes(32));
    $now = time();
    $data = json_encode(['kind' => 'android_handoff', 'hash' => hash('sha256', $ticket),
        'club' => $club, 'player' => (int)$player, 'expires' => $now + 300]);
    // Garbage collection is confined to this ticket namespace, not club sessions.
    securityQuery($db, "DELETE FROM sessions WHERE LEFT(id, 4) = 'apk_' AND access < ?", [$now - 300]);
    securityQuery($db, 'INSERT INTO sessions (id, access, data) VALUES (?, ?, ?)',
        [securityAndroidTicketKey($ticket), $now, $data]);
    return $ticket;
}

function securityAndroidConsume($db, $ticket, $club) {
    if (!is_string($ticket) || !preg_match('/\Aapk1\.[a-f0-9]{64}\z/D', $ticket)) return 0;
    $key = securityAndroidTicketKey($ticket);
    $row = securityQuery($db, 'SELECT data FROM sessions WHERE id = ?', [$key])->fetch(PDO::FETCH_ASSOC);
    if (!$row) return 0;
    $data = json_decode($row['data'], true);
    if (!is_array($data) || ($data['kind'] ?? '') !== 'android_handoff' ||
        ($data['club'] ?? null) !== $club || ($data['expires'] ?? 0) <= time() ||
        !is_string($data['hash'] ?? null) || !hash_equals($data['hash'], hash('sha256', $ticket)) ||
        !is_int($data['player'] ?? null) || $data['player'] <= 0) return 0;
    // Atomic claim also works on installations without transactional tables.
    $deleted = securityQuery($db, 'DELETE FROM sessions WHERE id = ? AND data = ?', [$key, $row['data']]);
    return $deleted->rowCount() === 1 ? $data['player'] : 0;
}

function securityAndroidDenied() {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    // No Location header: these APK WebViews send redirects to the browser.
    exit('<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Connexion Bridgette</title><body><h2>Reconnectez-vous dans l’application</h2><p>Utilisez le menu Déconnexion de l’application Android, puis connectez-vous à nouveau au club.</p></body></html>');
}

function securityAndroidRequire() {
    header('Cache-Control: no-store');
    header('Referrer-Policy: origin');
    if (isset($_GET['userid'])) {
        $ticket = $_GET['userid'];
        if (!is_string($ticket) || !preg_match('/\Aapk1\.[a-f0-9]{64}\z/D', $ticket)) securityAndroidDenied();
        $hash = hash('sha256', $ticket);
        $known = $_SESSION['android_ticket_hash'] ?? '';
        if (!securityPlayerId() || !is_string($known) || !hash_equals($known, $hash)) {
            $player = securityAndroidConsume(connectBDD(), $ticket, $_SESSION['prefix']);
            if (!$player) securityAndroidDenied();
            // A new native login must not inherit a previous user's club roles.
            $_SESSION = ['prefix' => $_SESSION['prefix'], 'timex' => $_SESSION['timex'], 'ttlast' => time()];
            setIdent($player);
            $_SESSION['android_ticket_hash'] = $hash;
        }
    }
    if (!securityPlayerId()) securityAndroidDenied();
    return securityRequirePlayer();
}
