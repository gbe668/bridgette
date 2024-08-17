<?php
function displayTableauResultatDonne() {
	global $max_tables;
	print '<table border="0" style="width:100%; max-width: 350px; margin:auto;">';
	print "<tr><td class='xres'>NS</td><td class='xres'>EO</td><td class='xres'>cont.</td><td class='xres'>par</td><td class='xres'>ent.</td><td class='xres'>res</td><td class='xres'>pts</td><td class='xres'>rg</td></tr>";

	for ($i = 0; $i < $max_tables; $i++) {
		$nr = "nr_" . $i;
		$nr1 = $nr . "_1";
		$nr2 = $nr . "_2";
		$nr3 = $nr . "_3";
		$nr4 = $nr . "_4";
		$nr5 = $nr . "_5";
		$nr6 = $nr . "_6";
		$nr7 = $nr . "_7";
		$nr8 = $nr . "_8";
		$nr9 = $nr . "_9";
		
		print "<tr id='$nr' class='xtr61'>";
		print "<td id='$nr1' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr2' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr3' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr4' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr5' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr6' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr7' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr8' class='xNum5'>&nbsp;</td>";
		print "</tr>";
	};
	print "</tbody></table>";
};
function displayFeuilleSuiviDonne( $maxlignes, $large ) {
	print '<table border="0" style="max-width: 350px; margin:auto;">';
	print "<tr><td class='xres'>NS</td><td class='xres'>EO</td>";
	if ($large > 0)
		print "<td class='xres'>cont.</td><td class='xres'>par</td><td class='xres'>enta.</td><td class='xres'>res</td>";
	print "<td class='xres'>pts</td></tr>";

	for ($i = 0; $i < $maxlignes; $i++) {
		$nr = "nr_" . $i;
		$nr1 = $nr . "_1";
		$nr2 = $nr . "_2";
		$nr3 = $nr . "_3";
		$nr4 = $nr . "_4";
		$nr5 = $nr . "_5";
		$nr6 = $nr . "_6";
		$nr7 = $nr . "_7";
		//$nr8 = $nr . "_8";
		//$nr9 = $nr . "_9";
		$ndok = "ok_" . $i;
		$j=$i+1;
		print "<tr id='$nr' class='xtr61'>";
		print "<td id='$nr1' class='xNum5'>&nbsp;</td>";
		print "<td id='$nr2' class='xNum5'>&nbsp;</td>";
		if ($large > 0) {
			print "<td id='$nr3' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr4' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr5' class='xNum5'>&nbsp;</td>";
			print "<td id='$nr6' class='xNum5'>&nbsp;</td>";
		}
		print "<td><input class='xNum5' type='text' id='$nr7' size='3'></td>";
		print "<td class='xNum5'><img src='images/ok.png' id='$ndok' class='clkok' height='20' /></td>";
		print "</tr>";
	};
	print "</tbody></table>";
};
?>
