<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");
require("lib45.php");

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
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge45.js"></script>
	<script src="js/bridge63.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var relimg = "<?php echo $relimg; ?>";

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto43() {		// clôture du tournoi
	var nextstring = "bridge43.php?idtournoi=" + idtournoi + "&etui=" + nodonne + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<h2>Correction du résultat d'une donne</br>en cours de tournoi</h2>
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$nodonne = htmlspecialchars( $_GET['etui'] );
	print "<h3>Résultats donne n°$nodonne</h3>";
	//$vulnerabilite = getinfoetui( $nodonne );
	print "<h3 id='texteDonneur'>donneur - vulnérabilité</h3>";
	displayTableauResultatDonne();
	
	$t = readTournoi( $idtournoi );
	$datef = $t[ 'datef' ];
	$ndonnes = $t[ 'ndonnes' ];
	?>
	
	<p>Sélectionnez la ligne concernée</br>dans le tableau des résultats.</p>
	
	<div id="section_correction" class="section_invisible">
	<h3 id="msgerr2" >&nbsp;</h3>
	
	<?php
	print_tables_saisie_contrat();
	?>
	<p id="textePoints">Points NS ?</p>
	<div id="section_validation">
	<p><button class="myStartButton" id="valid5" onClick="clickValidationCorrection()">Valider</br>la correction</button></p>
	<p id="validok">Attente validation correction ...</p>
	</div>
	</div>
	<p id="msgerr" >&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto43()">Retour au tableau de bord</button></p>
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	nodonne = parseInt( "<?php echo $nodonne; ?>" );
	
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	maxtables = parseInt( "<?php echo $max_tables; ?>" );
	masquelignes();
	getresultatdonne( nodonne );
	realdonne = new Donnejouee(nodonne);
	</script>
	</div>
	
</body>
</html>