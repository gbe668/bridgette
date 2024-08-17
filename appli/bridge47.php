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
	<script src="js/bridge47.js"></script>
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
	var nextstring = "bridge47.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
</script>

<?php
require("lib63.php");

function displayTableauResultatPaireNS( $large ) {
	global $maxetuis;
	print '<table border="0" style="max-width: 350px; margin:auto;">';
	print "<tr><th class='xres'>Etui</th><th class='xres'>EO</th>";
	if ($large > 0)
		print "<th class='xres'>cont.</th><th class='xres'>par</th><th class='xres'>ent.</th><th class='xres'>res</th>";
	print "<th class='xres'>pts</th></tr>";

	for ($i = 0; $i < $maxetuis; $i++) {
		$nr = "nr_" . $i;
		$nr1 = $nr . "_1";
		$nr2 = $nr . "_2";
		$nr3 = $nr . "_3";
		$nr4 = $nr . "_4";
		$nr5 = $nr . "_5";
		$nr6 = $nr . "_6";
		$nr7 = $nr . "_7";
		//$nr8 = $nr . "_8";
		//$nr9 = $nr . "_9";
		$ndok = "ok_" . $i;
		$j=$i+1;
		print "<tr id='$nr' class='xtr61 xtr_invisible'>";
		print "<td id='$nr1' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr2' class='xNum5'>&nbsp;</td>";
		if ($large > 0) {
			print "<td id='$nr3' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr4' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr5' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr6' class='xNum5'>&nbsp;</td>";
		}
		print "<td><input class='xNum5' type='text' id='$nr7' size='3'></td>";
		print "<td class='xNum5'><img src='images/ok.png' id='$ndok' class='clkok' height='20' /></td>";
		print "</tr>";
	};
	print "</tbody></table>";
};

?>
 
<body>
	<div style="text-align: center">
	<h2>Entrée des résultats d'une table</br>en utilisant la feuille de marque</h2>
	

	<?php
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = isset( $_GET['w'] ) ? htmlspecialchars( $_GET['w'] ) : '';
	?>
	
	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	console.log( screenw );
	if ( isNaN( screenw ) ) reload();
	</script>
		
	<?php
	$t = readTournoi( $idtournoi );
	$pairesNS = $t[ 'pairesNS' ];
	$pairesEO = $t[ 'pairesEO' ];
	$njouees = $t[ 'njouees' ];
	$idtype	= $t[ 'idtype' ];

	$ntables 	= $t[ 'ntables' ];
	$ndonnes	= $t[ 'ndonnes' ];
	$paquet		= $t[ 'paquet' ];
	$saut		= $t[ 'saut' ];
	$gueridon	= $t[ 'gueridon' ];
	?>
	<script type="text/javascript"> 
	idtournoi = parseInt( "<?php echo $idtournoi; ?>" );
	pairesNS  = parseInt( "<?php echo $pairesNS; ?>" );
	pairesEO  = parseInt( "<?php echo $pairesEO; ?>" );
	njouees	  = parseInt( "<?php echo $njouees; ?>" );
	
	ntables  = parseInt( "<?php echo $ntables; ?>" );
	ndonnes  = parseInt( "<?php echo $ndonnes; ?>" );
	paquet   = parseInt( "<?php echo $paquet; ?>" );
	saut   	 = parseInt( "<?php echo $saut; ?>" );
	gueridon = parseInt( "<?php echo $gueridon; ?>" );
	</script>
	
	<p>Naviguez entre les différentes tables</br>en cliquant sur les chiffres pour avancer</br>ou reculer d'une unité ou de 5 unités</p>
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="tabm5">-5</div></td>
	<td class='xNum2'><div id="tabm1">-1</div></td>
	<td class='xNum2'><div id="tabp1">+1</div></td>
	<td class='xNum2'><div id="tabp5">+5</div></td>
	</tr><tbody></table>
	<h3>Résultats table n°<span id='table'>1</span>&nbsp;<span id="msgerr1"></span></h3>
	<?php
	print "<p>PairesNS: $pairesNS, pairesEO: $pairesEO, ndonnes: $ndonnes, njouées: $njouees, paquet: $paquet</p>";
	print "<p>Cliquez sur <img src='images/ok.png' height='20' /> pour enregistrer le résultat<br/>Sur ordinateur, utilisez la touche ENTER</p>";
	displayTableauResultatPaireNS( ($screenw > $parametres['maxw2']) ? 1 : 0 );
	?>
	<p id="msgerr" >&nbsp;</p>
	<p><button class="mySmallButton" onclick="goto44()">Retour page de clôture</button></p>
	<script>
	sel_table( 0 );
	</script>
	</div>
	
</body>
</html>