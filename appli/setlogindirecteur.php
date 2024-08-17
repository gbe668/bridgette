<?php
	require("configuration.php");
	require("bridgette_bdd.php");

	if ( isset($_POST['pseudo']) ){
		$pseudo = stripslashes($_REQUEST['pseudo']);
		$pseudo = htmlspecialchars($pseudo);
		
		$password = stripslashes($_REQUEST['password']);
		$password = htmlspecialchars($password);
		
		$dbh = connectBDD();
		$sql = "SELECT count(*) FROM `$tab_directeurs` WHERE pseudo = '$pseudo';";
		$res = $dbh->query($sql);
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			// pseudonyme déjà enregistré, vérification mot de passe
			$sql = "SELECT count(*) FROM `$tab_directeurs` WHERE pseudo = '$pseudo' and password = '".hash('sha256', $password)."';";
			$res = $dbh->query($sql);
			$nbl = $res->fetchColumn();
			if ( $nbl == 1 ) {
				$message =  "Vous êtes déjà enregistré.";
			}
			else {
				$message =  "Erreur, pseudo déjà utilisé.";
			}
		}
		else {
			// nouveau pseudonyme
			$sql = "INSERT into `$tab_directeurs` (pseudo, password, droits) VALUES ('$pseudo', '".hash('sha256', $password)."', 'directeur');";
			$res = $dbh->query($sql);
			if($res){
				$message = "Directeur enregistré !";
				header("Location: admin.php");
				exit();
			}
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
</head>

<script>
function gotoindex() {
	var nextstring = "admin.php";
	location.replace( nextstring );
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

	<form action="" method="post" name="login">
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