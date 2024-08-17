<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}

function deleteDirecteur( $id ) {
	global $tab_directeurs;
	$dbh = connectBDD();
	$sql = "DELETE from $tab_directeurs where id='$id';";
	$dbh->query( $sql );
	$dbh = null;
};

// Fetching Values From URL

$id = htmlspecialchars( $_GET['id'] );
// précaution pour éviter de supprimer l'administrateur
if ( $id > 1 ) deleteDirecteur( $id );

echo "ok";
?>
