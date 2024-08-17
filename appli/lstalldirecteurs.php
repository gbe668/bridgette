<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}

$ids = array();
$pseudos = array();
$droits = array();

$dbh = connectBDD();
$sql = "SELECT count(*) FROM $tab_directeurs;";
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 1 ) { 	// masque zorglub
	$sth = $dbh->query( "SELECT * FROM $tab_directeurs;" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$nbl--;
	$nba = 0;	// nb admin
	$nbd = 0;	// nb directeurs
	$nbf = 0;
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$ids[$i] = $row['id'];
		$pseudos[$i] = $row['pseudo'];
		$droits[$i]  = $row['droits'];
		if ( $droits[$i] == 'admin' ) $nba++;
		if ( $droits[$i] == 'directeur' ) $nbd++;
		if ( $droits[$i] == 'formateur' ) $nbf++;
	}
};
$dbh = null;

echo json_encode( array( 'nbl'=>$nbl, 'nba'=>$nba, 'nbd'=>$nbd, 'nbf'=>$nbf, 'ids'=>$ids, 'pseudos'=>$pseudos, 'droits'=>$droits ) );

?>
