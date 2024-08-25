<?php
require("configuration.php");
require("bridgette_bdd.php");

// Vérifiez si l'utilisateur est connecté, sinon redirigez-le vers la page de connexion
if( !isAdmin() ){
	header("Location: logadmin.php");
	exit(); 
}

// nombre max de roles
$maxadministrateurs = 2;
$maxdirecteurs = 10;
$maxlignes = $maxadministrateurs + $maxdirecteurs;
function displayDirecteurs() {
	global $maxlignes;
	print '<table style="width:90%;max-width:350px;margin:auto;"><tbody>';
	print '<tr><td>Pseudo</td><td>Droit</td></tr>';

	for  ($i = 0; $i < $maxlignes; $i++) {
		$nr = "nr_" . $i;
		$ndnum = "num_" . $i;
		$ndnom = "nom_" . $i;
		$ndok = "ok_" . $i;
		print '<tr id="' . $nr . '" class="xtrsel xtr_invisible">';
		print '<td class="xTxt2" id="' . $ndnum . '">&nbsp;</td>';
		print '<td class="xTxt2" id="' . $ndnom . '">&nbsp;</td>';
		print '</tr>';
		};
	print "</tbody></table>";
};

$nbjoueurs = 0;
$nbtournois = 0;
$nbdonnes = 0;
$nbdiagrammes = 0;
function countElements() {
	global $tab_joueurs, $nbjoueurs, $min_noclub, $tab_tournois, $nbtournois, $tab_donnes, $nbdonnes, $tab_diagrammes, $nbdiagrammes;
	$dbh = connectBDD();
	
	$sql = "SELECT count(*) FROM $tab_joueurs where numero >= '$min_noclub';";
	$sth = $dbh->query( $sql );
	$nbjoueurs = $sth->fetchColumn();
	
	$sql = "SELECT count(*) FROM $tab_tournois;";
	$sth = $dbh->query( $sql );
	$nbtournois = $sth->fetchColumn();
	
	$sql = "SELECT count(*) FROM $tab_donnes;";
	$sth = $dbh->query( $sql );
	$nbdonnes = $sth->fetchColumn();
	
	$sql = "SELECT count(*) FROM $tab_diagrammes;";
	$sth = $dbh->query( $sql );
	$nbdiagrammes = $sth->fetchColumn();
	
	$dbh = null;
}
countElements();

$nomtables = Array();
$dimtables = Array();
function display_sizedb() {
	global $nomtables, $dimtables;
	print "<table border='1' style='width:90%; max-width: 350px; margin:auto;'>";
	print '<tbody>';
	print "<tr><th>Table</th><th>Taille (KB)</th></tr>";
	for ( $j = 0; $j < sizeof($nomtables); $j++ ) {
		print "<tr><td>$nomtables[$j]</td><td>$dimtables[$j]</td></tr>";
	}
	print '</tbody></table>';	
}

function getversionsql() {
	$dbh = connectBDD();
	$sql = "SELECT VERSION();";
	$sth = $dbh->query( $sql );
	$v = $sth->fetchColumn();
	$dbh = null;
	return $v;
}

function compute_sizedb() {
	global $nomtables, $dimtables, $db_name, $prefix;
	$sql_sizedb = "SELECT table_name AS 'Table',
		ROUND(((data_length + index_length) / 1024), 3) AS 'Size' FROM information_schema.TABLES
		WHERE table_schema ='$db_name' AND table_name like '$prefix%'
		ORDER BY (data_length + index_length) DESC;";
	$i =0;
	$totalsize = 0;
	$dbh = connectBDD();
	$results = $dbh->query( $sql_sizedb );
	while ( $ligne = $results->fetch(PDO::FETCH_ASSOC) ) {
		$nomtables[ $i ] = $ligne[ 'Table' ];
		$dimtables[ $i ] = $ligne[ 'Size' ];
		$totalsize += $dimtables[ $i ];
		$i++;
	}
	$dbh = null;
	return $totalsize;
}

