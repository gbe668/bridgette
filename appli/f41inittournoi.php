<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );
$pns = htmlspecialchars( $_GET['okns'] );
$peo = htmlspecialchars( $_GET['okeo'] );

echo initTournoi( $idt, $pns, $peo );
?>
