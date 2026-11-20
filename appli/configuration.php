<?php
require_once __DIR__.'/security.php';
if (PHP_SAPI !== 'cli' && isset($_GET['cmd']) &&
    in_array($_GET['cmd'], ['install', 'patch', 'droptables', 'gc'], true)) {
    securityDeny('Utiliser le superviseur opérateur ou la maintenance en ligne de commande.');
}

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
	function base_url(){
		if (isset($_SERVER['HTTP_HOST'])) {
			//$http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
			$http = 'https';	// **************************************************
			$hostname = $_SERVER['HTTP_HOST'];
			$dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			
			$tmplt = "%s://%s%s";
			$end = $dir;
			$base_url = sprintf( $tmplt, $http, $hostname, $end );
		}
		else $base_url = 'http://localhost/';
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
	die( $errmsg );
}

//
// initialisation de la session
//
$sql_sessions = "CREATE TABLE IF NOT EXISTS sessions (
	id varchar(32) NOT NULL, access int(10) unsigned,
	data text, PRIMARY KEY (id) );";
$dbh->query( $sql_sessions );
require_once __DIR__.'/session_maintenance.php';
try {
    $purgedSessions = bridgetteMonthlySessionGc($dbh, $db_name);
    if ($purgedSessions !== null) error_log('Bridgette monthly session GC: '.$purgedSessions.' row(s) deleted');
} catch (Throwable $e) {
    // Maintenance must not prevent club/Android connections; retry on a later visit.
    error_log('Bridgette monthly session GC failed: '.get_class($e).' code='.$e->getCode());
}
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
// Launcher tokens and club cookies select a club, never an identity.
$withtoken = isset($_GET['token']);
if ($withtoken || isset($_COOKIE['bridgette'])) {
    $raw = $withtoken ? $_GET['token'] : $_COOKIE['bridgette'];
    if (!is_string($raw)) securityDeny('Token incorrect.', 400);
    $json = base64_decode(strtr($raw, '-_', '+/'), true);
    $club = securityClub(json_decode($json ?: '', true));
} else {
    $club = securityClub(['prefix' => $_SESSION['prefix'] ?? null, 'timex' => $_SESSION['timex'] ?? null]);
}
securitySelectClub($club);
// Never refresh expired privileges just because a launcher token was supplied.
if (time() - ($_SESSION['ttlast'] ?? 0) > 1440) {
    unset($_SESSION['pseudo'], $_SESSION['fonction'], $_SESSION['auth_club']);
}
$_SESSION['ttlast'] = time();

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
	$min_noclub = 100;	// recherche numéro club disponible pour un nouveau joueur
	$max_noclub		= $config['max_noclub'];
}
else {
	session_unset();
	session_destroy();
	$errmsg = "<b>Erreur 06: Fichier de configuration absent !</b>";
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
$tab_events 	= $prefix . "events";

// constantes utilisées pour l'installation si requise
$debinvites = 4;
$max_tables = 15;		# limitation à 15

//
// test application correctement installée
//
$sql = "SELECT count(*) FROM `information_schema`.`tables` WHERE `table_schema`=? AND table_name like ?;";
$sqlBound = [$db_name, "$prefix%"];
$res = securityQuery($dbh, $sql, $sqlBound);
$ntablesbdd = $res->fetchColumn();		// 8 tables en tout
//print "N tables: $ntablesbdd";
if ( $ntablesbdd < 8 && !(PHP_SAPI === 'cli' && defined('BRIDGETTE_MAINTENANCE')) ) {
	$errmsg = "<b>Erreur 08: Tables $titre</b> absentes dans la base de données";
	$errmsg .= "<p>---> Installation / réinstallation requise !</p>";
	writelogerrors( $errmsg );
	session_unset();
	session_destroy(); 
	die( $errmsg );
}
// tables OK
$dbh = null;

$agepagemax = 3600;
?>
