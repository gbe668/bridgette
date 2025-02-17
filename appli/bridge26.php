<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

if ( file_exists( $file_calendar ) ) {
	$calendrier = json_decode( file_get_contents( $file_calendar ), true );
}
else $calendrier = array();
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="<?php echo $relpgm; ?>js/jquery-3.6.0.min.js"></script>
	<script src="<?php echo $relpgm; ?>js/jquery-ui-1.13.2.min.js"></script>
	<script src="js/bridge25.js"></script>
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/bridgestylesheet.css" />
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="<?php echo $relpgm; ?>images/favicon.ico">
</head>

<style>
#datepicker-container{
  text-align:center;
}
#datepicker-center{
  display:inline-block;
  margin:0 auto;
}
.cross:hover {
	cursor: pointer;
}
td.dayclose a {
    background: none !important;
	background-color:#FFC0CB !important;
    color: #006633;
}
td.dayopen a {
    background: none !important;
	background-color: lightgreen !important;
    color: #006633;
}
td.daygrey a {
    background: none !important;
	background-color: lightgrey !important;
    color: #006633;
}
</style>

<script>
var relpgm = "<?php echo $relpgm; ?>";
var parametres = <?php echo json_encode($parametres); ?>;	// jours d'ouverture
var calendrier = <?php echo json_encode($calendrier); ?>;

