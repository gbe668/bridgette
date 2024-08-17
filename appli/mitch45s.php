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
	var nextstring = "mitch44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<?php
require("lib45.php");
?>
 
<body>
	<div style="text-align: center">
	<h2>Suppression d'une donne</h2>
	
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
	<h3>Résultats donne n°<span id='etui'>1</span>&nbsp;<span id="msgerr1"></span></h3>
	<?php
	print "<h3 id='texteDonneur'>donneur - vulnérabilité</h3>";
	displayTableauResultatDonne();
	?>
	<p>En cas d'interversion de mains ou de cartes dans un étui sans possibilité de revenir en arrière, le mieux est de supprimer tous les résultats pour la donne concernée.</p>
	
	<div id="section_suppression">
	<p><button class="myButton" id="valid1" onClick="clickSuppressionDonne()">Supprimer la donne</button></p>
	<div id="section_confirme_suppression" class="section_invisible">
	<p><button class="myButton oktogoon" id="valid2" onClick="clickConfirmeSuppressionDonne()">Je confirme</button></p>
	<p><button class="myButton kotogoon" id="valid3" onClick="clickAnnulationSuppressionDonne()">Oups ! J'annule</button></p>
	</div>
	</div>
	
	<p>Pour les autres possibilités d'arbitrage (moyenne, correction, ajout de pénalité),</br>retournez à la page de clôture</p>
	
	<p><button class="mySmallButton" onclick="goto44()">Retour page de clôture</button></p>
	<script>
	masquelignes();
	getresultatdonne( 1 );
	realdonne = new Donnejouee(1);
	</script>
	</div>
	
</body>
</html>