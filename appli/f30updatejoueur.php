<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

function updateJoueur( $idj, $gender, $fname, $lname, $email ) {
	global $tab_joueurs;
	if ( $idj ) {
		$dbh = connectBDD();
		$joueur = $gender . " " . strtoupper( $lname );
		$sql = "UPDATE $tab_joueurs SET joueur='$joueur' , genre='$gender', prenom='$fname', nom='$lname', email='$email' where id='$idj';";
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
$gender = $_GET['gender'];
$fname = $_GET['fname'];
$lname = $_GET['lname'];
$email = $_GET['email'];
$res = updateJoueur( $idjoueur, $gender, $fname, $lname, $email );

echo json_encode( $res );
?>
