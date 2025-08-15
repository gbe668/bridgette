<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}

$ok = true;
//error_reporting(E_ALL); // Activer le rapport d'erreurs PHP

// Récupérer le contenu brut envoyé par fetch()
$input = file_get_contents("php://input");

// Décoder le JSON en tableau PHP
$data = json_decode($input, true);

// Vérifier si le tableau de requêtes existe
if (isset($data['queries']) && is_array($data['queries'])) {
	$queries = $data['queries'];
	$tstart = microtime(true);

    // ⚠️ IMPORTANT : NE PAS exécuter directement du SQL brut provenant du client !
    // Utilisez des requêtes préparées pour éviter les injections SQL.
    
	$dbh = connectBDD();

    foreach ($queries as $sql) {
        // Exemple d’exécution brute (dangereux si pas de contrôle préalable)
        // $pdo->exec($sql);
		$nbl = $dbh->exec( $sql );

        // Ou exécution sécurisée : reconstruire la requête avec bindParam()
        // ...
    }
	$dbh = null;
	$duration = microtime(true) - $tstart;
	$duration = intval( $duration *1000)/1000;

    echo json_encode(["ok" => true, "message" => count($queries)." requêtes exécutées en $duration secondes!"]);
}
else {
    echo json_encode(["ok" => false, "message" => "Format invalide"]);
}



/*

try {
	$tstart = time();
	$dbh = connectBDD();
	for ( $i=0; $i<$nlines; $i++ ) {
		//$sql = $job['sql'][i];
		//$nbl = $dbh->exec( $sql );
	}
	$dbh = null;
	
	$duration = intval( time() - $tstart );
	$message = " ... import partiel réalisé en $duration secondes!";
	$ok = true;
}
catch (Exception $e) {
	$message = 'Exception reçue : '.$e->getMessage();
	$ok = false;
}
*/
//echo json_encode( array( 'ok' => $ok, 'message' => $message ) );
?>
