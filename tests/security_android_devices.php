<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/security_passwords_support.php';
require __DIR__.'/../appli/security.php';
$count = 0;
function deviceCheck($value, $label) { global $count; $count++; if (!$value) throw new RuntimeException($label); }
$db = new PasswordTestDB();
$player = ['id'=>7, 'email'=>'player@example.org', 'password'=>'hash-original', 'datesupp'=>0, 'prenom'=>'Test', 'nom'=>'Player'];
$db->tables['one_joueurs'] = [$player];
$db->tables['two_joueurs'] = [$player];
$clubOne = securityAndroidDeviceIssue($db, 'one_', $player);
$clubTwo = securityAndroidDeviceIssue($db, 'two_', $player);
foreach ([['one_', $clubOne], ['two_', $clubTwo], ['one_', $clubOne], ['two_', $clubTwo]] as [$club, $saved]) {
    deviceCheck(securityAndroidDeviceResume($db, $club, $club.'joueurs', $saved)['id'] === 7, 'Monday/Tuesday club switching preserves both saved connections');
}
$token = securityAndroidDeviceIssue($db, 'one_', $player);
$key = securityAndroidDeviceKey($token);
deviceCheck(preg_match('/\Aadr1\.[a-f0-9]{64}\z/D', $token) === 1, 'Random secret');
deviceCheck(strlen($key) === 32 && str_contains($key, '_'), 'Fits sessions schema');
deviceCheck(!str_contains($db->rows[$key]['data'], $token), 'Secret not stored');
$before = json_decode($db->rows[$key]['data'], true);
deviceCheck($before['expires'] >= time() + ANDROID_DEVICE_LIFETIME - 1, 'Thirty days');
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token)['id'] === 7, 'Passwordless resume');
$after = json_decode($db->rows[$key]['data'], true);
deviceCheck($after['expires'] >= $before['expires'] && isset($after['revision']), 'Sliding expiry and new revision even same second');
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token)['id'] === 7, 'Lost response does not invalidate secret');
// Emulate a token last used a week ago, then verify a new thirty-day lease.
$weekly = json_decode($db->rows[$key]['data'], true);
$weekly['expires'] = time() + 23 * 86400;
$db->rows[$key] = ['access'=>time() - 7 * 86400, 'data'=>json_encode($weekly)];
securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token);
deviceCheck(json_decode($db->rows[$key]['data'], true)['expires'] >= time() + ANDROID_DEVICE_LIFETIME - 1, 'Weekly use renews for thirty days');
foreach (['7', [], 'adr1.'.str_repeat('a', 64), substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a')] as $bad) {
    deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $bad) === null, 'Malformed/tampered token');
}
deviceCheck(securityAndroidDeviceResume($db, 'two_', 'two_joueurs', $token) === null, 'Other club refused despite same player ID');
deviceCheck(securityAndroidDeviceRevoke($db, 'two_', $token) === 0 && isset($db->rows[$key]), 'Other club cannot revoke');
$db->tables['one_joueurs'][0]['password'] = 'changed';
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Password change invalidates token');
$db->tables['one_joueurs'] = [$player];
$db->tables['one_joueurs'][0]['email'] = 'changed@example.org';
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Identity changed');
$db->tables['one_joueurs'] = [$player];
$db->tables['one_joueurs'][0]['datesupp'] = 1;
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Disabled account');
$db->tables['one_joueurs'] = [];
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Deleted account');
$db->tables['one_joueurs'] = [$player];
$data = json_decode($db->rows[$key]['data'], true); $data['expires'] = time();
$db->rows[$key]['data'] = json_encode($data);
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Expiry boundary');
deviceCheck(securityAndroidDeviceRevoke($db, 'one_', $token) === 7, 'Expired token can be revoked');
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $token) === null, 'Revoked token rejected');
$a = securityAndroidDeviceIssue($db, 'one_', $player);
$b = securityAndroidDeviceIssue($db, 'one_', $player);
deviceCheck($a !== $b, 'Distinct devices have distinct secrets');
securityAndroidDeviceRevoke($db, 'one_', $a);
deviceCheck(securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $b)['id'] === 7, 'Revocation isolated to selected device');
foreach (['delete', 'refresh', 'fail'] as $race) {
    $raced = securityAndroidDeviceIssue($db, 'one_', $player); $db->deviceRace = $race;
    try { securityAndroidDeviceResume($db, 'one_', 'one_joueurs', $raced); deviceCheck(false, 'No false success after race/SQL error'); }
    catch (RuntimeException $error) { deviceCheck(true, 'Atomic renewal refuses conflicting/deleted row or SQL error'); }
    $db->deviceRace = null;
}
$db->rows['ordinary'] = ['access'=>0, 'data'=>'session'];
$db->rows['apk_reserved'] = ['access'=>0, 'data'=>'handoff'];
$db->rows['pwd_reserved'] = ['access'=>0, 'data'=>'reset'];
$db->rows[securityAndroidDeviceKey($b)]['access'] = time() - ANDROID_DEVICE_LIFETIME - 1;
securityAndroidDeviceIssue($db, 'one_', $player);
deviceCheck(!isset($db->rows[securityAndroidDeviceKey($b)]) && isset($db->rows['ordinary'], $db->rows['apk_reserved'], $db->rows['pwd_reserved']), 'Cleanup confined to devices');
echo "$count persistent Android device checks: OK\n";