var userid = -1;
var userconnected = false;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
//
// paramètres de sélection date tournoi spécifique directeur: datepicker, ...
//
var seldate;	// format aaaa-mm-jj
$(document).ready(function() {		// sélection date tournoi
	datetournoi = $( "#datetournoi" ).datepicker({	// initialisation
		//var dateFormat = "mm/dd/yy",
		//defaultDate: +1,
		//numberOfMonths: 1
	})
	.datepicker('setDate', 'today')
	//.datepicker( "option", "maxDate", '+4w' )
	.datepicker( "option", "beforeShowDay", function (date){
		//let datjour = date.toISOString().slice(0,10);
		let datjour = date.getFullYear() + '-' + String((date.getMonth() + 1)).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
		if ( datjour in calendrier ) {
			let special = calendrier[datjour];
			let specialDay = (special.etat > 0) ? "dayopen" : "dayclose";
			return [ true, specialDay, special.obs ];	// special.etat seulement pour les joueurs
		}

		let dd = date.getDay();		// de 0 (dimanche) à 6 (samedi)
		let jour = listeJours[dd];
		if ( parametres.opendays[jour] == '1' )
			return [ true, "", "Club ouvert" ];
		else
			return [ true, "daygrey", "Club fermé" ];
	})
	.on( "change", function(event) {
		//console.log(event);
		selectDate();
	});
});
$(document).on( "click", "a.ui-corner-all ", function(event) {
	//console.log( "click", event );
	$("#section_calendrier").hide();
	$("#section_inscription").hide();
	$("#seljour").text( "???" );
})
function selectDate() {
	seldate = $("#datetournoi").val();
	//console.log( "change", seldate );
	let date = new Date( seldate.slice(0,4), seldate.slice(5,7)-1, seldate.slice(8,10) );
	$("#seljour").text( strdatet(seldate) );
	$("#section_calendrier").show();
	if ( seldate in calendrier ) {	// evénement spécial défini
		let special = calendrier[seldate];
		( special.etat > 0 ) ? $("#cluby").prop("checked", true) : $("#clubn").prop("checked", true);
		$("#msgevent").text(special.obs);
	}
	else {	// jour habituel
		$("#clubo").prop("checked", true);
		$("#msgevent").text(" ");
	}
	refreshinscriptions();
	//setTimeout(function() { $("#msgcalendrier").text( " " ); }, 2000);
}
function savecalendar() {
	let val = parseInt( $("input[type='radio'][name='affday']:checked").val() );
	if ( val < 0 ) {	// suppression event si exist
		//console.log("suppression event si exist");
		if ( seldate in calendrier ) { // remove event
			delete calendrier[ seldate ];
		}
	}
	else {
		//console.log("ajout/modif event", seldate );
		calendrier[ seldate ] = { etat:val, obs:$("#msgevent").val()};
	}
	// supprime les événements passés
	let d = new Date();
	//d.setDate(d.getDate() - 1);
	let datnow = d.toISOString().slice(0,10);
	for ( event in calendrier ) {
		if ( event < datnow ) {
			delete calendrier[event];
		}
	}
	// ajout pour contourner bug stringify
	calendrier.update = datnow;
	writecalendar();
	refreshinscriptions();
}
function writecalendar() {
	let jsoncal = JSON.stringify( calendrier );
	//console.log("calendrier, jsoncal", calendrier, jsoncal);
	$("#msgcalendrier").html( "Enregistrement en cours ...");
	$.get("savecalendar.php?", {jsoncal:jsoncal}, function(strjson) {
		$("#msgcalendrier").html(strjson);
	},"text")
	.done( function() {
		$("#msgcalendrier").html( "Enregistrement terminé." ); 
		setTimeout(function() { $("#msgcalendrier").text( " " ); }, 2000);
		} )
	.fail( function( jqxhr, settings, ex ) { $("#msgcalendrier").html('Erreur: '+ ex ); } );
}
function toggle_calendar() {
	$("#section_calendrier").toggle();
}
function refreshinscriptions() {
	$("#tabdujour").text( strdatet(seldate) );
	let date = new Date( seldate.slice(0,4), seldate.slice(5,7)-1, seldate.slice(8,10) );
	// test club ouvert pour afficher le tableau de pré-Inscription
	if ( seldate in calendrier ) {
		let special = calendrier[seldate];
		$("#errdatetournoi").html( special.obs );
		if (special.etat == 0) {
			$("#section_inscription").hide();
			return;
		}
	}
	else {
		let dd = date.getDay();		// de 0 (dimanche) à 6 (samedi)
		let jour = listeJours[dd];
		if ( parametres.opendays[jour] == '0' ) {
			$("#section_inscription").hide();
			return;
		}
	}
	$("#section_inscription").show();
	selectTournoi( seldate );
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
	<button onclick="gotoindex();">Retour page précédente</button>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align:center; max-width:350px; margin:auto;" id='topwindow'>
	<h2>Calendrier du club</h2>
	<p>Les jours habituels d'ouverture du club sont définis dans les paramètres de fonctionnement.</p>
	<p>On peut compléter le calendrier avec des événements spéciaux: tournois primés, fermeture exceptionnelle, salle indisponible, ... Sur <span style="background-color:lightgreen">fond vert</span> ou <span style="background-color:#FFC0CB">fond rouge</span>, les événements spéciaux déjà définis.</p>
	<div id="section_seltournoi">
	<h3>Sélectionnez une date:</h3>
	<div id="datepicker-container">
		<div id="datepicker-center">
			<div id="datetournoi"></div>
		</div>
	</div>
	</div>
	<h2>Date: <span id="seljour">???</span></h2>
	
	<div id="section_calendrier" hidden>
	<table style="width:100%;max-width:350px;margin:auto;background-color:#E2EFDA"><tbody>
	
	<tr><td><h3>Ouverture du club</h3></td><td><span class="cross" onclick="toggle_calendar()" style="border:.5pt solid">&#x274C;</span></td></tr>
	
	<tr><td>Selon paramètres fonctionnement</td><td><input type="radio" id="clubo" name="affday" value="-1"></td></tr>
	<tr><td>Evénement club ouvert</td><td><input type="radio" id="cluby" name="affday" value="1"></td></tr>
	<tr><td>Evénement club fermé </td><td><input type="radio" id="clubn" name="affday" value="0"></td></tr>
	<tr><td colspan="2"><textarea id="msgevent" Cols="40" Rows="3" placeholder="Raison ..."></textarea></td></tr>
	<tr><td colspan="2">Enregistre modification <button class="myButton" id="valid1" onClick="savecalendar()"><img src="images/save.png" style="width:20px;" /></button></td></tr>
	<tr><td colspan="2"><span id="msgcalendrier">&nbsp;</span></td></tr>
	
	</tbody></table>
	</div>

	<h2>Pré-inscriptions</h2>
	
	<div id="msgdatetournoi"></div>
	
	<div id="section_inscription" hidden>
	<table style="width:100%;max-width:350px;margin:auto;background-color:#E2EFDA"><tbody>
	<tr><td style="width:90%"><h3>Tournoi du <span id="tabdujour">???</span></h3></td><td><span class="cross" onclick="close_inscription()" style="border:.5pt solid">&#x274C;</span></td></tr>
	<tr><td colspan="2">
		<div id="section_tableau" hidden>
		<div id="tabinscrits">&nbsp;</div>
		<p id="msgtabinscrits">&nbsp;</p>
		</div>
		<div id="menu_action" hidden>action</div>
		<div id="section_clavier" hidden>
		<div id="clavier">clavier</div>
		</div>
	</td></tr></tbody></table>
	</div>
	
	<p id='msgerr'>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>

	<div class="top"><img src="images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="goto40()"/>
	</div>
	
	<script>
	// valeurs par défaut
	$("#section_tableau").hide();
	$("#section_clavier").hide();
	$("#clavier").html( displayClavierSaisieJoueur() );
	</script>
 </body>
</html>