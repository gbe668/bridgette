<?php
	require("configuration.php");
	require("bridgette_bdd.php");

	if ( isset($_POST['codedujour']) ){
		$codedujour = stripslashes($_POST['codedujour']);
		$codedujour = htmlspecialchars($codedujour);
		$_SESSION['codedujour'] = $codedujour;
		
		$idt = existeTournoiVivant();
		if ( $idt > 0 ) {
			$t = readTournoi( $idt );
			if( $codedujour == $t['code'] ) {
				header("Location: bridge60.php");
				$message = "OK.";
			}
			else {
				$message = "Code erroné !";
			}
		}
		else {
			$message = "Pas de tournoi vivant !";
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
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2>Tournoi en préparation ou démarré</h2>
	<h3>Joueur: entrez le code du tournoi</h3>
	<p><em>Code fourni par le directeur de tournoi</br>qui vous permettra d'accéder</br>aux écrans d'entrée des résultats</em></p>
	<?php
	if( $site == 2 ) print "<p>Site de démonstration:</br>voir le code affiché sur le tableau de bord</br>du directeur dr tournoi</p>";
	?>

	<form action="" method="post" name="login">
	<p><input type="text" class="box-input" name="codedujour" id="codedujour" placeholder="code" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else echo "&nbsp;"
		?>
		<script>setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 3000);</script>
	</p>
	
	<p><input type="submit" value="Connexion " name="submit" class="mySmallButton"></p>
	</form>
	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	
	<script>
	$("#codedujour").focus();
	</script>
</body>
</html>