<?php
require("configuration.php");
require("bridgette_bdd.php");

$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
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
.bigDigit {
	font-size:12em;
	font-weight:bold;
	text-align:center;
	vertical-align:middle;
	margin-top: 0;
	margin-bottom: 0;
	}
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
screenw  = parseInt( "<?php echo $screenw; ?>" );
if ( isNaN( screenw ) ) {
	location.replace( "bigscreen.php?w=" + window.innerWidth );
};
	
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};

var st_notfound   = parseInt( "<?php echo $st_notfound; ?>" );
var st_phase_init = parseInt( "<?php echo $st_phase_init; ?>" );
var st_phase_jeu  = parseInt( "<?php echo $st_phase_jeu; ?>" );
var st_phase_fini = parseInt( "<?php echo $st_phase_fini; ?>" );
var st_closed = parseInt( "<?php echo $st_closed; ?>" );

var audiofindonne = 0;
var audiofinposition = 0;
var declic = 10;			// x secondes avant la fin du délai

function Timer_notfound() {		// attente création tournoi
	$.get( "existeTournoiNonClos.php", {}, function( id ) {
		if ( id > 0 ) {
			clearInterval( myTimer );
			location.replace( "bigscreen.php?w=" + window.innerWidth );
		}
	},"text");
}
function Timer_phase_init() {	// attente démarrage tournoi
	$.get( "getetattournoi.php", {idtournoi:idtournoi}, function( strjson ) {
		if ( strjson.etat != st_phase_init ) {
			clearInterval( myTimer );
			location.replace( "bigscreen.php?w=" + window.innerWidth );
		}
	},"json");
}
function Timer_phase_jeu() {	// pendant le tournoi
	$.get( "getpositionsjoueurs.php", {idtournoi:idtournoi}, function( json ) {
		console.log( "hello", json.etat );
		switch (json.etat) {
			case st_notfound: {
				console.log( "st_notfound" );
				myTimer = setInterval(Timer_notfound, 10000);
				break;
			}
			case st_phase_jeu: {
				$("#realtour").html( "<h2>Tournoi en cours</h2>"
					+ "<div style='text-align:center; margin:auto; max-width:700px; font-size:1.25em;'>"
					+ json.positions
					+ "</div>" );
				// décompte
				var now = new Date().getTime()/1000;	// en secondes
				var diff = Math.trunc( json.endofseq - now );
				var neg = ( diff < 0 ) ? true : false;
				
				var next = diff%json.tempo;
				// test fin position théorique
				if ( diff > declic ) {		// reste plus de declic secondes
					audiofinposition = 0;
					// test fin donne
					if ( next > declic ) audiofindonne = 0;
					else {
						if ( audiofindonne == 0 ) {
							console.log( "nouvelleDonne" );
							audiofindonne = 1;
							var audio1 = document.getElementById("nouvelleDonne");
							//audio1.play();				
							pop.style.display = "inline-block";
							setTimeout(function() { pop.style.display = "none"; }, 3000);
						}
					}
				}
				else {		// reste moins de declic secondes
					if ( (diff > 0)&&(audiofinposition == 0) ) {
						console.log( "changePosition" );
						audiofinposition = 1;
						var audio2 = document.getElementById("changePosition");
						audio2.play();
					}
				}
				diff = Math.abs( diff );
				var reste = new Date(diff * 1000).toISOString().slice(14, 19);
				if ( neg ) $("#dsprest").html( "&nbsp;<span style='color:red;'>" + reste + "</span>&nbsp;" );
				else $("#dsprest").html( "&nbsp;" + reste + "&nbsp;" );
				$("#dsprest").show();
				break;
			}
			case st_phase_fini: {
				console.log( "st_phase_fini" );
				$("#realtour").html( "<h2 style='text-align: center; color:red'>Résultats provisoires</h2>"
					+ "<div style='text-align:center; margin:auto; max-width:700px;'>"
					+ json.positions
					+ "</div>" );
				$("#dsprest").hide();
				clearInterval( myTimer );
				myTimer = setInterval(Timer_phase_fini, 5000);
				//location.replace( "bigscreen.php?w=" + window.innerWidth );
				break;
			}
		}
	},"json");
}
function Timer_phase_fini() {	// en phase de clôture
	// affichage des résultats provisoires
	$.get( "getpositionsjoueurs.php", {idtournoi:idtournoi}, function( json ) {
		if ( json.etat == st_phase_fini ) {
			$("#realtour").html( "<h2 style='text-align: center; color:red'>Résultats provisoires</h2>"
				+ "<div style='text-align:center; margin:auto; max-width:700px;'>"
				+ json.positions
				+ "</div>" );
		}
		else {
			clearInterval( myTimer );
			location.replace( "bigscreen.php?w=" + window.innerWidth );
		}
	},"json");
}
function Timer_phase_closed() {	// clôture
	// affichage des résultats provisoires
	$.get( "getetattournoi.php", {idtournoi:idtournoi}, function( strjson ) {
		if ( strjson.etat != st_phase_fini ) {
			clearInterval( myTimer );
			location.replace( "bigscreen.php?w=" + window.innerWidth );
		}
	},"json");
}

