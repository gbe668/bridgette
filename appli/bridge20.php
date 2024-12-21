<?php
require("configuration.php");
require("bridgette_bdd.php");

$nbl = 0;
$idtournois = array();
$datetournois = array();
$npairess = array();
$genres = array();

function getclosedtournois() {
	global $tab_tournois, $st_closed, $parametres;
	global $nbl, $idtournois, $datetournois, $npairess, $genres, $st_typetournoi;
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_tournois where etat = '$st_closed';";
	$res = $dbh->query($sql);
	$nbl = $res->fetchColumn();
	if ( $nbl > 0 ) {
		$sth = $dbh->query( "SELECT * FROM $tab_tournois where etat = '$st_closed' order by tournoi desc, id;" );
		$nbl = min( $nbl, $parametres['maxt'] );
		for ( $i = 0; $i < $nbl; $i++ ) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$idtournois[$i] = $row[ 'id' ];
			$npairess[$i] = getnpaires( $row[ 'idtype' ] );
			$datet = $row[ 'tournoi' ];
			$datef = strdatet( $datet );
			$datetournois[$i] = $datef;
			$tt = gettypetournoi( $row[ 'idtype' ] );
			$genre = $tt['genre'];
			$genres[$i] = $st_typetournoi[$genre];
		}
	};
	$dbh = null;
};

function displayLignesTournois() {
	global $parametres;
	global $nbl, $idtournois, $datetournois, $npairess, $genres;

	if ( $nbl > 0 ) {
		$max = min( $nbl, $parametres['maxt'] );
		print '<table style="width:95%;max-width:350px;margin:auto;"><tbody>';
		print '<tr><td>Type</td><td>Date</td></tr>';

		for ($i = 0; $i < $max; $i++) {
			$nr = "nr_" . $i . "_" . $idtournois[$i];
			$gg = "num_" . $i . "_" . $idtournois[$i];
			$dd = "nom_" . $i . "_" . $idtournois[$i];
			print '<tr id="' . $nr . '" class="xtrsel">';
			print '<td id="' . $gg . '" class="xTxt1">' . $genres[$i] . " " . $npairess[$i] . ' paires</td>';
			print '<td id="' . $dd . '" class="xTxt1">' . $datetournois[$i] . '</td>';
			print '</tr>';
			};
		print "</tbody></table>";
	}
	else {
		print "<p>Pas de tounois enregistrés !</p>";
	}
};
?>

<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery-ui-1.13.2.min.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="stylesheet" href="css/jquery-ui.css">
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<style>
#datepicker-container{
  text-align:center;
}
#datepicker-center{
  display:inline-block;
  margin:0 auto;
}
</style>

<script>
function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function seltournoi( n ) {
	$("#msg").text("Chargement en cours ...");
	var nextstring = "bridge21.php?idtournoi=" + n + "&w=" +  window.innerWidth;
	location.replace( nextstring );
}
function existetournoi(dd) {
	console.log( "datetournoi", dd );
	$.get("existetournoiclos.php", {datetournoi:dd}, function(strjson) {
		if ( strjson.nbl == 0 ) {
			$("#msg").text("Pas de tournoi à cette date !");
		}
		if ( strjson.nbl == 1 ) {
			seltournoi( strjson.ids[0] );
		}
		if ( strjson.nbl > 1 ) {
			// à développer la suite
			html = strjson.nbl + " tournois à cette date !</br>";
			html += "Choisissez:";
			for ( i=0; i<strjson.nbl; i++ ) {
				html += "&nbsp;<button onclick='seltournoi(" + strjson.ids[i] + ")'>" + (i+1) +"</button>";
			}
			html += "&nbsp;<button onclick=selmarathon(\'"+dd+"\')>Marathon</button><div id='resmarathon'</div>";
			$("#msg").html(html);
			var elmnt = document.getElementById("msg");
			elmnt.scrollIntoView();
		}
	},"json");
}

