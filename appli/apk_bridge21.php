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
	<script src="/js/jquery.mobile-1.5.0-rc1.min.js"></script>
	<script src="/js/bridge65.js"></script>
</head>

<script>
var token = "<?php echo $token; ?>";

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto20() {
	retparms = { next:"bridge20" };
	passAndroid( retparms );
};
function passAndroid( parms ) {
	Android.processNext( JSON.stringify(parms) );
};
function showAndroidToast(toast) {
	Android.showToast(toast);
}
var actuel = 1;	// étui sélectionné
var selns = seleo = 0;
function key_select( nbr ) {
	actuel = parseInt( $("#etui").text() );
	actuel += parseInt( nbr );
	if ( actuel < 1 ) actuel = 1;
	if ( actuel > ndonnes ) actuel = ndonnes;
	$("#etui").text( actuel );
	
	$.get('/f21getresultatdonne.php', {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo, token:token}, function(html) {
		$("#section_resultat").html(html);
		ndiag = "#ndiag_0";
		//console.log("ndiag", ndiag, $(ndiag).text());
		if ( displaydeal( $(ndiag).text(), actuel ) == false ) $("#section_diagramme").addClass( "section_invisible" );
		else {
			$("#section_diagramme").removeClass( "section_invisible" );
			elmnt = document.getElementById("tablenav");
			elmnt.scrollIntoView();
		}
	}, "html");
};
function display_donnes() {
	$("#section_donnes").removeClass( "section_invisible" );
	$("#section_roadmap").addClass( "section_invisible" );
}
function display_roadmap() {
	$("#section_roadmap").removeClass( "section_invisible" );
	$("#section_donnes").addClass( "section_invisible" );
}
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
		$.get("/f21getresultatdonne.php?", {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo}, function(html) {
			$("#section_resultat").html(html);
		}, "html");
		var axe = (figs[0]=="ns") ? "NS" : "EO";
		$.get("/f21getroadmap.php?", {idtournoi:idtournoi, axe:figs[0], num:figs[1], token:token}, function(strjson) {
			$("#team").html("Feuille de route de la paire "+strjson.ref+"</br>"+strjson.team);
			$("#roadmap").html(strjson.html);
			elmnt = document.getElementById("team");
			elmnt.scrollIntoView();
		}, "json");
	});
});
$(document).on( "click", "td.xres", function(event) {
	var id = $(this).parent().attr("id");
	const figs = id.split('_');
	console.log( "Etui ", figs[1] );
	$("#section_donnes").removeClass( "section_invisible" );
	$("#section_roadmap").addClass( "section_invisible" );
	$("#etui").text(figs[1]);
	key_select( 0 );
});
$.mobile.loading().hide();		// suite ajout jquery.mobile-1.5.0-rc1.min.js
</script>

<body>
	<div style="text-align: center">
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = htmlspecialchars( $_GET['w'] );
	if ( $idtournoi == 0 ) {
		$idtournoi = getlastclosedtournois();
	}
	displayResultatsTournoi( $idtournoi, $screenw );
	?>

	<div id="section_donnes">
	<p>Naviguez entre les différentes donnes</br>en balayant l'écran ou en cliquant sur les chiffres</p>
	
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="etuim10">-10</div></td>
	<td class='xNum2'><div id="etuim1">-1</div></td>
	<td class='xNum2'><div id="etuip1">+1</div></td>
	<td class='xNum2'><div id="etuip10">+10</div></td>
	</tr><tbody></table>
	
	<?php
	$t = readTournoi( $idtournoi );
	$ndonnes = $t[ 'ndonnes' ];
	if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
		$ordre = "pointsIMP";
	}
	else {
		$ordre = "points";
	}
	print '<div id="swipebox">';
	print '<div id="section_resultat">';
	print htmlResultatDonne($idtournoi, 1, 0, 0, $ordre);
	print '</div>';
	print_section_diagramme();
	print '</div>';
	?>
	<p><button class="myButton" onclick="display_roadmap()">Affichage feuille de route</button></p>
	</div>
	
	<div id="section_roadmap" class='section_invisible'>
	<h3 id="team" >Cliquez sur une paire</br>pour afficher sa feuille de route</h3>
	<div id="roadmap" >feuille de route</div>
	<p><button class="myButton" onclick="display_donnes()">Affichage donnes</button></p>
	</div>
	
	<p><button class="mySmallButton" onclick="goto20()">Retour liste des tournois</button></p>
	
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	key_select( 0 );	// affichage 1er diagramme
	$("#nsec_1").removeClass( "section_invisible" );
	</script>
 	</div>
</body>
</html>