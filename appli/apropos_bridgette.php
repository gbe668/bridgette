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
	<style>
	div.return {
	  position: fixed;
	  top: 10px;
	  right: 10px;
	}
	.return:hover {
		cursor: pointer;
	}
	</style>
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
</script>

<body>	
	<div style="text-align: center">
	<h1 id="top">Application conçue et réalisée pour les clubs de bridge</h1>
	<p>Bridg'ette permet d'enregistrer le déroulement et les résultats d'un tournoi de bridge
	de type "Mitchell" de 5 tables à 13 tables et "Howell" de 4 à 8 paires. Le nombre d'étuis par table est ajustable avant démarrage pour les deux types de tournois. A la fin d'un tournoi, l'application donne les résultats et le classement respectif des paires avec des possibilités d'arbitrage ou de correction de résultats.</p>
	<p>L'application est destinée principalement aux clubs de bridge qui ne sont pas affiliés à une fédération et donc qui ne sont pas connectés aux moyens informatiques de cette fédération.</p>
	<p>Le matériel utilisé est le smartphone de l'un des joueurs de la table. L'application a besoin pour fonctionner d'un site web avec base de données propre à chaque club utilisateur. La base de données stocke les noms des joueurs, les résultats successifs lors du tournoi et les classements des joueurs pour chaque tournoi.</p>
	<h2>Mode d'emploi: <a href ="mode d'emploi bridgette.pdf" target="_blank">présentation rapide du fonctionnement</a> pour les joueurs et directeurs de tournoi</h2>
	<h2><a href ="Types_tournois.pdf" target="_blank">Types de tournois</a> implémentés</h2>
	<?php
	print htmlTableTypeTournois();
	?>
	<h2>Documentation: <a href ="guide.htm" target="_blank">description détaillée de l'application</a> de l'installation au paramétrage ...</h2>
	<h3>Crédits</h3>
	<p>Conçu et réalisé par Bruno Coiffier.</br>Contact: bridgette@coiffier.org</br>
	Copyright (C) 2024  Bruno Coiffier</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	<h2>Licence</h2>
	</div>

	<?php
	$Emplacement = "./license_bridgette.txt";
	if ( file_exists($Emplacement) ) {

		$lines = file($Emplacement);
		$count = count($lines);
		foreach ($lines as $line_num => $line) {
			// Affiche de la ligne en la convertissant en code HMTL
			if (mb_detect_encoding($line, 'UTF-8', true) === false) {$line = utf8_encode($line);} //Codage en utf8 pour que l'affichage se passe bien sur cette page qui est en utf8
			echo $line."</br>";
		}
	}
	?>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/></div>
</body>
</html>