<?php
// paramètrage général
// valeurs par défaut
$defopendays = array( 'lundi' =>'1', 'mardi' => '1', 'mercredi' => '1', 'jeudi' => '1', 'vendredi' => '1', 'samedi' => '1', 'dimanche' => '1' );	// ouvert tous les jours par défaut
$parametres = array(
	'mailcopie' => "",
	
	'dureedonne' => 7.5,
	'dureediagrammes' => 2,
	'dureeinitiale' => 3,
		
	'maxt' => 10,		// taille tableau des anciens tournois
	'maxbj' => 10,		// supprimé taille tableau des joueurs actifs
	'maxdel' => 10,		// supprimé taille tableau des joueurs effacés
	'maxw' => 700,		// taille fenêtre pour passer en affichage 2 colonnes
	'maxw2' => 300,		// taille fenêtre pour passer en affichage réduit
	
	'checkin' => 0,		// check pour les joueurs
	'checkuser' => 0,	// check ident joueur
	'affimp'  => 0,		// Affichage IMP pour tournoi 4/5 paires: 0 %, 1 IMP
	'affprov' => 0,		// Affichage résultats provisoires
	'back241' => 0,		// retour 41 après démarrage
	'avancem' => 0,		// Mitchell: pas d'attente pour changer de position
	'avanceh' => 1,		// Howell: Changement de position synchrone
	
	'affperf' => 0,		// Affichage des performances
	'nbjperf' => 10,	// taille tableau des n premiers joueurs
	'nbmperf' => 3,		// nb de mois
	'minperf' => 15,	// nb minimum de tournois joués
	
	'param1'  => 0,		// $teston
	'param2'  => 0,		// ???
	
	'opendays'=> $defopendays,
);
// valeurs enregistrées
if ( file_exists( $file_params ) ) {
	$parametres = json_decode( file_get_contents( $file_params ), true );
	if ( !isset($parametres['avancem'] ) ) $parametres['avancem'] = 0;
	if ( !isset($parametres['avanceh'] ) ) $parametres['avanceh'] = 1;
	if ( !isset($parametres['opendays'] ) ) $parametres['opendays'] = $defopendays;
}

//tests de qualification
// 0	// pas de test
// 1	// remplissage aléatoires des résultats de chaque donne
// 2	// test tournoi avec données réelles - supprimé
$teston = $parametres['param1'];

//autres variables constantes non modifiables par le paramétrage admin
$max_tables = 15;		# limitation à 15

$idtype_table_libre = 1;	// type de tournoi table libre

$min_noclub = 100;	// recherche numéro club disponible pour un nouveau joueur
//$max_noclub		// fonction de l'abonnement du club
$del_noclub = 12;	// délai en mois avant réattribution du numéro
$debinvites = 4;

$st_notfound = 0;		# tournoi non trouvé
$st_mail = 1;			# résultats provenant d'un dépouillement externe
$st_appli = 2;			# résultats calculés par mon appli
//Tournoi en cours
$st_phase_init = 31;	# saisie des données avant le début du tournoi
//$st_phase_init2 = 32;	# inutilisé
$st_phase_jeu = 33;		# saisie des résultats au fur et à mesure
$st_phase_fini = 34;	# calculs finaux avant de stocker les résultats en bdd
$st_closed = 4;			# terminé
$st_unetable = 5;		# donne libre
$st_erased = 6;			# effacé, mais non purgé !
$st_err_requete = -1;

$st_text = [
	$st_notfound => "tournoi non trouvé",
	$st_mail => "résultats provenant d'un dépouillement externe",
	$st_appli => "résultats calculés par mon appli",
	$st_phase_init => "phase initialisation",
	$st_phase_jeu => " phase jeu",
	$st_phase_fini => "phase clôture",
	$st_closed => "clôturé",
	$st_unetable => "donnes libres",
	$st_erased => "effacé",
	$st_err_requete => "Erreur requete !",
	];

$pos_indefini = 0;
$pos_Nord = 1;
$pos_Est = 2;
$pos_Sud = 3;
$pos_Ouest = 4;

$st_position = [
	$pos_indefini =>"???",
	$pos_Nord => "Nord",
	$pos_Est => "Est",
	$pos_Sud => "Sud",
	$pos_Ouest => "Ouest"
	];
	
$st_vulnerable = [
	0 => "Personne vulnérable",
	1 => "Nord-Sud vulnerables",
	2 => "Est-Ouest vulnerables",
	3 => "Tous vulnérables"
	];

$col_indefini = 0;
$col_trefle = 1;
$col_carreau = 2;
$col_coeur = 3;
$col_pique = 4;
$col_sansatout = 5;

$dbl_indefini = 0;
$dbl_simple = 1;
$dbl_contre = 2;
$dbl_surcontre = 3;

$cnx_indefini = 0;
$cnx_ko = 1;	// attente connexion smartphone
$cnx_ok = 2;	// smartphone connecté
$cnx_fin = 3;	// tournoi terminé, smartphone s'est déconnecté

function connectBDD() {
	global $db_host, $db_port, $db_user, $db_password, $db_name;
	$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8";
	try { $p = new PDO($dsn, $db_user, $db_password); }
	catch (PDOException $e) {
		echo '<p>Erreur connexion : ' . $e->getMessage() . '</p>';
		//phpinfo();
		if ( extension_loaded('pdo_mysql') ){
			$code = $e->getCode();
			switch( $code ) {
				case 2002:	// SQLSTATE[HY000] [2002] php_network_getaddresses: gethostbyname failed. errno=0
					echo "<p>KO 2002: vérifier le site d'hébergement de la base de données</p>";
					echo "<p>dsn: $dsn</p>";
					break;
				case 1045:	// SQLSTATE[HY000] [1045] Access denied for user 'bruno'@'localhost' (using password: YES)
					echo "<p>KO 1045: vérifier le nom d'utilisateur de la base de données</p>";
					echo "<p>dsn: $dsn</p>";
					break;
				case 1049:	// SQLSTATE[HY000] [1049] Unknown database 'db_name'
					echo "<p>KO 1045: vérifier le nom de la base de données</p>";
					echo "<p>dsn: $dsn</p>";
					break;
			}
			echo '<p></p>';
		}
		else {
			echo '<p>extension PDO_MYSQL non trouvée</p>';
			echo '<p>modifier la config php pour inclure le driver PDO</p>';
		}
		echo "<p><b>Vérifiez l'installation de l'application !</b></p>";
		die();
	}
	return $p;
};

// routines
setlocale(LC_TIME, 'fr_FR.utf8','fra');
	
