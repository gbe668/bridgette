<?php
header("Access-Control-Allow-Origin: *");

require("configuration.php");
require("bridgette_bdd.php");

$datetournoi = htmlspecialchars( $_GET['datetournoi'] );
echo existeTournoiClos( $datetournoi );
?>
