<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

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
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

function goto43() {
	var nextstring = "bridge43.php?idtournoi=" + idtournoi + "&etui=" + etui + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function cdeplus() {
	$("#afficheplus") .hide();
	$("#affichemoins").show();
	initcanselect();
	setfocus( 1 );
}
function cdemoins() {
	$("#afficheplus") .show();
	$("#affichemoins").hide();
}

function clickcorrectiondiags() {
	$("#section_inputdiags").hide();
	$("#section_validiags").hide();
	$("#tstvalidok").show();
	
	// Calcul des contrats possibles, reformate la chaine pour avoir le donneur en premier
	let etui = etuis[donne];
	let deal = dealfield.slice(2).split(' ');
	let validDealers = "NESW";
	
	let index = etui[1]-1;	// N:0
	let str = validDealers[index];
	str += ":";
	for (let j=0; j<4; j++) {
		str += deal[index];
		if (j!=3) str += " ";
		index++;
		if (index==4) index=0;
	}
	console.log( "deal reformatté", str );
	let DDTable = calcDDTable(str);
	console.log( DDTable );
	
	// codage pour la bdd
	let tabi = ['N','S', 'H', 'D', 'C'];
	let tabj = ['N', 'S', 'E', 'W'];
	let dds = "";
	for ( let j in tabj ) {
		for ( let i in tabi ) {
			let value = DDTable[tabi[i]][tabj[j]];
			dds += String.fromCharCode(97 + value);
		}
	}
	console.log( "dds", dds );
	
	// Enregistrement du diagramme
	$.get( relpgm+"f65upddiagramme.php", { idtournoi:idtournoi, donne:donne, diagramme:dealfield, dds:dds },
	function(strjson) {
		$('#validok').html( strjson.display );
		goto43();
	},"json");
};

// ajout analyse diagramme - 30/07/2025
var Module = {};
</script>
<script src="jsdds/out.js"></script>
<script src="jsdds/dds.js"></script>

<body>
	<div style="text-align: center">
	
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$etui = htmlspecialchars( $_GET['etui'] );
	$t = readTournoi( $idtournoi );
	$pairesNS = $t[ 'pairesNS' ];
	$pairesEO = $t[ 'pairesEO' ];

	?>
	<h2>Affichage diagramme étui n°<?php echo $etui; ?></h2>
	<?php
	[$diagramme,$dds] = existeDiagramme($idtournoi, $etui);
	if ( $diagramme == null ) {
		print '<p>Diagramme non enregistré</p>';
	}
	else {
		print '<div id="section_diagramme">diagramme</div>';
		
		print '<div id="afficheplus">';
		print '<p><button class="myButton" onclick="cdeplus()">Correction diagramme</button></p>';
		print '</div>';

		print '<div id="affichemoins" hidden>';
		print '<div id="section_inputdiags">';
		print '<p id="msg">&nbsp;</p>';
		print '<div id="section_kbddiags"></div>';
		print "<p>La main d'Ouest est complétée automatiquement. Corrigez les autres mains.</p>";
		
		print '<div id="section_validiags">';
		print '<p><button class="myStartButton" id="valid1" onClick="clickcorrectiondiags()">Enregistrez</br>les diagrammes</button></p>';
		print "<p id='validok'>Attente fin d'entrée des diagrammes</p>";
		print '</div>';
		
		print '</div>';
		print '</div>';
	}
	?>
	
	<script>
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	etui	  = parseInt( "<?php echo $etui; ?>" );
	donne  = etui;
	pairesNS  = parseInt( "<?php echo $pairesNS; ?>" );
	pairesEO  = parseInt( "<?php echo $pairesEO; ?>" );
	
	$("#section_diagramme").html( diag_skeleton() );
	$("#section_kbddiags") .html( diag_keyboard() );
	$("#showanalysis").hide();
	
	var diagramme = String( "<?php echo $diagramme; ?>" );
	if ( displaydeal( diagramme, etui ) == true ) $("#section_diagramme").show();
	</script>
	
	<p><button class="mySmallButton" onclick="goto43()">Retour au tableau de bord</button></p>
	</div>
	
</body>
</html>