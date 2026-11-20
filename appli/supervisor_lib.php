<?php
require_once __DIR__.'/club_prefix.php';
function supervisorHost($host): string
{
    if (!is_string($host) || strlen($host) > 253 ||
        !preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*\z/iD', $host)) {
        throw new RuntimeException('Nom de serveur incorrect. Utiliser un domaine sans protocole, port ou chemin.');
    }
    return strtolower($host);
}

function supervisorActivationName(string $host): string
{
    return 'supervisor_access_'.supervisorHost($host).'.php';
}

// No application bootstrap here: maintenance must work before club tables exist.
function supervisorValidateConfig($c, ?string $expectedHost = null): array
{
    if (!is_array($c) || ($c['enabled'] ?? false) !== true ||
        !is_int($c['expires_at'] ?? null) || $c['expires_at'] <= time() ||
        !is_string($c['password_hash'] ?? null) ||
        empty(password_get_info($c['password_hash'])['algo']) ||
        !is_string($c['host'] ?? null) || !preg_match('/\A[A-Za-z0-9.-]+\z/D', $c['host']) ||
        !is_array($c['clubs'] ?? null) || !$c['clubs']) {
        throw new RuntimeException('Activation absente, expirée ou incorrecte.');
    }
    $c['host'] = supervisorHost($c['host']);
    if ($expectedHost !== null && $c['host'] !== supervisorHost($expectedHost)) {
        throw new RuntimeException('Cette activation appartient à un autre serveur. Vérifier son nom et son champ host.');
    }
    foreach ($c['clubs'] as $prefix => $label) {
        if (!bridgetteValidClubPrefix((string)$prefix) || !is_string($label)) {
            throw new RuntimeException('Préfixe de club incorrect : exactement trois lettres ou chiffres suivis de _ (exemple : tst_).');
        }
    }
    return $c;
}

function supervisorTarget(array $c, string $prefix, string $command): array
{
    if (!bridgetteValidClubPrefix($prefix) || !array_key_exists($prefix, $c['clubs']) ||
        !in_array($command, ['install', 'addadmin', 'droptables', 'gc', 'patch'], true)) {
        throw new RuntimeException('Opération ou club non autorisé.');
    }
    if ($command === 'addadmin') return ['directeurs' => $prefix.'directeurs'];
    $names = ['directeurs', 'joueurs', 'tournois', 'connexions', 'pairesNS', 'pairesEO', 'donnes', 'diagrammes', 'events'];
    return array_combine($names, array_map(static function ($name) use ($prefix) { return $prefix.$name; }, $names));
}

function supervisorConfirmation(string $prefix, string $command): string
{
    if ($command === 'gc') return 'PURGER LES SESSIONS DE TOUS LES CLUBS';
    if ($command === 'addadmin') return 'AJOUTER ADMINISTRATEUR '.$prefix;
    return strtoupper($command).' '.$prefix;
}

function supervisorPatch(array $c): string
{
    $file = __DIR__.'/patch.php';
    $expected = $c['patch_sha256'] ?? '';
    if (!is_string($expected) || !preg_match('/\A[a-f0-9]{64}\z/D', $expected) ||
        !is_file($file) || !hash_equals($expected, hash_file('sha256', $file))) {
        throw new RuntimeException('Patch absent ou empreinte non autorisée dans le fichier d’activation.');
    }
    return $expected;
}

function supervisorAdmin(string $name, string $password): array
{
    require_once __DIR__.'/security_passwords.php';
    $name = htmlspecialchars(stripslashes($name));
    if ($name === '' || strlen($name) > 32 || $name === 'zorglub' ||
        strlen($password) < 14 || strlen($password) > 256) {
        throw new RuntimeException('Administrateur : pseudo de 1 à 32 caractères (hors compte réservé), mot de passe de 14 à 256 caractères.');
    }
    return [$name, bridgettePasswordHash($password)];
}

function supervisorConnect(array $c): PDO
{
    // The FTP configuration chooses this file, never a request parameter or cookie.
    require_once __DIR__.'/clubs/conf_'.$c['host'].'.php';
    return new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
}

class SupervisorAdminExists extends RuntimeException {}

