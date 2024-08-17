<?php
require("configuration.php");
require("bridgette_bdd.php");

function strTeam($idtournoi, $axe, $num) {
	if ( $axe == "eo" ) {
		$ligneEO = getligneEO( $idtournoi, $num );
		$name1	= $ligneEO['E']['nomcomplet'];
		$name2	= $ligneEO['O']['nomcomplet'];
	}
	else {
		$ligneNS = getligneNS( $idtournoi, $num );
		$name1	= $ligneNS['N']['nomcomplet'];
		$name2	= $ligneNS['S']['nomcomplet'];
	}
	return $name1 . " - " . $name2 ;
}
function htmlResultatsPaire($idt, $axe, $num) {
	global $tab_donnes, $contratNJ;
	global $t_mitchell, $t_howell, $genre;
	
	$ordre = "points";
	
	$dbh = connectBDD();
	
	if( $axe == "ns" ) {	// ou Howell
		$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and ns='$num' order by etui;";
		$opposite = "eo";
		$axelabel = "% NS";
	}
	else {
		$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and eo='$num' order by etui;";
		$opposite = "ns";
		$axelabel = "% EO";
	}
	
	$prevadv = 0;
	$i = 0;
	$somme = 0;
	$j = 0;	// nombre de résultats moyennés
	$k = 0;	// nombre de paires affrontées
	$l = 0; // nombre de lignes par paire affrontée
	$prevadv = 0;
	foreach ($dbh->query($sql) as $row) {
		$tabns[$i] = $row['ns'];
		$tabeo[$i] = $row['eo'];
		$tabhw[$i] = $row['hweo'];
		$adv = ( $axe == "eo" ) ? $tabns[$i] : $tabeo[$i];
		if ( $adv != $prevadv ) {
			$tabs[$k] = $somme;
			$tabn[$k] = $j;
			$tabl[$k] = $l;
			$k++;
			$somme = 0;
			$j = 0;
			$l = 0;
			$prevadv = $adv;
		}
		$tabr1[$i] = $row['etui'];
		$tabr2[$i] = htmlContrat( $row['contrat'] );
		$tabr3[$i] = $row['jouepar'];
		$tabr4[$i] = htmlEntame( $row['entame'] );
		$tabr5[$i] = htmlResultat( $row['resultat'] );
		if ( $row['contrat'] == $contratNJ ) {
			$tabr6[$i] = "-";
			$tabr7[$i] = "-";
		}
		else {
			$tabr6[$i] = $row['points'];
			$tabr7[$i] = $row['note'];
			$somme += $tabr7[$i];
			$j++;
		}
		$i++;
		$l++;
	};
	$tabs[$k] = $somme;
	$tabn[$k] = $j;
	$tabl[$k] = $l;
	$dbh = null;
	
	$str = '<table border="0" style="width:100%; max-width: 350px; margin:auto;" class="notranslate">';
	$str .= "<tr><th colspan='8'>Résultats des donnes jouées</th></tr>";
	$str .= "<tr class='xtr61'><td class='xres'>Etui</td><td class='xres'>cont.</td><td class='xres'>par</td><td class='xres'>ent.</td><td class='xres'>res</td><td class='xres'>pts</td>";
	if ($ordre == "pointsIMP") {
		$str .= "<td class='xres'>IMP</td></tr>";
	}
	else {
		$str .= "<td class='xres'>".$axelabel."</td>";
	}
	$str .= "<td class='xres'>Moy.</td></tr>";
	$cc = 'xres seletui';
	$k = 0;	// nombre de paires affrontées
	$prevadv = 0;
	$flag = false;
	for ($j = 0; $j<$i; $j++) {
		$ns = $tabns[$j];
		$eo = $tabeo[$j];
		if ( $genre == $t_mitchell ) {
			$adv = ( $axe == "eo" ) ? $ns : $eo;
			$s = strTeam($idt, $opposite, $adv);
		}
		else {
			$adv = $eo;
			$opposite = "ns";
			$axadv = ($tabhw[$j] > 0) ? "NS: " : "EO: ";
			$s = $axadv.strTeam($idt, $opposite, $adv);
		}
		if ( $adv != $prevadv ) {
			$str .= "<tr><td colspan='8'>".$s."</td></tr>";
			$prevadv = $adv;
			$flag = true;
			$k++;
		}
		$r1 = $tabr1[$j];
		$nr = "nr_" . $r1;
		$r2 = $tabr2[$j];
		$r3 = $tabr3[$j];
		$r4 = $tabr4[$j];
		$r5 = $tabr5[$j];
		$r6 = $tabr6[$j];
		$r6 = ( ($axe == "ns")||($r6 == "-") ) ? $r6 : -$r6;
		if ( $r2 == $contratNJ ) {
			$r7str = "-";
		}
		else {
			$r7 = $tabr7[$j];
			$r7 = ( $axe == "ns" ) ? $r7 : (100-$r7);
			if ($ordre == "pointsIMP") {
				$r7str = intval( $r7 );
			}
			else {
				$r7str = sprintf( "%.1f", $r7);
			}
		}
		$str .= "<tr id='$nr' class='xres2'><td class='xres seletui'>$r1</td>";
		$str .= "<td class='xres seletui'>$r2</td>";
		$str .= "<td class='xres seletui'>$r3</td>";
		$str .= "<td class='xres seletui'>$r4</td>";
		$str .= "<td class='xres seletui'>$r5</td>";
		$str .= "<td class='xres seletui'>$r6</td>";
		$str .= "<td class='xres seletui'>$r7str</td>";
		if ( $flag ) {
			$nrow = $tabl[$k];
			if ( $tabn[$k] == 0 ) {
				$r8str = "-";
			}
			else {
				$moyenne = $tabs[$k]/$tabn[$k];
				$moyenne = ( $axe == "ns" ) ? $moyenne : (100-$moyenne);
				$r8str = sprintf( "%.1f", $moyenne);
			}
			$str .= "<td class='xres seletui' rowspan='$nrow'>$r8str</td>";
			$flag = false;
		}
		$str .= "</tr>";
	};
	$str .= "</tbody></table>";
	
	return $str;
}

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$axe = htmlspecialchars( $_GET['axe'] );
$num = htmlspecialchars( $_GET['num'] );

if ( $axe == "eo" ) {
	$ligneEO = getligneEO( $idtournoi, $num );
	$name1	= $ligneEO['E']['nomcomplet'];
	$name2	= $ligneEO['O']['nomcomplet'];
}
else {
	$ligneNS = getligneNS( $idtournoi, $num );
	$name1	= $ligneNS['N']['nomcomplet'];
	$name2	= $ligneNS['S']['nomcomplet'];
}
$t = readTournoi( $idtournoi );
$genre = $t['genre'];
if ( $genre == $t_mitchell ) {
	$ref = strtoupper($axe).$num;
}
else {
	$ref = "NS".$num;
}
$team = strTeam($idtournoi, $axe, $num);

$html = htmlResultatsPaire($idtournoi, $axe, $num);

echo json_encode( array( 'ref'=>$ref, 'team'=>$team, 'html'=>$html ) );
?>
