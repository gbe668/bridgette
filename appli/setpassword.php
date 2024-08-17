<?php
	require("configuration.php");
	require("bridgette_bdd.php");

	if ( isset($_POST['pseudo']) ){
		$pseudo = stripslashes($_REQUEST['pseudo']);
		$pseudo = htmlspecialchars($pseudo);
		
		$oldpassword = stripslashes($_REQUEST['oldpassword']);
		$oldpassword = htmlspecialchars($oldpassword);
		
		$newpassword1 = stripslashes($_REQUEST['newpassword1']);
		$newpassword1 = htmlspecialchars($newpassword1);
		$newpassword2 = stripslashes($_REQUEST['newpassword2']);
		$newpassword2 = htmlspecialchars($newpassword2);
		
		$dbh = connectBDD();
		$sql = "SELECT count(*) FROM `$tab_directeurs` WHERE pseudo='$pseudo' and password='".hash('sha256', $oldpassword)."';";
		$res = $dbh->query($sql);
		if($res->fetchColumn() == 1){
			// identification correcte, test mots de passe
			if ($newpassword1==$newpassword2) {
				$encpwd = hash('sha256', $newpassword1);
				$sql = "UPDATE `$tab_directeurs` SET  password = '$encpwd' WHERE pseudo = '$pseudo';";
				$res = $dbh->query($sql);
				if($res) $message =  "Mot de passe changé avec succès.";
				else $message =  "Erreur inconnue.";
				unset( $_SESSION['pseudo'] );
			}
			else {
				$message = "Nouveaux mots de passe différents.";
			}
		}
		else {
			$message = "Le pseudo ou le mot de passe est incorrect.";
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
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
</script>


<body>
	<?php
	?>

	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h1>Changement de mot de passe</h1>

	<form action="" method="post" name="login">
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