$strmois = [ "zéro", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" ];

function strtoday() {
	global $strmois;
	$d = date('Y-m-d');
	$dd = explode( "-", $d );
	return( $dd[2] . " " . $strmois[intval($dd[1])] . " " . $dd[0] );
};
function strdatef( $j, $m, $a ) {
	global $strmois;
	return( strval($j) . " " . $strmois[intval($m)] . " " . strval($a) );
};
function strdatet( $datet ) {
	global $strmois;
	$j = substr($datet, 8, 2 );
	$m = substr($datet, 5, 2 );
	$a = substr($datet, 0, 4 );
	$mm = $strmois[intval($m)];
	return( strval($j) . " " . "$mm" . " " . strval($a) );
};

function getSymboleCouleur( $tt ) {
	global $relimg;
	$code = "<img src='".$relimg;
	switch($tt) {
		case ("P"):
			$code .= "pique.png'";
			break;
		case ("C"):
			$code .= "coeur.png'";
			break;
		case ("K"):
			$code .= "carreau.png'";
			break;
		case ("T"):
			$code .= "trefle.png'";
			break;
		default:
			$code .= "sans-atout.png'";
			break;
	}
	$code .= ' height="13" />';
	return $code;
};
function htmlEntame( $entame ) {
	if ( $entame == "-" ) {
		// passe général, pas d'entame
		return $entame;
	}
	$niv = $entame[0];
	if ( $niv == 1 ) {
		$niv = 10;
		$col = getSymboleCouleur( $entame[2] );
	}
	else {
		$col = getSymboleCouleur( $entame[1] );
	}
	return( $niv . $col );
}
function htmlContrat( $contrat ) {
	$niv = $contrat[0];
	if ( ($niv == "-")||($niv == "P")||($niv == "M")||($niv == "N") ) {
		// passe général, moyenne générale, non jouée
		return $contrat;
	}
	$col = substr( $contrat, 2);
	$col = getSymboleCouleur( $contrat[2] );
	$n = substr_count( $contrat, "X" );
	if ( $n > 0 ) $add = " " . str_repeat( "X", $n );
	else $add = "";
	return( $niv . " " . $col . $add );
}
function htmlResultat( $result ) {
	if ( $result == "-" ) {
		// passe général, pas d'entame
		return( "-" );
	}
	if ( $result == "0" ) return( "=" );
	if ( $result > 0 ) return( "+" . $result );
	return( $result );
}
// routines sessions
$ID_NOIDENT		= -1;
$ID_INCORRECT	= 0;
$ID_CORRECT		= 1;
function setIdent( $userid ) {
	global $parametres, $prefix, $maxdureesession;
	$ident = array( 'ttt' => $prefix, 'userid' => $userid );
	$jsonIdent  = json_encode($ident);
	$cookieUser = base64_encode( $jsonIdent );
	setcookie('bridgette_user', $cookieUser, time()+$maxdureesession);
}
function getIdent() {
	global $parametres, $prefix, $maxdureesession;
	global $ID_NOIDENT, $ID_INCORRECT, $ID_CORRECT;
	if ( $parametres['checkuser'] > 0 ) {
		if ( !isset($_COOKIE['bridgette_user']) ) {
			$result = array( 'status' => $ID_INCORRECT, 'userid' => 0 );
		}
		else {
			$cookieUser = $_COOKIE['bridgette_user'];
			$jsonIdent  = base64_decode( $cookieUser );
			$ident = json_decode( $jsonIdent, true );
			if ( $ident['ttt'] == $prefix ) {
				// même club
				$result = array( 'status' => $ID_CORRECT, 'userid' => $ident['userid'] );
				// actualise le cookie, nouvelle date d'expiration
				setcookie('bridgette_user', $cookieUser, time()+$maxdureesession);
			}
			else {
				// club différent, redemande login
				$result = array( 'status' => $ID_INCORRECT, 'userid' => 0 );
			}
		}
	}
	else {
		$result = array( 'status' => $ID_NOIDENT, 'userid' => 0 );
	}
	return $result;
}
function isCheckedJoueur($idtournoi) {
	global $parametres;
	if ( $parametres['checkin'] > 0 ) {
		if ( $idtournoi > 0 ) {
			$t = readTournoi( $idtournoi );
			$code = $t[ 'code' ];

			if( isset($_SESSION["codedujour"]) ){
				$codedujour = $_SESSION["codedujour"];
				if( $codedujour == $code ) return true;
				else return false;
			}
			else return false;
		}
		else return true;
	}
	else return true;
}
function isAdmin() {
	if( isset($_SESSION["pseudo"]) ){
		if( $_SESSION["fonction"] == "admin" ) return true;
		return false;
	}
	else return false;
}
function isDirecteur() {
	if( isset($_SESSION["pseudo"]) ){
		if( $_SESSION["fonction"] == "directeur" ) return true;
		return false;
	}
	else return false;
}
function isFormateur() {
	if( isset($_SESSION["pseudo"]) ){
		if( $_SESSION["fonction"] == "formateur" ) return true;
		return false;
	}
	else return false;
}

//
// fonctions utilisant la table typetournoi
$t_unknown = 0;
$t_mitchell = 1;
$t_howell = 2;
$st_typetournoi = [
	$t_unknown	=> "???",
	$t_mitchell => "Mitchell",
	$t_howell	=> "Howell",
];
$def_genre = $t_howell;
$def_type_mitchell = 10;
$def_type_howell = 8;

$min_type_howell = 4;
$min_type_mitchell = 9;
$min_type_affimp = 5;

$min_tables_mitchell = 4;
$min_paires_howell = 4;
$max_paires_howell = 10;

$typetournois = array(
//'1'		=>array( 1,12,20,20, 1,20 ,0, 0, 0,"pas de tournoi","Paquet de donnes libres"),
//				[0]'npaires' 
//				 | [1]'ntables' y compris les tables relais, non compris le guéridon
//				 |  | [2]'paquet' de donnes par tables
//				 |  |  | [3]'ndonnes' en circulation
//				 |  |  |  | [4]'npositions' nb de positions
//				 |  |  |  |  | [5]'njouees' nb de donnes jouées
//				 |  |  |  |  |  | [6]'saut' si >0 saut après la xième position
//				 |  |  |  |  |  |  | [7] relais si >0, Howell: n°paire manquante, Mitchell: n°table relais
//				 |  |  |  |  |  |  |  |  [8]guéridon si =1
//				 |  |  |  |  |  |  |  |  |  [9] genre tournoi
//				 |  |  |  |  |  |  |  |  |  |			[10] description
'4'		=>array( 4, 4, 4,24, 6,24, 0, 0, 0,$t_howell,  "Equivalent Howell avec 2 tables, 3 positions jouant 2 paquets, échange des paquets en milieu de position, affichage en % ou IMP selon paramétrage"),
'5'		=>array( 5, 5, 5,20, 4,15, 0, 6, 0,$t_howell,  "Howell 5 paires avec 3 tables incomplètes, 4 positions, table relais variable en fonction du numéro de tour, affichage en % ou IMP selon paramétrage"),
'6'		=>array( 6, 6, 5,25, 5,25, 0, 0, 0,$t_howell,  "Howell 6 paires avec 3 tables, 5 positions, partage des donnes à la dernière position"),
'7'		=>array( 7, 7, 3,21, 7,18, 0, 8, 0,$t_howell,  "Howell 7 paires avec 4 tables incomplètes, 7 positions, table relais variable en fonction du numéro de tour"),
'47'	=>array( 7, 4, 4,16, 4,16, 0, 4, 1,$t_mitchell,"Mitchell, 4 tables incomplètes, 4 positions, guéridon entre les tables 2 et 3, pas d'étuis table 4, relais EO uniquement"),
'8'		=>array( 8, 8, 3,21, 7,21, 0, 0, 0,$t_howell,  "Howell 8 paires avec 4 tables, 7 positions"),
'48'	=>array( 8, 4, 4,16, 4,16, 0, 0, 1,$t_mitchell,"Mitchell, 4 tables complètes, 4 positions, guéridon entre les tables 2 et 3, pas d'étuis table 4, les tables 1 et 4 se partagent les étuis qui arrivent à la table 1"),
'32'	=>array( 9, 9, 3,27, 8,24, 0,10, 0,$t_howell,  "Howell 9 paires avec 5 tables incomplètes, 8 positions"),
'9'		=>array( 9, 5, 5,25, 5,20, 0, 5, 0,$t_mitchell,"Mitchell, 5 tables incomplètes, 5 positions, relais NS variable ou relais EO table 5"),
'33'	=>array(10,10, 3,27, 9,27, 0, 0, 0,$t_howell,  "Howell 10 paires avec 5 tables, 9 positions"),
'10'	=>array(10, 5, 5,25, 5,25, 0, 0, 0,$t_mitchell,"Mitchell, 5 tables complètes, 5 positions"),
'11'	=>array(11, 6, 4,24, 6,24, 0, 6, 1,$t_mitchell,"Mitchell, 6 tables incomplètes, 6 positions, guéridon entre les tables 3 et 4, pas d'étuis table 6, relais EO uniquement"),
'12'	=>array(12, 6, 4,24, 6,24, 0, 0, 1,$t_mitchell,"Mitchell, 6 tables complètes, 6 positions, guéridon entre les tables 3 et 4, pas d'étuis table 6, les tables 1 et 6 se partagent les étuis qui arrivent à la table 1"),
//'12'	=>array(12, 6, 5,30, 5,25, 3, 0, 0,$t_mitchell,"Mitchell, 6 tables complètes, 5 positions, saut après 3ème position"),
'13'	=>array(13, 7, 4,28, 7,28, 0, 7, 0,$t_mitchell,"Mitchell, 7 tables incomplètes, 7 positions, relais NS variable ou relais EO table 7"),
'14'	=>array(14, 7, 4,28, 7,28, 0, 0, 0,$t_mitchell,"Mitchell, 7 tables complètes, 7 positions"),
//'15'	=>array(15, 8, 4,32, 7,28, 4, 8, 0,$t_mitchell,"Mitchell, 8 tables incomplètes, 7 positions, saut après 4ème position, relais NS variable ou relais EO table 8"),
'15'	=>array(15, 8, 3,24, 8,24, 0, 8, 1,$t_mitchell,"Mitchell, 8 tables incomplètes, guéridon entre les tables 4 et 5, pas d'étuis table 8, relais EO uniquement"),
'16'	=>array(16, 8, 3,24, 8,24, 0, 0, 1,$t_mitchell,"Mitchell, 8 tables complètes, guéridon entre les tables 4 et 5, pas d'étuis table 8, les tables 1 et 8 se partagent les étuis qui arrivent à la table 1"),
//'16'	=>array(16, 8, 4,32, 7,28, 4, 0, 0,$t_mitchell,"Mitchell, 8 tables complètes, 7 positions, saut après 4ème position"),
'17'	=>array(17, 9, 3,27, 9,27, 0, 9, 0,$t_mitchell,"Mitchell, 9 tables incomplètes, 9 positions, relais EO table 9"),
'18'	=>array(18, 9, 3,27, 9,27, 0, 0, 0,$t_mitchell,"Mitchell, 9 tables complètes, 9 positions"),
'19'	=>array(19,10, 3,30, 9,27, 5,10, 0,$t_mitchell,"Mitchell, 10 tables incomplètes, 9 positions, saut après 5ème position, relais NS variable ou relais EO table 10"),
'20'	=>array(20,10, 3,30, 9,27, 5, 0, 0,$t_mitchell,"Mitchell, 10 tables complètes, 9 positions, saut après 5ème position"),
'21'	=>array(21,11, 3,33, 9,27, 0,11, 0,$t_mitchell,"Mitchell, 11 tables incomplètes, 9 positions, relais EO"),
'22'	=>array(22,11, 3,33, 9,27, 0, 0, 0,$t_mitchell,"Mitchell, 11 tables complètes, 9 positions"),
'23'	=>array(23,12, 2,24,12,24, 0,12, 1,$t_mitchell,"Mitchell, 12 tables incomplètes, 12 positions, guéridon entre les tables 6 et 7, pas d'étuis au relais table 12"),
'24'	=>array(24,12, 3,36, 9,24, 6, 0, 0,$t_mitchell,"Mitchell, 12 tables complètes, 9 positions, saut après 6ème position"),
'25'	=>array(25,13, 2,26,13,26, 0,13, 0,$t_mitchell,"Mitchell, 13 tables incomplètes, 13 positions, relais EO table 13"),
'26'	=>array(26,13, 2,26,13,26, 0, 0, 0,$t_mitchell,"Mitchell, 13 tables complètes, 13 positions"),
'27'	=>array(27,14, 2,28,13,26, 7,14, 0,$t_mitchell,"Mitchell, 14 tables incomplètes, saut après 7ème position"),
'28'	=>array(28,14, 2,28,13,26, 7, 0, 0,$t_mitchell,"Mitchell, 14 tables complètes, saut après 7ème position"),
'29'	=>array(29,15, 2,30,13,26, 0,15, 0,$t_mitchell,"Mitchell, 15 tables incomplètes"),
'30'	=>array(30,15, 2,30,13,26, 0, 0, 0,$t_mitchell,"Mitchell, 15 tables complètes"),
//
//				[0]'npaires' 
//				 | [1]'ntables' y compris les tables relais, non compris le guéridon
//				 |  | [2]'paquet' de donnes par tables
//				 |  |  | [3]'ndonnes' en circulation
//				 |  |  |  | [4]'npositions' nb de positions
//				 |  |  |  |  | [5]'njouees' nb de donnes jouées
//				 |  |  |  |  |  | [6]'saut' si >0 saut après la xième position
//				 |  |  |  |  |  |  | [7] relais si >0, Howell: n°paire manquante, Mitchell: n°table relais
//				 |  |  |  |  |  |  |  |  [8]guéridon si =1
//				 |  |  |  |  |  |  |  |  |  [9] genre tournoi
//				 |  |  |  |  |  |  |  |  |  |			[10] description
);

function computetype( $pns, $peo ) {
	if ( $peo == 0 ) {
		// howell
		if ( $pns > 8 ) {
			return( $pns + 23 );
		}
		else {
			return $pns;
		}
	}
	else {
		// mitchell
		$np = $pns + $peo;
		if ( $np > 8 ) {
			return( $np );
		}
		else {
			return( $np + 40 );
		}
	}
}	
function gettypetournoi( $id ) {
	global $typetournois;
	if ( array_key_exists( $id, $typetournois ) ) {
		$tt = array(
			'npaires' 	=> $typetournois[$id][0],
			'ntables' 	=> $typetournois[$id][1],
			'paquet' 	=> $typetournois[$id][2],
			'ndonnes' 	=> $typetournois[$id][3],
			'npositions' => $typetournois[$id][4],
			'njouees' 	=> $typetournois[$id][5],
			'saut' 		=> $typetournois[$id][6],
			'relais' 	=> $typetournois[$id][7],
			'gueridon'	=> $typetournois[$id][8],
			'genre'	 	=> $typetournois[$id][9],
			'desc'	 	=> $typetournois[$id][10]
		);
	}
	else {
		$tt = array(
			'npaires' 	=> 0,
			'ntables' 	=> 0,
			'paquet' 	=> 0,
			'ndonnes' 	=> 0,
			'npositions' => 0,
			'njouees' 	=> 0,
			'saut' 		=> 0,
			'relais' 	=> 0,
			'gueridon'	=> 0,
			'genre'	 	=> $t_unknown,
			'desc'	 	=> "type tournoi inconnu",
		);
	}
	return $tt;
};
function getdescriptiontournoi($idtype) {
	$tt = gettypetournoi( $idtype );
	return $tt['desc'];
}
$warningPMR = "<span style='color:red'>la paire n°1 est à réserver aux joueurs à mobilité réduite.</span>";
function htmlTableTypeTournois() {
	global $warningPMR, $typetournois;
	$str = "<p>Pour les tournois de type Howell, la paire n°1 à la table 1 ne bouge pas pendant le tournoi, $warningPMR</p>";
	$str .= '<table border="0"><tbody>';
	$str .= '<tr>';
	$str .= '<th class="xTypt">Nb<br/>paires</th>';
	$str .= '<th class="xTypt">Nb<br/>étuis<br/>(*)</th>';
	$str .= '<th style="text-align:left" class="xTypt">Description (**)</th>';
	$str .= '</tr>';
	foreach ( $typetournois as $type ) {
		$str .= "<tr>";
		$npaires = $type[0];
		$paquet = $type[2];
		$desc = $type[10];
		$str .= "<td class='xTypt'><b>$npaires</b></td>";
		$str .= "<td class='xTypt'>$paquet</td>";
		$str .= "<td style='text-align:left;' class='xTypt'>$desc</td>";
		$str .= '</tr>';
	}
	$str .= '</tbody></table>';
	$str .= '<p>(*): valeur par défaut, modifiable <b>avant</b> le démarrage du tournoi<br/>(**): nombre de positions modifiable <b>après</b> le démarrage du tournoi</p>';
	return $str;
}

function getnpaires( $id ) {
	global $typetournois;
	return( $typetournois[$id][0] );
}

function getmaxpositions( $id ) {
	global $typetournois;
	return( $typetournois[$id][4] );	// pour éviter de repartir de la valeur modifiée en cas de rechargement de la page
}

function getposhowell( $idtype, $numpaire, $notour, $paquet ) {
	// Tableau des positions successives pour les tournois Howell
	$posHowell = Array(		// NS/EO __ N°table
	// Duplicate tournant 4 paires - 3 positions - 6 tours
	//Paire n° --> 	1			2			3			4
	//	Table_ns/eo_paquet_adversaire
	'4_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3' ),	// Positions tour n°1
	'4_2'=>Array( '1_1_2_2', '1_2_2_1', '2_1_1_4', '2_2_1_3' ),	// Positions tour n°2
	'4_3'=>Array( '1_1_3_3', '2_2_4_4', '1_2_3_1', '2_1_4_2' ),	// Positions tour n°3
	'4_4'=>Array( '1_1_4_3', '2_2_3_4', '1_2_4_1', '2_1_3_2' ),	// Positions tour n°4
	'4_5'=>Array( '1_1_5_4', '2_1_6_3', '2_2_6_2', '1_2_5_1' ),	// Positions tour n°5
	'4_6'=>Array( '1_1_6_4', '2_1_5_3', '2_2_5_2', '1_2_6_1' ),	// Positions tour n°6
	
	// Howell 5 paires - 5 positions avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 	1			2			3			4		5 en relais
	//	Table_position_paquet_adversaire
	'5_1'=>Array( '1_1_1_2', '1_2_1_1', '2_2_4_0', '3_2_2_5', '3_1_2_4', '2_1_4_3' ),	// Positions tour n°1
	'5_2'=>Array( '1_1_2_3', '2_1_4_4', '1_2_2_1', '2_2_4_2', '3_2_3_0', '3_1_3_5' ),	// Positions tour n°2
	'5_3'=>Array( '1_1_3_4', '3_1_2_0', '2_1_1_5', '1_2_3_1', '2_2_1_3', '3_2_2_2' ),	// Positions tour n°3
	'5_4'=>Array( '1_1_4_5', '3_2_3_3', '3_1_3_2', '2_1_1_0', '1_2_4_1', '2_2_1_4' ),	// Positions tour n°4
	
	// Howell 6 paires - 5 positions avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 	1			2			3			4		5			6
	//	Table_position_paquet_adversaire
	'6_1'=>Array( '1_1_1_2', '1_2_1_1', '2_2_4_6', '3_2_2_5', '3_1_2_4', '2_1_4_3' ),	// Positions tour n°1
	'6_2'=>Array( '1_1_2_3', '2_1_4_4', '1_2_2_1', '2_2_4_2', '3_2_3_6', '3_1_3_5' ),	// Positions tour n°2
	'6_3'=>Array( '1_1_3_4', '3_1_2_6', '2_1_1_5', '1_2_3_1', '2_2_1_3', '3_2_2_2' ),	// Positions tour n°3
	'6_4'=>Array( '1_1_4_5', '3_2_3_3', '3_1_3_2', '2_1_1_6', '1_2_4_1', '2_2_1_4' ),	// Positions tour n°4
	'6_5'=>Array( '1_1_5_6', '2_2_5_5', '3_2_5_4', '3_1_5_3', '2_1_5_2', '1_2_5_1' ),	// Positions tour n°5, le dernier paquet est partagé entre les 3 tables
	
	// Howell 7 paires type anglais
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7
	//	Table_position_paquet_adversaire 					en relais
	'7_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3', '3_1_3_6', '3_2_3_5', '4_1_5_0', '4_2_5_7' ),	// tour n°1
	'7_2'=>Array( '1_1_2_6', '4_1_6_5', '2_2_3_7', '3_1_4_0', '4_2_6_2', '1_2_2_1', '2_1_3_3', '3_2_4_4' ),	// tour n°2
	'7_3'=>Array( '1_1_3_0', '2_1_4_7', '3_1_5_5', '4_2_7_6', '3_2_5_3', '4_1_7_4', '2_2_4_2', '1_2_3_1' ),	// tour n°3
	'7_4'=>Array( '1_1_4_5', '2_2_5_6', '4_2_1_0', '3_2_6_7', '1_2_4_1', '2_1_5_2', '3_1_6_4', '4_1_1_3' ),	// tour n°4
	'7_5'=>Array( '1_1_5_4', '3_1_7_3', '3_2_7_2', '1_2_5_1', '4_1_2_7', '2_2_6_0', '4_2_2_5', '2_1_6_6' ),	// tour n°5
	'7_6'=>Array( '1_1_6_3', '4_2_3_4', '1_2_6_1', '4_1_3_2', '2_1_7_0', '3_1_1_7', '3_2_1_6', '2_2_7_5' ),	// tour n°6
	'7_7'=>Array( '1_1_7_7', '3_2_2_0', '4_1_4_6', '2_1_1_5', '2_2_1_4', '4_2_4_3', '1_2_7_1', '3_1_2_2' ),	// tour n°7
	
	// Howell 8 paires type anglais avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8
	//	Table_position_paquet_adversaire 					en relais
	'8_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3', '3_1_3_6', '3_2_3_5', '4_1_5_8', '4_2_5_7' ),	// tour n°1
	'8_2'=>Array( '1_1_2_6', '4_1_6_5', '2_2_3_7', '3_1_4_8', '4_2_6_2', '1_2_2_1', '2_1_3_3', '3_2_4_4' ),	// tour n°2
	'8_3'=>Array( '1_1_3_8', '2_1_4_7', '3_1_5_5', '4_2_7_6', '3_2_5_3', '4_1_7_4', '2_2_4_2', '1_2_3_1' ),	// tour n°3
	'8_4'=>Array( '1_1_4_5', '2_2_5_6', '4_2_1_8', '3_2_6_7', '1_2_4_1', '2_1_5_2', '3_1_6_4', '4_1_1_3' ),	// tour n°4
	'8_5'=>Array( '1_1_5_4', '3_1_7_3', '3_2_7_2', '1_2_5_1', '4_1_2_7', '2_2_6_8', '4_2_2_5', '2_1_6_6' ),	// tour n°5
	'8_6'=>Array( '1_1_6_3', '4_2_3_4', '1_2_6_1', '4_1_3_2', '2_1_7_8', '3_1_1_7', '3_2_1_6', '2_2_7_5' ),	// tour n°6
	'8_7'=>Array( '1_1_7_7', '3_2_2_8', '4_1_4_6', '2_1_1_5', '2_2_1_4', '4_2_4_3', '1_2_7_1', '3_1_2_2' ),	// tour n°7
	
	// Howell 9 paires
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8			9		10
	//	Table_position_paquet_adversaire 					en relais
	'9_1'=>Array( '1_1_1_0', '5_1_9_5', '4_1_8_7', '3_2_3_6', '5_2_9_2', '3_1_3_4', '4_2_8_3', '2_1_2_9', '2_2_2_8', '1_2_1_1' ),	// tour n°1
	'9_2'=>Array( '1_1_2_2', '1_2_2_1', '5_1_1_6', '4_1_9_8', '3_2_4_7', '5_2_1_3', '3_1_4_5', '4_2_9_4', '2_1_3_0', '2_2_3_9' ),	// tour n°2
	'9_3'=>Array( '1_1_3_3', '2_2_4_0', '1_2_3_1', '5_1_2_7', '4_1_1_9', '3_2_5_8', '5_2_2_4', '3_1_5_6', '4_2_1_5', '2_1_4_2' ),	// tour n°3
	'9_4'=>Array( '1_1_4_4', '2_1_5_3', '2_2_5_2', '1_2_4_1', '5_1_3_8', '4_1_2_0', '3_2_6_9', '5_2_3_5', '3_1_6_7', '4_2_2_6' ),	// tour n°4
	'9_5'=>Array( '1_1_5_5', '4_2_3_7', '2_1_6_4', '2_2_6_3', '1_2_5_1', '5_1_4_9', '4_1_3_2', '3_2_7_0', '5_2_4_6', '3_1_7_8' ),	// tour n°5
	'9_6'=>Array( '1_1_6_6', '3_1_8_9', '4_2_4_8', '2_1_7_5', '2_2_7_4', '1_2_6_1', '5_1_5_0', '4_1_4_3', '3_2_8_2', '5_2_5_7' ),	// tour n°6
	'9_7'=>Array( '1_1_7_7', '5_2_6_8', '3_1_9_0', '4_2_5_9', '2_1_8_6', '2_2_8_5', '1_2_7_1', '5_1_6_2', '4_1_5_4', '3_2_9_3' ),	// tour n°7
	'9_8'=>Array( '1_1_8_8', '3_2_1_4', '5_2_7_9', '3_1_1_2', '4_2_6_0', '2_1_9_7', '2_2_9_6', '1_2_8_1', '5_1_7_3', '4_1_6_5' ),	// tour n°8
	'9_9'=>Array( '1_1_9_9', '4_1_7_6', '3_2_2_5', '5_2_8_0', '3_1_2_3', '4_2_7_2', '2_1_1_8', '2_2_1_7', '1_2_9_1', '5_1_8_4' ),	// tour n°9

	// Howell 10 paires
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8			9		10
	//	Table_position_paquet_adversaire 					en relais
	'10_1'=>Array( '1_1_1_10', '5_1_9_5', '4_1_8_7', '3_2_3_6', '5_2_9_2', '3_1_3_4', '4_2_8_3', '2_1_2_9', '2_2_2_8', '1_2_1_1' ),	// tour n°1
	'10_2'=>Array( '1_1_2_2', '1_2_2_1', '5_1_1_6', '4_1_9_8', '3_2_4_7', '5_2_1_3', '3_1_4_5', '4_2_9_4', '2_1_3_10', '2_2_3_9' ),	// tour n°2
	'10_3'=>Array( '1_1_3_3', '2_2_4_10', '1_2_3_1', '5_1_2_7', '4_1_1_9', '3_2_5_8', '5_2_2_4', '3_1_5_6', '4_2_1_5', '2_1_4_2' ),	// tour n°3
	'10_4'=>Array( '1_1_4_4', '2_1_5_3', '2_2_5_2', '1_2_4_1', '5_1_3_8', '4_1_2_10', '3_2_6_9', '5_2_3_5', '3_1_6_7', '4_2_2_6' ),	// tour n°4
	'10_5'=>Array( '1_1_5_5', '4_2_3_7', '2_1_6_4', '2_2_6_3', '1_2_5_1', '5_1_4_9', '4_1_3_2', '3_2_7_10', '5_2_4_6', '3_1_7_8' ),	// tour n°5
	'10_6'=>Array( '1_1_6_6', '3_1_8_9', '4_2_4_8', '2_1_7_5', '2_2_7_4', '1_2_6_1', '5_1_5_10', '4_1_4_3', '3_2_8_2', '5_2_5_7' ),	// tour n°6
	'10_7'=>Array( '1_1_7_7', '5_2_6_8', '3_1_9_10', '4_2_5_9', '2_1_8_6', '2_2_8_5', '1_2_7_1', '5_1_6_2', '4_1_5_4', '3_2_9_3' ),	// tour n°7
	'10_8'=>Array( '1_1_8_8', '3_2_1_4', '5_2_7_9', '3_1_1_2', '4_2_6_10', '2_1_9_7', '2_2_9_6', '1_2_8_1', '5_1_7_3', '4_1_6_5' ),	// tour n°8
	'10_9'=>Array( '1_1_9_9', '4_1_7_6', '3_2_2_5', '5_2_8_10', '3_1_2_3', '4_2_7_2', '2_1_1_8', '2_2_1_7', '1_2_9_1', '5_1_8_4' ),	// tour n°9
	);
	$npaires = getnpaires($idtype);
	$itour = $npaires ."_".$notour;
	$tour  = $posHowell[ $itour ];
	$stri  = $tour[$numpaire - 1];
	
	$figs = explode( "_", $stri );
	$p = array(
		'table'	=> $figs[0],					// numéro de table
		'NS'	=> ($figs[1] == 1) ? 1 : 2,	// orientation
		'last'	=> ($figs[2]-1)*$paquet,		// dernière donne "jouée"
		'adversaire'=> $figs[3],
		);
	return $p;
}
function getrelaishowell( $idtype, $notour, $paquet ) {
	$tt = gettypetournoi( $idtype );
	if ($tt['relais'] == 0 ) return 0;
	$maxtour	= $tt['npositions'];
	$maxpaires	= $tt['ntables'];
	for ($i=1; $i<=$maxpaires; $i++) {
		$p = getposhowell( $idtype, $i, $notour, $paquet );
		$adversaire = intval( $p['adversaire'] );
		if ( $adversaire == 0 ) return $i;
	}
}
function getfeuillesuivihowell( $idtype, $paquet, $n ) {
	//	$idtype: le type de tournoi de howell
	//	$paquet = taille du paquet (choisi avant démarrage du tournoi)
	//	$n le numéro de de la 1ère donne du paquet
	$feuille = Array();
	// return un tableau avec:
	// n°tour(de 1 à maxtour) - n°paire(NS) - n°paire(EO)
	$tt = gettypetournoi( $idtype );
	$maxtour	= $tt['npositions'];
	$maxpaires	= $tt['ntables'];
	$k = 0;
	for ($i=1; $i<=$maxtour; $i++) {
		// recherche si donne $n jouée par une paire en NS
		for ($j=1; $j<=$maxpaires; $j++) {
			//getposhowell( $idtype, $numpaire, $notour, $paquet )
			$p = getposhowell( $idtype, $j, $i, $paquet );
			if ( $p['NS'] != 1 ) continue;
			if ( ($p['last']+1)!= $n ) continue;
			$adversaire = intval( $p['adversaire'] );
			if ( $adversaire == 0 ) continue;
			// trouvé
			$feuille[$k]['found'] = 1;
			$feuille[$k]['NS'] = $j;
			$feuille[$k]['EO'] = $adversaire;
			$k++;
		}
	}
	$feuille[$k]['found'] = 0;
	return $feuille;
}
function htmlPositionHowell($idtype, $pns, $npos, $paquet) {
	$tab = "<table style='width:95%; max-width: 350px; margin:auto;'><tbody>";
	$tab .= "<tr><th class='xNum3' colspan='4'>Tour $npos</th></tr>";
	$tab .= "<tr><td class='xNum3'>Paire</td><td class='xNum3'>Table</td>";
	$tab .= "<td class='xNum3'>NS/EO</td><td class='xNum3'>Donnes</td></tr>";
	for ( $j = 1; $j <= $pns; $j++ ) {
		//$p = getposhowell( $idtype, $numpaire, $notour, $paquet );
		$p = getposhowell( $idtype, $j, $npos, $paquet );
		$t = $p['table'];	// numéro de table
		$o = ($p['NS'] == 1) ? "NS" : "EO"; 		// orientation: 1=NS, 2=EO
		$l1 = $p['last'] + 1;		// dernière donne "jouée"
		$l2 = $p['last'] + $paquet;	
		$a = $p['adversaire'];
		$tab .= "<tr><td class='xNum3'>$j</td><td class='xNum3'>$t</td><td class='xNum3'>$o</td><td class='xNum3'>$l1 à $l2</td></tr>";
	}
	$tab .= "</tbody></table>";
	return $tab;
}
// fonctions utilisant la table etuis
$maxetuis = 36;	// Nb max de donnes figurant sur la feuille de marque
function getetui( $n ) {
	global $st_position, $st_vulnerable;
	$etuis = array ( array (0,0,0,0),
	array (1,1,0,0),  array (2,2,1,0),  array (3,3,0,1),  array (4,4,1,1),
	array (5,1,1,0),  array (6,2,0,1),  array (7,3,1,1),  array (8,4,0,0),
	array (9,1,0,1),  array (10,2,1,1), array (11,3,0,0), array (12,4,1,0),
	array (13,1,1,1), array (14,2,0,0), array (15,3,1,0), array (16,4,0,1),
	array (17,1,0,0), array (18,2,1,0), array (19,3,0,1), array (20,4,1,1),
	array (21,1,1,0), array (22,2,0,1), array (23,3,1,1), array (24,4,0,0),
	array (25,1,0,1), array (26,2,1,1), array (27,3,0,0), array (28,4,1,0),
	array (29,1,1,1), array (30,2,0,0), array (31,3,1,0), array (32,4,0,1),
	array (33,1,0,0), array (34,2,1,0), array (35,3,0,1), array (36,4,1,1)
	);
	$etui = [
		'n' 	=> $etuis[$n][0],
		'donneur' => $etuis[$n][1],
		'vulns' => $etuis[$n][2],
		'vuleo' => $etuis[$n][3],
		'info'	=> $st_position[ $etuis[$n][1] ] . " donneur - " . $st_vulnerable[ $etuis[$n][2] + 2*$etuis[$n][3] ],
	];
	return $etui;
};
function getinfoetui( $n ) {
	$etui = getetui( $n );
	return $etui['info'];
}
function liste_etuis( $encours, $paquet ) {
	$first = intval(($encours-1)/$paquet)*$paquet +1;
	$strhtml = "<table style='margin:auto;'><tbody><tr>";
	//$strhtml .= "<td>Etuis&nbsp;</td>";
	for ($i=0;$i<$paquet;$i++) {
		$etui = $first + $i;
		if ( $etui == $encours ) $bg="background-color:lightgreen;";
		else $bg = "";
		$strhtml .= "<td style='border:.5pt solid windowtext; min-width:30px; $bg'>$etui</td>";
	}
	$strhtml .= "</tr></tbody></table>";
	return $strhtml;
}

