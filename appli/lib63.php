<?php
$Paquet2Play = Array();
$Donnes2Play = Array();
function getPaquet2Play( $idt, $ns, $encours, $paquet ) {
	global $tab_donnes, $Paquet2Play, $Donnes2Play;
	$first = intval(($encours-1)/$paquet)*$paquet +1;
	
	$dbh = connectBDD();
	for ( $i = 0; $i < $paquet; $i++ ) {
		$j = $first + $i;
		$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and ns='$ns' and etui='$j';";
		$res = $dbh->query($sql);
		$Paquet2Play[$i] = $res->fetchColumn();
		$Donnes2Play[$i] = $j;
	}
	$dbh = null;
}

$strFirst2play = "<p style='color:red'><b>Attention</b></br>Vous êtes les premiers à jouer cet étui,<br/><b>mélangez</b> les cartes et <b>distribuez</b></p>";

function infoDonneTournoi($n, $nmax, $p, $pmax) {
	if ( $n == 1) $strhtml = "<p>1ère";
	else $strhtml = "<p>".$n."ème";
	$strhtml .= " donne/$nmax - Tour n°<span class='notour'>$p</span>/$pmax</p>";
	return $strhtml;
}
function findFirst2Play() {
	global $Paquet2Play;
	for ( $i = 0; $i < sizeof($Paquet2Play); $i++ ) {
		if ( $Paquet2Play[$i] == 0 ) return $i;
	}
	return -1;
}
function findFirst2PlayAfter($first, $last) {
	global $Paquet2Play, $Donnes2Play;
	// recherche last dans le paquet de donnes à jouer
	$found = -1;
	for ( $i = 0; $i < sizeof($Donnes2Play); $i++ ) {
		if ( $Donnes2Play[$i] == $last ) {
			$found = $i;
			break;
		}
	}
	if ( $found < 0 ) {
		// non trouvé
		for ( $i = 0; $i < sizeof($Paquet2Play); $i++ ) {
			if ( $Paquet2Play[$i] == 0 ) return $first+$i;
		}
		return -1;
	}
	else {
		// trouvé
		for ( $i = 0; $i < sizeof($Paquet2Play); $i++ ) {
			$j = ($i + $found)%sizeof($Paquet2Play);
			if ( $Paquet2Play[$j] == 0 ) return $first+$j;
		}
		return -1;
	}
}
function showListeEtuis( $first, $paquet ) {
	global $Paquet2Play;
	$strhtml = "<table style='margin:auto;'><tbody><tr>";
	//$strhtml .= "<td>Etuis&nbsp;</td>";
	for ($i=0;$i<$paquet;$i++) {
		$etui = $first + $i;
		if ( $Paquet2Play[$i] > 0 ) $bg="kolight";
		else $bg = "oklight";
		$strhtml .= "<td style='border:.5pt solid windowtext; min-width:30px;' class='$bg'>$etui</td>";
	}
	$strhtml .= "</tr></tbody></table>";
	return $strhtml;
}
function showPaquet2play( $first, $paquet ) {
	global $Paquet2Play;
	$strhtml = "<div id='section_etuis' class='section_invisible'>";
	$strhtml .= "<table style='margin:auto;'><tbody><tr>";
	for ( $i = 0; $i < $paquet; $i++ ) {
		$j = $first + $i;
		if ( $Paquet2Play[$i] > 0 ) {
			$strhtml .= "<td class='numetui2 kolight'>$j</td>";
		}
		else {
			$id = "etui_".$j;
			$strhtml .= "<td class='numetui2 oklight' id='".$id."'>$j</td>";
		}
	}
	$strhtml .= "</tr></tbody></table>";
	$strhtml .= "</div>";
	return $strhtml;
}

