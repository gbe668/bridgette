<?php
require("configuration.php");
require("bridgette_bdd.php");
//require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$screenw = htmlspecialchars( $_GET['w'] );

$t = readTournoi( $idtournoi );
$datef   = $t[ 'datef' ];
$ndonnes = $t[ 'ndonnes' ];
if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
	$ordre = "pointsIMP";
}
else {
	$ordre = "points";
}

$diagrammes = getdiagrammes($idtournoi);
?>
 
<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery.mobile-1.5.0-rc1.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

const club  = "<?php echo $titre; ?>";
const idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
const datef = "<?php echo $datef; ?>";
const ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
const diagrammes = <?php echo $diagrammes; ?>;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto20() {
	var nextstring = "bridge20.php";
	location.replace( nextstring );
};
function reload() {
	var nextstring = "bridge21.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
var actuel = 1;	// étui sélectionné
var selns = seleo = 0;
function key_select( nbr ) {
	actuel += parseInt( nbr );
	if ( actuel < 1 ) actuel = 1;
	if ( actuel > ndonnes ) actuel = ndonnes;
	$("#etui").text( actuel );
	
	$.get(relpgm+"f64getresultatdonne.php?", {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo}, function(strjson) {
		showresultat( strjson );
	}, "json")
	.fail( function( jqxhr, settings, ex ) { console.log('Erreur: '+ ex ); } );
};
$(document).ready(function() {
	$("#etuim10").bind('click', function( event ){ key_select( -10 ); });
	$("#etuim1").bind('click', function( event ){ key_select( -1 ); });
	$("#etuip1").bind('click', function( event ){ key_select( 1 ); });
	$("#etuip10").bind('click', function( event ){ key_select( 10 ); });
	//$("#swipebox").bind('swipe', function( event ){ key_select( 1 ); });
	$("#swipebox").bind('swipeleft', function( event ){ key_select( 1 ); });
	$("#swipebox").bind('swiperight', function( event ){ key_select( -1 ); });
	$('td.select').click(function(event) {
		var id = event.target.id;
		const figs = id.split('_');
		console.log( "Id ", id, " Paire ", figs[0], " n° ", figs[1] );
		if ( figs[0] == "ns" ){
			selns = figs[1];
			seleo = 0;
		}
		if ( figs[0] == "eo" ){
			selns = 0;
			seleo = figs[1];
		}
		
		$.get(relpgm+"f64getresultatdonne.php?", {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo}, function(strjson) {
			showresultat( strjson );
		}, "json")
		.fail( function( jqxhr, settings, ex ) { console.log('Erreur: '+ ex ); } );
		
		$.get(relpgm+"f21getroadmap.php?", {idtournoi:idtournoi, axe:figs[0], num:figs[1]}, function(strjson) {
			$("#team").html("Feuille de route de la paire "+strjson.ref+"</br>"+strjson.team);
			$("#roadmap").html(strjson.html);
			elmnt = document.getElementById("team");
			elmnt.scrollIntoView();
		}, "json");
	});
});
$(document).on( "click", "td.seletui", function(event) {
	var id = $(this).parent().attr("id");
	const figs = id.split('_');
	console.log( "Etui ", figs[1] );
	actuel = parseInt( figs[1] );
	
	$("#section_donnes").show();
	$("#section_roadmap").hide();
	$("#etui").text(figs[1]);
	key_select( 0 );
});

$.mobile.loading().hide();		// suite ajout jquery.mobile-1.5.0-rc1.min.js

// ajout affichage résultat analyse - 30/072025
$(document).on( "click", "#showanalysis", function() { $("#makeableContracts").toggle(); });
</script>

<body>
	<div style="text-align: center">
	
	<script>
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	if ( isNaN( screenw ) ) reload();
	</script>
	
	<?php
	print htmlDisplayResultatsTournoi( $idtournoi, $screenw, True );
	?>

	<div id="section_donnes">
	<p>Naviguez entre les différentes donnes</br>en balayant l'écran ou en cliquant sur les chiffres</p>
	
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="etuim10">-10</div></td>
	<td class='xNum2'><div id="etuim1">-1</div></td>
	<td class='xNum2'><div id="etuip1">+1</div></td>
	<td class='xNum2'><div id="etuip10">+10</div></td>
	</tr><tbody></table>

	<div id="swipebox">
	<div id="section_resultat">
	<?php print htmlResultatDonne($idtournoi, 1, 0, 0, $ordre); ?>
	</div>
	<div id="section_diagramme"></div>
	<div id='makeableContracts' hidden></div>
	</div>
	
	<p><button class="myButton" onclick="$('#section_donnes').hide();$('#section_roadmap').show();">Affichage feuille de route</button></p>
	</div>
	
	<div id="section_roadmap" hidden>
	<h3 id="team" >Cliquez sur une paire</br>pour afficher sa feuille de route</h3>
	<div id="roadmap" >feuille de route</div>
	<p><button class="myButton" onclick="$('#section_donnes').show();$('#section_roadmap').hide();">Affichage donnes</button></p>
	</div>
	
	<div id="section_dds" hidden>
	<p><button id="btnexpdds" onclick="exportDDSolver()">Exporte les donnes</button> <button id="btndds" onclick="DDSolver()">Ouvre DDSolver</button></p>
	</div>
	
	<p><button class="mySmallButton" onclick="goto20()">Retour liste des tournois</button></p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour à l'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	

	<script>
	key_select( 0 );	// affichage 1er diagramme
	$("#nsec_1").show();
	
	// ajout analyse
	if ( diagrammes.length > 0 )  $("#section_dds").show();
	$("#showanalysis").toggle();

	</script>
	</div>
</body>
</html>