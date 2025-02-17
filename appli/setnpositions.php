<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

$idtournoi  = htmlspecialchars( $_GET['idtournoi'] );
$npositions = htmlspecialchars( $_GET['npositions'] );
logevent("setnpositions ".$idtournoi." n=".$npositions);

echo set_npositions( $idtournoi, $npositions );
?>
