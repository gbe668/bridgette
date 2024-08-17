<?php
require("configuration.php");
require("bridgette_bdd.php");

function htmlPositionsHowell($idtype, $pns, $npos, $paquet) {
	$str = "";
	for ( $i = 1; $i <= $npos; $i++ ) {
		$str .= "<p>".htmlPositionHowell($idtype, $pns, $i, $paquet)."</p>";
	}
	return $str;
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


<body>
	<div style="text-align: center">
	<h1>Positions Howell</h1>
	
	<?php
	$arr = array( 4, 5, 6, 7, 8, 32, 9, 33 );
	foreach ( $arr as &$idtype ) {
		$tt = gettypetournoi( $idtype );
		$pns = $tt['npaires'];
		$npos = $tt['npositions'];
		$paquet	= $tt['paquet'];
		$obs = $tt['obs'];
		print "<h2>Howell $pns paires</h2>";
		print "<p>$obs, $paquet étuis par table</p>";
		print htmlPositionsHowell($idtype, $pns, $npos, $paquet);
	};
			
	?>
	</div>
</body>
</html>