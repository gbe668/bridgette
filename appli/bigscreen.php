<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = existeTournoiNonClos();
if ( $idtournoi == 0 ) {
	$idtournoi = getlastclosedtournois();
}
if ( $idtournoi == 0 ) {
	$etat = $st_notfound;
}
else {
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
}
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
	<style>
		.popup2 {
			position: fixed;
			width: 600px;
			transform: translate(-50%, 100px);
			border: 1px solid;
			box-shadow: 20px 20px 100px 20px #FFA500;
			display: none;
			background: #f1f1f1;
			z-index: 10;
		}
	</style>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
</head>

<script>
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var etat = parseInt( "<?php echo $etat; ?>" );

var st_notfound		= parseInt( "<?php echo $st_notfound; ?>" );
var st_phase_init	= parseInt( "<?php echo $st_phase_init; ?>" );
var st_phase_jeu	= parseInt( "<?php echo $st_phase_jeu; ?>" );
var st_phase_fini	= parseInt( "<?php echo $st_phase_fini; ?>" );
var st_closed 		= parseInt( "<?php echo $st_closed; ?>" );

var affposprov = parseInt( "<?php echo $parametres['affposprov']; ?>" );
var sizpix = parseInt( "<?php echo $parametres['sizpix']; ?>" );			// largeur texte en % largeur fenêtre

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};

var audiofindonne = 0;
var audiofinposition = 0;
var prevtour = 1;			// 1er tour
var declic = 10;			// x secondes avant la fin du délai

function Timer_phase_init() {	// attente démarrage tournoi
	$.get( "getpositionsprovisoires.php", {idtournoi:idtournoi, w:window.innerWidth}, function( json ) {
		if ( json.etat != st_phase_init ) {
			if ( json.etat == st_phase_jeu ) {
				var audio3 = document.getElementById("letournoidemarre");
				if ( enable3 ) audio3.play();
			}
			dispatch( json.etat );
		}
		else {
			if ( affposprov > 0 ) {
				$(affichage).html( json.positions );
			}
			else {
				var hhmm = new Date().toLocaleTimeString().slice(0, 5);
				$("#dsprest").html( "&nbsp;" + hhmm + "&nbsp;" );
				$("#dsprest").show();
			}
			setTimeout(function() { Timer_phase_init(); }, 5000);
		}
	},"json");
}
function Timer_phase_jeu() {	// pendant le tournoi
	$.get( "getpositionsjoueurs.php", {idtournoi:idtournoi, w:window.innerWidth}, function( json ) {
		if ( json.etat != st_phase_jeu ) {
			dispatch( json.etat );
		}
		else {
			$(affichage).html( "<div style='text-align:center; margin:auto; font-size:1.25em;'>" + json.positions + "</div>" );
			// décompte
			var now = new Date().getTime()/1000;	// en secondes
			var diff = Math.trunc( json.endofseq - now );

			var reste = new Date( Math.abs( diff ) * 1000).toISOString().slice(14, 19);
			if ( diff < 0 ) $("#dsprest").html( "&nbsp;<span style='color:red;'>" + reste + "</span>&nbsp;" );
			else $("#dsprest").html( "&nbsp;" + reste + "&nbsp;" );
			
			if ( prevtour != json.tour ) {
				console.log( "changePosition" );
				audiofinposition = 1;
				prevtour = json.tour;
				var audio2 = document.getElementById("changePosition");
				if ( enable2 ) audio2.play();
			}
			
			else {
				// test fin donne
				if ( diff > declic ) {
					if ( diff%json.tempo > declic )
						audiofindonne = 0;
					else {
						if ( audiofindonne == 0 ) {
							console.log( "nouvelleDonne" );
							audiofindonne = 1;
							var audio1 = document.getElementById("nouvelleDonne");
							if ( enable1 ) audio1.play();
							
							pop.style.display = "inline-block";
							setTimeout(function() { pop.style.display = "none"; }, 3000);
						}
					}
				}
			}
		
			setTimeout(function() { Timer_phase_jeu(); }, 3000);
		}
	},"json");
}
function Timer_phase_fini() {	// en phase de clôture
	// affichage des résultats provisoires
	$.get( "getresultatstournoi.php", {idtournoi:idtournoi, w:window.innerWidth}, function( json ) {
		if ( json.etat != st_phase_fini ) {
			dispatch( json.etat );
		}
		else {
			$(affichage).html( "<div style='text-align:center; margin:auto;'>"
				+ json.resultats
				+ "</div>" );
			setTimeout(function() { Timer_phase_fini(); }, 5000);
		}
	},"json");
}
function Timer_closed() {	// tournoi cloturé
	// affichage des résultats définitifs
	$(affichage).html( "<p>En attente chargement ...</p>" );
	$.get( "getresultatstournoi.php", {idtournoi:idtournoi, w:window.innerWidth}, function( json ) {
		$(affichage).html( "<div style='text-align:center; margin:auto;'>"
			+ json.resultats
			+ "</div>" );
		Timer_wait();
	},"json");
}
function Timer_wait() {
	$.get( "existetournoinonclos.php", {}, function( id ) {
		if ( id > 0 ) {
			$.get( "getetattournoi.php", {idtournoi:id}, function( json ) {
				dispatch( json.etat );
			},"text");
		}
		setTimeout(function() { Timer_wait(); }, 10000);
	},"text");
}

