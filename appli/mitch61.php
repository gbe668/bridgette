<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib43.php");
require("lib61.php");

$resultIdent = getIdent();
if ( $resultIdent['status'] == $ID_INCORRECT ) {
	header("Location: loguserin.php");
}
else $userid = $resultIdent['userid'];

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$relais = 0;

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isCheckedJoueur($idtournoi) ) {
	header("Location: checkjoueur.php");
}

?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" media="screen"/>
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var relais;
var numtable;

// constantes définies dans bridge_bdd.php
const cnx_indefini = "0";
const cnx_ko = "1";	// attente connexion smartphone
const cnx_ok = "2";	// smartphone connecté
const cnx_fin = "3";	// tournoi terminé, smartphone s'est déconnecté

function selNumTable( place ) {
	const figs = place.split('_');
	numtable = figs[1];
	console.log( "Table:", numtable );
	
	var nextstring = "mitch62.php?idtournoi=" + idtournoi + "&table=" + numtable;
	location.replace( nextstring );
};
function goto64diags() {
	var nextstring = "mitch64diags.php?idtournoi=" + idtournoi + '&table=' + relais + '&donne=0';
	location.replace( nextstring );
};

$(document).ready( function() {
	// saisie numéro de table
	$("tr.xtrsel").click(function(event) {
		console.log( "tr", event.target.id );
		selNumTable( event.target.id );
	});
});

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto67() {
	var nextstring = "bridge67.php?idtournoi=" + idtournoi+ "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function erasecode() {
	var nextstring = "erasecode.php";
	location.replace( nextstring );
}
function toggleAffichagePaires() {
	if ( $("#section_tableaux").hasClass( 'section_invisible' ) )
		$('#section_tableaux').removeClass( 'section_invisible' );
	else
		$('#section_tableaux').addClass( 'section_invisible' );
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
	// voir code php en entête de ce fichier
	if ( $idtournoi == null ) {
		print "<p>Le tournoi n'existe pas ou plus !</p>";
		$relais = 0;
	}
	else {
		$t = readTournoi( $idtournoi );
		$etat  = $t['etat'];
		$datef = $t['datef'];
		if ( $etat != $st_phase_jeu ) {
			print "<p>Le tournoi n'est pas démarré !</p>";
		}
		else {
			$pairesNS = $t['pairesNS'];
			$pairesEO = $t['pairesEO'];
			$relais   = $t['relais'];
			$gueridon = $t['gueridon'];
			$paquet   =	$t['paquet'];
			$genre	  = $t['genre'];
			$idtype   = $t['idtype'];
			$desc = getdescriptiontournoi($idtype);
			
			// Relais NS
			$relaisNS = 0;
			$relaisEO = 0;
			if ( $relais > 0 ) {
				if ( $pairesNS > $pairesEO ) $relaisNS = $relais;
				else $relaisEO = $relais;
			}

			print "<h2>Tournoi du $datef</h2>";
			print "<p>$desc, $paquet étuis par table</p>";

			$okcheck = true;
			if ( $parametres['checkuser'] > 0 ) {
				$joueur = getJoueur( $userid );
				print "<p>Hello ".$joueur['nomcomplet']."</p>";

				$user = recherche_joueur( $idtournoi, $userid );
				$paire = $user['table'];		// position initiale
				if ( $paire == 0 ) {
					// joueur non trouvé
					print "<h3>Vous ne faites pas partie des joueurs inscrits !</h3>";
					if ( $parametres['checkuser'] == 2 ) {
						$okcheck = false;
					}
				}
			}
			
			if ( $okcheck ) {
				print '<h2>Joueur en sud: Identifiez vous</br>en cliquant sur votre paire.</h2>';
				print htmlTablesdisponibles( $idtournoi, $pairesNS );

				if ( ($relaisEO > 0)&&($gueridon == 0) ) {
					$notour = getMinTour( $relais-1 );
					if ( $notour==1 ) {		// compteur de tours
						print "<p>Dans la première position, la paire<br/><b>Est Ouest en relais</b> prépare les donnes<br/>et peut entrer les diagrammes.</p>";
						print "<p><button class='myButton' onclick='goto64diags()'>Paire EO $relais en relais</button></p>";
					}
				}
				print '<p id="tableok">Attente sélection ...</p>';
			}
			else {
				print "<p>&nbsp;</p>";
			}
			
			print '<div id="section_tableaux" class="section_invisible">';
			print '<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>';
			displayPaires($idtournoi, $genre, $pairesNS, $pairesEO, 100);
			print '</div>';
			print '<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>';
			if ( $parametres['affprov'] > 0 ) {
				print '<p><button class="mySmallButton" onclick="goto67()">Résultats provisoires ...</button></p><p>&nbsp;</p>';
			}
		}
	}
	?>
	
	<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	
	
	<?php
	if ( $parametres['checkin'] > 0 ) {
		print "<p><button onclick='erasecode()'>Effacer le code d'accès</button></p>";
	}
	?>
	<script>
	relais = parseInt( "<?php echo $relais; ?>" );
	</script>
	</div>
</body>
</html>