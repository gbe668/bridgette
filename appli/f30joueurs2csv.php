<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isDirecteur() ){
	header("Location: logdirecteur.php");
	exit(); 
}

// exporte tous les joueurs ycp les effacés vers un fichier csv

$tt = date('Y-m-d');
$strdate = strdatet( $tt );

$sql1 = "SELECT count(*) FROM $tab_joueurs where numero >= '$min_noclub';";
$sql2 = "SELECT * FROM $tab_joueurs where numero >= '$min_noclub' order by nom;";

$dbh = connectBDD();
$res = $dbh->query( $sql1 );
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$str = "$nbl joueurs à la date du $strdate" . "\n";
	$str .= "Numéro,M/Me,Prénom,Nom,Tél.,email,supp\n";

	$sth = $dbh->query( $sql2 );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$str .= $row[ 'numero' ] . ',';
		$str .= $row[ 'genre' ] . ',';
		$str .= $row[ 'prenom' ] . ',';
		$str .= $row[ 'nom' ] . ',';
		$str .= $row[ 'telephone' ] . ',';
		$str .= $row[ 'email' ] . ',';
		if ( $row[ 'datesupp' ]>0 ) $dd= date('Y-m-d', $row[ 'datesupp' ]);
		else $dd = " ";
		$str .= $dd . "\n";
	}
}
else {
	$str = 'Pas de joueurs enregistrés à la date du $tt.';
};
$dbh = null;

echo $str;
?>
