<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib43.php");

$file_version = "version.txt";
if ( file_exists( $file_version ) ) {
	$version = file_get_contents( $file_version );
}
else $version = "inconnue";

$apk_code_request = 8;	// numéro dernière version générée le 14 août 2024

$_SESSION['apk_code'] = htmlspecialchars( $_GET['apk_code'] );
$apk_name = htmlspecialchars( $_GET['apk_name'] );
$userid = htmlspecialchars( $_GET['userid'] );
$joueur = getJoueur( $userid );

$idtournoi = existeTournoiNonClos();
$paire = 0;
$ligne = "NS";

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
	<title>Apk Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
</head>

<script>
var token = "<?php echo $token; ?>";
	
var idtournoi;
var etat, genre;
var t_unknown = 0;
var t_mitchell = 1;
var t_howell = 2;
var st_phase_jeu = "<?php echo $st_phase_jeu; ?>";

function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function goto20() {
	retparms = { next:"bridge20" };
	passAndroid( retparms );
};
function goto25() {
	retparms = { next:"bridge25" };
	passAndroid( retparms );
};
function goto59() {
	retparms = { next:"bridge59" };
	passAndroid( retparms );
};
function goto62m() {
	retparms = { next:"mitch62", idtournoi:idtournoi, paire:paire, ligne:ligne };
	passAndroid( retparms );
}
function goto62h() {
	retparms = { next:"howell62", idtournoi:idtournoi, paire:paire, ligne:ligne };
	passAndroid( retparms );
};
function goto66() {
	retparms = { next:"bridge66", idtournoi:idtournoi, paire:paire, ligne:ligne };
	passAndroid( retparms );
};
function toggleAffichagePaires() {
	if ( $("#section_tableaux").hasClass( 'section_invisible' ) )
		$('#section_tableaux').removeClass( 'section_invisible' );
	else
		$('#section_tableaux').addClass( 'section_invisible' );
}
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) ) {
		$.get( "<?php echo $relpgm.'getpositionsjoueurs.php' ?>", {idtournoi:idtournoi}, function( json ) {
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

function passAndroid( parms ) {
	strjson = JSON.stringify(parms);
	console.log( "strjson", strjson );
	Android.processNext( strjson );
};
function showAndroidToast(toast) {
	Android.showToast(toast);
}
function pulltournoi() {
	$.get( '/jsonexistetournoinonclos.php', {idtournoi:idtournoi, token:token}, function( strjson ) {
		if ( strjson.id > 0 ) {
			if ( etat != strjson.etat ) {
				gotoindex();
			}
			else {
				// pas de changement
				setTimeout(function() { pulltournoi(); }, 3000);
			}
		}
		else {
			setTimeout(function() { pulltournoi(); }, 5000);
		}
	},"json");
}

function gotodownloadapk() {
	var urlDuFichier = "<?php echo $base_url.'/app-release.apk' ?>";
	window.open(urlDuFichier,"_blank", null);
}
</script>

<body>
	<div style="text-align: center">
	<p><img src="<?php echo $relimg.'bridgette.png'; ?>" alt="bridge" style="max-width:340px;" /></p>
	
	<?php
	if ( $_SESSION['apk_code'] < $apk_code_request ) {
		print "<p><button onclick='gotodownloadapk()'>Mettre à jour l'application android</button></p>";
	}
	print "<p>Hello ".$joueur['nomcomplet']."</p>";
	// voir code php en entête de ce fichier
	if ( $idtournoi > 0 ) {
		$t = readTournoi( $idtournoi );
		$etat   = $t['etat'];
		
		if ( $etat == $st_phase_init ) {
			// définition des paires en cours
			print '<h2>Tableau des participants</br>en cours de définition.</h2>';
			print '<p id="imwaiting">Attendez le démarrage du tournoi.</p>';
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
			
			// Recherche du joueur dans le tableau des participants
			$user = recherche_joueur( $idtournoi, $userid );
			$paire = $user['table'];		// position initiale
			if ( $paire > 0 ) {
				if ( ($user['place']==$pos_Nord)||($user['place']==$pos_Sud) ) $ligne = "NS";
				else $ligne = "EO";
				
				//print '<p id="imwaiting">&nbsp;</p>';
				if ( $genre == $t_mitchell )
					print "<button class='myBigButton' onclick='goto62m()'>Rejoindre le tournoi</button>";
				else
					print "<button class='myBigButton' onclick='goto62h()'>Rejoindre le tournoi</button>";
			}
			else {
				// joueur non trouvé
					print "<h3>Vous ne faites pas partie des joueurs inscrits!</h3>";
			};
			
			print "<p><button onclick='cdeplus()'>Affiche/masque positions</button></p>";
			
			print "<div id='afficheplus' class='section_invisible'>";
			print "<div style='text-align:center; margin:auto; max-width:350px;' id='realtour'>&nbsp;</div>";
			if ( $genre == $t_howell ) {
				$npos = $t['npositions'];
				print htmlPositionsHowell($idtype, $pns, $npos, $paquet);
			}
			print "<p><button onclick='cdemoins()'>Masque positions</button></p>";
			print "</div>";
		};
		
		if ( $etat == $st_phase_fini ) {
			print '<h2>Tournoi en phase de clôture.</h2>';
			print "<button class='mySmallButton' onclick='goto66()'>Affichage résultats provisoires</button>";
		};
	}
	else {
		$etat = $st_notfound;
		print '<h2>Pas de tournoi en cours</br>ou en préparation.</h2>';
	};
	
	?>
	<script type="text/javascript">
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	etat  = parseInt( "<?php echo $etat; ?>" );
	paire = "<?php echo $paire; ?>";
	ligne = "<?php echo $ligne; ?>";

	//setTimeout(function() { pulltournoi(); }, 5000);
	pulltournoi();
	</script>
	
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto20()">Affichage derniers résultats</button></p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto25()">Recherche partenaire</button></p>
	<p><button class="mySmallButton" onclick="goto59()">Calcul de la marque</button></p>
	<!--
	<p><button class="mySmallButton" onclick="gotoindex()">Actualiser l'affichage</button></p>
	-->

	<p>version <?php echo $version; ?> - APK: <?php echo $apk_name; ?></p>
	<p>Le retour à cette page d'accueil est réalisé en cliquant sur la flèche verte en haut à droite de l'écran.</p>
	
	<?php
	if ( $config['site'] == 1 ) {
		print "<p>base: $base_url</p>";
		$apk_code = $_SESSION['apk_code'];
		print "<p>apk_code: $apk_code</p>";
	}
	?>
	</div>
</body>
</html>