// fonctions utilisant la table "donnes"
//TABLE donnes ( id INT primary key not null auto_increment,
//    idtournoi INT, etui INT, ns INT, eo INT,
//    contrat VARCHAR(10), jouepar VARCHAR(5), entame VARCHAR(5), resultat VARCHAR(5),
//    points INT, rang float, note float );
// ALTER TABLE `xydonnes` ADD `hweo` TINYINT NULL DEFAULT 0 AFTER `note`;
$contratNJ = "N J";
function insertDonne( $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points ) {
	global $tab_donnes, $st_phase_jeu, $st_phase_fini, $tab_connexions, $cnx_ok, $t_howell;
	global $tab_tournois, $parametres;
	$result = array( 'ok'=> 0, 'display'=>" ");
	// test tournoi en cours
	$t = readTournoi( $idt );
	$etat  = $t['etat'];
	if ( $etat == $st_phase_jeu ) {
		// tournoi en cours
		
		$dbh = connectBDD();
		$dbh->query("LOCK TABLES $tab_donnes WRITE, $tab_connexions WRITE, $tab_tournois WRITE;");
		// test donne déjà enregistrée
		$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd' and ns='$ns' and eo='$eo';";
		$res = $dbh->query($sql);
		if ( $res->fetchColumn() == 0 ) {
			$hweo = 0;
			// enregistrement de la donne
			$sql = "INSERT into $tab_donnes ( idtournoi, etui, ns, eo, contrat, jouepar, entame, resultat, points, hweo ) values ( '$idt', '$dd', '$ns', '$eo', '$contrat', '$jouepar', '$entame', '$resultat', '$points', '$hweo' );";
			$sth = $dbh->query( $sql );
			$result[ 'ok' ] = 1;
			$result[ 'display' ] = "Enregistrement terminé.";
			
			// calcul du rang
			_setRang( $idt, $dd, $dbh, $hweo );
		
			// Incrémentation du compteur de donnes jouées/enregistrées par la paire ns
			$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$ns'" );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$cpt = $row['cpt'];
			$cpt ++;	// donne suivante
			
			// test changement de position / tour pour la table concernée
			$paquet = $t['paquet'];
			$change = $cpt % $paquet;
			$pos = $row['rdy'];
			if ( $change == 0 ) $pos++;
			$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ok', numEO='$eo', numdonne='$dd', cpt='$cpt', rdy ='$pos' where id = '$ns';";
			$res = $dbh->query($sql);			
			
			// test si donne symétrique howell
			if ( $t['genre'] == $t_howell ) {
				$hweo = 1;
				// enregistrement de la donne symétrique
				$points = -$points;
				$sql = "INSERT into $tab_donnes ( idtournoi, etui, ns, eo, contrat, jouepar, entame, resultat, points, hweo ) values ( '$idt', '$dd', '$eo', '$ns', '$contrat', '$jouepar', '$entame', '$resultat', '$points', '$hweo' );";
				$sth = $dbh->query( $sql );
				$result[ 'ok' ] = 1;
				$result[ 'display' ] = "Enregistrements terminés.";
			
				// calcul du rang
				_setRang( $idt, $dd, $dbh, $hweo );
				
				// Incrémentation du compteur de donnes jouées/enregistrées par la paire eo
				$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$eo'" );
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$cpt = $row[ 'cpt' ];
				$cpt ++;	// donne suivante
			
				// test changement de position / tour
				$paquet = $t[ 'paquet' ];
				$change = $cpt % $paquet;
				$pos = $row[ 'rdy' ];
				if ( $change == 0 ) $pos++;
				$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ok', numEO='$ns', numdonne='$dd', cpt='$cpt', rdy ='$pos' where id = '$eo';";
				$res = $dbh->query($sql);			
			}
			// Ajout pour tournoi Mitchell sur appli android
			// *********************************************
			else {
				// Incrémentation du compteur de donnes jouées/enregistrées par la paire eo
				$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$eo'" );
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$eocpt = $row['eocpt'];
				$eocpt ++;	// donne suivante
				$sql = "UPDATE $tab_connexions SET eonumNS='$ns', eodonne='$dd', eocpt='$eocpt' where id = '$eo';";
				$res = $dbh->query($sql);			
			}
			// test position suivante sans étui (tournoi avec guéridon)
			$gueridon = $t['gueridon'];
			if ($gueridon > 0) {
				// Relais NS
				$relaisNS = 0;
				$relaisEO = 0;
				if ( $t['relais'] > 0 ) {
					if ( $t['pairesNS'] > $t['pairesEO'] ) $relaisNS = $t['relais'];
					else $relaisEO = $t['relais'];
				}
				// test changement de position suivante en relais
				if ( $change==0 ) {
					if ( $ns == $relaisEO-1 ) {	// table avant le relais
						// Incrémentation du compteur de la paire eo en relais sans donne (guéridon)
						$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$eo'" );
						$row = $sth->fetch(PDO::FETCH_ASSOC);
						$eocpt = $row['eocpt'];
						$eocpt +=$paquet;

						$sql = "UPDATE $tab_connexions SET eocpt ='$eocpt' where id = '$eo';";
						$res = $dbh->query($sql);
					}
					// cas du relais NS variable à écrire
					// *************************************
				}
			}
			// Fin ajout
			// *********
			
			// détection changement de position / fin du tournoi
			$n = ( $t['genre'] == $t_howell )? $t['pairesNS'] : min( $t['pairesNS'], $t['pairesEO'] );
			$res = $dbh->query( "SELECT MIN(rdy) FROM $tab_connexions where id<=$n;" );
			$tour = $res->fetchColumn();
			
			// test dernière donne rentrée => fin du tournoi
			if ( $tour > $t['npositions'] ) {
				$sql = "UPDATE $tab_tournois SET etat = '$st_phase_fini' where id = '$idt';";
				$res = $dbh->query($sql);
			}
			else {
				if ( $tour > $t['notour'] ) {
					// enregistre changement de position
					$date = new DateTime();
					$startseq = $date->getTimestamp();	// heure démarrage du tour
					$endofseq = $startseq + $paquet * $parametres['dureedonne'] * 60;
					///$sequence = $tour."_".$endofseq;
					$sql = "UPDATE $tab_tournois SET notour = '$tour', endofseq = '$endofseq' where id = '$idt';";
					$res = $dbh->query($sql);
				}
			}
		}
		else {
			$result[ 'ok' ] = 0;
			$result[ 'display' ] = "Enregistrement déjà réalisé.";
		}
		$dbh->query("UNLOCK TABLES;");
		$dbh = null;
	}
	else {
		// donnes validées après terminaison du tournoi
		$result[ 'ok' ] = -1;
		$result[ 'display' ] = "Ignoré, tournoi terminé !";
	}
	return $result;
};
//function updateDonne( $idt, $id, $contrat, $jouepar, $entame, $resultat, $points ) {
function updateDonne( $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	
	// test donne déjà enregistrée
	$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd' and ns='$ns' and eo='$eo' and hweo=0;";
	$res = $dbh->query($sql);
	if ( $res->fetchColumn() == 1 ) {
		$sql = "UPDATE $tab_donnes SET contrat = '$contrat', jouepar = '$jouepar', entame = '$entame', resultat = '$resultat', points = '$points' where idtournoi='$idt' and etui='$dd' and ns='$ns' and eo='$eo' and hweo=0;";
		$sth = $dbh->query( $sql );
		_setRang( $idt, $dd, $dbh, 0 );
		
		// test si donne symétrique howell
		$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd' and ns='$eo' and eo='$ns' and hweo=1;";
		$res = $dbh->query($sql);
		if ( $res->fetchColumn() == 1 ) {
			$points = -$points;
			$sql = "UPDATE $tab_donnes SET contrat = '$contrat', jouepar = '$jouepar', entame = '$entame', resultat = '$resultat', points = '$points' where idtournoi='$idt' and etui='$dd' and ns='$eo' and eo='$ns' and hweo=1;";
			$sth = $dbh->query( $sql );
			_setRang( $idt, $dd, $dbh, 1 );
			$display_string = "Deux enregistrements mis à jour.";
		}
		else {
			$display_string = "Un enregistrement mis à jour.";
		}
	}
	else {
		$display_string = "Enregistrement non trouvé.";
	}
	
	$dbh->query("COMMIT;");
	$dbh = null;
	return $display_string;
};

