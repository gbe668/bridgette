<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$paire = htmlspecialchars( $_GET['paire'] );
$ligne = htmlspecialchars( $_GET['ligne'] );

//
// ajout le 12/07/2024
$backidt  = isset( $_SESSION['backidt']  ) ? $_SESSION['backidt']  : 0;
$withback = isset( $_SESSION['withback'] ) ? $_SESSION['withback'] : 0;
$back = "not set";
if ( ($withback > 0)&&($backidt == $idtournoi) ) {
	$back64 = $_SESSION['back64'];
	$back = base64_decode($back64);
}
else {
	$withback = 0;
}
//
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge62.js"></script>
</head>

<script>
var token = "<?php echo $token; ?>";
var relpgm = "<?php echo $relpgm; ?>";
var withback = parseInt( "<?php echo $withback; ?>" );;
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var paire = parseInt( "<?php echo $paire; ?>" );
	
function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto62() {
	retparms = { next:"mitch62" };
	passAndroid( retparms );
};
function goto63() {
	retparms = { next:"mitch63" };
	passAndroid( retparms );
};
var back = "<?php echo $back; ?>";
function back64() {
	retparms = { next:"mitch64", back:back, etui:0 };
	passAndroid( retparms );
};
function goto64diags() {
	retparms = { next:"mitch64diags", donne:donne };
	passAndroid( retparms );
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

	<div style="text-align:center">
	<?php
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$idtype		= $t['idtype'];
		$genre		= $t['genre'];
		$pairesNS 	= $t['pairesNS'];
		$pairesEO 	= $t['pairesEO'];
		$njouees  	= $t['njouees'];
		$ntables	= $t['ntables'];
		$ndonnes  	= $t['ndonnes'];
		$paquet		= $t['paquet'];
		$saut 		= $t['saut'];
		$gueridon	= $t['gueridon'];
		$posgueridon = $ntables/2;
		$npositions	= $t['npositions'];
		
		// Relais NS
		$relais  = $t['relais'];
		$relaisNS = 0;
		$relaisEO = 0;
		if ( $relais > 0 ) {
			if ( $pairesNS > $pairesEO ) $relaisNS = $relais;
			else $relaisEO = $relais;
		}
		
		if ( $ligne == "NS" ) {
			// récupération des dernières valeurs jouées par la paire NS en cas de reprise
			$res = getParmTable( $paire );
			$cpt = $res['cpt'];
			$position = intval( $cpt / $paquet );

			// numéro des équipes
			$numNS = $paire;
			$numEO = $paire - $position; 
			if ( ( $position >= $saut )&&( $saut > 0) ) 	$numEO --;
			if ( $numEO < 1 ) $numEO += max( $pairesNS, $pairesEO );
				
			$ligneNS = getligneNS( $idtournoi, $numNS );
			$nameNord = $ligneNS['N']['nomcomplet'];
			$nameSud  = $ligneNS['S']['nomcomplet'];
			print "<h2><span class='numpaire'>Paire n°$paire NS</span></h2>";
			print "<p>$nameNord et $nameSud</p>";
			
			// test reconnexion alors que la table a fini de jouer
			if ( $cpt < $njouees ) {
				print "<h2>Table</h2><h2><span class='numtable'>$numNS</span></h2>";
				
				$notour = $position+1;		// compteur de tours
				$mintour = getMinTour( $pairesNS );
				//print "<h2>Position n°<span class='notour'>$notour</span></h2>";
				
				$donne = $res['numdonne'];	// dernière donne jouée
				$firstdonne = floor(($donne-1)/$paquet)*$paquet +1;
				if ( $cpt%$paquet == 0 ) $firstdonne +=$paquet;
				if ( $firstdonne > $ndonnes ) $firstdonne = 1;

				if ( ($notour <= $mintour)||($parametres['avancem'] == 0) ) {
					// test relais NS
					$posrelaisNS = $position;
					if ( $posrelaisNS < 1 ) $posrelaisNS = $pairesNS;
					if ( ($relaisNS > 0)&&($posrelaisNS == $numNS) ) {
						// paire en relais
						print "<h3>Vous êtes en relais NS</h3>";
						print "<p>Dans la première position, la paire<br/><b>Nord Sud en relais</b> prépare les donnes<br/>et peut entrer les diagrammes.</p>";
					}
					else {
						print "<h3>Vérifiez vos adversaires:</h3>";
						$ligneEO = getligneEO( $idtournoi, $numEO );
						$nameEst   = $ligneEO['E']['nomcomplet'];
						$nameOuest = $ligneEO['O']['nomcomplet'];
						print "<p>En Est Ouest: $nameEst et $nameOuest</p>";
					}
					
					print "<h3>Tour n°<span class='notour'>$notour</span>/$npositions, récupérez les étuis:</br>";
					print liste_etuis( $firstdonne, $paquet );
					if ( ($gueridon > 0)&&($pairesNS == $pairesEO)&&( ($numNS==1)||($numNS==$pairesNS) ) ) {
						print "à partager avec la table ".( ($numNS==1) ? $pairesNS : 1 );
					}
					print "</h3>";
					
					if ( $withback == $paire ) {
						print '<p><button class="myButton" onclick="goto63()">Continuez à jouer</button></p><p>&nbsp;</p>';
					}
					else {
						print '<p><button class="myButton" onclick="goto63()">Si numéro table OK</br>commencez à jouer</button></p>';
					}
				}
				
				else {	// on patiente
					print "<h2>Patientez<br/>en attendant la fin du tour n°$mintour</h2>";
				}
				
				//
				// ajout le 12/07/2024
				if ( $withback == $paire ) {
					// retour possible
					print( "<p><button class='myButton' onClick='back64()'>Revoir les résultats</br> position précédente</button></p>" );
				}
				//
				// armement test cyclique
				print "<script>refreshPositions(".$numNS.");</script>";
			}
			else {
				setCnxFin( $numNS );
				print "<p>&nbsp;</p><p>Vous avez terminé le tournoi.</p>";
				print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</button></p>';
			}
		}
		else {	// EO
			// récupération des dernières valeurs jouées par la paire EO en cas de reprise
			$res = getParmTableEO( $paire );
			$cpt = $res['cpt'];		// donnes jouées (ou non, cas relais/guéridon)
			$position = intval( $cpt / $paquet );
				
			// numéro des équipes
			$numEO = $paire; 
			$numNS = $paire + $position;
			if ( ( $position >= $saut )&&( $saut > 0 ) ) $numNS++;	// table suivante
			if ( $numNS > max($pairesNS, $pairesEO) ) $numNS -= max($pairesNS, $pairesEO);
		
			$ligneEO = getligneEO( $idtournoi, $numEO );
			$nameEst   = $ligneEO['E']['nomcomplet'];
			$nameOuest = $ligneEO['O']['nomcomplet'];
			print "<h2><span class='numpaire'>Paire n°$paire EO</span></h2>";
			print "<p>$nameEst et $nameOuest</p>";
			
			// test reconnexion alors que la table a fini de jouer
			if ( $cpt < $njouees ) {
				print "<h2>Table</h2><h2><span class='numtable'>$numNS</span></h2>";
				
				$notour = $position+1;		// compteur de tours
				$mintour = getMinTour( $pairesNS );
				//print "<h2>Position n°<span class='notour'>$notour</span></h2>";
								
				$res	= getParmTable( $numNS );
				$cpt	= $res['cpt'];
				$donne	= $res['numdonne'];	// numéro dernière donne enregistrée par la paire NS
				$firstdonne = floor(($donne-1)/$paquet)*$paquet +1;
				if ( $cpt%$paquet == 0 ) $firstdonne +=$paquet;
				if ( $firstdonne > $ndonnes ) $firstdonne = 1;
				
				if ( ($notour <= $mintour)||($parametres['avancem'] == 0) ) {
					if ( $notour > $mintour ) {
						print "<h3>Attention<br/>le tour précédent n'est pas terminé.</h3>";
					}
					// test relais EO
					if ( $relaisEO == $numNS ) {
						// paire en relais
						print "<h2>Vous êtes en relais Est Ouest</h2>";
						print "<h3>Tour n°<span class='notour'>$notour</span>/$npositions</h3>";
						if ( ($notour == 1 )&&($gueridon == 0) ) {
							print "<p>Dans la première position, la paire<br/><b>Est Ouest en relais</b> prépare les donnes<br/>et peut entrer les diagrammes.</p>";
							print "<h3>Récupérez les étuis:</br>";
							print liste_etuis( $firstdonne, $paquet );
							print "</h3>";
							print "<p><button class='myButton' onclick='goto64diags()'>Préparez les étuis</button></p>";
						}
						if ($gueridon > 0) {
							print "<p>Il n'y a pas de donnes au relais<br/>avec un guéridon.</p>";
						}
						//incrementCompteurEO( $numEO, $paquet );
						print "<h3>Patientez en attendant<br/>la fin du tour en cours</h3>";
					}
					else {
						print "<h3>Vérifiez vos adversaires:</h3>";
						$ligneNS = getligneNS( $idtournoi, $numNS );
						$nameNord = $ligneNS['N']['nomcomplet'];
						$nameSud  = $ligneNS['S']['nomcomplet'];
						print "<p> En Nord Sud: $nameNord et $nameSud</p>";
					
						print "<h3>Tour n°<span class='notour'>$notour</span>/$npositions, récupérez les étuis:</br>";
						print liste_etuis( $firstdonne, $paquet );
						if ( ($gueridon > 0)&&($pairesNS == $pairesEO)&&( ($numNS==1)||($numNS==$pairesNS) ) ) {
							print "à partager avec la table ".( ($numNS==1) ? $pairesNS : 1 );
						}
						print "</h3>";
						
						if ( $withback == $paire ) {
							print '<p><button class="myButton" onclick="goto63()">Continuez à jouer</button></p><p>&nbsp;</p>';
						}
						else {
							print '<p><button class="myButton" onclick="goto63()">Si numéro table OK</br>commencez à jouer</button></p>';
						}
					}
				}
				
				else {	// on patiente
					print "<h3>Patientez<br/>en attendant la fin du tour n°$mintour</h3>";
					if ( $relaisEO == $numNS ) {	// table relais
						// paire en relais
						print "<h3>Au prochain tour,</br>vous serez en relais Est Ouest</h3>";
					}
					else {
						print "<h3>Au prochain tour, vous irez table</h3>";
						print "<h3><span class='numtable'>$numNS</span></h3>";
					}
				}
				
				//
				// ajout le 12/07/2024
				if ( $withback == $paire ) {
					// retour possible
					print( "<p><button  class='myButton' onClick='back64()'>Revoir les résultats</br> position précédente</button></p>" );
				}
				//
				// armement test cyclique
				print "<script>refreshPositions(0);</script>";
			}
			else {
				setCnxFin( $numNS );
				print "<p>&nbsp;</p><p>Vous avez terminé le tournoi.</p>";
				print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</button></p>';
			}
		}
	}
	else {
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Faux départ ou tournoi clôturé</h2><h2>Revenez page d'accueil</h2>";
	}
	?>
	<p id='currpos'>&nbsp;</p>

	<?php
	if ( $config['site'] == 1 ) {
		print "<p>notour: $notour, mintour: $mintour</p>";
	}
	?>
	
	<script type="text/javascript"> 
	var paquet = parseInt( "<?php echo $paquet; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	var notour = parseInt( "<?php echo $notour; ?>" );
	var mintour = parseInt( "<?php echo $mintour; ?>" );
	</script>
	
	</div>
</body>
</html>