<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtype = $_GET['idtype'];
$paquet = $_GET['paquet'];

$tt = gettypetournoi( $idtype );
$genre  = $tt['genre'];
$npaires = $tt['npaires'];
$ntables = $tt['ntables'];

$tour = getMinTour( $ntables );

if ( $genre == $t_howell ) {
	$positions = htmlPositionHowell($idtype, $npaires, $tour, $paquet);
}
else {
	$positions = "Mitchell";
}

echo json_encode( array( 'tour'=>$tour, 'positions'=>$positions ) );
?>
