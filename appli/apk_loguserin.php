<?php
require("configuration.php"); require("bridgette_bdd.php");
// POST form body for V3; GET remains available during the installed APK migration.
// Never fall back to URL credentials when a POST body is missing or invalid.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['login'=>1, 'message'=>'Méthode non autorisée.', 'userid'=>0, 'username'=>'anonyme', 'club'=>$titre]);
    exit;
}
$credentials = $method === 'POST' ? $_POST : $_GET;
$rememberDevice = $method === 'POST' && ($credentials['remember_device'] ?? null) === '1';
if ($rememberDevice) securityAndroidDeviceTransport();
$deviceToken = null;
$login = 1; $userid = 0; $username = 'anonyme'; $message = 'Adresse mail manquante.';
if (isset($credentials['mailuser'])) {
    $login = 2; $message = 'Mot de passe manquant !';
    if (isset($credentials['password'])) {
        $email = is_string($credentials['mailuser']) ? htmlspecialchars(stripslashes($credentials['mailuser'])) : '';
        $db = connectBDD();
        $rows = securityQuery($db, "SELECT * FROM `$tab_joueurs` WHERE datesupp = 0 AND email = ?", [$email])->fetchAll(PDO::FETCH_ASSOC);
        $login = 3; $message = 'Adresse mail inconnue ou ambiguë !';
        if (count($rows) === 1) {
            $row = bridgettePasswordAccount($db, $tab_joueurs, 'email', $email, $credentials['password']);
            $login = 4; $message = 'Mot de passe incorrect !';
            if ($row) {
                try {
                    if ($rememberDevice) $deviceToken = securityAndroidDeviceIssue($db, $prefix, $row);
                    $userid = securityAndroidIssue($db, $row['id'], $prefix);
                    $username = $row['prenom'].' '.$row['nom'];
                    $login = 0; $message = 'ok';
                } catch (Throwable $error) {
                    if (!$rememberDevice) throw $error;
                    http_response_code(503);
                    $deviceToken = null; $userid = 0;
                    $login = 5; $message = 'Connexion temporairement indisponible. Réessayez.';
                }
            }
        }
    }
}
header('Cache-Control: no-store'); header('Content-Type: application/json; charset=utf-8');
$answer = ['login'=>$login,'message'=>$message,'userid'=>$userid,'username'=>$username,'club'=>$titre];
if ($deviceToken !== null) $answer['device_token'] = $deviceToken;
echo json_encode($answer);
