<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
logevent("erase tournoi ".$idtournoi);
$res = eraseTournoi( $idtournoi );
echo json_encode( array( 'res'=> $res ) );
?>
