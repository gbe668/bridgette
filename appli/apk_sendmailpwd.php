<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libmailpwd.php");
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    passwordResetRequest(connectBDD(), $prefix, $tab_joueurs, $_GET['mailuser'] ?? null, 'send_mailpwd');
} catch (Throwable $e) {
    error_log('Bridgette reset request failed: '.get_class($e));
}
// Installed Volley clients expect these fields and accept login=0.
echo json_encode(['login' => 0, 'message' => PASSWORD_RESET_MESSAGE]);