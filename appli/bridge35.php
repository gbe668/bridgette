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
function supprimerjoueur( indice ) {
	$("#deljoueur").text( oldjoueurs[indice] );
	$("#section_confirme_suppression").removeClass( "section_invisible" );
	var elmnt = document.getElementById("section_confirme_suppression");
	elmnt.scrollIntoView();
};
function restaurerjoueur( indice ) {
	$("#msgerr1").text( "Restauration en cours ..." );
	$.get( "f30restorejoueur.php", {idjoueur:idjoueur}, function(strjson) {
		$("#msgerr1").text( strjson.msg );
		setTimeout(function() { goto30(); }, 1000);
	},"json");
};
function clickAnnulationSuppressionJoueur() {
	$("#section_confirme_suppression").addClass( "section_invisible" );
}
function clickConfirmeSuppressionJoueur() {
	$("#msgerr1").text( "Suppression en cours ..." );
	$.get( "f30deletejoueur.php", {idjoueur:idjoueur}, function(strjson) {
		$("#msgerr1").text( strjson.msg );
		setTimeout(function() { goto30(); }, 1000);
	},"json");
};

function effacerjoueur() {
	$("#msgerr1").text( "Effacement en cours ..." );
	$.get("f30erasejoueur.php", {idjoueur:idjoueur}, function(strjson) {
		$("#msgerr1").text( strjson.msg );
		setTimeout(function() { goto30(); }, 1000);
	}, "json");
};

</script>

<body>
	<div style="text-align:center; max-width:350px; margin:auto;">
	
	<h2>Edition d'un joueur</h2>
	<?php
	$idjoueur = htmlspecialchars( $_GET['id'] );
	$joueur = getJoueur( $idjoueur );
	?>
	
	<p>Les champs marqués (*) sont obligatoires</p>
	
	<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Genre:(*) Monsieur <input type="radio" id="male" name="gender" <?php echo ($joueur['genre']=='Mr')?'checked':'' ?> value="Mr"> Madame
	<input type="radio" id="female" name="gender" <?php echo ($joueur['genre']=='Me')?'checked':'' ?>  value="Me">
	
	<p>Prénom:(*)&nbsp;<input type="text" id="fname" value="<?php echo $joueur['prenom'] ?>" size="20"></p>
	<p>&nbsp;&nbsp;&nbsp;&nbsp;Nom:(*)&nbsp;<input type="text" id="lname" value="<?php echo $joueur['nom'] ?>" size="20">
	<p>&nbsp;&nbsp;Email:&nbsp;<input type="text" id="email1" value="<?php echo $joueur['email'] ?>" size="40"></p>
	<p>N°club:&nbsp;<input type="text" id="noclub" name="noclub" value="<?php echo $joueur['numero'] ?>" size="4" readonly>&nbsp;non modifiable</br>attribué automatiquement</p>
	
	<?php
	if ( $joueur['datesupp'] > 0 ) {
		?>
		<p><button class='myButton' onClick='restaurerjoueur(<?php echo $idjoueur ?>)'>Restaurer le joueur</button></p>
		<p><button class='myButton' onClick='supprimerjoueur(<?php echo $idjoueur ?>)'>Supprimer le joueur</button></p>
		
		<div id="section_confirme_suppression" class="section_invisible">
		<h3> Suppression de <?php echo $joueur['nomcomplet']; ?> ?</h3>
		<p>Attention: en cas de suppression définitive, le joueur n'apparait plus dans l'affichage des tableaux de résultats.</p>
		<p><button class="myButton oktogoon" id="valid2" onClick="clickConfirmeSuppressionJoueur()">Je confirme</button></p>
		<p><button class="myButton kotogoon" id="valid3" onClick="clickAnnulationSuppressionJoueur()">Oups ! J'annule</button></p>
		</div>
		
		<?php
	}
	else {
		?>
		<p><button class="myButton" onClick="modifierjoueur()">Enregistrer les modifications</button></p>
		<p><button class="myButton" onClick="effacerjoueur()">Effacer le joueur</button></p>
		<?php
	}
	?>
	<p id="msgerr1">&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto30()">Retour page gestion des joueurs</button></p>
	</div>
	
	<script>
	idjoueur = <?php echo $idjoueur; ?>
	</script>
</body>
</html>