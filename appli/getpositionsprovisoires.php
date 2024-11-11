<?php
require("configuration.php");
require("bridgette_bdd.php");

function displayProvisoiresNS( $idt, $tit ) {
	global $max_tables;
	$nblignesNS = maxNumPaireNS( $idt );	// valeur provenant de la table pairesNS
	$str = '<table style="width:90%; max-width: 350px; margin:auto;">';
	$str .= '<tbody>';
	$str .= "<tr><th class='xNum3' style='width:20%;'>$tit</th><th class='xNum3'>Noms</th></tr>";
	for  ($i = 1; $i < $nblignesNS+1; $i++) {
		$ligne = getligneNS( $idt, $i );
		
		$str .= '<tr class="xNum3">';
		$str .= '<td class="xNum3">'. $i .'</td>';
		$str .= '<td class="xNum3">' . $ligne['N']['nomcomplet'] . '</br>';
		$str .= $ligne['S']['nomcomplet'] . '</td>';
		$str .= '</tr>';
		};
	$str .= "</tbody></table>";
	return $str;
};
function displayProvisoiresEO( $idt, $tit ) {
	global $max_tables;
	$nblignesEO = maxNumPaireEO( $idt );	// valeur provenant de la table pairesEO
	$str = '<table style="width:90%; max-width: 350px; margin:auto;">';
	$str .= '<tbody>';
	$str .= "<tr><th class='xNum3' style='width:20%;'>$tit</th><th class='xNum3'>Noms</th></tr>";
	for  ($i = 1; $i < $nblignesEO+1; $i++) {
		$ligne = getligneEO( $idt, $i );
		
		$str .= '<tr class="xNum3">';
		$str .= '<td class="xNum3">'. $i .'</td>';
		$str .= '<td class="xNum3">' . $ligne['E']['nomcomplet'] . '</br>';
		$str .= $ligne['O']['nomcomplet'] . '</td>';
		$str .= '</tr>';
		}
	$str .= "</tbody></table>";
	return $str;
};

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$screenw = htmlspecialchars( $_GET['w'] );

$dbh = connectBDD();
$t = _readTournoi( $dbh, $idtournoi );
$etat = $t[ 'etat' ];

if ( $etat == $st_phase_init ) {
	if ( $t['genre'] == $t_howell ) {
		// Un seul tableau
		$str = '<div><h3>N° paires Howell (provisoire)</h3>';
		$str .= displayProvisoiresNS( $idtournoi, "N°" );
		$str .= '</div>';
	}
	else {
		$twocols = ( $screenw > $parametres['maxw'] ) ? true : false;
		$str = "<div>";
		if ( $twocols ) {
			// affichage des tableaux côte à côte
			$str = "<table style='margin:auto;'><tbody><tr><td style='width:45%;'>";
		}
		// 1er tableau
		$str .= '<h3>Paires NS Mitchell (provisoire)</h3>';
		$str .= displayProvisoiresNS( $idtournoi, "NS" );

		if ( $twocols ) {
			$str .= "</td><td style='width:3%;'></td><td style='width:45%;'>";
		}
		$str .= '<h3>Paires EO Mitchell (provisoire)</h3>';
		$str .= displayProvisoiresEO( $idtournoi, "EO" );
		if ( $twocols ) {
			$str .= '</td></tr></tbody></table>';
		}
		$str .= "</div>";
	}
}
else {
	$str = "attendez";
}
$dbh = null;
echo json_encode( array( 'etat'=>$etat, 'positions'=>$str ) );
?>
