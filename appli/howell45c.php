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
function goto44() {		// clôture du tournoi
	var nextstring = "howell44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<h2>Correction d'un résultat</h2>
	<p><em>En cas d'erreur de saisie,</br>correction du résultat.
	Les pénalités</br>pour une paire sont entrées</br>en modifiant le résultat de la donne</br>pour la paire concernée.</em></p>
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$t = readTournoi( $idtournoi );
	$datef = $t[ 'datef' ];
	$ndonnes = $t[ 'ndonnes' ];
	?>
	
	<script type="text/javascript"> 
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	maxtables = parseInt( "<?php echo $max_tables; ?>" );
	</script>

	
	<p>Naviguez entre les différentes donnes</br>en cliquant sur les chiffres pour avancer</br>ou reculer d'une unité ou d'une dizaine</p>
	
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="etuim10">-10</div></td>
	<td class='xNum2'><div id="etuim1">-1</div></td>
	<td class='xNum2'><div id="etuip1">+1</div></td>
	<td class='xNum2'><div id="etuip10">+10</div></td>
	</tr><tbody></table>
	<h2>Résultats donne n°<span id='etui'>1</span>&nbsp;<span id="msgerr1"></span></h2>
	<?php
	print "<h3 id='texteDonneur'>donneur - vulnérabilité</h3>";
	displayTableauResultatDonne();
	?>
	
	<p>Puis, sélectionnez la ligne concernée</br>dans le tableau des résultats.</p>
	
	<div id="section_correction" class="section_invisible">
	<h3 id="msgerr2" >&nbsp;</h3>
	
	<?php
	print_tables_saisie_contrat();
	?>
	<p id="textePoints">Points NS ?</p>
	<div id="section_validation">
	<p><button class="myStartButton" id="valid5" onClick="clickValidationCorrection()">Valider</br>la correction</button></p>
	<p id="validok">Attente validation correction ...</p>
	<p id="msgerr" >&nbsp;</p>
	</div>
	</div>
	<p><button class="mySmallButton" onclick="goto44()">Retour page de clôture</button></p>
	<script>
	masquelignes();
	getresultatdonne( 1 );
	realdonne = new Donnejouee(1);
	</script>
	</div>
	
</body>
</html>