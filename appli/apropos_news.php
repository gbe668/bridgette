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
<style>
.xlig {
	vertical-align:middle;
	text-align:left;
	border:.5pt solid windowtext;
	padding-left: 5px;
	padding-right: 5px;
	border-top:none;
	border-left:none;
	background:#ffffff;
	}

</style>
<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
</script>

<body>	
	<h1 style="text-align: center" id="top">Suivi des versions de l'application</h1>
	
	<table><tbody>
	<tr><th class='xlig'>Date</th><th class='xlig'>Description de l'évolution</th></tr>
	
	<tr><td class='xlig'>17/07/2026</td><td class='xlig'>Bug: Correction du nombre de donnes en circulation sur tournoi howell 9 paires</td></tr>
	<tr><td class='xlig'>17/07/2026</td><td class='xlig'>Fix: Ajout table des événements dans l'export de la base de données</td></tr>
	<tr><td class='xlig'>17/07/2026</td><td class='xlig'>Ajout page de suivi des versions successives</td></tr>
	<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
	<tr><td class='xlig'>14/07/2026</td><td class='xlig'>Fix: adaptation gestion base de données pour être compatible avec PHP 8.5</td></tr>
	<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
	
	<tr><td class='xlig'>12/07/2026</td><td class='xlig'>Modification calendrier de pré-incription pour afficher les semaines où la pré-inscription est possible sur la même page</td></tr>
	</tbody></table>
	<p style="text-align: center"><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/></div>
</body>
</html>