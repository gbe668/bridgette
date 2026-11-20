<?php
require("configuration.php"); require("bridgette_bdd.php");
$usermail = '';
if (isset($_POST['usermail'])) {
    $usermail = is_string($_POST['usermail']) ? htmlspecialchars(stripslashes($_POST['usermail'])) : '';
    $row = bridgettePasswordAccount(connectBDD(), $tab_joueurs, 'email', $usermail, $_POST['password'] ?? null);
    if (!$row) $message = "L'adresse mail ou le mot de passe est incorrect.";
    else {
        setIdent($row['id']);
        if (isset($_SESSION['login_return'])) {
            $return = $_SESSION['login_return']; unset($_SESSION['login_return']);
            header('Location: '.$return); exit;
        }
        header('Location: '.(isset($_GET['noreturn']) ? 'bridge25.php?noreturn' : 'bridge60.php'));
        $message = 'OK.';
    }
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
	
	<h2>Identifiez-vous</h2>

	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
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