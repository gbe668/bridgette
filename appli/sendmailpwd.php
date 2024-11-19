<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libmailpwd.php");

if ( isset($_POST['usermail']) ){
	$usermail = stripslashes($_REQUEST['usermail']);
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