function first2play( $idt, $dd ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd';";
	$res = $dbh->query($sql);
	return $res->fetchColumn();
}
function testnotplayed( $idt, $ns, $eo, $dd ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	// test donne déjà enregistrée
	$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd' and ns='$ns' and eo='$eo';";
	$res = $dbh->query($sql);
	$nb  = $res->fetchColumn();
	if ( $nb == 0 ) {
		// test autre orientation
		$sql = "SELECT count(*) from $tab_donnes where idtournoi='$idt' and etui='$dd' and ns='$eo' and eo='$ns';";
		$res = $dbh->query($sql);
		$nb  = $res->fetchColumn();
	}
	$dbh->query("COMMIT;");
	//print "<p>sql: $sql</p>";
	//print "<p>nb: $nb</p>";
	if ( $nb > 0 ) return false;
	else return true;
}
function _setRang( $idt, $dd, $dbh, $hweo ) {
	global $tab_donnes, $contratNJ;
	
	$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idt' and etui = '$dd' and hweo = '$hweo' and contrat !='$contratNJ';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl < 1 ) return;
	
	$sql = "SELECT id, ns, eo, points FROM $tab_donnes where idtournoi = '$idt' and etui = '$dd' and hweo = '$hweo' and contrat !='$contratNJ';";
	$tabid = array();
	$pns = array();
	$peo = array();
	$pps = array();
	$ordre = array();
	$j = 0;
	foreach ($dbh->query($sql) as $row) {
		$ordre[$j] = $j;
		$tabid[$j] = $row['id'];
		$pns[$j] = $row['ns'];
		$peo[$j] = $row['eo'];
		$pps[$j] = $row['points'];
		$j++;
		}
	$top = $j-1; // nb de fois où la donne a été jouée - 1
	// Si donne jouée une seule fois pour l'instant
	if ( $top == 0 ) {
		// 50% pour les 2 paires
		$id1 = $tabid[0];
		$pns1 = $pns[0];
		$peo1 = $peo[0];
		$sql = "UPDATE $tab_donnes SET rang = '0', note = '50' where id = '$id1';";
		$dbh->query($sql);
	}
	else {
		// tri des tableaux
		$rang = array();
		$note = array(); // utilisé pour compter le nb de paires ayant les mêmes points
		array_multisort( $pps, SORT_NUMERIC, SORT_ASC, $ordre );
		// print "<p>Après tri:</p>";
		for ( $jj = 0; $jj < $j; $jj++ ) {
			$ij = $ordre[ $jj ];
			$rang[ $ij ] = $jj;
			// print "<p>$jj PaireNS: $pns[$ij] PaireEO: $peo[$ij] Points: $pps[$jj]" ;
		};
		// calcul nb de résultats identiques
		for ( $jj =0; $jj < $j; $jj++ ) {
			$note[ $jj ] = 0;
			$rang[ $jj ] = 0;
			for ( $kk = $jj; $kk < $j; $kk++ ) {
				if ( $pps[$kk] == $pps[$jj] ) $note[$jj]++;
			};
		};
		// calcul rang
		for ( $k =0; $k < $j; $k += $note[ $k ] ) {
			$rang[$k] = 0;
			for ( $kk = $k; $kk < $k + $note[ $k ]; $kk++ ) {
				$rang[$k] = $rang[$k] + $kk;
			};
			for ( $kk = $k; $kk < $k + $note[ $k ]; $kk++ ) {
				$rang[$kk] = $rang[$k];
			};
		};
		for ( $k =0; $k < $j; $k += $note[ $k ] ) {
			$rang[$k] = $rang[$k]/$note[$k];
			for ( $kk = $k+1; $kk < $k + $note[ $k ]; $kk++ ) {
				$rang[$kk] = $rang[$k];
			};
		};
		// calcul note
		for ( $k =0; $k < $j; $k++ ) {
			$note[$k] = $rang[$k]*100/$top;
		};
		// stockage des résultats
		for ( $jj = 0; $jj < $j; $jj++ ) {
			$ij = $ordre[ $jj ];
			$sql = "UPDATE $tab_donnes SET rang = '$rang[$jj]', note = '$note[$jj]' where id = '$tabid[$ij]';";
			$dbh->query($sql);
			// print "<p>-- PaireNS: $pns[$ij] PaireEO: $peo[$ij] Points: $pps[$jj] Rg: $rang[$jj] Note: $note[$jj]%" ;
		};
	};
};

