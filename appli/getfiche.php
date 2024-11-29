<?php
require("configuration.php");
require("bridgette_bdd.php");

$idj = isset( $_GET['idj'] ) ? htmlspecialchars( $_GET['idj'] ) : 0;

if ( $idj > 0 ) {
	$dbh = connectBDD();
	$sql = "SELECT id, prenom, nom, telephone, email FROM $tab_joueurs WHERE id = $idj;";
	$res = $dbh->query($sql);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$dbh = null;
}
else $row = [ "id"=> 0 ];
echo json_encode( $row );
?>
