<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function updateJoueur( $idj, $gender, $fname, $lname, $phone, $email ) {
	global $tab_joueurs;
	if ( $idj ) {
		$dbh = connectBDD();
		$joueur = $gender . " " . strtoupper( $lname );
		$sql = "UPDATE $tab_joueurs SET joueur='$joueur' , genre='$gender', prenom='$fname', nom='$lname', telephone='$phone', email='$email' where id='$idj';";
		$sth = $dbh->query( $sql );
		$result['success'] = 1;
		$result['msg'] = $gender . " " . $fname . " " . $lname . " est à jour.";
		$dbh = null;
	}
	else {
		$result['success'] = 0;
		$result['msg'] = "Erreur id !";
	}
	return $result;
};

// Fetching Values From URL

$idjoueur = $_GET['idjoueur'];
$gender = htmlspecialchars( $_GET['gender'] );
$fname	= htmlspecialchars( $_GET['fname'] );
$lname	= htmlspecialchars( $_GET['lname'] );
$phone	= htmlspecialchars( $_GET['phone'] );
$email	= htmlspecialchars( $_GET['email'] );
$res = updateJoueur( $idjoueur, $gender, $fname, $lname, $phone, $email );

echo json_encode( $res );
?>
