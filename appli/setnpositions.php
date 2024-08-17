<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi  = htmlspecialchars( $_GET['idtournoi'] );
$npositions = htmlspecialchars( $_GET['npositions'] );
echo set_npositions( $idtournoi, $npositions );
?>
