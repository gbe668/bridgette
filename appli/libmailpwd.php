<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require_once __DIR__.'/security_password_reset.php';

function send_mailpwd( $usermail, $ticket ) {
	global $config, $token;
	date_default_timezone_set("Europe/Paris");
	
	// construction mail
	$lien = htmlspecialchars(passwordResetUrl($ticket, $token), ENT_QUOTES, 'UTF-8');
	$txt1 = '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>';
	$txt2 = '<p>Cliquez sur <a href="' . $lien . '">ce lien</a> pour accéder à la page de réinitialisation.</p>';
	$txt3 = '<p>Ce lien expire dans une heure et ne peut être utilisé qu’une fois. Une nouvelle demande remplace le lien précédent.</p><p>Bon bridge !</p>';
	
	$body = $txt1 . $txt2 . $txt3;
	
	// envoi mail
	$mail = new PHPMailer(true);
	$mail->CharSet = 'utf-8';
	
	//Server settings
	$mail->SMTPDebug = 0;		//Enable verbose debug output
	$mail->IsSMTP();
	$mail->SMTPAuth   = true;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	// défini dans fichier de configuration
	$mail->Host       = $config['mail_Host'];  
	$mail->Username   = $config['mail_Username'];
	$mail->Password   = $config['mail_Password'];
	$mail->Port       = $config['mail_Port'];
	//$mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true)); // ignorer l'erreur de certificat.
	
	//Recipients
	$mail->setFrom( $config['mail_setFrom'], 'Bridgette' );
	$mail->addAddress( $usermail );               //Name is optional
	//$mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
	//$mail->addReplyTo('bridgette@coiffier.org', 'Bridgette');
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
		error_log('Bridgette reset mail failed');
		$res = false;
	}
	return $res;
}
?>
