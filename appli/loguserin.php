<?php
require("configuration.php");
require("bridgette_bdd.php");

if ( isset($_POST['usermail']) ){
	$usermail = stripslashes($_REQUEST['usermail']);
	$usermail = htmlspecialchars($usermail);
	
	$password = stripslashes($_REQUEST['password']);
	$password = htmlspecialchars($password);
	$encodpwd  = hash('sha256', $password);
	
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail' and password='$encodpwd' and datesupp = 0;";
	$res = $dbh->query($sql);
	$n = $res->fetchColumn();
	if($n < 1) {
		$message = "L'adresse mail ou le mot de passe est incorrect.";
	}
	if($n == 1){
		// ok
		$sql = "SELECT * FROM `$tab_joueurs` WHERE email='$usermail' and password='$encodpwd' and datesupp = 0;";
		$res = $dbh->query($sql);
		$row = $res->fetch(PDO::FETCH_ASSOC);
		$userid = $row['id'];
		setIdent( $userid );
		if ( isset($_GET['noreturn']) ) {
			header("Location: bridge25.php?noreturn");
			$message = "OK.";
		}
		else {
			// par défaut
			header("Location: bridge60.php");
			$message = "OK.";
		}
	}
	if($n > 1) {
		$message = "Erreur: $n joueurs avec les mêmes identifiants !";
	}
	$dbh = null;
}
else $usermail="";
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
	
	<h2>Identifiez-vous</h2>

	<form action="" method="post" name="login">
	<p><input type="email" class="box-input" name="usermail" id="usermail" placeholder="adresse mail" value="<?php echo $usermail ?>" required ></p>
	<p><input type="password" class="box-input" name="password" placeholder="Mot de passe" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 2000);</script>
	</p>
	
	<p><input type="submit" value="Connexion " name="submit" class="mySmallButton"></p>
	
	</form>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoforgot()">Mot de passe oublié ?</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	<script>
	$("#usermail").focus();
	</script>
</body>
</html>