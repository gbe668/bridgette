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
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge63.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var relimg = "<?php echo $relimg; ?>";

function clickValidation() {
	$("#section_validation").addClass( "section_invisible" );
	$("#section_suivante").removeClass( "section_invisible" );
	
	// Enregistrement de la donne jouée
	var dataString = 'insertdonne.php?idtournoi=' + idtournoi + '&donne=' + realdonne.etui;
	dataString += '&ns=' + numNS + '&eo=' + numEO;
	dataString += '&contrat=' + realdonne.contrat;
	dataString += '&jouepar=' + realdonne.declarant;
	dataString += '&entame='  + realdonne.entame;
	dataString += '&resultat=' + realdonne.res;
	dataString += '&points=' + realdonne.points;
	//console.log( dataString );
	$.get(dataString, "", function(strjson) {
		$('#validok').html( strjson.display );
		if ( strjson.ok > 0 ) {
			// affichage scores, entrée diagrammes, ...
			//setTimeout(function() { goto64() }, 500);
			goto64();
		}
		if ( strjson.ok == 0 ) {
			// enregistrement déjà réalisé, délai affichage
			setTimeout(function() { goto64(); }, 1000);
		}
		if ( strjson.ok < 0 ) {
			// tournoi terminé
			setTimeout(function() { gotoindex() }, 1000);
		}
	},"json")
	.done( function() {  } )
	.fail( function( jqxhr,settings,ex ) {
		$('#validok').html('Erreur: '+ ex + "</br>" + dataString + "</br>Faire une copie d'écran pour Bruno</br><b>Rappuyez sur le bouton de validation.</b>" ); 
		$("#section_validation").removeClass( "section_invisible" );
	} );
};

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto64() {
	var nextstring = "mitch64.php?idtournoi="+idtournoi + '&table='+numNS + "&w="+window.innerWidth;
	location.replace( nextstring );
};
function gotorelaisns() {
	// incrementCompteurRelaisNS( $donne, $ns, $eo, $paquet );
	var first = Math.floor((donne-1)/paquet)*paquet +1;	// 1ère donne du paquet
	console.log( "donne ", donne, " first ", first );
	$.get( "relaismitchell.php", {donne:donne, ns:numNS, eo:0, paquet:paquet}, function() {
		goto64();
	},"text");
};
function goto66() {
	var nextstring = "bridge66.php?idtournoi=" + idtournoi + "&w=" + window.innerWidth;
	location.replace( nextstring );
};
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
	$numtable = htmlspecialchars( $_GET['table'] );
	
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$idtype	  = $t['idtype'];
		$paquet	  = $t['paquet'];
		$ndonnes  = $t['ndonnes'];
		$njouees  = $t['njouees'];
		$pairesNS = $t['pairesNS'];
		$pairesEO = $t['pairesEO'];
		$ntables  = $t['ntables' ];
		$saut	  = $t['saut'];
		$gueridon = $t['gueridon'];
		
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
		
		// récupération des dernières valeurs jouées en cas de reprise
		$res = getParmTable( $numtable );
		$cpt	= $res['cpt'];		// compteur de donnes déjà enregistrées
		$notour = $res['pos'];		// compteur de tours
		
		// numéro équipe adverse
		$position = intval( $cpt / $paquet );
		$numNS = $numtable;
		$numEO = $numtable - $position; 
		
		// test saut
		if ( ( $position >= $saut )&&( $saut > 0) ) 	$numEO --;
		if ( $numEO < 1 ) $numEO = $numEO + max( $pairesNS, $pairesEO );
		
		$ligneNS	= getligneNS( $idtournoi, $numNS );
		$nameNord	= $ligneNS['N']['nomcomplet'];
		$nameSud	= $ligneNS['S']['nomcomplet'];
		
		print "<h2><span class='numpaire'>Table n°$numtable</span></h2>";
		
		// test fin de tournoi après une diminution du nombre de positions
		if ( $cpt < $njouees ) {
			$donne	= $res['numdonne'];	// dernière donne enregistrée
			$firstdonne = floor(($donne-1)/$paquet)*$paquet +1;
			if ( $cpt%$paquet == 0 ) $firstdonne +=$paquet;
			if ( $firstdonne > $ndonnes ) $firstdonne = 1;
			getPaquet2play( $idtournoi, $numtable, $firstdonne, $paquet );
			
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
				// Donne suivante
				$cpt ++;
				
				$ligneEO	= getligneEO( $idtournoi, $numEO );
				$nameEst	= $ligneEO['E']['nomcomplet'];
				$nameOuest	= $ligneEO['O']['nomcomplet'];
				print "<p>Nord Sud: $nameNord et $nameSud</br>Est Ouest: $nameEst et $nameOuest</p>";
				print infoDonneTournoi($cpt, $njouees, $notour, $t['npositions']);

				//$donne = $firstdonne + findFirst2Play();
				$donne = findFirst2PlayAfter($firstdonne, $donne);
				
				print showListeEtuis( $firstdonne, $paquet );
				if ( ($gueridon > 0)&&($pairesNS == $pairesEO)&&( ($numNS==1)||($numNS==$pairesNS) ) ) {
					print "à partager avec la table ".( ($numNS==1) ? $pairesNS : 1 );
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
			setCnxFin( $numtable );
			print "<p>&nbsp;</p><p>La table $numtable a terminé le tournoi.</p>";
			print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</br>de la table</button></p>';
		}
	}
	else {
		$notour = 0;
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Faux départ ou tournoi clôturé</h2><h2>Revenez page d'accueil</h2>";
		print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
	}
	?>

	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	
	</div>

	<script type="text/javascript"> 
	var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	var donne = parseInt( "<?php echo $donne; ?>" );
	var numNS = parseInt( "<?php echo $numNS; ?>" );
	var numEO = parseInt( "<?php echo $numEO; ?>" );
	var paquet = parseInt( "<?php echo $paquet; ?>" );
	
	realdonne = new Donnejouee(donne);
	</script>
</body>
</html>