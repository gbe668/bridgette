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
</head>

<body>	
	<div style="text-align: center">
	<h1 id="top">Application conçue et réalisée pour les clubs de bridge</h1>
	<p>Bridg'ette permet d'enregistrer le déroulement et les résultats d'un tournoi de bridge
	de type "Mitchell" de 5 tables à 13 tables et "Howell" de 4 à 8 paires. Le nombre d'étuis par table est ajustable avant démarrage pour les deux types de tournois. A la fin d'un tournoi, l'application donne les résultats et le classement respectif des paires avec des possibilités d'arbitrage ou de correction de résultats.</p>
	<p>L'application est destinée principalement aux clubs de bridge qui ne sont pas affiliés à une fédération et donc qui ne sont pas connectés aux moyens informatiques de cette fédération.</p>
	<p>Le matériel utilisé est le smartphone de l'un des joueurs de la table. L'application a besoin pour fonctionner d'un site web avec base de données propre à chaque club utilisateur. La base de données stocke les noms des joueurs, les résultats successifs lors du tournoi et les classements des joueurs pour chaque tournoi.</p>
	<p>Bridgette inclut le programme "double dummy solver DDS" développé par <a href="https://privat.bahnhof.se/wb758135/index.html" target="_blank">Bo Haglund et al.</a> ainsi que son implémentation en javascript réalisé par <a href="https://github.com/danvk/dds.js" target="_blank">Dan Vanderkam (danvk)</a>, programmes sous license <a href="http://www.apache.org/licenses/LICENSE-2.0" target="_blank">Apache 2.0</a>.</p>
	<p>Bridgette peut exporter les donnes d'un tournoi pour analyse avec <a href = "https://mirgo2.co.uk/bridgesolver/"  target="_blank">Bridge Solver Online</a> développé par <b>John Goacher</b> mail: goacher.apps@gmail.com</p>
	<h3>Crédits</h3>
	<p>Conçu et réalisé par Bruno Coiffier.</br>Contact: bridgette@coiffier.org</br>
	Copyright (C) 2022, 2023  Bruno Coiffier</p>
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
</body>
</html>