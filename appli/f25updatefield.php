<?php
require("configuration.php");
require("bridgette_bdd.php");

function updatePhone( $idj, $phone ) {
	global $tab_joueurs;
	$result = [];
	if ( $idj ) {
		$dbh = connectBDD();
		$sql = "UPDATE $tab_joueurs SET telephone='$phone' where id='$idj';";
		$sth = $dbh->query( $sql );
		$result['success'] = 1;
		$result['msg'] = "Numéro téléphone enregistré.";
		$dbh = null;
	}
	else {
		$result['success'] = 0;
		$result['msg'] = "Erreur id !";
	}
	return $result;
};
function updateMail( $idj, $email ) {
	global $tab_joueurs;
	$result = [];
	$result['success'] = 0;
	if ( $idj ) {
		$dbh = connectBDD();
		// test adresse mail nulle
		if ( $email == "" ) {
			$sql = "UPDATE $tab_joueurs SET email='$email' where id='$idj';";
			$dbh->query( $sql );
			$result['success'] = 1;
			$result['msg'] = "Mail effacé.";
		}
		else {
			// test adresse mail déjà utilisée
			$sql = "SELECT count(*) from $tab_joueurs where email='$email';";
			$nbl = $dbh->query($sql)->fetchColumn();
			switch( $nbl ) {
				case 0:
					$sql = "UPDATE $tab_joueurs SET email='$email' where id='$idj';";
					$dbh->query( $sql );
					$result['success'] = 1;
					$result['msg'] = "Nouveau mail enregistré.";
					break;
				case 1: 
					$sql = "SELECT * from $tab_joueurs where email='$email';";
					$sth = $dbh->query($sql);
					$row = $sth->fetch(PDO::FETCH_ASSOC);
					if ( $row['id'] == $idj ) {
						$result["msg"] = "$email inchangé.";
					}
					else {
						$result["msg"] = "$email déjà utilisé.";
					}
					break;
				default: 
					$result["msg"] = "$email déjà utilisé (bug).";
			}
		}
		$dbh = null;
	}
	else {
		$result['msg'] = "Erreur id !";
	}
	return $result;
};

// Fetching Values From URL

$idj = $_GET['idjoueur'];
$cmd = $_GET['command'];
$res = [];
switch( $cmd ) {
	case 'email':
		$email	= htmlspecialchars( $_GET['email'] );
		$res = updateMail( $idj, $email );
		break;
	case 'phone':
		$phone	= htmlspecialchars( $_GET['phone'] );
		$res = updatePhone( $idj, $phone );
		break;
	default:
		$res['success'] = 0;
		$res['msg'] = "Unknown command !";
}

echo json_encode( $res );
?>
