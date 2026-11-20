<?php
// Operator-only tool: never callable through HTTP, including php -S.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Accès refusé.'); }
require_once __DIR__.'/club_prefix.php';
$options = getopt('', ['host:', 'prefix:', 'command:', 'confirm:']);
$host = $options['host'] ?? '';
$prefix = $options['prefix'] ?? null;
$cmd = $options['command'] ?? '';
if (!preg_match('/\A[A-Za-z0-9.-]+\z/D', $host) || !is_string($prefix) ||
    !bridgetteValidClubPrefix($prefix) ||
    !in_array($cmd, ['install', 'patch', 'droptables', 'gc'], true)) {
    exit("Usage: php maintenance.php --host=HOST --prefix=ABC_ --command=install|patch|droptables|gc [--confirm=ABC_]\n");
}
if ($cmd === 'droptables' && ($options['confirm'] ?? null) !== $prefix) exit("Confirmer le préfixe exact avec --confirm.\n");
chdir(__DIR__);
define('BRIDGETTE_MAINTENANCE', true);
$_SERVER['SERVER_NAME'] = $host;
$_SERVER['HTTP_USER_AGENT'] = 'maintenance';
$_GET['token'] = base64_encode(json_encode(['prefix' => $prefix, 'timex' => time()]));
require 'configuration.php';
$dbh = new PDO($dsn, $db_user, $db_password);
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
			$dbh->query( $sql_events );
			echo '<p>----> peuplement des tables si vides</p>';
			echo '<p>Comptes responsables conservés. Pour créer un administrateur, utiliser l’opération dédiée du superviseur.</p>';
			init_tab_joueurs($dbh);
			init_tab_connexions($dbh);
			echo '<p>----> terminé.</p>';
			break;
		}
        case 'patch':
            writelogerrors('Patch requested by admin');
            // Charger le script d'installation en local (vérifier existence)
            $patchFile = __DIR__ . '/patch.php';
            if (!is_file($patchFile)) {
                writelogerrors('patch.php missing');
                exit('Fichier patch absent.');
            }
            require $patchFile;
            // Les scripts SQL doivent utiliser PDO et être sécurisés
            try {
                $dbh->beginTransaction();
                if (function_exists('executepatch'))	executepatch($dbh);
				else echo '<p>Fonction executepatch absente.</p>';
                $dbh->commit();
                echo 'Installation terminée.';
            } catch (PDOException $e) {
                $dbh->rollBack();
                writelogerrors('Patch error: ' . $e->getMessage());
                exit('Erreur durant l\'exécution du patch.');
            }
            break;
		case "droptables": {
			writelogerrors( "Suppression des tables avant reinstallation ..." );
			echo "<p><b>Suppression des tables avant reinstallation ...</b></p>";
			require( "installtablesbdd.php" );
			droptablesbdd($dbh);
			echo '<p>----> terminé.</p>';
			break;
		}
		case "gc": {
			// garbage collector table sessions
			$session->gc($maxjoursgarbage * 86400);
			echo '<p>garbage collector terminé.</p>';
			break;
		}
		default: {
			$errmsg = "<b>Erreur 07: Commande inconnue !</b> $titre";
			writelogerrors( $errmsg );
			session_unset();
			session_destroy(); 
			die( $errmsg );
		}
	}
