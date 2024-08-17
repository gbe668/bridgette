<?php
header("Access-Control-Allow-Origin: *");

require("configuration.php");
require("bridgette_bdd.php");
require("libcontacts.php");

$id = htmlspecialchars( $_GET['id'] );
$contact = htmlspecialchars( $_GET['contact'] );
$memo = htmlspecialchars( $_GET['memo'] );
echo updateContact( $id, $contact, $memo );
?>
