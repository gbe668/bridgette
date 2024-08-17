<?php
require("configuration.php");
require("bridgette_bdd.php");

// Fetching Values From URL
$numtable = $_GET['numtable'];
$res = getParmTable( $numtable );
$donne = $res[ 'numdonne' ];
$cpt = $res[ 'cpt' ];
$numeo = $res[ 'numeo' ];
echo json_encode( array( 'donne'=>$donne, 'cpt'=>$cpt, 'numeo'=>$numeo ) );
?>
