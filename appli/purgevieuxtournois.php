<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}

$oldtournois = htmlspecialchars( $_GET['n'] );
$nbl = 0;

$dbh = connectBDD();
$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed';";
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) {
	// obtention des id tournoi
	$sth = $dbh->query( "SELECT id FROM $tab_tournois where etat = '$st_closed' order by tournoi asc;" );
	$nbl = min( $nbl, $oldtournois );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		del_Tournoi( $row[ 'id' ] );		
	}
	// purge des tournois
	for ( $i = 0; $i < $nbl; $i++ ) {
	}
};
$dbh = null;
echo "$nbl tournois purgés.";
?>
