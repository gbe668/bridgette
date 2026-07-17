<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$userid    = htmlspecialchars( $_GET['userid'] );

$dbh = connectBDD();
$t = _readTournoi( $dbh, $idtournoi );
$etat = $t[ 'etat' ];

if ( $etat == $st_phase_init ) {
	// Recherche du joueur dans le tableau des participants
	$user = recherche_joueur( $idtournoi, $userid );
	$paire = $user['table'];		// position initiale
	if ( $paire > 0 ) {
		if ( ($user['place']==$pos_Nord)||($user['place']==$pos_Sud) ) $ligne = "NS";
		else $ligne = "EO";
		$str = "<p><b>Table $paire en $ligne </b></p>";
	}
	else {
		// joueur non trouvé
		$str = "<p>Non placé à cet instant!</p>";
	}
}
else {
	$str = "Attendez";
}
$dbh = null;
echo json_encode( array( 'etat'=>$etat, 'position'=>$str ) );
?>