$firstdatet = 0;
$firstTournoi = 0;
function getDateFirstTournoi() {
	global $tab_tournois, $st_closed, $firstdatet, $firstTournoi;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_tournois where etat = '$st_closed';" );
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$firstdatet = $row[ 'tournoi' ];
		$firstTournoi = strdatet( $firstdatet );
	}
	else
		$firstTournoi = "non trouvé !";
	$dbh = null;
	return $nbl;
};
$oldtournois = 0;	// nb de tournois même année que le plus vieux
function getNbThisYear( $datet ) {
	global $tab_tournois, $st_closed;
	$thisy = substr($datet, 0, 4 );
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed' and tournoi like '$thisy%';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	$dbh = null;
	return $nbl;
};

// import / export BDD
$backup_file = $dir_configs.$prefix.'importbdd.sql';
?>

<!DOCTYPE HTML>
<html>
<head>	
	<title>Admin</title>	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
	<meta name="viewport" content="width=device-width, initial-scale=1">	
	<script src="js/jquery-3.6.0.min.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
var ids = Array();
var pseudos = Array();
var droits  = Array();
var idsel;	// pas de sélection
var maxadministrateurs;
var nbadministrateurs;	// nombre de administrateurs enregistrés
var maxdirecteurs;	// taille max du tableau des directeurs / formateurs
var nbdirecteurs;	// nombre de directeurs / formateurs enregistrés
var maxlignes;

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
var errmaxadm = "Maximum deux administrateurs !";
var errmaxdir = "Nombre maximum de directeurs / formateurs atteint !";
function setloginadmin() {
	if ( nbadministrateurs < maxadministrateurs )
		location.replace( "setloginadmin.php" );
	else
		$("#seldir").html( errmaxadm );
};
function setlogindirecteur() {
	if ( nbdirecteurs < maxdirecteurs )
		location.replace( "setlogindirecteur.php" );
	else
		$("#seldir").html( errmaxdir );
};
function setloginformateur() {
	if ( nbdirecteurs < maxdirecteurs )
		location.replace( "setloginformateur.php" );
	else
		$("#seldir").html( errmaxdir );
};
function reload() {
	location.replace( "admin.php" );
};
function setpassword() {
	var nextstring = "setpassword.php";
	location.replace( nextstring );
};
function lstDirecteurs() {
	$.get( "lstalldirecteurs.php", function(strjson) {
		nblignes = strjson.nbl;
		nbadministrateurs = strjson.nba;
		nbdirecteurs = strjson.nbd;
		if ( nblignes > 0 ) {
			ids = strjson.ids;
			pseudos = strjson.pseudos;
			droits = strjson.droits;
			for ( i = 0; i < nblignes; i++ ) {
				let nr = "#nr_" + i;
				$(nr).removeClass( "xtr_invisible" );
				let ndnum = "#num_" + i;
				let ndnom = "#nom_" + i;
				$(ndnum).text( pseudos[i] );
				$(ndnom).text( droits[i] );
			}
			for ( i = nblignes; i < maxlignes; i++ ) {
				let nr = "#nr_" + i;
				$(nr).addClass( "xtr_invisible" );
			}
			$("#seldir").html( "Cliquez sur une des lignes du tableau");
		}
		else {
			$("#seldir").html( "Pas de résultats disponibles !");
		}
	},"json");
};
function clickSuppression() {
	if ( idsel >= 0 )
		$("#section_confirme_suppression").removeClass( "section_invisible" );
	else
		$("#seldir").html( "Pour supprimer un rôle dans la liste,<br/>commencez par le sélectionner." );
};
function clickAnnulationSuppression() {
	$("#section_confirme_suppression").addClass( "section_invisible" );
}
function clickConfirmeSuppression() {
	$("#seldir").text( "Suppression en cours ..." );
	var dataString = 'deletedirecteur.php?id=' + ids[ idsel ];
	console.log( dataString );
	$.get( dataString, function(strjson) {
		$("#seldir").text( strjson.ok );
		//$("#seldir").text( "Suppression réalisée !" );
		//setTimeout(function() { $("#seldir").html( "&nbsp;" ); }, 1000);
		reload();
	},"text");
};

