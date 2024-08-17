<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isFormateur() ){
	header("Location: logformateur.php");
	exit(); 
}
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'form</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
</head>

<script>
function gotoindex() {
	var nextstring = "bridgform10.php";
	location.replace( nextstring );
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgform.png" alt="bridge" style="width:90%; max-width:350px;" />	</p>	
	<h2>Entrée des diagrammes d'une donne</h2>
	
	<?php
	print_section_diagramme();
	print '<div id="section_inputdiags">';
	print '<p id="msg">&nbsp;</p>';
	print_clavier_diagramme();
	?>
	
	<p id="dealfield" hidden>&nbsp;</p>
	</div>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour votre espace</button></p>

	<div id="section_validiags" class="section_invisible">
	<p><button class="myButton" id="valid1" onClick="">OK</button></p>
	</div>

	<div id="tstvalidok" class="section_invisible">
	<p id="validok">Enregistrement en cours ...</p>
 	</div>
  
	<script>
	$("#section_diagramme").removeClass( "section_invisible");
	initcanselect();
	setfocus( 1 );
	</script>
	</div>
</body>
</html>