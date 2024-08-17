<?php
require("configuration.php");
require("bridgette_bdd.php");

require("lib59.php");
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!--
	<link rel="stylesheet" href="/css/bridgestylesheet.css" />
	<script src="/js/jquery-3.6.0.min.js"></script>
	<script src="/js/bridge63.js"></script>
	-->
	<?php
	echo '<link  href="'.$relpgm.'css/bridgestylesheet.css" rel="stylesheet" />';
	echo '<script src="'.$relpgm.'js/jquery-3.6.0.min.js" ></script>';
	echo '<script src="'.$relpgm.'js/bridge63.js" ></script>';
	?>
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var relimg = "<?php echo $relimg; ?>";

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
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
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/></div>
	</div>
	
	<script>
	var realdonne = new Donnejouee(0);
	</script>
</body>
</html>