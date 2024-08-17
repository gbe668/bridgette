<?php
//require("configuration.php");
//require("bridgette_bdd.php");

function writelogerrors( $data ) {
	$file_errors = "errors_apk.json";
	$record = [	"t" => date("Y-m-d_H:i:s"), "data" => $data ];
	$ligne = json_encode($record)."\n";
	file_put_contents( $file_errors, $ligne, FILE_APPEND );
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
function gotoindex() {
	retparms = { next:"bridge60" };
	passAndroid( retparms );
};
function passAndroid( parms ) {
	Android.processNext( JSON.stringify(parms) );
};
function showAndroidToast(toast) {
	Android.showToast(toast);
}
</script>
 
 <body>
	<div style="text-align:center; max-width:350px; margin:auto;">
	<h2 style='text-align: center'>Signalisation bug</h2>
	<?php
	if ( isset($_GET['bug']) ) 
		$bug = htmlspecialchars( $_GET['bug'] );
	else
		$bug = "non précisé";
	
	writelogerrors( $bug );
	?>
	
	<p id="msg">bug: <?php echo $bug ?></p>
	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	</div>
 </body>
</html>