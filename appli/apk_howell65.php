<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$paire = htmlspecialchars( $_GET['paire'] );
$ligne = htmlspecialchars( $_GET['ligne'] );

$numpaire = $paire;
$donne = htmlspecialchars( $_GET['donne'] );
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge65.js"></script>
<?php securityCsrfHead(); ?>
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto64() {
	retparms = { next:"howell64", etui:donne };
	passAndroid( retparms );
};
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

// optimisation analyse diagramme - 30/08/2026
// A initialiser UNE SEULE FOIS au chargement de la page (pas à chaque donne),
// par exemple juste après le chargement du DOM.
let ddsWorker = new Worker('/js/ddsWorker.js');
let ddsWorkerReady = false;

ddsWorker.onmessage = function (e) {
    if (e.data.type === 'ready') {
        ddsWorkerReady = true;
        console.log('DDS worker prêt');
    }
    // Le traitement du résultat ('result') est branché dans clickValidiags()
};

</script>

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

	<p><button class='myButton' onclick='goto64()'>Retour à l'affichage</br>des résultats de la donne</button></p>

	<script>
	var token  = "<?php echo $token; ?>";
	
	var idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
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