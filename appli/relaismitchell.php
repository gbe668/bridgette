<?php
require("configuration.php");
require("bridgette_bdd.php");
	
$donne	= htmlspecialchars( $_GET['donne'] );
$ns		= htmlspecialchars( $_GET['ns'] );
$eo		= htmlspecialchars( $_GET['eo'] );
$paquet	= htmlspecialchars( $_GET['paquet'] );

incrementCompteurRelaisNS( $donne, $ns, $eo, $paquet );
?>