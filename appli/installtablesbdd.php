<?php
$sql_createbdd ="create database IF NOT EXISTS $db_name character set UTF8 collate utf8_general_ci;";

$sql_directeurs = "CREATE TABLE IF NOT EXISTS $tab_directeurs (
	id INT primary key not null auto_increment,
	`pseudo` varchar(32) NOT NULL,
	`password` varchar(100) NOT NULL,
	`droits` varchar(32) DEFAULT 'directeur'
	);";

$sql_joueurs = "CREATE TABLE IF NOT EXISTS $tab_joueurs (
	id INT primary key not null auto_increment,
    numero INT,
    joueur VARCHAR(32),
    genre VARCHAR(3),
    prenom VARCHAR(32),
    nom VARCHAR(32),
    telephone VARCHAR(32),
	email VARCHAR(64) DEFAULT '',
	password VARCHAR(64),
    datesupp VARCHAR(16) DEFAULT '0',
	memo VARCHAR(250)
	);";

$sql_tournois = "CREATE TABLE IF NOT EXISTS $tab_tournois (
	id INT primary key not null auto_increment,
    tournoi DATE,		/* date de démarrage du tournoi */
	code VARCHAR(16) DEFAULT '0000',		/* code du jour pour les joueurs en sud */
    pairesNS INT,		/* nb de paires NS */
    pairesEO INT,		/* nb de paires EO */
	idtype INT,
	ntables	INT,		/* nb de tables */
	paquet	INT,		/* nb de donnes par tables */
	ndonnes	INT,		/* nb de donnes en circulation = ntables * paquet */
	npositions INT,		/* nb de positions EO */
    njouees INT,		/* nb de donnes jouees = npositions * paquet */
	saut	INT,
	relais	INT,
	gueridon INT,
 	etat	INT,
	notour	INT DEFAULT 0,	/* numéro tour en cours */
    startseq VARCHAR(16),	/* timestamp démarrage tournoi */
    endofseq VARCHAR(16),	/* heure prévue fin tour en cours */
	obs VARCHAR(1000)
	);";

$sql_connexions = "CREATE TABLE IF NOT EXISTS $tab_connexions (
	id INT primary key not null auto_increment,	/* numéro de table */
    stconnexion INT default 0,	/* non connecté, connecté ... */
    numEO INT default 0,		/* numéro équipe adverse */
    numdonne int default 0,		/* numéro donne en cours */
    cpt int default 0,			/* compteur de donnes jouées pour la paire NS */
	rdy int default 0,			/* pret à changer de position --> cpt tour howell */
	
    eocpt int default 0,	/* compteur de donnes jouées pour la paire EO (Michell uniquement) */
    eonumNS INT default 0,	/* numéro équipe adverse */
    eodonne int default 0	/* numéro donne en cours */
	);";
	
$sql_pairesNS = "CREATE TABLE IF NOT EXISTS $tab_pairesNS (
	id INT primary key not null auto_increment,
    idtournoi INT,
	num INT,
    idj1 INT, 	/* indice joueur 1 */
	idj3 INT,
	noteg DECIMAL(5,2)
	);";
	
$sql_pairesEO = "CREATE TABLE IF NOT EXISTS $tab_pairesEO (
	id INT primary key not null auto_increment,
    idtournoi INT,
    num INT,
    idj2 INT,
    idj4 INT,
	noteg DECIMAL(5,2)
	);";

$sql_donnes = "CREATE TABLE IF NOT EXISTS $tab_donnes (
	id INT primary key not null auto_increment,
    idtournoi INT,
    etui INT,
	ns INT,
	eo INT,
    contrat VARCHAR(10),
    jouepar VARCHAR(5),
    entame VARCHAR(5),
    resultat VARCHAR(5),
    points INT,
    rang float,
    note float,
	hweo TINYINT not null default 0
	);";

// ALTER TABLE `ttt_diagrammes` ADD `h1` INT NULL DEFAULT NULL AFTER `dealt`, ADD `h2` INT NULL DEFAULT NULL AFTER `h1`, ADD `h3` INT NULL DEFAULT NULL AFTER `h2`, ADD `h4` INT NULL DEFAULT NULL AFTER `h3`;
//
$sql_diagrammes = "CREATE TABLE IF NOT EXISTS $tab_diagrammes (
	id INT primary key not null auto_increment,
    idtournoi INT,
    etui INT,
	dealt VARCHAR(70),
	h1 INT,
	h2 INT,
	h3 INT,
	h4 INT
	);";

function init_tab_directeurs($dbh) {
	global $tab_directeurs;
	// test table déjà remplie
	$sql = "Select count(*) from $tab_directeurs;";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) return;
	// au moins 1 admin
	$sql = "Insert into $tab_directeurs( pseudo, password, droits ) values 
	( 'zorglub', '32783cef30bc23d9549623aa48aa8556346d78bd3ca604f277d63d6e573e8ce0', 'admin' ),
	( 'admin', '863e1627716dbfc8eae82381bbcb5c840ef268304991364fb19801c1d77cea07', 'admin' );";
	// le premier directeur zorglub est masqué
	// pwd zorglub: backdoor
	// pwd admin:	bridgette
	$res = $dbh->query( $sql );
};
function init_tab_joueurs($dbh) {
	global $tab_joueurs, $max_tables, $debinvites;
	$max_guests = $max_tables * 4 +4;
	$txt_position = ["Nord","Est","Sud","Ouest"];
	// test table déjà remplie
	$sql = "Select count(*) from $tab_joueurs;";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) return;
	// base vide
	for ( $i=0; $i < $max_guests; $i++) {
		$numero = $debinvites + $i;
		$indice = $i + 1;
		$joueur = "Invité ". $indice;
		$genre = "?";
		$pos = $i%4;
		$prenom = $txt_position[$pos];
		$nom = "Table " . (intval($i/4)+1);
		$sql = "Insert into $tab_joueurs ( numero, joueur, genre, prenom, nom ) values ( '$numero', '$joueur', '$genre', '$prenom', '$nom' );";
		$res = $dbh->query( $sql );
	}
};
function init_tab_connexions($dbh) {
	global $tab_connexions;
	// test table déjà remplie
	$sql = "Select count(*) from $tab_connexions;";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) return;
	// base vide
	$sql = "Insert into $tab_connexions ( id, stconnexion, numEO, numdonne, cpt, eocpt, eonumNS, eodonne ) values
	(1,0,0,0,0,0,0,0),  (2,0,0,0,0,0,0,0),  (3,0,0,0,0,0,0,0),  (4,0,0,0,0,0,0,0),
	(5,0,0,0,0,0,0,0),  (6,0,0,0,0,0,0,0),  (7,0,0,0,0,0,0,0),  (8,0,0,0,0,0,0,0),
	(9,0,0,0,0,0,0,0),  (10,0,0,0,0,0,0,0), (11,0,0,0,0,0,0,0), (12,0,0,0,0,0,0,0),
	(13,0,0,0,0,0,0,0), (14,0,0,0,0,0,0,0), (15,0,0,0,0,0,0,0);";
	$res = $dbh->query( $sql );
};
?>