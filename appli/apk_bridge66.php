<?php
require("configuration.php");
require("bridgette_bdd.php");
?>

<!DOCTYPE HTML>
<html>

<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link href="/css/bridgestylesheet.css" rel="stylesheet" />
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
	$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
	$screenw = htmlspecialchars( $_GET['w'] );
	print "<h2 style='color:red'>Résultats provisoires</h2>";
	displayResultatsTournoi( $idtournoi, $screenw );
	?>
	
	</div>
		
</body>
</html>