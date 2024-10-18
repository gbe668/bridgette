<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );

$res = eraseTournoi( $idtournoi );
echo json_encode( array( 'res'=> $res ) );
?>
