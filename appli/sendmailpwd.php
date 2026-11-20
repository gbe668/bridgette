<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libmailpwd.php");

if (isset($_POST['usermail'])) {
    try {
        passwordResetRequest(connectBDD(), $prefix, $tab_joueurs, $_POST['usermail'], 'send_mailpwd');
    } catch (Throwable $e) {
        error_log('Bridgette reset request failed: '.get_class($e));
    }
    $message = PASSWORD_RESET_MESSAGE;
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
<?php securityCsrfHead(); ?>
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	bridgetteNavigate( nextstring );
};
function gotoforgot() {
	var nextstring = "sendmailpwd.php";
	bridgetteNavigate( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" />	</p>	
	<h2><?php echo $titre; ?></h2>
	
	<h2>Mot de passe oublié</h2>
	<h3>Réinitialisation du mot de passe</h3>
	<p>Entrez votre adresse mail connue du serveur bridgette de votre club de bridge</p>
	
	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
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