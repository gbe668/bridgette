<?php
// Technical records use '_' (not a PHP session-ID character) and fit VARCHAR(32).
const PASSWORD_RESET_MESSAGE = 'Si cette adresse correspond à un compte actif du club, un courriel de réinitialisation sera envoyé. Consultez votre messagerie.';
function passwordResetKey($club, $player) {
    return 'pwd_'.substr(hash('sha256', $club.'|'.$player), 0, 28);
}
function passwordResetIssue($db, $club, $player) {
    $ticket = 'pwd1.'.$player['id'].'.'.bin2hex(random_bytes(32));
    $data = json_encode(['club' => $club, 'player' => (int)$player['id'],
        'email' => $player['email'], 'password' => hash('sha256', (string)$player['password']),
        'hash' => hash('sha256', $ticket), 'expires' => time() + 3600]);
    securityQuery($db, 'INSERT INTO sessions (id, access, data) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE access = VALUES(access), data = VALUES(data)',
        [passwordResetKey($club, $player['id']), time(), $data]);
    return $ticket;
}
function passwordResetRecord($db, $club, $ticket) {
    if (!is_string($ticket) || !preg_match('/\Apwd1\.([1-9][0-9]{0,9})\.[a-f0-9]{64}\z/D', $ticket, $match)) return null;
    $key = passwordResetKey($club, $match[1]);
    $row = securityQuery($db, 'SELECT data FROM sessions WHERE id = ?', [$key])->fetch(PDO::FETCH_ASSOC);
    $data = $row ? json_decode($row['data'], true) : null;
    if (!is_array($data) || ($data['club'] ?? null) !== $club ||
        ($data['player'] ?? null) !== (int)$match[1] || ($data['expires'] ?? 0) <= time() ||
        !is_string($data['hash'] ?? null) || !hash_equals($data['hash'], hash('sha256', $ticket))) return null;
    return [$key, $row['data'], $data];
}
function passwordResetApply($db, $club, $table, $ticket, $password) {
    $record = passwordResetRecord($db, $club, $ticket);
    if (!$record) return false;
    [$key, $raw, $data] = $record;
    $player = securityQuery($db, "SELECT id, email, password FROM `$table` WHERE id = ? AND datesupp = 0", [$data['player']])->fetch(PDO::FETCH_ASSOC);
    if (!$player || $player['email'] !== $data['email'] ||
        !hash_equals($data['password'], hash('sha256', (string)$player['password']))) return false;
    // Claim before writing: one winner even with MyISAM and concurrent submissions.
    if (securityQuery($db, 'DELETE FROM sessions WHERE id = ? AND data = ?', [$key, $raw])->rowCount() !== 1) return false;
    $hash = bridgettePasswordHash($password);
    $changed = securityQuery($db, "UPDATE `$table` SET password = ? WHERE id = ? AND email = ? AND password <=> ? AND datesupp = 0",
        [$hash, $player['id'], $player['email'], $player['password']]);
    if ($changed->rowCount() === 1) return true;
    // PDO normally reports 0 for an unchanged password. Verify the final account.
    $current = securityQuery($db, "SELECT id, email, password FROM `$table` WHERE id = ? AND datesupp = 0", [$data['player']])->fetch(PDO::FETCH_ASSOC);
    return $current && $current['email'] === $data['email'] && hash_equals($hash, (string)$current['password']);
}
function passwordResetRate($db, $club, $scope, $value, $limit) {
    $key = 'rlm_'.substr(hash('sha256', $club.'|'.$scope.'|'.$value), 0, 28);
    $now = time();
    // Counter increment is atomic, independent of browser cookies. Fixed one-hour window.
    securityQuery($db, 'INSERT INTO sessions (id, access, data) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = IF(access <= ?, 1, CAST(data AS UNSIGNED) + 1), access = IF(access <= ?, VALUES(access), access)',
        [$key, $now, '1', $now - 3600, $now - 3600]);
    return (int)securityQuery($db, 'SELECT data FROM sessions WHERE id = ?', [$key])->fetchColumn() <= $limit;
}
function passwordResetRequest($db, $club, $table, $email, $send) {
    $email = is_string($email) ? trim($email) : '';
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;
    $ipAllowed = passwordResetRate($db, $club, 'ip', $_SERVER['REMOTE_ADDR'] ?? '', 20);
    if (!$ipAllowed) return;
    $emailAllowed = passwordResetRate($db, $club, 'mail', strtolower($email), 3);
    if (!$emailAllowed) return;
    // Same accounting and response for known, unknown, duplicate and disabled accounts.
    $rows = securityQuery($db, "SELECT id, email, password, datesupp FROM `$table` WHERE email = ?", [$email])->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) !== 1 || (int)$rows[0]['datesupp'] !== 0) return;
    $ticket = passwordResetIssue($db, $club, $rows[0]);
    $sent = false;
    try {
        $sent = $send($rows[0]['email'], $ticket);
    } finally {
        if (!$sent) {
            $record = passwordResetRecord($db, $club, $ticket);
            if ($record) securityQuery($db, 'DELETE FROM sessions WHERE id = ? AND data = ?', [$record[0], $record[1]]);
        }
    }
}
function passwordResetUrl($ticket, $clubToken) {
    // SERVER_NAME is also the configured host selector; never trust the Host header.
    $host = $_SERVER['SERVER_NAME'];
    if (!preg_match('/\A[a-zA-Z0-9.-]+\z/D', $host)) throw new RuntimeException('Invalid reset host');
    $local = in_array(strtolower($host), ['localhost', '127.0.0.1'], true);
    $scheme = $local && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
    $port = $local && isset($_SERVER['SERVER_PORT']) && !in_array((int)$_SERVER['SERVER_PORT'], [80,443], true) ? ':'.(int)$_SERVER['SERVER_PORT'] : '';
    return $scheme.'://'.$host.$port.securityAppPath('changeforgottenpwd.php').'?'.http_build_query(['phrase' => $ticket, 'token' => $clubToken], '', '&', PHP_QUERY_RFC3986);
}