function htmlResultatDonne($idt, $etui, $numNS, $ordre) {
	global $tab_donnes, $contratNJ;
	$hweo = 0;
	$dbh = connectBDD();
	
	if ( ($ordre == "points")||($ordre == "pointsIMP") )
		$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and etui='$etui' and hweo='$hweo' order by points desc;";
	else
		$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and etui='$etui' order by ns;";

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
	foreach ($dbh->query($sql) as $row) {
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
				$r8tr = intval( $r8 );
			}
			else {
				$r8str = sprintf( "%.1f", $r8);
			}
		}
		if ( $r1 == $numNS )
			$str .= "<tr><td class='xres pairens'>$r1</td>";
		else
			$str .= "<tr><td class='xres'>$r1</td>";
		$str .= "<td class='xres'>$r2</td>";
		$str .= "<td class='xres'>$r3</td>";
		$str .= "<td class='xres'>$r4</td>";
		$str .= "<td class='xres'>$r5</td>";
		$str .= "<td class='xres'>$r6</td>";
		$str .= "<td class='xres'>$r7</td>";
		if ( $r1 == $numNS )
			$str .= "<td class='xres pairens'>$r8str</td></tr>";
		else
			$str .= "<td class='xres'>$r8str</td></tr>";
	};
	$str .= "</tbody></table>";
	
	$dbh = null;
	return $str;
}
function displayResultatsDonnes($idt, $ordre) {
	$t = readTournoi( $idt );
	$ndonnes = $t[ 'ndonnes' ];
	for ( $i = 1; $i <= $ndonnes; $i++ ) {
		$nsec = "nsec_" . $i;
		print "<div id=$nsec class='section_invisible'>";
		//$vulnerabilite = getinfoetui( $i );
		//print "<h3 id='vulnerabilite'>$vulnerabilite</h3>";
		print htmlResultatDonne($idt, $i, 0, $ordre);
		
		$diags = existeDiagramme($idt,$i);
		$ndiag = "ndiag_" . $i;
		if ( $diags == null ) print "<p>Diagrammes non enregistrés</p>";
		else print "<p id='$ndiag' hidden>" . $diags . "</p>";
		print "</div>";
	}
};
function htmlResultatPaquet($idt, $ns, $eo) {
	global $tab_donnes, $contratNJ;
	$dbh = connectBDD();
	
	$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and ns='$ns' and eo='$eo' order by etui;";
	
	$i = 0;
	$somme = 0; $nl = 0;
	foreach ($dbh->query($sql) as $row) {
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
	$dbh = null;

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
		if ( existeDiagramme( $idt, $r1 ) == null ) {
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
	
	$dbh = null;
	return $str;
}

// fonctions utilisant la table "tournois"
function setTournoi($idt) {
	global $tab_tournois, $tab_donnes, $tab_pairesNS, $tab_pairesEO, $contratNJ;
	$dbh = connectBDD();
	
	$sql = "SELECT * FROM $tab_tournois where id = '$idt';";
	$sth = $dbh->query( $sql );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$pairesNS = $row['pairesNS'];
	$pairesEO = $row['pairesEO'];
	$ndonnes = $row['ndonnes'];

	// calcul des rangs
	for ( $i = 1; $i <= $ndonnes; $i++) {
		_setRang( $idt, $i, $dbh, 0 );
		_setRang( $idt, $i, $dbh, 1 );
		};

	// Calcul des notes globales NS
	$pns = array();
	$notegns = array();
	for ( $i = 0; $i < $pairesNS; $i++) {
		$pns[$i] = 0;
		$notegns[$i] = 0;
	}
	$i = 0;
	$sql = "SELECT ns, AVG(note) AS perf FROM $tab_donnes where idtournoi = '$idt' and contrat !='$contratNJ' GROUP BY ns ORDER by ns;";
	foreach ($dbh->query($sql) as $row) {
		$pns[$i] = $row['ns'];
		$notegns[$i] = $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesNS; $i++) {
		$sql = "UPDATE $tab_pairesNS SET noteg = '$notegns[$i]' where idtournoi = '$idt' and num = '$pns[$i]';";
		//print "<p>$sql</p>";
		$dbh->query($sql);
	};

	// Calcul des notes globales EO
	$peo = array();
	$notegeo = array();
	for ( $i = 0; $i < $pairesEO; $i++) {
		$peo[$i] = 0;
		$notegeo[$i] = 0;
	}
	$i = 0;
	$sql = "SELECT eo, AVG(note) AS perf FROM $tab_donnes where idtournoi = '$idt' and contrat !='$contratNJ' GROUP BY eo ORDER by eo;";
	foreach ($dbh->query($sql) as $row) {
		$peo[$i] = $row['eo'];
		$notegeo[$i] = 100 - $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesEO; $i++) {
		$sql = "UPDATE $tab_pairesEO SET noteg = '$notegeo[$i]' where idtournoi = '$idt' and num = '$peo[$i]';";
		// print "<p>$sql</p>";
		$dbh->query($sql);
	};
	$dbh = null;
};
function calculIMP( $delta ) {
	$tabimp = array( 0, 10, 40, 80, 120, 160, 210, 260, 310, 360, 420,
		490, 590, 740, 890, 1090, 1290, 1490, 1740, 1990, 2240,
		2490, 2990, 3490, 10000 );
	$n = sizeof($tabimp);
	$d = intval( $delta );
	for ( $i = 1; $i < $n; $i++ ) {
		$imp = $i-1;
		if ( ($d > $tabimp[$i-1])&&($d <= $tabimp[$i]) ) break;
	}
	return $imp;
};
function setTournoiIMP($idt) {	// tournoi type howell de 4 paires, chaque donne jouée 2 fois
	global $tab_tournois, $tab_donnes, $tab_pairesNS, $tab_pairesEO;
	$dbh = connectBDD();
	
	$sql = "SELECT * FROM $tab_tournois where id = '$idt';";
	$sth = $dbh->query( $sql );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$pairesNS = $row['pairesNS'];
	$pairesEO = $row['pairesEO'];
	$ndonnes = $row['ndonnes'];

	for ( $i = 1; $i <= $ndonnes; $i++) {
		$hweo = 0;
		$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idt' and etui = '$i' and hweo = '$hweo';";
		$res = $dbh->query($sql);
		$nb = $res->fetchColumn();
		if ( $nb == 2 ) {
			$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and etui = '$i' and hweo = '$hweo';";
			$res = $dbh->query($sql);
			$row1 = $res->fetch(PDO::FETCH_ASSOC);
			$row2 = $res->fetch(PDO::FETCH_ASSOC);
			$id1 = $row1['id'];
			$pts1 = $row1['points'];
			
			$id2 = $row2['id'];
			$pts2 = $row2['points'];
			// comparaison points obtenus
			$diff = $pts1 - $pts2;
			$imp1 = 0;
			$imp2 = 0;
			if ( $diff > 0 ) $imp1 = calculIMP( $diff );
			if ( $diff < 0 ) $imp2 = calculIMP( -$diff );
			//print( "<p>$i pts1 $pts1 pts2 $pts2 diff $diff imp1 $imp1 imp2 $imp2</p>" );
			// stockage résultats
			$sql = "UPDATE $tab_donnes SET note = '$imp1' where id = '$id1';";
			$dbh->query($sql);
			$sql = "UPDATE $tab_donnes SET note = '$imp2' where id = '$id2';";
			$dbh->query($sql);
		}
		else {
			// erreur
		}
	};
	for ( $i = 1; $i <= $ndonnes; $i++) {
		$hweo = 1;
		$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = '$idt' and etui = '$i' and hweo = '$hweo';";
		$res = $dbh->query($sql);
		$nb = $res->fetchColumn();
		if ( $nb == 2 ) {
			$sql = "SELECT * FROM $tab_donnes where idtournoi = '$idt' and etui = '$i' and hweo = '$hweo';";
			$res = $dbh->query($sql);
			$row1 = $res->fetch(PDO::FETCH_ASSOC);
			$row2 = $res->fetch(PDO::FETCH_ASSOC);
			$id1 = $row1['id'];
			$pts1 = $row1['points'];
			
			$id2 = $row2['id'];
			$pts2 = $row2['points'];
			// comparaison points obtenus
			$diff = $pts1 - $pts2;
			$imp1 = 0;
			$imp2 = 0;
			if ( $diff > 0 ) $imp1 = calculIMP( $diff );
			if ( $diff < 0 ) $imp2 = calculIMP( -$diff );
			//print( "<p>$i pts1 $pts1 pts2 $pts2 diff $diff imp1 $imp1 imp2 $imp2</p>" );
			// stockage résultats
			$sql = "UPDATE $tab_donnes SET note = '$imp1' where id = '$id1';";
			$dbh->query($sql);
			$sql = "UPDATE $tab_donnes SET note = '$imp2' where id = '$id2';";
			$dbh->query($sql);
		}
		else {
			// erreur
		}
	};

	// Calcul des points IMP
	$pns = array();
	$impns = array();
	for ( $i = 0; $i < $pairesNS; $i++) {
		$pns[$i] = 0;
		$impns[$i] = 0;
	}
	$i = 0;
	$sql = "SELECT ns, SUM(note) AS perf FROM $tab_donnes where idtournoi = '$idt' GROUP BY ns ORDER by ns;";
	foreach ($dbh->query($sql) as $row) {
		$pns[$i] = $row['ns'];
		$impns[$i] = $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesNS; $i++) {
		$sql = "UPDATE $tab_pairesNS SET noteg = '$impns[$i]' where idtournoi = '$idt' and num = '$pns[$i]';";
		//print "<p>$sql</p>";
		$dbh->query($sql);
	};
	
	$dbh = null;
};

function existeTournoiVivant() {		// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois;
	global $st_phase_init, $st_phase_jeu;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu';";
	if ( $res = $dbh->query($sql)) {
		$nbl = $res->fetchColumn();
		if ( $nbl == 1 ) {
			$sth = $dbh->query( "SELECT id FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu';" );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
		}
		else $idt = 0;
	}
	else $idt = 0;
	
	$dbh = null;
	return $idt;
};
function existeTournoiNonClos() {		// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois;
	global $st_phase_init, $st_phase_jeu, $st_phase_fini;
	$dbh = connectBDD();
	
	$sql1 = "SELECT count(*) FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu' or etat = '$st_phase_fini';";
	$sql2 = "SELECT id FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu' or etat = '$st_phase_fini';";
	if ( $res = $dbh->query( $sql1 )) {
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			$results = $dbh->query( $sql2 );
			$row = $results->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
		}
		else $idt = 0;
	}
	else $idt = 0;
	
	$dbh = null;
	return $idt;
};
function jsonexisteTournoiNonClos() {		// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois;
	global $st_notfound, $st_phase_init, $st_phase_jeu, $st_phase_fini;
	$etat = $st_notfound;
	
	$dbh = connectBDD();
	
	$sql1 = "SELECT count(*) FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu' or etat = '$st_phase_fini';";
	$sql2 = "SELECT id FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu' or etat = '$st_phase_fini';";
	if ( $res = $dbh->query( $sql1 ) ) {
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			$results = $dbh->query( $sql2 );
			$row = $results->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
			$t = _readTournoi( $dbh, $idt );
			$etat   = $t['etat'];
		}
		else $idt = 0;
	}
	else $idt = 0;
	
	$dbh = null;
	return json_encode( array( 'id'=>$idt, 'etat'=>$etat ) );
};
function existeTournoiClos( $datetournoi ) {		// return tableau des idtournoi qui existent à la date précisée, 0 si non trouvé
	global $tab_tournois, $st_closed;
	$ids = array();

	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat='$st_closed' and tournoi='$datetournoi';";
	$sth = $dbh->query( $sql );
	$nbl = $sth->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "SELECT id FROM $tab_tournois where etat='$st_closed' and tournoi='$datetournoi';";
		$sth = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$ids[$i] = $row[ 'id' ];
		}
	}
	$dbh = null;
	return json_encode( array( 'nbl'=>$nbl, 'ids'=>$ids ) );
};
function getlastclosedtournois() {		// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois, $st_closed;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_tournois where etat = '$st_closed' order by id desc;" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$id = $row[ 'id' ];
	}
	else $id = 0;
	$dbh = null;
	return $id;
};
function createTournoi() {			// return idtournoi, 0 si erreur
	global $tab_tournois;
	global $def_genre, $def_type_howell, $def_type_mitchell, $t_howell;
	global $st_phase_init, $st_phase_jeu, $st_phase_fini;
	$tt = date('Y-m-d');	// date de création en attendant de stocker la date de démarrage

	$dbh = connectBDD();
	$dbh->query("LOCK TABLES $tab_tournois WRITE;");
	
	// vérification d'absence de tournoi non clos avant création effective
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_phase_init' or etat = '$st_phase_jeu' or etat = '$st_phase_fini';";
	if ( $sth = $dbh->query($sql) ) {
		$nbl = $sth->fetchColumn();
		if ( $nbl > 0 ) {
			// il existe au moins un tournoi non clos
			$id = 0;	// pour signaler l'erreur création
		}
		else {
			// pas de tournoi non clos, création tournoi avec le code associé
			$n = rand( 1, 9999);
			$code = sprintf( "%04d", $n );
			if ( $def_genre == $t_howell ) {
				$sql = "INSERT INTO $tab_tournois ( tournoi, code, pairesNS, pairesEO, idtype, etat ) VALUES ( '$tt', '$code', 0, 0, $def_type_howell, $st_phase_init );";
			}
			else {
				$sql = "INSERT INTO $tab_tournois ( tournoi, code, pairesNS, pairesEO, idtype, etat ) VALUES ( '$tt', '$code', 0, 0, $def_type_mitchell, $st_phase_init );";
			}
			if ( $dbh->query( $sql ) ) {
				$id = $dbh->lastInsertId();
			}
			else $id = 0;
		}
	}
	else $id = 0;
	$dbh->query("UNLOCK TABLES;");

	if ( $id > 0 ) {
		// raz table connexions
		_razCnxTables( $dbh );
	}
	$dbh = null;
	return $id;
};
/*
function set_genretournoi( $idt, $genre ) {
	global $tab_tournois;
	global $def_type_howell, $def_type_mitchell, $t_howell;
	$type = ( $genre == $t_howell ) ? $def_type_howell : $def_type_mitchell;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET idtype = '$type' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $genre;
};
*/
function initTournoi( $idt, $pns, $peo ) {
	// Initialisation faite à la fin de la définition des paires
	global $tab_tournois;
	$type = computetype( $pns, $peo );
	$tt = gettypetournoi( $type );
	
	$ok = $tt['npaires'];
	if ( $ok > 0 ) {
		// type existe
		$ntables = $tt['ntables'];
		$paquet  = $tt['paquet'];
		$ndonnes = $tt['ndonnes'];
		$npositions = $tt['npositions'];
		$njouees = $tt['njouees'];
		$saut	= $tt['saut'];
		$relais	= $tt['relais'];
		$gueridon = $tt['gueridon'];
		//$genre	= $tt['genre'];			// genre howell / mitchell non stocké en bdd, déduit du type de tournoi
		$obs	= "aa";	// provision pour texte entré par le directeur de tournoi
	
		$dbh = connectBDD();
		$sql = "UPDATE $tab_tournois SET pairesNS = '$pns', pairesEO = '$peo', idtype = '$type', ntables = '$ntables', paquet = '$paquet', ndonnes = '$ndonnes', npositions = '$npositions', njouees = '$njouees', saut = '$saut', relais = '$relais', gueridon = '$gueridon', obs = '$obs' where id = '$idt';";
		$res = $dbh->query($sql);
		$dbh = null;
	}
	return $ok;
};

function purge_Tournoi($idt) {
	global $tab_tournois, $tab_diagrammes, $tab_donnes, $tab_pairesNS, $tab_pairesEO;
	$indices = array();
	$dbh = connectBDD();
	// suppression diagrammes éventuels
	//$sql = "DELETE from $tab_diagrammes where id ='$id'";	// n'est pas accepté en mode safe, faut utiliser la primary key
	$sql = "select count(*) from $tab_diagrammes where idtournoi ='$idt';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_diagrammes where idtournoi ='$idt';";
		$sth = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_diagrammes where id ='$ii';";
			$res = $dbh->query($sql);
		}
	}
	// suppression donnes
	$sql = "select count(*) from $tab_donnes where idtournoi ='$idt';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_donnes where idtournoi ='$idt';";
		$sth = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_donnes where id ='$ii';";
			$res = $dbh->query($sql);
		}
	}
	// suppression paires NS
	$sql = "select count(*) from $tab_pairesNS where idtournoi ='$idt';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesNS where idtournoi ='$idt';";
		$sth = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_pairesNS where id ='$ii';";
			$res = $dbh->query($sql);
		}
	}
	// suppression paires EO
	$sql = "select count(*) from $tab_pairesEO where idtournoi ='$idt';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesEO where idtournoi ='$idt';";
		$sth = $dbh->query( $sql );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_pairesEO where id ='$ii';";
			$res = $dbh->query($sql);
		}
	}
	$dbh = null;
};
function eraseTournoi($id) {			// true si OK, false sinon
	global $tab_tournois;
	purge_Tournoi($id);
	$dbh = connectBDD();
	
	$sql = "DELETE from $tab_tournois where id ='$id';";
	if ( $res = $dbh->query($sql)) {
		$dbh = null;
		return true;
	}
	else {
		$dbh = null;
		return false;
	};
};

function set_etat( $id, $etat ) {	// return etat
	global $tab_tournois, $st_notfound;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where id = '$id';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "UPDATE $tab_tournois SET etat = '$etat' where id = '$id';";
		$res = $dbh->query($sql);
	}
	else $etat = $st_notfound;
	$dbh = null;
	return $etat;
};
function set_pairesNS( $idt, $pns ) {	// return $pns
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET pairesNS = '$pns' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $pns;
};
function set_pairesEO( $idt, $peo ) {	// return $peo
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET pairesEO = '$peo' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $peo;
};
function set_typetournoi( $idt, $type ) {	// return typetournoi
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET idtype = '$type' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $type;
};
function set_ndonnes( $idt, $nb ) {	// return $nb
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET ndonnes = '$nb' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $nb;
};
function set_npositions( $idt, $npositions ) {	// phase jeu
	global $tab_tournois;
	$t = readTournoi( $idt );
	$dbh = connectBDD();
	// calcul des dépendances
	$njouees = $t[ 'paquet' ] * $npositions;
	
	$sql = "UPDATE $tab_tournois SET npositions = '$npositions', njouees = '$njouees' where id = '$idt';";
	$res = $dbh->query($sql);
	$dbh = null;
	return $sql;
};

