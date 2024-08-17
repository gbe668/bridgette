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

if ( isset($_POST['usermail']) ){
	$usermail = stripslashes($_REQUEST['usermail']);
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
		
		$txt1 = '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>';
		$txt2 = '<p>Cliquez sur <a href="' . $lien . '">ce lien</a> pour accéder à la page de réinitialisation.</p>';
		$txt3 = '<p>Bon bridge !</p>';
		
		$txtbody = $txt1 . $txt2 . $txt3;
		
		$ok = send_mailpwd( $usermail, $txtbody );
		if ( $ok ) {
			// Détruire la session.
			session_destroy();
			// Redirection vers la page de connexion
			header("Location: waitmailpwd.php");
		}
		else {
			$message = "Erreur: le mail n'est pas parti !!!";
		}
	}
	else {
		$message = "L'adresse mail est inconnue !!!";
	}
	$dbh = null;
}
?>
<!DOCTYPE html>
<html>

<head>
	<title>Login</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function gotoforgot() {
	var nextstring = "sendmailpwd.php";
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" />	</p>	
	<h2><?php echo $titre; ?></h2>
	
	<h2>Mot de passe oublié</h2>
	<h3>Réinitialisation du mot de passe</h3>
	<p>Entrez votre adresse mail connue du serveur bridgette de votre club de bridge</p>
	
	<form action="" method="post" name="login">
	<p><input type="email" class="box-input" name="usermail" id="usermail" placeholder="adresse mail" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 5000);</script>
	</p>
	
	<p><input type="submit" name="submit" class="mySmallButton"></p>
	</form>
	<p>Notez que l'envoi de votre demande de réinitialisation peut prendre plusieurs secondes</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	<script>
	$("#usermail").focus();
	</script>
</body>
</html>