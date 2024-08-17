<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$res = set_etat( $idtournoi, $st_closed );
if ( $res == $st_closed) {
	setTournoi($idtournoi);	//valeurs définitives
}
echo json_encode( array( 'res'=>$res ) );
?>