function selmarathon(dd) {
	$.get("f20getmarathon.php?", {datetournoi:dd}, function(strjson) {
		$("#resmarathon").html(strjson.classement );
	}, "json");
}
// obtention d'une date
$.datepicker.regional['fr'] = {
	dateFormat: 'yy-mm-dd',	//'dd-mm-yy',
	closeText: 'Fermer',
	//prevText: 'P',
	//nextText: 'S',
	currentText: 'Aujourd\'hui',
	monthNames: ['Janvier','Fevrier','Mars','Avril','Mai','Juin', 'Juillet','Aout','Septembre','Octobre','Novembre','Decembre'],
	monthNamesShort: ['Jan','Fev','Mar','Avr','Mai','Jun', 'Jul','Aou','Sep','Oct','Nov','Dec'],
	dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
	dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
	dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
	weekHeader: 'Sm',
	firstDay: 1,
	minDate: new Date(2022, 4 -1, 15),		// début bridgette au club
	//maxDate: '+0',	//new Date(),	//'+12M +0D',
	//showButtonPanel: true,
	isRTL: false
};
$.datepicker.setDefaults( $.datepicker.regional['fr'] );

$( function() {
	//var dateFormat = "mm/dd/yy",
	datetournoi = $( "#datetournoi" ).datepicker({
			//defaultDate: +1,
			//numberOfMonths: 1
		})
		.on( "change", function() {
			console.log( "change", $("#datetournoi").val() );
			existetournoi( $("#datetournoi").val() );
		});

	function getDate( element ) {
		var date;
		try {
			date = $.datepicker.parseDate( dateFormat, element.value );
		}
		catch( error ) {
			date = null;
		}
		return date;
	}
} );
$(document).ready(function() {
	$("tr.xtrsel").click(function(event) {
		console.log( event.target.id );
		var figs = event.target.id.split('_');
		seltournoi( figs[2] );
	});
});
function topwindow() {
	elmnt = document.getElementById("topwindow");
	elmnt.scrollIntoView();
}
function cdeplus() {
	if ( $("#afficheplus").hasClass( "section_invisible" ) )
		$("#afficheplus").removeClass( "section_invisible" );
	else
		$("#afficheplus").addClass( "section_invisible" );
}
function cdemoins() {
	$("#afficheplus").addClass( "section_invisible" );
}

function exportypes() {
	var hiddenElement = document.createElement('a');
	hiddenElement.href = 'data:text/csv;charset=utf-8,%EF%BB%BF' + $("#listetypes").html();
	hiddenElement.target = '_blank';
	hiddenElement.download = "typestournois.html";
	hiddenElement.click();
};
</script>
 
 <body>
	<div style="text-align:center; max-width:350px; margin:auto;">
	<h2 style='text-align: center' id='topwindow'>Affichage des résultats</h2>
	<?php
	$maxt = $parametres['maxt'];
	print "<h3>Liste des $maxt derniers tournois</h3>";
	getclosedtournois();
	displayLignesTournois();
	?>
	<h3>Rechercher les résultats d'un tournoi</h3>
	<div id="datepicker-container">
		<div id="datepicker-center">
		<div id="datetournoi"></div>
		</div>
	</div>
	<script>
	// valeurs par défaut
	$('#datetournoi').datepicker();	// initialisation
	$('#datetournoi').datepicker('setDate', 'today');
	$('#datetournoi').datepicker( "option", "maxDate", 'today' );
	</script>
	<p id="msg">&nbsp;</p>
	
	<p><button class="myButton" onclick="cdeplus()">Affiche/masque types tournois</button></p>
	<div id="afficheplus" class="section_invisible">
	<div id="listetypes">
	<?php
	print htmlTableTypeTournois();
	//print '<p><button onClick="exportypes()">Exporter les types de tournoi</button></p>';
	?>
	</div>
	<p><button class="myButton" onclick="cdemoins()">Masque types tournois</button></p>
	</div>
	
	<p><button class="mySmallButton" onclick="gotoindex()">Retour page d'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	<div class="top"><img src="images/upwindow.png" style="width:40px;" onclick="topwindow()"/></div>
	</div>
	
	<?php
	if ( $parametres['affperf']==1 ) {
		getClassement();
	}
	?>
	</div>
 </body>
</html>