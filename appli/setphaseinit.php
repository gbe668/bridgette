<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );

$etat = set_etat( $idtournoi, $st_phase_init );
echo json_encode( array( 'res'=> $etat ) );
?>
