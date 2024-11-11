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
var withback = "<?php echo $withback; ?>";
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var paire = parseInt( "<?php echo $paire; ?>" );

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto62() {
	retparms = { next:"howell62" };
	passAndroid( retparms );
};
function goto63() {
	retparms = { next:"howell63" };
	passAndroid( retparms );
};
var back = "<?php echo $back; ?>";
function back64() {
	retparms = { next:"howell64", back:back, etui:0 };
	passAndroid( retparms );
};
function goto64relais() {
	$.get( "/relaishowell.php", {donne:firstdonne, ns:paire, eo:0, inc:paquet, paquet:paquet, token:token}, function() {
		retparms = { next:"howell64", etui:0 };
		passAndroid( retparms );
	},"text");
};
function goto66() {
	retparms = { next:"bridge66" };
	passAndroid( retparms );
};

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
	<button onclick="gotoindex();">Retour page d'accueil</button>
	<p>&nbsp;</p>
	</div>
	</center>

	<div style="text-align: center">
	<?php
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$idtype  = $t[ 'idtype' ];
		$njouees = $t[ 'njouees' ];
		$ntables = $t[ 'ntables' ];
		$paquet  = $t[ 'paquet' ];
		$relais  = $t[ 'relais' ];
		
		$ligneP = getligneNS( $idtournoi, $paire );
		$nameP1 = $ligneP['N']['nomcomplet'];
		$nameP2 = $ligneP['S']['nomcomplet'];
		print "<h2><span class='numpaire'>Paire n°$paire</span></h2>";
		print "<p>$nameP1 et $nameP2</p>";
		
		// récupération des dernières valeurs jouées en cas de reprise
		$res = getParmTable( $paire );
		$donne = $res['numdonne'];		// dernière donne jouée
		$cpt   = $res['cpt'];			// compteur de donnes déjà jouées
		
		// test reconnexion alors que la table a fini de jouer
		if ( $cpt < $njouees ) {
			$notour = $res['pos'];			// compteur de tours
			$mintour = getMinTour( $ntables );
			$p = getposhowell( $idtype, $paire, $notour, $paquet );	
			$table   = $p['table'];
			$firstdonne = $p['last'] + 1;	// 1ère donne du paquet
			$etuis = liste_etuis( $firstdonne, $paquet );
			$adversaire = $p['adversaire'];

			if ( ($notour <= $mintour)||($parametres['avanceh'] == 0) ) {
				if ( $adversaire == 0 ) {	// en relais
					// paire en relais
					print "<h2>Tour n°$notour, vous êtes en relais</h2>";
					print "<h2>Patientez table</h2><h2><span class='numtable'>$table</span></h2>";
					print '<p>en attendant la fin du tour.</p>';
				
					//print "<h3>Récupérer les étuis:</br>$etuis</h3>";

					print '<p><button class="myStartButton" onclick="goto64relais()">Si joueurs OK</br>cliquez ICI</br>pour continuer</button></p>';
				}
				else {
					print "<h2>Tour n°<span class='notour'>$notour</span>, allez table</h2><h2><span class='numtable'>$table</span></h2>";
					if ( $p['NS'] == 1 ) {
						// Vous êtes en NS
						$numNS = $paire;
						$numEO = $adversaire;
						$ligneA = getligneNS( $idtournoi, $numEO );
						print "<h2>en Nord-Sud.</h2>";
					
						print "<p>En Est-Ouest, la paire n°$numEO:";
						$nameA1 = $ligneA['N']['nomcomplet'];
						$nameA2 = $ligneA['S']['nomcomplet'];
						print "</br>$nameA1 et $nameA2</p>";
					}
					else {
						// Vous êtes en EO
						$numEO = $paire;
						$numNS = $adversaire;
						$ligneA = getligneNS( $idtournoi, $numNS );
						print "<h2>en Est-Ouest.</h2>";
					
						print "<p>En Nord-Sud, la paire n°$numNS:";
						$nameA1 = $ligneA['N']['nomcomplet'];
						$nameA2 = $ligneA['S']['nomcomplet'];
						print "</br>$nameA1 et $nameA2</p>";
					}
					
					print "<h3>Récupérez les étuis:</br>$etuis</h3>";

					if ( $withback == $paire ) {
						print '<p><button class="myButton" onclick="goto63()">Continuez à jouer</button></p><p>&nbsp;</p>';
					}
					else {
						print '<p><button class="myButton" onclick="goto63()">Si joueurs OK commencez</br>à jouer</button></p>';
					}
				}
			}
			else {	// on patiente
				$firstdonne = 0;
				print "<h2>Patientez<br/>en attendant la fin du tour n°$mintour</h2>";
				print "<h3>Au prochain tour, vous irez table</h2><h2><span class='numtable'>$table</span></h3>";
				if ( $p['NS'] == 1 ) print "<h3>en Nord-Sud.</h3>";
				else print "<h3>en Est-Ouest.</h3>";
				
				if ( $adversaire == 0 ) {	// en relais
					print "Vous serez en relais";
				}
				else {
					$ligneA = getligneNS( $idtournoi, $adversaire );
					$nameA1 = $ligneA['N']['nomcomplet'];
					$nameA2 = $ligneA['S']['nomcomplet'];
					print "<h3>Vos prochains adversaires:</h3>";
					print "<p>$nameA1 et $nameA2</p>";
				}
				print "<h3>Les prochains étuis:</br>$etuis</h3>";
			}
			
			//
			// ajout le 12/07/2024
			if ( $withback == $paire ) {
				// retour possible
				print( "<p><button  class='myButton' onClick='back64()'>Revoir les résultats</br> position précédente</button></p>" );
			}
			//
			// armement test cyclique
			print "<script>refreshPositions(".$paire.");</script>";
		}
		else {
			setCnxFin( $paire );
			print "<p>&nbsp;</p><p>Vous avez terminé le tournoi</p>";
			print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</br>de la table</button></p>';
			// init variables
			$firstdonne = 0;
			$notour = 0;
		}
	}
	else {
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Faux départ ou tournoi clôturé !</h2><h2>Revenez page d'accueil</h2>";
	}
	?>
	<p id='currpos'>&nbsp;</p>
	
	<script type="text/javascript"> 
	var donne  = parseInt( "<?php echo $donne; ?>" );
	var firstdonne  = parseInt( "<?php echo $firstdonne; ?>" );
	var notour = parseInt( "<?php echo $notour; ?>" );
	var cpt  = parseInt( "<?php echo $cpt; ?>" );
	var paquet = parseInt( "<?php echo $paquet; ?>" );
	var mintour = parseInt( "<?php echo $mintour; ?>" );
	</script>
	
	</div>
</body>
</html>