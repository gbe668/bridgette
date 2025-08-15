<?php
require("configuration.php");
require("bridgette_bdd.php");
require("libevents.php");

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
	
	try {
		$sql = "SELECT count(*) FROM $tab_joueurs where numero >= '$min_noclub';";
		$sth = $dbh->query( $sql );
		$nbjoueurs = $sth->fetchColumn();
	}
	catch (Exception $e) {
		$nbjoueurs = 0;
	}
		
	try {
		$sql = "SELECT count(*) FROM $tab_tournois;";
		$sth = $dbh->query( $sql );
		$nbtournois = $sth->fetchColumn();
	}
	catch (Exception $e) {
		$nbtournois = 0;
	}
		
	try {
		$sql = "SELECT count(*) FROM $tab_donnes;";
		$sth = $dbh->query( $sql );
		$nbdonnes = $sth->fetchColumn();
	}
	catch (Exception $e) {
		$nbdonnes = 0;
	}
		
	try {
		$sql = "SELECT count(*) FROM $tab_diagrammes;";
		$sth = $dbh->query( $sql );
		$nbdiagrammes = $sth->fetchColumn();
	}
	catch (Exception $e) {
		$nbdiagrammes = 0;
	}
	
	$dbh = null;
}
countElements();

$nomtables = Array();
$dimtables = Array();
function display_sizedb() {
	global $nomtables, $dimtables;
	print "<table border='1' style='width:90%; max-width: 350px; margin:auto;'>";
	print '<tbody>';
	print "<tr><th>Table BDD</th><th>Taille (KB)</th></tr>";
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
$post_max_size = ini_parse_quantity( ini_get('post_max_size') ); 	// in bytes

// liste événements
$maxevents = 10;
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
		$("#section_confirme_suppression").show();
	else
		$("#seldir").html( "Pour supprimer un rôle dans la liste,<br/>commencez par le sélectionner." );
};
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

