<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../appli/security.php';
require __DIR__.'/security_passwords_support.php';
$checks = 0;
function passwordCheck($ok, $label) { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
foreach (['simple', "é & < > ' \" \\ +", str_repeat('x', 72).'a', str_repeat('x', 72).'b', "with\0zero", str_repeat('é',512)] as $raw) {
    $hash = bridgettePasswordHash($raw);
    passwordCheck(strlen($hash) === 63 && str_starts_with($hash, 'b2:'), 'Fits existing schema');
    passwordCheck(bridgettePasswordVerify($raw, $hash), 'Exact input verified');
    passwordCheck(!bridgettePasswordVerify($raw.'!', $hash), 'Different input denied, including beyond 72 bytes');
    passwordCheck(!bridgettePasswordNeedsUpgrade($hash), 'Current cost');
}
foreach ([[], null, '', str_repeat('x',1025)] as $bad) passwordCheck(!bridgettePasswordInput($bad), 'New malformed/empty/long password denied');
passwordCheck(bridgettePasswordHash('same') !== bridgettePasswordHash('same'), 'Random salt');
$raw = "old & < > ' \\";
$legacy = hash('sha256', htmlspecialchars(stripslashes($raw)));
passwordCheck(bridgettePasswordVerify($raw, $legacy), 'Historic transformations');
passwordCheck(!bridgettePasswordVerify('wrong', $legacy), 'Bad legacy password');
passwordCheck(!bridgettePasswordVerify('anything', null) && !bridgettePasswordVerify('anything','corrupt'), 'Null/malformed stored data');
$db = new PasswordTestDB();
$account = ['id'=>7,'email'=>'player@example.org','password'=>$legacy,'datesupp'=>0];
$db->tables['one_joueurs'] = [$account];
passwordCheck(bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],'bad') === null, 'Wrong login');
passwordCheck($db->tables['one_joueurs'][0]['password'] === $legacy, 'Wrong login never migrates');
$row = bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],$raw);
passwordCheck($row && bridgettePasswordVerify($raw,$row['password']) && str_starts_with($row['password'],'b2:'), 'Successful legacy migration');
$hash = $row['password'];
passwordCheck(bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],$raw)['password'] === $hash, 'Modern login does not rewrite');
passwordCheck(bridgettePasswordAccount($db,'two_joueurs','email',$account['email'],$raw) === null, 'Club isolation');
$db->tables['one_joueurs'][0]['datesupp'] = 1;
passwordCheck(bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],$raw) === null, 'Disabled account');
$db->tables['one_joueurs'] = [$account, $account];
passwordCheck(bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],$raw) === null, 'Ambiguous account');
$db->tables['one_joueurs'] = [$account]; $db->race = hash('sha256','newer');
passwordCheck(bridgettePasswordAccount($db,'one_joueurs','email',$account['email'],$raw) === null, 'Concurrent password change denies stale login');
passwordCheck($db->tables['one_joueurs'][0]['password'] === $db->race, 'Migration preserves concurrent password');
$db->race = null;
$db->tables['one_directeurs'] = [['id'=>1,'pseudo'=>'Boss','droits'=>'admin','password'=>$legacy]];
passwordCheck(bridgettePasswordAccount($db,'one_directeurs','pseudo','Boss',$raw,'directeur') === null, 'Wrong role');
passwordCheck($db->tables['one_directeurs'][0]['password'] === $legacy, 'Wrong role never migrates');
passwordCheck(bridgettePasswordAccount($db,'one_directeurs','pseudo','Boss',$raw,'admin') !== null, 'Responsible account migration');
passwordCheck(bridgettePasswordAccount($db,'one_directeurs','unsafe','Boss',$raw) === null, 'Column allowlist');
$empty = hash('sha256','');
passwordCheck(bridgettePasswordVerify('',$empty), 'Preserve existing empty legacy credentials, never create new ones');
echo "$checks password storage/migration checks passed\n";
