<?php
require("configuration.php");
require("bridgette_bdd.php");

require("lib59.php");
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Apk Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link  href="/css/bridgestylesheet.css" rel="stylesheet" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge63.js"></script>
</head>

<script>
var relimg = "<?php echo $relimg; ?>";

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
	<div style="text-align: center">
	<h2 style="text-align: center">Entrez le contrat</h2>

	<div id="section_contrat">
	<?php
	print_grille_calcul_marque();
	?>
	<div id="section_entame">
	<div id="section_resultat">
	</div>
	</div>
	</div>
	</div>
	
	<script>
	var realdonne = new Donnejouee(0);
	</script>
</body>
</html>