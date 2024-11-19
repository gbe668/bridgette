<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libmailpwd.php");

$login = 0;		// signale une ereur

if ( isset($_GET['mailuser']) ){
	$usermail = stripslashes($_GET['mailuser']);
	$usermail = htmlspecialchars($usermail);
	
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail';";
	$res = $dbh->query($sql);
	if($res->fetchColumn() == 1){
		//Envoi du mail de réinitialisation
		$ok = send_mailpwd( $usermail );
		if ( $ok ) {
			// Détruire la session.
			session_destroy();
			// Redirection vers la page de connexion
			//header("Location: waitmailpwd.php");
			$login = 0;
			$message = "Mail de réinitialisation du mot de passe envoyé.\nConsultez votre messagerie.";
		}
		else {
			$message = "Erreur: le mail n'est pas parti !!!";
			$login = 5;
		}
	}
	else {
		$message = "Adresse mail inconnue !";
		$login = 3;
	}
	$dbh = null;
}
else {
	$message = "Adresse mail manquante.";
	$login = 1;
}
echo json_encode( array( 'login'=>$login, 'message'=>$message ) );
?>