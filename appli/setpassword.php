<?php
require("configuration.php"); require("bridgette_bdd.php");
if (isset($_POST['pseudo'])) {
    $pseudo = is_string($_POST['pseudo']) ? htmlspecialchars(stripslashes($_POST['pseudo'])) : '';
    $old = $_POST['oldpassword'] ?? null;
    $first = $_POST['newpassword1'] ?? null; $second = $_POST['newpassword2'] ?? null;
    $dbh = connectBDD();
    $rows = securityQuery($dbh, "SELECT * FROM `$tab_directeurs` WHERE pseudo = ?", [$pseudo])->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) !== 1 || !bridgettePasswordVerify($old, $rows[0]['password'])) $message = 'Le pseudo ou le mot de passe est incorrect.';
    elseif (!bridgettePasswordInput($first) || $first !== $second) $message = 'Saisissez deux mots de passe identiques et non vides (maximum 1024 octets).';
    else {
        $row = $rows[0];
        $changed = securityQuery($dbh, "UPDATE `$tab_directeurs` SET password = ? WHERE id = ? AND BINARY password = ?", [bridgettePasswordHash($first), $row['id'], $row['password']]);
        $message = $changed->rowCount() === 1 ? 'Mot de passe changé avec succès.' : 'Le compte a changé. Recommencez.';
        if ($changed->rowCount() === 1) unset($_SESSION['pseudo'], $_SESSION['fonction'], $_SESSION['auth_club']);
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
	<h1>Changement de mot de passe</h1>

	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
	<p><input type="text" class="box-input" name="pseudo" placeholder="Votre pseudo" required ></p>
	<p><input type="password" class="box-input" name="oldpassword" placeholder="Ancien mot de passe" required ></p>
	<p><input type="password" class="box-input" name="newpassword1" placeholder="Nouveau mot de passe" required ></p>
	<p><input type="password" class="box-input" name="newpassword2" placeholder="Retapez le nouveau mot de passe" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 2000);</script>
	</p>
	
	<p><input type="submit" value="Change le mot de passe" name="submit" class="mySmallButton"></p>
	
	</form>
	<p>&nbsp;</p>	
	<p><button class="mySmallButton" onclick="gotoindex()">Page d'accueil Bridgette</button></p>
	</div>
	<script>
	$("#pseudo").focus();
	</script>
</body>
</html>