<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$testdelai_1 = 500;	//délai entrée résultat

//
// ajout le 12/07/2024
$_SESSION['withback'] = 0;	// retour interdit
//
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge63.js"></script>
</head>

<script>
var token  = "<?php echo $token; ?>";
var relpgm = "<?php echo $relpgm; ?>";
var relimg = "<?php echo $relimg; ?>";

function clickValidation() {
	$("#section_validation").addClass( "section_invisible" );
	$("#section_suivante").removeClass( "section_invisible" );
	
	// Enregistrement de la donne jouée
	var dataString = '/insertdonne.php?idtournoi=' + idtournoi + '&donne=' + realdonne.etui;
	dataString += '&ns=' + numNS + '&eo=' + numEO;
	dataString += '&contrat=' + encodeURI(realdonne.contrat);
	dataString += '&jouepar=' + realdonne.declarant;
	dataString += '&entame='  + realdonne.entame;
	dataString += '&resultat=' + realdonne.res;
	dataString += '&points=' + realdonne.points;
	dataString += '&token=' + token;
	console.log( dataString );
	$.get(dataString, "", function(strjson) {
		$('#validok').html( strjson.display );
		okkk = strjson.ok;
		if ( strjson.ok > 0 ) {
			// affichage scores, entrée diagrammes, ...
			setTimeout(function() { goto64() }, 500);
		}
		if ( strjson.ok == 0 ) {
			// enregistrement déjà réalisé, récupération des bons compteurs
			$.get( "/getparmtable.php", { numtable:numNS, token:token }, function(strjson) {
				cpt = strjson.cpt;
				donne = strjson.donne;
				setTimeout(function() { goto64(); }, 1000);
			},"json");
		}
		if ( strjson.ok < 0 ) {
			// tournoi terminé
			setTimeout(function() { gotoindex() }, 1000);
		}
	},"json")
	.done( function() {} )
	.fail( function( jqxhr,settings,ex ) {
		$(validok).html('Erreur: '+ ex + "</br>" + dataString + "</br>Faire une copie d'écran pour Bruno</br><b>Rappuyez sur le bouton de validation.</b>" ); 
		$("#section_validation").removeClass( "section_invisible" );
	} );
};

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};

function goto64() {
	retparms = { next:"mitch64", etui:0 };
	passAndroid( retparms );
};
function gotorelaisns() {
	// incrementCompteurRelaisNS( $donne, $ns, $eo, $paquet );
	first = Math.floor((donne-1)/paquet)*paquet +1;	// 1ère donne du paquet
	console.log( "donne ", donne, " first ", first );
	$.get( "/relaismitchell.php", {donne:donne, ns:numNS, eo:0, paquet:paquet, token:token}, function() {
		goto64();
	},"text");
};
function goto66() {
	retparms = { next:"bridge66" };
	passAndroid( retparms );
};

