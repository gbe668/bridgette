<?php
require("configuration.php");
require("bridgette_bdd.php");

$ntables = $_GET['ntables'];
$mintour = getMinTour( $ntables );
echo $mintour;
?>
