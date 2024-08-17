<?php
$tab_contacts = $prefix . "contacts";

$dbh = connectBDD();
$sql_contacts = "CREATE TABLE IF NOT EXISTS $tab_contacts (
	id INT primary key not null auto_increment,
    tournoi DATE,		/* date tournoi */
	nom varchar(64),
	contact varchar(64),
	memo varchar(250)
	);";
$dbh->query( $sql_contacts );
$dbh = null;

function listeContacts( $datetournoi ) {	
	global $tab_contacts;
	$ids = array();
	$names = array();
	$contacts = array();
	$memos = array();
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_contacts where tournoi='$datetournoi';";
	$res = $dbh->query( $sql );
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "SELECT * FROM $tab_contacts where tournoi='$datetournoi';";
		$res = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			$ids[$i] = $row[ 'id' ];
			$names[$i] = $row[ 'nom' ];
			$contacts[$i] = $row[ 'contact' ];
			$memos[$i] = $row[ 'memo' ];
		}
	}
	$dbh = null;
	return json_encode( array( 'nbl'=>$nbl, 'ids'=>$ids, 'names'=>$names, 'contacts'=>$contacts, 'memos'=>$memos ) );
};
function insertContact( $datetournoi, $name, $contact, $memo ) {	
	global $tab_contacts;
	$dbh = connectBDD();
	$sql = "INSERT into $tab_contacts ( tournoi, nom, contact, memo ) values ( '$datetournoi', '$name', '$contact', '$memo' );";
	$res = $dbh->query( $sql );
	$dbh = null;
	return json_encode( array( 'res'=>'Contact enregistré' ) );
};
function updateContact( $id, $contact, $memo ) {	
	global $tab_contacts;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_contacts SET contact='$contact', memo='$memo' where id='$id';";
	$res = $dbh->query( $sql );
	$dbh = null;
	return json_encode( array( 'res'=>'Contact mis à jour' ) );
};
function erasecontact( $id ) {
	global $tab_contacts;
	$dbh = connectBDD();
	$sql = "DELETE from $tab_contacts where id ='$id'";
	$res = $dbh->query( $sql );
	$dbh = null;
	return json_encode( array( 'res'=>'Contact supprimé' ) );
}
?>