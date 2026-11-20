<?php
require 'configuration.php'; require 'bridgette_bdd.php';
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST'); http_response_code(405);
    echo json_encode(['login'=>5, 'message'=>'POST requis.']); exit;
}
securityAndroidDeviceTransport();
$action = $_POST['action'] ?? 'resume';
$deviceToken = $_POST['device_token'] ?? null;
if (!in_array($action, ['resume', 'touch', 'revoke'], true)) {
    http_response_code(400); echo json_encode(['login'=>5, 'message'=>'Action incorrecte.']); exit;
}
try {
    $db = connectBDD();
    if ($action === 'revoke') {
        $player = securityAndroidDeviceRevoke($db, $prefix, $deviceToken);
        if ($player && securityNativePlayer() && securityPlayerId() === $player) {
            $_SESSION = ['prefix'=>$prefix, 'timex'=>$_SESSION['timex'], 'ttlast'=>time()];
            session_regenerate_id(true);
        }
        echo json_encode(['login'=>0, 'message'=>'Connexion mémorisée supprimée.']); exit;
    }
    $player = securityAndroidDeviceResume($db, $prefix, $tab_joueurs, $deviceToken);
    if (!$player) {
        http_response_code(401);
        echo json_encode(['login'=>4, 'message'=>'Reconnectez-vous avec votre mot de passe.']); exit;
    }
    if ($action === 'touch') {
        echo json_encode(['login'=>0, 'message'=>'ok']); exit;
    }
    $ticket = securityAndroidIssue($db, $player['id'], $prefix);
    echo json_encode(['login'=>0, 'message'=>'ok', 'userid'=>$ticket,
        'username'=>$player['prenom'].' '.$player['nom'], 'club'=>$titre]);
} catch (Throwable $error) {
    http_response_code($error->getCode() === 409 ? 409 : 503);
    // Never expose token, SQL, database configuration or account details.
    echo json_encode(['login'=>5, 'message'=>'Connexion temporairement indisponible. Réessayez.']);
}
