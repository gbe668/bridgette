<?php
// Exemple : adapter ces deux valeurs à l'installation.
$prefix = 'ttt_';
$location = 'https://bridge.example.org/appli';
$token = base64_encode(json_encode(['prefix' => $prefix, 'timex' => time()]));
$transfer = rtrim($location, '/') . '/bridgette.php?token=' . rawurlencode($token);
if (isset($_GET['cmd']) && is_string($_GET['cmd'])) {
    $transfer .= '&cmd=' . rawurlencode($_GET['cmd']);
}
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bridg’ette</title></head>
<body><h1>Club de bridge</h1>
<p><a href="<?= htmlspecialchars($transfer, ENT_QUOTES, 'UTF-8') ?>">Accéder à Bridg’ette</a></p>
</body></html>
