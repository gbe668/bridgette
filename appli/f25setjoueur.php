<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );
$cmd = htmlspecialchars( $_GET['cmd'] );
$idj = htmlspecialchars( $_GET['idj'] );
$kli = htmlspecialchars( $_GET['k'] );
$aoub = htmlspecialchars( $_GET['pos'] );

$dbh = connectBDD();

// vérif tournoi en préparation
$t = _readTournoi( $dbh, $idt );
$etat = $t['etat'];

if ( $etat == $st_preinscription ) {
	// tournoi en phase de pré-inscription
	// tableau des paires avant modification
	$lignes = _getlignes($dbh, $idt);
	
	// vérification que l'inscription est toujours valide,
	//	ie les 2 joueurs n'ont pas été inscrits d'autres personnes ( cas d'une page html ouverte trop longtemps )

	$k = -1;	// recherche première paire vide
	$ija = -1;	// joueur en nord/est
	$ijb = -1;	// joueur en sud/ouest
	for ($i = 0; $i < count($lignes); $i++) {
		$ligne =$lignes[ $i ];
		if ( $ligne['A']['id'] > 0 ) {
			if ( $ligne['A']['id'] == $idj ) $ija = $i;
			if ( $ligne['B']['id'] == $idj ) $ijb = $i;
		}
		if ( ($ligne['A']['id'] == 0)&&($ligne['B']['id'] == 0)&&( $k < 0) ) $k = $i;	// 1ère ligne vide
	}

	// ok, enregistre
	
	// lock la table des paires NS
	switch( $cmd ) {
		case "eff1": {	// efface le joueur
			efface_joueur( $idt, $idj );
			$ret = "ok";
			break;
		}
		case "add1": {	// ajoute un joueur dans une nouvelle paire
			// effacement du joueur si déjà placé
			efface_joueur( $idt, $idj );
			// ajout paire
			$n = intval( $k/2 ) + 1;	// 1er numéro de paire libre dans le tableau NS ou EO
			if ( $k%2 > 0 ) {	// paire EO
				if ( $aoub == 'a' )
					_set_joueurEst  ( $dbh, $idt, $n, $idj );
				else
					_set_joueurOuest( $dbh, $idt, $n, $idj );
			}
			else {
				if ( $aoub == 'a' )
					_set_joueurNord( $dbh, $idt, $n, $idj );
				else
					_set_joueurSud ( $dbh, $idt, $n, $idj );
			}
			$ret = "ok";
			break;
		}
		case "mod1": {	// modifie le joueur
			// effacement du joueur si déjà placé
			efface_joueur( $idt, $idj );
			$n = intval( $kli/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
			if ( $kli%2 > 0 ) {	// paire EO
				if ( $aoub == 'a' )
					_set_joueurEst  ( $dbh, $idt, $n, $idj );
				else
					_set_joueurOuest( $dbh, $idt, $n, $idj );
			}
			else {
				if ( $aoub == 'a' )
					_set_joueurNord( $dbh, $idt, $n, $idj );
				else
					_set_joueurSud ( $dbh, $idt, $n, $idj );
			}
			$ret = "ok";
			break;
		}
		default: {
			$ret = "ko, commande inconnue !";
			break;
		}
	}
	// Retourne le tableau des paires mis à jour
	$lignes = _getlignes($dbh, $idt);

	// unlock
	
}
else {
	// tournoi non en phase préparation, rejet de la commande
	$ret = "ko";
}
$dbh = null;

echo json_encode( array( 'ret'=>$ret, 'lignes'=>$lignes ) );
?>
