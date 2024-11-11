<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$table = htmlspecialchars( $_GET['table'] );
$ligne = "NS";

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
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link  href="css/bridgestylesheet.css" rel="stylesheet" />
	<link  href="images/favicon.ico" rel="icon" type="image/x-icon">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge62.js"></script>
</head>

<script>
var relpgm = "<?php echo $relpgm; ?>";
var withback = "<?php echo $withback; ?>";
var table = "<?php echo $table; ?>";
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function back61() {
	var nextstring = 'mitch61.php?idtournoi=' + idtournoi;
	location.replace( nextstring );
};
function goto62() {
	var nextstring = "mitch62.php?idtournoi=" + idtournoi + '&table=' + table;
	location.replace( nextstring );
};
function goto63() {
	var nextstring = "mitch63.php?idtournoi=" + idtournoi + '&table=' + table;
	location.replace( nextstring );
};
function back64() {
	location.replace( "<?php echo $back; ?>" );
};
function goto66() {
	var nextstring = "bridge66.php?idtournoi=" + idtournoi+ "&w=" +  window.innerWidth;
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

	<div style="text-align:center">
	<?php
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$idtype		= $t['idtype'];
		$pairesNS 	= $t['pairesNS'];
		$pairesEO 	= $t['pairesEO'];
		$njouees  	= $t['njouees'];
		$ntables	= $t['ntables'];
		$ndonnes  	= $t['ndonnes'];
		$paquet		= $t['paquet'];
		$saut 		= $t['saut'];
		$gueridon	= $t['gueridon'];
		
		// Relais NS
		$relais  = $t['relais'];
		$relaisNS = 0;
		$relaisEO = 0;
		if ( $relais > 0 ) {
			if ( $pairesNS > $pairesEO ) $relaisNS = $relais;
			else $relaisEO = $relais;
		}
		
		if ( $ligne == "NS" ) {	// toujours vrai pour Mitch62
			// récupération des dernières valeurs jouées par la paire NS en cas de reprise
			$res = getParmTable( $table );
			$cpt = $res['cpt'];
			$position = intval( $cpt / $paquet );
			
			// numéro des équipes
			$numNS = $table;
			$numEO = $table - $position; 
			if ( ( $position >= $saut )&&( $saut > 0) ) 	$numEO --;
			if ( $numEO < 1 ) $numEO += max( $pairesNS, $pairesEO );
			$notour = $position+1;		// compteur de tours
			$mintour = getMinTour( $pairesNS );
			
			print "<h2>Table</h2><h2><span class='numtable'>$numNS</span></h2>";
			$ligneNS = getligneNS( $idtournoi, $numNS );
			$nameNord = $ligneNS['N']['nomcomplet'];
			$nameSud  = $ligneNS['S']['nomcomplet'];
			print "<p>En Nord Sud: $nameNord et $nameSud</p>";
			
			// test reconnexion alors que la table a fini de jouer
			if ( $cpt < $njouees ) {
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
						print "<h3>Vous êtes en relais</h3>";
						print "<p>Dans la première position, la paire<br/><b>Nord Sud en relais</b> prépare les donnes<br/>et peut entrer les diagrammes.</p>";
					}
					else {
						print "<h3>Vérifiez vos adversaires:</h3>";
						$ligneEO = getligneEO( $idtournoi, $numEO );
						$nameEst   = $ligneEO['E']['nomcomplet'];
						$nameOuest = $ligneEO['O']['nomcomplet'];
						print "<p>En Est Ouest: $nameEst et $nameOuest</p>";
					}
					
					print "<h3>Récupérez les étuis:</br>";
					print liste_etuis( $firstdonne, $paquet );
					if ( ($gueridon > 0)&&($pairesNS == $pairesEO)&&( ($numNS==1)||($numNS==$pairesNS) ) ) {
						print "à partager avec la table ".( ($numNS==1) ? $pairesNS : 1 );
					}
					print "</h3>";
					
					if ( $withback == $table ) {
						print '<p><button class="myButton" onclick="goto63()">Continuez à jouer</button></p><p>&nbsp;</p>';
					}
					else {
						print '<p><button class="myButton" onclick="goto63()">Si numéro table OK</br>commencez à jouer</button></p>';
					}
				}
				
				else { // on patiente
					print "<h2>Patientez<br/>en attendant la fin du tour n°$mintour</h2>";
					
					if ( !$withback ) {
						print '<p>&nbsp;</p>';
						print '<p><button class="myButton" onclick="back61()">Si ERREUR sur le n° table</br>retour page de sélection table</button></p>';
					}
				}
				
				//
				// ajout le 12/07/2024
				if ( $withback == $table ) {
					// retour possible
					print( "<p><button  class='myButton' onClick='back64()'>Revoir les résultats</br> position précédente</button></p>" );
				}
				//
				// armement test cyclique
				print "<script>refreshPositions(".$numNS.");</script>";
			}
			else {
				setCnxFin( $numNS );
				print "<p>&nbsp;</p><p>La table $numNS a terminé le tournoi.</p>";
				print '<p><button class="myButton" onclick="goto66()">Affichage des résultats provisoires</br>de la table</button></p>';
			}
		}
		else {	// EO
			print "<h2>Bug Table</h2><h2><span class='numtable'>$numNS</span></h2>";
		}
	}
	else {
		print "<h2>Table</h2><h2><span class='numtable'>?</span></h2>";
		print "<h2>Faux départ ou tournoi clôturé</h2><h2>Revenez page d'accueil</h2>";
	}
	?>
	<p id='currpos'>&nbsp;</p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	
	
	<script type="text/javascript"> 
	
	var notour = parseInt( "<?php echo $notour; ?>" );
	var mintour = parseInt( "<?php echo $mintour; ?>" );
	</script>
	
	</div>
</body>
</html>