<?php
require("configuration.php"); require("bridgette_bdd.php");
if (isset($_POST['pseudo'])) {
    $pseudo = is_string($_POST['pseudo']) ? htmlspecialchars(stripslashes($_POST['pseudo'])) : '';
    $row = bridgettePasswordAccount(connectBDD(), $tab_directeurs, 'pseudo', $pseudo, $_POST['password'] ?? null, 'admin');
    if ($row) {
        session_regenerate_id(true);
        $_SESSION['auth_club'] = $prefix; $_SESSION['pseudo'] = $pseudo; $_SESSION['fonction'] = 'admin';
        logevent('logadmin');
        header('Location: admin.php'); $message = 'OK.';
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
	<?php
	?>

	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h1>Administration du site</h1>
	<h2>Identifiez-vous</h2>
	<p>&nbsp;</p>

	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
	<p><input type="text" class="box-input" name="pseudo" id="pseudo" placeholder="Pseudo administrateur" required ></p>
	<p><input type="password" class="box-input" name="password" placeholder="Mot de passe" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);</script>
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