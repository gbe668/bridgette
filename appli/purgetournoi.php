<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$res = eraseTournoi( $idtournoi );

echo "<p>tournoi purgé: $res</p>";
?>
