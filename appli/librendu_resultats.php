<?php
// Rendu des données déjà lues : aucune connexion ni requête SQL.
// Formats historiques conservés, y compris les classes et identifiants HTML.

function renderResultatDonne($data, $etui, $numNS, $numEO, $ordre) {
	global $contratNJ, $t_howell, $t_mitchell;
	$vulnerabilite = getinfoetui( $etui );
	$str = '<table border="0" style="width:100%; max-width: 350px; margin:auto;" class="notranslate">';
	$str .= "<tr><th colspan='8' style='font-size: 1.2em;'>Donne n°<span id='etui'>$etui</span></th></tr>";
	$str .= "<tr><th colspan='8'>$vulnerabilite</th></tr>";
	$str .= "<tr class='xtr61'><td class='xres'>NS</td><td class='xres'>EO</td><td class='xres'>cont.</td><td class='xres'>par</td><td class='xres'>ent.</td><td class='xres'>res</td><td class='xres'>pts</td>";
	if ($ordre == "pointsIMP") {
		$str .= "<td class='xres'>IMP</td></tr>";
	}
	else {
		$str .= "<td class='xres'>% NS</td></tr>";
	}
	
	$genre = $data['genre'];
	foreach ($data['rows'] as $row) {
		$r1 = $row['ns'];
		$r2 = $row['eo'];
		$r3 = htmlContrat( $row['contrat'] );
		$r4 = $row['jouepar'];
		$r5 = htmlEntame( $row['entame'] );
		$r6 = htmlResultat( $row['resultat'] );
		if ( $row['contrat'] == $contratNJ ) {
			$r7 = '-';
			$r8 = '-';
			$r8str = '-';
		}
		else {
			$r7 = $row['points'];
			$r8 = $row['note'];
			if ($ordre == "pointsIMP") {
				$r8str = intval( $r8 );
			}
			else {
				$r8str = sprintf( "%.1f", $r8);
			}
		}
		if ( $r1 == $numNS )
			$str .= "<tr><td class='xres pairens'>$r1</td>";
		else
			$str .= "<tr><td class='xres'>$r1</td>";
		if ( (($genre==$t_mitchell)&&($r2 == $numEO)) || (($genre==$t_howell)&&($r2 == $numNS)) )
			$str .= "<td class='xres pairens'>$r2</td>";
		else
			$str .= "<td class='xres'>$r2</td>";
		$str .= "<td class='xres'>$r3</td>";
		$str .= "<td class='xres'>$r4</td>";
		$str .= "<td class='xres'>$r5</td>";
		$str .= "<td class='xres'>$r6</td>";
		$str .= "<td class='xres'>$r7</td>";
		if ( ($r1 == $numNS)||($r2 == $numEO) )
			$str .= "<td class='xres pairens'>$r8str</td></tr>";
		else
			$str .= "<td class='xres'>$r8str</td></tr>";
	};
	$str .= "</tbody></table>";
	
	
	return $str;
}
function renderResultatPaquet($data) {
	global $contratNJ;
	$i = 0;
	$somme = 0; $nl = 0;
	foreach ($data['rows'] as $row) {
		$tabns[$i] = $row['ns'];
		$tabeo[$i] = $row['eo'];
		$tabr1[$i] = $row['etui'];
		$tabr2[$i] = htmlContrat( $row['contrat'] );
		$tabr3[$i] = $row['jouepar'];
		$tabr4[$i] = htmlEntame( $row['entame'] );
		$tabr5[$i] = htmlResultat( $row['resultat'] );
		if ( $row['contrat'] == $contratNJ ) {
			$tabr6[$i] = '-';
			$tabr7[$i] = '-';
		}
		else {
			$tabr6[$i] = $row['points'];
			$tabr7[$i] = sprintf( "%.1f", $row['note'] );
			$somme += $row['note'];
			$nl++;
		}
		$i++;
	};
	

	$str = '<table border="0" style="width:100%; max-width: 350px; margin:auto;" class="notranslate">';
	$str .= "<tr><th colspan='8'>Résultats des donnes jouées</th></tr>";
	$str .= "<tr class='xtr61'><td class='xres'>Etui</td><td class='xres'>cont.</td><td class='xres'>par</td><td class='xres'>ent.</td><td class='xres'>res</td><td class='xres'>pts</td>";
	$str .= "<td class='xres'>% NS</td>";
	$str .= "<td class='xres'>Moy.</td></tr>";

	for ($j = 0; $j<$i; $j++) {
		$r1 = $tabr1[$j];
		$nr = "nr_" . $r1;
		$r2 = $tabr2[$j];
		$r3 = $tabr3[$j];
		$r4 = $tabr4[$j];
		$r5 = $tabr5[$j];
		$r6 = $tabr6[$j];
		$r7 = $tabr7[$j];
		//$r7str = sprintf( "%.1f", $r7);
		$str .= "<tr id='$nr' class='xres2'>";
		[$diags,$dds] = $data['diagrammes'][$j];
		if ( $diags == null ) {
			$str .= "<td class='xres seletui pairens'>$r1</td>";
		}
		else {
			$str .= "<td class='xres seletui'>$r1</td>";
		}
		$str .= "<td class='xres seletui'>$r2</td>";
		$str .= "<td class='xres seletui'>$r3</td>";
		$str .= "<td class='xres seletui'>$r4</td>";
		$str .= "<td class='xres seletui'>$r5</td>";
		$str .= "<td class='xres seletui'>$r6</td>";
		$str .= "<td class='xres seletui'>$r7</td>";
		if ( $j == 0 ) {
			$r8str = ( $nl == 0 ) ? "-" : sprintf( "%.1f", $somme/$nl );
			$str .= "<td class='xres seletui' rowspan='$i'>$r8str</td>";
		}
		$str .= "</tr>";
	};
	$str .= "</tbody></table>";
	
	
	return $str;
}


