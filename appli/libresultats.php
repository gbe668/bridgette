<?php
// Lectures des résultats, sans génération HTML ni modification des données.
require_once __DIR__.'/librendu_resultats.php';

function readResultatDonne($idt, $etui, $ordre) {
	global $tab_donnes;
	$hweo = 0;
	$dbh = connectBDD();
	if ( ($ordre == "points")||($ordre == "pointsIMP") ) {
		$sql = "SELECT * FROM $tab_donnes where idtournoi = ? and etui=? and hweo=? order by points desc;";
		$sqlBound = [$idt, $etui, $hweo];
	} else {
		$sql = "SELECT * FROM $tab_donnes where idtournoi = ? and etui=? order by ns;";
		$sqlBound = [$idt, $etui];
	}

	$t = _readTournoi($dbh, $idt);
	$rows = [];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) $rows[] = $row;
	$dbh = null;
	return ['genre'=>$t['genre'], 'rows'=>$rows];
}

function readResultatPaquet($idt, $ns, $eo) {
    global $tab_donnes;
    $dbh = connectBDD();
    $sql = "SELECT * FROM $tab_donnes where idtournoi = ? and ns=? and eo=? order by etui;";
    $rows = [];
    foreach (securityQuery($dbh, $sql, [$idt, $ns, $eo]) as $row) $rows[] = $row;
    $dbh = null;
    $diagrammes = [];
    foreach ($rows as $row) $diagrammes[] = existeDiagramme($idt, $row['etui']);
    return ['rows'=>$rows, 'diagrammes'=>$diagrammes];
}

// Façades historiques : les pages web et Android gardent les mêmes appels.
function htmlResultatDonne($idt, $etui, $numNS, $numEO, $ordre) {
    return renderResultatDonne(readResultatDonne($idt, $etui, $ordre), $etui, $numNS, $numEO, $ordre);
}
function htmlResultatPaquet($idt, $ns, $eo) {
    return renderResultatPaquet(readResultatPaquet($idt, $ns, $eo));
}
function readDisplayTournoi($idt, $displayGeneral = false) {
	global $tab_pairesNS, $tab_pairesEO, $t_mitchell;
	$dbh = connectBDD();
	$t = _readTournoi($dbh, $idt);
	$pairesNS = $t['pairesNS'];
	$pairesEO = $t['pairesEO'];
	$pns = [];
	$notegns = [];
	$fullj1 = [];
	$fullj3 = [];
	$peo = [];
	$notegeo = [];
	$fullj2 = [];
	$fullj4 = [];
	$paires = [];
	$sens = [];
	$notes = [];
	$full1 = [];
	$full2 = [];
	// Lecture des notes globales NS
	$pns = array();
	$notegns = array();
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT num, idj1, idj3, noteg FROM $tab_pairesNS where idtournoi = ? order by noteg desc;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$pns[$i] = $row['num'];
		$tabj1[$i] = $row['idj1'];
		$tabj3[$i] = $row['idj3'];
		$notegns[$i] = $row['noteg'];
		$i++;
	};
	$nomj1 = array();
	$nomj3 = array();
	$fullj1 = array();
	$fullj3 = array();
	for ( $i = 0; $i < $pairesNS; $i++) {
		$joueur = _getJoueur( $dbh, $tabj1[$i] );
		$nomj1[$i] = $joueur['joueur'];
		$fullj1[$i] = $joueur['nomcomplet'];
		
		$joueur = _getJoueur( $dbh, $tabj3[$i] );
		$nomj3[$i] = $joueur['joueur'];
		$fullj3[$i] = $joueur['nomcomplet'];
	};
	
	if ( $t['genre'] == $t_mitchell ) {
		// Lecture des notes globales EO
		$peo = array();
		$notegeo = array();
		$tabj2 = array();
		$tabj4 = array();
		$i = 0;
		$sql = "SELECT num, idj2, idj4, noteg FROM $tab_pairesEO where idtournoi = ? order by noteg desc;";
		$sqlBound = [$idt];
		foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
			$peo[$i] = $row['num'];
			$tabj2[$i] = $row['idj2'];
			$tabj4[$i] = $row['idj4'];
			$notegeo[$i] = $row['noteg'];
			$i++;
		};
		$nomj2 = array();
		$nomj4 = array();
		$fullj2 = array();
		$fullj4 = array();
		for ( $i = 0; $i < $pairesEO; $i++) {
			$joueur = _getJoueur( $dbh, $tabj2[$i] );
			$nomj2[$i] = $joueur['joueur'];
			$fullj2[$i] = $joueur['nomcomplet'];
		
			$joueur = _getJoueur( $dbh, $tabj4[$i] );
			$nomj4[$i] = $joueur['joueur'];
			$fullj4[$i] = $joueur['nomcomplet'];
		};
	}

	
	if (($t['genre'] == $t_mitchell) && ($displayGeneral == true)) {
		// Lecture des notes globales NS & EO
		$paires = array();
		$sens = array();
		$notes = array();
		$tabj1 = array();
		$tabj3 = array();
		$i = 0;
		$sql = "SELECT num, j1, j2, noteg, sens FROM (
			(SELECT 'NS' as sens, num, idj1 as j1, idj3 as j2, noteg FROM $tab_pairesNS where idtournoi = ?)
			UNION
			(SELECT 'EO' as sens, num, idj2 as j1, idj4 as j2, noteg FROM $tab_pairesEO where idtournoi = ?)
			) T1 order by noteg desc;";
		$sqlBound = [$idt, $idt];
		foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
			$paires[$i] = $row['num'];
			$sens[$i] = $row['sens'];
			$tab1[$i] = $row['j1'];
			$tab2[$i] = $row['j2'];
			$notes[$i] = $row['noteg'];
			$i++;
		};
		$nom1 = array();
		$nom2 = array();
		$full1 = array();
		$full2 = array();
		for ( $i = 0; $i < count($paires); $i++) {
			$joueur = _getJoueur( $dbh, $tab1[$i] );
			$nom1[$i]  = $joueur['joueur'];
			$full1[$i] = $joueur['nomcomplet'];
			
			$joueur = _getJoueur( $dbh, $tab2[$i] );
			$nom2[$i]  = $joueur['joueur'];
			$full2[$i] = $joueur['nomcomplet'];
		};
		
	}
	$dbh = null;
	return [
		'tournoi'=>$t,
		'pns'=>$pns, 'notegns'=>$notegns, 'fullj1'=>$fullj1, 'fullj3'=>$fullj3,
		'peo'=>$peo, 'notegeo'=>$notegeo, 'fullj2'=>$fullj2, 'fullj4'=>$fullj4,
		'paires'=>$paires, 'sens'=>$sens, 'notes'=>$notes, 'full1'=>$full1, 'full2'=>$full2,
	];
}

