<?php
require("configuration.php");
require("bridgette_bdd.php");
	
$eo		= htmlspecialchars( $_GET['eo'] );
$paquet	= htmlspecialchars( $_GET['paquet'] );

incrementCompteurEO( $eo, $paquet );
?>