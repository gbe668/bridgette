<?php
require("configuration.php");
require("bridgette_bdd.php");
securityWebLogin();

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
	$sql = "SELECT count(*) FROM $tab_tournois WHERE tournoi >=(DATE_SUB(curdate(), INTERVAL ? MONTH)) order by id asc;";
	$sqlBound = [$nbm];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	//print "<p>$sql $nbl</p>";
	if ( $nbl > 0 ) {
		$sql = "SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf, MIN(tournoi) AS depuis FROM (
			(SELECT idj1 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = ? AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH))
			AND idj1 = ? ) 
			UNION 
			(SELECT idj2 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = ? AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH))
			AND idj2 = ?) 
			UNION 
			(SELECT idj3 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = ? AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH))
			AND idj3 = ?)
			UNION
			(SELECT idj4 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = ? AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH))
			AND idj4 = ?) 
			) T1 group by idj;";
		$sqlBound = [$st_closed, $nbm, $userid, $st_closed, $nbm, $userid, $st_closed, $nbm, $userid, $st_closed, $nbm, $userid];
		//print "<p>$sql</p>";
				
		$res = securityQuery($dbh, $sql, $sqlBound);
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

$tournoiEnCours = existeTournoiNonClos();
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="<?php echo $relpgm; ?>js/jquery-3.6.0.min.js"></script>
	<script src="<?php echo $relpgm; ?>js/bridge25.js"></script>
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/bridgestylesheet.css" />
	<link rel="stylesheet" href="<?php echo $relpgm; ?>css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="<?php echo $relpgm; ?>images/favicon.ico">
<?php securityCsrfHead(); ?>
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
/* ---- Widget calendrier n-semaines (remplace jQuery UI datepicker) ---- */
.datepicker{
  width:280px; background:#D8E5D0; border:1px solid #d0d5dd;
  border-radius:10px; box-shadow:0 4px 14px rgba(0,0,0,.06); padding:12px;
  margin:0 auto;
}
.dp-header{ text-align:center; margin-bottom:10px; }
.dp-title{ font-weight:600; font-size:16px; color:#1f2937; text-transform:capitalize; }
.datepicker table{ width:100%; border-collapse:collapse; }
.datepicker th{ font-size:12px; color:#000000; font-weight:500; padding:4px 0; }
.datepicker td{ text-align:center; padding:2px; }
.dp-day{
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  width:32px; height:32px; margin:auto; border-radius:8px;
  font-size:13px; cursor:pointer; color:#111827; position:relative;
}
.dp-day .dp-month-tag{ position:absolute; bottom:-2px; font-size:8px; color:#000000; }
.dp-day:hover{ background:#f3f4f6; }
.dp-day.today{ box-shadow: inset 0 0 0 1px #2563eb; font-weight:600; }

.dp-day.today-active{ background-color:#16a34a; color:#fff; font-weight:600; box-shadow:none; animation: dp-blink 1.2s ease-in-out infinite;
}
@keyframes dp-blink {
  0%, 100% { background-color:#16a34a; }
  50% { background-color:#86efac; }
}
.dp-day.selected{ background:#2563eb; color:#fff; font-weight:600; }
.dp-day.selected:hover{ background:#1d4ed8; }
.dp-day.disabled{ color:#9ca3af; background:#f3f4f6; cursor:not-allowed; }
.dp-day.dayopen{ background-color: lightblue; color:#006633; }
.dp-day.dayclose{ background-color:#FFC0CB; color:#006633; }
.dp-range-note{ font-size:11px; color:#9ca3af; margin-top:8px; text-align:center; }
</style>

<script>
var relpgm = "<?php echo $relpgm; ?>";
var parametres = <?php echo json_encode($parametres); ?>;	// jours d'ouverture
var calendrier = <?php echo json_encode($calendrier); ?>;
var userid = <?php echo $userid; ?>;
var userconnected = true;
var tournoiEnCours = ( parseInt(<?php echo $tournoiEnCours; ?>) > 0 ) ? true : false;

function loguserin() {
	var nextstring = "loguserin.php";
	bridgetteNavigate( nextstring );
};
function loguserout() {
	var nextstring = "loguserout.php";
	bridgetteNavigate( nextstring );
};

function gotoindex() {
	var nextstring = "bridgette.php";
	bridgetteNavigate( nextstring );
};
function goto60() {
	var nextstring = "bridge60.php";
	bridgetteNavigate( nextstring );
};
function goto24() {
	var nextstring = "bridge24.php";
	bridgetteNavigate( nextstring );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
//
// routines mise à jour téléphone
//
// mécanisme détectant une page expirée
var agepagemax = "<?php echo $agepagemax; ?>";
var loadedpage = "<?php echo $_SESSION['ttlast']; ?>";
document.addEventListener('visibilitychange', function (event) {
	if ( !document.hidden ) {
		var agepage = Math.floor(Date.now()/1000)-loadedpage;
		if ( agepage > agepagemax ) {
			const pop = document.getElementById('pop');
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
	<!-- <div id="msgdatetournoi"></div> -->
	
	<div id="section_inscription" class="framestyle" hidden>
	<table style="width:100%;max-width:350px;margin:auto;"><tbody>
	<tr><td>
	<div id="section_tableau" hidden>
	<table style="width:100%;margin:auto;"><tbody><tr>
		<td style="width:90%"><h3>Tournoi du <span id="tabdujour">???</span></h3></td>
		<td><span class="cross" onclick="close_inscription()" style="border:.5pt solid">&#x274C;</span></td>
	</tr></tbody></table>
	<div id="tabinscrits">&nbsp;</div>
	</div>
	<p id="msgtabinscrits">&nbsp;</p>
	
	<?php if ( $userid > 0 ) { ?>
		<div id="menu_noninscrit" hidden>
		<!-- <p>Vous n'êtes pas inscrit à ce tournoi !</p> -->
		<p>Vous pouvez contacter un joueur en recherche de partenaire en cliquant sur son nom</p>
		<p>ou vous inscrire <button class="myButton" onclick="sans_partenaire()">sans partenaire</button></p>
		<p>ou vous inscrire <button class="myButton" onclick="avec_partenaire()">avec un partenaire</button> qui peut être un joueur pré-inscrit en recherche de partenaire après avoir obtenu son accord.</p>
		</div>
		
		<div id="menu_inscrit" hidden>
		<!-- <p>Vous êtes inscrit à ce tournoi !</p> -->
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
	<p style="margin: 0";>&nbsp;</p>
	<div>
	
	<?php if ( $userid > 0 ) { ?>
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
		
		<p style="margin: 0";>&nbsp;</p>
		<p><button class="mButton" onclick="loguserout()">Se déconnecter</button></p>

	<?php } else { ?>
		<p>L'accès aux informations de contact des joueurs pré-inscrits est réservé aux joueurs identifiés.</p>
		<p>Connectez-vous pour vous identifier,</br>vous pourrez ainsi vous pré-inscrire, rechercher un partenaire, consulter l'annuaire du club ...</p>
		<p><button class="myButton" onclick="loguserin()">Se connecter</button></p>
	<?php } ?>
	
	</div>
	<!-- <p id='msgerr'>&nbsp;</p> -->

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