<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$numtable = htmlspecialchars( $_GET['table'] );
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

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto62() {
	var nextstring = "mitch62.php?idtournoi=" + idtournoi + "&table=" + numNS;
	location.replace( nextstring );
};
function goto63back() {
	var nextstring = "mitch63.php?idtournoi=" + idtournoi;
	nextstring += '&table=' + numNS + '&back=' + base64_origine;
	location.replace( nextstring );
};
function goto63() {
	var nextstring = "mitch63.php?idtournoi=" + idtournoi;
	nextstring += '&table=' + numNS;
	location.replace( nextstring );
};
function goto65() {
	var nextstring = "mitch65.php?idtournoi=" + idtournoi;
	nextstring += '&table=' + numNS + '&donne=' + donne;
	location.replace( nextstring );
};
function goto66() {
	var nextstring = "bridge66.php?idtournoi=" + idtournoi+ "&w=" + window.innerWidth;
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
		$idtype   = $t['idtype'];
		$pairesNS = $t['pairesNS'];
		$pairesEO = $t['pairesEO'];
		
		$ntables = $t['ntables'];
		$ndonnes = $t['ndonnes'];
		$paquet	 = $t['paquet'];
		$saut	 = $t['saut'];
		
		// Relais NS
		$relais  = $t['relais'];
		$relaisNS = 0;
		$relaisEO = 0;
		if ( $relais > 0 ) {
			if ( $pairesNS > $pairesEO ) $relaisNS = $relais;
			else $relaisEO = $relais;
		}
		
		// récupération des dernières valeurs jouées après incrémentation des compteurs
		$res = getParmTable( $numtable );
		$cpt = $res['cpt'];			// compteur de donnes déjà enregistrées
			
		$position = intval( $cpt / $paquet );
		$numNS = $numtable;
		$numEO = $numtable - $position;
		// test saut
		if ( ( $position >= $saut ) and ( $saut > 0) ) 	$numEO --;
		if ( $numEO < 1 ) $numEO = $numEO + max( $pairesNS, $pairesEO );
		
		// numéro équipe adverse avant incrémentation compteur de donnes jouées
		$oldcpt = $cpt - 1;
		$oldposition = intval( $oldcpt / $paquet );
		$oldEO = $numtable - $oldposition; 	// pour l'affichage de la bonne paire Est Ouest
		// test saut
		if ( ( $oldposition >= $saut ) and ( $saut > 0) ) 	$oldEO --;
		if ( $oldEO < 1 ) $oldEO = $oldEO + max( $pairesNS, $pairesEO );

		$ligneNS = getligneNS( $idtournoi, $numNS );
		$nameNord  = $ligneNS['N']['nomcomplet'];
		$nameSud   = $ligneNS['S']['nomcomplet'];
		
		print "<h2><span class='numpaire'>Table n°$numtable</span></h2>";
		
		// Relais NS
		if ( ($relaisNS > 0)&&($oldEO == $pairesNS) ) {
			print "<p> En Nord Sud: $nameNord et $nameSud</br>Vous êtes en relais</p>";
		}
		else {
			$ligneEO = getligneEO( $idtournoi, $oldEO );	// paire avant incrémentation $cpt
			$nameEst   = $ligneEO['E']['nomcomplet'];
			$nameOuest = $ligneEO['O']['nomcomplet'];
			print "<p>Nord Sud: $nameNord et $nameSud</br>Est Ouest: $nameEst et $nameOuest</p>";
		}
		
		$donne = ( $etui > 0 ) ? $etui : $res['numdonne'];	// numéro dernière donne enregistrée

		// modifié le 10/06/2024
		print '<div class="swipebox">';
		print '<div id="section_resultat">résultat</div>';
		print '<div id="section_diagramme">diagramme</div>';
		print '<div id="makeableContracts" hidden>&nbsp;</div>';
		print '</div>';

		$njouees = $t[ 'njouees' ];
		$changement = $cpt % $paquet;
		if ( $cpt == 1) print "<p>1ère donne jouée sur $njouees</p>";
		else print "<p>$cpt donnes jouées sur $njouees</p>";
			
		if ( $cpt < $njouees ) {
			if ( $changement == 0 and $cpt > 0 ) {
				print htmlResultatPaquet($idtournoi, $numNS, $oldEO );
				$enableswipe = 1;
				
				if ( ( $position == $saut ) and ( $saut > 0) ) {
					print "<h3>Les Est-Ouest sautent une table</h3>";
				}
				print "<p><button class='myStartButton' onclick='goto62()'>Passez à la</br>position suivante</button></p>";
				//
				// ajout le 12/07/2024
				$_SESSION['withback'] = $numtable;	// retour possible
				//
			}
			else {
				print "<p><button class='myStartButton' onclick='goto63()'>Passez à la</br>donne suivante</button></p>";
			}
			if ( $teston == 1 ) print "TEST remplissage auto TEST<script>setTimeout( goto63, 1000 );</script>";
		}
		else {
			print htmlResultatPaquet($idtournoi, $numNS, $oldEO );
			$enableswipe = 1;
			
			setCnxFin( $numtable );
			print "<p>La table $numtable a terminé le tournoi.</p>";
			print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</button></p>';
			print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
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
	var idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	var numNS  = parseInt( "<?php echo $numNS; ?>" );
	var numEO  = parseInt( "<?php echo $numEO; ?>" );
	var enableswipe = parseInt( "<?php echo $enableswipe; ?>" );;
	var paquet = parseInt( "<?php echo $paquet; ?>" );;

	display_resultat(donne);
	</script>
	</div>
</body>
</html>