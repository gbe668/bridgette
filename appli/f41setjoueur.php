<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$numpaire  = htmlspecialchars( $_GET['numpaire'] );
$position  = htmlspecialchars( $_GET['position'] );
$idjoueur  = htmlspecialchars( $_GET['idjoueur'] );
$previousposition = array( 'paire'=> 0, 'position'=> 0 ); 	// ( paire, position )
$reponse = 0;
$prevpos = 0;

// vérif tournoi en préparation
$t = readTournoi( $idtournoi );
$etat = $t['etat'];

if ( $etat == $st_phase_init ) {
	// tournoi en phase de définition
	if ( $idjoueur > 0 ) {
		$joueur = getJoueur( $idjoueur );
		$name = $joueur['joueur'];
		$nomcomplet = $joueur['nomcomplet'];
		
		// efface joueur si déjà placé quelque part
		$previousposition = efface_joueur( $idtournoi, $idjoueur );
		if ( $previousposition[ 'paire' ] > 0 ) $prevpos = 1;
	
		if ( $position == $pos_Nord	 ) set_joueurNord ( $idtournoi, $numpaire, $idjoueur );
		if ( $position == $pos_Est	 ) set_joueurEst  ( $idtournoi, $numpaire, $idjoueur );
		if ( $position == $pos_Sud	 ) set_joueurSud  ( $idtournoi, $numpaire, $idjoueur );
		if ( $position == $pos_Ouest ) set_joueurOuest( $idtournoi, $numpaire, $idjoueur );
	}
	// test tableaux complets
	$reponse = 1;
	$okns = testlignescompletesNS( $idtournoi );
	$okeo = testlignescompletesEO( $idtournoi );
}
else {
	// tournoi non en phase préparation
	// rejet de la commande
	$reponse = -1;
	$okns = 0;
	$okeo = 0;
	$name = "???";
	$nomcomplet = "???";
}
// retourne:
//	réponse du serveur: 0 = n° inconnu, 1 = n° connu ou 0
//	nom du joueur si connu, "N° inconnu" sinon
//	si joueur non placé auparavant: 0, si déjà placé quelque part: 1
//	$previousposition = array( 0, 0 ); 	// ( paire, position )
// complétude des tableaux
echo json_encode( array('reponse'=>$reponse,
	'idjoueur'=>$idjoueur, 'nomjoueur'=>$name, 'nomcomplet'=>$nomcomplet,
	'newpaire'=>$numpaire, 'newposition'=>$position,
	'prevpos'=>$prevpos, 'prevpaire'=>$previousposition[ 'paire' ], 'prevposition'=>$previousposition[ 'position' ],
	'oktabns'=>$okns, 'oktabeo'=>$okeo ) );
?>
