<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['z_etui'] );
	
$dbh = connectBDD();
$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = ? and etui=?;";
$sqlBound = [$idtournoi, $etui];
$res = securityQuery($dbh, $sql, $sqlBound);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$res = securityQuery($dbh, "SELECT * FROM $tab_donnes where idtournoi = ? and etui=?;", [$idtournoi, $etui]);
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$indices[$i] = $row['id'];
	}
	for ( $i = 0; $i < $nbl; $i++ ) {
		$ii = $indices[$i];
		$sql = "DELETE from $tab_donnes where id =?;";
		$sqlBound = [$ii];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
};

$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = ? and etui=?;";
$sqlBound = [$idtournoi, $etui];
$res = securityQuery($dbh, $sql, $sqlBound);
if ( $res->fetchColumn() > 0 ) {
	$sql = "SELECT * FROM $tab_diagrammes where idtournoi = ? and etui=?;";
	$sqlBound = [$idtournoi, $etui];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$id = $row['id'];
	$sql = "DELETE from $tab_diagrammes where id =?;";
	$sqlBound = [$id];
	$res = securityQuery($dbh, $sql, $sqlBound);
};

$dbh = null;

echo json_encode( array( 'nbl'=>$nbl ) );
?>
