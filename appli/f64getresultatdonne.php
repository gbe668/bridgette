<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['etui'] );
$numNS = htmlspecialchars( $_GET['ns'] );
$numEO = htmlspecialchars( $_GET['eo'] );

$t = readTournoi( $idtournoi );
if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
	$ordre = "pointsIMP";
}
else {
	$ordre = "points";
}

$res1 = htmlResultatDonne($idtournoi, $etui, $numNS, $numEO, $ordre);
[$diags,$dds] = existeDiagramme($idtournoi, $etui);

echo json_encode( array( 'result'=>$res1, 'diags'=>$diags, 'dds'=>$dds ) );
?>