function start_mitchell( $idt, $paquet ) {	// phase jeu
	global $tab_tournois, $tab_connexions;
	global $parametres, $st_phase_jeu, $max_tables, $cnx_ko;
	$start = date('Y-m-d');	// date de démarrage
	
	$t = readTournoi( $idt );
	$ntables  = $t[ 'ntables' ];
	
	// calcul des dépendances
	$njouees = $t[ 'npositions' ] * $paquet;
	$ndonnes = $t[ 'ntables' ] * $paquet;
	$gueridon = $t['gueridon'];
	$posgueridon = $t[ 'ntables' ]/2;
	
	$firstduree = ( $paquet * ($parametres['dureedonne'] + $parametres['dureediagrammes']) + $parametres['dureeinitiale'] ) * 60;	// en secondes
	$date = new DateTime();
	$startseq = $date->getTimestamp();
	$endofseq = $startseq + $firstduree;
	//$sequence = "1_".$endofseq;		// fin du 1er tour
	
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET tournoi = '$start', etat = '$st_phase_jeu', paquet = '$paquet', ndonnes = '$ndonnes', njouees = '$njouees', notour = 1, startseq = '$startseq', endofseq = '$endofseq' where id = '$idt';";
	$res = $dbh->query($sql);
	
	for ( $i = 1; $i <= $ntables; $i++ ) {
		$donne = ($i-1)*$paquet;
		// test guéridon
		if ( ( $i > $posgueridon ) and ( $gueridon > 0 ) )
			$donne += $paquet;
		if ( ( $i == $ntables ) and ( $gueridon > 0 ) )
			$eocpt = $paquet;
		else
			$eocpt = 0;
		$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ko', numEO=0, numdonne='$donne', cpt=0, rdy=1, eocpt='$eocpt', eonumNS=0, eodonne='$donne' where id='$i';";
		$res = $dbh->query($sql);
	}
	for ( $i = $ntables+1; $i <= $max_tables; $i++ ) {
		$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ko', numEO=0, numdonne=0, cpt=0, rdy=0, eocpt=0, eonumNS=0, eodonne=0 where id='$i';";
		$res = $dbh->query($sql);
	}
	$dbh = null;
};
function start_howell( $idt, $paquet ) {	// phase jeu
	global $tab_tournois, $tab_connexions;
	global $parametres, $st_phase_jeu, $max_tables, $cnx_ko;
	$start = date('Y-m-d');	// date de démarrage
	
	$t = readTournoi( $idt );
	$idtype  = $t[ 'idtype' ];
	$ntables = $t[ 'ntables' ];
	$pairesNS = $t[ 'pairesNS' ];
	
	// calcul des dépendances
	$njouees = $t[ 'npositions' ] * $paquet;
	$ndonnes = ($t['ndonnes']/$t['paquet']) * $paquet;
	
	$firstduree = ( $paquet * ($parametres['dureedonne'] + $parametres['dureediagrammes']) + $parametres['dureeinitiale'] ) * 60;	// en secondes
	$date = new DateTime();
	$startseq = $date->getTimestamp();
	$endofseq = $startseq + $firstduree;
	//$sequence = "1_".$endofseq;		// fin du 1er tour
	
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET tournoi = '$start', etat = '$st_phase_jeu', paquet = '$paquet', ndonnes = '$ndonnes', njouees = '$njouees', notour = 1, startseq = '$startseq', endofseq = '$endofseq' where id = '$idt';";
	$res = $dbh->query($sql);
	
	for ( $i = 1; $i <= $ntables; $i++ ) {
		$p = getposhowell( $idtype, $i, 1, $paquet );	
		$lastdonne = $p['last'];
		$numEO = $p['adversaire'];
		
		$sql = "UPDATE $tab_connexions SET stconnexion ='$cnx_ko', numEO ='$numEO', numdonne ='$lastdonne', cpt = 0, rdy = 1 where id = '$i';";
		$res = $dbh->query($sql);
	}
	for ( $i = $ntables+1; $i <= $max_tables; $i++ ) {
		$sql = "UPDATE $tab_connexions SET stconnexion = '$cnx_ko', numEO = 0, numdonne = 0, cpt = 0, rdy = 0 where id = '$i';";
		$res = $dbh->query($sql);
	}
	$dbh = null;
};
function _readTournoi( $dbh, $idt ) {
	global $tab_tournois, $type_mitchell, $type_howell;
	$sql = "SELECT * FROM $tab_tournois where id = '$idt';";
	$sth = $dbh->query( $sql );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$datet = $row[ 'tournoi' ];
	$datef = strdatet( $datet );
	$tt = gettypetournoi( $row[ 'idtype' ] );
	$genre = $tt['genre'];
	//$sequence = explode("_", $row[ 'endofseq' ]);
	//if ( count($sequence) < 2 ) $sequence = [ 0, 0 ];
	
	$dt = array(
		'id' 		=> $row[ 'id' ],			// id tournoi
		'tournoi' 	=> $datet,		// date tournoi
		'datef'		=> $datef,		// date formattée
		'code'		=> $row[ 'code' ],
		'pairesNS'	=> $row[ 'pairesNS' ],
		'pairesEO' 	=> $row[ 'pairesEO' ],
		'idtype' 	=> $row[ 'idtype' ],
		'ntables' 	=> $row[ 'ntables' ],
		'paquet' 	=> $row[ 'paquet' ],
		'ndonnes' 	=> $row[ 'ndonnes' ],		// max donnes en circulation
		'npositions'=> $row[ 'npositions' ],
		'njouees' 	=> $row[ 'njouees' ],		// max donnes jouées
		'saut' 		=> $row[ 'saut' ],
		'relais' 	=> $row[ 'relais' ],
		'gueridon'	=> $row[ 'gueridon' ],
		'etat' 		=> $row[ 'etat' ],
		'notour'	=> $row[ 'notour' ],		// tour en cours
		'startseq' 	=> $row[ 'startseq' ],		// timestamp démarrage tournoi
		'endofseq' 	=> $row[ 'endofseq' ],		// timestamp fin du tour en cours
		'genre'		=> $genre,					// howell / mitchell
		'obs'	 	=> $row[ 'obs' ],		// entré par le directeur de tournoi
		);
	return $dt;
};
function readTournoi( $idt ) {
	$dbh = connectBDD();
	$t = _readTournoi( $dbh, $idt );
	$dbh = null;
	return $t;
}
function elabClassement( $paires, $notes ) {
	$exaequo = "ex";
	$result = array();
	$suffix = array();
	$result[0] = 1;		// le premier
	$suffix[0] = "";	// le premier
	for ($i = 1; $i < $paires; $i++) {
		if ($notes[$i] == $notes[$i-1] ) {
			$result[$i] = $result[$i-1];
			$suffix[$i-1] = $exaequo;
			$suffix[$i] = $exaequo;
		}
		else {
			$result[$i] = $i+1;
			$suffix[$i] = "";
		}
	}
	for ($i = 0; $i < $paires; $i++) {
		$result[$i] .= $suffix[$i];
	}
	return $result;
};
function htmlDisplayTournoi($idt, $screenw) {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO;
	global $parametres, $t_mitchell, $t_howell;
	$dbh = connectBDD();
	
	$t = _readTournoi( $dbh, $idt );
	$pairesNS = $t['pairesNS'];
	$pairesEO = $t['pairesEO'];
	$njouees = $t['njouees'];
	$ndonnes = $t['ndonnes'];
	$datet = $t[ 'tournoi' ];
	$datef = strdatet( $datet );
	
	// Lecture des notes globales NS
	$pns = array();
	$notegns = array();
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT num, idj1, idj3, noteg FROM $tab_pairesNS where idtournoi = '$idt' order by noteg desc;";
	foreach ($dbh->query($sql) as $row) {
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
		$sql = "SELECT num, idj2, idj4, noteg FROM $tab_pairesEO where idtournoi = '$idt' order by noteg desc;";
		foreach ($dbh->query($sql) as $row) {
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
	$dbh = null;

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
		$str .= "<table style='margin:auto;'><tbody><tr><td style='width:45%;'>";
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

	$pts = getPointsHonneursMoyens($idt);
	$str .= "<p>Points Honneurs moyens:<br>Nord-Sud: ".$pts['ns']."    Est-Ouest: ".$pts['eo']."</p>";
	return $str;
};
function buildDestinatairesResultats($idt) {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO;
	$dbh = connectBDD();
	
	$stack = [];

	$sql = "SELECT * FROM $tab_tournois where id = '$idt';";
	foreach ($dbh->query($sql) as $row) {
		$pairesNS = $row['pairesNS'];
		$pairesEO = $row['pairesEO'];
		}

	// Lecture des joueurs NS
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = '$idt';";
	foreach ($dbh->query($sql) as $row) {
		$tabj1[$i] = $row['idj1'];
		$tabj3[$i] = $row['idj3'];
		$i++;
	};
	for ( $i = 0; $i < $pairesNS; $i++) {
		$joueur = _getJoueur( $dbh, $tabj1[$i] );
		$email = $joueur['email'];
		if ( $email != "" ) {
			array_push( $stack, "$email" );
		}
		
		$joueur = _getJoueur( $dbh, $tabj3[$i] );
		$email = $joueur['email'];
		if ( $email != "" ) {
			array_push( $stack, "$email" );
		}
	};
	
	// Lecture des joueurs EO
	$tabj2 = array();
	$tabj4 = array();
	$i = 0;
	$sql = "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = '$idt';";
	foreach ($dbh->query($sql) as $row) {
		$tabj2[$i] = $row['idj2'];
		$tabj4[$i] = $row['idj4'];
		$i++;
	};
	for ( $i = 0; $i < $pairesEO; $i++) {
		$joueur = _getJoueur( $dbh, $tabj2[$i] );
		$email = $joueur['email'];
		if ( $email != "" ) {
			array_push( $stack, "$email" );
		}
		
		$joueur = _getJoueur( $dbh, $tabj4[$i] );
		$email = $joueur['email'];
		if ( $email != "" ) {
			array_push( $stack, "$email" );
		}
	};
	$dbh = null;

	return $stack;
};
function displayTournoi($idt, $screenw) {
	print htmlDisplayTournoi($idt, $screenw);
};
function htmlDisplayTournoiIMP($idt) {
	global $tab_tournois, $tab_pairesNS;
	global $parametres, $t_mitchell, $t_howell;
	$dbh = connectBDD();
	
	$t = _readTournoi( $dbh, $idt );
	$pairesNS = $t['pairesNS'];
	$pairesEO = $t['pairesEO'];
	$njouees = $t['njouees'];
	$ndonnes = $t['ndonnes'];
	$datet = $t[ 'tournoi' ];
	$datef = strdatet( $datet );
	
	$str = "<h2>Résultats tournoi du $datef</h2><p>$pairesNS paires (résultats en IMP)</br>$njouees donnes jouées / $ndonnes en circulation</p>";

	// Lecture des notes globales NS
	$pns = array();
	$notegns = array();
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT num, idj1, idj3, noteg FROM $tab_pairesNS where idtournoi = '$idt' order by noteg desc;";
	foreach ($dbh->query($sql) as $row) {
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
function displayTournoiIMP($idt) {
	print htmlDisplayTournoiIMP($idt);
};
function htmlDisplayResultatsTournoi($idt, $screenw) {
	global $parametres, $min_type_affimp;
	$t = readTournoi( $idt );
	if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
		setTournoiIMP( $idt );
		$str = htmlDisplayTournoiIMP( $idt );
	}
	else {
		setTournoi($idt);
		$str = htmlDisplayTournoi( $idt, $screenw );
	}
	return $str;
};
function displayResultatsTournoi($idt, $screenw) {
	print htmlDisplayResultatsTournoi($idt, $screenw);
}
// fonctions utilisant la table des diagrammes
function existeDiagramme($idt,$n) {		// return diagramme, null si non trouvé
	global $tab_diagrammes;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = '$idt' and etui= '$n';";
	if ( $res = $dbh->query($sql)) {
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			// on lit la première ligne
			$sth = $dbh->query( "SELECT * FROM $tab_diagrammes where idtournoi = '$idt' and etui= '$n';" );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$diag = $row['dealt'];
		} else $diag = null;
	} else $diag = null;
	
	$dbh = null;
	return $diag;
};
function insertDiagramme($idt,$n,$diag) {
	global $tab_diagrammes;
	$mains = [];
	$h =[];	// points honneurs N E S O
	
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	
	// test diagramme déjà enregistré
	$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = '$idt' and etui= '$n';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();

	if ( $nbl == 0 ) {
		// diagramme inconnu, calcul des points honneur
		$mains = explode(" ", substr( $diag, 2 ));
		for ( $i=0;$i<4;$i++ ) {
			$h[$i] = 4*substr_count($mains[$i],"A")+3*substr_count($mains[$i],"K")
			+2*substr_count($mains[$i],"Q")+substr_count($mains[$i],"J");
		}
		$sql = "INSERT INTO $tab_diagrammes ( idtournoi, etui, dealt, h1, h2, h3, h4 ) VALUES ('$idt', '$n', '$diag', $h[0], $h[1], $h[2], $h[3] );";
		$res = $dbh->query($sql);
		$id = $dbh->lastInsertId();
	}
	else {
		// diagramme connu
		$sql = "SELECT id FROM $tab_diagrammes where idtournoi = '$idt' and etui= '$n';";
		$res = $dbh->query($sql);
		$id = $res->fetchColumn();
	}
	$dbh->query("COMMIT;");
	$dbh = null;
	return $id;
};
function updateDiagramme($idt,$n,$diag) {
	global $tab_diagrammes;
	$mains = [];
	$h =[];	// points honneurs N E S O
	
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	
	// test diagramme déjà enregistré
	$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = '$idt' and etui= '$n';";
	$res = $dbh->query($sql);

	if ( $res->fetchColumn() == 1 ) {
		// diagramme connu, recalcul des points honneur
		$mains = explode(" ", substr( $diag, 2 ));
		for ( $i=0;$i<4;$i++ ) {
			$h[$i] = 4*substr_count($mains[$i],"A")+3*substr_count($mains[$i],"K")
			+2*substr_count($mains[$i],"Q")+substr_count($mains[$i],"J");
		};
		$sql = "UPDATE $tab_diagrammes SET dealt='$diag', h1=$h[0], h2=$h[1], h3=$h[2], h4=$h[3] where idtournoi='$idt' and etui='$n';";
		$res = $dbh->query($sql);
		$r = 1;
	}
	else {
		// diagramme inconnu
		$r = 0;
	}
	$dbh->query("COMMIT;");
	$dbh = null;
	return $r;
};
function getPointsHonneursMoyens($idt) {
	global $tab_diagrammes;
	$pts = array( 'id'=>$idt, 'nord'=>'0', 'est'=>'0', 'sud'=>'0', 'ouest'=>'0', 'ns'=>'0', 'eo'=>'0' );
	$dbh = connectBDD();
	
	$res = $dbh->query( "SELECT AVG(h1) FROM $tab_diagrammes where idtournoi = '$idt';" );
	$pn = $res->fetchColumn();
	$pts['nord'] = sprintf( "%.2f", $pn );
	
	$res = $dbh->query( "SELECT AVG(h2) FROM $tab_diagrammes where idtournoi = '$idt';" );
	$pe = $res->fetchColumn();
	$pts['est'] = sprintf( "%.2f", $pe );
	
	$res = $dbh->query( "SELECT AVG(h3) FROM $tab_diagrammes where idtournoi = '$idt';" );
	$ps = $res->fetchColumn();
	$pts['sud'] = sprintf( "%.2f", $ps );
	
	$res = $dbh->query( "SELECT AVG(h4) FROM $tab_diagrammes where idtournoi = '$idt';" );
	$po = $res->fetchColumn();
	$pts['ouest'] = sprintf( "%.2f", $po );
	
	$pts['ns'] = sprintf( "%.2f", $pn+$ps );
	$pts['eo'] = sprintf( "%.2f", $pe+$po );
	
	$dbh = null;
	return $pts;
}

// fonctions utilisant les tables des paires NS et EO
function _maxNumPaireNS( $dbh, $idt ) {		// return max(numtable), 0 sinon
	global $tab_pairesNS;
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt'";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sql = "SELECT max(num) FROM $tab_pairesNS where idtournoi = '$idt'";
		$res = $dbh->query($sql);
		$nb = $res->fetchColumn();
	}
	return $nb;
};
function _maxNumPaireEO( $dbh, $idt ) {
	global $tab_pairesEO;
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt'";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sql = "SELECT max(num) FROM $tab_pairesEO where idtournoi = '$idt'";
		$res = $dbh->query($sql);
		$nb = $res->fetchColumn();
	}
	return $nb;
};
function maxNumPaireNS( $idt ) {		// return max(numtable), 0 sinon
	$dbh = connectBDD();
	$nb = _maxNumPaireNS( $dbh, $idt );
	$dbh = null;
	return $nb;
};
function maxNumPaireEO( $idt ) {
	$dbh = connectBDD();
	$nb = _maxNumPaireEO( $dbh, $idt );
	$dbh = null;
	return $nb;
};
function _getligneNS( $dbh, $idt, $numpaire ) {
	global $tab_pairesNS;
	$libre = array( 'id'=>0, 'numero'=>0, 'joueur'=>" ", 'nomcomplet'=>" ");
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and num = '$numpaire'";
	$res = $dbh->query($sql);
	if ( $res->fetchColumn() == 1 ) {
		$sth = $dbh->query( "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = '$idt' and num = '$numpaire'" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ( $row[ 'idj1' ] != null )	$ligne['N'] = _getJoueur( $dbh, $row[ 'idj1' ] );
		else	$ligne['N'] = $libre;
		
		if ( $row[ 'idj3' ] != null ) 	$ligne['S'] = _getJoueur( $dbh, $row[ 'idj3' ] );
		else 	$ligne['S'] = $libre;
	}
	else {
		$ligne['N'] = $libre;
		$ligne['S'] = $libre;
	}
	return $ligne;
};
function _getligneEO( $dbh, $idt, $numpaire ) {
	global $tab_pairesEO;
	$libre = array( 'id'=>0, 'numero'=>0, 'joueur'=>" ", 'nomcomplet'=>" ");
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and num = '$numpaire'";
	$res = $dbh->query($sql);
	if ( $res->fetchColumn() == 1 ) {
		$sth = $dbh->query( "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = '$idt' and num = '$numpaire'" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ( $row[ 'idj2' ] != null ) $ligne['E'] = _getJoueur( $dbh, $row[ 'idj2' ] );
		else 	$ligne['E'] = $libre;

		if ( $row[ 'idj4' ] != null ) $ligne['O'] = _getJoueur( $dbh, $row[ 'idj4' ] );
		else 	$ligne['O'] = $libre;
	}
	else {
		$ligne['E'] = $libre;
		$ligne['O'] = $libre;
	}
	return $ligne;
};
function getligneNS( $idt, $numpaire ) {
	$dbh = connectBDD();
	$ligne = _getligneNS( $dbh, $idt, $numpaire );
	$dbh = null;
	return $ligne;
};
function getligneEO( $idt, $numpaire ) {
	$dbh = connectBDD();
	$ligne = _getligneEO( $dbh, $idt, $numpaire );
	$dbh = null;
	return $ligne;
};
function setligneNS( $idt, $numpaire, $id1, $id2 ) {
	// effacement ligne NS si existe
	global $tab_pairesNS;
	$dbh = connectBDD();
	$sql = "select count(*) from $tab_pairesNS where idtournoi ='$idt' and num = '$numpaire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesNS where idtournoi ='$idt' and num = '$numpaire';";
		$sth = $dbh->query( $sql );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$indice = $row['id'];
		$sql = "DELETE from $tab_pairesNS where id ='$indice';";
		$res = $dbh->query($sql);
	}
	$dbh = null;
	
	// nouvelle ligne avec les joueurs
	if( $id1 > 0 ) set_joueurNord( $idt, $numpaire, $id1 );
	if( $id2 > 0 ) set_joueurSud( $idt, $numpaire, $id2 );
	return;
};
function setligneEO( $idt, $numpaire, $id1, $id2 ) {
	// effacement ligne EO si existe
	global $tab_pairesEO;
	$dbh = connectBDD();
	$sql = "select count(*) from $tab_pairesEO where idtournoi ='$idt' and num = '$numpaire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesEO where idtournoi ='$idt' and num = '$numpaire';";
		$sth = $dbh->query( $sql );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$indice = $row['id'];
		$sql = "DELETE from $tab_pairesEO where id ='$indice';";
		$res = $dbh->query($sql);
	}
	$dbh = null;
	
	// nouvelle ligne avec les joueurs
	if( $id1 > 0 ) set_joueurEst( $idt, $numpaire, $id1 );
	if( $id2 > 0 ) set_joueurOuest( $idt, $numpaire, $id2 );
	return;
};
function testlignescompletesNS( $idt ) { 
	global $tab_pairesNS;
	$dbh = connectBDD();

	$nb = _maxNumPaireNS( $dbh, $idt );
	if ( $nb > 0 ) {
		for ( $i = 1; $i <= $nb; $i++ ) {
			// test paire existe ?
			$res = $dbh->query( "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and num = '$i'");
			$n = $res->fetchColumn();
			if ( $n == 0 ) { $nb = -1; break; }
			// test paire complète
			$sth = $dbh->query( "SELECT * FROM $tab_pairesNS where idtournoi = '$idt' and num = '$i'");
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ( $row[ 'idj1' ] == null ) { $nb = -1; break; }
			if ( $row[ 'idj3' ] == null ) { $nb = -1; break; }
		}
	}
	$dbh = null;
	return $nb;		// >0: tab complet	=0: tab vide	-1: tab incomplet
};
function testlignescompletesEO( $idt ) {
	global $tab_pairesEO;
	$dbh = connectBDD();

	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt'";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	
	if ( $nb > 0 ) {
		$sql = "SELECT max(num) FROM $tab_pairesEO where idtournoi = '$idt'";
		$res = $dbh->query($sql);
		$nb = $res->fetchColumn();
		if ( $nb > 0 ) {
			for ( $i = 1; $i <= $nb; $i++ ) {
				// test paire existe ?
				$res = $dbh->query( "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and num = '$i'");
				$n = $res->fetchColumn();
				if ( $n == 0 ) { $nb = -1; break; }
				// test paire complète
				$sth = $dbh->query( "SELECT * FROM $tab_pairesEO where idtournoi = '$idt' and num = '$i'");
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				if ( $row[ 'idj2' ] == null ) { $nb = -1; break; }
				if ( $row[ 'idj4' ] == null ) { $nb = -1; break; }
			}
		}
	}
	$dbh = null;
	return $nb;		// >0: tab complet	=0: tab vide	-1: tab incomplet
};
function testlignescompletes2( $idt ) {
	$ns = testlignescompletesNS( $idt );
	$eo = testlignescompletesEO( $idt );
	return ($ns * $eo);
};	
function set_joueurNord( $idt, $paire, $idj ) {
	global $tab_pairesNS;
	$dbh = connectBDD();
	// recherche si ligne NS déjà utilisé
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and num = '$paire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl == 0 ) { // pas encore utilisé
		if ( $idj > 0 ) {
			$sql = "INSERT $tab_pairesNS ( idtournoi, num, idj1 ) VALUES ( '$idt', '$paire', '$idj' );";
			$res = $dbh->query($sql);
		};
	};
	if ( $nbl == 1 ) { // déja utilisé par sud
		if ( $idj == 0 ) {
			$sql = "UPDATE $tab_pairesNS SET idj1 = 'null' where idtournoi = '$idt' and num = '$paire';";
		} else {
			$sql = "UPDATE $tab_pairesNS SET idj1 = '$idj' where idtournoi = '$idt' and num = '$paire';";
		};
		$res = $dbh->query($sql);
	};
	$dbh = null;
	return $idj;
};
function set_joueurSud( $idt, $paire, $idj ) {
	global $tab_pairesNS;
	$dbh = connectBDD();
	// recherche si ligne NS déjà utilisé
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and num = '$paire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl == 0 ) { // pas encore utilisé
		if ( $idj > 0 ) {
			$sql = "INSERT $tab_pairesNS ( idtournoi, num, idj3 ) VALUES ( '$idt', '$paire', '$idj' );";
			$res = $dbh->query($sql);
		};
	};
	if ( $nbl == 1 ) { // déja utilisé par nord
		if ( $idj == 0 ) {
			$sql = "UPDATE $tab_pairesNS SET idj3 = 'null' where idtournoi = '$idt' and num = '$paire';";
		} else {
			$sql = "UPDATE $tab_pairesNS SET idj3 = '$idj' where idtournoi = '$idt' and num = '$paire';";
		};
		$res = $dbh->query($sql);
	};
	$dbh = null;
	return $idj;
};
function set_joueurEst( $idt, $paire, $idj ) {
	global $tab_pairesEO;
	$dbh = connectBDD();
	// recherche si ligne EO déjà utilisé
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and num = '$paire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl == 0 ) { // pas encore utilisé
		if ( $idj > 0 ) {
			$sql = "INSERT $tab_pairesEO ( idtournoi, num, idj2 ) VALUES ( '$idt', '$paire', '$idj' );";
			$res = $dbh->query($sql);
		};
	};
	if ( $nbl == 1 ) { // déja utilisé par ouest
		if ( $idj == 0 ) {
			$sql = "UPDATE $tab_pairesEO SET idj2 = 'null' where idtournoi = '$idt' and num = '$paire';";
		} else {
			$sql = "UPDATE $tab_pairesEO SET idj2 = '$idj' where idtournoi = '$idt' and num = '$paire';";
		};
		$res = $dbh->query($sql);
	};
	$dbh = null;
	return $idj;
};
function set_joueurOuest( $idt, $paire, $idj ) {
	global $tab_pairesEO;
	$dbh = connectBDD();
	// recherche si ligne EO déjà utilisé
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and num = '$paire';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl == 0 ) { // pas encore utilisé
		if ( $idj > 0 ) {
			$sql = "INSERT $tab_pairesEO ( idtournoi, num, idj4 ) VALUES ( '$idt', '$paire', '$idj' );";
			$res = $dbh->query($sql);
		};
	};
	if ( $nbl == 1 ) { // déja utilisé par est
		if ( $idj == 0 ) {
			$sql = "UPDATE $tab_pairesEO SET idj4 = 'null' where idtournoi = '$idt' and num = '$paire';";
		} else {
			$sql = "UPDATE $tab_pairesEO SET idj4 = '$idj' where idtournoi = '$idt' and num = '$paire';";
		};
		$res = $dbh->query($sql);
	};
	$dbh = null;
	return $idj;
};
function efface_joueur( $idt, $idj ) {	// id tournoi, id joueur
	global $tab_pairesNS, $tab_pairesEO;
	$dbh1 = connectBDD();
	$dbh2 = connectBDD();

	// recherche si joueur déjà placé
	$nn = 0; //compteur de position trouvée
	$oldposition = array( 'paire'=> 0, 'position'=> 0 ); 	// ( numéro paire, position )
	for ( $i = 1; $i < 5; $i++ ) {
		if ( $i == 1 ) { // Nord
			$sql1 = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and idj1 = '$idj';";
			$sql2 = "SELECT id, num, idj3 FROM $tab_pairesNS where idtournoi = '$idt' and idj1 = '$idj';";				
		} elseif ( $i == 2 ) {// Est
			$sql1 = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and idj2 = '$idj';";
			$sql2 = "SELECT id, num, idj4 FROM $tab_pairesEO where idtournoi = '$idt' and idj2 = '$idj';";
		} elseif ( $i == 3 ) { // Sud
			$sql1 = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and idj3 = '$idj';";
			$sql2 = "SELECT id, num, idj1 FROM $tab_pairesNS where idtournoi = '$idt' and idj3 = '$idj';";
		} elseif ( $i == 4 ) { // Ouest
			$sql1 = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and idj4 = '$idj';";
			$sql2 = "SELECT id, num, idj2 FROM $tab_pairesEO where idtournoi = '$idt' and idj4 = '$idj';";
		};
		$res = $dbh1->query($sql1);
		$nbl = $res->fetchColumn();
		if ( $nbl > 1 ) {
			// bug: le même joueur référencé plusieurs fois dans une même position
			echo 'Erreur : joueur référencé plusieurs fois';
			die();
		};
		if ( $nbl == 1 ) {
			$nn++;
			
			// effacement joueur sur l'emplacement trouvé
			$sth = $dbh1->query( $sql2 );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$idpaire = $row['id'];
			
			// mémorise ancienne position
			$oldposition[ 'paire' ] = $row['num'];
			$oldposition[ 'position' ] = $i;
			
			$idpartenaire = 0;
			
			if ( $i == 1 ) { // Nord
				$idpartenaire = $row['idj3'];
				$sql3 = "DELETE FROM $tab_pairesNS where idtournoi = '$idt' and id = '$idpaire';";
				$sql4 = "UPDATE $tab_pairesNS SET idj1 = null where idtournoi = '$idt' and id = '$idpaire';";
			} elseif ( $i == 2 ) {// Est
					$idpartenaire = $row['idj4'];
					$sql3 = "DELETE FROM $tab_pairesEO where idtournoi = '$idt' and id = '$idpaire';";
					$sql4 = "UPDATE $tab_pairesEO SET idj2 = null where idtournoi = '$idt' and id = '$idpaire';";
			} elseif ( $i == 3 ) { // Sud
					$idpartenaire = $row['idj1'];
					$sql3 = "DELETE FROM $tab_pairesNS where idtournoi = '$idt' and id = '$idpaire';";
					$sql4 = "UPDATE $tab_pairesNS SET idj3 = null where idtournoi = '$idt' and id = '$idpaire';";
			} elseif ( $i == 4 ) { // Ouest
					$idpartenaire = $row['idj2'];
					$sql3 = "DELETE FROM $tab_pairesEO where idtournoi = '$idt' and id = '$idpaire';";
					$sql4 = "UPDATE $tab_pairesEO SET idj4 = null where idtournoi = '$idt' and id = '$idpaire';";
			};
				
			if ( $idpartenaire > 0 ) {
				// effacement joueur dans la paire
				$res3 = $dbh2->query($sql4);
			} else {
				// suppression paire
				$res3 = $dbh2->query($sql3);
			};
		};
	};
	if ( $nn > 1 ) {
		// bug: le même joueur référencé plusieurs fois dans différentes positions
		echo 'Erreur : joueur référencé plusieurs fois';
		die();
	};
	$dbh1 = null;
	$dbh2 = null;
	return $oldposition;
};
function recherche_joueur( $idt, $idj ) {
	global $tab_pairesNS, $tab_pairesEO;
	global $pos_indefini, $pos_Nord, $pos_Est, $pos_Sud, $pos_Ouest, $maxtableslibres;
	$dbh = connectBDD();

	// recherche si joueur déjà placé
	$emplacement = [ 'table'=>0, 'place'=>$pos_indefini, 'nplaces'=>0 ];
	for ( $i = 1; $i < 5; $i++ ) {
		if ( $i == $pos_Nord ) { // Nord
			$sql  = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and idj1 = '$idj';";
			$sql2 = "SELECT id, num  FROM $tab_pairesNS where idtournoi = '$idt' and idj1 = '$idj';";				
		} elseif ( $i == $pos_Est ) { // Est
			$sql  = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and idj2 = '$idj';";
			$sql2 = "SELECT id, num  FROM $tab_pairesEO where idtournoi = '$idt' and idj2 = '$idj';";
		} elseif ( $i == $pos_Sud ) { // Sud
			$sql  = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and idj3 = '$idj';";
			$sql2 = "SELECT id, num  FROM $tab_pairesNS where idtournoi = '$idt' and idj3 = '$idj';";
		} elseif ( $i == $pos_Ouest ) { // Ouest
			$sql  = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and idj4 = '$idj';";
			$sql2 = "SELECT id, num  FROM $tab_pairesEO where idtournoi = '$idt' and idj4 = '$idj';";
		};
		if ( $res = $dbh->query($sql) ) {
			$nb = $res->fetchColumn();
			if ( $nb > 0 ) {	// joueur trouvé
				// numéro de table
				$sth = $dbh->query( $sql2 );
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$emplacement['table'] = $row['num'];	// numéro de table
				$emplacement['place'] = $i;				// position joueur sur la table
				// test table complète
				$emplacement['nplaces'] = _testtablecomplete( $dbh, $idt, $emplacement['table'] );
				break;	
			};
		}
	};
	$dbh = null;
	return $emplacement;
};
function _testtablecomplete( $dbh, $idt, $numpaire ) {
	global $tab_pairesNS, $tab_pairesEO;
	$nbj = 0;
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = '$idt' and num = '$numpaire'";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sth = $dbh->query( "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = '$idt' and num = '$numpaire'" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ( $row[ 'idj1' ] != null ) $nbj++;
		if ( $row[ 'idj3' ] != null ) $nbj++;
	}
		
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = '$idt' and num = '$numpaire'";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sth = $dbh->query( "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = '$idt' and num = '$numpaire'" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ( $row[ 'idj2' ] != null ) $nbj++;
		if ( $row[ 'idj4' ] != null ) $nbj++;
	}
	return $nbj;
};
function testtablecomplete( $idt, $numpaire ) {
	$dbh = connectBDD();
	$nbj = _testtablecomplete( $dbh, $idt, $numpaire );
	$dbh = null;
	return $nbj;
};

