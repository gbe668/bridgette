<?php
$tab_events = $prefix . "events";
$sql_events = "CREATE TABLE IF NOT EXISTS $tab_events (
	id INT primary key not null auto_increment,
	pseudo varchar(64),
	datevt varchar(64),
	event varchar(250)
	);";

function logevent( $event ) {
	global $tab_events, $sql_events;
	$pseudo = $_SESSION['pseudo'];
	$datevt = date('Y-m-d H:i:s');
	
	$dbh = connectBDD();
	$dbh->query( $sql_events );
	
	$sql = "INSERT into $tab_events ( pseudo, datevt, event ) values ( '$pseudo', '$datevt', '$event' );";
	$res = $dbh->query( $sql );
	$dbh = null;
};
function htmlevents( $n ) {
	global $tab_events;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_events;";
	$res = $dbh->query( $sql );
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "SELECT * FROM $tab_events order by id desc;";
		$res = $dbh->query( $sql );
		if ( $n > 0 ) $nbl = min( $nbl, $n );
		$str = "<p>Derniers événements enregistrés:</p>";
		$str .= "<table border='1' style='margin:auto;'><tbody>";
		$str .= "<tr><th class='xTxt1'>Pseudo</th><th class='xTxt1'>Heure TU</th><th class='xTxt1'>Event</th></tr>";
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			$str .= "<tr><td class='xTxt1'>".$row['pseudo']."</td><td class='xTxt1'>".$row['datevt']."</td><td class='xTxt1'>".$row['event']."</td></tr>";
		}
		$str .= "</tbody></table>";
	}
	else {
		$str = "<p>Pas d'événements enregistrés !</p>";
	}
	$dbh = null;
	return $str;
}
?>