function renderDisplayTournoi($data, $screenw, $displayGeneral = false) {
	global $parametres, $t_mitchell, $t_howell;
	$t = $data['tournoi'];
	$pairesNS = $t['pairesNS'];
	$pairesEO = $t['pairesEO'];
	$njouees = $t['njouees'];
	$ndonnes = $t['ndonnes'];
	$datet = $t[ 'tournoi' ];
	$datef = strdatet( $datet );
	
	$pns = $data['pns'];
	$notegns = $data['notegns'];
	$fullj1 = $data['fullj1'];
	$fullj3 = $data['fullj3'];
	$peo = $data['peo'];
	$notegeo = $data['notegeo'];
	$fullj2 = $data['fullj2'];
	$fullj4 = $data['fullj4'];
	$paires = $data['paires'];
	$sens = $data['sens'];
	$notes = $data['notes'];
	$full1 = $data['full1'];
	$full2 = $data['full2'];
	// affichage des tableaux
	if ( $t['genre'] == $t_mitchell )
		$str = "<h2>Résultats tournoi du $datef</h2><p>$pairesNS paires Nord-Sud, $pairesEO paires Est-Ouest</br>$njouees donnes jouées / $ndonnes en circulation</p>";
	else
		$str = "<h2>Résultats tournoi du $datef</h2><p>$pairesNS paires Howell</br>$njouees donnes jouées / $ndonnes en circulation</p>";

	if ( $t['genre'] == $t_howell ) {
		// forçage une colonne
		$screenw = 0;
	}
	$twocols = ( $screenw > $parametres['maxw'] ) ? true : false;
	if ( $twocols ) {
		// affichage des tableaux côte à côte
		$str .= "<table style='width:90%; margin:auto;'><tbody><tr><td style='width:45%;'>";
	}
	
	// affichage 1er tableau
	$str .= "<table style='margin:auto;'><tbody><tr><td class='xNum3' style='width:10%;'>Rg</td><td class='xNum3' style='width:10%;'>NS</td><td class='xNum3'>Paire</td><td class='xNum3' style='width:15%;'>%</td></tr>";
	
	// élab classement avec recherche d'ex-aequos
	$classement = elabClassement( $pairesNS, $notegns );
	for ($i = 0; $i < $pairesNS; $i++) {
		$idpaire = "ns_".$pns[$i];
		$score = sprintf( "%.1f", $notegns[$i]);
		$str .= "<tr><td class='xNum3'>$classement[$i]</td>";
		$str .= "<td class='xNum3'>$pns[$i]</td>";
		$str .= "<td class='xNum3 select' id='".$idpaire."'>$fullj1[$i]<br />$fullj3[$i]</td>";
		//$str .= "<td class='xNum3'>$notegns[$i] %</td>";
		$str .= "<td class='xNum3'>$score</td>";
		$str .= "</tr>";
		}
	$str .= "</tbody></table>";

	if ( $twocols ) {
		$str .= "</td><td style='width:3%;'></td><td style='width:45%;'>";
	}
	else {
		$str .= "<p>&nbsp;</p>";
	}
	
	if ( $t['genre'] == $t_mitchell ) {
		// affichage 2ème tableau
		$str .= "<table style='margin:auto;'><tbody><tr><td class='xNum3' style='width:10%;'>Rg</td><td class='xNum3' style='width:10%;'>EO</td><td class='xNum3'>Paire</td><td class='xNum3' style='width:15%;'>%</td></tr>";
	
		// élab classement avec recherche d'ex-aequos
		$classement = elabClassement( $pairesEO, $notegeo );
		for  ($i = 0; $i < $pairesEO; $i++) {
			$idpaire = "eo_".$peo[$i];
			$score = sprintf( "%.1f", $notegeo[$i]);
			$str .= "<tr><td class='xNum3'>$classement[$i]</td>";
			$str .= "<td class='xNum3'>$peo[$i]</td>";
			$str .= "<td class='xNum3 select' id='".$idpaire."'>$fullj2[$i]<br />$fullj4[$i]</td>";
			$str .= "<td class='xNum3'>$score</td>";
			$str .= "</tr>";
			}
		$str .= "</tbody></table>";		
	}
	
	if ( $twocols ) {
		$str .= '</td></tr></tbody></table>';
	}
	
	// élab classement général toutes paires avec recherche d'ex-aequos pour les tournois Mitchell
	if ( ($t['genre'] == $t_mitchell)&&($displayGeneral == True) ) {
		// élab classement avec recherche d'ex-aequos
		$classement = elabClassement( count($paires), $notes );
		$str .= "<div id='section_general' hidden>";
		$str .= "<p><button class='myButton' onclick='$(`#section_general`).toggle()';>Classement général</button></p>";
		$str .= "<table style='margin:auto;'><tbody><tr><td class='xNum3' style='width:10%;'>Rg</td><td class='xNum3' style='width:10%;'>N°</td><td class='xNum3'>Paire</td><td class='xNum3' style='width:15%;'>%</td></tr>";
		for ($i = 0; $i < count($paires); $i++) {
			$idpaire = strtolower($sens[$i])."_".$paires[$i];
			$numpaire = $sens[$i].$paires[$i];
			$score = sprintf( "%.1f", $notes[$i]);
			$str .= "<tr><td class='xNum3'>$classement[$i]</td>";
			$str .= "<td class='xNum3'>$numpaire</td>";
			$str .= "<td class='xNum3 select' id='".$idpaire."'>$full1[$i]<br />$full2[$i]</td>";
			//$str .= "<td class='xNum3'>$notegns[$i] %</td>";
			$str .= "<td class='xNum3'>$score</td>";
			$str .= "</tr>";
			}
		$str .= "</tbody></table>";
		$str .= "</div>";
		$str .= "<p><button class='myButton' onclick='$(`#section_general`).toggle()';>Classement général</button></p>";
	}
	
	return $str;
};

