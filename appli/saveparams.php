<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	echo "Durée session directeur écoulée !"; 
}
else {
	try {
		clubParametersSave($file_params, $_POST['jsonparms'] ?? null);
		logevent("saveparams");
		echo "Enregistrement terminé.";
	}
	catch (InvalidArgumentException $e) {
		http_response_code(400);
		echo $e->getMessage();
	}
	catch (Throwable $e) {
		http_response_code(500);
		echo "Erreur écriture fichier de paramètres !";
	}
}
?>
