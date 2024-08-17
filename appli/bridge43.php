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
	<script src="js/bridge43.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function stoptournoi() {
	$.get( "stoptournoi.php?", {idtournoi:idtournoi}, function(strjson) {
		goto44();
	},"json");
};
function goto41() {	// Oups ! Il manque une paire
	$.get("setphaseinit.php?", { idtournoi:idtournoi }, function(strjson) {
		if ( strjson.res == st_phase_init ) {
			var nextstring = "bridge41.php?idtournoi=" + idtournoi+ "&w=" +  window.innerWidth;
			location.replace( nextstring );
		}
	},"json");
};
function goto44() {
	var nextstring = (genre == t_mitchell) ? "mitch44.php" : "howell44.php";
	nextstring += "?idtournoi=" + idtournoi+ "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function goto43c() {		// correction d'une donne
	var etui = $("#etui").text();
	var nextstring = "bridge43c.php?idtournoi=" + idtournoi + "&etui=" + etui;
	console.log( nextstring );
	location.replace( nextstring );
};
function goto46() {
	var etui = $("#etui").text();
	var nextstring = "bridge46.php?idtournoi=" + idtournoi + "&etui=" + etui;
	location.replace( nextstring );
};
function goto61() {
	var nextstring = 'mitch61.php?idtournoi=' + idtournoi;
	location.replace( nextstring );
};
function clickForcageArretTournoi() {
	$("#section_arret_tournoi").removeClass( "section_invisible" );
}
function clickAnnulationArretTournoi() {
	$("#section_arret_tournoi").addClass( "section_invisible" );
}
function toggleAffichagePaires() {
	if ( $("#section_tableaux").hasClass( 'section_invisible' ) )
		$('#section_tableaux').removeClass( 'section_invisible' );
	else
		$('#section_tableaux').addClass( 'section_invisible' );
}
function reload() {
	var nextstring = "bridge43.php?idtournoi=" + idtournoi + "&etui=" + etui + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function refreshConnexions() {
	// mise à jour connexions
	lstConnexions();
	// test tournoi terminé
	if ( tstfintournoi() ) {
		$('#section_encours').addClass( 'section_invisible' );
		$("#section_affichage").removeClass( "section_invisible" );
		$("#section_forcage").addClass( "section_invisible" );
		//goto44();
	}
	else {
		setTimeout(function() { refreshConnexions(); }, 3000);
	}
}
</script>

<body>
	<div style="text-align:center; margin:auto;">
	
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$etui = isset( $_GET['etui'] ) ? htmlspecialchars( $_GET['etui'] ) : '1';
	$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
	
	$t = readTournoi( $idtournoi );
	$datef = $t[ 'datef' ];
	$code = $t[ 'code' ];

	$pns = $t[ 'pairesNS' ];
	$peo = $t[ 'pairesEO' ];
	$idtype = $t[ 'idtype' ];
	
	$ntables = $t[ 'ntables' ];
	$ndonnes = $t[ 'ndonnes' ];
	$npositions = $t[ 'npositions' ];
	$njouees =	$t[ 'njouees' ];
	$paquet =	$t[ 'paquet' ];
	$relais =	$t[ 'relais' ];
	$gueridon = $t[ 'gueridon' ];
	$genre = $t[ 'genre' ];

	$desc = getdescriptiontournoi($idtype);
	
	//print "<h2>Tournoi $st_typetournoi[$genre] du $datef</h2>";
	print "<h2>Tournoi du $datef</h2>";
	print "<p style='color:red;font-size: 1.2em;'>$desc, $paquet étuis par table</p>";
	if ( $parametres['checkin'] > 0 ) print "<h3>Code d'accès $code</h3>";
	
	$startseq = $t[ 'startseq' ];				// date fin 1ère séquence en secondes
	$dureefirstposition = ( $paquet * ($parametres['dureedonne'] + $parametres['dureediagrammes']) + $parametres['dureeinitiale'] ) * 60;	// en secondes
	$dureenextposition = $paquet * $parametres['dureedonne'] * 60;	// durée exprimée en secondes
	$fintournoi = $startseq + $dureefirstposition + $dureenextposition * ($npositions-1);
	$strfintournoi = date( "H:i:s", $fintournoi);
	?>
	
	<div id="section_encours">
	<div id="section_tableaux" class="section_invisible">
	<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>

	<?php
	displayPaires($idtournoi, $genre, $pns, $peo, $screenw);
	?>
	</div>
	<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>

	<?php
	$donnesjouees = getdonnesjouees();
	if ( ($donnesjouees == 0)&&($parametres['back241']==1) ) {
		print "<div id='back241' >";
		print "<p>En cas d'arrivée tardive d'un joueur et si <b>personne n’a commencé à jouer</b>, il est possible de revenir à la page de définition des paires pour prendre en compte le joueur en retard. <button class='myButton' onclick='goto41()'>Revenir définition des paires</button></p>";
		print "</div>";
	}	
	?>
	
	<p>Si le tournoi s'éternise, vous pouvez</br>diminuer le nombre de positions</p>
	<p>Nombre positions:&nbsp;<span class='xNum2' id="signemoins"><img src="images/signe-moins.png" height="20"/></span>
	<span id='npositions' class="xDigit" style="padding-left:20px; padding-right:20px;"><?php echo $npositions ?></span>
	<span class='xNum2' id="signeplus"><img src="images/signe-plus.png" height="20"/></span>
	</br>Donnes jouées: <span id="njouees"><?php echo $njouees ?></span> sur <span id="ndonnes"><?php echo $ndonnes ?></p>
	<p><b>fin tournoi: <span id="fintournoi"><?php echo $strfintournoi ?></span></b></p>
	<p id="msgerr">&nbsp;</p>
	</div>
	
	<?php
	displayCnxTables();
	$maxpositions = getmaxpositions( $idtype );	// pour éviter de repartir de la valeur modifiée en cas de rechargement de la page
	$maxdonnesjouees = $pns*$njouees;
	//print "<p>jouées: ".$donnesjouees." ".$maxdonnesjouees."</p>";
	?>
	
	<div id="section_affichage" class="section_invisible">
	<h3>Tournoi terminé</h3>
	<p><button class="myButton" onclick="goto44()">Affichage résultats provisoires</br>Arbitrage / Clôture du tournoi</button></p>
	<p>&nbsp;</p>
	</div>
	
	<div id="section_forcage">
	<p>En cas d'erreur de saisie sur une donne, vous pouvez corriger le résultat sans attendre que le tournoi soit terminé, en cas de souci sur un étui, vous pouvez afficher le diagramme pour reconfigurer l'étui.</p>
	<p><em>Sélectionnez l'étui concerné</em></br>
	<span id="etuim10" class='xNum2'>&nbsp;-10&nbsp;</span>&nbsp;
	<span id="etuim1" class='xNum2'>&nbsp;-1&nbsp;</span>&nbsp;
	<span class="xDigit">n°<span id='etui'><?php echo $etui; ?></span></span>&nbsp;
	<span id="etuip1" class='xNum2'>&nbsp;+1&nbsp;</span>&nbsp;
	<span id="etuip10" class='xNum2'>&nbsp;+10&nbsp;</span></p>
	
	<p><button class="myButton" onClick="goto43c()">Correction</button>&nbsp;
	<button class="myButton" onClick="goto46()">Diagramme</button></p>
	
	<h3>Attente tournoi terminé pour afficher les résultats provisoires</h3>
	<p>S'il manque des résultats, forçage terminaison et entrée des résultats manquants en utilisant les feuilles de suivi des étuis ou la feuille de marque de la table concernée.</p>
	<p><button class="myButton" onClick="clickForcageArretTournoi()">Forcer l'arrêt du tournoi</button></p>
	
	<div id="section_arret_tournoi" class="section_invisible">
	<p>Attention, après l'arrêt, le directeur devra rentrer les résultats manquants !</p>
	<p><button class="myButton oktogoon" id="valid2" onClick="stoptournoi()">Je confirme</button></p>
	<p><button class="myButton kotogoon" id="valid3" onClick="clickAnnulationArretTournoi()">Oups ! J'annule</button></p>
	</div>
	
	</div>
	
	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	

	</div>
	
	<script>
	idtournoi	= parseInt( "<?php echo $idtournoi; ?>" );
	genre		= parseInt( "<?php echo $genre; ?>" );
	st_phase_init = parseInt( "<?php echo $st_phase_init; ?>" );
	max_tables	= parseInt( "<?php echo $max_tables; ?>" );
	ntables		= parseInt( "<?php echo $ntables; ?>" );
	ndonnes		= parseInt( "<?php echo $ndonnes; ?>" );
	npositions	= parseInt( "<?php echo $npositions; ?>" );
	
	masquelignesconnexions();
	affichelignesconnexions();
	//afficheconnexions();
	
	paquet  = parseInt( "<?php echo $paquet; ?>" );
	relais  = parseInt( "<?php echo $relais; ?>" );
	maxpositions = parseInt( "<?php echo $maxpositions; ?>" );
	
	startseq  = parseInt( "<?php echo $startseq; ?>;" );
	dureefirstposition  = parseInt( "<?php echo $dureefirstposition; ?>;" );
	dureenextposition  = parseInt( "<?php echo $dureenextposition; ?>;" );

	donnesjouees = parseInt( "<?php echo $donnesjouees; ?>" );
	maxdonnesjouees = parseInt( "<?php echo $maxdonnesjouees; ?>" );

	if( donnesjouees == maxdonnesjouees ) {
		$('#section_encours').addClass( 'section_invisible' );
		$("#section_affichage").removeClass( "section_invisible" );
		$("#section_forcage").addClass( "section_invisible" );
	}
	else {
		refreshConnexions();
	}
	</script>
	
</body>
</html>