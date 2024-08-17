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
$sql = "SELECT count(*) FROM $tab_directeurs where droits <> 'admin';";
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$sth = $dbh->query( "SELECT * FROM $tab_directeurs where droits <> 'admin';" );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$ids[$i] = $row['id'];
		$pseudos[$i] = $row['pseudo'];
		$droits[$i]  = $row['droits'];
	}
};
$dbh = null;

echo json_encode( array( 'nbl'=>$nbl, 'ids'=>$ids, 'pseudos'=>$pseudos, 'droits'=>$droits ) );

?>
