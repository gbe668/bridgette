<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../appli/security.php';
$count = 0;
foreach (['', '/appli', '/bridgette/appli'] as $directory) {
    foreach (['', '/', '/loguserin.php'] as $pathInfo) {
        $_SERVER['SCRIPT_FILENAME'] = 'C:/web/appli/apk_bridgette.php';
        $_SERVER['SCRIPT_NAME'] = $directory.'/apk_bridgette.php'.$pathInfo;
        $_SERVER['PATH_INFO'] = $pathInfo;
        if (securityAppPath('loguserin.php') !== $directory.'/loguserin.php' ||
            securityAppPath('apk_bridgette.php') !== $directory.'/apk_bridgette.php') {
            throw new RuntimeException('Invalid redirect for '.$_SERVER['SCRIPT_NAME']);
        }
        $count++;
    }
}
echo "$count redirect path cases passed\n";
