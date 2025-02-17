<?php
require("configuration.php");
require("bridgette_bdd.php");
 
// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}
$opendays = $parametres['opendays'];
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
var parametres = <?php echo json_encode($parametres); ?>;
console.log( <?php echo $opendays['mardi']; ?> );

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function isNumeric(x) {
	return (parseFloat(x) == x);
}
function isEmail(email) {
  const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}
function verifSyntaxe() {
	var str;
	
	// mail copie des résultats
	str = $("#mailcopie").val();
	// mise en forme de l'email
	str = str.trim( str );
	str = str.toLowerCase();
	$("#mailcopie").val( str );
	if ( str != "" ) {
		// vérif syntaxe
		if ( isEmail( str ) ) {
			parametres.mailcopie = str;
		}
		else {
			$("#msgerr1").text( "Adresse mail incorrecte" );
			return false;
		}
	}
	else parametres.mailcopie = "";
	
	// jours d'ouverture du club
	parametres.opendays.lundi 		= day_1.checked ? "1" : "0";
	parametres.opendays.mardi 		= day_2.checked ? "1" : "0";
	parametres.opendays.mercredi 	= day_3.checked ? "1" : "0";
	parametres.opendays.jeudi 		= day_4.checked ? "1" : "0";
	parametres.opendays.vendredi 	= day_5.checked ? "1" : "0";
	parametres.opendays.samedi 		= day_6.checked ? "1" : "0";
	parametres.opendays.dimanche 	= day_7.checked ? "1" : "0";
	//console.log( parametres.opendays );

	str = $("#maxweeks").val();
	if ( isNumeric(str) ) {
		parametres.maxweeks = parseFloat( str );
		if ( parametres.maxweeks < 1 ) parametres.maxweeks = 1;
		if ( parametres.maxweeks > 13 ) parametres.maxweeks = 13;
		$("#maxweeks").val(parametres.maxweeks);
	}
	else {
		$("#msgerr1").text( "Période pré-inscription incorrecte" );
		return false;
	}
	
	// durées
	str = $("#dureedonne").val();
	if ( isNumeric(str) ) {
		parametres.dureedonne = parseFloat( str );
		if ( parametres.dureedonne < 5 ) parametres.dureedonne = 5;
		if ( parametres.dureedonne > 15 ) parametres.dureedonne = 15;
		$("#dureeinitiale").val(parametres.dureeinitiale);
	}
	else {
		$("#msgerr1").text( "Durée donne incorrecte" );
		return false;
	}
	
	str = $("#dureeinitiale").val();
	if ( isNumeric(str) ) {
		parametres.dureeinitiale =  parseFloat( str );
		if ( parametres.dureeinitiale < 0 ) parametres.dureeinitiale = 0;
		if ( parametres.dureeinitiale > 5 ) parametres.dureeinitiale = 5;
		$("#dureeinitiale").val(parametres.dureeinitiale);
	}
	else {
		$("#msgerr1").text( "Durée initiale incorrecte" );
		return false;
	}
	
	str = $("#dureediagrammes").val();
	if ( isNumeric(str) ) {
		parametres.dureediagrammes = parseFloat( str );
		if ( parametres.dureediagrammes < 0 ) parametres.dureediagrammes = 0;
		if ( parametres.dureediagrammes > 5 ) parametres.dureediagrammes = 5;
		$("#dureediagrammes").val(parametres.dureediagrammes);
	}
	else {
		$("#msgerr1").text( "Durée diagrammes incorrecte" );
		return false;
	}

	// autres
	str = $("#maxt").val();
	if ( isNumeric(str) ) {
		parametres.maxt = parseInt( str );
		if (parametres.maxt<1)		parametres.maxt=1;
		if (parametres.maxt>100)	parametres.maxt=100;
		$("#maxt").val(parametres.maxt);
	}
	else {
		$("#msgerr1").text( "Nombre de tournois non précisé" );
		return false;
	}
	
	/*
	str = $("#maxbj").val();
	if ( isNumeric(str) ) {
		maxbj = parseInt( str );
		if (maxbj<10) maxbj=10;
		if (maxbj>100) maxbj=100;
		$("#maxbj").val(maxbj);
	}
	else {
		$("#msgerr1").text( "Nombre de tournois non précisé" );
		return false;
	}
	
	str = $("#maxdel").val();
	if ( isNumeric(str) ) {
		maxdel = parseInt( str );
		if (maxdel<10) maxdel=10;
		if (maxdel>100) maxdel=100;
		$("#maxdel").val(maxdel);
	}
	else {
		$("#msgerr1").text( "Nombre de tournois non précisé" );
		return false;
	}
	*/
	str = $("#maxw").val();
	if ( isNumeric(str) ) {
		parametres.maxw = parseInt( str );
		if (parametres.maxw<300) parametres.maxw=300;
		$("#maxw").val(parametres.maxw);
	}
	else {
		$("#msgerr1").text( "Largeur minimale d'écran non précisée" );
		return false;
	}

	str = $("#maxw2").val();
	if ( isNumeric(str) ) {
		parametres.maxw2 = parseInt( str );
		if (parametres.maxw2<100) parametres.maxw=100;
		$("#maxw2").val(parametres.maxw2);
	}
	else {
		$("#msgerr1").text( "Largeur minimale non précisée" );
		return false;
	}

	// fonctions optionnnelles
	var rbs = document.querySelectorAll('input[name="checkin"]');
	parametres.checkin = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.checkin = rb.value;
			break;
		}
	}

	var rbs = document.querySelectorAll('input[name="checkuser"]');
	parametres.checkuser = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.checkuser = rb.value;
			break;
		}
	}

	rbs = document.querySelectorAll('input[name="affimp"]');
	parametres.affimp = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.affimp = rb.value;
			break;
		}
	}

	rbs = document.querySelectorAll('input[name="affprov"]');
	parametres.affprov = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.affprov = rb.value;
			break;
		}
	}

	rbs = document.querySelectorAll('input[name="back241"]');
	parametres.back241 = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.back241 = rb.value;
			break;
		}
	}

	// section avances
	rbs = document.querySelectorAll('input[name="avancem"]');
	parametres.avancem = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.avancem = rb.value;
			break;
		}
	}
	
	rbs = document.querySelectorAll('input[name="avanceh"]');
	parametres.avanceh = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.avanceh = rb.value;
			break;
		}
	}
	
	// section performances
	var rbs = document.querySelectorAll('input[name="affperf"]');
	parametres.affperf = "0";		// par défaut
	for (const rb of rbs) {
		if (rb.checked) {
			parametres.affperf = rb.value;
			break;
		}
	}
	str = $("#nbjperf").val();
	if ( isNumeric(str) ) {
		parametres.nbjperf = parseInt( str );
		if (parametres.nbjperf<3)	parametres.nbjperf=3;
		if (parametres.nbjperf>100) parametres.nbjperf=100;
		$("#nbjperf").val(parametres.nbjperf);
	}
	else {
		$("#msgerr1").text( "Taille du tableau non précisée" );
		return false;
	}
	str = $("#nbmperf").val();
	if ( isNumeric(str) ) {
		parametres.nbmperf = parseInt( str );
		if (parametres.nbmperf<1)	parametres.nbmperf=1;
		if (parametres.nbmperf>12)	parametres.nbmperf=12;
		$("#nbmperf").val(parametres.nbmperf);
	}
	else {
		$("#msgerr1").text( "Nombre de mois non précisé" );
		return false;
	}
	str = $("#minperf").val();
	if ( isNumeric(str) ) {
		parametres.minperf = parseInt( str );
		if (parametres.minperf<4)	parametres.minperf=4;
		if (parametres.minperf>100) parametres.minperf=100;
		$("#minperf").val(parametres.minperf);
	}
	else {
		$("#msgerr1").text( "Nombre de tournois non précisé" );
		return false;
	}
	
	// fonctions de test
	str = $("#param1").val();
	if ( isNumeric(str) ) {
		parametres.param1 = parseInt( str );
	}
	else {
		$("#msgerr1").text( "Erreur remplissage auto" );
		return false;
	}
	str = $("#param2").val();
	if ( isNumeric(str) ) {
		parametres.param2 = parseInt( str );
	}
	else {
		//$("#msgerr1").text( "Erreur param2" );
		//return false;
	}
	return true;
}
function saveparams() {
	if ( verifSyntaxe() ) {
		// syntaxe correcte
		jsonparms = JSON.stringify( parametres );
		console.log(jsonparms);
		$("#msgerr1").html( "Enregistrement en cours ...");
		$.get("saveparams.php?", {jsonparms:jsonparms}, function(strjson) {
			$("#msgerr1").html(strjson);
		},"text")
		//.done( function() { $("#msgerr1").html( "Enregistrement terminé." ); } )
		.fail( function( jqxhr, settings, ex ) { $("#msgerr1").html('Erreur: '+ ex ); } );
	}
};
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	
	<h2>Paramétrage application</h2>
	<table border="0" style="margin:auto;"><tbody>
	<tr><td colspan="2" style="background-color:lightgreen;">Mail en copie des résultats<td></tr>
	<tr><td colspan="2"><input type="text" id="mailcopie" name="mailcopie" value="<?php echo $parametres['mailcopie'] ?>"  placeholder="adresse mail" size="40"></td></tr>
	
	<tr><td colspan="2" style="background-color:lightgreen;">Ouverture du club<td></tr>
	<tr><td colspan="2" class="defparm">
	Lundi<input type="checkbox" id="day_1" <?php echo ($opendays['lundi']=="1")?'checked':'' ?> >&nbsp;
	Mardi<input type="checkbox" id="day_2" <?php echo ($opendays['mardi']=="1")?'checked':'' ?> >&nbsp;
	Mercredi<input type="checkbox" id="day_3" <?php echo ($opendays['mercredi']=="1")?'checked':'' ?> >&nbsp;
	Jeudi<input type="checkbox" id="day_4" <?php echo ($opendays['jeudi']=="1")?'checked':'' ?> >&nbsp;
	Vendredi<input type="checkbox" id="day_5" <?php echo ($opendays['vendredi']=="1")?'checked':'' ?> >&nbsp;
	Samedi<input type="checkbox" id="day_6" <?php echo ($opendays['samedi']=="1")?'checked':'' ?> >&nbsp;
	Dimanche<input type="checkbox" id="day_7" <?php echo ($opendays['dimanche']=="1")?'checked':'' ?> >&nbsp;</td></tr>
	
	<tr><td class="defparm">Période pour pré-inscription tournoi:</br><em>entre 1 et 13 semaines</em></td><td class="valparm"><input type="text" id="maxweeks" name="maxweeks" value="<?php echo $parametres['maxweeks'] ?>" size="2"> sem</td></tr>
	
	<tr><td colspan="2" style="background-color:lightgreen;">Durées de jeu<td></tr>
	<tr><td class="defparm">Durée pour jouer une donne:</br><em>entre 5 et 15 minutes</br>7.5 minutes habituellement</em></td>
	<td class="valparm" style="width:75px"><input type="text" id="dureedonne" name="dureedonne" value="<?php echo $parametres['dureedonne'] ?>" size="2"> mn</td></tr>
	
	<tr><td class="defparm">Durée initiale pour</br>la mise en place des joueurs:</br><em>entre 0 et 5 minutes</em></td><td class="valparm"><input type="text" id="dureeinitiale" name="dureeinitiale" value="<?php echo $parametres['dureeinitiale'] ?>" size="2"> mn</td></tr>
	
	<tr><td class="defparm">Durée supplémentaire pour</br>l'entrée des diagrammes:</br><em>entre 0 et 5 minutes</em></td><td class="valparm"><input type="text" id="dureediagrammes" name="dureediagrammes" value="<?php echo $parametres['dureediagrammes'] ?>" size="2"> mn</td></tr>

	<tr><td colspan="2" style="background-color:lightgreen;">Affichage<td></tr>
	<tr><td class="defparm">Nombre maximum de tournois</br>affichés dans la liste des</br>résultats précédents:</br><em>entre 1 et 100</em></td><td class="valparm"><input type="text" id="maxt" name="maxt" value="<?php echo $parametres['maxt'] ?>" size="2"></td></tr>
	
	<!--
	<tr><td class="defparm">Taille du tableau des joueurs actifs dans la liste des joueurs du club:</br><em>entre 10 et 100</em></td><td class="valparm"><input type="text" id="maxbj" name="maxbj" value="<?php echo $parametres['maxbj'] ?>" size="2"></td></tr>
	
	<tr><td class="defparm">Nombre de joueurs affichés</br>dans la liste des joueurs effacés:</br><em>entre 10 et 100</em></td><td class="valparm"><input type="text" id="maxdel" name="maxdel" value="<?php echo $parametres['maxdel'] ?>" size="2"></td></tr>
	-->
	
	<tr><td class="defparm">Largeur écran nécessaire pour</br>passer en affichage 2 colonnes:</br><em>défaut 700 pixels, mini 300</em></td><td class="valparm"><input type="text" id="maxw" name="maxw" value="<?php echo $parametres['maxw'] ?>" size="2"> px</td></tr>
	
	<tr><td class="defparm">Largeur écran nécessaire pour</br>passer en tableau réduit:</br><em>défaut 200 pixels, mini 100</em></td><td class="valparm"><input type="text" id="maxw2" name="maxw2" value="<?php echo $parametres['maxw2'] ?>" size="2"> px</td></tr>

	<tr><td colspan="2" style="background-color:lightgreen;">Fonctions optionnelles<td></tr>
	<tr><td class="defparm">Demande du code pour</br>rejoindre le tournoi en cours</br>pour les joueurs:</td>
	<td class="valparm">Oui<input type="radio" id="codey" name="checkin" <?php echo ($parametres['checkin']==1)?'checked':'' ?> value="1"></br>Non<input type="radio" id="coden" name="checkin" <?php echo ($parametres['checkin']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td class="defparm">Identification des joueurs pour rejoindre le tournoi en cours:</br>
	Oui, seulement Membres du club</br>
	Oui, seulement Participants</td>
	<td class="valparm"></br>
	Non<input type="radio" id="checkuser0" name="checkuser" <?php echo ($parametres['checkuser']==0)?'checked':'' ?> value="0"></br>
	M<input type="radio" id="checkuser1" name="checkuser" <?php echo ($parametres['checkuser']==1)?'checked':'' ?> value="1"></br>
	P<input type="radio" id="checkuser2" name="checkuser" <?php echo ($parametres['checkuser']==2)?'checked':'' ?> value="2">
	</td></tr>

	<tr><td class="defparm">Affichage résultats en IMP:</br><em>seulement pour les tournois de 4 ou 5 paires</em></td><td class="valparm">Oui<input type="radio" id="impy" name="affimp" <?php echo ($parametres['affimp']==1)?'checked':'' ?> value="1"></br>Non<input type="radio" id="impn" name="affimp" <?php echo ($parametres['affimp']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td class="defparm">Affichage résultats provisoires</br>avant la fin du tournoi:&nbsp;</td><td class="valparm">Oui<input type="radio" id="affprovy" name="affprov" <?php echo ($parametres['affprov']==1)?'checked':'' ?> value="1"></br>Non<input type="radio" id="affprovn" name="affprov" <?php echo ($parametres['affprov']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td class="defparm">Retour possible à la définition des paires après le démarrage du tournoi:&nbsp;</td><td class="valparm">Oui<input type="radio" id="back241yes" name="back241" <?php echo ($parametres['back241']==1)?'checked':'' ?> value="1"></br>Non<input type="radio" id="back241no" name="back241" <?php echo ($parametres['back241']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td colspan="2" style="background-color:lightgreen;">Changement de position synchrone<td></tr>
	<tr><td class="defparm">Mitchell:&nbsp;attente des autres tables</br>pour changer de position</td>
	<td class="valparm">Oui<input type="radio" id="avancemyes" name="avancem" <?php echo ($parametres['avancem']==1)?'checked':'' ?> value="1">
	</br>Non<input type="radio" id="avancemno" name="avancem" <?php echo ($parametres['avancem']==0)?'checked':'' ?> value="0"></td></tr>
	<tr><td class="defparm">Howell:&nbsp;attente des autres tables</br>pour changer de position</td>
	<td class="valparm">Oui<input type="radio" id="avancehyes" name="avanceh" <?php echo ($parametres['avanceh']==1)?'checked':'' ?> value="1">
	</br>Non<input type="radio" id="avancehno" name="avanceh" <?php echo ($parametres['avanceh']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td colspan="2" style="background-color:lightgreen;">Performances des joueurs<td></tr>
	<tr><td class="defparm">Affichage des performances:&nbsp;</td><td class="valparm">Oui<input type="radio" id="affperfy" name="affperf" <?php echo ($parametres['affperf']==1)?'checked':'' ?> value="1"></br>Non<input type="radio" id="affperfn" name="affperf" <?php echo ($parametres['affperf']==0)?'checked':'' ?> value="0"></td></tr>
	
	<tr><td class="defparm">Taille tableau des performances:</br><em>entre 3 et 100 joueurs</em></td><td class="valparm"><input type="text" id="nbjperf" name="nbjperf" value="<?php echo $parametres['nbjperf'] ?>" size="2"></td></tr>
	<tr><td class="defparm">Période considérée pour l'analyse:</br><em>entre 1 et 12 dernier mois</em></td><td class="valparm"><input type="text" id="nbmperf" name="nbmperf" value="<?php echo $parametres['nbmperf'] ?>" size="2"></td></tr>
	<tr><td class="defparm">Nombre minimum de tournois joués sur la période considérée:</br><em>entre 4 et 100 tournois</em></td><td class="valparm"><input type="text" id="minperf" name="minperf" value="<?php echo $parametres['minperf'] ?>" size="2"></td></tr>
	
	<tr><td colspan="2" style="background-color:lightgreen;">Fonctions de test<td></tr>
	<tr><td class="defparm">0 = normal<br/>1 = remplissage auto</td><td class="valparm"><input type="text" id="param1" name="param1" value="<?php echo $parametres['param1'] ?>" size="2"></td></tr>
	</tbody></table>
	
	<p><button class="myButton" id="valid1" onClick="saveparams()">Enregistrer les paramètres</button></br><span id="msgerr1">&nbsp;</span></p>
	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="goto40()"/>
	</div>
</body>
</html>