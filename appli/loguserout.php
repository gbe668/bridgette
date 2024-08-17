<?php
// déconnexion joueur
setcookie("bridgette_user", "", time() - 3600);
// Redirection vers la page de connexion
header("Location: bridgette.php");
?>