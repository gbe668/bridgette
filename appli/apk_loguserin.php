<?php
require("configuration.php");
require("bridgette_bdd.php");

$login = 0;		// signale une erreur
$userid = 0;
$username = "anonyme";

if ( isset($_GET['mailuser']) ){
	$usermail = stripslashes($_GET['mailuser']);
	$usermail = htmlspecialchars($usermail);
	if ( isset($_GET['password']) ){
		$password = stripslashes($_GET['password']);
		$password = htmlspecialchars($password);
		$encodpwd  = hash('sha256', $password);
		
		$dbh = connectBDD();
		$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail';";
		$res = $dbh->query($sql);
		$n = $res->fetchColumn();
		if($n < 1){
			$message = "Adresse mail inconnue !";
			$login = 3;
		}
		if($n == 1){
			// l'adresse existe et est unique
			$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail' and password='$encodpwd';";
			$res = $dbh->query($sql);
			if($res->fetchColumn() == 1){
				// ok
				$sql = "SELECT * FROM `$tab_joueurs` WHERE email='$usermail' and password='$encodpwd';";
				$res = $dbh->query($sql);
				$row = $res->fetch(PDO::FETCH_ASSOC);
				$userid = $row['id'];
				$username = $row['prenom'] . " " . $row['nom'];
				$_SESSION['userid'] = $userid;
				$_SESSION['username'] = $username;
				$_SESSION['usermail'] = $usermail;
				//header("Location: bridgette.php");
				$message = "ok";
				$login = 0;		// pas d'erreur
			}
			else {
				$message = "Mot de passe incorrect !";
				$login = 4;
			}
		}
		if($n > 1){
			$message = "Erreur: $n joueurs avec la même adresse mail !";
			$login = 3;
		}
		$dbh = null;
	}
	else {
		$message = "Mot de passe manquant !";
		$login = 2;
	}	
}
else {
	$message = "Adresse mail manquante.";
	$login = 1;
}
echo json_encode( array( 'login'=>$login, 'message'=>$message, 'userid'=>$userid, 'username'=>$username, 'club'=>$titre ) );
?>