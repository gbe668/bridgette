<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib43.php");

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
	<script src="js/bridge41.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function settypetournoi_mitchell(type) {
	$.get( 'f41settypetournoi.php', {idtournoi:idtournoi, type:type}, function() {
		reload();
	});
};
function settypetournoi_howell(type) {
	if ( okeo == 0 ) {
		$.get( 'f41settypetournoi.php', {idtournoi:idtournoi, type:type}, function() {
		reload();
		})
	}
	else {
		$("#statuseo").text( "Howell: Tableau EO non vide !" );
	};
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function goto42() {
	$.get( "f41inittournoi.php", { idtournoi:idtournoi, okns:okns, okeo:okeo }, function( ret ) {
		console.log( "ret", ret );
		if ( parseInt(ret) > 0 ) {
			var nextstring = "bridge42.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
			console.log( ret, nextstring );
			location.replace( nextstring );
		}
		else $("#msgerr").html( "type tournoi non configuré" );
	},"text");
};
function reload() {
	var nextstring = "bridge41.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) )
		$("#afficheplus").removeClass( "section_invisible" );
	else
		$("#afficheplus").addClass( "section_invisible" );
}
function cdemoins() {
	$("#afficheplus").addClass( "section_invisible" );
}
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
	?>
	
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	console.log( screenw );
	if ( isNaN( screenw ) ) reload();
	</script>
	
	<p><button class="mySmallButton" onclick="cdeplus()">Affiche / masque types de tournoi</button></p>
	<div id="afficheplus" class="section_invisible">
	<div id="listetypes">
	<?php
	print htmlTableTypeTournois();
	?>
	</div>
	<p><button class="mySmallButton" onclick="cdemoins()">Masque types de tournoi</button></p>
	</div>
	
	<?php
	print '<div id="section_clavierns" class="section_invisible">';
	print '<h2><div id="lignetitre1">titre1</div></h2>';
	displayClavierSaisieJoueur();
	print '</div>';
	
	$t = readTournoi( $idtournoi );
	$idtype = $t['idtype'];
	
	print '<div id="section_tableaux">';
	//if ( $idtype < $min_type_mitchell ) {
	if ( $t['genre'] == $t_howell ) {
		// type Howell
		print "<p><button class='myButton' onclick='settypetournoi_mitchell($def_type_mitchell)'>Howell <img src='images/right.png' height='13' /> Mitchell</button></p>";
		print "<p><b>Howell:</b> compléter le tableau des paires,</br>minimum 4 paires pour 2 tables complètes,</br>$warningPMR</p>";
	
		// Un seul tableau
		print '<div id="section_tableauns"><h3>Tableau des paires</h3>';
		displayLignesNS( $idtournoi, "N°", $max_tables, 1 );
		print '</div>';
		print '<p id="statusns">&nbsp;</p>';
	}
	else {
		// type Mitchell
		print "<p><button class='myButton' onclick='settypetournoi_howell($def_type_howell)'>Mitchell <img src='images/right.png' height='13' /> Howell</button></p>";
		print "<p><b>Mitchell:</b> compléter les 2 tableaux,</br>minimum $min_tables_mitchell tables incomplètes</p>";
		if ( $screenw > $parametres['maxw'] ) {
			// les tableaux sont cote à cote
			print '<table style="width:90%; margin:auto;"><tbody><tr><td style="width:45%;">';
			print '<div id="section_tableauns"><h3>Tableau des paires Nord-Sud</h3>';
			displayLignesNS( $idtournoi, "NS", $max_tables, 1 );
			print '</div>';
			print '<p id="statusns">&nbsp;</p>';
			print '</td><td style="width:45%;">';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idtournoi, "EO", $max_tables, 1 );
			print '</div>';
			print '<p id="statuseo">&nbsp;</p>';
			print '</td></tr></tbody></table>';
		}
		else {
			// les tableaux sont l'un en dessous de l'autre
			print '<div id="section_tableauns"><h3>Tableau des paires Nord-Sud</h3>';
			displayLignesNS( $idtournoi, "NS", $max_tables, 1 );
			print '</div>';
			print '<p id="statusns">&nbsp;</p>';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idtournoi, "EO", $max_tables, 1 );
			print '<p id="statuseo">&nbsp;</p>';
			print '</div>';
		}
	}
	print '<p><em><span id="msg">Permutation de paires:<br/>cliquez sur une paire pour la sélectionner</span></em></p>';
	print '</div>';

	$nblignesNS = maxNumPaireNS( $idtournoi );	// valeur provenant de la table pairesNS
	$nblignesEO = maxNumPaireEO( $idtournoi );	// valeur provenant de la table pairesEO
	if ( $nblignesNS < $min_tables_mitchell ) $nblignesNS = $min_tables_mitchell;	// taille mini mitchell
	if ( $nblignesEO < $min_tables_mitchell ) $nblignesEO = $min_tables_mitchell;
	?>
	
	<p id="msgerr">&nbsp;</p>
	<div id="endofdef" class="section_invisible">
	<p><button class="myButton" onclick="goto42()">ok pour continuer !</button></p>
	</div>
	
	<script>
	nblignesNS = parseInt( "<?php echo $nblignesNS; ?>" );
	nblignesEO = parseInt( "<?php echo $nblignesEO; ?>" );
	max_tables = parseInt( "<?php echo $max_tables; ?>" );
	
	const min_paires_howell   = parseInt( "<?php echo $min_paires_howell; ?>" );
	const max_paires_howell   = parseInt( "<?php echo $max_paires_howell; ?>" );
	const min_tables_mitchell = parseInt( "<?php echo $min_tables_mitchell; ?>" );
	const min_type_mitchell   = parseInt( "<?php echo $min_type_mitchell; ?>" );
	
	okns = parseInt( "<?php echo testlignescompletesNS( $idtournoi ); ?>" );
	okeo = parseInt( "<?php echo testlignescompletesEO( $idtournoi ); ?>" );
	idtype = parseInt( "<?php echo $idtype; ?>" );
	masquelignesNS();
	masquelignesEO();
	checktableaux();
	</script>
	
	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="goto40()"/>
	</div>

	<?php
	if ( $config['site'] == 1 ) {
		// Site developpement = 1 / démonstration = 2 / déployé =3
		print '<p>Fonctions de test: génération automatique des paires</p>';
		print '<h3><a href="bridge49h.php?idtournoi=' . $idtournoi . '&paires=6">Test 6 paires Howell</a></h3>';
		print '<h3><a href="bridge49h.php?idtournoi=' . $idtournoi . '&paires=8">Test 8 paires Howell</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=10">Test 5 tables complètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=11">Test 6 tables incomplètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=12">Test 6 tables complètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=13">Test 7 tables incomplètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=14">Test 7 tables complètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=15">Test 8 tables incomplètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=16">Test 8 tables complètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=17">Test 9 tables incomplètes</a></h3>';
		print '<h3><a href="bridge49m.php?idtournoi=' . $idtournoi . '&paires=18">Test 9 tables complètes</a></h3>';
	}
	?>
	
</body>
</html>