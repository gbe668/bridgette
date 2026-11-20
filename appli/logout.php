<?php
// deconnexion directeur tournoi, formateur, admin
require("configuration.php");
securityCsrfRequire();
unset($_SESSION['csrf']);
unset( $_SESSION['pseudo'] );
unset( $_SESSION['fonction'] );
// Redirection vers la page de connexion
header("Location: bridgette.php");
?>
