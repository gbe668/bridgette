<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['etui'] );

$t = readTournoi( $idtournoi );
if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
	$ordre = "pointsIMP";
}
else {
	$ordre = "points";
}

$str = htmlResultatDonne($idtournoi, $etui, 0, $ordre);
$diags = existeDiagramme($idtournoi, $etui);
$str .= "<p id='ndiag_0' hidden>" . $diags . "</p>";
if ( $diags == null ) $str .= "<p>Diagrammes non enregistrés</p>";

echo $str;
?>
