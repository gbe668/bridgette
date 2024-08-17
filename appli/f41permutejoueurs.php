<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];
// vérif tournoi en préparation
$t = readTournoi( $idtournoi );
$etat = $t['etat'];

if ( $etat == $st_phase_init ) {
	// tournoi en phase de définition
	$src = $_GET['src'];
	$dst = $_GET['dst'];

	$d1 = substr( $src, 0, 2 );
	$d2 = substr( $dst, 0, 2 );
	$n1 = substr( $src, 2 );
	$n2 = substr( $dst, 2 );

	if ( $d2 == "NS" ) {
		// sauvegarde ligne de destination
		$savligne = getligneNS( $idtournoi, $n2 );
		$savid1 = $savligne['N']['id'];
		$savid2 = $savligne['S']['id'];
		if ( $d1 == "NS" ) {
			$ligne = getligneNS( $idtournoi, $n1 );
			$id1 = $ligne['N']['id'];
			$id2 = $ligne['S']['id'];
			setligneNS( $idtournoi, $n2, $id1, $id2 );
			setligneNS( $idtournoi, $n1, $savid1, $savid2 );
		}
		else {	// "EO"
			$ligne = getligneEO( $idtournoi, $n1 );
			$id1 = $ligne['E']['id'];
			$id2 = $ligne['O']['id'];
			setligneNS( $idtournoi, $n2, $id1, $id2 );
			setligneEO( $idtournoi, $n1, $savid1, $savid2 );
		}
	}
	else {
		// sauvegarde ligne de destination
		$savligne = getligneEO( $idtournoi, $n2 );
		$savid1 = $savligne['E']['id'];
		$savid2 = $savligne['O']['id'];
		if ( $d1 == "NS" ) {
			$ligne = getligneNS( $idtournoi, $n1 );
			$id1 = $ligne['N']['id'];
			$id2 = $ligne['S']['id'];
			setligneEO( $idtournoi, $n2, $id1, $id2 );
			setligneNS( $idtournoi, $n1, $savid1, $savid2 );
		}
		else {	// "EO"
			$ligne = getligneEO( $idtournoi, $n1 );
			$id1 = $ligne['E']['id'];
			$id2 = $ligne['O']['id'];
			setligneEO( $idtournoi, $n2, $id1, $id2 );
			setligneEO( $idtournoi, $n1, $savid1, $savid2 );
		}
	}
	// test tableaux complets
	$okns = testlignescompletesNS( $idtournoi );
	$okeo = testlignescompletesEO( $idtournoi );
	$reponse = 1;
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
