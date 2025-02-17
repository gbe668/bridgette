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
idtournoi  = 0;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto30() {	// gestion joueurs
	var nextstring = "bridge30.php";
	location.replace( nextstring );
};
function goto31() {	// ajout d'un nouveau joueur
	var nextstring = "bridge31.php";
	location.replace( nextstring );
};
function goto33() {
	var nextstring = "bridge33.php";
	location.replace( nextstring );
};
function goto34() {	// modif / suppression d'un joueur
	var nextstring = "bridge34.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function exportcsv() {
	$.get( "f30joueurs2csv.php", function(csvStr) {
		$("#liste").text( csvStr );
		var hiddenElement = document.createElement('a');
		hiddenElement.href = 'data:text/csv;charset=utf-8,%EF%BB%BF' + encodeURI(csvStr);
		hiddenElement.target = '_blank';
		hiddenElement.download = "joueurs.csv";
		hiddenElement.click();
	}, );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	
	<h2>Gestion des joueurs du club</h2>
	<p>&nbsp;</p>
	<p><button class="myBigButton" onclick="goto34()">Lister les joueurs enregistrés</br>Modifier / supprimer un joueur</button></p>
	<p>&nbsp;</p>
	<p><button class="myBigButton" onclick="goto31()">Ajouter un nouveau joueur</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>
	<p>&nbsp;</p>
	<p><button class="mButton" id="valid3" onClick="exportcsv()">Télécharger la liste des joueurs</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="goto40()"/>
	</div>
</body>
</html>