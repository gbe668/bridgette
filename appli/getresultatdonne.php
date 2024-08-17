<?php
require("configuration.php");
require("bridgette_bdd.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$etui = htmlspecialchars( $_GET['z_etui'] );
	
$tab1 = array();
$tab2 = array();
$tab3 = array();
$tab4 = array();
$tab5 = array();
$tab6 = array();
$tab7 = array();
$tab8 = array();
$tab9 = array();
$tab10 = array();

$dbh = connectBDD();

$row = getetui( $etui );
$vulns = $row[ 'vulns' ];
$vuleo = $row[ 'vuleo' ];
$info = getinfoetui( $etui );

// resultats donnes
$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idtournoi' and etui='$etui';";
$res = $dbh->query($sql);
$nbl = $res->fetchColumn();
if ( $nbl > 0 ) { 
	$sth = $dbh->query( "SELECT * FROM $tab_donnes where idtournoi = '$idtournoi' and etui='$etui' order by ns;" );
	for ( $i = 0; $i < $nbl; $i++ ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$tab1[$i] = $row['ns'];
		$tab2[$i] = $row['eo'];
		$tab3[$i] = $row['contrat'];
		$tab4[$i] = $row['jouepar'];
		$tab5[$i] = $row['entame'];
		$tab6[$i] = $row['resultat'];
		$tab7[$i] = $row['points'];
		$tab8[$i] = $row['rang'];
		$tab9[$i] = $row['note'];
		$tab10[$i] = $row['hweo'];
	}
};
$dbh = null;

echo json_encode( array( 'nbl'=>$nbl, 'info'=>$info, 'vulns'=>$vulns, 'vuleo'=>$vuleo, 'ns'=>$tab1, 'eo'=>$tab2, 'contrat'=>$tab3, 'jouepar'=>$tab4, 'entame'=>$tab5, 'resultat'=>$tab6, 'points'=>$tab7, 'rang'=>$tab8, 'note'=>$tab9, 'hweo'=>$tab10 ) );
?>
