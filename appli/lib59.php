<?php
//
// routines calcul de la marque
//
function print_table_contrat_calcul() {
	global $relimg;
	print '<table style="width:100%; max-width: 350px; margin:auto;"><tbody>';

	for  ($i = 1; $i < 8; $i++) {
		$idc5 = "niv_" . $i . "_5";
		$idc4 = "niv_" . $i . "_4";
		$idc3 = "niv_" . $i . "_3";
		$idc2 = "niv_" . $i . "_2";
		$idc1 = "niv_" . $i . "_1";
		print "<tr>";
		print "<td class='xNum2'><div id='" . $idc5 . "'>".$i." <img id='" . $idc5 . "' src='".$relimg."sans-atout.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='" . $idc4 . "'>".$i." <img id='" . $idc4 . "' src='".$relimg."pique.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='" . $idc3 . "'>".$i." <img id='" . $idc3 . "' src='".$relimg."coeur.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='" . $idc2 . "'>".$i." <img id='" . $idc2 . "' src='".$relimg."carreau.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='" . $idc1 . "'>".$i." <img id='" . $idc1 . "' src='".$relimg."trefle.png' height='17' /></div></td>";
		print '</tr>';
		}
		
	print '<tr>';
	print '<td class="xPasse" colspan="3"><div id="vul_0">Non vulnérable</div></td>';
	print '<td class="xPasse" colspan="2"><div id="vul_1">Vulnérable</div></td>';
	print '</tr><td>&nbsp;</td>';
	print '<td class="xNum2"><div id="dbl_1" class="bonnumero">-</div></td>';
	print '<td class="xNum2"><div id="dbl_2">X</div></td>';
	print '<td class="xNum2"><div id="dbl_3">XX</div></td>';
	print '<td>&nbsp;</td></tr>';
	print '</tbody></table>';
}
function print_table_section_resultat() {
	print '<table style="width:100%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print '<tr>';
	print '<td class="xNum2"><div id="res_p_0">=</div></td>';
	print '<td class="xNum2" id="dsp_p1"><div id="res_p_1">+1</div></td>';
	print '<td class="xNum2" id="dsp_p2"><div id="res_p_2">+2</div></td>';
	print '<td class="xNum2" id="dsp_p3"><div id="res_p_3">+3</div></td>';
	print '<td class="xNum2" id="dsp_p4"><div id="res_p_4">+4</div></td>';
	print '</tr>';
	print '<tr id="dsp_p5">';
	print '<td class="xNum2" id="dsp_p5"><div id="res_p_5">+5</div></td>';
	print '<td class="xNum2" id="dsp_p6"><div id="res_p_6">+6</div></td>';
	print '</tr>';
	print '<tr><td>&nbsp;</td></tr>';
	print '<tr>';
	print '<td class="xNum2"><div id="res_m_1">-1</div></td>';
	print '<td class="xNum2"><div id="res_m_2">-2</div></td>';
	print '<td class="xNum2"><div id="res_m_3">-3</div></td>';
	print '<td class="xNum2"><div id="res_m_4">-4</div></td>';
	print '<td class="xNum2"><div id="res_m_5">-5</div></td>';
	print '</tr>';
	print '<tr id="dsp_m6">';
	print '<td class="xNum2" id="dsp_m6"><div id="res_m_6">-6</div></td>';
	print '<td class="xNum2" id="dsp_m7"><div id="res_m_7">-7</div></td>';
	print '<td class="xNum2" id="dsp_m8"><div id="res_m_8">-8</div></td>';
	print '<td class="xNum2" id="dsp_m9"><div id="res_m_9">-9</div></td>';
	print '<td class="xNum2" id="dsp_m10"><div id="res_m_10">-10</div></td>';
	print '</tr>';
	print '<tr id="dsp_m11">';
	print '<td class="xNum2" id="dsp_m11"><div id="res_m_11">-11</div></td>';
	print '<td class="xNum2" id="dsp_m12"><div id="res_m_12">-12</div></td>';
	print '<td class="xNum2" id="dsp_m13"><div id="res_m_13">-13</div></td>';
	print '</tr>';
	print '</tbody>';
	print '</table>';
}

function print_grille_calcul_marque() {
	print_table_contrat_calcul();
	print '<h2 style="text-align: center">Entrez le résultat</h2>';
	print_table_section_resultat();
	print '<p id="textePoints"><span style="font-size:1.3em"<b>Points NS ?</b></span></p>';
}
?>
