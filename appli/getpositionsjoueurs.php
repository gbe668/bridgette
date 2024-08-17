<?php
require("configuration.php");
require("bridgette_bdd.php");

$idt = htmlspecialchars( $_GET['idtournoi'] );

$dbh = connectBDD();
$t = _readTournoi( $dbh, $idt );
$etat = $t[ 'etat' ];

switch( $etat ) {
	case $st_phase_jeu: {
		$idtype = $t['idtype'];
		$pairesNS = $t['pairesNS'];
		$pairesEO = $t['pairesEO'];
		$paquet = $t['paquet'];
		$ndonnes  = $t['ndonnes'];
		$saut	  = $t['saut'];
		$endofseq = $t['endofseq'];
		$npositions = $t['npositions'];

		$n = ( $t['genre'] == $t_howell )? $t['pairesNS'] : min( $t['pairesNS'], $t['pairesEO'] );
		$res = $dbh->query( "SELECT MIN(rdy) FROM $tab_connexions where id<=$n;" );
		$tour = $res->fetchColumn();
		
		if ( $tour <= $t['npositions'] ) {
			$tabj1  = array(); $tabj2  = array(); $tabj3  = array(); $tabj4  = array();
			$fullj1 = array(); $fullj2 = array(); $fullj3 = array(); $fullj4 = array();
			// paires NS
			$sql = "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = '$idt' order by num;";
			$res = $dbh->query($sql);
			for ($i = 0; $i < $pairesNS; $i++) {
				$row = $res->fetch(PDO::FETCH_ASSOC);
				$tabj1[$i] = $row['idj1'];
				$tabj3[$i] = $row['idj3'];
			};
			for ( $i = 0; $i < $pairesNS; $i++) {
				$joueur = _getJoueur( $dbh, $tabj1[$i] );
				$fullj1[$i] = $joueur['nomcomplet'];
				
				$joueur = _getJoueur( $dbh, $tabj3[$i] );
				$fullj3[$i] = $joueur['nomcomplet'];
			};

			if ( $t['genre'] == $t_howell ) {
				$tab = "<table style='width:100%; margin:auto;'><tbody>";
				$tab .= "<tr><th class='xNum3' colspan='5'>Tour $tour</th></tr>";
				$tab .= "<tr><td class='xNum3'>P</td><td class='xNum3' style='width:50%'>Joueurs</td>";
				$tab .= "<td class='xNum3'>Position</td><td class='xNum3'>Etuis</td></tr>";
				for ( $j = 1; $j <= $pairesNS; $j++ ) {
					$position = $tour-1;
					$p = getposhowell( $idtype, $j, $tour, $paquet );
					$t = $p['table'];	// numéro de table
					$o = ($p['NS'] == 1) ? "NS" : "EO"; 		// orientation: 1=NS, 2=EO
					$l1 = $p['last'] + 1;		// dernière donne "jouée"
					$l2 = $p['last'] + $paquet;	
					
					$q = getParmTable( $j );
					$cpt = $q['cpt'];
					$pos = intval( $cpt / $paquet );
					if ( $pos == $position ) {
						$etuis = $l1."-".$l2;
					}
					else {
						$etuis = "- - -";
					}
					$tab .= "<tr><td class='xNum3'>$j</td>";
					$tab .= "<td class='xNum3'>".$fullj1[$j-1]."</br>".$fullj3[$j-1]."</td>";
					$tab .= "<td class='xNum3'>Table $t</br>en $o</td>";
					$tab .= "<td class='xNum3'>$etuis</td></tr>";
				}
				$tab .= "</tbody></table>";
			}
			else { // paires EO
				$sql = "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = '$idt' order by num;";
				$res = $dbh->query($sql);
				for ($i = 0; $i < $pairesEO; $i++) {
					$row = $res->fetch(PDO::FETCH_ASSOC);
					$tabj2[$i] = $row['idj2'];
					$tabj4[$i] = $row['idj4'];
				};
				for ( $i = 0; $i < $pairesEO; $i++) {
					$joueur = _getJoueur( $dbh, $tabj2[$i] );
					$fullj2[$i] = $joueur['nomcomplet'];
					
					$joueur = _getJoueur( $dbh, $tabj4[$i] );
					$fullj4[$i] = $joueur['nomcomplet'];
				};

				$tab = "<table style='width:100%; margin:auto;'><tbody>";
				$tab .= "<tr><th class='xNum3' colspan='5'>Tour $tour</th></tr>";
				$tab .= "<tr><td class='xNum3'>N</td><td class='xNum3' style='width:40%'>Joueurs en NS</td>";
				$tab .= "<td class='xNum3' style='width:40%'>Joueurs en EO</td><td class='xNum3'>Etuis</td></tr>";
				for ( $j = 1; $j <= max( $pairesNS, $pairesEO); $j++ ) {
					$position = $tour-1;
					$k = $j - $position; 	// $k = n°paire EO
					// test saut
					if ( ( $position >= $saut )&&( $saut > 0) ) $k --;
					if ( $k < 1 ) $k += max( $pairesNS, $pairesEO );
					
					$p = getParmTable( $j );
					$cpt = $p['cpt'];
					$pos = intval( $cpt / $paquet );
					if ( $pos == $position ) {
						$donne	= $p['numdonne'];	// dernière donne enregistrée
						$firstdonne = floor(($donne-1)/$paquet)*$paquet +1;
						if ( $cpt%$paquet == 0 ) $firstdonne +=$paquet;
						if ( $firstdonne > $ndonnes ) $firstdonne = 1;
						$lastdonne = $firstdonne + $paquet -1;
						$etuis = $firstdonne."-".$lastdonne;
					}
					else {
						$etuis = "- - -";
					}
					
					$relais  = $t['relais'];
					$relaisNS = 0;
					$relaisEO = 0;
					if ( $relais > 0 ) {
						if ( $pairesNS > $pairesEO ) {
							$relaisNS = $relais;
						}
						else $relaisEO = $relais;
					}
					if ( ($relaisNS > 0)&&($k == $pairesNS) ) {
						// paire en relais
						$tab .= "<tr><td class='xNum3'>$j</td>";
						$tab .= "<td class='xNum3'>".$fullj1[$j-1]."</br>".$fullj3[$j-1]."</td>";
						$tab .= "<td class='xNum3'>table relais</td>";
						$tab .= "<td class='xNum3'>$etuis</td></tr>";
					}
					else {
						if ( ($relaisEO > 0)&&($j == $pairesEO) ) {
							// paire en relais
							$tab .= "<tr><td class='xNum3'>$j</td>";
							$tab .= "<td class='xNum3'>table relais</td>";
							$tab .= "<td class='xNum3'>".$fullj2[$k-1]."</br>".$fullj4[$k-1]."</td>";
							$tab .= "<td class='xNum3'>$etuis</td></tr>";
						}
						else {
							$tab .= "<tr><td class='xNum3'>$j</td>";
							$tab .= "<td class='xNum3'>".$fullj1[$j-1]."</br>".$fullj3[$j-1]."</td>";
							$tab .= "<td class='xNum3'>".$fullj2[$k-1]."</br>".$fullj4[$k-1]."</td>";
							$tab .= "<td class='xNum3'>$etuis</td></tr>";
						}
					}
				}
				$tab .= "</tbody></table>";
			}
		}
		// durée prévue pour une donne
		$tempo = $parametres['dureedonne'];
		if ( $tour ==1 ) $tempo += $parametres['dureediagrammes'];
		$tempo *= 60;	// en secondes
		break;
	}
	case $st_phase_fini: {
		if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
			setTournoiIMP( $idt );
			$tab = htmlDisplayTournoiIMP( $idt );
		}
		else {
			setTournoi($idt);
			$tab = htmlDisplayTournoi( $idt, 1380 );	// sur 2 colonnes
		}
		$endofseq = 0;
		$tempo = 0;
		break;
	}
	default: {
		$tab = "attendez";
		$endofseq = 0;
		$tempo = 0;
		break;
	}
}
$dbh = null;
echo json_encode( array( 'etat'=>$etat, 'positions'=>$tab, 'endofseq'=>$endofseq, 'tempo'=>$tempo ) );
?>
