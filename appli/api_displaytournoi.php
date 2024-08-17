<?php
header('Access-Control-Allow-Origin: *');

require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$screenw = htmlspecialchars( $_GET['w'] );

if ( $idtournoi == 0 ) {
	$idtournoi = getlastclosedtournois();
}

if ($idtournoi> 0) {
	$tabhtml = htmlDisplayResultatsTournoi($idtournoi, $screenw);
}
else $tabhtml = "<p>Pas de tournois enregistrés !</p>";
	
echo $tabhtml;
?>