<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function restoreJoueur( $idj ) {
	global $tab_joueurs;
	if ( $idj ) {
		$dbh = connectBDD();
		$sql = "UPDATE $tab_joueurs SET datesupp=0 where id='$idj';";
		$sth = $dbh->query( $sql );
		$result['success'] = 1;
		$result['msg'] = "Joueur restauré.";
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
$res = restoreJoueur( $idjoueur );

echo json_encode( $res );
?>
