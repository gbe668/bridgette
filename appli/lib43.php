<?php
function displayLignesNS( $idt, $tit, $n ) {
	print '<table border="1" style="width:90%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print "<tr class='xtr'><td class='xTitre'>$tit</td><td class='xTitre xtd_invisible'>N°club</td><td class='xTitre'>Nom</td></tr>";
	for  ($i = 1; $i < $n+1; $i++) {
		$nsi= "NS" . $i;
		$ligne = getligneNS( $idt, $i );
		$rownsia = "rowns" . $i . "a";
		$rownsib = "rowns" . $i . "b";
		
		print '<tr id="' . $rownsia . '" class="xtr">';
		print '<td rowspan="2" class="xPaire" id="'. $nsi . '">'. $i .'</td>';
		print '<td class="xNumero xtd_invisible" id="idnum_' . $i . '_1">' . $ligne['N']['numero'] . '</td>';
		print '<td class="xNom clknom" id="idnom_' . $i . '_1">' . $ligne['N']['nomcomplet'] . '</td>';
		print '</tr>';
		print '<tr id="' . $rownsib . '" class="xtr">';
		print '<td class="xNumero xtd_invisible" id="idnum_' . $i . '_3">' . $ligne['S']['numero'] . '</td>';
		print '<td class="xNom clknom" id="idnom_' . $i . '_3">' . $ligne['S']['nomcomplet'] . '</td>';
		print '</tr>';
		};
	print "</tbody></table>";
};
function displayLignesEO( $idt, $n ) {
	print '<table border="1" style="width:90%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print '<tr class="xtr"><td class="xTitre">EO</td><td class="xTitre xtd_invisible">N°club</td><td class="xTitre">Nom</td></tr>';
	for  ($i = 1; $i < $n+1; $i++) {
		$eoi= "EO" . $i;
		$ligne = getligneEO( $idt, $i );
		$roweoia = "roweo" . $i . "a";
		$roweoib = "roweo" . $i . "b";
		
		print '<tr id="' . $roweoia . '" class="xtr">';
		print '<td rowspan="2" class="xPaire" id="'. $eoi . '">'. $i .'</td>';
		print '<td class="xNumero xtd_invisible" id="idnum_' . $i . '_2">' . $ligne['E']['numero'] . '</td>';
		print '<td class="xNom clknom" id="idnom_' . $i . '_2">' . $ligne['E']['nomcomplet'] . '</td>';
		print '</tr>';
		print '<tr id="' . $roweoib . '" class="xtr">';
		print '<td class="xNumero xtd_invisible" id="idnum_' . $i . '_4">' . $ligne['O']['numero'] . '</td>';
		print '<td class="xNom clknom" id="idnom_' . $i . '_4">' . $ligne['O']['nomcomplet'] . '</td>';
		print '</tr>';
		}
	print "</tbody></table>";
};
function displayPaires($idt, $genre, $pns, $peo, $screenw) {
	global $t_mitchell, $parametres;
	if ( $genre == $t_mitchell ) {
		// type Mitchell
		if ( $screenw > $parametres['maxw'] ) {
			// les tableaux sont cote à cote
			print '<table style="width:90%; margin:auto;"><tbody><tr><td style="width:45%;">';
			print '<div id="section_tableauns"><h3>Tableau des paires Nord-Sud</h3>';
			displayLignesNS( $idt, "NS", $pns );
			print '</div>';
			print '</td><td style="width:45%;">';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idt, $peo );
			print '</div>';
			print '</td></tr></tbody></table>';
		}
		else {
			// les tableaux sont l'un en dessous de l'autre
			print '<div id="section_tableauns"><h3>Tableau des paires Nord-Sud</h3>';
			displayLignesNS( $idt, "NS", $pns );
			print '</div>';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idt, $peo );
			print '</div>';
		}
	}
	else {
		displayLignesNS( $idt, "N°", $pns );
	}
}
?>