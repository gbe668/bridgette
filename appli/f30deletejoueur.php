<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function deleteJoueur( $idj ) {
	global $tab_joueurs;
	if ( $idj ) {
		$dbh = connectBDD();
		$sql = "DELETE from $tab_joueurs where id='$idj';";
		$sth = $dbh->query( $sql );
		$result['success'] = 1;
		$result['msg'] = "Joueur supprimé.";
		$dbh = null;
	}
	else {
		$result['success'] = 0;
		$result['msg'] = "Erreur id !";
	}
	return $result;
};

// Fetching Values From URL

$idjoueur = htmlspecialchars( $_GET['idjoueur'] );
$res = deleteJoueur( $idjoueur );

echo json_encode( $res );
?>
