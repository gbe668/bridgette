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
$sql = "SELECT count(*) FROM $tab_tournois where etat = ?;";
$sqlBound = [$st_closed];
$res = securityQuery($dbh, $sql, $sqlBound);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) {
	// obtention des id tournoi
	$sth = securityQuery($dbh, "SELECT id FROM $tab_tournois where etat = ? order by tournoi asc;", [$st_closed]);
	$nbl = min( $nbl, $oldtournois );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		eraseTournoi( $row[ 'id' ] );		
	}
	// purge des tournois
	for ( $i = 0; $i < $nbl; $i++ ) {
	}
};
$dbh = null;
echo "$nbl tournois purgés.";
?>
