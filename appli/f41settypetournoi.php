<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$type = htmlspecialchars( $_GET['type'] );
set_typetournoi( $idtournoi, $type );
?>
