<?php
require("configuration.php");
require("bridgette_bdd.php");

if ( file_exists( $file_calendar ) ) {
	$calendrier = json_decode( file_get_contents( $file_calendar ), true );
}
else $calendrier = array();

$resultIdent = getIdent();
if ( $resultIdent['status'] == $ID_INCORRECT ) {
	header("Location: loguserin.php");
}
else $userid = $resultIdent['userid'];

function getMyClassement($userid) {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO, $maxjoueurs, $st_closed;
	global $parametres;
	$nbm = $parametres['nbmperf'];	// $nbm nombre de mois précédent la date du jour
	$min = $parametres['minperf'];	// $min nombre minimum de tournois joués
	$nbj = $parametres['nbjperf'];	// taille du tableau
	$dbh = connectBDD();
	
	// recherche du 1er tournoi avec une date >= date du jour - $nbm mois
	$sql = "SELECT count(*) FROM $tab_tournois WHERE tournoi >=(DATE_SUB(curdate(), INTERVAL $nbm MONTH)) order by id asc;";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	//print "<p>$sql $nbl</p>";
	if ( $nbl > 0 ) {
		$sql = "SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf, MIN(tournoi) AS depuis FROM (
			(SELECT idj1 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = $st_closed AND tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH))
			AND idj1 = $userid ) 
			UNION 
			(SELECT idj2 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = $st_closed AND tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH))
			AND idj2 = $userid) 
			UNION 
			(SELECT idj3 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = $st_closed AND tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH))
			AND idj3 = $userid)
			UNION
			(SELECT idj4 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = $st_closed AND tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH))
			AND idj4 = $userid) 
			) T1 group by idj;";
		//print "<p>$sql</p>";
				
		$res = $dbh->query( $sql );
		if ( $row = $res->fetch(PDO::FETCH_ASSOC) ) {
			$score = sprintf( "%.1f", $row["perf"] );
			$nbfois = $row["nbfois"];
			$depuis = $row["depuis"];
			$datef = strdatet( $depuis );
			
			$str = "<p>Performance moyenne sur les $nbm derniers mois (du $datef à ce jour):</p>";
			$str .= "<p><b>$score %</b> sur $nbfois tournois joués</p>";
		}
		else {
			$str = "Vous n'avez pas joué ces $nbm derniers mois !";
		}
	}
	else {
		$str = "Pas de tournoi enregistré ces derniers mois !";
	}
	$dbh = null;
	return $str;
};
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="<?php echo $relpgm; ?>js/jquery-3.6.0.min.js"></script>
	<script src="<?php echo $relpgm; ?>js/jquery-ui-1.13.2.min.js"></script>
	<script src="<?php echo $relpgm; ?>js/bridge25.js"></script>
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/bridgestylesheet.css" />
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="<?php echo $relpgm; ?>images/favicon.ico">
</head>

<style>
#datepicker-container{
  text-align:center;
}
#datepicker-center{
  display:inline-block;
  margin:0 auto;
}
.cross:hover {
	cursor: pointer;
}
td.dayclose a {
    background: none !important;
	background-color:#FFC0CB !important;
    color: #006633;
}
td.dayopen a {
    background: none !important;
	background-color: lightgreen !important;
    color: #006633;
}
</style>

<script>
var relpgm = "<?php echo $relpgm; ?>";
var parametres = <?php echo json_encode($parametres); ?>;	// jours d'ouverture
var calendrier = <?php echo json_encode($calendrier); ?>;
var userid = <?php echo $userid; ?>;
var userconnected = true;

