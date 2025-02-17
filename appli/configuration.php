<?php
//
// Utilitaires
//
function writelogerrors( $data ) {
	$file_errors = "errors_".$_SERVER['SERVER_NAME'].".json";
	$record = [	"t" => date("Y-m-d_H:i:s"), "ttt" => $_SESSION['prefix'], "data" => $data ];
	$ligne = json_encode($record)."\n";
	file_put_contents( $file_errors, $ligne, FILE_APPEND );
}

if (!function_exists('base_url')) {
	function base_url($atRoot=FALSE, $atCore=FALSE, $parse=FALSE){
		if (isset($_SERVER['HTTP_HOST'])) {
			//$http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
			$http = 'https';	// **************************************************
			$hostname = $_SERVER['HTTP_HOST'];
			$dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			
			$core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), -1, PREG_SPLIT_NO_EMPTY);
			$core = $core[0];
			
			$tmplt = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
			$end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
			$base_url = sprintf( $tmplt, $http, $hostname, $end );
		}
		else $base_url = 'http://localhost/';
        
		if ($parse) {
			$base_url = parse_url($base_url);
			if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';
		}
        
		return $base_url;
	}
}
$base_url = base_url();
$isMobile  = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "mobile")); 
$isAndroid = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "android")); 

$relpgm = $isAndroid ? "/" : "";
$relimg = ( $isAndroid ? $relpgm : "" )."images/";		// sous-répertoire ./images

//
// configuration base de données
//
$dir_configs = "clubs/";
require( $dir_configs."conf_".$_SERVER['SERVER_NAME'].".php" );
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_port = DB_PORT;
$db_user = DB_USER;
$db_password = DB_PASS;

// test connexion base de données
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8";
try {
	$dbh = new PDO($dsn, $db_user, $db_password);
}
catch (PDOException $e) {
	$errmsg = "<b>Erreur 01: Vérifiez les paramètres de connexion à la base de données !</b>";
	$errmsg .= '<p>Erreur connexion : ' . $e->getMessage() . '</p>';
	//phpinfo();
	if ( extension_loaded('pdo_mysql') ){
		$code = $e->getCode();
		switch( $code ) {
			case 2002:	// SQLSTATE[HY000] [2002] php_network_getaddresses: gethostbyname failed. errno=0
				$errmsg .= "<p>KO 2002: Vérifier le site d'hébergement de la base de données</p>";
				break;
			case 1045:	// SQLSTATE[HY000] [1045] Access denied for user '$db_user' (using password: YES)
				$errmsg .= "<p>KO 1045: Vérifier le nom d'utilisateur / mot de passe de la base de données</p>";
				break;
			case 1049:	// SQLSTATE[HY000] [1049] Unknown database 'db_name'
				$errmsg .= "<p>KO 1045: Vérifier le nom de la base de données</p>";
				break;
			case 1203:	// SQLSTATE[HY000] [1203]
				$errmsg .= "<p>KO 1203: more than 'max_user_connections' active connections</p>";
				break;
		}
	}
	else {
		$errmsg .= '<p>Extension PDO_MYSQL non trouvée</p>';
		$errmsg .= '<p>Modifier la config php pour inclure le driver PDO</p>';
	}
	$errmsg .= "<p>DSN: $dsn</p>";
	//header( "Location: erreurbridgette.php?errmsg=".$errmsg );
	die( $errmsg );
}

//
// initialisation de la session
//
$sql_sessions = "CREATE TABLE IF NOT EXISTS sessions (
	id varchar(32) NOT NULL, access int(10) unsigned,
	data text, PRIMARY KEY (id) );";
$dbh->query( $sql_sessions );
include("src/database.class.php");	//Include MySQL database class
include("src/mysql.sessions.php");	//Include PHP MySQL sessions
try {
	$session = new Session();			//Start a new PHP MySQL session
}
catch (PDOException $e) {
	$errmsg = '<b>Erreur 02: Table sessions :</b> ' . $e->getMessage() . '</p>';
	die( $errmsg );
}
//
// initialisation avec cookie si existe
//
// priorité token en argument, on ne tient pas compte d'un cookie existant ou des informations de session
//
if ( $withtoken = isset($_GET['token']) ){
	$token = stripslashes($_GET['token']);
	$token = htmlspecialchars($token);
	$json  = base64_decode($token);
	
	if ( !$json ) {	// erreur sur base64 decodage
		session_unset();
		session_destroy();
		$errmsg = "<b>Erreur 03: Token incorrect, reconnectez-vous !</b>";
		die( $errmsg );
	}
	// décryptage réussi du token, décodage du contenu
	$club = json_decode($json, true);
	
	if ( $club == null ) { // erreur sur json décodage
		session_unset();
		session_destroy();
		$errmsg = "<b>Erreur 04: Décodage token incorrect, reconnectez-vous !</b>";
		//header( "Location: erreurbridgette.php?errmsg=".$errmsg );
		die( $errmsg );
	}
	// décodage réussi avec token, test d'une session existante avec un autre préfixe
	if ( isset($_SESSION['prefix']) ) {
		if ( $_SESSION['prefix'] != $club['prefix'] ) {
			// efface login directeur si existe
			unset( $_SESSION["pseudo"], $_SESSION["fonction"] );
		}
	}
	//initialise paramètres session
	$_SESSION['prefix'] = $club['prefix'];
	$_SESSION['timex']  = $club['timex'];
	$_SESSION['ttlast'] = time();
}
else { 	// pas de token, test si cookie existe
	if ( isset($_COOKIE['bridgette']) ) {	// cookie existe
		$token = $_COOKIE['bridgette'];
		$json = base64_decode($token);
		$club = json_decode($json, true);
		// décodage réussi avec cookie, initialise paramètres session
		$_SESSION['prefix'] = $club['prefix'];
		$_SESSION['timex']  = $club['timex'];
	}
	else {	// cookie absent, cas d'un client refusant les cookies !!!
		// test d'une session existante
		if ( !isset($_SESSION['prefix']) ) {
			session_unset();
			session_destroy();
			$errmsg = "<b>Erreur 05: Votre session a expiré, veuillez vous reconnecter !</b>";
			die( $errmsg );
		}
		// récupération des infos sur la session existante
		$club['prefix'] = $_SESSION['prefix'];
		$club['timex']  = $_SESSION['timex'];
	}
	if ( !isset($_SESSION['ttlast']) ) {
		$_SESSION['ttlast'] = $_SESSION['timex'];
	}
}

