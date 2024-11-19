<?php
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
/**
* Decrypt a message
*
* @param string $encrypted - message encrypted with safeEncrypt()
* @param string $key - encryption key
* @return string
*/
function safeDecrypt( $encrypted, $key ) {  
    $decoded = base64_decode($encrypted);
    if ($decoded === false) {
        //throw new Exception('Scream bloody murder, the encoding failed');
        return 'Scream bloody murder, the encoding failed';
    }
    if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
        //throw new Exception('Scream bloody murder, the message was truncated');
        return 'Scream bloody murder, the message was truncated';
    }
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

    $plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
    if ($plain === false) {
		//throw new Exception('the message was tampered with in transit');
		return 'The message was tampered with in transit';
		}
    sodium_memzero($ciphertext);
    sodium_memzero($key);
    return $plain;
};

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function send_mailpwd( $usermail ) {
	global $config, $key, $token;
	date_default_timezone_set("Europe/Paris");
	
	// construction mail
	$enc = bin2hex( safeEncrypt( $usermail, $key) );
	$lien = 'https://' . $_SERVER['HTTP_HOST'];
	$lien .= '/changeforgottenpwd.php?phrase='.$enc . '&token='.$token;
	
	$txt1 = '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>';
	$txt2 = '<p>Cliquez sur <a href="' . $lien . '">ce lien</a> pour accéder à la page de réinitialisation.</p>';
	$txt3 = '<p>Bon bridge !</p>';
	
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
		echo "<p>Mailer Error: " . $mail->ErrorInfo . "</p>";
		$res = false;
	}
	return $res;
}
?>