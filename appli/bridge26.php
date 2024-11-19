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

function getMyClassement($userid) {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO, $maxjoueurs;
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
		$sth = $dbh->query( "SELECT * FROM $tab_tournois WHERE tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH)) order by id asc;" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$minidt = $row['id'];
		$since  = $row['tournoi'];
		$datef = strdatet( $since );

		$sql = "SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf FROM (
			(SELECT idj1 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= $minidt AND idj1 = $userid) UNION 
			(SELECT idj2 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= $minidt AND idj2 = $userid) UNION 
			(SELECT idj3 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= $minidt AND idj3 = $userid) UNION
			(SELECT idj4 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= $minidt AND idj4 = $userid) 
			) T1 group by idj;";
		//print "<p>$sql</p>";
				
		$res = $dbh->query( $sql );
		if ( $row = $res->fetch(PDO::FETCH_ASSOC) ) {
			$score = sprintf( "%.1f", $row["perf"] );
			$nbfois = $row["nbfois"];
			
			$str = "<p>Performance moyenne sur les $nbm derniers mois (du $datef à ce jour):</p>";
			$str .= "<p>$score % sur $nbfois tournois joués</p>";
		}
		else {
			$str = "Vous n'avez pas joué ces derniers mois !";
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
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery-ui-1.13.2.min.js"></script>
	<script src="js/bridge25.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="stylesheet" href="css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var parametres = <?php echo json_encode($parametres); ?>;
var resultIdent = <?php echo json_encode($resultIdent); ?>;
console.log( resultIdent );

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
	/*
	if ( $parametres['checkuser'] > 0 ) {
		$joueur = getJoueur( $userid );
		print "<p><b>Hello ".$joueur['nomcomplet']."</b></p>";
	}
	*/
	if ( $resultIdent['status'] == $ID_CORRECT ) {
		$joueur = getJoueur( $userid );
		print "<p><b>Hello ".$joueur['nomcomplet']."</b></p>";
		print getMyClassement($userid);
		print '<p>Tél <input type="text" id="phone" name="phone" value="'.$joueur['phone'].'" size="12">&nbsp;<button class="mButton" onclick="oktel('.$userid.')">Ok</button></p>';

		print '<h2>Annuaire des joueurs actifs</h2>';
		print '<p><button class="myButton" onclick="cdeplus()">Affiche/masque annuaire</button></p>';
		print '<div id="afficheplus" class="section_invisible">';
		print htmlAnnuaire();
		print '<p><button class="myButton" onclick="cdemoins()">Masque annuaire</button></p>';
		print '</div>';
	}
	?>
	
	<h2>Recherche partenaire</h2>
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
	if ( isset($_GET['noreturn']) ) {
		// depuis le site internet du club
		print '<p><button class="mySmallButton" onclick="window.close();">Fermer cette fenêtre</button></p>';
	}
	else {
		// depuis le menu bridgette 
		print '<p><button class="mySmallButton" onclick="goto60()">Retour page précédente</button></p>';
		print '<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>';
		print '</div>';
		if ( $userid > 0 ) {
			print '<p><button class="mButton" onclick="loguserout()">Se déconnecter</button></p>';
		}
		else {
			print '<p><button class="mButton" onclick="loguserin()">Se connecter</button></p>';
		}
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