function suppressionVieuxTournois() {
	$("#section_suppression_tournois").removeClass( "section_invisible" );
};
function confirmeSuppressionVieuxTournois() {
	$("#msgvieuxtournois").html( "Suppression en cours ..." );
	$.get( "purgevieuxtournois.php?", { n:oldtournois }, function(html) {
		$("#msgvieuxtournois").html( html );
		setTimeout(function() { reload(); }, 2000);
	},"html");
}
function annulationSuppressionVieuxTournois() {
	$("#section_suppression_tournois").addClass( "section_invisible" );
}

$(document).ready(function() {
	$("tr.xtrsel").click(function(event) {
		console.log( event.target.id );
		var figs = event.target.id.split('_');
		
		idsel = figs[1];
		if ( droits[idsel] == 'admin' ) {
			if ( nbadministrateurs > 1 ) {
				$("#seldir").text( "Administrateur sélectionné: " + pseudos[idsel] );
			}
			else {
				$("#seldir").text( "Suppression interdite du seul administrateur !" );
				idsel = -1;
			}
		}
		else {
			$("#seldir").text( "Directeur sélectionné: " + pseudos[idsel] );
		}
	});
});
function toggleAffichagePseudos() {
	if ( $("#section_pseudos").hasClass( 'section_invisible' ) )
		$('#section_pseudos').removeClass( 'section_invisible' );
	else
		$('#section_pseudos').addClass( 'section_invisible' );
}
function toggleAffichageBDD() {
	if ( $("#section_bdd").hasClass( 'section_invisible' ) )
		$('#section_bdd').removeClass( 'section_invisible' );
	else
		$('#section_bdd').addClass( 'section_invisible' );
}
function exportbdd() {
	$("#msgbdd").html( "Export en cours ..." );
	$.get( 'exportbdd.php', {}, function(strjson) {
		var hiddenElement = document.createElement('a');
		hiddenElement.href = 'data:text/sql;charset=utf-8,%EF%BB%BF' + strjson.data;
		hiddenElement.target = '_blank';
		hiddenElement.download = strjson.file;
		hiddenElement.click();
		$("#msgbdd").html( "Fichier export: " + strjson.file );
	},"json")
	.done( function() {  } )
	.fail( function( jqxhr,settings,ex ) {
		$("#msgbdd").html('Erreur: '+ ex ); 
	} );
}
function confirmationImportBDD() {
	$("#section_importBDD").addClass( "section_invisible" );
	$("#msgbdd").html( "Import en cours, patientez ..." );
	$.get( 'importbdd.php', {}, function(strjson) {
		$("#msgbdd").html( strjson.comment );
	},"json")
	.done( function() {  } )
	.fail( function( jqxhr,settings,ex ) {
		$("#msgbdd").html('Erreur: '+ ex ); 
	} );
};
function annulationImportBDD() {
	$("#section_importBDD").addClass( "section_invisible" );
}
function importbdd() {
	$("#msgbdd").html( "&nbsp;" );
	$("#section_importBDD").removeClass( "section_invisible" );
}
</script>