function loguserin() {
	var nextstring = "loguserin.php";
	location.replace( nextstring );
};
function loguserout() {
	var nextstring = "loguserout.php";
	location.replace( nextstring );
};

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto60() {
	var nextstring = "bridge60.php";
	location.replace( nextstring );
};
function goto24() {
	var nextstring = "bridge24.php";
	location.replace( nextstring );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
//
// routines mise à jour téléphone
//
function isNumeric(str) {
  return /^(\s*[0-9-]+\s*)+$/.test(str);
}
function isEmail(email) {
  const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}
function oktel() {
	let phone = $("#phone").val();
	phone = phone.replaceAll( '-', ' ');
	phone = phone.trim();
	$("#phone").val( phone );
	if ( (phone.length > 0)&&(!isNumeric( phone)) ) {
		$("#telperso").text( "Téléphone: caractères non numériques" );
	}
	else {
		$.get( relpgm+"f25updatefield.php", { idjoueur:userid, command:'phone', phone:phone },
		function(strjson) {
			$("#telperso").text( strjson.msg );
		}, "json");
	}
	setTimeout(function() { $("#telperso").text( "" ); }, 2000);
}
function okmail() {
	// mise en forme de l'email
	email = $("#email1").val();
	email = email.trim( email );
	email = email.toLowerCase();
	$("#email1").val( email );
	if ( !isEmail( email) ) {
		$("#mailperso").text( "email incorrect" );
	}
	else {
		console.log("okmail ok", userid, email);
		$.get( relpgm+"f25updatefield.php", { idjoueur:userid, command:'email', email:email },
		function(strjson) {
			$("#mailperso").text( strjson.msg );
		}, "json");
	}
	setTimeout(function() { $("#mailperso").text( "" ); }, 2000);
}

//
// paramètres de sélection date tournoi spécifique joueur: datepicker, ...
//
var strmaxdate = '+'+ parametres.maxweeks +'w';	// en semaines
$(document).ready(function() {		// sélection date tournoi
	datetournoi = $( "#datetournoi" ).datepicker({	// initialisation
		//var dateFormat = "mm/dd/yy",
		//defaultDate: +1,
		//numberOfMonths: 1
	})
	.datepicker('setDate', 'today')
	.datepicker( "option", "maxDate", strmaxdate )
	.datepicker( "option", "beforeShowDay", function (date){
		let datjour = date.getFullYear() + '-' + String((date.getMonth() + 1)).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
		if ( datjour in calendrier ) {
			let special = calendrier[datjour];
			if (special.etat > 0)
				return [ true, "dayopen", special.obs ];
			else
				return [ true, "dayclose", special.obs ];
		}

		let dd = date.getDay();		// de 0 (dimanche) à 6 (samedi)
		let jour = listeJours[dd];
		if ( parametres.opendays[jour] == '1' )
			return [ true, "", "Le club est ouvert" ]
		else
			return [ false, "", "Pas de tournoi ce jour" ]
	})
	.on( "change", function() {
		$("#errdatetournoi").html( "&nbsp;" );
		let seldate = $("#datetournoi").val();
		console.log( "change", seldate );
		// test club ouvert
		if ( seldate in calendrier ) {
			let special = calendrier[seldate];
			$("#errdatetournoi").html( "<p style='color:red'><b>"+special.obs+"</b></p>" );
			if (special.etat == 0) {
				$("#section_inscription").hide();
				return;
			}
		}
		selectTournoi( seldate );
	});
});

// mécanisme détectant une page expirée
var agepagemax = "<?php echo $agepagemax; ?>";
var loadedpage = "<?php echo $_SESSION['ttlast']; ?>";
document.addEventListener('visibilitychange', function (event) {
	if ( !document.hidden ) {
		var agepage = Math.floor(Date.now()/1000)-loadedpage;
		if ( agepage > agepagemax ) {
			pop.style.display = "inline-block";
			setTimeout(function() { gotoindex(); }, 2000);
		}
    }
});
</script>
 
 <body>
	<center>
	<div id="pop" class="popup">
	<p>&nbsp;</p>
	<h2>Page périmée</h2>
	<button onclick="gotoindex();">Retour page précédente</button>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align:center; max-width:350px; margin:auto;" id='topwindow'>
	<?php
	if ( $userid > 0 ) {
		$joueur = getJoueur( $userid );
		print "<p>Hello ".$joueur['nomcomplet']."</p>";
	}
	?>
	<h2>Pré-inscription à un tournoi</h2>
	<div id="section_seltournoi">
	<p>La pré-inscription est possible pour les tournois des <script>document.write(parametres.maxweeks)</script> prochaines semaines</p>
	<h3>Sélectionnez la date du tournoi:</h3>
	<div id="datepicker-container">
		<div id="datepicker-center">
			<div id="datetournoi"></div>
		</div>
	</div>
	</div>
	<div id="errdatetournoi"></div>
	<div id="msgdatetournoi"></div>
	
	<div id="section_inscription" class="framestyle" hidden>
	<table style="width:100%;max-width:350px;margin:auto;"><tbody>
	<tr><td>
	<div id="section_tableau" hidden>
	<table style="width:100%;margin:auto;"><tbody><tr>
		<td style="width:90%"><h3>Tournoi du <span id="tabdujour">???</span></h3></td>
		<td><span class="cross" onclick="close_inscription()" style="border:.5pt solid">&#x274C;</span></td>
	</tr></tbody></table>
	<div id="tabinscrits">&nbsp;</div>
	<p id="msgtabinscrits">&nbsp;</p>
	</div>
	
	<?php
	if ( $userid > 0 ) { ?>
		<div id="menu_noninscrit" hidden>
		<p>Vous n'êtes pas inscrit à ce tournoi !</p>
		<p>Vous pouvez contacter un joueur en recherche de partenaire en cliquant sur son nom</p>
		<p>ou vous inscrire <button class="myButton" onclick="sans_partenaire()">sans partenaire</button></p>
		<p>ou vous inscrire <button class="myButton" onclick="avec_partenaire()">avec un partenaire</button></p>
		</div>
		
		<div id="menu_inscrit" hidden>
		<p>Vous êtes inscrit à ce tournoi !</p>
		<p>Vous pouvez vous <button class="myButton" onclick="annule_inscription()">désinscrire</button></p>
		<p>ou <button class="myButton" onclick="sans_partenaire()">chercher</button> un partenaire</p>
		<p>ou <button class="myButton" onclick="avec_partenaire()">changer</button> de partenaire</p>
		</div>
		
		<div id="section_clavier" hidden>
		<div id="clavier">clavier</div>
		</div>
	
	<?php } ?>
	</td></tr></tbody></table>
	</div>
	<p>&nbsp;</p>
	<div>
	<?php
	if ( $userid > 0 ) { ?>
		<h2>Annuaire des joueurs actifs</h2>
		<div id="section_annuaire" hidden>
		<p><button class="myButton" onclick="$('#section_annuaire').toggle();">Masque annuaire</button></p>
		<?php	print htmlAnnuaire();	?>
		</div>
		<p><button class="myButton" onclick="$('#section_annuaire').toggle(); elmnt = document.getElementById('section_annuaire'); elmnt.scrollIntoView();">Affiche/Masque annuaire</button></p>
		
		<h2>Mes données personnelles</h2>
		<div id="section_perso" class="framestyle" hidden>
		<h3>Mes coordonnées</h3>
		<p>Au sein du club, vous avez le n°<b><?php echo $joueur['numero'] ?></b></p>
		<!--
		<p>Monsieur <input type="radio" id="male" name="gender" <?php echo ($joueur['genre']=='Mr')?'checked':'' ?> value="Mr"> Madame <input type="radio" id="female" name="gender" <?php echo ($joueur['genre']=='Me')?'checked':'' ?>  value="Me"></p>
		-->
		<p>Prénom:<input type="text" id="fname" value="<?php echo $joueur['prenom'] ?>" size="20"></p>
		<p>Nom:<input type="text" id="lname" value="<?php echo $joueur['nom'] ?>" size="20"></p>
		
		<p>Téléphone:<input type="text" id="phone" name="phone" value="<?php echo $joueur['phone'] ?>" size="12"> <button class="mButton" id="valid1" onClick="oktel()"><img src="images/save.png" style="width:16px;" /></button></p>
		<p id="telperso"></p>
		
		<p><input type="text" id="email1" value="<?php echo $joueur['email']; ?>" size="30"> <button class="mButton" id="valid2" onClick="okmail()"><img src="images/save.png" style="width:16px;" /></button></br>Si vous modifiez votre adresse mail,</br>vous devrez vous reconnecter</p>
		<p id="mailperso"></p>
		
		<h3>Mes performances passées</h3>
		<?php	print getMyClassement($userid);	?>
		
		</div>
		<p><button class="myButton" onclick="$('#section_perso').toggle(); elmnt = document.getElementById('section_perso'); elmnt.scrollIntoView();">Affiche/Masque mes données</button></p>
		
		<p>&nbsp;</p>
		<p><button class="mButton" onclick="loguserout()">Se déconnecter</button></p>

	<?php } else { ?>
		
		<p>L'accès aux informations de contact des joueurs pré-inscrits est réservé aux joueurs identifiés.</p>
		<p>Connectez-vous pour vous identifier,</br>vous pourrez ainsi vous pré-inscrire, rechercher un partenaire, consulter l'annuaire du club ...</p>
		<p><button class="myButton" onclick="loguserin()">Se connecter</button></p>
	<?php } ?>
	</div>
	<p id='msgerr'>&nbsp;</p>

	<?php
	if ( isset($_GET['noreturn']) ) {
		// depuis le site internet du club
		print '<p><button class="mySmallButton" onclick="window.close();">Fermer cette fenêtre</button></p>';
	}
	else {
		// depuis le menu bridgette 
		print '<p><button class="mySmallButton" onclick="goto60()">Retour page précédente</button></p>';
		print '<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/></div>';
	}
	?>

	<div class="top"><img src="images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
	</div>
	
	<script>
	// valeurs par défaut
	$("#section_tableau").hide();
	$("#section_clavier").hide();
	$("#clavier").html( displayClavierSaisieJoueur() );
	</script>
 </body>
</html>