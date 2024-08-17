<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib61.php");

$resultIdent = getIdent();
if ( $resultIdent['status'] == $ID_INCORRECT ) {
	header("Location: loguserin.php");
}
else $userid = $resultIdent['userid'];

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );

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
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
var st_phase_jeu = parseInt( "<?php echo $st_phase_jeu; ?>" );
// constantes définies dans bridge_bdd.php
const cnx_indefini = "0";
const cnx_ko = "1";	// attente connexion smartphone
const cnx_ok = "2";	// smartphone connecté
const cnx_fin = "3";	// tournoi terminé, smartphone s'est déconnecté

var idtournoi;
var numpaire;
var p_numpaire;
var p_numpaire;

function selNumPaire( place ) {
	const figs = place.split('_');
	numpaire = figs[1];
	p_numpaire = "#np_" + numpaire;
	console.log( "Paire:", numpaire );

	var nextstring = "howell62.php?idtournoi=" + idtournoi + "&paire=" + numpaire;
	location.replace( nextstring );
};

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
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) ) {
		$.get( "getpositionsjoueurs.php", {idtournoi:idtournoi}, function( json ) {
			if ( json.etat == st_phase_jeu ) {
				$("#afficheplus").removeClass( "section_invisible" );
				$("#realtour").html( json.positions );
			}
		},"json");
	}
	else
		$("#afficheplus").addClass( "section_invisible" );
}
function cdemoins() {
	$("#afficheplus").addClass( "section_invisible" );
}

$(document).ready( function() {
	// saisie numéro de table
	$("tr.xtrsel").click(function(event) {
		console.log( "tr", event.target.id );
		selNumPaire( event.target.id );
	});
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
	// voir code php en entête de ce fichier
	if ( $idtournoi == null ) {
		print "<p>Le tournoi n'existe pas ou plus !</p>";
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
			$paquet   =	$t['paquet'];
			$idtype   = $t['idtype'];
			$desc = getdescriptiontournoi($idtype);
		
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
			};
			
			if ( $okcheck ) {
				print '<h2>Identifiez vous</br>en cliquant sur votre paire.</h2>';
				print htmlTablesdisponibles( $idtournoi, $pairesNS );
				print '<p id="tableok">Attente sélection ...</p>';
			}
			else {
				print "<p>&nbsp;</p>";
			}
			print "<p><button onclick='cdeplus()'>Affiche/masque positions</button></p>";
			print "<div id='afficheplus' class='section_invisible'>";
			print "<div style='text-align:center; margin:auto; max-width:350px;' id='realtour'>&nbsp;</div>";
			print "<p><button onclick='cdemoins()'>Masque positions</button></p>";
			print "</div>";
			
			if ( $parametres['affprov'] > 0 ) {
				print '<p><button class="mySmallButton" onclick="goto67()">Résultats provisoires ...</button></p><p>&nbsp;</p>';
			}
		}
	}
	print "<p><button class='mySmallButton' onclick='gotoindex()'>Retour page d'accueil</button></p>";
	if ( $parametres['checkin'] > 0 ) {
		print "<p><button onclick='erasecode()'>Effacer le code d'accès</button></p>";
	}
	?>
	</div>
</body>
</html>