function readDisplayTournoiIMP($idt) {
	global $tab_pairesNS;
	$dbh = connectBDD();
	$t = _readTournoi($dbh, $idt);
	$pairesNS = $t['pairesNS'];
	// Lecture des notes globales NS
	$pns = array();
	$notegns = array();
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT num, idj1, idj3, noteg FROM $tab_pairesNS where idtournoi = ? order by noteg desc;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$pns[$i] = $row['num'];
		$tabj1[$i] = $row['idj1'];
		$tabj3[$i] = $row['idj3'];
		$notegns[$i] = $row['noteg'];
		$i++;
	};
	$nomj1 = array();
	$nomj3 = array();
	$fullj1 = array();
	$fullj3 = array();
	for ( $i = 0; $i < $pairesNS; $i++) {
		$joueur = _getJoueur( $dbh, $tabj1[$i] );
		$nomj1[$i] = $joueur['joueur'];
		$fullj1[$i] = $joueur['nomcomplet'];
		
		$joueur = _getJoueur( $dbh, $tabj3[$i] );
		$nomj3[$i] = $joueur['joueur'];
		$fullj3[$i] = $joueur['nomcomplet'];
	};
	
	$dbh = null;
	return ['tournoi'=>$t, 'pns'=>$pns, 'notegns'=>$notegns, 'fullj1'=>$fullj1, 'fullj3'=>$fullj3];
}

function htmlDisplayTournoi($idt, $screenw, $displayGeneral = False) {
    return renderDisplayTournoi(readDisplayTournoi($idt, $displayGeneral), $screenw, $displayGeneral);
}
function htmlDisplayTournoiIMP($idt) {
    return renderDisplayTournoiIMP(readDisplayTournoiIMP($idt));
}
