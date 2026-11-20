<?php
require("configuration.php");
require("bridgette_bdd.php");
require_once __DIR__.'/security_password_reset.php';
header('Cache-Control: no-store');
// Keep the POST Origin available to the supplemental origin check, while
// excluding the recovery secret in the URL from the Referer header.
header('Referrer-Policy: origin');
$phrase1 = $_POST['phrase3'] ?? ($_GET['phrase'] ?? '');
$valid = false;
$okchange = 0;
$message = 'Lien invalide, expiré ou déjà utilisé. Demandez un nouveau courriel depuis la connexion du club.';
try {
    $dbh = connectBDD();
    $valid = passwordResetRecord($dbh, $prefix, $phrase1) !== null;
    if ($valid) $message = '';
    if ($valid && isset($_POST['phrase3'])) {
        $first = $_POST['newpassword1'] ?? null;
        $second = $_POST['newpassword2'] ?? null;
        if (!is_string($first) || !is_string($second) || $first === '' || strlen($first) > 1024 || $first !== $second) {
            $message = 'Saisissez deux mots de passe identiques et non vides (maximum 1024 octets).';
        } else {
            $password = $first;
            $okchange = passwordResetApply($dbh, $prefix, $tab_joueurs, $phrase1, $password) ? 1 : 0;
            $valid = false;
            $message = $okchange ? 'Mot de passe changé avec succès. Reconnectez-vous au club, sur le site ou dans l’application Android.' : 'Lien invalide, expiré ou déjà utilisé. Demandez un nouveau courriel.';
        }
    }
} catch (Throwable $e) {
    $valid = false;
    $message = 'Réinitialisation indisponible. Demandez un nouveau courriel et réessayez.';
    error_log('Bridgette reset failed: '.get_class($e));
}
$phrase1 = is_string($phrase1) ? htmlspecialchars($phrase1, ENT_QUOTES, 'UTF-8') : '';
?><!DOCTYPE html>
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
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2><?php echo $titre; ?></h2>
	<h1>Mot de passe oublié</h1><p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

	<?php if ($valid) { ?><form action="" method="post" name="login"><?php securityCsrfField(); ?>
	<p><input type="hidden" name="phrase3" value="<?php echo $phrase1; ?>" ></p>
	<p><input type="password" class="box-input" name="newpassword1" id="newpassword1" placeholder="Nouveau mot de passe" required ></p>
	<p><input type="password" class="box-input" name="newpassword2" placeholder="Retapez le nouveau mot de passe" required ></p>
	

	
	<p><input type="submit" value="Enregistre le mot de passe" name="submit" class="mySmallButton"></p>
	
	</form><?php } ?>
	<p>&nbsp;</p>	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	<script>
	$("#newpassword1").focus();
	</script>
</body>
</body>
</html>
