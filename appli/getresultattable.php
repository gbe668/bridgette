<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$ns = htmlspecialchars( $_GET['table'] );
$eo = htmlspecialchars( $_GET['adversaire'] );
	
$tab0 = array();
$tab1 = array();
$tab2 = array();
$tab3 = array();
$tab4 = array();
$tab5 = array();
$tab6 = array();
$tab7 = array();
$tab8 = array();
$tab9 = array();

$dbh = connectBDD();

// resultats de la table spécifiée
if ( $eo > 0 ) {
	// avec la paire eo spécifiée
	$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idtournoi' and ns='$ns' ans eo='$eo';";
}
else {
	// tous les résultats de la table
	$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idtournoi' and ns='$ns';";
}
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) {
	if ( $eo > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_donnes where idtournoi = '$idtournoi' and ns='$ns' ans eo='$eo' order by etui;" );
	}
	else {
		$sth = $dbh->query( "SELECT * FROM $tab_donnes where idtournoi = '$idtournoi' and ns='$ns' order by etui;" );
	}
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$tab0[$i] = $row['id'];
		$tab1[$i] = $row['etui'];
		$tab2[$i] = $row['eo'];
		$tab3[$i] = $row['contrat'];
		$tab4[$i] = $row['jouepar'];
		$tab5[$i] = $row['entame'];
		$tab6[$i] = $row['resultat'];
		$tab7[$i] = $row['points'];
		//$tab8[$i] = $row['rang'];
		//$tab9[$i] = $row['note'];
	}
};
$dbh = null;

echo json_encode( array( 'nbl'=>$nbl, 'id'=>$tab0, 'etui'=>$tab1, 'eo'=>$tab2, 'contrat'=>$tab3, 'jouepar'=>$tab4, 'entame'=>$tab5, 'resultat'=>$tab6, 'points'=>$tab7, 'rang'=>$tab8, 'note'=>$tab9 ) );
?>
