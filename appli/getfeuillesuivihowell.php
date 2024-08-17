<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtype = htmlspecialchars( $_GET['idtype'] );
$paquet = htmlspecialchars( $_GET['paquet'] );
$first  = htmlspecialchars( $_GET['first'] );

$feuille = getfeuillesuivihowell( $idtype, $paquet, $first );

echo json_encode( array( 'feuille'=>$feuille ) );
?>
