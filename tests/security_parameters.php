<?php
require __DIR__.'/../appli/club_parameters.php';
$checks = 0;
function verify($condition) { global $checks; ++$checks; if (!$condition) throw new RuntimeException('Failed check '.$checks); }
function rejects($json) { try { clubParametersDecode($json); } catch (InvalidArgumentException $e) { verify(true); return; } throw new RuntimeException('Unexpected acceptance'); }
$default = clubParametersDefaults();
verify(clubParametersDecode(json_encode($default)) == $default);
verify(!array_key_exists('param2', $default));
$obsolete = $default; $obsolete['param2'] = 0;
verify(!array_key_exists('param2', clubParametersDecode(json_encode($obsolete))));
foreach (glob(__DIR__.'/../appli/clubs/*_parametres.json') as $file) {
    $loaded = clubParametersLoad($file);
    verify(count($loaded) === count($default));
}
foreach (['', 'null', '[]', 'true', '{', '{"checkin":0}', str_repeat(' ',16385)] as $json) rejects($json);
$legacy = clubParametersDecode('{"checkin":"1","checkuser":"2"}');
verify($legacy['checkin'] === 1 && $legacy['checkuser'] === 2 && $legacy['affposprov'] === 1 && $legacy['avanceh'] === 1);
foreach (['checkin'=>[null,true,2,[],"1e0"], 'checkuser'=>[-1,3,"0 OR 1"],
    'dureedonne'=>[4,16,"NaN"], 'maxt'=>[0,101,1.5], 'maxweeks'=>[0,14],
    'mailcopie'=>[[],"x\r\nBcc:y@example.org",'"<script>"@example.org'],
    'opendays'=>[[],(object)['lundi'=>1]], 'unknown'=>[1]] as $key=>$values) {
    foreach ($values as $value) { $bad=$default; $bad[$key]=$value; rejects(json_encode($bad)); }
}
$directory = __DIR__.'/parameters-tmp-'.bin2hex(random_bytes(8));
mkdir($directory);
try {
    $a=$directory.'/abc_parametres.json'; $b=$directory.'/tst_parametres.json';
    verify(clubParametersLoad($a) === $default);
    clubParametersSave($a,json_encode($default));
    clubParametersSave($a,json_encode($obsolete));
    verify(!array_key_exists('param2', json_decode(file_get_contents($a), true)));
    $other=$default; $other['checkuser']=2;
    clubParametersSave($b,json_encode($other));
    verify(clubParametersLoad($a)['checkuser'] === 0 && clubParametersLoad($b)['checkuser'] === 2);
    $before=file_get_contents($a);
    try { clubParametersSave($a,'null'); } catch (InvalidArgumentException $e) {}
    verify(file_get_contents($a) === $before);
    clubParametersSave($a,json_encode($other));
    verify(clubParametersLoad($a)['checkuser'] === 2);
    // Rename failure keeps the destination and removes temporary files.
    mkdir($directory.'/blocked');
    $failed = false;
    try { clubParametersSave($directory.'/blocked',json_encode($default)); } catch (RuntimeException $e) { $failed = true; }
    verify($failed);
    verify(is_dir($directory.'/blocked') && count(glob($directory.'/.params-*')) === 0);
    rmdir($directory.'/blocked');
    file_put_contents($a,'null');
    try { clubParametersLoad($a); verify(false); } catch (InvalidArgumentException $e) { verify(true); }
} finally {
    foreach (glob($directory.'/*') as $file) unlink($file);
    rmdir($directory);
}
echo "$checks parameter checks: OK\n";
