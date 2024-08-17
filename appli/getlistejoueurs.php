<?php
require("configuration.php");
require("bridgette_bdd.php");

$liste = htmlspecialchars( $_GET['liste'] );
$ordre = htmlspecialchars( $_GET['ordre'] );
$filtre = htmlspecialchars( $_GET['filtre'] );

echo getListeJoueurs($liste, $ordre, $filtre);
?>
