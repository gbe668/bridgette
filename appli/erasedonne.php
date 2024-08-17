<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['z_etui'] );
	
$dbh = connectBDD();
$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idtournoi' and etui='$etui';";
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$res = $dbh->query( "SELECT * FROM $tab_donnes where idtournoi = '$idtournoi' and etui='$etui';" );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$indices[$i] = $row['id'];
	}
	for ( $i = 0; $i < $nbl; $i++ ) {
		$ii = $indices[$i];
		$sql = "DELETE from $tab_donnes where id ='$ii';";
		$res = $dbh->query($sql);
	}
};

$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = '$idtournoi' and etui='$etui';";
$res = $dbh->query($sql);
if ( $res->fetchColumn() > 0 ) {
	$sql = "SELECT * FROM $tab_diagrammes where idtournoi = '$idtournoi' and etui='$etui';";
	$res = $dbh->query($sql);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$id = $row['id'];
	$sql = "DELETE from $tab_diagrammes where id ='$id';";
	$res = $dbh->query($sql);
};

$dbh = null;

echo json_encode( array( 'nbl'=>$nbl ) );
?>
