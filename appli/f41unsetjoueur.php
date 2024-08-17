<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
$numpaire  = $_GET['numpaire'];
$position  = $_GET['position'];
$reponse = 0;
$prevpos = 0;

// vérif tournoi en préparation
$t = readTournoi( $idtournoi );
$etat = $t['etat'];

if ( $etat == $st_phase_init ) {
	// tournoi en phase de définition
	// recherche si emplacement occupé
	$oldidjoueur = 0;
	$ligneNS = getligneNS( $idtournoi, $numpaire );
	$ligneEO = getligneEO( $idtournoi, $numpaire );
	if ( $position == $pos_Nord	 ) $oldidjoueur = $ligneNS['N']['id'];
	if ( $position == $pos_Est	 ) $oldidjoueur = $ligneEO['E']['id'];
	if ( $position == $pos_Sud	 ) $oldidjoueur = $ligneNS['S']['id'];
	if ( $position == $pos_Ouest ) $oldidjoueur = $ligneEO['O']['id'];

	if ( $oldidjoueur > 0 ) {
		$previousposition = efface_joueur( $idtournoi, $oldidjoueur );
		$reponse = 1;
	}

	// test tableaux complets
	$okns = testlignescompletesNS( $idtournoi );
	$okeo = testlignescompletesEO( $idtournoi );
}
else {
	// tournoi non en phase préparation
	// rejet de la commande
	$reponse = -1;
	$okns = 0;
	$okeo = 0;
}
echo json_encode( array('reponse'=>$reponse, 'oktabns'=>$okns, 'oktabeo'=>$okeo ) );
?>
