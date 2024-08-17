<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}
?>
<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function goto41() {
	var nextstring = "bridge41.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
</script>

<body>
	<h3><a href="bridge40.php">Retour page direction de tournoi</a></h3>
	<p>Génération automatique des paires Howell</p>

	<?php
	$idtournoi	= htmlspecialchars( $_GET['idtournoi'] );
	$nbpaires	= htmlspecialchars( $_GET['paires'] );
	
	purge_Tournoi( $idtournoi );	// supprime les paires déjà entrées
	
	$maxlignesNS = intval($nbpaires);

	$dbh = connectBDD();

	for ( $i = 1; $i <= $maxlignesNS; $i++ ) {
		$idj1 = $i*4 -3;
		$idj3 = $idj1 + 2;
		$sql = "INSERT $tab_pairesNS ( idtournoi, num, idj1, idj3 ) VALUES ( '$idtournoi', '$i', '$idj1', '$idj3' );";
		$res = $dbh->query($sql);
		};
	$dbh = null;
	?>
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	</script>

	<p>Paires générées</p>
	<p><button class="mySmallButton" onclick="goto41()">Retour page définition des tables Bridg'ette</button></p>
</body>
</html>