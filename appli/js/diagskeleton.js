const isAndroid = false;
const relimg = ( isAndroid ? "/" : "" )+"images/";		// sous-répertoire ./images

function diag_skeleton() {
	global $relimg;

	let str = '<div id="skeleton_diagramme">';
	str += '<table border="1" style="width:100%; max-width: 350px; margin:auto; border-collapse: collapse;" class="notranslate"><tbody>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td style="width:15%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_1">&nbsp;</td></tr>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_2">&nbsp;</td></tr>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'carreau.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_3">&nbsp;</td></tr>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'trefle.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_4">&nbsp;</td></tr>';
	
	str += '<tr><td style="width:10%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td>';
	str += '<td class="xsmallDigit" id="ligne_13">&nbsp;</td>';
	str += '<td id="diagrowtop" style="border:2pt solid windowtext; border-bottom:none;">Nord</td>';
	str += '<td style="width:10%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td>';
	str += '<td style="width:35%;" class="xsmallDigit" id="ligne_5">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'coeur.png" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_14">&nbsp;</td>';
	str += '<td id="diagrowmid1" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_6">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'carreau.png" alt="carreau" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_15">&nbsp;</td>';
	str += '<td id="diagrowmid2" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	str += '<td><img src="'+relimg+'carreau.png" alt="carreau" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_7">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'trefle.png" alt="trefle" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_16">&nbsp;</td>';
	str += '<td id="diagrowbottom" style="border:2pt solid windowtext; border-top:none">Sud</td>';
	str += '<td><img src="'+relimg+'trefle.png" alt="trefle" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_8">&nbsp;</td></tr>';
	
	print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'pique.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_9">&nbsp;</td></tr>';
	str += '<tr><td rowspan="3" colspan="2">';
		str += '<table border="1" style="margin:auto;" id="points_honneurs" hidden><tbody>';
		str += '<tr><td>&nbsp;</td><td id="ph_nord" class="phonn">NN</td><td>&nbsp;</td></tr>';
		str += '<tr><td id="ph_ouest" class="phonn">OO</td><td>&nbsp;</td><td id="ph_est" class="phonn">EE</td></tr>';
		str += '<tr><td>&nbsp;</td><td id="ph_sud" class="phonn">SS</td><td>&nbsp;</td></tr>';
		str += '</tbody></table>';
	str += '</td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_10">&nbsp;</td></tr>';
	str += '<tr>';
	str += '<td><img src="'+relimg+'carreau.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_11">&nbsp;</td></tr>';
	str += '<tr>';
	str += '<td><img src="'+relimg+'trefle.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_12">&nbsp;</td></tr>';
	
	print '</tbody></table>';
	str += '</div>';
}