//
// Installation cookie
//
$maxjours = 30;	// max xx jours inactivité pour la durée de vie
$maxjoursgarbage = 100;	// max xx jours inactivité pour la conservation dans la table sessions
$maxdureesession = 3600*24*$maxjours;
// mise à jour du cookie
$json = json_encode(array( 'prefix'=>$club['prefix'], 'timex'=>$club['timex'] ));
$token = base64_encode( $json );
setcookie('bridgette', $token, time()+$maxdureesession);

//
// lecture du fichier de configuration du club
//
$prefix = $_SESSION['prefix'];
$dir_clubs = "clubs/";
$file_config = $dir_clubs.$prefix."configuration.php";
$file_params = $dir_clubs.$prefix."parametres.json";
$file_calendar = $dir_clubs.$prefix."calendrier.json";

if ( file_exists( $file_config ) ) {
	require( $file_config );
	
	// Site developpement = 1 / démonstration = 2 / déployé =3
	$site	= $config['site'];
	$titre	= $config['titre'];

	$urlbridgette = $config['urlbridgette'];
	$sitename = $config['sitename'];

	// taille max en ko de la base de données générant un message d'avertissement
	$maxSizeBDD = $config['maxSizeBDD'];		// kilo octets

	// Dimensionnement selon abonnement du club
	$max_noclub		= $config['max_noclub'];
}
else {
	session_unset();
	session_destroy();
	$errmsg = "<b>Erreur 06: Fichier de configuration absent !</b>";
	//header( "Location: erreurbridgette.php?errmsg=".$errmsg );
	writelogerrors( $errmsg );
	die( $errmsg );
}

// tables base de données, 8 tables en tout
$tab_donnes 	= $prefix . "donnes";
$tab_tournois	= $prefix . "tournois";
$tab_pairesNS	= $prefix . "pairesNS";
$tab_pairesEO	= $prefix . "pairesEO";
$tab_directeurs	= $prefix . "directeurs";
$tab_joueurs	= $prefix . "joueurs";
$tab_connexions	= $prefix . "connexions";
$tab_diagrammes	= $prefix . "diagrammes";

// constantes utilisées pour l'installation si requise
$debinvites = 4;
$max_tables = 13;

//
// test d'une commande à exécuter avec le token
//
if ( isset($_GET['cmd'])&&$withtoken ) {
	$cmd = stripslashes($_GET['cmd']);
	$cmd = htmlspecialchars($cmd);
	switch($cmd) {
		case "install": {
			writelogerrors( "Installation en cours ..." );
			echo "<p><b>Installation en cours ...</b></p>";
			require( "installtablesbdd.php" );
			echo "<p>----> création des tables si n'existent pas</p>";
			$dbh->query( $sql_directeurs );
			$dbh->query( $sql_joueurs );
			$dbh->query( $sql_tournois );
			$dbh->query( $sql_connexions );
			$dbh->query( $sql_pairesNS );
			$dbh->query( $sql_pairesEO );
			$dbh->query( $sql_donnes );
			$dbh->query( $sql_diagrammes );
			echo '<p>----> peuplement des tables si vides</p>';
			init_tab_directeurs($dbh);
			init_tab_joueurs($dbh);
			init_tab_connexions($dbh);
			echo '<p>----> terminé.</p>';
			break;
		}
		case "gc": {
			// garbage collector table sessions
			$session->_gc($maxjoursgarbage);
			echo '<p>garbage collector terminé.</p>';
			break;
		}
		default: {
			$errmsg = "<b>Erreur 07: Commande inconnue !</b> $titre";
			//header( "Location: erreurbridgette.php?errmsg=".$errmsg );
			writelogerrors( $errmsg );
			session_unset();
			session_destroy(); 
			die( $errmsg );
		}
	}
}

//
// test application correctement installée
//
$sql ="SELECT count(*) FROM `information_schema`.`tables` WHERE `table_schema`='$db_name' AND table_name like '$prefix%';";
$res = $dbh->query( $sql );
$ntablesbdd = $res->fetchColumn();		// 8 tables en tout
//print "N tables: $ntablesbdd";
if ( $ntablesbdd < 8 ) {
	$errmsg = "<b>Erreur 08: Tables $titre</b> absentes dans la base de données";
	$errmsg .= "<p>---> Installation / réinstallation requise !</p>";
	writelogerrors( $errmsg );
	session_unset();
	session_destroy(); 
	//header( "Location: erreurbridgette.php?errmsg=".$errmsg );
	die( $errmsg );
}
// tables OK
$dbh = null;

//
// test durée inactivité pour les directeurs/administrateurs
//
if ( !$withtoken ) {
	$agelogin = time() - $_SESSION['ttlast'];
	$maxloginactif = 1440;	// max 24 minutes d'inactivité = le standard !
	if ( $agelogin > $maxloginactif )
		unset( $_SESSION["pseudo"], $_SESSION["fonction"] );
}
$_SESSION['ttlast'] = time();
$agepagemax = 3600;		// 1 heure
?>