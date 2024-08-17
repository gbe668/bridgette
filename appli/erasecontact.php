<?php
header("Access-Control-Allow-Origin: *");

require("configuration.php");
require("bridgette_bdd.php");
require("libcontacts.php");

$id = htmlspecialchars( $_GET['id'] );
echo erasecontact( $id );
?>
