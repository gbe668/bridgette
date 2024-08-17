<?php
//
// fonction utilisé par mitch61 et howell51
//
function htmlTablesdisponibles( $idtournoi, $maxlignesNS ) {
	global $tab_connexions;
	global $cnx_ko, $cnx_ok, $cnx_fin;
	$dbh = connectBDD();
	$sth = $dbh->query( "SELECT * FROM $tab_connexions;" );
	for  ($i = 0; $i < $maxlignesNS; $i++) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$stconn[$i] = $row[ 'stconnexion' ];
		$numeo[$i] = $row[ 'numEO' ];
		$numdonne[$i] = $row[ 'numdonne' ];
		$cpt[$i] = $row[ 'cpt' ];
	}
	$dbh = null;
	
	$tab  = '<table border="0" style="margin:auto;"><tbody>';
	$tab .= '<tr class="xtr61">';
	$tab .= '<td class="xTitre61" style="min-width:200px;">Paire NS</td>';
	$tab .= '<td class="xTitre61" style="width:50px;">N°<br/>paire</td>';
	$tab .= '<td class="xTitre61" style="width:50px;">N</td>';
	$tab .= '</tr>';
	
	for ($i = 1; $i < $maxlignesNS+1; $i++) {
		$ligne = getligneNS( $idtournoi, $i );
		
		$stcnx = intval( $stconn[$i-1] );
		$stcol = "";
		if ( $stcnx == $cnx_fin ) $stcol = "xCnxtelOk";
		$np = "np_" . $i;
		$nr = "nr_" . $i;
		$tab .= '<tr id="' . $nr . '" class="xtrsel">';
		$tab .= '<td id="' . $nr . '" class="xNom61">' . $ligne['N']['nomcomplet'] . '</br>' . $ligne['S']['nomcomplet'] . '</td>';
		$tab .= '<td class="numpaire ' . $stcol . '" id="' . $np . '">' . $i . '</td>';
		$tab .= '<td id="' . $nr . '" class="xNom61">' . $cpt[$i-1] . '</td>';
		$tab .= '</tr>';
		$tab .= '<tr></tr>';
		}
	$tab .= "</tbody></table>";
	return $tab;
};
function toggleDisplayPaires($idtournoi, $genre, $pairesNS, $pairesEO) {
	print '<div id="section_tableaux" class="section_invisible">';
	print '<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>';
	displayPaires($idtournoi, $genre, $pairesNS, $pairesEO, 100);
	print '</div>';
	print '<p><button onclick="toggleAffichagePaires()">Affiche / masque les paires</button></p>';
}
function toggleAvancementTables($idtournoi, $pairesNS) {
	print '<div id="section_tableaux" class="section_invisible">';
	print "<p><button onclick='toggleAvancementTables()'>Affiche / masque le nombre</br>de donnes jouées par table</button></p>";
	print htmlTablesdisponibles( $idtournoi, $pairesNS );
	print '</div>';
	print "<p><button onclick='toggleAvancementTables()'>Affiche / masque le nombre</br>de donnes jouées par table</button></p>";
}
?>