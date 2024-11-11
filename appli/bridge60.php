<?php
require("configuration.php");
require("bridgette_bdd.php");

$resultIdent = getIdent();
if ( $resultIdent['status'] == $ID_INCORRECT ) {
	header("Location: loguserin.php");
}
else $userid = $resultIdent['userid'];

$idtournoi = existeTournoiNonClos(); // where etat = '$st_phase_init' or etat = '$st_phase_jeu'
// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isCheckedJoueur($idtournoi) ) {
	header("Location: checkjoueur.php");
}

function htmlPositionsHowell($idtype, $pns, $npos, $paquet) {
	$tab = "<h3>Howell $pns paires</h3>";
	for ( $i = 1; $i <= $npos; $i++ ) {
		$tab .= "<p>".htmlPositionHowell($idtype, $pns, $i, $paquet)."</p>";
	}
	return $tab;
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
var idtournoi, etat, genre;
var st_phase_init = parseInt( "<?php echo $st_phase_init; ?>" );
var st_phase_jeu  = parseInt( "<?php echo $st_phase_jeu; ?>" );
var t_unknown = 0;
var t_mitchell = 1;
var t_howell = 2;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function loguserout() {
	var nextstring = "loguserout.php";
	location.replace( nextstring );
};
function goto20() {
	var nextstring = "bridge20.php";
	location.replace( nextstring );
};
function goto25() {
	var nextstring = "bridge25.php";
	location.replace( nextstring );
};
function goto59() {
	var nextstring = "bridge59.php";
	location.replace( nextstring );
};
function goto60() {
	var nextstring = "bridge60.php";
	location.replace( nextstring );
};
function goto61m() {
	var nextstring = "mitch61.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
}
function goto61h() {
	var nextstring = "howell61.php?idtournoi=" + idtournoi;
	location.replace( nextstring );
};
function goto66() {
	var nextstring = "bridge66.php?idtournoi=" + idtournoi+ "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function waitPhaseJeu() {
	$.get( "getetattournoi.php", {idtournoi:idtournoi}, function( strjson ) {
		etat = strjson.etat;
		if ( etat == st_phase_init ) {
			setTimeout(function() { waitPhaseJeu(); }, 3000);
		}
		if ( etat == st_phase_jeu ) {
			$("#imwaiting").text( "c'est parti !" );
			goto60();
		}
	},"json");
};
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) ) {
		$.get( "getpositionsjoueurs.php", {idtournoi:idtournoi, w:window.innerWidth }, function( json ) {
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

var maxaffprov = 10;
var affprovTO;
function decompte( n ) {
	if ( n > 0 ) {
		$(nsec).text(n);
		affprovTO = setTimeout(function() { decompte( n-1 ); }, 1000);
	}
	else $(idposprov).hide();
}
function affposprov() {
	if ( $(idposprov).is(":hidden") ) {
		$.get( "getpositionsprovisoires.php", {idtournoi:idtournoi, w:0 }, function( json ) {
			if ( json.etat == st_phase_init ) {
				$(posprov).html( json.positions );
				decompte( maxaffprov );
				$(idposprov).show();
			}
		},"json");
	}
	else {
		$(idposprov).hide();
		clearTimeout( affprovTO );
	}
}
</script>

<body>
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	
	<?php
	if ( $parametres['checkuser'] > 0 ) {
		$joueur = getJoueur( $userid );
		print "<p>Hello ".$joueur['nomcomplet']."</p>";
	}
	// voir code php en entête de ce fichier
	if ( $idtournoi > 0 ) {
		$t = readTournoi( $idtournoi );
		$etat = $t[ 'etat' ];
		if ( $etat == $st_phase_init ) {
			// définition des paires en cours
			print '<h2>Tableau des participants</br>en cours de définition.</h2>';
			print '<p id="imwaiting">Attendez le démarrage du tournoi.</p>';

			print "<p><button class='myBigButton' onclick='affposprov()'>Affiche / masque</br>les positions provisoires</button></p>";
			print "<div id='idposprov' hidden>";
			print "<div style='text-align:center; margin:auto; max-width:350px;' id='posprov'>&nbsp;</div>";
			print "fermeture automatique dans <span id='nsec'>10</span> s";
			print "</div>";
			?>
			<script type="text/javascript"> 
			idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
			waitPhaseJeu();
			</script>
			<?php
		};
		
		if ( $etat == $st_phase_jeu ) {
			print "<h2>Tournoi en cours !</h2>";
			$idtype = $t['idtype'];
			$genre  = $t['genre'];
			$paquet	= $t['paquet'];
			$pns	= $t['pairesNS'];
			$peo	= $t['pairesEO'];
			$desc = getdescriptiontournoi($idtype);
			print "<p>$desc, $paquet étuis par table</p>";
			
			if ( $genre == $t_mitchell )
				print "<button class='myBigButton' onclick='goto61m()'>Rejoindre le tournoi</button>";
			else {
				print "<button class='myBigButton' onclick='goto61h()'>Rejoindre le tournoi</button>";
				
			}
			print "<p><button onclick='cdeplus()'>Affiche / masque positions</button></p>";
			
			print "<div id='afficheplus' class='section_invisible'>";
			print "<div style='text-align:center; margin:auto; max-width:350px;' id='realtour'>&nbsp;</div>";
			if ( $genre == $t_howell ) {
				$npos = $t['npositions'];
				print htmlPositionsHowell($idtype, $pns, $npos, $paquet);
			}
			print "<p><button onclick='cdemoins()'>Masque positions</button></p>";
			print "</div>";
			?>
			<script type="text/javascript">
			idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
			genre = parseInt( "<?php echo $genre; ?>" );
			</script>
			<?php
		};
		
		if ( $etat == $st_phase_fini ) {
			print '<h2>Tournoi en phase de clôture.</h2>';
			print "<button class='mySmallButton' onclick='goto66()'>Affichage résultats provisoires</button>";
			?>
			<script type="text/javascript"> 
			idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
			</script>
			<?php
		};
	}
	else {
		print '<h2>Pas de tournoi en cours</br>ou en préparation.</h2>';
	};
	
	?>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto20()">Affichage derniers résultats</button></p>
	<p><button class="mySmallButton" onclick="goto25()">Annuaire / Recherche partenaire</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto59()">Calcul de la marque</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	
	<p>&nbsp;</p>
	<?php
	if ( $parametres['checkuser'] > 0 ) {
		print "<p><button class='mButton' onclick='loguserout()'>Se déconnecter</button></p>";
	}
	?>
	</div>
</body>
</html>