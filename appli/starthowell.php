<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$paquet = htmlspecialchars( $_GET['paquet'] );
logevent("starthowell ".$idtournoi." paq=".$paquet);

start_howell( $idtournoi, $paquet );
?>
