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
	<script src="js/bridge48.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto44() {		// clôture du tournoi
	var nextstring = "mitch44.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
function reload() {
	var nextstring = "bridge48.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<?php
require("lib63.php");
require("lib45.php");
?>
 
<body>
	<div style="text-align: center">
	<h2>Entrée des résultats d'une donne</br>en utilisant la feuille de suivi de l'étui</h2>

	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
	?>
	
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	if ( isNaN( screenw ) ) reload();
	</script>
		
	<?php
	$t = readTournoi( $idtournoi );
	$pairesNS = $t[ 'pairesNS' ];
	$pairesEO = $t[ 'pairesEO' ];
	$njouees = $t[ 'njouees' ];
	$idtype	= $t[ 'idtype' ];
	$genre	= $t[ 'genre' ];

	$ntables 	= $t[ 'ntables' ];
	$ndonnes	= $t[ 'ndonnes' ];
	$paquet		= $t[ 'paquet' ];
	$saut		= $t[ 'saut' ];
	$relais		= $t[ 'relais' ];
	$gueridon	= $t[ 'gueridon' ];
	
	$maxlignes = min( $pairesNS, $pairesEO );	// nombre de lignes de la feuille de suivi
	//if ( $saut > 0 ) $maxlignes --;	
	?>
	<script type="text/javascript"> 
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	pairesNS  = parseInt( "<?php echo $pairesNS; ?>" );
	pairesEO  = parseInt( "<?php echo $pairesEO; ?>" );
	njouees	  = parseInt( "<?php echo $njouees; ?>" );
	genre	  = parseInt( "<?php echo $genre; ?>" );
	
	ntables  = parseInt( "<?php echo $ntables; ?>" );
	ndonnes  = parseInt( "<?php echo $ndonnes; ?>" );
	paquet   = parseInt( "<?php echo $paquet; ?>" );
	saut   	 = parseInt( "<?php echo $saut; ?>" );
	relais   = parseInt( "<?php echo $relais; ?>" );
	gueridon = parseInt( "<?php echo $gueridon; ?>" );
	
	maxlignes = parseInt( "<?php echo $maxlignes; ?>" );
	</script>
	
	<p>Naviguez entre les différentes donnes</br>en cliquant sur les chiffres pour avancer</br>ou reculer d'une unité ou de 5 unités</p>
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="tabmm5">-5</div></td>
	<td class='xNum2'><div id="tabmm1">-1</div></td>
	<td class='xNum2'><div id="tabmp1">+1</div></td>
	<td class='xNum2'><div id="tabmp5">+5</div></td>
	</tr><tbody></table>
	<h3>Résultats donne n°<span id='donne'>1</span>&nbsp;<span id="msgerr1"></span></h3>
	<p id="msgdonne" >&nbsp;</p>
	<?php
	print "<p>PairesNS: $pairesNS, pairesEO: $pairesEO, ndonnes: $ndonnes, njouées: $njouees, paquet: $paquet</p>";
	print "<p>Cliquez sur <img src='images/ok.png' height='20' /> pour enregistrer le résultat<br/>Sur ordinateur, utilisez la touche ENTER</p>";
	displayFeuilleSuiviDonne( $maxlignes, ($screenw > $parametres['maxw2']) ? 1 : 0 );
	?>
	<p><button id="nextdonne" class="myButton" onClick="nav_mdonne(1)">Donne suivante</button></p>
	<p id="msgerr" >&nbsp;</p>
	
	<p><button class="mySmallButton" onclick="goto44()">Retour page de clôture</button></p>
	<script>
	nav_mdonne( 0 );
	</script>
	</div>
	
</body>
</html>