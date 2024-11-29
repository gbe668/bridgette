<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
echo startInscriptionTournoi( $idtournoi );
?>
