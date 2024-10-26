<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libcontacts.php");

$resultIdent = getIdent();
if ( $resultIdent['status'] == $ID_INCORRECT ) {
	if ( isset($_GET['noreturn']) ) {
		header("Location: loguserin.php?noreturn");
	}
	else {
		header("Location: loguserin.php");
	}
}
else $userid = $resultIdent['userid'];
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery-ui-1.13.2.min.js"></script>
	<script src="js/bridge25.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="stylesheet" href="css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var parametres = <?php echo json_encode($parametres); ?>;
console.log( parametres.opendays );

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto60() {
	var nextstring = "bridge60.php";
	location.replace( nextstring );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) )
		$("#afficheplus").removeClass( "section_invisible" );
	else
		$("#afficheplus").addClass( "section_invisible" );
}
function cdemoins() {
	$("#afficheplus").addClass( "section_invisible" );
}
function masquerecherche() {
	$("#section_inscription").hide();
	$("#section_edition").hide();
}
</script>
 
 <body>
	<div style="text-align:center; max-width:350px; margin:auto;" id='topwindow'>
	<?php
	if ( $parametres['checkuser'] > 0 ) {
		$joueur = getJoueur( $userid );
		print "<p>Hello ".$joueur['nomcomplet']."</p>";
	}
	
	if ( !isset($_GET['noreturn']) ) {
		print '<h2 style="text-align: center">Annuaire des joueurs</h2>';
		print '<p><button class="myButton" onclick="cdeplus()">Affiche/masque annuaire</button></p>';
		print '<div id="afficheplus" class="section_invisible">';
		print htmlAnnuaire();
		print '<p><button class="myButton" onclick="cdemoins()">Masque annuaire</button></p>';
		print '</div>';
	}
	?>
	
	<h2 style='text-align: center'>Recherche partenaire</h2>
	<h3>Choisssez une date de tournoi:</h3>
	<div id="datetournoi"></div>
	<p id="msg">&nbsp;</p>
	<div id="section_recherche">
	<div id="section_inscription">
		<p>Vous recherchez un partenaire ?</br><b>Alors, inscrivez-vous !</b></p>
		<p>Prénom Nom <input type="text" id="name" placeholder="Nom" size="20" value="<?php
		if ( $parametres['checkuser'] > 0 ) echo $joueur['nomcomplet'];
		?>"></p>
		<p>Tél Contact <input type="text" id="contact" placeholder="Téléphone" size="20"></p>
		<p><textarea type="text" id="memo"  Cols="40" Rows="5" placeholder="Optionnel, un petit mot pour vous présenter et plus ..."></textarea></p>
		<p><button class="mButton" onclick="insertContact()">Enregistrer</button></p>
		<p><button class="myButton" onclick="masquerecherche()">Masque recherche</button></p>
	</div>
	<div id="section_edition">
		<p>Joueur <b><span id='nomjoueur'></span></b></p>
		<p>Tél Contact <input type="text" id="contact2" placeholder="Téléphone" size="20"></p>
		<p><textarea type="text" id="memo2"  Cols="40" Rows="5" placeholder="Optionnel, un petit mot pour vous présenter et plus ..."></textarea></p>
		<p><button class="mButton" onclick='updateContact()'>Mettre à jour</button> <button class="mButton" onclick='eraseContact()'>Se désinscrire</button> <button class="mButton annule" onclick='annuleEraseContact()'>Annuler</button></p>
		<p><button class="myButton" onclick="masquerecherche()">Masque recherche</button></p>
	</div>
	</div>
	<p id="msgerr">&nbsp;</p>

	<?php
	if ( !isset($_GET['noreturn']) ) {
		print '<p><button class="mySmallButton" onclick="goto60()">Retour page précédente</button></p>';
		print '<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>';
		print '</div>';
	}
	?>
	<div class="top"><img src="images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
	</div>
	<script>
	// valeurs par défaut
	$('#datetournoi').datepicker();	// initialisation
	$('#datetournoi').datepicker('setDate', 'today');
	$('#datetournoi').datepicker( "option", "maxDate", '+4w' );
	$("#datetournoi").datepicker( "option", "beforeShowDay", noTournois );
	$("#section_inscription").hide();
	$("#section_edition").hide();
	
	var prefdir = "";	// distingo site / android
	</script>
 </body>
</html>