function renderDisplayTournoiIMP($data) {
	$t = $data['tournoi'];
	$pairesNS = $t['pairesNS'];
	$pairesEO = $t['pairesEO'];
	$njouees = $t['njouees'];
	$ndonnes = $t['ndonnes'];
	$datet = $t[ 'tournoi' ];
	$datef = strdatet( $datet );
	
	$str = "<h2>Résultats tournoi du $datef</h2><p>$pairesNS paires (résultats en IMP)</br>$njouees donnes jouées / $ndonnes en circulation</p>";

		$pns = $data['pns'];
	$notegns = $data['notegns'];
	$fullj1 = $data['fullj1'];
	$fullj3 = $data['fullj3'];
	// affichage des tableaux
	$str .= "<table border='1' style='width:100%; max-width: 350px; margin:auto;'>";
	$str .= "<tbody><tr><td class='xNum3' style='width:10%;'>Rg</td><td class='xNum3' style='width:10%;'>N°</br>paire</td><td class='xNum3'>Paire</td><td class='xNum3' style='width:15%;'>IMP</td></tr>";
	
	// élab classement avec recherche d'ex-aequos
	$classement = elabClassement( $pairesNS, $notegns );
	for ($i = 0; $i < $pairesNS; $i++) {
		$idpaire = "ns_".$pns[$i];
		$score = intval( $notegns[$i] );
		$str .= "<tr><td class='xNum3'>$classement[$i]</td>";
		$str .= "<td class='xNum3'>$pns[$i]</td>";
		$str .= "<td class='xNum3 select' id='".$idpaire."'>$fullj1[$i]<br />$fullj3[$i]</td>";
		$str .= "<td class='xNum3'>$score</td>";
		$str .= "</tr>";
		}
	$str .= "</tbody></table>";

	return $str;
};