// fonctions utilisant la table "joueurs"
function _getJoueur( $dbh, $id ) {
	global $tab_joueurs;
	$joueur = array( 'id'=>0, 'numero'=>0, 'joueur'=>" ", 'genre'=>" ", 'prenom'=>" ", 'nom'=>" ", 'nomcomplet'=>" ", 'email'=>"");
	$sql = "SELECT count(*) FROM $tab_joueurs where id = '$id';";
	if ( $res = $dbh->query($sql) ) {
		$nbl = $res->fetchColumn();
		if ( $nbl == 1 ) {
			$sth = $dbh->query( "SELECT * FROM $tab_joueurs where id = '$id';" );
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			// nouvelle implémentation
			$joueur['id'] = $row[ 'id' ];
			$joueur['numero'] = $row[ 'numero' ];
			$joueur['joueur'] = $row[ 'joueur' ];
			$joueur['genre'] = $row[ 'genre' ];
			$joueur['prenom'] = $row[ 'prenom' ];
			$joueur['nom'] = $row[ 'nom' ];
			$joueur['nomcomplet'] = $joueur['prenom'] . " " . $joueur['nom'];
			$joueur['phone'] = $row[ 'telephone' ];
			$joueur['email'] = $row[ 'email' ];
			$joueur['datesupp'] = $row[ 'datesupp' ];
		};
	};
	return $joueur;
}
function getJoueur( $idj ) {
	$dbh = connectBDD();
	$joueur = _getJoueur( $dbh, $idj );
	$dbh = null;
	return $joueur;
};
function getClassement() {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO, $maxjoueurs;
	global $parametres;
	$nbm = $parametres['nbmperf'];	// $nbm nombre de mois précédent la date du jour
	$min = $parametres['minperf'];	// $min nombre minimum de tournois joués
	$nbj = $parametres['nbjperf'];	// taille du tableau
	$dbh = connectBDD();
	
	// recherche du 1er tournoi avec une date >= date du jour - $nbm mois
	$sql = "SELECT count(*) FROM $tab_tournois WHERE tournoi >=(DATE_SUB(curdate(), INTERVAL $nbm MONTH)) order by id asc;";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	//print "<p>$sql $nbl</p>";
	if ( $nbl > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_tournois WHERE tournoi >= (DATE_SUB(curdate(), INTERVAL $nbm MONTH)) order by id asc;" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$minidt = $row['id'];
		$since  = $row['tournoi'];
		$datef = strdatet( $since );
		//print "<p>$sql $minidt</p>";

		$i=0;
		$sql = "SELECT idj, nbfois, perf FROM (
			SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf FROM (
				(SELECT idj1 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= $minidt) UNION 
				(SELECT idj2 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= $minidt) UNION 
				(SELECT idj4 AS idj, idtournoi, noteg FROM $tab_pairesEO where idtournoi >= $minidt) UNION 
				(SELECT idj3 AS idj, idtournoi, noteg FROM $tab_pairesNS where idtournoi >= $minidt)
				) T1 group by idj
			) T2 where nbfois > '$min' order by perf desc;";
		//print "<p>$sql</p>";

		print "<p>Performance moyenne des joueurs sur les $nbm derniers mois (du $datef à ce jour) avec au moins $min tournois joués</p>";
		print '<table border="0" style="width:100%; max-width: 350px; margin:auto;"><tbody>';
		print "<tr><td class='xNum3' style='width:10%;'>Rg</td>";
		print "<td class='xNum3'>Joueur</td>";
		print "<td class='xNum3' style='width:10%;'>N</td>";
		print "<td class='xNum3' style='width:15%;'>%</td></tr>";
		foreach  ($dbh->query($sql) as $row) {
			if ( ++$i > $nbj ) break;
			$j = _getJoueur( $dbh, $row['idj'] );
			$score = sprintf( "%.1f", $row["perf"] );
			print "<tr>";
			print "<td class='xNum3'>" . $i . "</td>";
			print "<td class='xNum3'>" . $j['nomcomplet'] . "</td>";
			print "<td class='xNum3'>" . $row['nbfois'] . "</td>";
			print "<td class='xNum3'>" . $score . "</td>";
			print "</tr>";
			}
		print "</tbody></table>";
	}
	else {
		print "Pas de tournoi enregistré !";
	}
	$dbh = null;
};
function getListeJoueurs($liste, $ordre, $filtre) {
	global $db_name, $tab_tournois, $tab_joueurs, $st_closed, $tab_pairesNS, $tab_pairesEO, $min_noclub;
	$dbh = connectBDD();
	if ( $liste == 1 ) $groupe = "and datesupp = 0";	// actifs
	if ( $liste == 2 ) $groupe = "and datesupp > 0";	// inactifs
	if ( $liste == 3 ) $groupe = "";	// tous
	
	if ( $ordre == "tournoi") {
		$selordre = "tournoi desc, nom";
	}
	else $selordre = $ordre;
	
	if ( strlen($filtre) > 0) {
		$likename = $filtre . "%";
		$filtrage = "and nom like '$likename'";
	}
	else $filtrage = "";
	
	$sql = "SELECT $tab_joueurs.id, numero, prenom, nom, email, password, nbfois, tournoi, datesupp
			FROM $tab_joueurs
			LEFT JOIN (
				SELECT idj, COUNT(*) AS nbfois, MAX(idtournoi) as maxid FROM (
					(SELECT idj1 AS idj, idtournoi FROM $tab_pairesNS) UNION
					(SELECT idj2 AS idj, idtournoi FROM $tab_pairesEO) UNION
					(SELECT idj3 AS idj, idtournoi FROM $tab_pairesNS) UNION
					(SELECT idj4 AS idj, idtournoi FROM $tab_pairesEO) ) T1
				group by idj ) T2 ON T2.idj = $tab_joueurs.id
			LEFT JOIN $tab_tournois ON T2.maxid = $tab_tournois.id 
			WHERE numero >= $min_noclub $groupe $filtrage ORDER BY $selordre;";
	
	//$str = "<p>$sql</p>";
	$str = '<table border="1" style="margin:auto;"><tbody>';
	$str .= "<tr>";
	$str .= "<td class='xTxt1 selordre' id='ordre_nom'>Joueur</td>";
	$str .= "<td class='xTxt1 selordre' id='ordre_tournoi'>dernier</br>tournoi</td>";
	$str .= "<td class='xTxt1 selordre' id='ordre_nbfois'>N</td>";
	$str .= "</tr>";
	foreach  ($dbh->query($sql) as $row) {
		$nr = "nr_" . $row['id'];
		$str .= "<tr>";
		if ($row['datesupp']>0) {
			$str .= "<td id='$nr' class='xTxt1 canclick' style='color: grey'>" . $row['prenom'] ." ". $row['nom'] . "</td>";
		}
		else {
			if ( ($row['password']==null)||($row['password']=="bridgette") ) {
				$s = "";
			}
			else {
				$s = "style='color: blue'";
			}
			$str .= "<td id='$nr' class='xTxt1 canclick' $s>" . $row['prenom'] ." ". $row['nom'] . "</td>";
		}
		$str .= "<td class='xTxt1'>" . $row['tournoi'] . "</td>";
		$str .= "<td class='xTxt1'>" . $row['nbfois'] . "</td>";
		$str .= "</tr>";
		}
	$str .= "</tbody></table>";
	$dbh = null;
	return $str;
};
function htmlAnnuaire() {
	global $tab_joueurs, $min_noclub;
	$dbh = connectBDD();
	
	$sql = "SELECT id, prenom, nom, telephone
			FROM $tab_joueurs
			WHERE numero >= $min_noclub ORDER BY nom;";
	
	$str = "<table border='1' style='margin:auto;'><tbody>";
	$str .= "<tr>";
	$str .= "<th class='xTxt1'>Joueur</th>";
	$str .= "<th class='xTxt1'>Téléphone</th>";
	$str .= "</tr>";
	foreach  ($dbh->query($sql) as $row) {
		$str .= "<tr>";
		$str .= "<td class='xTxt1'>" . $row['prenom'] ." ". $row['nom'] . "</td>";
		$str .= "<td class='xTxt1'>" . $row['telephone'] . "</td>";
		$str .= "</tr>";
	}
	$str .= "</tbody></table>";
	$dbh = null;
	return $str;
};

