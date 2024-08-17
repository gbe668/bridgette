<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
$donne = $_GET['donne'];
$diagramme = $_GET['diagramme'];
$ok = updateDiagramme( $idtournoi, $donne, $diagramme );

if ( $ok == 0 ) $strok = "erreur enregistrement";
else $strok = "enregistrement mis à jour";

echo json_encode( array( 'ok' => $ok, 'display' => $strok ) );
?>
