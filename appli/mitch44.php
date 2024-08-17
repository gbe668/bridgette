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
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function closetournoi() {
	$.get( "closetournoi.php?", {idtournoi:idtournoi}, function(strjson) {
		if ( strjson.res == st_closed ) {
			$("#msg").text("Tournoi clôturé.");
			var nextstring = "bridge44mails.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
			location.replace( nextstring );
		}
		else $("#msg").text("Tournoi non trouvé ! déjà supprimé ?");
	},"json");
};
function reload() {
	var nextstring = "mitch44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function goto45c() {		// correction d'une donne
	var nextstring = "mitch45c.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
function goto45m() {		// moyenne générale
	var nextstring = "mitch45m.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
function goto45s() {		// suppression d'une donne
	var nextstring = "mitch45s.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
function goto47() {		// Entrer les résultats manquants d'une table
	var nextstring = "bridge47.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
function goto48() {		// Entrer les résultats manquants d'une donne
	var nextstring = "bridge48.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
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
</script>

<body>
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = htmlspecialchars( $_GET['w'] );
	?>
	
	<script>
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	st_closed = parseInt( "<?php echo $st_closed; ?>" );
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	if ( isNaN( screenw ) ) reload();
	</script>
	
	<div style="text-align: center">
	
	<?php
	$t = readTournoi( $idtournoi );
	$datef = $t[ 'datef' ];
	set_etat( $idtournoi, $st_phase_fini );
	
	//print '<h3>Tournoi du ' . $datef . '</h3>';
	print '<h2>... résultats provisoires ...</h2>';
	$ndonnes = $t[ 'ndonnes' ];
	?>
	
	<script type="text/javascript"> 
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	</script>
	
	<?php
	setTournoi($idtournoi);
	displayTournoi( $idtournoi, $screenw );
	?>
	
	<p>En cas d'erreur de saisie, correction d'un résultat.</br>Les pénalités pour une paire sont entrées en modifiant le résultat de la donne concernée.</p>
	<p><button class="myButton" onClick="goto45c()">Corriger un résultat</button></p>
	
	<div id="afficheplus">
	<p><button class="myButton" onclick="cdeplus()"><em>Plus de commandes pour arbitrer,</br>entrer des résultats manquants ...</em></button></p>
	</div>
	<div id="affichemoins" class="section_invisible">
	<p><button onclick="cdemoins()" style="font-style: italic;">Moins d'affichage</button></p>
	<p>En cas d'autre problème découvert lors du déroulé d'une donne à une table,</br>application de la moyenne des résultats des autres tables.</p>
	<p><button class="myButton" onClick="goto45m()">Appliquer une moyenne générale</button></p>
	<p>En cas d'interversion de mains ou de cartes dans un étui sans possibilité de revenir en arrière,</br>le mieux est de supprimer tous les résultats pour la donne concernée.</p>
	<p><button class="myButton" onClick="goto45s()">Supprimer une donne</button></p>
	<p>En cas de résultats manquants (connexion internet sur smartphone, incapacité à saisir les résultats pour une table, ...),</br>les résultats peuvent être entrés en utilisant:
	<p><button class="myButton" onClick="goto47()">la feuille de marque de la table</button></p>
	<p><button class="myButton" onClick="goto48()">la feuille de suivi de l'étui</button></p>
	<p style='color:red'>Attention: la feuille de suivi de l'étui ne fonctionne pas pour les tournois avec guéridon et partage éventuel des donnes arrivant à la table 1. A suivre ...</p>
	</div>
	<p><button class="myStartButton" onClick="closetournoi()">Clôturer le tournoi</button></p>
	
	<p id='msg'>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
	
</body>
</html>