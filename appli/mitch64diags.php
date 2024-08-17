<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function goto64diags() {
	var nextstring = "mitch64diags.php?idtournoi=" + idtournoi;
	nextstring += '&table=' + numtable + '&donne=' + donne;
	location.replace( nextstring );
};
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function clickValidiags() {
	$("#section_inputdiags").addClass( "section_invisible" );
	$("#section_validiags").addClass( "section_invisible" );
	$("#tstvalidok").removeClass( "section_invisible" );
	
	// Enregistrement du diagramme
	$.get( "f65setdiagramme.php", { idtournoi:idtournoi, donne:donne, diagramme:dealfield },
	function(strjson) {
		$('#validok').html( strjson.display );
		//goto64();
	},"json");	
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
	$idtournoi	= htmlspecialchars( $_GET['idtournoi'] );
	$numtable	= htmlspecialchars( $_GET['table'] );
	$donne		= htmlspecialchars( $_GET['donne'] );
	
	$t = readTournoi( $idtournoi );
	$etat = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		$paquet	= $t['paquet'];
		
		// récupération des dernières valeurs jouées en cas de reprise
		$res = getParmTable( $numtable );
		$firstdonne = $res['numdonne'];
		if ( $donne == 0 ) {
			$donne = $firstdonne;
		}
		
		$ligneEO	= getligneEO( $idtournoi, $numtable );
		$nameEst	= $ligneEO['E']['nomcomplet'];
		$nameOuest	= $ligneEO['O']['nomcomplet'];
		
		print "<h2><span class='numpaire'>Table n°$numtable</span></h2>";
		print "<p>Est Ouest: $nameEst et $nameOuest</p>";
		print "<h2>Première position, vous êtes en relais.</h2>";
		print "<p>Vous devez préparer les donnes<br/>et entrer les diagrammes<br/>pour la position suivante.</p>";

		print "<h3>Récupérez les étuis:</br>";
		$donne ++;
		print liste_etuis( $donne, $paquet );
		print "</h3>";
		
		//Entrée diagramme
		$diagramme = existeDiagramme( $idtournoi, $donne );
		if ( $diagramme == null ) {
			print "<h2>Diagrammes donne n°$donne</h2>";
			print_section_diagramme();
			print '<div id="section_inputdiags">';
			print '<p id="msg">&nbsp;</p>';
			print_clavier_diagramme();
			?>
			
			<p id="dealfield" hidden>&nbsp;</p>
			</div>

			<div id="section_validiags" class="section_invisible">
			<p><button class="myStartButton" id="valid1" onClick="clickValidiags()">Enregistrez</br>les diagrammes</button></p>
			</div>

			<p id="validok">Attente fin d'entrée des diagrammes</p>
			<?php
		}
		else print "<p>Diagrammes donne $donne déjà enregistrés.</p>";
		$cpt = $donne - $firstdonne;
		print "<p>&nbsp;</p>";
		if ( $cpt < $paquet ) {
			print '<p><button class="myButton" onclick="goto64diags()">Préparez la donne suivante</button></p>';
		}
		else {
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
	var numtable  = parseInt( "<?php echo $numtable; ?>" );
	var donne  = parseInt( "<?php echo $donne; ?>" );
	$("#section_diagramme").removeClass( "section_invisible");
	initcanselect();
	setfocus( 1 );
	elmnt = document.getElementById("section_diagramme");
	elmnt.scrollIntoView();
	</script>
	</div>
</body>
</html>