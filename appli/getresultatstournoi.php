<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );
$w = htmlspecialchars( $_GET['w'] );

$t = readTournoi( $idt );
$str = htmlDisplayResultatsTournoi($idt, $w);

echo json_encode( array( 'etat'=>$t['etat'], 'resultats'=>$str ) );
