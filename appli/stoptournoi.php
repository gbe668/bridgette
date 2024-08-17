<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
set_etat( $idtournoi, $st_phase_fini );
echo json_encode( array( 'idtournoi'=> $idtournoi ) );
?>
