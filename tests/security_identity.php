<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../appli/security.php';
$file_params = __DIR__.'/absent.json';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SESSION = ['prefix' => 'one_'];
$prefix = 'one_';
require __DIR__.'/../appli/bridgette_bdd.php';
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$_COOKIE['bridgette_user'] = base64_encode(json_encode(['ttt' => 'one_', 'userid' => 42]));
check(getIdent()['userid'] === 0, 'Forged cookie must not authenticate');
$_SESSION['player_identity'] = ['club' => 'one_', 'id' => 42, 'time' => time()];
check(getIdent()['userid'] === 42, 'Server identity accepted');
$_SESSION += ['pseudo' => 'director', 'fonction' => 'directeur', 'auth_club' => 'one_', 'ttlast' => time(), 'codedujour' => '123'];
check(isDirecteur(), 'Bound role accepted');
securitySelectClub(['prefix' => 'one_', 'timex' => time()]);
check(isDirecteur() && getIdent()['userid'] === 42, 'Same-club launcher preserves authentication');
securitySelectClub(['prefix' => 'two_', 'timex' => time()]);
check(!isDirecteur() && getIdent()['userid'] === 0 && !isset($_SESSION['codedujour']), 'Club switch clears all credentials');
$_SESSION += ['pseudo' => 'legacy', 'fonction' => 'admin', 'ttlast' => time()];
check(!isAdmin(), 'Unbound legacy role rejected');
$_SESSION['player_identity'] = ['club' => 'two_', 'id' => 42, 'time' => time() - 31*86400];
check(getIdent()['userid'] === 0, 'Expired identity rejected');
$_SESSION['player_identity'] = ['club' => 'two_', 'id' => 42, 'time' => time() - 29*86400];
$before = $_SESSION['player_identity']['time'];
check(getIdent()['userid'] === 42 && $_SESSION['player_identity']['time'] === $before, 'A simple identity lookup does not extend retention');
securityRememberPlayer();
check($_SESSION['player_identity']['time'] >= time()-1, 'Joining renews the thirty-day window');
$_SESSION['player_identity']['time'] = time() - 31*86400;
securityRememberPlayer();
check(securityPlayerId() === 0, 'An expired identity cannot renew itself');
echo "10 identity/isolation/renewal cases passed\n";
