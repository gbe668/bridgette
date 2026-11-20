<?php
require("configuration.php"); require("bridgette_bdd.php");
if (isset($_POST['pseudo'])) {
    $pseudo = is_string($_POST['pseudo']) ? htmlspecialchars(stripslashes($_POST['pseudo'])) : '';
    $password = $_POST['password'] ?? null;
    if (!bridgettePasswordInput($password)) $message = 'Mot de passe vide ou trop long (maximum 1024 octets).';
    else {
        $dbh = connectBDD();
        $rows = securityQuery($dbh, "SELECT * FROM `$tab_directeurs` WHERE pseudo = ?", [$pseudo])->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $message = count($rows) === 1 && bridgettePasswordVerify($password, $rows[0]['password']) ? 'Vous êtes déjà enregistré.' : 'Erreur, pseudo déjà utilisé.';
        } else {
            securityQuery($dbh, "INSERT INTO `$tab_directeurs` (pseudo, password, droits) VALUES (?, ?, ?)", [$pseudo, bridgettePasswordHash($password), 'directeur']);
            header('Location: admin.php'); exit;
        }
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
	var nextstring = "admin.php";
	bridgetteNavigate( nextstring );
};
</script>


<body>
	<?php
	?>

	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h1>Organisation des tournois</h1>
	<h2>Ajout d'un nouveau directeur de tournoi</br>Entrez les identifiants de connexion</h2>
	<p>&nbsp;</p>

	<form action="" method="post" name="login"><?php securityCsrfField(); ?>
	<p><input type="text" class="box-input" name="pseudo" id="pseudo" placeholder="Pseudo directeur" required ></p>
	<p><input type="password" class="box-input" name="password" placeholder="Mot de passe" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);</script>
	</p>
	
	<p><input type="submit" value="OK" name="submit" class="mySmallButton"></p>
	
	</form>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour administration</button></p>
	</div>
	<script>
	$("#pseudo").focus();
	</script>
</body>
</html>