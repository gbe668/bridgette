<?php
require("configuration.php");
require("bridgette_bdd.php");
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
	<h2>Application de la moyenne générale</h2>
	<p><em>En cas d'autre problème découvert</br>lors du déroulé d'une donne</br>à une table, utilisation de la médiane</br>des résultats des autres tables.</em></p>
	
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
	
	<div id="section_moyenne" class="section_invisible">
	<p>Médiane calculée: <span id="mediane1">???</span></p>
	<p>Valeur appliquée: <input class="xNum5" type="text" id="moyenne2" size="3"></p>
	<p><button class="myStartButton" id="valid5" onClick="clickMoyenneDonne()">Valider</br>la moyenne</button></p>
	</div>
	
	</div>
	<p><button class="mySmallButton" onclick="goto44()">Retour page de clôture</button></p>
	<script>
	$("#moyenne2").val(0);
	masquelignes();
	getresultatdonne( 1 );
	realdonne = new Donnejouee(1);
	</script>
	</div>
	
</body>
</html>