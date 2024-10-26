<?php
require("configuration.php");
require("bridgette_bdd.php");

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
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function goto30() {	// gestion joueurs
	var nextstring = "bridge30.php";
	location.replace( nextstring );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
var liste = 1;	// au démarrage
var ordre = "nom";
var strname = "";
function liste_joueurs() {
	console.log( "liste_joueurs", liste, ordre, strname );
	$.get("getlistejoueurs.php", {liste:liste,ordre:ordre,filtre:strname}, function(strhtml) {
		$("#listejoueurs").html(strhtml);
	});
}
/*
$(document).ready(function() {
	$("button.selliste").click( function(event) {
		console.log( "event", event.target.id );
		var figs = event.target.id.split('_');
		liste = figs[1];
		$("#liste_1").removeClass( "oktogoon" );
		$("#liste_2").removeClass( "oktogoon" );
		$("#liste_3").removeClass( "oktogoon" );
		if ( liste == 1 ) $("#liste_1").addClass( "oktogoon" );
		if ( liste == 2 ) $("#liste_2").addClass( "oktogoon" );
		if ( liste == 3 ) $("#liste_3").addClass( "oktogoon" );
		liste_joueurs();
		});
});
*/
$(document).on( "click", "button.selliste", function(event) {
	console.log( "event", event.target.id );
	var figs = event.target.id.split('_');
	liste = figs[1];
	$("#liste_1").removeClass( "oktogoon" );
	$("#liste_2").removeClass( "oktogoon" );
	$("#liste_3").removeClass( "oktogoon" );
	if ( liste == 1 ) $("#liste_1").addClass( "oktogoon" );
	if ( liste == 2 ) $("#liste_2").addClass( "oktogoon" );
	if ( liste == 3 ) $("#liste_3").addClass( "oktogoon" );
	liste_joueurs();
});
$(document).on( "click", "td.selordre", function(event) {
	console.log( "event", event.target.id );
	var figs = event.target.id.split('_');
	ordre = figs[1];
	liste_joueurs();
});
$(document).on( "click", "td.canclick", function(event) {
	console.log( "event", event.target.id );
	var figs = event.target.id.split('_');
	var nextstring = "bridge35.php?id=" + figs[1];
	if ( figs[1] > 0 ) location.replace( nextstring );
});
// filtre joueurs
$(document).keydown(function(event) {
	var touche = event.key;
	//console.log( touche );
	switch( true ) {
		case event.key == 'Backspace':
			let len = strname.length
			strname = strname.slice(0,len-1);
			$("#btn3").text( strname );
			liste_joueurs();
			break;
		case event.key == 'Escape':
			strname = "";
			$("#btn3").text( strname );
			liste_joueurs();
			break;
		//case event.key == ' ':
		case event.key >= 'a' && event.key <= 'z':
			strname = strname + event.key;
			$("#btn3").text( strname );
			//if ( strname.length > 1 ) liste_joueurs();
			liste_joueurs();
			break;
		default:
	}
});
$(function() {      
    let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;

    if (isMobile) {
        //Conditional script here
		$('#btnalpha').addClass( 'section_invisible' );
    }
 });
 </script>

 <body>
	<div style="text-align:center; max-width:350px; margin:auto;">
	<h2 style='text-align: center' id='topwindow'>Joueurs enregistrés</h2>
	<p><button id="liste_1" class="mButton selliste oktogoon">Joueurs actifs</button> <button id="liste_2" class="mButton selliste">Anciens joueurs</button> <button id="liste_3" class="mButton selliste">Tous</button></p>

	<div id="btnalpha">
	<p>et dont le nom commence par: <button id="btn3"></button></p>
	</div>

	<p><span style='color:grey'>En gris les anciens joueurs</span></br>
	<span style='color:blue'>En bleu les joueurs "android"</span></br>Date dernier tournoi joué</br>Nombre de tournois en BDD joués</p>
	
	<div id='listejoueurs'>
	<?php
	print getListeJoueurs(1, "nom", "");
	?>
	</div>
	
	<p><button class="mySmallButton" onclick="goto30()">Retour page gestion des joueurs</button></p>
	</div>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="goto30()"/></div>
	<div class="top"><img src="images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
 </body>
 </html>