<?php
require("configuration.php");
require("bridgette_bdd.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

// Test taille table de données / taille max allouée
function compute_sizedb() {
	global $db_name, $prefix;

	$sql_sizedb = "SELECT table_name AS 'Table',
		ROUND(((data_length + index_length) / 1024), 3) AS 'Size' FROM information_schema.TABLES
		WHERE table_schema ='$db_name' AND table_name like '$prefix%'
		ORDER BY (data_length + index_length) DESC;";
	$nomtables = Array();
	$dimtables = Array();
	$i =0;
	$totalsize = 0;
	$dbh = connectBDD();
	$results = $dbh->query( $sql_sizedb );
	while ( $ligne = $results->fetch(PDO::FETCH_ASSOC) ) {
		$nomtables[ $i ] = $ligne[ 'Table' ];
		$dimtables[ $i ] = $ligne[ 'Size' ];
		$totalsize += $dimtables[ $i ];
		$i++;
	}
	$dbh = null;
	return $totalsize;
}
function send_mailwarning( $sujet, $body ) {
	global $config;
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
	$mail->Password   = $config['mail_Password'];      // identique bridghome
	$mail->Port       = $config['mail_Port'];
	
	//Recipients
	$mail->setFrom( $config['mail_setFrom'], 'Bridgette' );
	$mail->addAddress( $config['mail_admin'] );

	//Content
	$mail->IsHTML(true); // envoyer au format html, passer a false si en mode texte 
	$mail->Subject = $sujet; // sujet
	$mail->Body = $body; 

	try	{
		$mail->Send();
		$res = "OK";
	}
	catch (Exception $e) {
		$res = "Mailer Error: ".$mail->ErrorInfo;
		writelogerrors( $res );
	}
	return $res;
}

$actualSizeBDD = compute_sizedb();
if ( $actualSizeBDD > 0.9*$maxSizeBDD ) {
	$msgSizeBDD = "<p>Taille base de données: $actualSizeBDD ko<br/>soit plus de 90% de la taille maximum allouée<br/>Warning envoyé à l'administrateur !</p>";
	$body = "<p>Site bridgette:  $urlbridgette</p>";
	$body .= "Attention, la taille de la base de données du site est maintenant $actualSizeBDD ko<br/>soit plus de 90% de la taille maximum allouée pour la base de données: $maxSizeBDD ko.</p>";
	$body .= "<p>Il est peut-être temps de supprimer les anciens tournois. Consultez la section Statistiques de la page administrateur !</p>";
	send_mailwarning( "Warning base de données bridgette", $body );
}
else $msgSizeBDD = "";
?>
<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
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
function sendresults() {
	$("#msg").text("L'envoi des mails peut prendre quelques secondes, patientez.");
	message = $("#message").val();
	message = message.replace( /\r?\n/g, '<br />' );
	console.log( "message:", message );
	//$("#msg").html( message );
	$.get( "sendresults.php?", {idtournoi:idtournoi, message:message}, function(text) {
		$("#msg").html(text + " Envoi terminé, vous pouvez retourner à la page d'accueil.");
		//gotoindex();
	},"text")
	.done( function() {  } )
	.fail( function( jqxhr, settings, ex ) {
		$("#msg").html('Erreur: '+ ex  ); 
	} );
};
function reload() {
	var nextstring = "bridge44mails.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<body>
	<?php
	print $msgSizeBDD;
	
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$t = readTournoi( $idtournoi );
	$datef = $t[ 'datef' ];
	$ndonnes = $t[ 'ndonnes' ];
	
	$screenw = htmlspecialchars( $_GET['w'] );
	?>
	
	<script>
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	if ( isNaN( screenw ) ) reload();
	
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	</script>
	
	<div style="text-align: center">
	
	<?php
	print htmlDisplayResultatsTournoi($idtournoi, $screenw);
	?>
	
	<h3>... résultats définitifs ...</h3>
	<div id="divsendresults">
	<p>Envoi des résultats aux participants.</p>
	<textarea id="message" name="message" Cols="40" Rows="5" placeholder="Un petit mot aux joueurs pour présenter les résultats ..."></textarea>
	<p><button class="myButton" onclick="sendresults()">Envoyer les résultats</button></p>
	</div>

	<p id='msg'>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	
	<script>
	$("#message").focus();
	</script>
	
</body>
</html>