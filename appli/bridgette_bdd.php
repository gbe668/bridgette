<?php
require_once __DIR__.'/club_parameters.php';
require_once __DIR__.'/libcalculs.php';
try {
    $parametres = clubParametersLoad($file_params);
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Paramètres du club invalides ou illisibles. Contactez le responsable pour restaurer le fichier de paramètres.');
}

//tests de qualification
// 0 : fonctionnement normal
// 1 : test avec remplissage automatique et changement de position automatique
// 2 : test avec remplissage automatique et changement de position manuel
$teston = $parametres['param1'];

//autres variables constantes non modifiables par le paramétrage admin
$idtype_table_libre = 1;	// type de tournoi table libre

$del_noclub = 12;	// délai en mois avant réattribution du numéro
$debinvites = 4;

$st_notfound = 0;		# tournoi non trouvé
$st_mail = 1;			# résultats provenant d'un dépouillement externe
//Tournoi ouvert à la pré-inscription
$st_preinscription = 2;	# préinscription des joueurs en ligne
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
	$st_preinscription => "préinscription des joueurs en ligne",
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
function _logevent( $dbh, $event ) {
	global $tab_events;
	$pseudo = $_SESSION['pseudo'];
	$datevt = date('Y-m-d H:i:s');
	$sql = "INSERT into $tab_events ( pseudo, datevt, event ) values ( ?, ?, ? );";
	$sqlBound = [$pseudo, $datevt, $event];
	$res = securityQuery($dbh, $sql, $sqlBound);
};
function logevent( $event ) {
	$dbh = connectBDD();
	_logevent( $dbh, $event );
	$dbh = null;
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
function setIdent($userid) {
    global $prefix;
    unset($_SESSION['android_ticket_hash']);
    session_regenerate_id(true);
    $_SESSION['player_identity'] = ['club' => $prefix, 'id' => (int)$userid, 'time' => time()];
    securityRememberPlayer();
    setcookie('bridgette_user', '', time() - 3600);
}
function getIdent() {
    global $parametres, $ID_NOIDENT, $ID_INCORRECT, $ID_CORRECT;
    $id = securityPlayerId();
    if ($id) {
        return ['status' => $ID_CORRECT, 'userid' => $id];
    }
    return ['status' => !empty($parametres['checkuser']) ? $ID_INCORRECT : $ID_NOIDENT, 'userid' => 0];
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
	return securityRole('admin');
}
function isDirecteur() {
	return securityRole('directeur');
}
function isFormateur() {
	return securityRole('formateur');
}

//
require_once __DIR__.'/libmouvements.php';

// fonctions utilisant la table "donnes"
$contratNJ = "N J";
require_once __DIR__.'/libdonnes.php';
function first2play( $idt, $dd ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=?;";
	$sqlBound = [$idt, $dd];
	$res = securityQuery($dbh, $sql, $sqlBound);
	return $res->fetchColumn();
}
function testnotplayed( $idt, $ns, $eo, $dd ) {
	global $tab_donnes;
	$dbh = connectBDD();
	$dbh->query("START TRANSACTION;");
	// test donne déjà enregistrée
	$sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=?;";
	$sqlBound = [$idt, $dd, $ns, $eo];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nb  = $res->fetchColumn();
	if ( $nb == 0 ) {
		// test autre orientation
		$sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=?;";
		$sqlBound = [$idt, $dd, $eo, $ns];
		$res = securityQuery($dbh, $sql, $sqlBound);
		$nb  = $res->fetchColumn();
	}
	$dbh->query("COMMIT;");
	//print "<p>sql: $sql</p>";
	//print "<p>nb: $nb</p>";
	if ( $nb > 0 ) return false;
	else return true;
}
function _setRang( $idt, $dd, $dbh, $hweo, $x_donnes ) {
	// 28/11/2024: optimisation durée exécution
	global $tab_donnes, $contratNJ;
	/*
	$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = ? and etui = ? and hweo = ? and contrat !=?;";
	$sqlBound = [$idt, $dd, $hweo, $contratNJ];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl < 1 ) return;			// *************** manque la raz du rang et de la note
	*/
	$sql = "SELECT id, ns, eo, points FROM $x_donnes where idtournoi = ? and etui = ? and hweo = ? and contrat !=?;";
	$sqlBound = [$idt, $dd, $hweo, $contratNJ];
	$tabid = array();
	$pns = array();
	$peo = array();
	$pps = array();
	$ordre = array();
	$j = 0;
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$ordre[$j] = $j;
		$tabid[$j] = $row['id'];
		$pns[$j] = $row['ns'];
		$peo[$j] = $row['eo'];
		$pps[$j] = $row['points'];
		$j++;
	}
	if ( $j < 1 ) return;
	
	$top = $j-1; // nb de fois où la donne a été jouée - 1
	// Si donne jouée une seule fois pour l'instant
	if ( $top == 0 ) {
		// 50% pour les 2 paires
		$id1 = $tabid[0];
		$pns1 = $pns[0];
		$peo1 = $peo[0];
		$sql = "UPDATE $tab_donnes SET rang = '0', note = '50' where id = ?;";
		$sqlBound = [$id1];
		securityQuery($dbh, $sql, $sqlBound);
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
			$sql = "UPDATE $tab_donnes SET rang = ?, note = ? where id = ?;";
			$sqlBound = [$rang[$jj], $note[$jj], $tabid[$ij]];
			securityQuery($dbh, $sql, $sqlBound);
			// print "<p>-- PaireNS: $pns[$ij] PaireEO: $peo[$ij] Points: $pps[$jj] Rg: $rang[$jj] Note: $note[$jj]%" ;
		};
	};
};

require_once __DIR__.'/libresultats.php';

// fonctions utilisant la table "tournois"
function setTournoi($idt) {
	global $tab_tournois, $tab_donnes, $tab_pairesNS, $tab_pairesEO, $contratNJ;
	global $prefix;
	$dbh = connectBDD();
	
	$sql = "SELECT * FROM $tab_tournois where id = ?;";
	
	$sqlBound = [$idt];
	$sth = securityQuery($dbh, $sql, $sqlBound);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$pairesNS = $row['pairesNS'];
	$pairesEO = $row['pairesEO'];
	$ndonnes = $row['ndonnes'];

	// 28/11/2024	Optimisation durée d'exécution
	// Création table temporaire regroupant les résultats des donnes jouées pendant le tournoi
	$x_donnes = $prefix."TMP".sprintf( "%06d", rand( 1, 999999) );
	$sql = "CREATE TABLE $x_donnes AS SELECT * FROM $tab_donnes where idtournoi = ?;";
	$sqlBound = [$idt];
	//print "<p>sql: $sql</p>";
	$sth = securityQuery($dbh, $sql, $sqlBound);

	// calcul des rangs
	for ( $i = 1; $i <= $ndonnes; $i++) {
		//$t0 = microtime(true);
		_setRang( $idt, $i, $dbh, 0, $x_donnes );
		//$t1 = microtime(true);
		_setRang( $idt, $i, $dbh, 1, $x_donnes );
		//$t2 = microtime(true);
		//$exec1 = intval( ($t1 - $t0)*1000 );	// en millisecondes
		//$exec2 = intval( ($t2 - $t1)*1000 );	// en millisecondes
		//print "Durée exécution:".$exec1."   ".$exec2." ms</br>";
	};
	// Suppression table temporaire
	$sql = "DROP TABLE $x_donnes;";
	$sqlBound = [];
	$sth = securityQuery($dbh, $sql, $sqlBound);

	// Calcul des notes globales NS
	$pns = array();
	$notegns = array();
	for ( $i = 0; $i < $pairesNS; $i++) {
		$pns[$i] = $i+1;
		$notegns[$i] = 0;
	}
	$i = 0;
	$sql = "SELECT ns, AVG(note) AS perf FROM $tab_donnes where idtournoi = ? and contrat !=? GROUP BY ns ORDER by ns;";
	$sqlBound = [$idt, $contratNJ];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$pns[$i] = $row['ns'];
		$notegns[$i] = $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesNS; $i++) {
		$sql = "UPDATE $tab_pairesNS SET noteg = ? where idtournoi = ? and num = ?;";
		$sqlBound = [$notegns[$i], $idt, $pns[$i]];
		securityQuery($dbh, $sql, $sqlBound);
	};

	// Calcul des notes globales EO
	$peo = array();
	$notegeo = array();
	for ( $i = 0; $i < $pairesEO; $i++) {
		$peo[$i] = $i+1;
		$notegeo[$i] = 0;
	}
	$i = 0;
	$sql = "SELECT eo, AVG(note) AS perf FROM $tab_donnes where idtournoi = ? and contrat !=? GROUP BY eo ORDER by eo;";
	$sqlBound = [$idt, $contratNJ];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$peo[$i] = $row['eo'];
		$notegeo[$i] = 100 - $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesEO; $i++) {
		$sql = "UPDATE $tab_pairesEO SET noteg = ? where idtournoi = ? and num = ?;";
		$sqlBound = [$notegeo[$i], $idt, $peo[$i]];
		// print "<p>$sql</p>";
		securityQuery($dbh, $sql, $sqlBound);
	};
	$dbh = null;
};

