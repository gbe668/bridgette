<?php
require("configuration.php");
require("bridgette_bdd.php");

function getlstpositions( $idtournoi, $numtable ) {
	global $tab_connexions, $st_phase_jeu, $max_tables, $t_mitchell;
	
	$dbh = connectBDD();
	$t = _readTournoi( $dbh, $idtournoi );
	$etat = $t['etat'];
	$ntables = $t['ntables'];
	if ( $etat == $st_phase_jeu ) {
		$relais  = $t['relais'];
		$genre   = $t['genre'];
		$pairesNS = $t['pairesNS'];

		$positions = array();
		$sth = $dbh->query( "SELECT * FROM $tab_connexions;" );
		for ( $i = 1; $i <= $max_tables; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$positions[$i] = $row['rdy'];
		}

		$mintour = 99;
		if ( $genre == $t_mitchell ) {
			for ( $i = 1; $i <= $pairesNS; $i++ ) {
				if ( $positions[$i] < $mintour ) $mintour = $positions[$i];
			}
			$txt = "tables";
		}
		else { // Howell
			for ( $i = 1; $i <= $ntables; $i++ ) {
				if ( $positions[$i] < $mintour ) $mintour = $positions[$i];
			}
			$txt = "paires";
		}
		
		$str = "<h3>Avancement des " .$txt. "</h3>";
		$str .= "<table border='0' align='center'><body><tr>";
		for ( $i=1; $i<=$ntables; $i++ ) {
			if ( $i == $numtable ) $str .= "<td class='xTxt1 pairens'>" .$i. "</td>";
			else $str .= "<td class='xTxt1'>" .$i. "</td>";
		}
		$str .= "</tr><tr>";
		for ( $i=1; $i<=$ntables; $i++ ) {
			if ( $i == $numtable ) $str .= "<td class='xTxt1 notour'>" .$positions[$i]. "</td>";
			else {
				if ( $positions[$i] < $positions[$numtable] ) {
					$str .= "<td class='xTxt1 pairens'>" .$positions[$i]. "</td>";
				}
				else {
					$str .= "<td class='xTxt1'>" .$positions[$i]. "</td>";
				}
			}
		}
		$str .= "</tr></tbody></table>";
	}
	else {
		$mintour = 0;
		$str = "<h3>Tournoi n'est plus en cours</h3>";
	}
		
	$dbh = null;
	return array( 'ntables'=>$ntables, 'mintour'=>$mintour, 'positions'=>$positions, 'str'=>$str );
}

$idtournoi = $_GET['idtournoi'];
$numtable = $_GET['numtable'];
$result = getlstpositions( $idtournoi, $numtable );
//print_r( $result );
echo json_encode( $result );
?>
