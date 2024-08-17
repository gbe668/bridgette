<?php
require("configuration.php");
require("bridgette_bdd.php");

// Fetching Values From URL

$idtournoi = $_GET['idtournoi'];
$donne = $_GET['donne'];
$numNS = $_GET['ns'];
$numEO = $_GET['eo'];
$contrat = $_GET['contrat'];
$jouepar = $_GET['jouepar'];
$entame = $_GET['entame'];
$resultat = $_GET['resultat'];
$points = $_GET['points'];
$ok = updateDonne( $idtournoi, $donne, $numNS, $numEO, $contrat, $jouepar, $entame, $resultat, $points );

echo $ok;
?>
