<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );

$etat = set_etat( $idtournoi, $st_phase_init );

logevent("setphaseinit ".$idtournoi);

echo json_encode( array( 'res'=> $etat ) );
?>
