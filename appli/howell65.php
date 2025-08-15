<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$numpaire = htmlspecialchars( $_GET['paire'] );
$donne = htmlspecialchars( $_GET['donne'] );
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto64() {
	var nextstring = "howell64.php?idtournoi=" + idtournoi;
	nextstring += '&paire=' + numpaire + '&etui=' + donne;
	location.replace( nextstring );
};

// mécanisme détectant une page expirée
var agepagemax = "<?php echo $agepagemax; ?>";
var loadedpage = "<?php echo $_SESSION['ttlast']; ?>";
document.addEventListener('visibilitychange', function (event) {
	if ( !document.hidden ) {
		var agepage = Math.floor(Date.now()/1000)-loadedpage;
		if ( agepage > agepagemax ) {
			pop.style.display = "inline-block";
			setTimeout(function() { gotoindex(); }, 2000);
		}
	}
});

// ajout analyse diagramme - 30/07/2025
var Module = {};
</script>
<script src="jsdds/out.js"></script>
<script src="jsdds/dds.js"></script>

<body>
	<center>
	<div id="pop" class="popup">
	<p>&nbsp;</p>
	<h2>Page périmée</h2>
	<button onclick="gotoindex();">Retour page d'accueil</button>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align: center">
	<?php
	print "<h2>Diagrammes donne n°$donne</h2>";
	?>
	<div id="section_diagramme">diagramme</div>
	<div id="section_inputdiags">
	<p id="msg">&nbsp;</p>
	<div id="section_kbddiags"></div>
	</div>

	<div id="section_validiags" hidden>
	<p><button class="myStartButton" id="valid1" onClick="clickValidiags(goto64)">Enregistrez</br>les diagrammes</button></p>
	</div>

	<p id="validok">Attente fin d'entrée des diagrammes</p>
	<?php if ( $teston == 2 ) print "<p><button onClick='autoDiagramme()'>auto remplissage</button></p>"; ?>
	<p><button onclick='goto64()'>Retour à l'affichage</br>des résultats de la donne</button></p>

	<script>
	var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	var numpaire  = parseInt( "<?php echo $numpaire; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	
	$("#section_diagramme").html( diag_skeleton() );
	$("#showanalysis").hide();
	$("#section_kbddiags").html( diag_keyboard() );
	initcanselect();
	setfocus( 1 );
	</script>
	</div>
</body>
</html>