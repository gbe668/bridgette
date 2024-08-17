<?php
header("Access-Control-Allow-Origin: *");

require("configuration.php");
require("bridgette_bdd.php");
require("libcontacts.php");

$datetournoi = htmlspecialchars( $_GET['datetournoi'] );
$name = htmlspecialchars( $_GET['name'] );
$contact = htmlspecialchars( $_GET['contact'] );
$memo = htmlspecialchars( $_GET['memo'] );
echo insertContact( $datetournoi, $name, $contact, $memo );
?>
