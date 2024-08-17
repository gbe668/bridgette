<?php
header("Access-Control-Allow-Origin: *");

require("configuration.php");
require("bridgette_bdd.php");
require("libcontacts.php");

$datetournoi = htmlspecialchars( $_GET['datetournoi'] );
echo listeContacts( $datetournoi );
?>
