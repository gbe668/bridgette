<?php
require("configuration.php");
require("bridgette_bdd.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

$strstyle = "<style type='text/css'>.xNum3 {font-size:1em; text-align:center; vertical-align:middle; border:.5pt solid windowtext; background:#E2EFDA; padding-left:10px; padding-right:10px; border-top:none; border-left:none; height:20.0pt; width: 30.0pt;}</style>";

function send_mailpwd( $sujet, $dests, $body ) {
	global $config, $parametres;
	date_default_timezone_set("Europe/Paris");
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
	
	for ( $i = 0; $i < sizeof( $dests ); $i++ ) {
		$mail->addAddress( $dests[$i] );               //Name is optional
	}
	
	//$mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
	//$mail->addReplyTo('bridgette@coiffier.org', 'Bridgette');
	if ( $parametres['mailcopie'] <> "" ) $mail->addCC( $parametres['mailcopie'] );
	$mail->addBCC( $config['mail_admin'] );

	//Attachments
	//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
  
	//Content
	$mail->IsHTML(true); // envoyer au format html, passer a false si en mode texte 
	$mail->Subject = $sujet; // sujet
	$mail->Body = $body; 
	//$mail->AltBody = $altbody; //Body au format texte

	try	{
		$mail->Send();
		$res = "OK";
	}
	catch (Exception $e) {
		$res = "Mailer Error: " . $mail->ErrorInfo;
		writelogerrors( $res."\n"."dests:".json_encode($dests) );
	}
	return $res;
}

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$t = readTournoi( $idtournoi );

$message = $_GET['message'];

// envoi des résultats aux participants
$attach = "<p>$message</p>" . $strstyle;

if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
	//setTournoiIMP( $idtournoi );
	$attach .= htmlDisplayTournoiIMP( $idtournoi );
}
else {
	//setTournoi($idtournoi);
	$attach .= htmlDisplayTournoi( $idtournoi, -1 );
}

//$attach .= htmlDisplayTournoi($idtournoi, -1);
$attach .= "<p>Voir le détail des résultats sur le site <a href=$urlbridgette>$urlbridgette</a>, rubrique '<b>Affichage derniers résultats</b>'.</p>";

$dests = buildDestinatairesResultats($idtournoi);
$sujet = "Bridgette: résultats du tournoi du jour";
$result = send_mailpwd( $sujet, $dests, $attach );

echo $result;
?>
