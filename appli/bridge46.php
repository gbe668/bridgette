<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['etui'] );
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
function supprime() {
	deletediags(idtournoi, etui, goto43);
}
function corrige() {
	$("#afficheplus") .hide();
	$("#affichemoins").show();
	$(".edit").show();
	initcanselect();
	setfocus( 1 );
}

// ajout analyse diagramme - 30/07/2025
var Module = {};
</script>
<script src="jsdds/out.js"></script>
<script src="jsdds/dds.js"></script>

<body>
	<div style="text-align: center">
	
	<?php
	$t = readTournoi( $idtournoi );
	$pairesNS = $t[ 'pairesNS' ];
	$pairesEO = $t[ 'pairesEO' ];

	?>
	<h2>Affichage diagramme étui n°<?php echo $etui; ?></h2>
	<h2><span class='numetui' id='etui'><?php echo $etui; ?></span></h2>
	<?php
	[$diagramme,$dds] = existeDiagramme($idtournoi, $etui);
	if ( $diagramme == null ) {
		print '<p>Diagramme non enregistré</p>';
	}
	else { ?>
		<div id="section_diagramme">diagramme</div>
		
		<div id="afficheplus">
		<p><button class="myButton" onclick="corrige()">Correction diagramme</button></p>
		</div>

		<div id="affichemoins" hidden>
		<div id="section_inputdiags">
		<p id="msg">&nbsp;</p>
		<div id="section_kbddiags"></div>
		<p>La main d'Ouest est complétée automatiquement. Corrigez les autres mains.</p>
		
		<div id="section_validiags">
		<p><button class="myStartButton" id="valid1" onClick="clickcorrectiondiags(donothing)">Enregistrez</br>les diagrammes</button></p>
		<p id='validok'>Attente fin d'entrée des diagrammes</p>
		</div>
		

		</div>
		</div>
		<p><button class="mButton" onclick="$('#section_del').toggle();">Supprimer le diagramme</button></p>
		<div id='section_del' hidden><p><button class='mButton' onclick='supprime();' style='background-color:lightgreen'>Je confirme</button> <button class='mButton' onclick='$(`#section_del`).toggle();' style='background-color:#FF5050'>Oups !!! J'annule</button></p></div>
		<?php
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
	$(".edit").hide();
	
	var diagramme = String( "<?php echo $diagramme; ?>" );
	if ( displaydeal( diagramme, etui ) == true ) $("#section_diagramme").show();
	</script>
	
	<p><button class="mySmallButton" onclick="goto43()">Retour au tableau de bord</button></p>
	</div>
	
</body>
</html>