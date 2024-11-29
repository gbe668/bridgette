<?php
require("configuration.php");
require("bridgette_bdd.php");

/* Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}
*/
// Fetching Values From URL
//	var dataString = 'strname=' + strname;
$strname = htmlspecialchars( $_GET['strname'] );
$likename = "%" . $strname . "%";

$idj = array();
$numj = array();
$jouj = array();
$genj = array();
$prej = array();
$nomj = array();
$emaj = array();
$datesupp = array();
$nomcomplet = array();

//$sql1 = "SELECT count(*) FROM $tab_joueurs where joueur like '$likename' and datesupp = 0;";
//$sql2 = "SELECT * FROM $tab_joueurs where joueur like '$likename' and datesupp = 0 order by nom;";
$sql1 = "SELECT count(*) FROM $tab_joueurs where (nom like '$likename' or prenom like '$likename') and datesupp = 0;";
$sql2 = "SELECT * FROM $tab_joueurs where (nom like '$likename' or prenom like '$likename') and datesupp = 0 order by nom;";
	
$dbh = connectBDD();
$res = $dbh->query( $sql1 );
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$results = $dbh->query( $sql2 );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $results->fetch(PDO::FETCH_ASSOC);
		$idj[$i] = $row[ 'id' ];
		$numj[$i] = $row[ 'numero' ];
		$jouj[$i] = $row[ 'joueur' ];
		$genj[$i] = $row[ 'genre' ];
		$prej[$i] = $row[ 'prenom' ];
		$nomj[$i] = $row[ 'nom' ];
		$emaj[$i] = $row[ 'email' ];
		$datesupp[$i] = $row[ 'datesupp' ];
		$nomcomplet[$i] = $prej[$i] . " " . $nomj[$i];
	}
};
$dbh = null;

echo json_encode( array( 'nbl'=>$nbl, 'idj'=>$idj, 'numj'=>$numj, 'jouj'=>$jouj, 'genj'=>$genj, 'prej'=>$prej, 'nomj'=>$nomj, 'emaj'=>$emaj, 'datesupp'=>$datesupp, 'nomcomplet'=>$nomcomplet ) );

?>
