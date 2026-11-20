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
// Find an executable assignment, ignoring obsolete values in comments.
$offset = 0; $start = false;
foreach (token_get_all($source) as $token) {
    if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$apk_code_request') {
        $start = $offset; break;
    }
    $offset += strlen(is_array($token) ? $token[1] : $token);
}
if ($start === false) throw new RuntimeException('Executable version assignment not found');
$end = strpos($source, '$joueur = getJoueur', $start);
if ($start === false || $end === false) throw new RuntimeException('Startup block not found');
$startup = substr($source, $start, $end - $start);
foreach ([true, false] as $firstLogin) {
    foreach ([8, 9, 12, 13, 14] as $version) {
        $_SESSION = ['prefix' => 'test_'];
        $_GET = ['apk_code' => (string)$version, 'apk_name' => 'test'];
        eval($startup);
        if ($apk_code_request !== 13 || ($_SESSION['apk_code'] ?? null) !== (string)$version ||
            (($_SESSION['apk_code'] < $apk_code_request) !== ($version < 13))) {
            throw new RuntimeException('Version lost: '.($firstLogin ? 'first login' : 'refresh').', '.$version);
        }
    }
}
echo "10 Android version startup cases passed (legacy, below/at/above threshold 13)\n";