function confirmeSuppressionVieuxTournois() {
	$("#msgvieuxtournois").html( "Suppression en cours ..." );
	$.get( "purgevieuxtournois.php?", { n:oldtournois }, function(html) {
		$("#msgvieuxtournois").html( html );
		setTimeout(function() { reload(); }, 2000);
	},"html");
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

// import export BDD
const post_max_size = parseInt( "<?php echo $post_max_size; ?>" );
var sqlfile = null;
var queue;
function videqueue() {
	if ( queue.length > 0 ) {
		let job = queue.shift();
		//console.log( "job", job );
		var queries = job.pieces;
		fetch( "importbddpart.php", {
			method: "POST",
			headers: { "Content-Type": "application/json" }, // On envoie du JSON
			body: JSON.stringify({ queries: queries }) // On envoie un objet avec la clé "queries"
		})
		.then(response => response.json())
		.then(data => {
			$("#msgbdd").html( job.deb +" "+ job.fin+ " : "+data.message );
			$("#complete").html( job.pc );
			if ( data.ok ) videqueue();
		})
		.catch(error => {
			console.error("Erreur :", error);
		});
	}
	else {
		$("#complete").html( "import terminé" );
		$("#msgbdd").html( "<span style='color:red'><b>Retournez page d'accueil pour vérifier l'importation !</b></span>" );
	}
}
function uploadpart() {
	$('#section_confirme').hide();
	if ( sqlfile ) {
		console.log( "uploadpart", sqlfile, "size", sqlfile.size );
		var reader = new FileReader();
		reader.readAsText(sqlfile);
		reader.onload  = event => {
			// supprime commentaires
			var sqlLines = event.target.result
			.replace(/(\/\*[^*]*\*\/)|(\/\/[^*]*)|\n(--[^.].*)/g, '')	// comment
			.replace(/^\s+/gm,'')										// ligne vide
			.split( ";\n" );
			for ( let i=0; i<sqlLines.length; i++ ) sqlLines[i] += ";";

			// avancement exprimé en %, pas minimum de 3% si possible soit 33 pas
			let sizemax = parseInt( post_max_size / 10 );	// pour tenir compte encodage / décodage
			let n = Math.ceil( sqlfile.size/sizemax );		// nombre de morceaux
			let nlpm = Math.ceil( sqlLines.length / n );
			queue = [];
			console.log( "sqlLines", sqlLines.length, "nb morceaux", n, nlpm );
			if ( n == 1 ) {
				// Un seul morceau
				let job = [];
				job.pieces = [];
				job.deb = 1;
				job.fin = sqlLines.length;
				for ( let j=0; j<sqlLines.length; j++ ) {
					job.pieces.push( sqlLines[cpt] );
				}
				job.pc = "100%";
				queue.push( job );
			}
			else {
				// plusieurs morceaux, calcul moyenne nb lignes / morceaux
				$("#complete").html( "0%" );
				let cpt = 0;	// compteur de lignes
				for ( let i=0; i<n; i++ ) {
					// pour chaque paquet
					let job = [];
					job.pieces = [];
					job.deb = cpt+1;
					for ( let j=0; j<nlpm; j++ ) {
						job.pieces.push( sqlLines[cpt] );
						cpt++;
						if ( cpt == sqlLines.length ) break;	// 1ère sécurité
					}
					job.fin = cpt;
					job.pc = Math.ceil( cpt/sqlLines.length *100 )+"%";
					queue.push( job );
					if ( cpt == sqlLines.length ) break;		// 2ème sécurité
				}
			}
			videqueue();
		}
		reader.onerror = event => {
			$("#msgbdd").html('Unable to read ' + sqlfile.fileName);
		}
	}
	else {
		$("#msgbdd").html( "Pas de fichier sélectionné !" );
	}
}
$(document).ready(function() {
	document.getElementById('choicefile').addEventListener('change', (event) => {
		sqlfile = event.target.files[0];
		$("#msgbdd").html( "&nbsp;" );
		console.log( "upload", sqlfile.name );
	});
});
function btn_upload() {
	if ( sqlfile ) {
		$('#section_confirme').show();
	}
	else {
		$('#section_confirme').hide();
		$("#msgbdd").html( "Pas de fichier sélectionné !" );
	}
}
function importbdd() {
	$("#msgbdd").html( "&nbsp;" );
	$("#section_importBDD").show();
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
</script>

<body>	
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>	 	
	<h1>Administration</h1>
	<h3>Votre pseudo : <?php echo $_SESSION["pseudo"]; ?></h3>
	<p><button class="mButton" onclick="setpassword()">Changer mon mot de passe</button></p>

	<p><button class="myButton" onclick="$('#section_pseudos').toggle();">Gestion des pseudos !</button></p>
	<div id="section_pseudos" hidden>
	<p><button class="mButton" onclick="setloginadmin()">Ajout administrateur</button></p>
	<p><button class="mButton" onclick="setlogindirecteur()">Ajout directeur tournoi</button></p>
	<p><button class="mButton" onclick="setloginformateur()">Ajout formateur</button></p>
	<p><b>Liste des pseudos enregistrés</b></p>
	<?php displayDirecteurs(); ?>
	<p id="seldir">Attente sélection</p>
	
	<p><button class="myButton" onclick="clickSuppression()">Suppression</br>pseudo sélectionné</button></p>
	<div id="section_confirme_suppression" hidden>
	<p><button class="myButton oktogoon" onClick="clickConfirmeSuppression()">Je confirme</button></p>
	<p><button class="myButton kotogoon" onClick="$('#section_confirme_suppression').hide();">Oups ! J'annule</button></p>
	</div>
	<p>&nbsp;</p>
	</div>
	
	<p><button class="myButton" onclick="$('#section_bdd').toggle();">Base de données</button></p>
	<div id="section_bdd" hidden>
	<p>Joueurs enregistrés: <?php echo $nbjoueurs; ?></p>
	<p>Tournois enregistrés: <?php echo $nbtournois; ?><p>
	<p>Donnes jouées: <?php echo $nbdonnes; ?></p>
	<p>Diagrammes enregistrés: <?php echo $nbdiagrammes; ?></p>
	<p>Version base de données: <?php echo getversionsql(); ?></p>
	<p>Taille base de données: <?php echo compute_sizedb(); ?> kiloOctets</p>
	<p>La durée de calcul du tableau des résultats pour un tournoi augmente en fonction du nombre de donnes jouées</br>Si ce nombre de donnes est supérieur à 100000 ou si la taille de la base de données dépasse 90% de <?php echo $maxSizeBDD; ?> kiloOctets,</br>il est peut-être temps de supprimer les anciens tournois.</p>
	<?php
	$n = getDateFirstTournoi();
	if ( $n > 0 ) {
		// il existe au moins un tournoi
		$oldtournois = getNbThisYear( $firstdatet );
		print "<p>Date 1er tournoi: $firstTournoi</br>Nb tournois même année: $oldtournois</p>";
		print "<p><button class='mButton' onclick='suppressionVieuxTournois()'>Suppression $oldtournois anciens tournois</button></p>";
		print "<div id='section_suppression_tournois' hidden>";
		print "<p><button class='myButton oktogoon' id='valid4' onClick='confirmeSuppressionVieuxTournois()'>Je confirme</button></p>";
		print "<p><button class='myButton kotogoon' id='valid5' onClick='$(`#section_suppression_tournois`).hide();'>Oups ! J'annule</button></p>";
		print "<p id='msgvieuxtournois'>&nbsp;</p>";
		print "</div>";
	}
	?>
	</div>
	
	<p><button class="myButton" onclick="$('#section_events').toggle()">Evénements</button></p>
	<div id="section_events" hidden>
	<?php print htmlevents( $maxevents ); ?>
	</div>
	
	<h2>Fonctions d'import export à utiliser en cas de migration.</h2>
	<p><button class='mButton' onclick='exportbdd()'>Export base de données</button></p>
	<p><button class='mButton' onclick='importbdd()'>Import base de données</button></p>
	<div id="section_importBDD" hidden>
	<p style="color:red"><b>Attention:</b> l'importation de la base de données va remplacer toutes les données existantes dans la base de données actuelle, y compris les identifiants des administrateurs !</br>L'importation du fichier peut prendre plusieurs minutes. <b>N'interrompez pas le processus !!!</b></p>
	<p><b>2 méthodes possibles pour importer la base de données</b></p>
	
	<p><b>Méthode 1: </b>Utilisez l'application "PHPMyAdmin" sur le serveur de base de données de votre nouvel hébergement pour importer le fichier précédemment exporté dans la base de données "bridgette".</p>
	
	<p><b>Méthode 2: </b>Choisissez le fichier à utiliser sur votre disque dur, fichier  provenant d'un export réalisé sur l'ancien serveur.</p>
	<p>Fichier à importer: <input type="file" id="choicefile" name="sqlfile" accept=".sql" /> <button id="upload" onclick="btn_upload();">Import fichier</button> <span id="complete">&nbsp;</span></p>
	
	<div id="section_confirme" hidden>
	<p><button class="myButton oktogoon" onClick="uploadpart()">Je confirme</button> <button class="myButton kotogoon" onClick="$('#section_confirme').hide(); $('#section_importBDD').hide();">Oups ! J'annule</button></p>
	</div>

	<p id="msgbdd">&nbsp;</p>
	</div>
	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	
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