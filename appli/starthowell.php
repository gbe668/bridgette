<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$paquet = htmlspecialchars( $_GET['paquet'] );
start_howell( $idtournoi, $paquet );
?>
