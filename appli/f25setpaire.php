<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );
$cmd = htmlspecialchars( $_GET['cmd'] );
$ida = htmlspecialchars( $_GET['ida'] );
$idb = htmlspecialchars( $_GET['idb'] );

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

	$k = -1;		// recherche première paire vide
	$ija = -1;	// joueur en nord/est
	$ijb = -1;	// joueur en sud/ouest
	for ($i = 0; $i < count($lignes); $i++) {
		$ligne =$lignes[ $i ];
		if ( $ligne['A']['id'] > 0 ) {
			if ( $ligne['A']['id'] == $ida ) $ija = $i;
			if ( $ligne['B']['id'] == $ida ) $ijb = $i;
		}
		if ( ($ligne['A']['id'] == 0)&&($ligne['B']['id'] == 0)&&( $k < 0) ) $k = $i;	// 1ère ligne vide
	}

	// ok, enregistre
	
	// lock la table des paires NS
	switch( $cmd ) {
		case "add": {	// ajoute une paire
			if ( $ija >= 0 ) {
				$ret = "ko, déjà inscrit !";
				break;
			}
			if ( $ijb >= 0 ) {
				$ret = "ko, déjà inscrit comme partenaire !";
				break;
			}
			// utilisation 1ère paire vide
			if ( $k < 0 ) {
				$ret = "ko, tableau des inscriptions complet !";
				break;
			}
			// effacement des joueurs si déjà placés
			efface_joueur( $idt, $ida );
			efface_joueur( $idt, $idb );
			// ajout paire
			$n = intval( $k/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
			if ( $k%2 > 0 ) {	// paire EO
				_set_joueurEst  ( $dbh, $idt, $n, $ida );
				_set_joueurOuest( $dbh, $idt, $n, $idb );
			}
			else {
				_set_joueurNord( $dbh, $idt, $n, $ida );
				_set_joueurSud ( $dbh, $idt, $n, $idb );
			}
			$ret = "ok";
			break;
		}
		case "mod": {	// modifie le partenaire
			if ( $ija >= 0 ) {
				$n = intval( $ija/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
				if ( $ija%2 > 0 ) {
					_set_joueurOuest( $dbh, $idt, $n, $idb );
				}
				else {
					_set_joueurSud( $dbh, $idt, $n, $idb );
				}
				$ret = "ok";
			}
			else {
				$ret = "ko, non trouvé !";
			}
			break;
		}
		case "del": {	// supprime la paire
			if ( $ija >= 0 ) {
				$n = intval( $ija/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
				if ( $ija%2 > 0 ) {
					_set_joueurEst  ( $dbh, $idt, $n, 0 );
					_set_joueurOuest( $dbh, $idt, $n, 0 );
				}
				else {
					_set_joueurNord( $dbh, $idt, $n, 0 );
					_set_joueurSud ( $dbh, $idt, $n, 0 );
				}
				$ret = "ok";
			}
			else {
				$ret = "ko, non trouvé !";
			}
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
