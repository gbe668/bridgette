<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
logevent("stoptournoi ".$idtournoi);

set_etat( $idtournoi, $st_phase_fini );
echo json_encode( array( 'idtournoi'=> $idtournoi ) );
?>
