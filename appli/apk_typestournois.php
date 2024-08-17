<?php
require("configuration.php");
require("bridgette_bdd.php");
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Apk Bridg'ette</title>
    <meta charset="UTF-8">
	<link rel="stylesheet" href="/css/bridgestylesheet.css" />
</head>

 <body>
	<div style="text-align:center; max-width:350px; margin:auto;">	
	<?php
	print htmlTableTypeTournois();
	?>
	</div>
 </body>
</html>