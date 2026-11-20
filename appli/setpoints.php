<?php
require("configuration.php");
require("bridgette_bdd.php");

//TABLE donnes ( id INT primary key not null auto_increment,
//    idtournoi INT, etui INT, ns INT, eo INT,
//    contrat VARCHAR(10), jouepar VARCHAR(5), entame VARCHAR(5), resultat VARCHAR(5),
//    points INT, rang float, note float );

function updatePoints( $idt, $dd, $ns, $eo, $points ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	
	// test donne déjà enregistrée
	$sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=?;";
	$sqlParams = [$idt, $dd, $ns, $eo];
	$res = securityQuery($dbh, $sql, $sqlParams);
	if ( $res->fetchColumn() > 0 ) {
		$sql = "UPDATE $tab_donnes SET points = ? where idtournoi=? and etui=? and ns=? and eo=?;";
		$sqlParams = [$points, $idt, $dd, $ns, $eo];
		$sth = securityQuery($dbh, $sql, $sqlParams);
		$display_string = "Enregistrement mis à jour.";
	}
	else {
		// donne non enregistrée
		$sql = "INSERT into $tab_donnes ( idtournoi, etui, ns, eo, contrat, jouepar, entame, resultat, points ) values ( ?, ?, ?, ?, '-', '-', '-', '-', ? );";
		$sqlParams = [$idt, $dd, $ns, $eo, $points];

		$sth = securityQuery($dbh, $sql, $sqlParams);
		$display_string = "Enregistrement terminé.";
	}
	$dbh->query("COMMIT;");
	$dbh = null;
	return $display_string;
};

// Fetching Values From URL

$idtournoi = $_GET['tournoi'];
$etui = $_GET['etui'];
$numNS = $_GET['ns'];
$numEO = $_GET['eo'];
$points = $_GET['points'];
$ok = updatePoints($idtournoi, $etui, $numNS, $numEO, $points );

echo $ok;
?>
