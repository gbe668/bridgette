<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$paire = htmlspecialchars( $_GET['paire'] );
$ligne = htmlspecialchars( $_GET['ligne'] );
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
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge65.js"></script>
</head>

<script>
var back64 = "<?php echo $back64; ?>";

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
function goto65() {
	retparms = { next:"mitch65", donne:donne };
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
$(document).on( "click", "td.seletui", function(event) {
	var id = $(this).parent().attr("id");
	const figs = id.split('_');
	console.log( "Etui ", figs[1] );
	if ( donne != figs[1] ) {
		// rechargement donne sélectionnée
		retparms = { next:"mitch64", etui:figs[1] };
		passAndroid( retparms );
	}
});

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
	$numtable = $paire;
	
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
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
		if ( $ligne == "NS" ) {
			// récupération des dernières valeurs jouées en cas de reprise
			$res	= getParmTable( $paire );
			$cpt	= $res['cpt'];		// compteur de donnes déjà enregistrées
			$position = intval( $cpt / $paquet );
			
			// numéro des équipes
			$numNS = $paire;
			$numEO = $paire - $position; 
			
			// test saut
			if ( ( $position >= $saut )&&( $saut > 0) ) 	$numEO --;
			if ( $numEO < 1 ) $numEO = $numEO + max( $pairesNS, $pairesEO );
			
			// numéro équipe adverse avant incrémentation compteur de donnes jouées
			$oldNS  = $paire;
			$oldcpt = $cpt - 1;
			$oldposition = intval( $oldcpt / $paquet );
			$oldEO = $oldNS - $oldposition; 	// pour l'affichage de la bonne paire Est Ouest
			// test saut
			if ( ( $oldposition >= $saut ) and ( $saut > 0) ) 	$oldEO --;
			if ( $oldEO < 1 ) $oldEO = $oldEO + max( $pairesNS, $pairesEO );
		}
		else {
			// récupération des dernières valeurs jouées en cas de reprise
			$res = getParmTableEO( $paire );
			$cpt 	= $res['cpt'];		// donnes jouées
			$position = intval( $cpt / $paquet );
				
			// numéro des équipes
			$numEO = $paire; 
			$numNS = $paire + $position;
			
			// test changement de position pour jouer la donne suivante
			//if ( $cpt%$paquet == 0 ) $numNS++;
			if ( ( $position >= $saut )&&( $saut > 0 ) ) $numNS++;
			if ( $numNS > max( $pairesNS, $pairesEO ) ) $numNS = 1;
			
			// numéro équipe adverse avant incrémentation compteur de donnes jouées
			$oldEO  = $paire;
			$oldcpt = $cpt - 1;
			$oldposition = intval( $oldcpt / $paquet );
			$oldNS = $oldEO - $oldposition; 	// pour l'affichage de la bonne paire Est Ouest
			// test saut
			if ( ( $oldposition >= $saut ) and ( $saut > 0) ) 	$oldNS --;
			if ( $oldNS < 1 ) $oldNS = $oldNS + max( $pairesNS, $pairesEO );
		}

		$ligneNS = getligneNS( $idtournoi, $oldNS );
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
		print htmlResultatDonne($idtournoi, $donne, $oldNS, "points" );
		
		//Entrée diagramme
		$diagramme = existeDiagramme( $idtournoi, $donne );
		if ( $diagramme == null ) {
			//print '<p>&nbsp;</p>';
			print '<p><button class="myButton" onclick="goto65()">Entrez les diagrammes</button></p>';
			print "<p><em>Si vous n'avez pas le temps de les entrer</br>laissez aux suivants !</em></p>";
		}
		else {
			print_section_diagramme();
		}

		$njouees = $t[ 'njouees' ];
		$changement = $cpt % $paquet;
		if ( $cpt == 1) print "<p>1ère donne jouée sur $njouees</p>";
		else print "<p>$cpt donnes jouées sur $njouees</p>";
		if ( $cpt < $njouees ) {
			if ( $changement == 0 and $cpt > 0 ) {
				//print htmlResultatPaquet($idtournoi, $numNS, $oldEO );
				print htmlResultatPaquet($idtournoi, $numtable, $oldEO );
				
				print "<h3>Attention</br>les Est-Ouest changent de position";
				if ( ( $position == $saut ) and ( $saut > 0) ) {
					print "</br>en sautant une table</h3>";
				}
				else print " !</h3>";
				print "<p><button class='myStartButton' onclick='goto62()'>Passez à la</br>position suivante</button></p>";
				//
				// ajout le 12/07/2024
				$_SESSION['withback'] = $numtable;	// retour possible
				//
				if ( $teston == 1 ) print "TEST remplissage auto TEST<script>setTimeout( goto62, 1000 );</script>";
			}
			else {
				print "<p><button class='myStartButton' onclick='goto63()'>Passez à la</br>donne suivante</button></p>";
				if ( $teston > 0 ) print "TEST remplissage auto TEST<script>setTimeout( goto63, 1000 );</script>";
			}
		}
		else {
			print htmlResultatPaquet($idtournoi, $numNS, $oldEO );
			
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
	if ( $config['site'] == 1 ) {
		print "<p>numtable: $numtable, oldEO: $oldEO</p>";
	}
	?>
	
	<p style="text-align: center">&nbsp;</p>

	<script type="text/javascript">
	var idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	var cpt	   = parseInt( "<?php echo $cpt; ?>" );
	var numNS  = parseInt( "<?php echo $oldNS; ?>" );
	var numEO  = parseInt( "<?php echo $oldEO; ?>" );
	var vulns  = 0;
	var vuleo  = 0;
	
	var diagramme = String( "<?php echo $diagramme; ?>" );
	console.log( diagramme );
	if ( displaydeal( diagramme ) == true ) $("#section_diagramme").removeClass( "section_invisible");
	</script>
	</div>
</body>
</html>