<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}
error_reporting(E_ALL); // Activer le rapport d'erreurs PHP

$backup_file = $dir_configs.$prefix.'importbdd.sql';
if ( file_exists( $backup_file ) ) {
	logevent("importbdd");	// table events non sauvegardée dans le backup
	$importsql = file_get_contents( $backup_file );
	$queries = explode( ";\n", $importsql );
	$nbl = sizeof( $queries );
	
	$dbh = connectBDD();
	for ( $i = 0; $i < $nbl; $i++ ) {
		if ( strlen($queries[$i]) < 1 ) continue;
		$res = $dbh->query( $queries[$i] );
	}
	$dbh = null;
	
	echo json_encode( array( 'ok'=>1, 'comment'=>" ... $nbl lignes importées !" ) );
}
else {
	echo json_encode( array( 'ok'=>0, 'comment'=>"$backup_file introuvable !" ) );
}
?>
