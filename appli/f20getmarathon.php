<?php
require("configuration.php");
require("bridgette_bdd.php");

$datetournoi = htmlspecialchars( $_GET['datetournoi'] );
	
function getClassementMarathon($dd) {
	global $tab_tournois, $st_closed, $tab_pairesNS, $tab_pairesEO, $maxjoueurs;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_tournois where etat=? and tournoi=?;";
	
	$sqlBound = [$st_closed, $dd];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	//print "<p>$sql $nbl</p>";
	if ( $nbl > 0 ) {
		$sth = securityQuery($dbh, "SELECT * FROM $tab_tournois where etat=? and tournoi=? order by id asc;", [$st_closed, $dd]);
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$minidt = $row['id'];
		$maxidt = $minidt + $nbl;
		//print "<p>$sql $minidt</p>";

		$sql = "SELECT idj, nbfois, perf FROM (
			SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf FROM (
				(SELECT idj1 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= ? and idtournoi < ?) UNION 
				(SELECT idj2 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= ? and idtournoi < ?) UNION 
				(SELECT idj4 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= ? and idtournoi < ?) UNION 
				(SELECT idj3 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= ? and idtournoi < ?)
				) T1 group by idj
			) T2 order by perf desc;";
		$sqlBound = [$minidt, $maxidt, $minidt, $maxidt, $minidt, $maxidt, $minidt, $maxidt];
		//print "<p>$sql</p>";
		$datef = strdatet( $dd );
		$str = "<p>Résultat du marathon du $datef</p>";
		$str .= '<table border="0" style="width:100%; max-width: 350px; margin:auto;"><tbody>';
		$str .= "<tr><td class='xNum3' style='width:10%;'>Rg</td>";
		$str .= "<td class='xNum3'>Joueur</td>";
		$str .= "<td class='xNum3' style='width:10%;'>N</td>";
		$str .= "<td class='xNum3' style='width:15%;'>%</td></tr>";
		$i=0;
		foreach  (securityQuery($dbh, $sql, $sqlBound) as $row) {
			$j = _getJoueur( $dbh, $row['idj'] );
			$score = sprintf( "%.1f", $row["perf"] );
			$str .= "<tr>";
			$str .= "<td class='xNum3'>" . ++$i . "</td>";
			$str .= "<td class='xNum3'>" . $j['nomcomplet'] . "</td>";
			$str .= "<td class='xNum3'>" . $row['nbfois'] . "</td>";
			$str .= "<td class='xNum3'>" . $score . "</td>";
			$str .= "</tr>";
			}
		$str .= "</tbody></table>";
	}
	else {
		$str = "Pas de tournoi enregistré !";
	}
	$dbh = null;
	return $str;
};

$str = getClassementMarathon($datetournoi);

echo json_encode( array( 'classement'=>$str ) );
?>