function testaudio() {
	var audio3 = document.getElementById("letournoidemarre");
	audio3.play();
}

function dispatch( etat ) {
	switch (etat) {
		case st_notfound: {	// attente création 1er tournoi
			$(titre).html( "<h2>Pas de tournoi en cours ou en préparation</h2>" );
			$(affichage).html( "<p>Pas de tournoi enregistré.</p>" );
			Timer_wait();
			break;
		}
		case st_phase_init: {
			$(titre).html( "<h2>Tournoi en préparation, attendez</h2>" );
			$("#dsprest").hide();
			Timer_phase_init();
			break;
		}
		case st_phase_jeu: {
			$(titre).html( "<h2>Tournoi en cours</h2>" );
			$("#dsprest").show();
			Timer_phase_jeu();
			break;
		}
		case st_phase_fini: {
			$(titre).html( "<h2 style='text-align: center; color:red'>Résultats provisoires</h2>" );
			$("#dsprest").hide();
			Timer_phase_fini();
			break;
		}
		case st_closed: {
			$(titre).html( "<h2 style='text-align: center;'>Résultats définitifs</h2>" );
			Timer_closed();
			break;
		}
		
		default:
	}
}

const sizpixmin = 20;
const sizpixmax = 80;
const factechelle = 3
function setsizepx(n) {
	sizpix += n;
	if ( sizpix < sizpixmin ) sizpix = sizpixmin;
	if ( sizpix > sizpixmax ) sizpix = sizpixmax;
	document.getElementById("dsprest").style.fontSize = sizpix/100*window.innerWidth/factechelle+"px";
	document.getElementById("sizpol").innerHTML = sizpix + " %";
}
var enable1 = true;
function toggleaudio1() {
	if (enable1) {
		document.getElementById("ok1").innerHTML = "Non";
		enable1 = false;
	}
	else {
		document.getElementById("ok1").innerHTML = "Oui";
		enable1 = true;
	}
}
var enable2 = true;
function toggleaudio2() {
	if (enable2) {
		document.getElementById("ok2").innerHTML = "Non";
		enable2 = false;
	}
	else {
		document.getElementById("ok2").innerHTML = "Oui";
		enable2 = true;
	}
}
var enable3 = true;
function toggleaudio3() {
	if (enable3) {
		document.getElementById("ok3").innerHTML = "Non";
		enable3 = false;
	}
	else {
		document.getElementById("ok3").innerHTML = "Oui";
		enable3 = true;
	}
}
</script>

<body>
	<center>
	<div id="pop" class="popup2">
	<p>&nbsp;</p>
	<p style='color:red;font-size: 4em;'>Donne suivante</p>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align:center; margin:auto;">

	<audio id="changePosition">
		<source src="changeposition.mp3" type="audio/mpeg">
		Your browser does not support the audio element.
	</audio>
	<audio id="nouvelleDonne">
		<source src="nouvelledonne.mp3" type="audio/mpeg">
		Your browser does not support the audio element.
	</audio>
	<audio id="letournoidemarre">
		<source src="letournoidemarre.mp3" type="audio/mpeg">
		Your browser does not support the audio element.
	</audio>
	
	<div id='titre'></div>
	<p><b><span id='dsprest'hidden>&nbsp;</span></b></p>	<!-- décompte -->
	
	<div id='affichage'></div>	<!-- tableau -->
	<p><button class="mySmallButton" onClick='gotoindex()'>Retour page d'accueil</button></p>
	<p><button onClick='toggleaudio1()'>Audio nouvelle donne</button>&nbsp;<b><span id='ok1'>Oui</span></b>&nbsp;&nbsp;&nbsp;
	<button onClick='toggleaudio2()'>Audio nouvelle position</button>&nbsp;<b><span id='ok2'>Oui</span></b>&nbsp;&nbsp;&nbsp;
	<button onClick='toggleaudio3()'>Audio démarrage tournoi</button>&nbsp;<b><span id='ok3'>Oui</span></b></p>
	<p><video id="vid" autoplay muted loop playsinline>		<!-- vidéo mise en veille -->
		<source src="vid-chat_AdobeExpress.mp4" type="video/mp4">
	</video></p>
	<p><button class='xNum2' onClick='setsizepx(-10)'><img src="images/signe-moins.png" height="20"/></button> taille chronomètre <b><span id='sizpol'>&nbsp;</span></b> largeur fenêtre <button class='xNum2' onClick='setsizepx(10)'><img src="images/signe-plus.png" height="20"/></button></p>
	<p><button id='testaudio' onClick='testaudio()'>Test audio</button></p>
	</div>
	
<script>
console.log( "idtournoi=", idtournoi, "etat=", etat );
dispatch( etat );
setsizepx(0);
toggleaudio1();
toggleaudio2();
toggleaudio3();
</script>

</body>
</html>