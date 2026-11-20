<?php
// Persistent native credentials are separate from PHP sessions and handoff tickets.
// Reuse the technical sessions table without assuming a transactional engine.
const ANDROID_DEVICE_LIFETIME = 30 * 86400;

function securityAndroidDeviceKey($token) {
    return 'adr_'.substr(hash('sha256', $token), 0, 28);
}

function securityAndroidDeviceTransport() {
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$local && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
        securityDeny('HTTPS requis pour la connexion persistante.', 426);
    }
}

function securityAndroidDeviceIssue($db, $club, $player) {
    $token = 'adr1.'.bin2hex(random_bytes(32));
    $now = time();
    $data = json_encode(['kind'=>'android_device', 'hash'=>hash('sha256', $token),
        'club'=>$club, 'player'=>(int)$player['id'], 'email'=>$player['email'],
        'account'=>hash('sha256', (string)$player['password']), 'expires'=>$now + ANDROID_DEVICE_LIFETIME]);
    securityQuery($db, "DELETE FROM sessions WHERE LEFT(id, 4) = 'adr_' AND access < ?", [$now - ANDROID_DEVICE_LIFETIME]);
    securityQuery($db, 'INSERT INTO sessions (id, access, data) VALUES (?, ?, ?)',
        [securityAndroidDeviceKey($token), $now, $data]);
    return $token;
}

function securityAndroidDeviceRecord($db, $club, $token) {
    if (!is_string($token) || !preg_match('/\Aadr1\.[a-f0-9]{64}\z/D', $token)) return null;
    $key = securityAndroidDeviceKey($token);
    $row = securityQuery($db, 'SELECT data FROM sessions WHERE id = ?', [$key])->fetch(PDO::FETCH_ASSOC);
    $data = $row ? json_decode($row['data'], true) : null;
    if (!is_array($data) || ($data['kind'] ?? '') !== 'android_device' ||
        ($data['club'] ?? null) !== $club || !is_int($data['player'] ?? null) || $data['player'] <= 0 ||
        !is_string($data['hash'] ?? null) || !hash_equals($data['hash'], hash('sha256', $token))) return null;
    return [$key, $row['data'], $data];
}

function securityAndroidDeviceResume($db, $club, $table, $token) {
    $record = securityAndroidDeviceRecord($db, $club, $token);
    if (!$record) return null;
    [$key, $raw, $data] = $record;
    if (($data['expires'] ?? 0) <= time()) return null;
    $player = securityQuery($db, "SELECT * FROM `$table` WHERE id = ? AND datesupp = 0", [$data['player']])->fetch(PDO::FETCH_ASSOC);
    if (!$player || ($data['email'] ?? null) !== $player['email'] ||
        !is_string($data['account'] ?? null) || !hash_equals($data['account'], hash('sha256', (string)$player['password']))) return null;
    $now = time();
    // Keep the secret stable: a lost HTTP response must not strand the device.
    // A record revision guarantees a changed row even for calls in one second,
    // without extending expiry beyond thirty days from actual use.
    $data['expires'] = $now + ANDROID_DEVICE_LIFETIME;
    $data['revision'] = bin2hex(random_bytes(8));
    $changed = securityQuery($db, 'UPDATE sessions SET access = ?, data = ? WHERE id = ? AND BINARY data = ?',
        [$now, json_encode($data), $key, $raw]);
    if ($changed->rowCount() !== 1) throw new RuntimeException('Connexion concurrente ou jeton révoqué.', 409);
    return $player;
}

function securityAndroidDeviceRevoke($db, $club, $token) {
    $record = securityAndroidDeviceRecord($db, $club, $token);
    if (!$record) return 0;
    [$key, $raw, $data] = $record;
    if (securityQuery($db, 'DELETE FROM sessions WHERE id = ? AND BINARY data = ?', [$key, $raw])->rowCount() !== 1) {
        throw new RuntimeException('Révocation concurrente.', 409);
    }
    return $data['player'];
}
