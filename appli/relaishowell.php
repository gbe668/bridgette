<?php
require("configuration.php");
require("bridgette_bdd.php");
	
$donne	= htmlspecialchars( $_GET['donne'] );
$ns		= htmlspecialchars( $_GET['ns'] );
$eo		= htmlspecialchars( $_GET['eo'] );
$inc	= htmlspecialchars( $_GET['inc'] );
$paquet	= htmlspecialchars( $_GET['paquet'] );

incrementCompteurRelaisEO( $donne, $ns, $eo, $inc, $paquet );
?>