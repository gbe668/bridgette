<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
$oldtable = $_GET['oldtable'];
$newtable = $_GET['newtable'];
$oldplace = $_GET['oldplace'];
$newplace = $_GET['newplace'];

if ( ($oldtable == $newtable) && ($oldplace == $newplace) ) {
	// on ne fait rien
	$ok = "Position joueur " . $newtable . " re-sélectionnée";
}
else {
	$dbh = connectBDD();
	$sth = $dbh->query( "SELECT * FROM $tab_connexions" );
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$stconn[$i] = $row[ 'stconnexion' ];
	}
	
	if ( $oldtable <> 0 ) {
		// désélection de l'ancien numéro
		$sql = "UPDATE $tab_connexions SET stconnexion = '$cnx_ko' where id = '$oldtable';";
		$res = $dbh->query($sql);
	}
	if ( $stconn[ $newtable ] == $cnx_ko ) {
		// case grise, on passe à case occupée
		$sql = "UPDATE $tab_connexions SET stconnexion = '$cnx_ok' where id = '$newtable';";
		$res = $dbh->query($sql);
			
		$ok = "Table " . $newtable . " sélectionnée";
	}
	else {
		// table déjà sélectionnée par ailleurs
		$ok = "Erreur: table " . $newtable . " déjà occupée";
	}
	$dbh = null;
	}

echo $ok;
?>
