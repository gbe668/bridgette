<?php
function displayLignesNS( $idt, $tit, $n, $add ) {
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
	if ( $add > 0 ) {
		print '<tr>';
		print '<td  colspan="3" class="xNumero"  onclick="addligneNS()">Ajouter une paire</td>';
		print '</tr>';
	}
	print "</tbody></table>";
};
function displayLignesEO( $idt, $tit, $n, $add ) {
	print '<table border="1" style="width:90%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print "<tr class='xtr'><td class='xTitre'>$tit</td><td class='xTitre xtd_invisible'>N°club</td><td class='xTitre'>Nom</td></tr>";
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
	if ( $add > 0 ) {
		print '<tr>';
		print '<td  colspan="3" class="xNumero" onclick="addligneEO()">Ajouter une paire</td>';
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
			displayLignesNS( $idt, "NS", $pns, 0 );
			print '</div>';
			print '</td><td style="width:45%;">';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idt, "EO", $peo, 0 );
			print '</div>';
			print '</td></tr></tbody></table>';
		}
		else {
			// les tableaux sont l'un en dessous de l'autre
			print '<div id="section_tableauns"><h3>Tableau des paires Nord-Sud</h3>';
			displayLignesNS( $idt, "NS", $pns, 0 );
			print '</div>';
			print '<div id="section_tableaueo"><h3>Tableau des paires Est-Ouest</h3>';
			displayLignesEO( $idt, "EO", $peo, 0 );
			print '</div>';
		}
	}
	else {
		displayLignesNS( $idt, "N°", $pns, 0 );
	}
}
function displayClavierSaisieJoueur() {
	print '<table style="width:100%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print '<tr>';
	print '<td colspan="2" class="xNum2 clklet" id="n_ko"><img src="images/ko.png" alt="ko" height="30" class="clklet"/></td>';
	print '<td>&nbsp;</p>';
	print '<td colspan="3" class="xNum2 clklet" id="n_gt">Invité</td>';
	print '<td colspan="4" class="xNum2 clklet" id="n_nv">Nouveau</td>';
	print '</tr>';
	print '<tr id="efface" class="xtr_invisible">';
	print '<td>&nbsp;</p>';
	print '<td>&nbsp;</p>';
	print '<td>&nbsp;</p>';
	print '<td colspan="7" class="xNum2 xNumSmall"><div onclick="enleveJoueur()">Efface joueur en place</div></td>';
	print '</tr>';
	print '<tr><td colspan="10" class="xDigit"><div id="btnAlphabetic">&nbsp;</div></td></tr>';
	print '<tr>';
	print '<td colspan="10" class="xNum">';
	
	print '<table style="width:100%; margin:auto;"><tbody>';
	for  ($i = 0; $i < 10; $i++) {
		$nr = "nr_" . $i;
		$ndnum = "num_" . $i;
		$ndnom = "nom_" . $i;
		print '<tr id="' . $nr . '" class="xtrsel xtr_invisible">';
		print '<td class="xTxt2 xtd_invisible" id="' . $ndnum . '">numéro</td>';
		print '<td class="xTxt2 clkrow" id="' . $ndnom . '">nom du joueur</td>';
		print '</tr>';
		};
	print "</tbody></table>";
	
	print '</td>';
	print '</tr>';
	print '<tr><td colspan="10"><span id="msgclavier">&nbsp;</span></td></tr>';
	print '<tr>';
	print '<td class="xNum2 clklet" id="n_a">a</td>';
	print '<td class="xNum2 clklet" id="n_z">z</td>';
	print '<td class="xNum2 clklet" id="n_e">e</td>';
	print '<td class="xNum2 clklet" id="n_r">r</td>';
	print '<td class="xNum2 clklet" id="n_t">t</td>';
	print '<td class="xNum2 clklet" id="n_y">y</td>';
	print '<td class="xNum2 clklet" id="n_u">u</td>';
	print '<td class="xNum2 clklet" id="n_i">i</td>';
	print '<td class="xNum2 clklet" id="n_o">o</td>';
	print '<td class="xNum2 clklet" id="n_p">p</td>';
	print '</tr>';
	print '<tr>';
	print '<td class="xNum2 clklet" id="n_q">q</td>';
	print '<td class="xNum2 clklet" id="n_s">s</td>';
	print '<td class="xNum2 clklet" id="n_d">d</td>';
	print '<td class="xNum2 clklet" id="n_f">f</td>';
	print '<td class="xNum2 clklet" id="n_g">g</td>';
	print '<td class="xNum2 clklet" id="n_h">h</td>';
	print '<td class="xNum2 clklet" id="n_j">j</td>';
	print '<td class="xNum2 clklet" id="n_k">k</td>';
	print '<td class="xNum2 clklet" id="n_l">l</td>';
	print '<td class="xNum2 clklet" id="n_m">m</td>';
	print '</tr>';
	print '<tr>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td class="xNum2 clklet" id="n_w">w</td>';
	print '<td class="xNum2 clklet" id="n_x">x</td>';
	print '<td class="xNum2 clklet" id="n_c">c</td>';
	print '<td class="xNum2 clklet" id="n_v">v</td>';
	print '<td class="xNum2 clklet" id="n_b">b</td>';
	print '<td class="xNum2 clklet" id="n_n">n</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';
	print '<tr>';
	print '<td colspan="2" class="xNum2 clklet" id="n_cl">Clear</td>';
	print '<td>&nbsp;</td>';
	print '<td colspan="4" class="xNum2 clklet" id="n_space">espace</td>';
	print '<td>&nbsp;</td>';
	print '<td colspan="2" class="xNum2 clklet" id="n_bs">&larr;</td>';
	print '</tr>';
	print '</tbody>';
	print '</table>';
};
?>