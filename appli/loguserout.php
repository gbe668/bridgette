<?php
require 'configuration.php';
securityCsrfRequire();
unset($_SESSION['csrf']);
unset($_SESSION['player_identity'], $_SESSION['android_ticket_hash'], $_SESSION['userid'], $_SESSION['username'], $_SESSION['usermail'], $_SESSION['login_return'], $_SESSION['codedujour']);
session_regenerate_id(true);
setcookie('bridgette_user', '', time() - 3600);
header('Location: bridgette.php');
exit;
