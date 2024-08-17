<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = createTournoi();
echo json_encode( array( 'idtournoi'=> $idtournoi ) );
?>
