<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi	= htmlspecialchars( $_GET['idtournoi'] );
$paire = htmlspecialchars( $_GET['paire'] );
$ligne = htmlspecialchars( $_GET['ligne'] );
$donne= htmlspecialchars( $_GET['donne'] );

$numtable = $paire;
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge65.js"></script>
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto64diags() {
	retparms = { next:"mitch64diags", donne:donne };
	passAndroid( retparms );
};
function donothing() {
}

function passAndroid( parms ) {
	Android.processNext( JSON.stringify(parms) );
};
function showAndroidToast(toast) {
	Android.showToast(toast);
}

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
<script src="/jsdds/out.js"></script>
<script src="/jsdds/dds.js"></script>


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
	$t = readTournoi( $idtournoi );
	$paquet	= $t['paquet'];
	
	// récupération des dernières valeurs jouées en cas de reprise
	$res = getParmTable( $numtable );
	$firstdonne = $res['numdonne'];
	if ( $donne == 0 ) {
		$donne = $firstdonne;
	}
	
	$ligneEO	= getligneEO( $idtournoi, $numtable );
	$nameEst	= $ligneEO['E']['nomcomplet'];
	$nameOuest	= $ligneEO['O']['nomcomplet'];
	
	print "<h2><span class='numpaire'>Table n°$numtable</span> <button id='sos' onClick='gotoindex()'>SOS</button></h2>";
	print "<p>Est Ouest: $nameEst et $nameOuest</p>";
	print "<h2>Première position, vous êtes en relais.</h2>";
	print "<p>Vous devez préparer les donnes<br/>et entrer les diagrammes<br/>pour la position suivante.</p>";

	print "<h3>Récupérez les étuis:</br>";
	$donne ++;
	print liste_etuis( $donne, $paquet );
	print "</h3>";
	
	//Entrée diagramme
	[$diags,$dds] = existeDiagramme($idtournoi, $donne);
	if ($diags == null ) {
		print "<h2>Préparez la donne n°$donne,<br/>puis entrez les diagrammes</h2>";
		?>
		<div id="section_diagramme">diagramme</div>
		<div id="section_inputdiags">
		<p id="msg">&nbsp;</p>
		<div id="section_kbddiags"></div>
		<p id="dealfield" hidden>&nbsp;</p>
		</div>

		<div id="section_validiags" class="section_invisible">
		<p><button class="myStartButton" id="valid1" onClick="clickValidiags(donothing)">Enregistrez</br>les diagrammes</button></p>
		</div>

		<p id="validok">Attente fin d'entrée des diagrammes</p>
		<?php
		if ( $teston == 2 ) print "<p><button onClick='autoDiagramme()'>auto remplissage</button></p>";
	}
	else print "<p>Diagrammes donne $donne déjà enregistrés.</p>";
	$cpt = $donne - $firstdonne;
	print "<p>&nbsp;</p>";
	if ( $cpt < $paquet ) {
		print '<p><button class="myButton" onclick="goto64diags()">Préparez la donne suivante</button></p>';
	}
	else {
		print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
	}
	?>

	<script type="text/javascript"> 
	var idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	var numtable  = parseInt( "<?php echo $numtable; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	
	$("#section_diagramme").html( diag_skeleton() );
	$("#section_kbddiags" ).html( diag_keyboard() );
	$("#showanalysis").hide();
	initcanselect();
	setfocus( 1 );
	elmnt = document.getElementById("section_diagramme");
	elmnt.scrollIntoView();
	</script>
	</div>
</body>
</html>