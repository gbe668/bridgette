<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$numpaire = htmlspecialchars( $_GET['paire'] );
$etui = isset($_GET['etui']) ? $_GET['etui'] : 0;

//
// ajout le 12/07/2024
$_SESSION['backidt'] = $idtournoi;
$origine = $_SERVER['REQUEST_URI'];
$back64 = base64_encode($origine);
$_SESSION['withback'] = 0;	// retour interdit
$_SESSION['back64'] = $back64;
//
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery.mobile-1.5.0-rc1.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

var back64 = "<?php echo $back64; ?>";

var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var numpaire = parseInt( "<?php echo $numpaire; ?>" );

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto62() {
	var nextstring = "howell62.php?idtournoi=" + idtournoi + '&paire=' + numpaire;
	location.replace( nextstring );
};
function goto63() {
	var nextstring = "howell63.php?idtournoi=" + idtournoi;
	nextstring += '&paire=' + numpaire;
	location.replace( nextstring );
};
function goto65() {
	var nextstring = "howell65.php?idtournoi=" + idtournoi;
	nextstring += '&paire=' + numpaire + '&donne=' + donne;
	location.replace( nextstring );
};
function goto66() {
	var nextstring = "bridge66.php?idtournoi=" + idtournoi + "&w=" + window.innerWidth;
	location.replace( nextstring );
};
$(document).on( "click", "td.seletui", function(event) {
	var id = $(this).parent().attr("id");
	const figs = id.split('_');
	console.log( "Etui ", figs[1] );
	if ( donne != parseInt(figs[1]) ) {
		donne = parseInt(figs[1]);
		display_resultat(donne);
		$("#makeableContracts").hide();
	}
});
$(function() {
	$("div.swipebox").on('swipeleft' , function(event) { swipeleft();  } );
	$("div.swipebox").on('swiperight', function(event) { swiperight(); } );
})
$.mobile.loading().hide();		// suite ajout jquery.mobile-1.5.0-rc1.min.js

// ajout affichage résultat analyse - 30/07/2025
$(document).on( "click", "#showanalysis", function() { $("#makeableContracts").toggle(); });

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
	$enableswipe = 0;
	if ( ($etat == $st_phase_jeu)||($etat == $st_phase_fini) ) {
		$idtype	 = $t['idtype'];
		$njouees = $t['njouees'];
		$paquet	 = $t['paquet'];
		$relais  = $t['relais'];
		// récupération des dernières valeurs jouées en cas de reprise
		$res = getParmTable( $numpaire );
		$cpt = $res['cpt'];			// compteur de donnes déjà enregistrées
		$notour = $res['pos'];		// compteur de tours

		// correction compteur de tours pour l'affichage de la dernière donne du paquet
		$changement = $cpt % $paquet;
		if ( $changement == 0 and $cpt > 0 ) $notour--;
		
		// positionnement ...
		$p = getposhowell( $idtype, $numpaire, $notour, $paquet );	
		$numtable   = $p['table'];
		$adversaire = $p['adversaire'];
		$diagramme = null;
		
		print "<h2><span class='numpaire'>Table n°$numtable</span></h2>";
		if ( $adversaire == 0 ) {
			$ligneP = getligneNS( $idtournoi, $numpaire );
			$nameP1 = $ligneP['N']['nomcomplet'];
			$nameP2 = $ligneP['S']['nomcomplet'];
			if ( $p['NS'] == 1 ) {
				// Vous êtes en NS
				print "<p>Nord Sud: $nameP1 et $nameP2</br>en relais en Nord-Sud.</p>";
				$numNS = $numpaire;
				$numEO = $p['adversaire'];
			}
			else {
				// Vous êtes en EO
				print "<p>Est Ouest: $nameP1 et $nameP2</br>en relais en Est-Ouest.</p>";
				$numEO = $numpaire;
				$numNS = $p['adversaire'];
			}
			if ( $cpt < $njouees ) {
				print '<script>goto62();</script>';
			}
			else {
				setCnxFin( $numNS );
				setCnxFin( $numEO );
				print "<p>La table $numtable a terminé le tournoi.</p>";
				print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</button></p>';
			}
			$donne = 0;
		}
		else {
			if ( $p['NS'] == 1 ) {
				$numNS = $numpaire;
				$numEO = $p['adversaire'];
			}
			else {
				$numNS = $p['adversaire'];
				$numEO = $numpaire;
			}
			$ligneNS = getligneNS( $idtournoi, $numNS );
			$ligneEO = getligneNS( $idtournoi, $numEO );
			$nameNord	= $ligneNS['N']['nomcomplet'];
			$nameSud	= $ligneNS['S']['nomcomplet'];
			$nameEst	= $ligneEO['N']['nomcomplet'];
			$nameOuest	= $ligneEO['S']['nomcomplet'];

			print "<p>Nord Sud: $nameNord et $nameSud</br>Est Ouest: $nameEst et $nameOuest</p>";
			
			$donne = ( $etui > 0 ) ? $etui : $res['numdonne'];	// numéro dernière donne enregistrée

			// modifié le 10/06/2024
			print '<div class="swipebox">';
			print '<div id="section_resultat">résultat</div>';
			print '<div id="section_diagramme">diagramme</div>';
			print '<div id="makeableContracts" hidden>&nbsp;</div>';
			print '</div>';
			
			if ( $cpt == 1) print "<p>1ère donne jouée sur $njouees</p>";
			else print "<p>$cpt donnes jouées sur $njouees</p>";
			if ( $cpt < $njouees ) {
				if ( $changement == 0 and $cpt > 0 ) {
					print htmlResultatPaquet($idtournoi, $numNS, $numEO );
					$enableswipe = 1;
				
					//print "<h3>Attention</br>les joueurs changent de position !</h3>";
					print '<p><button class="myStartButton" onclick="goto62()">Passez à la</br>position suivante</button></p>';
					//
					// ajout le 12/07/2024
					$_SESSION['withback'] = $numpaire;	// retour possible
					//
				}
				else {
					//$nextdonne = $donne +1;
					print "<p><button class='myStartButton' onclick='goto63()'>Passez à la</br>donne suivante</button></p>";
				}
				if ( $teston == 1 ) print "TEST remplissage auto TEST<script>setTimeout( goto62, 1000 );</script>";
			}
			else {
				print htmlResultatPaquet($idtournoi, $numNS, $numEO );
				$enableswipe = 1;
				
				setCnxFin( $numNS );
				setCnxFin( $numEO );
				print "<p>La table $numtable a terminé le tournoi.</p>";
				print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</button></p>';
				print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
			}
		}
	}
	else {
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Tournoi clôturé</h2><h2>Revenez page d'accueil</h2>";
	}
	?>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	
	
	<script type="text/javascript">
	var donne  = parseInt( "<?php echo $donne; ?>" );
	var numNS  = parseInt( "<?php echo $numNS; ?>" );
	var numEO  = 0;
	var enableswipe = parseInt( "<?php echo $enableswipe; ?>" );;
	var paquet = parseInt( "<?php echo $paquet; ?>" );;

	display_resultat(donne);
	</script>
	</div>
</body>
</html>