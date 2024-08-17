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
function goto43() {
	var nextstring = "bridge43.php?idtournoi=" + idtournoi + "&etui=" + etui + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function cdeplus() {
	$("#afficheplus").addClass( "section_invisible" );
	$("#affichemoins").removeClass( "section_invisible" );
	setfocus( 1 );
}
function cdemoins() {
	$("#afficheplus").removeClass( "section_invisible" );
	$("#affichemoins").addClass( "section_invisible" );
}
function clickValidiags() {
	//$("#section_inputdiags").addClass( "section_invisible" );
	//$("#section_validiags").addClass( "section_invisible" );
	$("#tstvalidok").removeClass( "section_invisible" );
	
	// Enregistrement du diagramme
	$.get( "f65upddiagramme.php", { idtournoi:idtournoi, donne:etui, diagramme:dealfield },
	function(strjson) {
		$('#validok').html( strjson.display );
		goto43();
	},"json");	
};
</script>

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
	$diagramme = existeDiagramme($idtournoi,$etui);
	if ( $diagramme == null ) {
		print '<p>Diagramme non enregistré</p>';
	}
	else {
		print_section_diagramme();
		
		print '<div id="afficheplus">';
		print '<p><button onclick="cdeplus()">Correction diagramme</button></p>';
		print '</div>';

		print '<div id="affichemoins" class="section_invisible">';
		//print '<p><button onclick="cdemoins()" style="font-style: italic;">referme</button></p>';
		print '<div id="section_inputdiags">';
		print '<p id="msg">&nbsp;</p>';
		print_clavier_diagramme();
		print "<p>La main d'Ouest est complétée automatiquement. Corrigez les autres mains.</p>";
		
		print '<div id="section_validiags" class="section_invisible">';
		print '<p><button class="myStartButton" id="valid1" onClick="clickValidiags()">Enregistrez</br>les diagrammes</button></p>';
		print "<p id='validok'>Attente fin d'entrée des diagrammes</p>";
		print '</div>';
		
		print '</div>';
		print '</div>';
	}
	?>
	
	<script>
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	etui	  = parseInt( "<?php echo $etui; ?>" );
	pairesNS  = parseInt( "<?php echo $pairesNS; ?>" );
	pairesEO  = parseInt( "<?php echo $pairesEO; ?>" );
	var diagramme = String( "<?php echo $diagramme; ?>" );
	if ( displaydeal( diagramme ) == true ) $("#section_diagramme").removeClass( "section_invisible");
	</script>
	
	<p><button class="mySmallButton" onclick="goto43()">Retour au tableau de bord</button></p>
	</div>
	
</body>
</html>