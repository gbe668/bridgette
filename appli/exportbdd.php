<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}
error_reporting(E_ALL); // Activer le rapport d'erreurs PHP

// https://stackoverflow.com/questions/13659534/backup-mysql-tables-with-php
function backup_tables($host, $user, $pass, $name) {
	global $tab_joueurs, $tab_directeurs, $tab_tournois, $tab_donnes, $tab_pairesNS, $tab_pairesEO, $tab_diagrammes, $tab_connexions;
	$tables = "$tab_joueurs $tab_directeurs $tab_tournois $tab_donnes $tab_pairesNS $tab_pairesEO $tab_diagrammes $tab_connexions";
	
	$dbh = connectBDD();
	$sql = "SET NAMES `utf8` COLLATE `utf8_general_ci`";
	$sth = $dbh->query( $sql );

	$data = "\n/*---------------------------------------------------------------".
		"\n  SQL DB BACKUP ".date("d.m.Y H:i")." ".
		"\n  HOST: {$host}".
		"\n  DATABASE: {$name}".
		"\n  TABLES: {$tables}".
		"\n  ---------------------------------------------------------------*/\n";
	$tables = is_array($tables) ? $tables : explode(' ',$tables);
	foreach($tables as $table) {
		$data.= "\n/*---------------------------------------------------------------".
			"\n  TABLE: `{$table}`".
            "\n  ---------------------------------------------------------------*/\n";           
		$data.= "DROP TABLE IF EXISTS `{$table}`;\n";
		
		$sql = "SHOW CREATE TABLE `{$table}`;";
		$sth = $dbh->query( $sql );
		$row = $sth->fetch(PDO::FETCH_NUM );
		$data.= $row[1].";\n";

		$sth = $dbh->query( "SELECT count(*) FROM `$table`;" );
		$nrows = $sth->fetchColumn();

		//echo "<p>$table: $nrows</p>";
		if( $nrows > 0 ){
			$sth = $dbh->query( "SELECT * FROM `$table`;" );
			$vals = Array();
			$z=0;
			for($i=0; $i<$nrows; $i++) {
				$row = $sth->fetch(PDO::FETCH_NUM );
				$k = count($row);
				$vals[$z]="(";
				for($j=0; $j<$k; $j++) {
					if (isset($row[$j])) {
						$vals[$z].= $dbh->quote( $row[$j] );
					}
					else {
						$vals[$z].= "NULL";
					}
					if ($j<(count($row)-1)){ $vals[$z].= ","; }
				}
				$vals[$z].= ")"; $z++;
			}
			$data .= "INSERT INTO `{$table}` VALUES ";      
			$data .= implode(";\nINSERT INTO `{$table}` VALUES ", $vals).";\n";
		}
	}
	$dbh = null;
	return $data;
}

// get backup
$mybackup = backup_tables($db_host,$db_user,$db_password,$db_name);
//echo $mybackup;

// save to file
$tt = date('Y_m_d');
$backup_file = $prefix.$tt.'.sql';
$handle = fopen($dir_configs.$backup_file,'w+');
fwrite($handle,$mybackup);
fclose($handle);

echo json_encode( array( 'ok'=>1, 'file'=>$backup_file, 'data'=>$mybackup ) );
?>
