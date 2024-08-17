<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");
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
function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto64() {
	retparms = { next:"howell64", etui:donne };
	passAndroid( retparms );
};
function clickValidiags() {
	$("#section_inputdiags").addClass( "section_invisible" );
	$("#section_validiags").addClass( "section_invisible" );
	$("#tstvalidok").removeClass( "section_invisible" );
	
	// Enregistrement du diagramme
	$.get( "/f65setdiagramme.php", { idtournoi:idtournoi, donne:donne, diagramme:dealfield, token:token },
	function(strjson) {
		$('#validok').html( strjson.display );
		goto64();
	},"json");	
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
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$paire = htmlspecialchars( $_GET['paire'] );
	$ligne = htmlspecialchars( $_GET['ligne'] );
	
	$numpaire = $paire;
	$donne = htmlspecialchars( $_GET['donne'] );

    print "<h2>Diagrammes donne n°$donne</h2>";
	print_section_diagramme();
	print '<div id="section_inputdiags">';
	print '<p id="msg">&nbsp;</p>';
	print_clavier_diagramme();
	print '</div>';
	?>

	<div id="section_validiags" class="section_invisible">
	<p><button class="myStartButton" id="valid1" onClick="clickValidiags()">Enregistrez</br>les diagrammes</button></p>
	</div>

	<p id="validok">Attente fin d'entrée des diagrammes</p>
   
	<p><button onclick='goto64()'>Retour à l'affichage</br>des résultats de la donne</button></p>

	<script>
	var token  = "<?php echo $token; ?>";
	
	var idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	var numpaire  = parseInt( "<?php echo $numpaire; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	$("#section_diagramme").removeClass( "section_invisible");
	initcanselect();
	setfocus( 1 );
	</script>
	</div>
</body>
</html>