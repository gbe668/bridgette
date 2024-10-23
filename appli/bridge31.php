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
	<title>Bridge</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge30.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto30() {
	var nextstring = "bridge30.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	
	<h2>Création d'un nouveau joueur</h2>
	<p>Les champs marqués (*) sont obligatoires</p>
	
	<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Genre:(*) Monsieur <input type="radio" id="male" name="gender" value="Mr"> Madame
	<input type="radio" id="female" name="gender" value="Me">
	<p>Prénom:(*)&nbsp;<input type="text" id="fname" name="fname" placeholder="Prénom" size="20"></p>
	<p>&nbsp;&nbsp;&nbsp;&nbsp;Nom:(*)&nbsp;<input type="text" id="lname" name="lname" placeholder="Nom" size="20">
	<p>Téléphone:&nbsp;<input type="text" id="phone" name="phone" placeholder="Numéro" size="20">
	<p>&nbsp;&nbsp;Email:&nbsp;<input type="text" id="email1" name="email1" placeholder="monadresse@mondomaine" size="40"></p>
	<p>N°club:&nbsp;<input type="text" id="noclub" name="noclub" placeholder="N° club" size="4" readonly>&nbsp;non modifiable</br>attribué automatiquement</p>
	
	<p><button class="myButton" id="valid1" onClick="creerjoueur()">Créer le joueur</button></p>
	<p id="msgerr1">&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto40()">Retour page création d'un tournoi</button></p>
	
	<p><button class="mySmallButton" onclick="goto30()">Retour page gestion des joueurs</button></p>
	</div>
</body>
</html>