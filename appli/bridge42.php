<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
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
// id tournoi
var idtournoi, genre;
var t_mitchell = 1;
var t_howell = 2;

var ntables, npositions;
var maxpaquet, paquet;
var dureedonne, dureediagrammes, dureeinitiale;		// exprimées en secondes
var finseq;

function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function goto41() {
	var nextstring = "bridge41.php?idtournoi="+idtournoi + "&w="+window.innerWidth;
	location.replace( nextstring );
};
function goto43() {
	var nextpage = "bridge43.php?idtournoi=" + idtournoi + "&w="+window.innerWidth;
	if ( genre == t_howell ) {
		//var nextpage = "howell43.php?idtournoi=" + idtournoi + "&w="+window.innerWidth;
		$.get( "starthowell.php", {idtournoi:idtournoi, paquet:paquet}, function() {
			$("#msgerr").text( "Enregistrement terminé." );
			location.replace( nextpage );
		});
	}
	else {
		// tournoi Mitchell
		//var nextpage = "mitch43.php?idtournoi=" + idtournoi + "&w="+window.innerWidth;
		$.get( "startmitchell.php", {idtournoi:idtournoi, paquet:paquet}, function() {
			$("#msgerr").text( "Enregistrement terminé." );
			location.replace( nextpage );
		});
	}
};
function changepaquet( valeur ) {
	let newpaquet = paquet + valeur;
	if ( (newpaquet > maxpaquet ) || (newpaquet < 1 ) ) {
		$("#msgerr").text( "Non autorisé ..." );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1500);
	}
	else {
		paquet = newpaquet;
		let njouees = npositions * paquet;
		let ndonnes = ntables * paquet;
		if ( genre == t_howell ) ndonnes = njouees;
		
		// recalcul heure de fin
		firstduree	= paquet * (dureedonne + dureediagrammes) + dureeinitiale;	// en secondes
		nextduree	= paquet * dureedonne;	// durée exprimée en secondes
		duree = firstduree + nextduree * (npositions-1);
		//$("#msgerr").text( "firstduree: " + firstduree + " nextduree: " + nextduree + " npositions: " + npositions);
		
		var endt = new Date( Date.now() + duree * 1000 );
		strfintournoi = endt.toLocaleTimeString();
		strfintournoi = strfintournoi.substring(0,5) + " minutes";

		$("#paquet").text(paquet);
		$("#ndonnes").text(ndonnes);	// test
		$("#njouees").text(njouees);
		$("#fintournoi").text(strfintournoi);
	}
};
$(document).ready(function() {
	$("#signemoins").bind('click', function( event ){ changepaquet( -1 ); });
	$("#signeplus").bind('click', function( event ){ changepaquet( 1 ); });
});
</script>

<body>
	<div style="text-align:center; max-width:350px; margin:auto;">
	
	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
	
	$t = readTournoi( $idtournoi );
	$datef = $t['datef'];

	$pns = $t['pairesNS'];
	$peo = $t['pairesEO'];
	$idtype = $t['idtype'];
	
	$ntables =	$t['ntables'];
	$ndonnes =	$t['ndonnes'];
	$npositions = $t['npositions'];
	$njouees =	$t['njouees'];
	$paquet =	$t['paquet'];
	$gueridon = $t['gueridon'];
	$saut  =	$t['saut'];
	$genre =	$t['genre'];

	$tt = gettypetournoi( $idtype );
	$maxpaquet =  $tt['paquet']+1;
	$desc = getdescriptiontournoi($idtype);
	
	//print "<h2>Tournoi $st_typetournoi[$genre] $idtype du $datef</h2>";
	print "<h2>Tournoi du $datef</h2>";
	print "<p>Vérifiez les caractéristiques du tournoi:</p>";
	print "<p style='color:red;font-size: 1.2em;'>$desc</p>";
	//if ( $genre == $t_howell ) {
	//	// tournoi type Howell
	//	$textetables = "$pns paires et $npositions positions";
	//	print $textetables;
	//}
	//else {
	//	// tournoi type Mitchell
	//	if ( $pns == $peo )
	//		print "<h2 style='color:red'>$ntables tables complètes</h2>";
	//	else
	//		print "<h2 style='color:red'>$ntables tables incomplètes</h2><h3>$pns paires Nord-Sud, $peo paires Est-Ouest</h3>";
	//	if ( $gueridon > 0 ) {
	//		$pos1 = $ntables / 2;
	//		$pos2 = $pos1 + 1;
	//		print "<p>Guéridon entre table $pos1 et table $pos2</p>";
	//	}
	//}
	
	$firstduree = ( $paquet * ($parametres['dureedonne'] + $parametres['dureediagrammes']) + $parametres['dureeinitiale'] ) * 60;	// en secondes
	$nextduree = $paquet * $parametres['dureedonne'] * 60;	// durée exprimée en secondes
	$duree = $firstduree + $nextduree * ($npositions-1);
	?>
	
	<p style="text-align: center"><button class="myButton" onclick="goto41()">Si incorrect</br>retour définition des paires</button></p>
	
	<p><b>Fin tournoi prévue à <span id="fintournoi">fintournoi</span></b></p>
	<p><b><span id="ndonnes"><?php echo $ndonnes ?></span></b> donnes en circulation</br><b><span id="njouees"><?php echo $njouees ?></span></b> donnes jouées par table</p>
	<p>Avant le démarrage du tournoi,</br>vous pouvez raccourcir la durée du tournoi</br>en diminuant le nombre d'étuis par tables</p>
	<p>Etuis par table:&nbsp;<span class='xNum2' id="signemoins"><img src="images/signe-moins.png" height="20"/></span>
	<span id='paquet' class="xDigit" style="padding-left:20px; padding-right:20px;"><?php echo $paquet ?></span>
	<span class='xNum2' id="signeplus"><img src="images/signe-plus.png" height="20"/></span></p>
	<p id="msgerr">&nbsp;</p>
	
 	<p style="text-align: center"><button class="myStartButton" onclick="goto43()">Démarrage tournoi !</button></p>
	<p>Après le démarrage, si le tournoi s'éternise</br>vous pourrez supprimer les dernières positions</p>

	<p><button class="mySmallButton" onclick="goto40()">Retour page direction de tournoi</button></p>
	
	<script>
	idtournoi	= parseInt( "<?php echo $idtournoi; ?>" );
	ntables		= parseInt( "<?php echo $ntables; ?>" );
	maxpaquet	= parseInt( "<?php echo $maxpaquet; ?>" );
	paquet		= parseInt( "<?php echo $paquet; ?>" );
	npositions  = parseInt( "<?php echo $npositions; ?>" );
	genre		= "<?php echo $genre; ?>";
	
	dureedonne		= parseInt( "<?php echo 60*$parametres['dureedonne']; ?>" );
	dureeinitiale	= parseInt( "<?php echo 60*$parametres['dureeinitiale']; ?>" );
	dureediagrammes	= parseInt( "<?php echo 60*$parametres['dureediagrammes']; ?>" );
	changepaquet( 0 );
	</script>
	</div>
</body>
</html>