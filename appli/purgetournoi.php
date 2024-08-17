<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
del_Tournoi( $idtournoi );
echo "<p>tournoi purgé</p>";
?>
