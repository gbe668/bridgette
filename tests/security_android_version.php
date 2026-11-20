<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
// Run the actual page's startup block, isolating the authentication callback.
// A first native login clears session metadata; a refresh does not.
function securityAndroidRequire() {
    global $firstLogin;
    if ($firstLogin) $_SESSION = ['prefix' => 'test_', 'player_identity' => ['id' => 7]];
    return 7;
}
$source = file_get_contents(__DIR__.'/../appli/apk_bridgette.php');
$start = strpos($source, '$apk_code_request = 9;');
$end = strpos($source, '$joueur = getJoueur', $start);
if ($start === false || $end === false) throw new RuntimeException('Startup block not found');
$startup = substr($source, $start, $end - $start);
foreach ([true, false] as $firstLogin) {
    foreach ([8, 9, 10] as $version) {
        $_SESSION = ['prefix' => 'test_'];
        $_GET = ['apk_code' => (string)$version, 'apk_name' => 'test'];
        eval($startup);
        if (($_SESSION['apk_code'] ?? null) !== (string)$version ||
            (($_SESSION['apk_code'] < $apk_code_request) !== ($version < 9))) {
            throw new RuntimeException('Version lost: '.($firstLogin ? 'first login' : 'refresh').', '.$version);
        }
    }
}
echo "6 Android version startup cases passed\n";