function supervisorAddAdmin($dbh, string $table, array $admin): void
{
    if (count($admin) !== 2) throw new RuntimeException('Administrateur manquant.');
    $locked = false;
    try {
        // The schema has no unique pseudo constraint. This also serializes
        // ordinary account creation, not just requests using GET_LOCK.
        if ($dbh->exec('LOCK TABLES `'.$table.'` WRITE') === false) throw new RuntimeException('Verrouillage impossible.');
        $locked = true;
        $stmt = $dbh->prepare('SELECT COUNT(*) FROM `'.$table.'` WHERE pseudo = ?');
        if ($stmt->execute([$admin[0]]) === false) throw new RuntimeException('Vérification du pseudo impossible.');
        if ((int)$stmt->fetchColumn() > 0) throw new SupervisorAdminExists('Ce pseudo existe déjà dans le club. Choisir un autre pseudo.');
        $stmt = $dbh->prepare('INSERT INTO `'.$table.'` (pseudo, password, droits) VALUES (?, ?, ?)');
        if ($stmt->execute([$admin[0], $admin[1], 'admin']) === false || $stmt->rowCount() !== 1) {
            throw new RuntimeException('Création de l’administrateur non confirmée.');
        }
    } finally {
        if ($locked && $dbh->exec('UNLOCK TABLES') === false) throw new RuntimeException('Déverrouillage non confirmé.');
    }
}

function supervisorRun($dbh, array $c, string $prefix, string $command, array $admin = []): string
{
    $tables = supervisorTarget($c, $prefix, $command);
    if ($command === 'addadmin') {
        supervisorAddAdmin($dbh, $tables['directeurs'], $admin);
        return 'Administrateur ajouté au club. Les comptes existants sont conservés.';
    }
    if ($command === 'gc') {
        $stmt = $dbh->prepare('DELETE FROM `sessions` WHERE `access` < ?');
        $stmt->execute([time() - 100 * 86400]);
        return $stmt->rowCount().' ancienne(s) session(s) supprimée(s), tous clubs confondus.';
    }
    if ($command === 'droptables') {
        foreach ($tables as $table) $dbh->exec('DROP TABLE IF EXISTS `'.$table.'`');
        return 'Les neuf tables du club ont été supprimées. Les fichiers du club et la table sessions sont conservés.';
    }
    // Legacy schema/patch helpers use global table variables.
    foreach ($tables as $name => $table) { $GLOBALS['tab_'.$name] = $table; ${'tab_'.$name} = $table; }
    $GLOBALS['prefix'] = $prefix;
    if ($command === 'patch') {
        supervisorPatch($c);
        require __DIR__.'/patch.php';
        if (!function_exists('executepatch')) throw new RuntimeException('Fonction executepatch absente.');
        executepatch($dbh);
        return 'Patch exécuté. Vérifier son résultat fonctionnel avant de rouvrir le club.';
    }
    if (count($admin) !== 2) throw new RuntimeException('Administrateur initial manquant.');
    $db_name = defined('DB_NAME') ? DB_NAME : '';
    require __DIR__.'/installtablesbdd.php';
    foreach ($tables as $name => $table) $dbh->exec(${'sql_'.$name});
    // No predefined administrator and no hidden account are seeded by this interface.
    if ((int)$dbh->query('SELECT COUNT(*) FROM `'.$tab_directeurs.'`')->fetchColumn() === 0) {
        $stmt = $dbh->prepare('INSERT INTO `'.$tab_directeurs.'` (pseudo, password, droits) VALUES (?, ?, ?)');
        $stmt->execute([$admin[0], $admin[1], 'admin']);
        $message = 'Administrateur initial créé.';
    } else {
        $message = 'Comptes existants conservés ; administrateur proposé non ajouté.';
    }
    $GLOBALS['max_tables'] = 15;
    $GLOBALS['debinvites'] = 4;
    init_tab_joueurs($dbh);
    init_tab_connexions($dbh);
    return 'Tables créées si absentes, tables d’initialisation peuplées si vides. '.$message;
}

// A shared, locked file prevents bypassing the limit by deleting browser cookies.
function supervisorPassword(array $c, string $password): bool
{
    $key = hash('sha256', __DIR__.$c['host'].$c['password_hash'].$c['expires_at']);
    $file = sys_get_temp_dir().'/bridgette-supervisor-'.$key.'.json';
    $handle = fopen($file, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) throw new RuntimeException('Contrôle d’accès indisponible.');
    try {
        $state = json_decode(stream_get_contents($handle), true);
        if (!is_array($state) || ($state['until'] ?? 0) <= time()) $state = ['count' => 0, 'until' => time() + 900];
        if ($state['count'] >= 5) throw new RuntimeException('Trop de tentatives. Réessayer après 15 minutes.');
        $ok = strlen($password) <= 1024 && password_verify($password, $c['password_hash']);
        if ($ok) $state['count'] = 0;
        else $state['count']++;
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, json_encode($state)) === false || !fflush($handle)) {
            throw new RuntimeException('Contrôle d’accès indisponible.');
        }
        return $ok;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
