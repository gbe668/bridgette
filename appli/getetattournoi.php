<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
$t = readTournoi( $idtournoi );
$etat  = $t['etat'];
$genre = $t['genre']; 

echo json_encode( array( 'etat'=>$etat, 'genre'=>$genre ) );
?>
