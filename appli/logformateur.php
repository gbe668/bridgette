<?php
require("configuration.php"); require("bridgette_bdd.php");
if (isset($_POST['pseudo'])) {
    $pseudo = is_string($_POST['pseudo']) ? htmlspecialchars(stripslashes($_POST['pseudo'])) : '';
    $row = bridgettePasswordAccount(connectBDD(), $tab_directeurs, 'pseudo', $pseudo, $_POST['password'] ?? null, 'formateur');
    if ($row) {
        session_regenerate_id(true);
        $_SESSION['auth_club'] = $prefix; $_SESSION['pseudo'] = $pseudo; $_SESSION['fonction'] = 'formateur';
        
        header('Location: bridgform10.php'); $message = 'OK.';
    } else $message = 'Le pseudo, le mot de passe ou le rôle est incorrect.';
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
</script>


<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2>Espace de formation</br>réservé aux formateurs du club</h2>
	<h3>Identifiez-vous</h3>
	<?php
	if( $site == 2 ) print "<p>Site de démonstration</br>Pseudo: formateur</br>Mot de passe: bridgette</p>";
	else print "<p>&nbsp;</p>";
	?>

	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
	<p><input type="text" class="box-input" name="pseudo" id="pseudo" placeholder="Pseudo formateur" required ></p>
	<p><input type="password" class="box-input" name="password" placeholder="Mot de passe" required ></p>
	<p><input type="hidden" name="fonction" value="formateur"></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 3000);</script>
	</p>
	
	<p><input type="submit" value="Connexion " name="submit" class="mySmallButton"></p>
	
	</form>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	<script>
	$("#pseudo").focus();
	</script>
</body>
</html>