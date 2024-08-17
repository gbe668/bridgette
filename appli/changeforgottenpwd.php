<?php
require("configuration.php");
require("bridgette_bdd.php");

$hexkey = "b1208554230fa605d3aaf1611f1093024394b5d97a4f4628ed46c25736048f39";
$key = hex2bin( $hexkey );
/**
* Decrypt a message
*
* @param string $encrypted - message encrypted with safeEncrypt()
* @param string $key - encryption key
* @return string
*/
function safeDecrypt( $encrypted, $key ) {  
    $decoded = base64_decode($encrypted);
    if ($decoded === false) {
        //throw new Exception('Scream bloody murder, the encoding failed');
        return 'Scream bloody murder, the encoding failed';
    }
    if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
        //throw new Exception('Scream bloody murder, the message was truncated');
        return 'Scream bloody murder, the message was truncated';
    }
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

    $plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
    if ($plain === false) {
		//throw new Exception('the message was tampered with in transit');
		return 'The message was tampered with in transit';
		}
    sodium_memzero($ciphertext);
    sodium_memzero($key);
    return $plain;
};

if ( isset($_POST['phrase3']) ) {
	//$phrase = stripslashes($_REQUEST['phrase3']);
	//$phrase = htmlspecialchars($phrase);
	// phrase constituée du seul email
	$phrase3 = $_REQUEST['phrase3'];
	$phrase4 = hex2bin( $phrase3 );
	$usermail = safeDecrypt($phrase4, $key);
	
	$newpassword1 = htmlspecialchars(stripslashes($_REQUEST['newpassword1']));
	$newpassword2 = htmlspecialchars(stripslashes($_REQUEST['newpassword2']));
	
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM `$tab_joueurs` WHERE email='$usermail';";
	$res = $dbh->query($sql);
	if($res->fetchColumn() == 1){
		// identification correcte, test mots de passe
		if ($newpassword1==$newpassword2) {
			$encpwd = hash('sha256', $newpassword1);
			$sql = "UPDATE `$tab_joueurs` SET  password='$encpwd' WHERE email='$usermail';";
			$res = $dbh->query($sql);
			if($res) {
				$message =  "Mot de passe changé avec succès.";
				// Redirection vers la page de connexion
				header("Location: bridgette.php");
			}
			else $message =  "Erreur inconnue.";
		}
		else {
			$message = "Nouveaux mots de passe différents.";
		}
	}
	else {
		$message = "Identifiant $usermail inconnu.";
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
	<h2><?php echo $titre; ?></h2>
	<?php
	$phrase1 = $_GET['phrase'];
	$phrase2 = hex2bin( $phrase1 );
	// phrase constituée du seul email
	//$email = safeDecrypt( $phrase2, $key );
	?>
	<h1>Mot de passe oublié</h1>

	<form action="" method="post" name="login">
	<p><input type="hidden" name="phrase3" value="<?php echo $phrase1; ?>" ></p>
	<p><input type="password" class="box-input" name="newpassword1" id="newpassword1" placeholder="Nouveau mot de passe" required ></p>
	<p><input type="password" class="box-input" name="newpassword2" placeholder="Retapez le nouveau mot de passe" required ></p>
	
	<p id="msgerr">
		<?php
		if (! empty($message)) echo $message;
		else	echo "&nbsp;"
		?>
		<script>//setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 5000);
		</script>
	</p>
	
	<p><input type="submit" value="Enregistre le mot de passe" name="submit" class="mySmallButton"></p>
	
	</form>
	<p>&nbsp;</p>	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	<script>
	$("#newpassword1").focus();
	</script>
</body>
</body>
</html>