function setTournoiIMP($idt) {	// tournoi type howell de 4 paires, chaque donne jouée 2 fois
	global $tab_tournois, $tab_donnes, $tab_pairesNS, $tab_pairesEO;
	$dbh = connectBDD();
	
	$sql = "SELECT * FROM $tab_tournois where id = ?;";
	
	$sqlBound = [$idt];
	$sth = securityQuery($dbh, $sql, $sqlBound);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$pairesNS = $row['pairesNS'];
	$pairesEO = $row['pairesEO'];
	$ndonnes = $row['ndonnes'];

	for ( $i = 1; $i <= $ndonnes; $i++) {
		$hweo = 0;
		$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = ? and etui = ? and hweo = ?;";
		$sqlBound = [$idt, $i, $hweo];
		$res = securityQuery($dbh, $sql, $sqlBound);
		$nb = $res->fetchColumn();
		if ( $nb == 2 ) {
			$sql = "SELECT * FROM $tab_donnes where idtournoi = ? and etui = ? and hweo = ?;";
			$sqlBound = [$idt, $i, $hweo];
			$res = securityQuery($dbh, $sql, $sqlBound);
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
			$sql = "UPDATE $tab_donnes SET note = ? where id = ?;";
			$sqlBound = [$imp1, $id1];
			securityQuery($dbh, $sql, $sqlBound);
			$sql = "UPDATE $tab_donnes SET note = ? where id = ?;";
			$sqlBound = [$imp2, $id2];
			securityQuery($dbh, $sql, $sqlBound);
		}
		else {
			// erreur
		}
	};
	for ( $i = 1; $i <= $ndonnes; $i++) {
		$hweo = 1;
		$sql = "SELECT count(*) FROM $tab_donnes where idtournoi = ? and etui = ? and hweo = ?;";
		$sqlBound = [$idt, $i, $hweo];
		$res = securityQuery($dbh, $sql, $sqlBound);
		$nb = $res->fetchColumn();
		if ( $nb == 2 ) {
			$sql = "SELECT * FROM $tab_donnes where idtournoi = ? and etui = ? and hweo = ?;";
			$sqlBound = [$idt, $i, $hweo];
			$res = securityQuery($dbh, $sql, $sqlBound);
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
			$sql = "UPDATE $tab_donnes SET note = ? where id = ?;";
			$sqlBound = [$imp1, $id1];
			securityQuery($dbh, $sql, $sqlBound);
			$sql = "UPDATE $tab_donnes SET note = ? where id = ?;";
			$sqlBound = [$imp2, $id2];
			securityQuery($dbh, $sql, $sqlBound);
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
	$sql = "SELECT ns, SUM(note) AS perf FROM $tab_donnes where idtournoi = ? GROUP BY ns ORDER by ns;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$pns[$i] = $row['ns'];
		$impns[$i] = $row['perf'];
		$i++;
	};
	for ( $i = 0; $i < $pairesNS; $i++) {
		$sql = "UPDATE $tab_pairesNS SET noteg = ? where idtournoi = ? and num = ?;";
		$sqlBound = [$impns[$i], $idt, $pns[$i]];
		//print "<p>$sql</p>";
		securityQuery($dbh, $sql, $sqlBound);
	};
	
	$dbh = null;
};

function existeTournoiPreinscription($datetournoi) {	// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois, $st_preinscription;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_tournois where etat = ? and tournoi=?;";
	
	$sqlBound = [$st_preinscription, $datetournoi];
	if ( $res = securityQuery($dbh, $sql, $sqlBound)) {
		$nbl = $res->fetchColumn();
		if ( $nbl == 1 ) {
			$sth = securityQuery($dbh, "SELECT id FROM $tab_tournois where etat = ? and tournoi=?;", [$st_preinscription, $datetournoi]);
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
		}
		else $idt = 0;
	}
	else $idt = 0;
	
	$dbh = null;
	return $idt;
};
function existeTournoiVivant() {			// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois;
	global $st_phase_init, $st_phase_jeu;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_tournois where etat = ? or etat = ?;";
	
	$sqlBound = [$st_phase_init, $st_phase_jeu];
	if ( $res = securityQuery($dbh, $sql, $sqlBound)) {
		$nbl = $res->fetchColumn();
		if ( $nbl == 1 ) {
			$sth = securityQuery($dbh, "SELECT id FROM $tab_tournois where etat = ? or etat = ?;", [$st_phase_init, $st_phase_jeu]);
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
		}
		else $idt = 0;
	}
	else $idt = 0;
	
	$dbh = null;
	return $idt;
};
function existeTournoiNonClos() {			// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois;
	global $st_phase_init, $st_phase_jeu, $st_phase_fini;
	$dbh = connectBDD();
	
	$sql1 = "SELECT count(*) FROM $tab_tournois where etat = ? or etat = ? or etat = ?;";
	
	$sql1Bound = [$st_phase_init, $st_phase_jeu, $st_phase_fini];
	$sql2 = "SELECT id FROM $tab_tournois where etat = ? or etat = ? or etat = ?;";
	$sql2Bound = [$st_phase_init, $st_phase_jeu, $st_phase_fini];
	if ( $res = securityQuery($dbh, $sql1, $sql1Bound)) {
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			$results = securityQuery($dbh, $sql2, $sql2Bound);
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
	
	$sql1 = "SELECT count(*) FROM $tab_tournois where etat = ? or etat = ? or etat = ?;";
	
	$sql1Bound = [$st_phase_init, $st_phase_jeu, $st_phase_fini];
	$sql2 = "SELECT id FROM $tab_tournois where etat = ? or etat = ? or etat = ?;";
	$sql2Bound = [$st_phase_init, $st_phase_jeu, $st_phase_fini];
	if ( $res = securityQuery($dbh, $sql1, $sql1Bound) ) {
		$nbl = $res->fetchColumn();
		if ( $nbl > 0 ) {
			$results = securityQuery($dbh, $sql2, $sql2Bound);
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
function existeTournoiClos( $datetournoi ) {// return tableau des idtournoi qui existent à la date
	global $tab_tournois, $st_closed;
	$ids = array();

	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat=? and tournoi=?;";
	$sqlBound = [$st_closed, $datetournoi];
	$sth = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $sth->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "SELECT id FROM $tab_tournois where etat=? and tournoi=?;";
		$sqlBound = [$st_closed, $datetournoi];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$ids[$i] = $row[ 'id' ];
		}
	}
	$dbh = null;
	return json_encode( array( 'nbl'=>$nbl, 'ids'=>$ids ) );
};
function getlastclosedtournois() {			// return idtournoi si existe, 0 si non trouvé
	global $tab_tournois, $st_closed;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = ?;";
	$sqlBound = [$st_closed];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sth = securityQuery($dbh, "SELECT * FROM $tab_tournois where etat = ? order by tournoi desc;", [$st_closed]);
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$id = $row[ 'id' ];
	}
	else $id = 0;
	$dbh = null;
	return $id;
};

function createTournoi() {			// return idtournoi, 0 si erreur
	global $tab_tournois, $tab_events;
	global $def_genre, $def_type_howell, $def_type_mitchell, $t_howell;
	global $st_phase_init, $st_phase_jeu, $st_phase_fini;
	$tt = date('Y-m-d');	// date de création en attendant de stocker la date de démarrage

	$dbh = connectBDD();
	$dbh->query("LOCK TABLES $tab_tournois WRITE, $tab_events WRITE;");
	
	// vérification d'absence de tournoi non clos avant création effective
	$sql = "SELECT count(*) FROM $tab_tournois where etat = ? or etat = ? or etat = ?;";
	$sqlBound = [$st_phase_init, $st_phase_jeu, $st_phase_fini];
	if ( $sth = securityQuery($dbh, $sql, $sqlBound) ) {
		$nbl = $sth->fetchColumn();
		if ( $nbl > 0 ) {
			// il existe au moins un tournoi non clos
			$id = 0;	// pour signaler l'erreur création
		}
		else {
			// pas de tournoi non clos, création tournoi avec le code associé
			$n = rand( 1, 9999);
			$code = sprintf( "%04d", $n );
			$idtype = ( $def_genre == $t_howell ) ? $def_type_howell : $def_type_mitchell;
			$sql = "INSERT INTO $tab_tournois ( tournoi, code, pairesNS, pairesEO, idtype, etat ) VALUES ( ?, ?, 0, 0, ?, ? );";
			$sqlBound = [$tt, $code, $idtype, $st_phase_init];
			if ( securityQuery($dbh, $sql, $sqlBound) ) {
				$id = $dbh->lastInsertId();
				//_logevent($dbh, "createTournoi ".$tt." id ".$id);
			}
			else $id = 0;
		}
	}
	else $id = 0;
	$dbh->query("UNLOCK TABLES;");

	$dbh = null;
	return json_encode( array( 'idtournoi'=> $id ) );
};
function startInscriptionTournoi( $id ) { // Démarrage tournoi pré-inscription
	global $tab_tournois, $st_phase_init;
	$dbh = connectBDD();
	//_logevent($dbh, "startInscriptionTournoi ".$id);
	
	// changement d'état du tournoi
	$sql = "UPDATE $tab_tournois SET etat = ? where id = ?;";
	$sqlBound = [$st_phase_init, $id];
	$res = securityQuery($dbh, $sql, $sqlBound);

	$dbh = null;
	return json_encode( array( 'idtournoi'=> $id ) );
}
function createInscriptionTournoi( $datetournoi ) { // Création tournoi pré-inscription si n'existe pas
	// Si un tournoi est en cours pour le même jour, on crée un nouveau tournoi de préinscription
	// 	cas d'un marathon où les joueurs peuvent s'inscrire pour le tournoi suivant le même jour
	// 	alors que le tournoi en cours n'est pas terminé
	global $tab_tournois, $tab_events, $tab_pairesNS, $tab_pairesEO, $tab_joueurs, $def_type_mitchell;
	global $st_preinscription, $st_phase_init, $st_phase_jeu, $st_phase_fini, $max_tables;

	$dbh = connectBDD();
	$dbh->query("LOCK TABLES $tab_tournois WRITE, $tab_events WRITE, $tab_pairesNS WRITE, $tab_pairesEO WRITE, $tab_joueurs WRITE;");
	
	// vérification d'absence de tournoi non clos avant création effective
	$sql = "SELECT count(*) FROM $tab_tournois where tournoi=? and (etat = ? or etat = ? or etat = ?);";
	$sqlBound = [$datetournoi, $st_phase_init, $st_phase_jeu, $st_phase_fini];
	$nbl = securityQuery($dbh, $sql, $sqlBound)->fetchColumn();
	if ( $nbl > 0 ) {	// il existe un tounoi non clos en cours
		$idt = 0;
		$ret = "<p><span style='color:red'>Attention, il existe un tounoi en cours !</br>La pré-inscription est possible pour un nouveau tournoi qui ne pourra commencer qu'après la clôture du tournoi en cours !</span></p>";
	}
	else $ret = "";
	// test existence d'un tournoi de préinscription existant
	$sql = "SELECT count(*) FROM $tab_tournois where tournoi=? AND etat = ?;";
	$sqlBound = [$datetournoi, $st_preinscription];
	$nbl = securityQuery($dbh, $sql, $sqlBound)->fetchColumn();
	switch( $nbl ) {
		case 0: { // Pas de tournoi, création d'un tournoi en préparation de type mitchell
			$n = rand( 1, 9999);
			$code = sprintf( "%04d", $n );
			$sql = "INSERT INTO $tab_tournois ( tournoi, code, pairesNS, pairesEO, idtype, etat )
				VALUES ( ?, ?, 0, 0, ?, ? );";
			$sqlBound = [$datetournoi, $code, $def_type_mitchell, $st_preinscription];
			securityQuery($dbh, $sql, $sqlBound);
			$idt = $dbh->lastInsertId();
			//_logevent($dbh, "createInscriptionTournoi ".$datetournoi." id ".$idt);
			$ret .= "<p>Création tournoi pré-inscription</p>";
			$lignes = [];
			break;
		}
		case 1: {	// déjà créé
			$sql = "SELECT id FROM $tab_tournois where tournoi=? AND etat = ?;";
			$sqlBound = [$datetournoi, $st_preinscription];
			$row = securityQuery($dbh, $sql, $sqlBound)->fetch(PDO::FETCH_ASSOC);
			$idt = $row['id'];
			$ret .= "<p>Tournoi pré-inscription déjà créé</p>";
			// Tableau des paires pré-inscrites (paires complètes ou à compléter)
			$lignes = _getlignes($dbh, $idt);
			break;
		}
		default: {	// Erreur
			$idt = 0;
			$ret .= "<p>Erreur, $nbl tournois pré-inscription</p>";
			$lignes = [];
			break;
		}
	}
	$dbh->query("UNLOCK TABLES;");

	$dbh = null;
	return json_encode( array( 'idtournoi'=> $idt, 'ret'=>$ret, 'lignes'=>$lignes ) );
}
function _getlignes($dbh, $idt) {
	global $max_tables, $tab_pairesNS, $tab_pairesEO, $tab_joueurs;
	$lignes = [];
	if ( $idt > 0 ) {
		$libre = array( 'id'=>0, 'nomcomplet'=>" ");
		
		$sqlns = "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = :idt and num = :indice;";
		$sqleo = "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = :idt and num = :indice;";
		$sthns = $dbh->prepare( $sqlns );
		$stheo = $dbh->prepare( $sqleo );
		
		$sqlj = "SELECT * FROM $tab_joueurs where id = :id;";
		$sthj = $dbh->prepare( $sqlj );
		
		for  ($i = 1; $i < $max_tables+1; $i++) {
			// paire NS
			$resns = $sthns->execute(array(':indice' => $i, ':idt' => $idt));
			if ( $sthns->rowCount() > 0 ) {		// ************* be careful with rowCount
				$row = $sthns->fetch(PDO::FETCH_ASSOC);
				if ( $row['idj1'] != null ) {
					$idj = $row['idj1'];
					$resj = $sthj->execute( array(':id'=>$idj) );
					$joueur = $sthj->fetch(PDO::FETCH_ASSOC);
					$ligne['A'] = array( 'id'=>$idj, 'prenom'=> $joueur['prenom'],
						'nomcomplet'=> $joueur['prenom'] . " " . $joueur['nom'],
						'telephone' => $joueur[ 'telephone' ],
						'email' => $joueur[ 'email' ] );
					//$ligne['A'] = _getJoueur( $dbh, $row[ 'idj1' ] );
				}
				else $ligne['A'] = $libre;
				if ( $row[ 'idj3' ] != null ) {
					$idj = $row['idj3'];
					$resj = $sthj->execute( array(':id'=>$idj) );
					$joueur = $sthj->fetch(PDO::FETCH_ASSOC);
					$ligne['B'] = array( 'id'=>$idj, 'prenom'=> $joueur['prenom'],
						'nomcomplet'=> $joueur['prenom'] . " " . $joueur['nom'],
						'telephone' => $joueur[ 'telephone' ],
						'email' => $joueur[ 'email' ] );
					//$ligne['B'] = _getJoueur( $dbh, $row[ 'idj3' ] );
				}
				else $ligne['B'] = $libre;
			}
			else {
				$ligne['A'] = $libre;
				$ligne['B'] = $libre;
			}
			array_push( $lignes, $ligne );
			
			// paire EO
			$reseo = $stheo->execute(array(':indice' => $i, ':idt' => $idt));
			if ( $stheo->rowCount() > 0 ) {
				$row = $stheo->fetch(PDO::FETCH_ASSOC);
				if ( $row[ 'idj2' ] != null ) {
					$idj = $row['idj2'];
					$resj = $sthj->execute( array(':id'=>$idj) );
					$joueur = $sthj->fetch(PDO::FETCH_ASSOC);
					$ligne['A'] = array( 'id'=>$idj, 'prenom'=> $joueur['prenom'],
						'nomcomplet'=> $joueur['prenom'] . " " . $joueur['nom'],
						'telephone' => $joueur[ 'telephone' ],
						'email' => $joueur[ 'email' ] );
					//$ligne['A'] = _getJoueur( $dbh, $row[ 'idj2' ] );
				}
				else $ligne['A'] = $libre;
				if ( $row[ 'idj4' ] != null ) {
					$idj = $row['idj4'];
					$resj = $sthj->execute( array(':id'=>$idj) );
					$joueur = $sthj->fetch(PDO::FETCH_ASSOC);
					$ligne['B'] = array( 'id'=>$idj, 'prenom'=> $joueur['prenom'],
						'nomcomplet'=> $joueur['prenom'] . " " . $joueur['nom'],
						'telephone' => $joueur[ 'telephone' ],
						'email' => $joueur[ 'email' ] );
					//$ligne['B'] = _getJoueur( $dbh, $row[ 'idj4' ] );
				}
				else $ligne['B'] = $libre;
			}
			else {
				$ligne['A'] = $libre;
				$ligne['B'] = $libre;
			}
			array_push( $lignes, $ligne );
		}
	}
	return $lignes;
}
function initTournoi( $idt, $pns, $peo ) {
	// Initialisation faite à la fin de la définition des paires
	global $tab_tournois, $t_howell;
	$type = computetype( $pns, $peo );
	$tt = gettypetournoi( $type );
	
	$ok = $tt['npaires'];
	if ( $ok > 0 ) {
		// type existe
		$ntables = $tt['ntables'];
		$paquet  = $tt['paquet'];			// valeur par défaut
		$npositions = $tt['npositions'];	// valeur par défaut
		$ndonnes = $ntables*$paquet;		//$tt['ndonnes'];			// en circulation
		if ($tt['genre'] == $t_howell) $ndonnes = $tt['npaquets'] * $paquet;
		$njouees = $npositions*$paquet;		//$tt['njouees'];			// valeur calculée !!!
		$saut	= $tt['saut'];
		$relais	= $tt['relais'];
		$gueridon = $tt['gueridon'];
		//$genre	= $tt['genre'];			// genre howell / mitchell non stocké en bdd, déduit du type de tournoi
		$obs	= "aa";	// provision pour texte entré par le directeur de tournoi
	
		$dbh = connectBDD();
		$sql = "UPDATE $tab_tournois SET pairesNS = ?, pairesEO = ?, idtype = ?, ntables = ?, paquet = ?, ndonnes = ?, npositions = ?, njouees = ?, saut = ?, relais = ?, gueridon = ?, obs = ? where id = ?;";
		$sqlBound = [$pns, $peo, $type, $ntables, $paquet, $ndonnes, $npositions, $njouees, $saut, $relais, $gueridon, $obs, $idt];
		$res = securityQuery($dbh, $sql, $sqlBound);
		$dbh = null;
	}
	return $ok;
};

function purge_Tournoi($idt) {
	global $tab_tournois, $tab_diagrammes, $tab_donnes, $tab_pairesNS, $tab_pairesEO;
	$dbh = connectBDD();
	_logevent($dbh, "purge tournoi ".$idt);
	
	$indices = array();
	// suppression diagrammes éventuels
	//$sql = "DELETE from $tab_diagrammes where id ='$id'";	// n'est pas accepté en mode safe, faut utiliser la primary key
	$sql = "select count(*) from $tab_diagrammes where idtournoi =?;";
	$sqlBound = [$idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_diagrammes where idtournoi =?;";
		$sqlBound = [$idt];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_diagrammes where id =?;";
			$sqlBound = [$ii];
			$res = securityQuery($dbh, $sql, $sqlBound);
		}
	}
	// suppression donnes
	$sql = "select count(*) from $tab_donnes where idtournoi =?;";
	$sqlBound = [$idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_donnes where idtournoi =?;";
		$sqlBound = [$idt];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_donnes where id =?;";
			$sqlBound = [$ii];
			$res = securityQuery($dbh, $sql, $sqlBound);
		}
	}
	// suppression paires NS
	$sql = "select count(*) from $tab_pairesNS where idtournoi =?;";
	$sqlBound = [$idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesNS where idtournoi =?;";
		$sqlBound = [$idt];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_pairesNS where id =?;";
			$sqlBound = [$ii];
			$res = securityQuery($dbh, $sql, $sqlBound);
		}
	}
	// suppression paires EO
	$sql = "select count(*) from $tab_pairesEO where idtournoi =?;";
	$sqlBound = [$idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesEO where idtournoi =?;";
		$sqlBound = [$idt];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$indices[$i] = $row['id'];
		}
		for ( $i = 0; $i < $nbl; $i++ ) {
			$ii = $indices[$i];
			$sql = "DELETE from $tab_pairesEO where id =?;";
			$sqlBound = [$ii];
			$res = securityQuery($dbh, $sql, $sqlBound);
		}
	}
	$dbh = null;
};
function eraseTournoi($id) {			// true si OK, false sinon
	global $tab_tournois;
	purge_Tournoi($id);
	
	$dbh = connectBDD();
	$sql = "DELETE from $tab_tournois where id =?;";
	$sqlBound = [$id];
	if ( $res = securityQuery($dbh, $sql, $sqlBound)) {
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
	$sql = "SELECT count(*) FROM $tab_tournois where id = ?;";
	$sqlBound = [$id];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "UPDATE $tab_tournois SET etat = ? where id = ?;";
		$sqlBound = [$etat, $id];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
	else $etat = $st_notfound;
	$dbh = null;
	return $etat;
};
function set_pairesNS( $idt, $pns ) {	// return $pns
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET pairesNS = ? where id = ?;";
	$sqlBound = [$pns, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$dbh = null;
	return $pns;
};
function set_pairesEO( $idt, $peo ) {	// return $peo
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET pairesEO = ? where id = ?;";
	$sqlBound = [$peo, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$dbh = null;
	return $peo;
};
function set_typetournoi( $idt, $type ) {	// return typetournoi
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET idtype = ? where id = ?;";
	$sqlBound = [$type, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$dbh = null;
	return $type;
};
function set_ndonnes( $idt, $nb ) {	// return $nb
	global $tab_tournois;
	$dbh = connectBDD();
	$sql = "UPDATE $tab_tournois SET ndonnes = ? where id = ?;";
	$sqlBound = [$nb, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$dbh = null;
	return $nb;
};
function set_npositions( $idt, $npositions ) {	// phase jeu
	global $tab_tournois;
	$dbh = connectBDD();
	_logevent($dbh, "setnpositions ".$idt." n=".$npositions);
	$t = _readTournoi( $dbh, $idt );
	// calcul des dépendances
	$njouees = $t[ 'paquet' ] * $npositions;
	
	$sql = "UPDATE $tab_tournois SET npositions = ?, njouees = ? where id = ?;";
	
	$sqlBound = [$npositions, $njouees, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$t = _readTournoi( $dbh, $idt );
	$dbh = null;
	return $t['desc'];
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
	//_logevent($dbh, "startmitchell ".$idt." paq=".$paquet);
	
	$sql = "UPDATE $tab_tournois SET tournoi = ?, etat = ?, paquet = ?, ndonnes = ?, njouees = ?, notour = 1, startseq = ?, endofseq = ? where id = ?;";
	
	$sqlBound = [$start, $st_phase_jeu, $paquet, $ndonnes, $njouees, $startseq, $endofseq, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	
	for ( $i = 1; $i <= $ntables; $i++ ) {
		$donne = ($i-1)*$paquet;
		// test guéridon
		if ( ( $i > $posgueridon ) and ( $gueridon > 0 ) )
			$donne += $paquet;
		if ( ( $i == $ntables ) and ( $gueridon > 0 ) )
			$eocpt = $paquet;
		else
			$eocpt = 0;
		$sql = "UPDATE $tab_connexions SET stconnexion=?, numEO=0, numdonne=?, cpt=0, rdy=1, eocpt=?, eonumNS=0, eodonne=? where id=?;";
		$sqlBound = [$cnx_ko, $donne, $eocpt, $donne, $i];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
	for ( $i = $ntables+1; $i <= $max_tables; $i++ ) {
		$sql = "UPDATE $tab_connexions SET stconnexion=?, numEO=0, numdonne=0, cpt=0, rdy=0, eocpt=0, eonumNS=0, eodonne=0 where id=?;";
		$sqlBound = [$cnx_ko, $i];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
	$dbh = null;
};
function start_howell( $idt, $paquet ) {	// phase jeu
	global $tab_tournois, $tab_connexions, $t_howell;
	global $parametres, $st_phase_jeu, $max_tables, $cnx_ko;
	
	$start = date('Y-m-d');	// date de démarrage
	
	$t = readTournoi( $idt );
	$idtype   = $t[ 'idtype' ];
	$genre    = $t[ 'genre' ];
	$ntables  = $t[ 'ntables' ];
	$pairesNS = $t[ 'pairesNS' ];
	
	// Stock d'étuis propre au mouvement, y compris en présence d'un relais.
	$njouees = $t[ 'npositions' ] * $paquet;
	$tt = gettypetournoi($idtype);
	if ($paquet < 1 || $paquet != (int)$paquet || $paquet > $tt['maxpaquet']) {
		throw new InvalidArgumentException('Taille de paquet Howell invalide.');
	}
	$ndonnes = $tt['npaquets'] * $paquet;
	$firstduree = ( $paquet * ($parametres['dureedonne'] + $parametres['dureediagrammes']) + $parametres['dureeinitiale'] ) * 60;	// en secondes
	$date = new DateTime();
	$startseq = $date->getTimestamp();
	$endofseq = $startseq + $firstduree;
	//$sequence = "1_".$endofseq;		// fin du 1er tour
	
	$dbh = connectBDD();
	//_logevent($dbh, "starthowell ".$idt." paq=".$paquet);
	
	$sql = "UPDATE $tab_tournois SET tournoi = ?, etat = ?, paquet = ?, ndonnes = ?, njouees = ?, notour = 1, startseq = ?, endofseq = ? where id = ?;";
	
	$sqlBound = [$start, $st_phase_jeu, $paquet, $ndonnes, $njouees, $startseq, $endofseq, $idt];
	$res = securityQuery($dbh, $sql, $sqlBound);
	
	for ( $i = 1; $i <= $ntables; $i++ ) {
		$p = getposhowell( $idtype, $i, 1, $paquet );	
		$lastdonne = $p['last'];
		$numEO = $p['adversaire'];
		
		$sql = "UPDATE $tab_connexions SET stconnexion =?, numEO =?, numdonne =?, cpt = 0, rdy = 1 where id = ?;";
		
		$sqlBound = [$cnx_ko, $numEO, $lastdonne, $i];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
	for ( $i = $ntables+1; $i <= $max_tables; $i++ ) {
		$sql = "UPDATE $tab_connexions SET stconnexion = ?, numEO = 0, numdonne = 0, cpt = 0, rdy = 0 where id = ?;";
		$sqlBound = [$cnx_ko, $i];
		$res = securityQuery($dbh, $sql, $sqlBound);
	}
	$dbh = null;
};
function _readTournoi( $dbh, $idt ) {
	global $tab_tournois, $type_mitchell, $t_howell, $t_mitchell;
	global $st_notfound, $st_phase_jeu, $st_phase_fini, $st_closed;
	
	$sql = "SELECT * FROM $tab_tournois WHERE id = :idt;";
	$sth = $dbh->prepare($sql);
	$sth->execute([':idt' => $idt]);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	if ( $row ) {
		$datet = $row[ 'tournoi' ];
		$datef = strdatet( $datet );
		$tt = gettypetournoi( $row[ 'idtype' ] );
		$genre = $tt['genre'];
		if ( $row['npositions'] == null ) {
			$desc = str_replace("%np%", $tt['npositions'], $tt['desc']);
		}
		else {
			$desc = str_replace("%np%", $row[ 'npositions' ], $tt['desc']);
			if (($row[ 'etat' ] == $st_phase_jeu)||
				($row[ 'etat' ] == $st_phase_fini)||
				($row[ 'etat' ] == $st_closed)) {
					$desc .= ", ".$row['paquet']." étuis par table";
					$desc .= ", ".$row['ndonnes']." donnes en circulation";
				}
		}
		
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
			'desc'		=> $desc,
			'obs'	 	=> $row[ 'obs' ],		// entré par le directeur de tournoi
			);
	}
	else {
		$dt = array(
			'id' 		=> $idt,			// id tournoi
			'etat' 		=> $st_notfound,
			'genre'		=> $t_mitchell,	
			'desc'		=> "inconnu",
			);
	}
	return $dt;
};
function readTournoi( $idt ) {
	$dbh = connectBDD();
	$t = _readTournoi( $dbh, $idt );
	$dbh = null;
	return $t;
}

function buildDestinatairesResultats($idt) {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO;
	$dbh = connectBDD();
	
	$stack = [];

	$sql = "SELECT * FROM $tab_tournois where id = ?;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$pairesNS = $row['pairesNS'];
		$pairesEO = $row['pairesEO'];
		}

	// Lecture des joueurs NS
	$tabj1 = array();
	$tabj3 = array();
	$i = 0;
	$sql = "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = ?;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
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
	$sql = "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = ?;";
	$sqlBound = [$idt];
	foreach (securityQuery($dbh, $sql, $sqlBound) as $row) {
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
function displayTournoiIMP($idt) {
	print htmlDisplayTournoiIMP($idt);
};
function htmlDisplayResultatsTournoi($idt, $screenw, $displayGeneral = False) {
	global $parametres, $min_type_affimp;
	$t0 = microtime(true);
	$t = readTournoi( $idt );
	if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
		setTournoiIMP( $idt );
		$str = htmlDisplayTournoiIMP( $idt );
	}
	else {
		setTournoi($idt);
		$str = htmlDisplayTournoi( $idt, $screenw, $displayGeneral );
	}
	$t1 = microtime(true);
	$exec = intval( ($t1 - $t0)*1000 );	// en millisecondes
	$pts = getPointsHonneursMoyens($idt);
	$str .= "<p>Points Honneurs moyens:<br>Nord-Sud: ".$pts['ns']." Est-Ouest: ".$pts['eo'];
	//$str .= "</br>Durée exécution:".$exec." ms";
	$str .= "</p>";
	return $str;
};

// fonctions utilisant la table des diagrammes
// optimisées avec chatgpt le 04/08/2026
function existeDiagramme($idt,$n) {		// return diagramme, null si non trouvé
	global $tab_diagrammes;
	$dbh = connectBDD();
	
    $sql = "SELECT dealt, dds
            FROM $tab_diagrammes
            WHERE idtournoi = :idt AND etui = :etui
            LIMIT 1";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':idt'  => $idt,
        ':etui' => $n
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $dbh = null;

    if ($row) {
        return [$row['dealt'], $row['dds']];
    }
    return [null, null];
};
function insertDiagramme($idt, $n, $diag, $dds) {
    global $tab_diagrammes;

    // Calcul des points d'honneur
    $mains = explode(" ", substr($diag, 2));
    $h = array_map(fn($main) =>
        4 * substr_count($main, "A") +
        3 * substr_count($main, "K") +
        2 * substr_count($main, "Q") +
            substr_count($main, "J"),
        array_slice($mains, 0, 4)
    );

    $dbh = connectBDD();
    $dbh->beginTransaction();

    try {
        // INSERT IGNORE : ne fait rien si (idtournoi, etui) existe déjà
        $sql = "INSERT IGNORE INTO $tab_diagrammes
                    (idtournoi, etui, dealt, h1, h2, h3, h4, dds)
                VALUES
                    (:idt, :n, :diag, :h0, :h1, :h2, :h3, :dds)";
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':idt'  => $idt,  ':n'   => $n,
            ':diag' => $diag, ':dds' => $dds,
            ':h0'   => $h[0], ':h1'  => $h[1], ':h2' => $h[2], ':h3' => $h[3],
        ]);

        if ($sth->rowCount() === 1) {
            // Ligne insérée
            $id = $dbh->lastInsertId();
        }
		else {
            // Ligne déjà existante : récupération de l'id
            $sel = $dbh->prepare(
                "SELECT id FROM $tab_diagrammes WHERE idtournoi = :idt AND etui = :n"
            );
            $sel->execute([':idt' => $idt, ':n' => $n]);
            $id = $sel->fetchColumn();
        }

        $dbh->commit();
    }
	catch (PDOException $e) {
        $dbh->rollBack();
        $id = null;
        // error_log($e->getMessage());
    }
	finally {
        $dbh = null;
    }

    return $id;
}
function updateDiagramme($idt, $n, $diag, $dds) {
    global $tab_diagrammes;

    // Calcul des points d'honneur
    $mains = explode(" ", substr($diag, 2));
    $h = array_map(fn($main) =>
        4 * substr_count($main, "A") +
        3 * substr_count($main, "K") +
        2 * substr_count($main, "Q") +
            substr_count($main, "J"),
        array_slice($mains, 0, 4)
    );

    $dbh = connectBDD();
    $dbh->beginTransaction();

    try {
        $sql = "UPDATE $tab_diagrammes
                SET dealt = :diag, h1 = :h0, h2 = :h1, h3 = :h2, h4 = :h3, dds = :dds
                WHERE idtournoi = :idt AND etui = :n";
        $sth = $dbh->prepare($sql);
        $sth->execute([
            ':diag' => $diag,
            ':h0'   => $h[0], ':h1' => $h[1], ':h2' => $h[2], ':h3' => $h[3],
            ':dds'  => $dds,
            ':idt'  => $idt,
            ':n'    => $n,
        ]);

        $r = $sth->rowCount(); // 0 si inconnu, 1 si mis à jour

        $dbh->commit();
    } catch (PDOException $e) {
        $dbh->rollBack();
        $r = 0;
        // error_log($e->getMessage());
    } finally {
        $dbh = null;
    }

    return $r;
}
function deleteDiagramme($idt, $n) {
    global $tab_diagrammes;

    $dbh = connectBDD();
    $dbh->beginTransaction();

    try {
        // Suppression directe sans SELECT préalable (DELETE retourne le nombre de lignes affectées)
        $sql = "DELETE FROM $tab_diagrammes WHERE idtournoi = :idt AND etui = :n";
        $sth = $dbh->prepare($sql);
        $sth->execute([':idt' => $idt, ':n' => $n]);

        $r = $sth->rowCount(); // 0 si inconnu, >= 1 si supprimé

        $dbh->commit();
    } catch (PDOException $e) {
        $dbh->rollBack();
        $r = 0;
        // error_log($e->getMessage()); // décommenter pour le debug
    } finally {
        $dbh = null;
    }

    return $r;
}
function getPointsHonneursMoyens($idt) {
	global $tab_diagrammes;
	$pts = array( 'id'=>$idt, 'nord'=>'0', 'est'=>'0', 'sud'=>'0', 'ouest'=>'0', 'ns'=>'0', 'eo'=>'0' );
	$dbh = connectBDD();
	
	$res = securityQuery($dbh, "SELECT AVG(h1) FROM $tab_diagrammes where idtournoi = ?;", [$idt]);
	$pn = $res->fetchColumn();
	$pts['nord'] = sprintf( "%.2f", $pn );
	
	$res = securityQuery($dbh, "SELECT AVG(h2) FROM $tab_diagrammes where idtournoi = ?;", [$idt]);
	$pe = $res->fetchColumn();
	$pts['est'] = sprintf( "%.2f", $pe );
	
	$res = securityQuery($dbh, "SELECT AVG(h3) FROM $tab_diagrammes where idtournoi = ?;", [$idt]);
	$ps = $res->fetchColumn();
	$pts['sud'] = sprintf( "%.2f", $ps );
	
	$res = securityQuery($dbh, "SELECT AVG(h4) FROM $tab_diagrammes where idtournoi = ?;", [$idt]);
	$po = $res->fetchColumn();
	$pts['ouest'] = sprintf( "%.2f", $po );
	
	$pts['ns'] = sprintf( "%.2f", $pn+$ps );
	$pts['eo'] = sprintf( "%.2f", $pe+$po );
	
	$dbh = null;
	return $pts;
}
function getdiagrammes($idt) {
	global $tab_diagrammes;
	
	$dbh = connectBDD();
    $sql = "SELECT etui, dealt
            FROM $tab_diagrammes
            WHERE idtournoi = :idt
            ORDER BY etui";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([':idt' => $idt]);

    $diags = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $diags[] = [
            'etui' => $row['etui'],
            'deal' => $row['dealt']
        ];
    }
	$dbh = null;
	return json_encode( $diags );
};

// fonctions utilisant les tables des paires NS et EO
function _maxNumPaireNS( $dbh, $idt ) {		// return max(numtable), 0 sinon
	global $tab_pairesNS;
    $sql = "SELECT MAX(num) FROM $tab_pairesNS WHERE idtournoi = :idt";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([ ':idt' => $idt ]);
    $max = $stmt->fetchColumn();
    return $max !== false && $max !== null ? (int)$max : 0;
};
function _maxNumPaireEO( $dbh, $idt ) {
	global $tab_pairesEO;
    $sql = "SELECT MAX(num) FROM $tab_pairesEO WHERE idtournoi = :idt";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([ ':idt' => $idt ]);
    $max = $stmt->fetchColumn();
    return $max !== false && $max !== null ? (int)$max : 0;
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
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = ? and num = ?";
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	if ( $res->fetchColumn() == 1 ) {
		$sth = securityQuery($dbh, "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = ? and num = ?", [$idt, $numpaire]);
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
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = ? and num = ?";
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	if ( $res->fetchColumn() == 1 ) {
		$sth = securityQuery($dbh, "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = ? and num = ?", [$idt, $numpaire]);
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
	$sql = "select count(*) from $tab_pairesNS where idtournoi =? and num = ?;";
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesNS where idtournoi =? and num = ?;";
		$sqlBound = [$idt, $numpaire];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$indice = $row['id'];
		$sql = "DELETE from $tab_pairesNS where id =?;";
		$sqlBound = [$indice];
		$res = securityQuery($dbh, $sql, $sqlBound);
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
	$sql = "select count(*) from $tab_pairesEO where idtournoi =? and num = ?;";
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sql = "select id from $tab_pairesEO where idtournoi =? and num = ?;";
		$sqlBound = [$idt, $numpaire];
		$sth = securityQuery($dbh, $sql, $sqlBound);
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$indice = $row['id'];
		$sql = "DELETE from $tab_pairesEO where id =?;";
		$sqlBound = [$indice];
		$res = securityQuery($dbh, $sql, $sqlBound);
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
			$res = securityQuery($dbh, "SELECT count(*) FROM $tab_pairesNS where idtournoi = ? and num = ?", [$idt, $i]);
			$n = $res->fetchColumn();
			if ( $n == 0 ) { $nb = -1; break; }
			// test paire complète
			$sth = securityQuery($dbh, "SELECT * FROM $tab_pairesNS where idtournoi = ? and num = ?", [$idt, $i]);
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

	$nb = _maxNumPaireEO( $dbh, $idt );
	if ( $nb > 0 ) {
		for ( $i = 1; $i <= $nb; $i++ ) {
			// test paire existe ?
			$res = securityQuery($dbh, "SELECT count(*) FROM $tab_pairesEO where idtournoi = ? and num = ?", [$idt, $i]);
			$n = $res->fetchColumn();
			if ( $n == 0 ) { $nb = -1; break; }
			// test paire complète
			$sth = securityQuery($dbh, "SELECT * FROM $tab_pairesEO where idtournoi = ? and num = ?", [$idt, $i]);
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ( $row[ 'idj2' ] == null ) { $nb = -1; break; }
			if ( $row[ 'idj4' ] == null ) { $nb = -1; break; }
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

function _set_joueurNord( $dbh, $idt, $paire, $idj ) {
	global $tab_pairesNS;
	try {
		$dbh->beginTransaction();

		// recherche si ligne NS déjà utilisée (verrouillée pour éviter la concurrence)
		$sql = "SELECT count(*) FROM $tab_pairesNS WHERE idtournoi = :idt AND num = :paire FOR UPDATE";
		$sth = $dbh->prepare( $sql );
		$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
		$nbl = (int) $sth->fetchColumn();

		if ( $nbl == 0 ) { // pas encore utilisé
			if ( $idj > 0 ) {
				$sql = "INSERT INTO $tab_pairesNS ( idtournoi, num, idj1 ) VALUES ( :idt, :paire, :idj )";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}
		elseif ( $nbl == 1 ) { // déjà utilisé par sud
			if ( $idj == 0 ) {
				$sql = "UPDATE $tab_pairesNS SET idj1 = NULL WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
			} else {
				$sql = "UPDATE $tab_pairesNS SET idj1 = :idj WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}

		$dbh->commit();
	}
	catch ( PDOException $e ) {
		$dbh->rollBack();
		throw $e;
	}
	return $idj;
};
function _set_joueurSud( $dbh, $idt, $paire, $idj ) {
	global $tab_pairesNS;
	try {
		$dbh->beginTransaction();

		$sql = "SELECT count(*) FROM $tab_pairesNS WHERE idtournoi = :idt AND num = :paire FOR UPDATE";
		$sth = $dbh->prepare( $sql );
		$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
		$nbl = (int) $sth->fetchColumn();

		if ( $nbl == 0 ) { // pas encore utilisé
			if ( $idj > 0 ) {
				$sql = "INSERT INTO $tab_pairesNS ( idtournoi, num, idj3 ) VALUES ( :idt, :paire, :idj )";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}
		elseif ( $nbl == 1 ) { // déjà utilisé par nord
			if ( $idj == 0 ) {
				$sql = "UPDATE $tab_pairesNS SET idj3 = NULL WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
			} else {
				$sql = "UPDATE $tab_pairesNS SET idj3 = :idj WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}

		$dbh->commit();
	}
	catch ( PDOException $e ) {
		$dbh->rollBack();
		throw $e;
	}
	return $idj;
};
function _set_joueurEst( $dbh, $idt, $paire, $idj ) {
	global $tab_pairesEO;
	try {
		$dbh->beginTransaction();

		$sql = "SELECT count(*) FROM $tab_pairesEO WHERE idtournoi = :idt AND num = :paire FOR UPDATE";
		$sth = $dbh->prepare( $sql );
		$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
		$nbl = (int) $sth->fetchColumn();

		if ( $nbl == 0 ) { // pas encore utilisé
			if ( $idj > 0 ) {
				$sql = "INSERT INTO $tab_pairesEO ( idtournoi, num, idj2 ) VALUES ( :idt, :paire, :idj )";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}
		elseif ( $nbl == 1 ) { // déjà utilisé par ouest
			if ( $idj == 0 ) {
				$sql = "UPDATE $tab_pairesEO SET idj2 = NULL WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
			} else {
				$sql = "UPDATE $tab_pairesEO SET idj2 = :idj WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}

		$dbh->commit();
	}
	catch ( PDOException $e ) {
		$dbh->rollBack();
		throw $e;
	}
	return $idj;
};
function _set_joueurOuest( $dbh, $idt, $paire, $idj ) {
	global $tab_pairesEO;
	try {
		$dbh->beginTransaction();

		$sql = "SELECT count(*) FROM $tab_pairesEO WHERE idtournoi = :idt AND num = :paire FOR UPDATE";
		$sth = $dbh->prepare( $sql );
		$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
		$nbl = (int) $sth->fetchColumn();

		if ( $nbl == 0 ) { // pas encore utilisé
			if ( $idj > 0 ) {
				$sql = "INSERT INTO $tab_pairesEO ( idtournoi, num, idj4 ) VALUES ( :idt, :paire, :idj )";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}
		elseif ( $nbl == 1 ) { // déjà utilisé par est
			if ( $idj == 0 ) {
				$sql = "UPDATE $tab_pairesEO SET idj4 = NULL WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire ) );
			} else {
				$sql = "UPDATE $tab_pairesEO SET idj4 = :idj WHERE idtournoi = :idt AND num = :paire";
				$sth = $dbh->prepare( $sql );
				$sth->execute( array( ':idt' => $idt, ':paire' => $paire, ':idj' => $idj ) );
			}
		}

		$dbh->commit();
	}
	catch ( PDOException $e ) {
		$dbh->rollBack();
		throw $e;
	}
	return $idj;
};

function set_joueurNord( $idt, $paire, $idj ) {
	$dbh = connectBDD();
	$idj = _set_joueurNord( $dbh, $idt, $paire, $idj );
	$dbh = null;
	return $idj;
};
function set_joueurSud( $idt, $paire, $idj ) {
	$dbh = connectBDD();
	$idj = _set_joueurSud( $dbh, $idt, $paire, $idj );
	$dbh = null;
	return $idj;
};
function set_joueurEst( $idt, $paire, $idj ) {
	$dbh = connectBDD();
	$idj =_set_joueurEst( $dbh, $idt, $paire, $idj );
	$dbh = null;
	return $idj;
};
function set_joueurOuest( $idt, $paire, $idj ) {
	$dbh = connectBDD();
	$idj =_set_joueurOuest( $dbh, $idt, $paire, $idj );
	$dbh = null;
	return $idj;
};

function efface_joueur( $idt, $idj ) {	// id tournoi, id joueur
	global $tab_pairesNS, $tab_pairesEO;
	$oldposition = array( 'paire' => 0, 'position' => 0 ); 	// ( numéro paire, position )

	if ( $idj <= 0 ) {
		return $oldposition;
	}

	$dbh = connectBDD();
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// définition des 4 positions à vérifier : table, colonne du joueur, colonne du partenaire
	$positions = array(
		1 => array( 'table' => $tab_pairesNS, 'col_j' => 'idj1', 'col_partenaire' => 'idj3' ), // Nord
		2 => array( 'table' => $tab_pairesEO, 'col_j' => 'idj2', 'col_partenaire' => 'idj4' ), // Est
		3 => array( 'table' => $tab_pairesNS, 'col_j' => 'idj3', 'col_partenaire' => 'idj1' ), // Sud
		4 => array( 'table' => $tab_pairesEO, 'col_j' => 'idj4', 'col_partenaire' => 'idj2' ), // Ouest
	);

	$nn = 0; // compteur de position trouvée

	try {
		$dbh->beginTransaction();

		foreach ( $positions as $i => $p ) {
			$table = $p['table'];
			$col_j = $p['col_j'];
			$col_partenaire = $p['col_partenaire'];

			// recherche si joueur déjà placé à cette position
			$sql1 = "SELECT count(*) FROM $table WHERE idtournoi = :idt AND $col_j = :idj";
			$sth1 = $dbh->prepare( $sql1 );
			$sth1->execute( array( ':idt' => $idt, ':idj' => $idj ) );
			$nbl = (int) $sth1->fetchColumn();

			if ( $nbl > 1 ) {	// bug: le même joueur référencé plusieurs fois dans une même position
				throw new RuntimeException( "Joueur référencé plusieurs fois (position $i) pour idj=$idj" );
			}

			if ( $nbl === 1 ) {
				$nn++;

				$sql2 = "SELECT id, num, $col_partenaire AS idpartenaire FROM $table WHERE idtournoi = :idt AND $col_j = :idj";
				$sth2 = $dbh->prepare( $sql2 );
				$sth2->execute( array( ':idt' => $idt, ':idj' => $idj ) );
				$row = $sth2->fetch( PDO::FETCH_ASSOC );

				if ( $row === false ) {
					throw new RuntimeException( "Incohérence : ligne attendue introuvable (position $i) pour idj=$idj" );
				}

				$idpaire = $row['id'];

				// mémorise ancienne position
				$oldposition['paire'] = $row['num'];
				$oldposition['position'] = $i;

				$idpartenaire = $row['idpartenaire'];

				if ( $idpartenaire > 0 ) {
					// effacement joueur dans la paire (le partenaire reste)
					$sql4 = "UPDATE $table SET $col_j = null WHERE idtournoi = :idt AND id = :idpaire";
					$sth4 = $dbh->prepare( $sql4 );
					$sth4->execute( array( ':idt' => $idt, ':idpaire' => $idpaire ) );
				} else {
					// suppression paire (plus personne dedans)
					$sql3 = "DELETE FROM $table WHERE idtournoi = :idt AND id = :idpaire";
					$sth3 = $dbh->prepare( $sql3 );
					$sth3->execute( array( ':idt' => $idt, ':idpaire' => $idpaire ) );
				}
			}
		}

		if ( $nn > 1 ) {	// bug non critique: le même joueur référencé plusieurs fois dans différentes positions
			// on ne bloque pas, mais on trace l'anomalie
			error_log( "Anomalie : joueur idj=$idj référencé $nn fois dans le tournoi idt=$idt" );
		}

		$dbh->commit();

	} catch ( Exception $e ) {
		if ( $dbh->inTransaction() ) {
			$dbh->rollBack();
		}
		throw $e; // propage à l'appelant, qui décide comment réagir (log, réponse JSON, etc.)
	} finally {
		$dbh = null;
	}

	return $oldposition;
}
function recherche_joueur( $idt, $idj ) {
	global $tab_pairesNS, $tab_pairesEO;
	global $pos_indefini, $pos_Nord, $pos_Est, $pos_Sud, $pos_Ouest, $maxtableslibres;
	$dbh = connectBDD();

	// recherche si joueur déjà placé
	$emplacement = [ 'table'=>0, 'place'=>$pos_indefini, 'nplaces'=>0 ];
	for ( $i = 1; $i < 5; $i++ ) {
		if ( $i == $pos_Nord ) { // Nord
			$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = ? and idj1 = ?;";
			$sqlBound = [$idt, $idj];
			$sql2 = "SELECT id, num  FROM $tab_pairesNS where idtournoi = ? and idj1 = ?;";
			$sql2Bound = [$idt, $idj];				
		} elseif ( $i == $pos_Est ) { // Est
			$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = ? and idj2 = ?;";
			$sqlBound = [$idt, $idj];
			$sql2 = "SELECT id, num  FROM $tab_pairesEO where idtournoi = ? and idj2 = ?;";
			$sql2Bound = [$idt, $idj];
		} elseif ( $i == $pos_Sud ) { // Sud
			$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = ? and idj3 = ?;";
			$sqlBound = [$idt, $idj];
			$sql2 = "SELECT id, num  FROM $tab_pairesNS where idtournoi = ? and idj3 = ?;";
			$sql2Bound = [$idt, $idj];
		} elseif ( $i == $pos_Ouest ) { // Ouest
			$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = ? and idj4 = ?;";
			$sqlBound = [$idt, $idj];
			$sql2 = "SELECT id, num  FROM $tab_pairesEO where idtournoi = ? and idj4 = ?;";
			$sql2Bound = [$idt, $idj];
		};
		if ( $res = securityQuery($dbh, $sql, $sqlBound) ) {
			$nb = $res->fetchColumn();
			if ( $nb > 0 ) {	// joueur trouvé
				// numéro de table
				$sth = securityQuery($dbh, $sql2, $sql2Bound);
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
	$sql = "SELECT count(*) FROM $tab_pairesNS where idtournoi = ? and num = ?";
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sth = securityQuery($dbh, "SELECT idj1, idj3 FROM $tab_pairesNS where idtournoi = ? and num = ?", [$idt, $numpaire]);
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ( $row[ 'idj1' ] != null ) $nbj++;
		if ( $row[ 'idj3' ] != null ) $nbj++;
	}
		
	$sql = "SELECT count(*) FROM $tab_pairesEO where idtournoi = ? and num = ?";
		
	$sqlBound = [$idt, $numpaire];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nb = $res->fetchColumn();
	if ( $nb > 0 ) {
		$sth = securityQuery($dbh, "SELECT idj2, idj4 FROM $tab_pairesEO where idtournoi = ? and num = ?", [$idt, $numpaire]);
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
	$sql = "SELECT count(*) FROM $tab_joueurs where id = ?;";
	$sqlBound = [$id];
	if ( $res = securityQuery($dbh, $sql, $sqlBound) ) {
		$nbl = $res->fetchColumn();
		if ( $nbl == 1 ) {
			$sth = securityQuery($dbh, "SELECT * FROM $tab_joueurs where id = ?;", [$id]);
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
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO, $st_closed;
	global $parametres;
	$nbm = $parametres['nbmperf'];	// $nbm nombre de mois précédent la date du jour
	$min = $parametres['minperf'];	// $min nombre minimum de tournois joués
	$nbj = $parametres['nbjperf'];	// taille du tableau
	$dbh = connectBDD();
	
	// recherche du 1er tournoi avec une date >= date du jour - $nbm mois
	$sql = "SELECT count(*) FROM $tab_tournois WHERE tournoi >=(DATE_SUB(curdate(), INTERVAL ? MONTH)) order by id asc;";
	$sqlBound = [$nbm];
	$res = securityQuery($dbh, $sql, $sqlBound);
	$nbl = $res->fetchColumn();
	//print "<p>$sql $nbl</p>";
	if ( $nbl > 0 ) {
		$i=0;
		$sql = "SELECT idj, nbfois, perf, depuis FROM (
		SELECT idj, COUNT(*) AS nbfois, AVG(noteg) AS perf, MIN(tournoi) AS depuis FROM (
			(SELECT idj1 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = ? 
			AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH)) ) UNION 
			
			(SELECT idj2 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = ? 
			AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH)) ) UNION 
			
			(SELECT idj3 AS idj, tournoi, noteg FROM $tab_pairesNS
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesNS.idtournoi 
			WHERE etat = ? 
			AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH)) ) UNION 
			
			(SELECT idj4 AS idj, tournoi, noteg FROM $tab_pairesEO
			INNER JOIN $tab_tournois ON $tab_tournois.id = $tab_pairesEO.idtournoi 
			WHERE etat = ? 
			AND tournoi >= (DATE_SUB(curdate(), INTERVAL ? MONTH)) )
			
			) T1 group by idj
		) T2 where nbfois > ? order by perf desc;";
		$sqlBound = [$st_closed, $nbm, $st_closed, $nbm, $st_closed, $nbm, $st_closed, $nbm, $min];
		//print "<p>$sql</p>";

		print "<p>Performance moyenne des joueurs sur les $nbm derniers mois avec au moins $min tournois joués</p>";
		print '<table border="0" style="width:100%; max-width: 350px; margin:auto;"><tbody>';
		print "<tr><td class='xNum3' style='width:10%;'>Rg</td>";
		print "<td class='xNum3'>Joueur</td>";
		print "<td class='xNum3' style='width:10%;'>N</td>";
		print "<td class='xNum3' style='width:15%;'>%</td></tr>";
		foreach  (securityQuery($dbh, $sql, $sqlBound) as $row) {
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
	else $selordre = ['nom' => 'nom', 'nbfois' => 'nbfois'][$ordre] ?? 'nom';
	
	if ( strlen($filtre) > 0) {
		$likename = $filtre . "%";
		$filtrage = "and nom like ?";
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
			WHERE numero >= ? $groupe $filtrage ORDER BY $selordre;";
	
	$sqlBound = array_merge([$min_noclub], strlen($filtre) > 0 ? [$likename] : []);
	
	//$str = "<p>$sql</p>";
	$str = '<table border="1" style="margin:auto;"><tbody>';
	$str .= "<tr>";
	$str .= "<th class='xTxt1 selordre' id='ordre_nom'>Joueur</th>";
	$str .= "<th class='xTxt1 selordre' id='ordre_tournoi'>dernier</br>tournoi</th>";
	$str .= "<th class='xTxt1 selordre' id='ordre_nbfois'>N</th>";
	$str .= "</tr>";
	foreach  (securityQuery($dbh, $sql, $sqlBound) as $row) {
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
function htmlAnnuaire($avecEmail = false) {
	global $tab_joueurs, $min_noclub;
	$groupe = "and datesupp = 0";	// uniquement les actifs
	$dbh = connectBDD();
	
	$champEmail = $avecEmail ? ', email' : '';
	$sql = "SELECT id, prenom, nom, telephone$champEmail
			FROM $tab_joueurs
			WHERE numero >= ? $groupe ORDER BY nom;";
	
	$sqlBound = [$min_noclub];
	
	$str = "<table border='1' style='margin:auto;'><tbody>";
	$str .= "<tr>";
	$str .= "<th class='xTxt1'>Joueur</th>";
	$str .= "<th class='xTxt1'>Téléphone</th>";
	$str .= "</tr>";
	foreach  (securityQuery($dbh, $sql, $sqlBound) as $row) {
		$str .= "<tr>";
		$str .= "<td class='xTxt1'>" . $row['prenom'] ." ". $row['nom'] . "</td>";
		$emailAttr = $avecEmail ? ' data-email="' . htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
		$str .= "<td class='xTxt1'" . $emailAttr . ">" . $row['telephone'] . "</td>";
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
		$sql = "UPDATE $tab_connexions SET stconnexion=?, numEO=0, numdonne=0, cpt=0, rdy=0, eocpt=0, eonumNS=0, eodonne=0 where id=?;";
		$sqlBound = [$cnx_ko, $i];
		$res = securityQuery($dbh, $sql, $sqlBound);
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
	//print '<tr><td  colspan="5" class="xTitre">Avancement tournoi</td></tr>';
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
	
	$sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$k]);
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
	
	$sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$k]);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$table  = $row[ 'eonumNS' ];
	$numdonne = $row[ 'eodonne' ];
	$cpt = $row[ 'eocpt' ];
	
	$dbh = null;
	$result = array( 'numdonne' => $numdonne, 'cpt' => $cpt, 'table' => $table );
	return $result;
}
function renderCnxFin($table) {
    echo '<script>bridgetteFinish('.json_encode((int)($_GET['idtournoi'] ?? 0)).','.
        json_encode((int)$table).');</script>';
}
function setCnxFin($table) {
    securityCsrfRequire();
    global $tab_connexions, $tab_pairesNS, $cnx_fin;
    // Honor the tournament's Non/M/P policy, including a borrowed member's phone.
    $id = filter_var($_GET['idtournoi'] ?? null, FILTER_VALIDATE_INT);
    $table = filter_var($table, FILTER_VALIDATE_INT);
    if (!$id || !$table) return;
    $t = readTournoi($id);
    if (!$t) return;
    // Marking the connection finished remains valid after the final score ends play.
    securityTournament($id, $table, null, true);
    $db = connectBDD();
    securityQuery($db, "UPDATE $tab_connexions SET stconnexion=? WHERE id=? AND rdy>?", [$cnx_fin, $table, $t['npositions']]);
}
function getMinTour( $n ) {
	global $tab_connexions;
	$dbh = connectBDD();
	$res = securityQuery($dbh, "SELECT MIN(rdy) FROM $tab_connexions where id<=?;", [$n]);
	$nb  = $res->fetchColumn();
	$dbh = null;
	return $nb;
}
function incrementCompteurRelaisEO( $dd, $ns, $eo, $inc, $paquet ) {
	global $tab_connexions, $cnx_ok;
	$dbh = connectBDD();
	// Incrémentation du compteur de donnes jouées/enregistrées par la paire ns
	$sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$ns]);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$cpt = $row['cpt'];
	$cpt = $cpt + $inc;
	$dd  = $dd  + $inc;

	// test changement de position / tour
	$pos = $row['rdy'];
	$change = $cpt % $paquet;
	if ( $change == 0 ) $pos++;

	$sql = "UPDATE $tab_connexions SET stconnexion=?, numEO = ?, numdonne = ?, cpt =?, rdy =? where id = ?;";
	$sqlBound = [$cnx_ok, $eo, $dd, $cpt, $pos, $ns];
	$res = securityQuery($dbh, $sql, $sqlBound);			
	$dbh = null;
}
function incrementCompteurRelaisNS( $dd, $ns, $eo, $paquet ) {
	global $tab_connexions, $cnx_ok;
	$dbh = connectBDD();
	// Incrémentation du compteur de donnes jouées/enregistrées par la paire ns
	$sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$ns]);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$cpt = $row['cpt'];
	$cpt++;

	// test changement de position / tour
	$pos = $row['rdy'];
	$change = $cpt % $paquet;
	if ( $change == 0 ) $pos++;

	$sql = "UPDATE $tab_connexions SET stconnexion=?, numEO = ?, numdonne = ?, cpt =?, rdy =? where id = ?;";
	$sqlBound = [$cnx_ok, $eo, $dd, $cpt, $pos, $ns];
	$res = securityQuery($dbh, $sql, $sqlBound);			
	$dbh = null;
}
function incrementCompteurEO( $eo, $paquet ) {
	global $tab_connexions;
	$dbh = connectBDD();
	// Incrémentation du compteur de la paire eo en relais sans donne (guéridon)
	$sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$eo]);
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$eocpt = $row['eocpt'];
	$eocpt +=$paquet;

	$sql = "UPDATE $tab_connexions SET eocpt =? where id = ?;";
	$sqlBound = [$eocpt, $eo];
	$res = securityQuery($dbh, $sql, $sqlBound);			
	$dbh = null;
	return $eocpt;
}

require_once __DIR__.'/security_routes.php';