function print_table_section_contrat_jouepar() {
	global $relimg, $contratNJ;
	
	print '<table style="width:100%; max-width: 350px; margin:auto;"><tbody>';
	print '<tr><td>&nbsp;</td>';
	print '<td class="xPasse" colspan="3"><div id="pas_0_passe">passe général</div></td>';
	//print '<td><div id="dnj_0_nonjoue">&nbsp;</div></td></tr>';
	print '<td class="xPasse"><em><div id="dnj_0_nonjoue">'.$contratNJ.'</div></em></td></tr>';

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
		
	print '<tr><td>&nbsp;</td>';
	print '<td class="xNum2"><div id="dbl_1" class="bonnumero">-</div></td>';
	print '<td class="xNum2"><div id="dbl_2">X</div></td>';
	print '<td class="xNum2"><div id="dbl_3">XX</div></td>';
	print '<td>&nbsp;</td></tr>';
	print '<tr><td>&nbsp;</td></tr>';
	
	print '<tr><td class="xJouepar">par</td>';
	print '<td class="xPasse"><div id="pos_1">Nord</div></td>';
	print '<td class="xPasse"><div id="pos_2">Est</div></td>';
	print '<td class="xPasse"><div id="pos_3">Sud</div></td>';
	print '<td class="xPasse"><div id="pos_4">Ouest</div></td>';
	print '</tr></tbody></table>';
}
function print_table_section_entame() {
	global $relimg;
	
	$figures = Array( "0","0","2","3","4","5","6","7","8","9","10","V","D","R","As" );
	print "<table style='width:100%; max-width: 350px; margin:auto;'>";
	print "<tbody>";
	for  ($i = 14; $i > 1; $i--) {
		$idc4 = "ent_" . $i . "_4";
		$idc3 = "ent_" . $i . "_3";
		$idc2 = "ent_" . $i . "_2";
		$idc1 = "ent_" . $i . "_1";
		$figure = $figures[$i];

		print "<tr>";
		print "<td class='xNum2'><div id='".$idc4."'>".$figure."<img id='".$idc4."' src='".$relimg."pique.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='".$idc3."'>".$figure."<img id='".$idc3."' src='".$relimg."coeur.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='".$idc2."'>".$figure."<img id='".$idc2."' src='".$relimg."carreau.png' height='17' /></div></td>";
		print "<td class='xNum2'><div id='".$idc1."'>".$figure."<img id='".$idc1."' src='".$relimg."trefle.png' height='17' /></div></td>";
		print "</tr>";
	}
	print "</tbody>";
	print "</table>";
}
function print_table_section_resultat() {
	print '<table style="width:90%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print '<tr>';
	print '<td class="xNum2"><div id="res_p_0">=</div></td>';
	print '</tr>';
	print '<tr><td>&nbsp;</td></tr>';
	print '<tr>';
	print '<td class="xNum2" id="dsp_p1"><div id="res_p_1">+1</div></td>';
	print '<td class="xNum2" id="dsp_p2"><div id="res_p_2">+2</div></td>';
	print '<td class="xNum2" id="dsp_p3"><div id="res_p_3">+3</div></td>';
	print '<td class="xNum2" id="dsp_p4"><div id="res_p_4">+4</div></td>';
	print '<td class="xNum2" id="dsp_p5"><div id="res_p_5">+5</div></td>';
	print '</tr>';
	print '<tr id="dsp_p6">';
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
function print_tables_saisie_contrat() {
	print '<p><button class="myButton" id="contrat1" onClick="realdonne.openContrat()">Entrez le contrat</button></p>';
	print '<div id="section_contrat" class="section_invisible">';
	print_table_section_contrat_jouepar();
	print '<p><input type="button" class="okButton" value="OK" id="ok_contrat" onClick="realdonne.closeContrat()"></p>';
	print '</div>';

	// section masquée pour PG et NJ
	print '<div id="section_contrat_joue">';
	
	print '<p><button class="myButton" id="entame1" onClick="realdonne.openEntame()">Entrez l\'entame</button></p>';
	print '<div id="section_entame" class="section_invisible">';
	print_table_section_entame();
	print '<p><input type="button" class="okButton" value="OK" id="ok_entame" onClick="realdonne.closeEntame()"></p>';
	print '</div>';
	
	print '<p><button class="myButton" id="resultat1" onClick="realdonne.openResultat()">Entrez le résultat</button></p>';
	print '<div id="section_resultat" class="section_invisible">';
	print_table_section_resultat();
	print '<p><input type="button" class="okButton" value="OK" id="ok_resultat" onClick="realdonne.closeResultat()"></p>';
	print '</div>';
	
	print '</div>';	
}
function print_section_diagramme() {
	global $relimg;

	print '<div id="section_diagramme" class="section_invisible">';
	print '<table border="1" style="width:100%; max-width: 350px; margin:auto; border-collapse: collapse;" class="notranslate"><tbody>';
    print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	print '<td style="width:15%;"><img src="'.$relimg.'pique.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_1">&nbsp;</td></tr>';
	print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	print '<td><img src="'.$relimg.'coeur.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_2">&nbsp;</td></tr>';
	print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	print '<td><img src="'.$relimg.'carreau.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_3">&nbsp;</td></tr>';
	print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	print '<td><img src="'.$relimg.'trefle.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_4">&nbsp;</td></tr>';
	
    print '<tr><td style="width:10%;"><img src="'.$relimg.'pique.png" height="18" style="vertical-align:middle" /></td>';
	print '<td class="xsmallDigit" id="ligne_13">&nbsp;</td>';
	print '<td id="diagrowtop" style="border:2pt solid windowtext; border-bottom:none;">Nord</td>';
	print '<td style="width:10%;"><img src="'.$relimg.'pique.png" height="18" style="vertical-align:middle" /></td>';
	print '<td style="width:35%;" class="xsmallDigit" id="ligne_5">&nbsp;</td></tr>';
	
	print '<tr><td><img src="'.$relimg.'coeur.png" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_14">&nbsp;</td>';
	print '<td id="diagrowmid1" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	print '<td><img src="'.$relimg.'coeur.png" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_6">&nbsp;</td></tr>';
	
	print '<tr><td><img src="'.$relimg.'carreau.png" alt="carreau" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_15">&nbsp;</td>';
	print '<td id="diagrowmid2" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	print '<td><img src="'.$relimg.'carreau.png" alt="carreau" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_7">&nbsp;</td></tr>';
	
	print '<tr><td><img src="'.$relimg.'trefle.png" alt="trefle" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_16">&nbsp;</td>';
	print '<td id="diagrowbottom" style="border:2pt solid windowtext;
	border-top:none">Sud</td>';
	print '<td><img src="'.$relimg.'trefle.png" alt="trefle" height="18" /></td>';
	print '<td class="xsmallDigit" id="ligne_8">&nbsp;</td></tr>';
	
    print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	print '<td><img src="'.$relimg.'pique.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_9">&nbsp;</td></tr>';
	print '<tr><td rowspan="3" colspan="2">';
		print '<table border="1" style="margin:auto;" id="points_honneurs" hidden><tbody>';
		print '<tr><td>&nbsp;</td><td id="ph_nord" class="phonn">NN</td><td>&nbsp;</td></tr>';
		print '<tr><td id="ph_ouest" class="phonn">OO</td><td>&nbsp;</td><td id="ph_est" class="phonn">EE</td></tr>';
		print '<tr><td>&nbsp;</td><td id="ph_sud" class="phonn">SS</td><td>&nbsp;</td></tr>';
		print '</tbody></table>';
	print '</td>';
	print '<td><img src="'.$relimg.'coeur.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_10">&nbsp;</td></tr>';
	print '<tr>';
	print '<td><img src="'.$relimg.'carreau.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_11">&nbsp;</td></tr>';
	print '<tr>';
	print '<td><img src="'.$relimg.'trefle.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_12">&nbsp;</td></tr>';
	
    print '</tbody></table>';
	print '</div>';
}
function print_clavier_diagramme() {
	print "<table style='width:100%; max-width: 350px; margin:auto;'><tbody>";
	print "<tr>";
	print '<td rowspan="2" class="xNum65" id="ok_up" style="font-size: 3em;">▲</td>';
	print '<td width="20pts">&nbsp;</td>';
	print '<td class="xNum65"><div id="n_0">As</div></td>';
	print '<td class="xNum65"><div id="n_1">R</div></td>';
	print '<td class="xNum65"><div id="n_2">D</div></td>';
	print '<td class="xNum65"><div id="n_3">V</div></td>';
	print "</tr>";
	print "<tr>";
	print "<td>&nbsp;</td>";
	print '<td class="xNum65"><div id="n_4">10</div></td>';
	print '<td class="xNum65"><div id="n_5">9</div></td>';
	print '<td class="xNum65"><div id="n_6">8</div></td>';
	print '<td class="xNum65"><div id="n_7">7</div></td>';
	print "</tr>";
	print "<tr>";
	print '<td rowspan="2" class="xNum65" id="ok_down" style="font-size: 3em;">▼</td>';
	print "<td>&nbsp;</td>";
	print '<td class="xNum65"><div id="n_8">6</div></td>';
	print '<td class="xNum65"><div id="n_9">5</div></td>';
	print '<td class="xNum65"><div id="n_10">4</div></td>';
	print '<td class="xNum65"><div id="n_11">3</div></td>';
	print "</tr>";
	print "<tr>";
	print "<td>&nbsp;</td>";
	print '<td class="xNum65"><div id="n_12">2</div></td>';
	print "<td>&nbsp;</td>";
	print '<td colspan="2" class="xNum65" id="ok_next">suivant</td>';
	print "</tr>";
	print "</tbody></table>";
}
?>
