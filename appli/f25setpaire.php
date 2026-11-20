<?php
require("configuration.php");
require("bridgette_bdd.php");

header('Content-Type: application/json');

// vérification présence des paramètres
if ( !isset($_GET['idtournoi'], $_GET['cmd'], $_GET['ida'], $_GET['idb']) ) {
	echo json_encode( array( 'ret'=>'ko, paramètres manquants', 'lignes'=>array() ) );
	exit;
}

$idt = intval( $_GET['idtournoi'] );
$cmd = htmlspecialchars( $_GET['cmd'] );   // reste une chaîne (add/mod/del), htmlspecialchars OK ici
$ida = intval( $_GET['ida'] );
$idb = intval( $_GET['idb'] );

$lignes = array();   // toujours défini, même en cas de rejet plus bas
$ret = "ko";

$dbh = connectBDD();

// vérif tournoi en préparation
$t = _readTournoi( $dbh, $idt );

if ( !$t ) {
	$ret = "ko, tournoi introuvable !";
}
else {

	$etat = $t['etat'];

	if ( $etat == $st_preinscription ) {
		// tournoi en phase de pré-inscription
		// tableau des paires avant modification
		$lignes = _getlignes($dbh, $idt);

		// vérification que l'inscription est toujours valide,
		//	ie les 2 joueurs n'ont pas été inscrits d'autres personnes ( cas d'une page html ouverte trop longtemps )

		$k = -1;	// recherche première paire vide
		$ija = -1;	// ligne où $ida est déjà présent (en A)
		$ijb = -1;	// ligne où $ida est déjà présent (en B, comme partenaire)
		$row_idb = -1;	// ligne où $idb est déjà présent (quelle que soit la colonne)
		$col_idb = '';	// 'A' ou 'B' : colonne où $idb est présent sur $row_idb
		$paired_idb = false;	// true si $idb est dans une paire complète (déjà en couple avec quelqu'un d'autre)

		for ($i = 0; $i < count($lignes); $i++) {
			$ligne = $lignes[ $i ];
			$aid = $ligne['A']['id'];
			$bid = $ligne['B']['id'];

			if ( ($aid == $ida) && ($aid > 0) ) $ija = $i;
			if ( ($bid == $ida) && ($bid > 0) ) $ijb = $i;

			if ( $idb > 0 ) {
				if ( $aid == $idb ) {
					$row_idb = $i; $col_idb = 'A';
					$paired_idb = ( $bid > 0 );	// l'autre place est occupée => idb déjà en couple
				}
				if ( $bid == $idb ) {
					$row_idb = $i; $col_idb = 'B';
					$paired_idb = ( $aid > 0 );
				}
			}

			if ( ($aid == 0) && ($bid == 0) && ( $k < 0) ) $k = $i;	// 1ère ligne vide
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
				if ( $row_idb >= 0 ) {
					$ret = "ko, partenaire déjà inscrit !";
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
				$ret = "ok, paire ajoutée";
				break;
			}
			case "mod": {	// modifie le partenaire
				if ( $ija >= 0 ) {
					// si idb est trouvé ailleurs (pas sur la ligne de ida) ET qu'il est déjà en couple complet,
					// on refuse : il n'est pas disponible
					if ( $row_idb >= 0 && $row_idb != $ija && $paired_idb ) {
						$ret = "ko, partenaire déjà en couple !";
						break;
					}

					$n = intval( $ija/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
					if ( $ija%2 > 0 ) {
						_set_joueurOuest( $dbh, $idt, $n, $idb );
					}
					else {
						_set_joueurSud( $dbh, $idt, $n, $idb );
					}

					// si idb était "en recherche de partenaire" (solo) ailleurs, on libère son ancienne place
					if ( $row_idb >= 0 && $row_idb != $ija && !$paired_idb ) {
						$np = intval( $row_idb/2 ) + 1;	// numéro de paire dans le tableau NS ou EO
						if ( $row_idb%2 > 0 ) {	// ancienne place en paire EO
							if ( $col_idb == 'A' ) _set_joueurEst  ( $dbh, $idt, $np, 0 );
							else                   _set_joueurOuest( $dbh, $idt, $np, 0 );
						}
						else {	// ancienne place en paire NS
							if ( $col_idb == 'A' ) _set_joueurNord( $dbh, $idt, $np, 0 );
							else                   _set_joueurSud ( $dbh, $idt, $np, 0 );
						}
					}

					$ret = "ok, partenaire changé";
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
					$ret = "ok, supprimé";
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
		$ret = "ko, tournoi non en phase préparation !";
	}
}

$dbh = null;

echo json_encode( array( 'ret'=>$ret, 'lignes'=>$lignes ) );
?>
