<?php
require("configuration.php");
require("bridgette_bdd.php");

$hexkey = "b1208554230fa605d3aaf1611f1093024394b5d97a4f4628ed46c25736048f39";
$key = hex2bin( $hexkey );
/**
* Encrypt a message
*
* @param string $message - message to encrypt
* @param string $key - encryption key
* @return string
*/
//
// extension sodium à activer: dans php.ini extension=sodium
//In \xampp\apache\conf\extra\httpd-xampp.conf
//
//Find:
//# PHP-Module setup
//Add:
//LoadFile "/xampp/php/libsodium.dll"
//
function safeEncrypt( $message, $key ) {
    $nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
    $cipher = base64_encode( $nonce . sodium_crypto_secretbox( $message, $nonce, $key ) );
    sodium_memzero($message);
    sodium_memzero($key);
    return $cipher;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function send_mailpwd( $qui, $body ) {
	date_default_timezone_set("Europe/Paris");
	$mail = new PHPMailer(true);
	$mail->CharSet = 'utf-8';
	
	//Server settings
	$mail->SMTPDebug = 0;		//Enable verbose debug output
	$mail->IsSMTP();
	$mail->Host       = "ssl0.ovh.net";  
	$mail->SMTPAuth   = true;
	$mail->Username   = "bridgette@coiffier.org";
	$mail->Password   = "2gsJ@UR3W7MV";      
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	//$mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true)); // ignorer l'erreur de certificat.
	$mail->Port       = 465;
	
	//Recipients
	$mail->setFrom( 'bridgette@coiffier.org', 'Bridgette' );
	$mail->addAddress( $qui );               //Name is optional
	//$mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
	$mail->addReplyTo('bridgette@coiffier.org', 'Bridgette');
	//$mail->addCC('cc@example.com');
	//$mail->addBCC('bcc@example.com');

	//Attachments
	//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
  
	//Content
	$mail->IsHTML(true); // envoyer au format html, passer a false si en mode texte 
	$mail->Subject = "Bridg'ette: réinitialisation de mot de passe"; // sujet
	$mail->Body = $body; 
	//$mail->AltBody = $altbody; //Body au format texte

	try	{
		$mail->Send();
		$res = true;
	
		//$mail->clearAddresses();
		//$mail->smtpClose();
	}
	catch (Exception $e) {
		echo "<p>Mailer Error: " . $mail->ErrorInfo . "</p>";
		$res = false;
	}
	return $res;
}

$login = 0;		// signale une ereur

if ( isset($_GET['mailuser']) ){
	$usermail = stripslashes($_GET['mailuser']);
	$usermail = htmlspecialchars($usermail);
	
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail';";
	$res = $dbh->query($sql);
	if($res->fetchColumn() == 1){
		//
		//Envoi du mail de réinitialisation
		$enc = bin2hex( safeEncrypt( $usermail, $key) );
		$lien = 'https://' . $_SERVER['HTTP_HOST'];
		$lien .= '/changeforgottenpwd.php?phrase=' . $enc;
		//$lien = $relpgm.'changeforgottenpwd.php?phrase='.$enc;
		
		$txt1 = '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>';
		$txt2 = '<p>Cliquez sur <a href="' . $lien . '">ce lien</a> pour accéder à la page de réinitialisation.</p>';
		$txt3 = '<p>Bon bridge !</p>';
		
		$txtbody = $txt1 . $txt2 . $txt3;
		
		$ok = send_mailpwd( $usermail, $txtbody );
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