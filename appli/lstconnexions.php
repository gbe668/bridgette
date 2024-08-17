<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = $_GET['idtournoi'];

$col1 = array();
$col2 = array();
$col3 = array();
$col4 = array();
$col5 = array();

$dbh = connectBDD();
$t = _readTournoi( $dbh, $idtournoi );
$n = $t['ntables'];

$sth = $dbh->query( "SELECT * FROM $tab_connexions;" );
for ( $i = 0; $i < $max_tables; $i++ ) {
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$col1[$i] = $row['stconnexion'];
	$col2[$i] = $row['numEO'];
	$col3[$i] = $row['numdonne'];
	$col4[$i] = $row['cpt'];
	$col5[$i] = $row['rdy'];
}
$sth = $dbh->query( "SELECT MIN(rdy) FROM $tab_connexions where id<=$n;" );
$mintour = $sth->fetchColumn();
$dbh = null;

echo json_encode( array( 'stconn'=>$col1, 'numEO'=>$col2, 'numdonne'=>$col3, 'cpt'=>$col4, 'pos'=>$col5, 'ntables'=>$n, 'mintour'=>$mintour ) );

?>