// A helper to add sources to video
function addSourceToVideo(element, type, dataURI) {
    var source = document.createElement('source');
    source.src = dataURI;
    source.type = 'video/' + type;
    element.appendChild(source);
}

// A helper to concat base64
var base64 = function(mimeType, base64) {
    return 'data:' + mimeType + ';base64,' + base64;
};
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
	
	<?php
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
	
	switch( $etat ) {
		case $st_notfound: {
			$aff = "<h2>Pas de tournoi en cours ou en préparation </h2>";
			$aff .= "<p>Pas de tournoi enregistré.</p>";
			break;
		}
		case $st_phase_init: {
			$aff = "<h2>Tournoi en préparation</h2>";
			$aff .= "<p>attendez le démarrage ...</p>";
			break;
		}
		case $st_phase_jeu: {
			$aff = "<h2>Tournoi en cours</h2>";
			$aff .= "<p style='color:red;font-size: 1.2em;'>tableau des positions en cours de chargement ...</p>";
			break;
		}
		case $st_phase_fini: {
			$aff = "<h2 style='text-align: center; color:red'>Résultats provisoires</h2>";
			if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
				setTournoiIMP( $idtournoi );
				$aff .= htmlDisplayTournoiIMP( $idtournoi );
			}
			else {
				setTournoi($idtournoi);
				$aff .= htmlDisplayTournoi( $idtournoi, $screenw );
			}
			break;
		}
		case $st_closed: {
			// Affichage des résultats du dernier tournoi enregistré.
			if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
				setTournoiIMP( $idtournoi );
				$aff = htmlDisplayTournoiIMP( $idtournoi );
			}
			else {
				setTournoi( $idtournoi );
				$aff = htmlDisplayTournoi($idtournoi, $screenw);
			}
			break;
		}
	}
	?>
	<p id='realtour'></p>	<!-- tableau -->
	<p id='dsprest'></p>	<!-- décompte -->
	<p id='salon'></p>		<!-- vidéo mise en veille -->
	<p><button onclick='video.play()'>Cliquez ici pour ne pas avoir de mise en veille</button></p>
	<button id='sos' onClick='gotoindex()'>Retour page d'accueil</button>
	</div>
	
<script>
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var etat = parseInt( "<?php echo $etat; ?>" );

