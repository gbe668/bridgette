<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function insertJoueur( $gender, $fname, $lname, $email, $password ) {
	global $tab_joueurs;
	global $min_noclub, $max_noclub, $del_noclub;
	$result = array( "ok" => 0, "noclub" => 0, "msg" => "message" );	// Ok/Ko, noclub, message
	$joueur = $gender . " " . strtoupper( $lname );
	// test joueur déjà connu
	$dbh = connectBDD();
	$sql = "SELECT count(*) from $tab_joueurs where genre='$gender' and prenom='$fname' and nom='$lname';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl == 0 ) {
		// rechercher un numéro club disponible
		$sql = "SELECT count(*) FROM $tab_joueurs where numero >= '$min_noclub' and numero < '$max_noclub' order by numero;";
		$res = $dbh->query($sql);
		$nbl = $res->fetchColumn();
		if ( $nbl == 0 ) {
			// le 1er joueur à s'enregistrer
			$prevnum = $min_noclub;
			$result["ok"] = 1;	// trouvé
			$result["noclub"] = $prevnum;
		}
		else {
			// pour les joueurs suivants
			$sql = "SELECT numero FROM $tab_joueurs where numero >= '$min_noclub' and numero < '$max_noclub' order by numero;";
			$sth = $dbh->query( $sql );
			
			$prevnum = $min_noclub;		// point de départ de la recherche d'un trou entre les numéros déjà attribués
			for ( $i = 0; $i < $nbl; $i++ ) {
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$nextnum = $row[ 'numero' ];
				if ( $prevnum < $nextnum ) {
					$result["ok"] = 1;	// trouvé
					$result["noclub"] = $prevnum;
					break;
				}
				$prevnum = $nextnum + 1;
			}
			// pas de trous entre les numéros déjà attribués
			if ( $result["ok"] == 0 ) {
				// attribution du numéro suivant si possible
				if ( $prevnum < $max_noclub ) {
					$result["ok"] = 1;	// trouvé
					$result["noclub"] = $prevnum;
				}
			}
		}
		if ( $result["ok"] == 1 ) {
			// enregistrer le joueur
			$sql = "INSERT into $tab_joueurs ( numero, joueur, genre, prenom, nom, email, password ) values ( '$prevnum', '$joueur', '$gender', '$fname', '$lname', '$email', '$password' );";
			$sth = $dbh->query( $sql );
			if ( $gender == "Me" ) $result["msg"] = "$fname $lname est enregistrée.";
			else $result["msg"] = "$fname $lname est enregistré.";
		}
		else {	// plus de numéro disponible
			// recherche parmi les joueurs effacés
			$date = new DateTime();
			$datesupp = $date->getTimestamp();	// date du jour
			$dureemin = 86400*30*$del_noclub;	// $del_noclub exprimé en mois
			$datesupp = $datesupp - $dureemin;
			$sql = "SELECT count(*) FROM $tab_joueurs where datesupp < '$datesupp' and datesupp > 0 order by numero;";
			$res = $dbh->query($sql);
			$nbl = $res->fetchColumn();
			if ( $nbl > 0 ) {
				$sql = "SELECT id, numero FROM $tab_joueurs where datesupp < '$datesupp' and datesupp > 0 order by numero;";
				$sth = $dbh->query( $sql );
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$idj = $row[ 'id' ];
				$numero = $row[ 'numero' ];
				
				$sql = "UPDATE $tab_joueurs SET joueur='$joueur', genre='$gender', prenom='$fname', nom='$lname', email='$email', password='$password', datesupp=0 where id='$idj';";
				$sth = $dbh->query( $sql );
				$result["ok"] = 1;
				$result["noclub"] = $numero;
				if ( $gender == "Me" ) $result[2] = "$joueur est enregistrée.";
				else $result["msg"] = "$joueur est enregistré.";
			}				
			else {
				$result["ok"] = 0;
				$nbmaxjoueurs = $max_noclub - $min_noclub -1;
				$result["msg"] = "Erreur: $nbmaxjoueurs joueurs déjà enregistrés !</br>Supprimez les anciens joueurs pour<br/>pouvoir ajouter un nouveau joueur.";
			}
		}
	}
	else {
		$result["ok"] = 0;
		if ( $nbl == 1 ) {	// un seul joueur heureusement, recherche numéro
			$sql = "SELECT numero, joueur from $tab_joueurs where genre='$gender' and prenom='$fname' and nom='$lname';";
			$sth = $dbh->query( $sql );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$result["noclub"] = $row[ 'numero' ];
			$joueur = $row[ 'joueur' ];
			if ( $gender == "Me" ) $result["msg"] = "$joueur est déjà connue.";
			else $result["msg"] = "$joueur est déjà connu.";
		} 
		else {
			$result["msg"] = "Bug BDD: $joueur multiples.";
		}
	}
	$dbh = null;
	return $result;
};

// Fetching Values From URL

$gender = htmlspecialchars( $_GET['gender'] );
$fname	= htmlspecialchars( $_GET['fname'] );
$lname	= htmlspecialchars( $_GET['lname'] );
$email	= htmlspecialchars( $_GET['email'] );
$password = "bridgette";
$res = insertJoueur( $gender, $fname, $lname, $email, $password );

echo json_encode( array( 'success'=>$res["ok"], 'numero'=>$res["noclub"], 'msg'=>$res["msg"] ) );
?>