<body>	
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>	 	
	<h1>Administration</h1>
	<h3>Votre pseudo : <?php echo $_SESSION["pseudo"]; ?></h3>
	<p><button class="mButton" onclick="setpassword()">Changer mon mot de passe</button></p>

	<p><button class="myButton" onclick="toggleAffichagePseudos()">Gestion des pseudos !</button></p>
	<div id="section_pseudos" class="section_invisible">
	<p><button class="mButton" onclick="setloginadmin()">Ajout administrateur</button></p>
	<p><button class="mButton" onclick="setlogindirecteur()">Ajout directeur tournoi</button></p>
	<p><button class="mButton" onclick="setloginformateur()">Ajout formateur</button></p>
	<p><b>Liste des pseudos enregistrés</b></p>
	<?php
	displayDirecteurs();
	?>
	<p id="seldir">Attente sélection</p>
	
	<p><button class="myButton" onclick="clickSuppression()">Suppression</br>pseudo sélectionné</button></p>
	<div id="section_confirme_suppression" class="section_invisible">
	<p><button class="myButton oktogoon" onClick="clickConfirmeSuppression()">Je confirme</button></p>
	<p><button class="myButton kotogoon" onClick="clickAnnulationSuppression()">Oups ! J'annule</button></p>
	</div>
	<p>&nbsp;</p>
	</div>
	
	<p><button class="myButton" onclick="toggleAffichageBDD()">Base de données</button></p>
	<div id="section_bdd" class="section_invisible">
	<p>Joueurs enregistrés: <?php echo $nbjoueurs; ?></p>
	<p>Tournois enregistrés: <?php echo $nbtournois; ?><p>
	<p>Donnes jouées: <?php echo $nbdonnes; ?></p>
	<p>Diagrammes enregistrés: <?php echo $nbdiagrammes; ?></p>
	<p>Version base de données: <?php echo getversionsql(); ?></p>
	<p>Taille base de données: <?php echo compute_sizedb(); ?> kiloOctets</p>
	<p>Si la taille de la base de données dépasse 90% de <?php echo $maxSizeBDD; ?> kiloOctets,</br>il est peut-être temps de supprimer les anciens tournois.</p>
	<?php
	$n = getDateFirstTournoi();
	if ( $n > 0 ) {
		// il existe au moins un tournoi
		$oldtournois = getNbThisYear( $firstdatet );
		print "<p>Date 1er tournoi: $firstTournoi</br>Nb tournois même année: $oldtournois</p>";
		print "<p><button class='mButton' onclick='suppressionVieuxTournois()'>Suppression $oldtournois anciens tournois</button></p>";
		print "<div id='section_suppression_tournois' class='section_invisible'>";
		print "<p><button class='myButton oktogoon' id='valid4' onClick='confirmeSuppressionVieuxTournois()'>Je confirme</button></p>";
		print "<p><button class='myButton kotogoon' id='valid5' onClick='annulationSuppressionVieuxTournois()'>Oups ! J'annule</button></p>";
		print "<p id='msgvieuxtournois'>&nbsp;</p>";
		print "</div>";
	}
	?>
	</div>
	<p>&nbsp;</p>
	
	<h2>Fonctions d'import export à utiliser en cas de migration.</h2>
	<p><button class='mButton' onclick='exportbdd()'>Export base de données</button></p>
	<p><button class='mButton' onclick='importbdd()'>Import base de données</button></p>
	<div id="section_importBDD" class="section_invisible">
	<p style="color:red"><b>Attention:</b> l'importation de la base de données va remplacer toutes les données existantes dans la base de données actuelle, y compris les identifiants des administrateurs !</p>
	<p>Le fichier d'importation de la base de données doit s'appeler <b><?php echo $prefix.'importbdd.sql' ?></b>. </br>Concrètement, vous devez recopier le fichier <b><?php echo $prefix.'yyyy_mm_jj.sql' ?></b> provenant d'un export réalisé sur l'ancien serveur dans ce fichier <b><?php echo $prefix.'importbdd.sql' ?></b>, 'copier' et non 'renommer' pour conserver une sauvegarde.</p>
	<p>Assurez-vous que ce fichier <b><?php echo $prefix.'importbdd.sql' ?></b> existe dans le répertoire <b><?php echo $dir_configs ?></b> de l'application Bridgette sur le nouveau serveur.</p>
	<p><em>En cas d'erreur, vérifier l'encodage du fichier .sql qui doit être en UTF8</em></p>
	<p>Puis confirmez !</p>
	<p><button class="myButton oktogoon" onClick="confirmationImportBDD()">Je confirme</button></p>
	<p><button class="myButton kotogoon" onClick="annulationImportBDD()">Oups ! J'annule</button></p>
	</div>
	<p id="msgbdd">&nbsp;</p>
	<p>&nbsp;</p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	<p>&nbsp;</p>
	
	<?php
	display_sizedb();
	?>
	</div>
	
	<script>
	maxadministrateurs = parseInt( "<?php echo $maxadministrateurs; ?>" );
	maxdirecteurs = parseInt( "<?php echo $maxdirecteurs; ?>" );
	maxlignes = maxadministrateurs + maxdirecteurs;
	idsel = -1;
	lstDirecteurs();
	oldtournois = parseInt( "<?php echo $oldtournois; ?>" );
	</script>
</body>
</html>