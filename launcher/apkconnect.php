<?php
// Exemple Android : adapter ces deux valeurs à l'installation.
$prefix = 'ttt_';
$location = 'https://bridge.example.org/appli';
$token = base64_encode(json_encode(['prefix' => $prefix, 'timex' => time()]));
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['token' => $token, 'server' => $location]);
