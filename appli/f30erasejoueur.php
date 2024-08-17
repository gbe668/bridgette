<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function eraseJoueur( $idj ) {
	global $tab_joueurs;
	if ( $idj ) {
		$date = new DateTime();
		$datesupp = $date->getTimestamp();
		$dbh = connectBDD();
		$sql = "UPDATE $tab_joueurs SET datesupp='$datesupp' where id='$idj';";
		$sth = $dbh->query( $sql );
		$result['success'] = 1;
		$result['msg'] = "Joueur effacé !";
		$dbh = null;
	}
	else {
		$result['success'] = 0;
		$result['msg'] = "Erreur id !";
	}
	return $result;
};

// Fetching Values From URL

$idjoueur = $_GET['idjoueur'];
$res = eraseJoueur( $idjoueur );

echo json_encode( $res );
?>
