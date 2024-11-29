<?php
require("configuration.php");
require("bridgette_bdd.php");

$datetournoi = $_GET['datetournoi'];
echo createInscriptionTournoi( $datetournoi );
?>
