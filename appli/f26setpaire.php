<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );
$cmd = htmlspecialchars( $_GET['cmd'] );
$idnord = htmlspecialchars( $_GET['idnord'] );
$idsud  = htmlspecialchars( $_GET['idsud'] );

$dbh = connectBDD();

// vérif tournoi en préparation
$t = _readTournoi( $dbh, $idt );
$etat = $t['etat'];

if ( $etat == $st_preinscription ) {
	// tournoi en phase de pré-inscription
	// vérification que l'inscription est toujours valide,
	//	ie les 2 joueurs n'ont pas été inscrits d'autres personnes ( cas d'une page html ouverte trop longtemps )

	$k = 0;		// recherche première paire vide
	$ijn = 0;	// joueur en nord
	$ijs = 0;	// joueur en sud
	for ($i = 1; $i < $max_tables+1; $i++) {
		$ligne = _getligneNS( $dbh, $idt, $i );
		if ( $ligne['N']['id'] > 0 ) {
			if ( $ligne['N']['id'] == $idnord ) $ijn = $i;
			if ( $ligne['S']['id'] == $idnord ) $ijs = $i;
		}
		if ( ($ligne['N']['id'] == 0)&&($ligne['S']['id'] == 0)&&( $k == 0) ) $k = $i;
	}

	// ok, enregistre
	
	// lock la table des paires NS
	switch( $cmd ) {
		case "add": {	// ajoute une paire
			if ( $ijn > 0 ) {
				$ret = "ko, déjà inscrit !";
				break;
			}
			if ( $ijs > 0 ) {
				$ret = "ko, déjà inscrit comme partenaire !";
				break;
			}
			// utilisation 1ère paire vide
			if ( $k == 0 ) {
				$ret = "ko, $max_tables inscriptions !";		// ****************************
				break;
			}
			_set_joueurNord( $dbh, $idt, $k, $idnord );
			_set_joueurSud ( $dbh, $idt, $k, $idsud );
			$ret = "ok";
			break;
		}
		case "mod": {	// modifie le partenaire en sud
			if ( $ijn > 0 ) {
				_set_joueurSud( $dbh, $idt, $ijn, $idsud );
				$ret = "ok";
			}
			else {
				$ret = "ko, non trouvé !";
			}
			break;
		}
		case "del": {	// supprime la paire
			if ( $ijn > 0 ) {
				_set_joueurNord( $dbh, $idt, $ijn, 0 );
				_set_joueurSud ( $dbh, $idt, $ijn, 0 );
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
	
	// unlock
	
	}
}
else {
	// tournoi non en phase préparation, rejet de la commande
	$ret = "ko";
}
$lignes = [];
// Retourne le tableau des paires mis à jour
for  ($i = 1; $i < $max_tables+1; $i++) {
	$ligne = _getligneNS( $dbh, $idt, $i );
	array_push( $lignes, $ligne );
}

$dbh = null;

echo json_encode( array( 'ret'=>$ret, 'lignes'=>$lignes ) );
?>