var myTimer;	// timer
switch (etat) {
	case st_notfound: {
		console.log( "st_notfound" );
		$("#realtour").html( "<?php echo $aff; ?>" );
		myTimer = setInterval(Timer_notfound, 10000);
		break;
	}
	
	case st_phase_init: {
		console.log( "st_phase_init" );
		$("#realtour").html( "<?php echo $aff; ?>" );
		myTimer = setInterval(Timer_phase_init, 5000);
		break;
	}
	
	case st_phase_jeu: {
		console.log( "st_phase_jeu" );
		$("#realtour").html( "<?php echo $aff; ?>" );
		$("#dsprest").html( "&nbsp;??:??&nbsp;" )
		.addClass( "bigDigit" );
		myTimer = setInterval(Timer_phase_jeu, 3000);
		break;
	}
	
	case st_phase_fini: {
		console.log( "st_phase_fini" );
		$("#realtour").html( "<?php echo $aff; ?>" );
		myTimer = setInterval(Timer_phase_fini, 5000);
		break;
	}
	
	case st_closed: {
		console.log( "st_closed" );
		$("#realtour").html( "<?php echo $aff; ?>" );
		//myTimer = setInterval(Timer_phase_closed, 5000);
		break;
	}
	
	default:
}

// Create the root video element
var video = document.createElement('video');
video.setAttribute('loop', '');
// Add some styles if needed	//video.setAttribute('style', 'position: fixed;');
addSourceToVideo(video, 'mp4', "vid-chat_AdobeExpress.mp4");
// Append the video to where ever you need
document.getElementById("salon").appendChild(video);

/* pour mémoire

// Add Fake sourced
addSourceToVideo(video,'webm', base64('video/webm', 'GkXfo0AgQoaBAUL3gQFC8oEEQvOBCEKCQAR3ZWJtQoeBAkKFgQIYU4BnQI0VSalmQCgq17FAAw9CQE2AQAZ3aGFtbXlXQUAGd2hhbW15RIlACECPQAAAAAAAFlSua0AxrkAu14EBY8WBAZyBACK1nEADdW5khkAFVl9WUDglhohAA1ZQOIOBAeBABrCBCLqBCB9DtnVAIueBAKNAHIEAAIAwAQCdASoIAAgAAUAmJaQAA3AA/vz0AAA='));
addSourceToVideo(video, 'mp4', base64('video/mp4', 'AAAAHGZ0eXBpc29tAAACAGlzb21pc28ybXA0MQAAAAhmcmVlAAAAG21kYXQAAAGzABAHAAABthADAowdbb9/AAAC6W1vb3YAAABsbXZoZAAAAAB8JbCAfCWwgAAAA+gAAAAAAAEAAAEAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAIVdHJhawAAAFx0a2hkAAAAD3wlsIB8JbCAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAQAAAAAAIAAAACAAAAAABsW1kaWEAAAAgbWRoZAAAAAB8JbCAfCWwgAAAA+gAAAAAVcQAAAAAAC1oZGxyAAAAAAAAAAB2aWRlAAAAAAAAAAAAAAAAVmlkZW9IYW5kbGVyAAAAAVxtaW5mAAAAFHZtaGQAAAABAAAAAAAAAAAAAAAkZGluZgAAABxkcmVmAAAAAAAAAAEAAAAMdXJsIAAAAAEAAAEcc3RibAAAALhzdHNkAAAAAAAAAAEAAACobXA0dgAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAIAAgASAAAAEgAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABj//wAAAFJlc2RzAAAAAANEAAEABDwgEQAAAAADDUAAAAAABS0AAAGwAQAAAbWJEwAAAQAAAAEgAMSNiB9FAEQBFGMAAAGyTGF2YzUyLjg3LjQGAQIAAAAYc3R0cwAAAAAAAAABAAAAAQAAAAAAAAAcc3RzYwAAAAAAAAABAAAAAQAAAAEAAAABAAAAFHN0c3oAAAAAAAAAEwAAAAEAAAAUc3RjbwAAAAAAAAABAAAALAAAAGB1ZHRhAAAAWG1ldGEAAAAAAAAAIWhkbHIAAAAAAAAAAG1kaXJhcHBsAAAAAAAAAAAAAAAAK2lsc3QAAAAjqXRvbwAAABtkYXRhAAAAAQAAAABMYXZmNTIuNzguMw=='));

// Start playing video after any user interaction.
// NOTE: Running video.play() handler without a user action may be blocked by browser.
var playFn = function() {
    video.play();
    //document.body.removeEventListener('keydown', playFn);
};
document.body.addEventListener("keydown", playFn);
*/
</script>

</body>
</html>