<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isFormateur() ){
	header("Location: logformateur.php");
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
</head>

<script>
idtournoi  = 0;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function logout() {
	var nextstring = "logout.php";
	location.replace( nextstring );
};
function setpassword() {
	var nextstring = "setpassword.php";
	location.replace( nextstring );
};
function admin() {
	var nextstring = "logadmin.php";
	location.replace( nextstring );
};
function cdeplus() {
	$("#afficheplus").addClass( "section_invisible" );
	$("#affichemoins").removeClass( "section_invisible" );
}
function cdemoins() {
	$("#afficheplus").removeClass( "section_invisible" );
	$("#affichemoins").addClass( "section_invisible" );
}
function gotoform11() {
	var nextstring = "bridgform11.php";
	location.replace( nextstring );
}
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgform.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2>Espace formation</h2>
	<h3> Bienvenue <?php print $_SESSION["pseudo"]; ?>
	
	<p>&nbsp;</p>
	<p><button class="myBigButton" onclick="gotoform11()">Préparer un diagramme</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	
	<p>&nbsp;</p>
	<div id="afficheplus">
	<p><button onclick="cdeplus()" style="font-style: italic;">Plus d'affichage</button></p>
	</div>
	<div id="affichemoins" class="section_invisible">
	<p><button onclick="cdemoins()" style="font-style: italic;">Moins d'affichage</button></p>
	<p><button onclick="logout()">Se déconnecter</button></p>
	<p><button onclick="setpassword()">Changer mon mot de passe</button></p>
	<p><button onclick="admin()">Administration</button></p>
	</div>
	
	</div>
</body>
</html>