function passAndroid( parms ) {
	strjson = JSON.stringify(parms);
	console.log( "strjson", strjson );
	Android.processNext( strjson );
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
	<button onclick="gotoindex();">Retour page d'accueil</button>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align: center">
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$paire = htmlspecialchars( $_GET['paire'] );
	$ligne = htmlspecialchars( $_GET['ligne'] );
	
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$idtype	  = $t['idtype'];
		$pairesNS = $t['pairesNS'];
		$pairesEO = $t['pairesEO'];
		$ntables  = $t['ntables' ];
		$ndonnes  = $t['ndonnes'];
		$paquet	  = $t['paquet'];
		$saut	  = $t['saut'];
		$njouees  = $t['njouees'];
		
		// Relais NS
		$relais  = $t['relais'];
		$relaisNS = 0;
		$relaisEO = 0;
		if ( $relais > 0 ) {
			if ( $pairesNS > $pairesEO ) {
				$relaisNS = $relais;
			}
			else $relaisEO = $relais;
		}
		
		if ( $ligne == "NS" ) {
			// récupération des dernières valeurs jouées par la paire NS en cas de reprise
			$res	= getParmTable( $paire );
			$cpt	= $res['cpt'];		// compteur de donnes déjà enregistrées
			$position = intval( $cpt / $paquet );
			
			// numéro des équipes
			$numNS = $paire;
			$numEO = $paire - $position; 
			
			// test saut
			if ( ( $position >= $saut )&&( $saut > 0) ) $numEO --;
			if ( $numEO < 1 ) $numEO += max( $pairesNS, $pairesEO );
		}
		else {	// ligne "EO"
			// récupération des dernières valeurs jouées par la paire EO en cas de reprise
			$res = getParmTableEO( $paire );
			$cpt 	= $res['cpt'];		// donnes jouées
			$position = intval( $cpt / $paquet );
				
			// numéro des équipes
			$numEO = $paire; 
			$numNS = $paire + $position;
			
			// test changement de position pour jouer la donne suivante
			if ( ( $position >= $saut )&&( $saut > 0 ) ) $numNS++;
			if ( $numNS > max( $pairesNS, $pairesEO ) ) $numNS -= max($pairesNS, $pairesEO);
		}
		$res	= getParmTable( $numNS );
		$cpt	= $res['cpt'];
		$notour = $res['pos'];		// compteur de tours
		
		$ligneNS	= getligneNS( $idtournoi, $numNS );
		$nameNord	= $ligneNS['N']['nomcomplet'];
		$nameSud	= $ligneNS['S']['nomcomplet'];
		
		print "<h2><span class='numpaire'>Table n°$numNS</span></h2>";
		
		// test fin de tournoi après une diminution du nombre de positions
		if ( $cpt < $njouees ) {
			$donne	= $res['numdonne'];	// dernière donne enregistrée par la paire NS
			$firstdonne = floor(($donne-1)/$paquet)*$paquet +1;
			if ( $cpt%$paquet == 0 ) $firstdonne +=$paquet;
			if ( $firstdonne > $ndonnes ) $firstdonne = 1;
			getPaquet2play( $idtournoi, $numNS, $firstdonne, $paquet );
			
			// Relais NS
			if ( ($relaisNS > 0)&&($numEO == $pairesNS) ) {
				// paire en relais
				print "<p> En Nord Sud: $nameNord et $nameSud</br>Vous êtes en relais</p>";
				print infoDonneTournoi($cpt, $njouees, $notour, $t['npositions']);
				
				$donne = $firstdonne + $cpt%$paquet;
				
				print liste_etuis( $donne, $paquet );
				print "<h2><span class='numetui' id='etui'>$donne</span></h2>";
				if ( first2play( $idtournoi, $firstdonne ) == 0 ) {
					if ( existeDiagramme( $idtournoi, $donne ) == null ) {
						print $strFirst2play;
					}
				}
				
				print "<p>&nbsp;</p>";
				print '<p><button class="myStartButton" onclick="gotorelaisns()">Cliquez ICI</br>pour continuer</button></p>';
				print "<p>Après avoir cliqué sur le bouton ci-dessus,</br>le score provisoire sur la donne s'affichera,</br>vous pourrez entrer les diagrammes</br>et passer à la donne suivante.</p>";
			}
			else {
				$cpt ++;
				
				$ligneEO	= getligneEO( $idtournoi, $numEO );
				$nameEst	= $ligneEO['E']['nomcomplet'];
				$nameOuest	= $ligneEO['O']['nomcomplet'];
				print "<p>Nord Sud: $nameNord et $nameSud</br>Est Ouest: $nameEst et $nameOuest</p>";
				print infoDonneTournoi($cpt, $njouees, $notour, $t['npositions']);

				//$donne = $firstdonne + findFirst2Play();
				$donne = findFirst2PlayAfter($firstdonne, $donne);												  
				
				print showListeEtuis( $firstdonne, $paquet );
				if ( ($idtype == 12)&&($gueridon > 0)&&(($numNS ==1)||($numNS ==6)) ) {
					print "à partager avec la table ".(($numNS==1)?6:1);
				}
				print "<h2><span class='numetui' id='etui'>$donne</span></h2>";
				print showPaquet2play( $firstdonne, $paquet );
				
				if ( first2play( $idtournoi, $donne ) == 0 ) {
					if ( existeDiagramme( $idtournoi, $donne ) == null ) {
						print $strFirst2play;
					}
					else {
						print "<p>L'étui a été préparé par le relais<br/>les diagrammes sont déjà entrés</p>";
					}
				}
				
				print "<h3 id='texteDonneur'>donneur - vulnérabilité</h3>";
				print_tables_saisie_contrat();
				
				print "<p id='textePoints'>Points NS ?</p>";
			
				print "<div id='section_validation' class='section_invisible'>";
				print "<p><button class='myStartButton' id='valid1' onClick='clickValidation()'>Faites valider</br>le résultat</br>par Est/Ouest</button></p>";
				print "<p>Après validation,</br>le score provisoire sur la donne s'affichera,</br>vous pourrez entrer les diagrammes</br>et passer à la donne suivante.</p>";
				print "</div>";

				print "<div id='section_suivante' class='section_invisible'>";
				print "<p id='validok'>Enregistrement en cours ...</p>";
				print "</div>";
			}
			if ( $teston == 1 ) print "TEST remplissage auto TEST<script>setTimeout( autoValidation, $testdelai_1);</script>";
			if ( $teston == 2 ) print "<p><button onClick='autoValidation()'>auto</button></p>";
		}
		else {
			setCnxFin( $numNS );
			print "<p>&nbsp;</p><p>La table $numNS a terminé le tournoi.</p>";
			print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</br>de la table</button></p>';
		}
	}
	else {
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Faux départ ou tournoi clôturé</h2><h2>Revenez page d'accueil</h2>";
		print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
	}
	?>
	</div>

	<script type="text/javascript"> 
	var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	var donne = parseInt( "<?php echo $donne; ?>" );
	var numNS = parseInt( "<?php echo $numNS; ?>" );
	var numEO = parseInt( "<?php echo $numEO; ?>" );
	var position = parseInt( "<?php echo $position; ?>" );
	var cpt  = parseInt( "<?php echo $cpt; ?>" );
	var paquet = parseInt( "<?php echo $paquet; ?>" );
	
	realdonne = new Donnejouee(donne);
	</script>
</body>
</html>