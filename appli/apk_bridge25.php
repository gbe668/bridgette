<?php
require("configuration.php");
require("bridgette_bdd.php");

$userid = htmlspecialchars( $_GET['userid'] );
$joueur = getJoueur( $userid );

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
	<title>Apk Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/jquery-ui-1.13.2.min.js"></script>
	<script src="/js/bridge25.js"></script>
	<link rel="stylesheet" href="/css/bridgestylesheet.css" />
	<link rel="stylesheet" href="/css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="/images/favicon.ico">
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
</style>

<script>
var relpgm = "<?php echo $relpgm; ?>";
var parametres = <?php echo json_encode($parametres); ?>;
var userid = <?php echo $userid; ?>;

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto60() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
function passAndroid( parms ) {
	Android.processNext( JSON.stringify(parms) );
};
function showAndroidToast(toast) {
	Android.showToast(toast);
}

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
	<?php print "<p>Hello ".$joueur['nomcomplet']."</p>"; ?>
	<h2>Pré-inscription à un tournoi</h2>
	<div id="section_seltournoi">
	<p>La pré-inscription est possible pour les tournois des 4 prochaines semaines</p>
	<h3>Sélectionnez la date du tournoi:</h3>
	<div id="datepicker-container">
		<div id="datepicker-center">
			<div id="datetournoi"></div>
		</div>
	</div>
	</div>

	<div id="msgdatetournoi"></div>
	
	<div id="section_inscription" hidden>
	<table style="width:100%;max-width:350px;margin:auto;background-color:#E2EFDA"><tbody>
	<tr><td>
	<div id="section_tableau" hidden>
	<table style="width:100%;margin:auto;"><tbody><tr>
		<td style="width:90%"><h3>Tournoi du <span id="tabdujour">???</span></h3></td>
		<td><span class="cross" onclick="affiche_inscription()" style="border:.5pt solid">&#x274C;</span></td>
	</tr></tbody></table>
	<div id="tabinscrits">&nbsp;</div>
	<p id="msgtabinscrits">&nbsp;</p>
	</div>
	
	<div id="menu_noninscrit" hidden>
	<p>Vous n'êtes pas inscrit à ce tournoi !</p>
	<p>Contacter un joueur en recherche de partenaire en cliquant sur son som dans le tableau</p>
	<p><button class="myButton" onclick="sans_partenaire()">Inscription sans partenaire</button></p>
	<p><button class="myButton" onclick="avec_partenaire()">Inscription avec partenaire</button></p>
	</div>
	
	<div id="menu_inscrit" hidden>
	<p>Vous êtes inscrit à ce tournoi !</p>
	<p><button class="myButton" onclick="annule_inscription()">Désinscription</button></p>
	<p><button class="myButton" onclick="sans_partenaire()">Reherche nouveau partenaire</button></p>
	<p><button class="myButton" onclick="avec_partenaire()">Changement de partenaire</button></p>
	</div>
	
	<div id="section_clavier" hidden>
	<div id="clavier">clavier</div>
	</div>
	</td></tr></tbody></table>					   
	</div>
	
	<h2>Annuaire des joueurs actifs</h2>
	<p><button class="myButton" onclick="affiche_annuaire()">Affiche/masque annuaire</button></p>
	<div id="section_annuaire" hidden>
	<?php	print htmlAnnuaire();	?>
	<p><button class="myButton" onclick="masque_annuaire()">Masque annuaire</button></p>
	</div>

	<h2>Mes données personnelles</h2>
	<p>Tél <input type="text" id="phone" name="phone" value="<?php echo $joueur['phone']; ?>" size="12">&nbsp;<button class="mButton" onclick="oktel()">Ok</button></p>
	<p id="msgperso">&nbsp;</p>
	
	<h2>Mes performances passées</h2>
	<?php	print getMyClassement($userid);	?>

	<div class="top"><img src="/images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
	</div>
	
	<script>
	// valeurs par défaut
	$("#section_tableau").hide();
	$("#section_clavier").hide();
	$("#clavier").html( displayClavierSaisieJoueur() );
	</script>
 </body>
</html>