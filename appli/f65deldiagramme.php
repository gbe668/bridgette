<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
$donne = $_GET['donne'];
$ok = deleteDiagramme( $idtournoi, $donne );

if ( $ok == 0 ) $strok = "diagramme non trouvé";
else $strok = "diagramme supprimé";

echo json_encode( array( 'ok' => $ok, 'display' => $strok ) );
?>
