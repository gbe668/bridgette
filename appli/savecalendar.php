<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	echo "Durée session directeur écoulée !"; 
}
else {
	$jsoncalendar  = $_GET['jsoncal'];
	if ( file_put_contents( $file_calendar, $jsoncalendar ) ){
		echo "Enregistrement terminé.";
	}
	else {
		echo "Erreur écriture fichier calendrier !";
	}
}
?>
