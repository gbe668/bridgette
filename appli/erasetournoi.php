<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );

$etat = set_etat( $idtournoi, $st_erased );
echo json_encode( array( 'res'=> $etat ) );
?>
