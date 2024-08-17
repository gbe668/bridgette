<?php
require("configuration.php");
require("bridgette_bdd.php");
?>

<!DOCTYPE HTML>
<html>
    <head>	
        <title>Bridg'ette</title>	
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
        <meta name="viewport" content="width=device-width, initial-scale=1">	
        <link rel="stylesheet" href="css/bridgestylesheet.css" />
		<link rel="icon" type="image/x-icon" href="images/favicon.ico">
    </head>
<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function gotodownloadapk() {
	var urlDuFichier = "<? echo $base_url.'app-release.apk' ?>";
	window.open(urlDuFichier,"_blank", null);
}
</script>

<body>	
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>	
	</div>
	
	<h2>Attention: lisez ce qui suit pour installer l'application sur votre téléphone Android</h2>
	<h3>Installation</h3>
	<p>Android protège les utilisateurs contre le téléchargement et l'installation par inadvertance d'applications ne provenant pas d'une plate-forme de téléchargement d'applications propriétaire fiable telle que Google Play. <span style="color:red">Ces installations sont bloquées tant que l'utilisateur n'accepte pas l'installation d'applications provenant d'autres sources.</span> Pour continuer, vous devez donc autoriser l'installation à partir d'une autre source.</p>
	<p>Le processus d'activation dépend de la version d'Android qui est exécutée sur l'appareil de l'utilisateur : suivez les indications de votre appareil.<p>
	<div style="text-align: center">
	<p><button onclick="gotodownloadapk()">Télécharger l'appli android</button></p>
	</div>
	<p>A l'issue du téléchargement, cliquez sur le fichier téléchargé pour lancer l'installation sur votre téléphone.</p>
	
	<h3>Après installation</h3>
	<p>Après l'installation de l'application sur votre téléphone, il faudra entrer l'adresse du serveur de votre club et vous identifier par votre mail et un mot de passe.</p>
	<p>Si vous n'avez pas encore créé de mot de passe sur le serveur du club ou si vous l'avez oublié, cliquez sur <em>"Mot de passe oublié ?"</em>, vous recevrez alors un mail de réinitialisation de mot de passe.</p>
	
	<h3>Mise à jour / réinstallation</h3>
	<p>Avant de procéder à une mise à jour de l'application, il faudra peut-être supprimer l'application existante, puis la réinstaller.</p>

	<div style="text-align: center">
	<p>version 13 juin 2023</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
</body>
</html>