// fonctions utilisant la table $tab_connexions
function _razCnxTables( $dbh ) {
	global $tab_connexions, $cnx_ko;
	global $max_tables;
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ko', numEO=0, numdonne=0, cpt=0, rdy=0, eocpt=0, eonumNS=0, eodonne=0 where id='$i';";
		$res = $dbh->query($sql);
	};
};
function getdonnesjouees() {
	global $tab_connexions;
	$dbh = connectBDD();
	$res = $dbh->query( "SELECT SUM(cpt) FROM $tab_connexions" );
	$donnesjouees = $res->fetchColumn();
	$dbh = null;
	
	return $donnesjouees;
};
function displayCnxTables() {
	global $tab_connexions;
	global $max_tables;
	$dbh = connectBDD();
	$sth = $dbh->query( "SELECT * FROM $tab_connexions" );
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$stconn[$i] = $row[ 'stconnexion' ];
		$numeo[$i]  = $row[ 'numEO' ];
		$numdonne[$i] = $row[ 'numdonne' ];
		$cpt[$i] = $row[ 'cpt' ];
		$pos[$i] = $row[ 'rdy' ];
		};
	$dbh = null;
	
	print '<table border="1" style="width:90%; max-width: 350px; margin:auto;">';
	print '<tbody>';
	print '<tr><td  colspan="5" class="xTitre">Avancement tournoi</td></tr>';
	print '<tr class="xtr"><td class="xTitre">Paire</br>NS</td><td class="xTitre">Paire</br>EO</td><td class="xTitre">Dernière</br>donne</br>enreg.</td><td class="xTitre">Donnes</br>jouées</td><td class="xTitre">Position</br>courante</td></tr>';
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$stcnx = $stconn[ $i ];
		print '<tr id="row_' . $i . '" class="xtr">';
		if ( $stcnx == 1 ) print '<td id="row_' . $i . '_0" class="xCnxtel">'   . $i . '</td>';
		if ( $stcnx == 2 ) print '<td id="row_' . $i . '_0" class="xCnxtelKo ">' . $i . '</td>';
		if ( $stcnx == 3 ) print '<td id="row_' . $i . '_0" class="xCnxtelOk ">' . $i . '</td>';
		print '<td id="row_' . $i . '_1">' . $numeo[$i] . '</td>';
		print '<td id="row_' . $i . '_2">' . $numdonne[$i] . '</td>';
		print '<td id="row_' . $i . '_3">' . $cpt[$i] . '</td>';
		print '<td id="row_' . $i . '_4">' . $pos[$i] . '</td>';
		print '</tr>';
		};
	print "</tbody></table>";
};
function largeDisplayCnxTables() {
	global $tab_connexions;
	global $max_tables;
	$dbh = connectBDD();
	$sth = $dbh->query( "SELECT * FROM $tab_connexions" );
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$stconn[$i] = $row[ 'stconnexion' ];
		$numeo[$i]  = $row[ 'numEO' ];
		$numdonne[$i] = $row[ 'numdonne' ];
		$cpt[$i] = $row[ 'cpt' ];
		$pos[$i] = $row[ 'rdy' ];
		};
	$dbh = null;
	
	print '<table border="1" style="margin:auto;">';
	print '<tbody>';
	print '<tr><td  colspan="5" class="xTitre">Avancement tournoi</td></tr>';
	print '<tr class="xtr"><td class="xTitre">&nbsp;Paire NS&nbsp;</td><td class="xTitre">&nbsp;Paire EO&nbsp;</td><td class="xTitre">&nbsp;Dernière donne&nbsp;</br>enregistrée</td><td class="xTitre">&nbsp;Donnes&nbsp;</br>jouées&nbsp;</td><td class="xTitre">&nbsp;Position&nbsp;</br>courante&nbsp;</td></tr>';
	for  ($i = 1; $i < $max_tables+1; $i++) {
		$stcnx = $stconn[ $i ];
		print '<tr id="row_' . $i . '" class="xtr">';
		if ( $stcnx == 1 ) print '<td id="row_' . $i . '_0" class="xCnxtel">'   . $i . '</td>';
		if ( $stcnx == 2 ) print '<td id="row_' . $i . '_0" class="xCnxtelKo ">' . $i . '</td>';
		if ( $stcnx == 3 ) print '<td id="row_' . $i . '_0" class="xCnxtelOk ">' . $i . '</td>';
		print '<td id="row_' . $i . '_1">' . $numeo[$i] . '</td>';
		print '<td id="row_' . $i . '_2">' . $numdonne[$i] . '</td>';
		print '<td id="row_' . $i . '_3">' . $cpt[$i] . '</td>';
		print '<td id="row_' . $i . '_4">' . $pos[$i] . '</td>';
		print '</tr>';
		};
	print "</tbody></table>";
};
function getParmTable( $k ) {
	global $tab_connexions;
	$dbh = connectBDD();
	
	$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$k'" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$stconn = $row[ 'stconnexion' ];
	$numeo  = $row[ 'numEO' ];
	$numdonne = $row[ 'numdonne' ];
	$cpt = $row[ 'cpt' ];
	$pos = $row[ 'rdy' ];	// compteur de positions Mitchell / tours Howell
	
	$dbh = null;
	$result = array( 'numdonne' => $numdonne, 'cpt' => $cpt, 'pos' => $pos, 'numeo' => $numeo );
	return $result;
}
function getParmTableEO( $k ) {
	global $tab_connexions;
	$dbh = connectBDD();
	
	$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$k'" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$table  = $row[ 'eonumNS' ];
	$numdonne = $row[ 'eodonne' ];
	$cpt = $row[ 'eocpt' ];
	
	$dbh = null;
	$result = array( 'numdonne' => $numdonne, 'cpt' => $cpt, 'table' => $table );
	return $result;
}
function setCnxFin( $table ) {
	global $tab_connexions, $cnx_fin;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_connexions SET stconnexion = '$cnx_fin' where id = '$table';";
	$dbh->query($sql);
	$dbh = null;
}
function getMinTour( $n ) {
	global $tab_connexions;
	$dbh = connectBDD();
	$res = $dbh->query( "SELECT MIN(rdy) FROM $tab_connexions where id<=$n;" );
	$nb  = $res->fetchColumn();
	$dbh = null;
	return $nb;
}
function incrementCompteurRelaisEO( $dd, $ns, $eo, $inc, $paquet ) {
	global $tab_connexions, $cnx_ok;
	$dbh = connectBDD();
	// Incrémentation du compteur de donnes jouées/enregistrées par la paire ns
	$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$ns'" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$cpt = $row['cpt'];
	$cpt = $cpt + $inc;
	$dd  = $dd  + $inc;

	// test changement de position / tour
	$pos = $row['rdy'];
	$change = $cpt % $paquet;
	if ( $change == 0 ) $pos++;

	$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ok', numEO = '$eo', numdonne = '$dd', cpt ='$cpt', rdy ='$pos' where id = '$ns';";
	$res = $dbh->query($sql);			
	$dbh = null;
}
function incrementCompteurRelaisNS( $dd, $ns, $eo, $paquet ) {
	global $tab_connexions, $cnx_ok;
	$dbh = connectBDD();
	// Incrémentation du compteur de donnes jouées/enregistrées par la paire ns
	$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$ns'" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$cpt = $row['cpt'];
	$cpt++;

	// test changement de position / tour
	$pos = $row['rdy'];
	$change = $cpt % $paquet;
	if ( $change == 0 ) $pos++;

	$sql = "UPDATE $tab_connexions SET stconnexion='$cnx_ok', numEO = '$eo', numdonne = '$dd', cpt ='$cpt', rdy ='$pos' where id = '$ns';";
	$res = $dbh->query($sql);			
	$dbh = null;
}
function incrementCompteurEO( $eo, $paquet ) {
	global $tab_connexions;
	$dbh = connectBDD();
	// Incrémentation du compteur de la paire eo en relais sans donne (guéridon)
	$sth = $dbh->query( "SELECT * FROM $tab_connexions where id='$eo'" );
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$eocpt = $row['eocpt'];
	$eocpt +=$paquet;

	$sql = "UPDATE $tab_connexions SET eocpt ='$eocpt' where id = '$eo';";
	$res = $dbh->query($sql);			
	$dbh = null;
	return $eocpt;
}
?>