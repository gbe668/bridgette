<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}
function getlastclosedtournoi() {
	global $tab_tournois, $st_closed, $parametres;
	global $nbl, $idtournois, $datetournois, $idtypes, $genres, $st_typetournoi;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_tournois where etat = '$st_closed' order by tournoi desc;" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$idlast = $row[ 'id' ];
	}
	else $idlast = 0;
	$dbh = null;
	return $idlast;
};
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
var idtournoi = 0;
var t_unknown = 0;
var t_mitchell = 1;
var t_howell = 2;
var genre = t_unknown;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function logout() {
	var nextstring = "logout.php";
	location.replace( nextstring );
};
function setpassword() {
	var nextstring = "setpassword.php";
	location.replace( nextstring );
};
function admin() {
	var nextstring = "logadmin.php";
	location.replace( nextstring );
};
function goto30() {	// gestion joueurs
	var nextstring = "bridge30.php";
	location.replace( nextstring );
};
function goto36() {	// paramétrage
	var nextstring = "bridge36.php";
	location.replace( nextstring );
};
function createtournoi() {
	console.log( "createtournoi" );
	$.get("createtournoi.php?", function(strjson) {
		idtournoi = strjson.idtournoi;
		if ( idtournoi > 0 ) {
			goto41();
		}
		else {
			$("#msg").text("Tournoi créé entretemps !!!");
			goto40();
		}
	},"json");
};
function startInscriptionTournoi() {
	console.log( "startInscriptionTournoi", idtournoi );
	$.get("startinscriptiontournoi.php", { idtournoi:idtournoi }, function(strjson) {
		goto41();
	},"json");
};
function effacetournoi() {
	console.log("effacetournoi");
	$.get( "erasetournoi.php?", {idtournoi:idtournoi}, function(json) {
		goto40();
	},"json");
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function goto41() {
	var nextstring = "bridge41.php?idtournoi=" + idtournoi + "&w=" + window.innerWidth;
	location.replace( nextstring );
};
function goto43() {		// tableau de bord
	var nextstring = "bridge43.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function goto44() {		// clôture du tournoi
	var nextstring;
	if ( genre == t_howell ) {
		nextstring = "howell44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
		location.replace( nextstring );
	}
	if ( genre == t_mitchell ) {
		nextstring = "mitch44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
		location.replace( nextstring );
	}
};
function goto44mails() {
	if ( idlastclosed > 0 ) {
		var nextstring = "bridge44mails.php?idtournoi=" + idlastclosed + "&w=" +  window.innerWidth;
		location.replace( nextstring );
	}
	else {
		$("#msg").text( "Il n'existe pas de tournoi précédent !" );
		setTimeout(function() { $("#msg").html( "&nbsp;" ); }, 1000);
	}
};
function cdeplus() {
	$("#afficheplus").addClass( "section_invisible" );
	$("#affichemoins").removeClass( "section_invisible" );
	var elmnt = document.getElementById("affichemoins");
	elmnt.scrollIntoView();
}
function cdemoins() {
	$("#afficheplus").removeClass( "section_invisible" );
	$("#affichemoins").addClass( "section_invisible" );
}
function clickSuppressionTournoi() {
	$("#section_suppression_tournoi").removeClass( "section_invisible" );
	var elmnt = document.getElementById("section_suppression_tournoi");
	elmnt.scrollIntoView();
}
function clickAnnulSuppTournoi() {
	$("#section_suppression_tournoi").addClass( "section_invisible" );
}
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	
	<?php
	$idtournoi = existeTournoiNonClos();	// 
	if ( $idtournoi > 0 ) {
		$t = readTournoi( $idtournoi );
		$etat  = $t['etat'];
		$datef = $t['datef'];
		$genre = $t['genre'];
		
		if ( $etat == $st_phase_init ) {
			$code = $t[ 'code' ];
			print "<h2>Tournoi du $datef </h2>";
			print '<h3>... en préparation</h3>';
			if ( $parametres['checkin'] > 0 ) print "<h3>Code d'accès $code</h3>";
			print "<p><button class='myBigButton' onclick='goto41()'>Définition des tables</button></p>";
		};
		
		if ( $etat == $st_phase_jeu ) {
			$code = $t[ 'code' ];
			$st_genre = $st_typetournoi[$t['genre']];
			print "<h2>Tournoi $st_genre du $datef </h2>";
			print '<h3>... démarré.</h3>';
			if ( $parametres['checkin'] > 0 ) print "<h3>Code d'accès $code</h3>";
			print '<p><button class="myBigButton" onclick="goto43()">Tableau de bord</button></p>';
		};
		
		if ( $etat == $st_phase_fini ) {
			$st_genre = $st_typetournoi[$t['genre']];
			print "<h2>Tournoi $st_genre du $datef </h2>";
			print '<h3>... terminé, en phase de clôture !</h3>';
			print '<p><button class="myBigButton" onclick="goto44()">Clôture du tournoi</button></p>';
			print '<h3>Attente clôture pour préparer un nouveau tournoi</h3>';
		};
	}
	else {
		$today = date('Y-m-d');
		$idtournoi = existeTournoiPreinscription($today);
		if ( $idtournoi > 0 ) {
			$t = readTournoi( $idtournoi );
			$datef = $t['datef'];
			print "<h2>Tournoi du $datef </h2>";
			print '<h3>... avec des joueurs pré-inscrits</h3>';
			print "<p><button class='myBigButton' onclick='startInscriptionTournoi()'>Définition des tables</button></p>";
		}
		else {
			print "<h2>Aujourd'hui " . strtoday() . "</h2>";
			print '<h3>Pas de tournoi en préparation,</br>en cours ou en clôture</h2>';
			print "<p><button class='myBigButton' onclick='createtournoi()'>Création nouveau tournoi</button></p>";
		}
		$genre = $t_unknown;
	};
	$idlastclosed = getlastclosedtournoi();
	?>
	
	<script>
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	genre = parseInt( "<?php echo $genre; ?>" );
	idlastclosed = parseInt( "<?php echo $idlastclosed; ?>" );
	</script>
	
	<p>&nbsp;</p>
	<p><button class="myBigButton" onclick="goto30()">Gestion des joueurs</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto36()">Paramétrage application</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>

	<p id='msg'>&nbsp;</p>
	<div id="afficheplus">
	<p><button onclick="cdeplus()" style="font-style: italic;">Plus d'affichage</button></p>
	</div>
	<div id="affichemoins" class="section_invisible">
	<p><button onclick="cdemoins()" style="font-style: italic;">Moins d'affichage</button></p>
	<p><button class="mButton" onclick="goto44mails()">Afficher les résultats<br>du tournoi précédent</button></p>
	<p><button class="mButton" onclick="logout()">Se déconnecter</button></p>
	<p><button class="mButton" onclick="setpassword()">Changer mon mot de passe</button></p>
	<p><button class="mButton" onclick="admin()">Administration</button></p>
	<?php
	if ( $idtournoi > 0 ) {
		print '<p><button class="mButton" onClick="clickSuppressionTournoi()">Supprimer le tournoi en cours</button></p>';
	};
	?>
	<div id="section_suppression_tournoi" class="section_invisible">
	<p>Attention, toutes les données du tournoi seront supprimées !</p>
	<p><button class="myButton oktogoon" id="valid2" onClick="effacetournoi()">Je confirme</button></p>
	<p><button class="myButton kotogoon" id="valid3" onClick="clickAnnulSuppTournoi()">Oups ! J'annule</button></p>
	</div>
	</div>
	
	</div>
</body>
</html>