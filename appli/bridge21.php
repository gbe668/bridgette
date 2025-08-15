<?php
require("configuration.php");
require("bridgette_bdd.php");
require("lib63.php");

$idtournoi = htmlspecialchars( $_GET['idtournoi'] );
$screenw = htmlspecialchars( $_GET['w'] );

$t = readTournoi( $idtournoi );
$datef = $t[ 'datef' ];
$ndonnes = $t[ 'ndonnes' ];
if ( ($t['idtype'] <= $min_type_affimp)&&($parametres['affimp']==1) ) {
	$ordre = "pointsIMP";
}
else {
	$ordre = "points";
}

function getdiagrammes($idt) {
	global $tab_diagrammes;
	
	$dbh = connectBDD();
	$sql = "SELECT count(*) FROM $tab_diagrammes where idtournoi = '$idt';";
	$res = $dbh->query($sql);
	$nb = $res->fetchColumn();
	$diags = Array();
	if ( $nb > 0 ) {
		$sql = "SELECT etui, dealt FROM $tab_diagrammes where idtournoi = '$idt' order by etui;";
		$res = $dbh->query($sql);
		for ( $i = 0; $i < $nb; $i++ ) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			array_push( $diags, array( 'etui'=>$row['etui'], 'deal'=>$row['dealt'] ) );
		}
	}
	$dbh = null;
	return json_encode( $diags );
};
$diagrammes = getdiagrammes($idtournoi);
?>
 
<!DOCTYPE HTML>
<html>
<head>
	<title>Bridg'ette</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/jquery.mobile-1.5.0-rc1.min.js"></script>
	<script src="js/bridge65.js"></script>
	<link rel="stylesheet" href="css/bridgestylesheet.css" />
	<link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>

<script>
const relpgm = "<?php echo $relpgm; ?>";
const relimg = "<?php echo $relimg; ?>";

const diagrammes = <?php echo $diagrammes; ?>;
const club = "<?php echo $titre; ?>";

function gotoindex() {
	var nextstring = "bridgette.php";
	location.replace( nextstring );
};
function goto20() {
	var nextstring = "bridge20.php";
	location.replace( nextstring );
};
function reload() {
	var nextstring = "bridge21.php?idtournoi=" + idtournoi + "&w=" +  window.innerWidth;
	location.replace( nextstring );
};
var actuel = 1;	// étui sélectionné
var selns = seleo = 0;
function key_select( nbr ) {
	actuel += parseInt( nbr );
	if ( actuel < 1 ) actuel = 1;
	if ( actuel > ndonnes ) actuel = ndonnes;
	$("#etui").text( actuel );
	
	$.get(relpgm+"f64getresultatdonne.php?", {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo}, function(strjson) {
		showresultat( strjson );
	}, "json")
	.fail( function( jqxhr, settings, ex ) { console.log('Erreur: '+ ex ); } );
};
$(document).ready(function() {
	$("#etuim10").bind('click', function( event ){ key_select( -10 ); });
	$("#etuim1").bind('click', function( event ){ key_select( -1 ); });
	$("#etuip1").bind('click', function( event ){ key_select( 1 ); });
	$("#etuip10").bind('click', function( event ){ key_select( 10 ); });
	//$("#swipebox").bind('swipe', function( event ){ key_select( 1 ); });
	$("#swipebox").bind('swipeleft', function( event ){ key_select( 1 ); });
	$("#swipebox").bind('swiperight', function( event ){ key_select( -1 ); });
	$('td.select').click(function(event) {
		var id = event.target.id;
		const figs = id.split('_');
		console.log( "Id ", id, " Paire ", figs[0], " n° ", figs[1] );
		if ( figs[0] == "ns" ){
			selns = figs[1];
			seleo = 0;
		}
		if ( figs[0] == "eo" ){
			selns = 0;
			seleo = figs[1];
		}
		
		$.get(relpgm+"f64getresultatdonne.php?", {idtournoi:idtournoi, etui:actuel, ns:selns, eo:seleo}, function(strjson) {
			showresultat( strjson );
		}, "json")
		.fail( function( jqxhr, settings, ex ) { console.log('Erreur: '+ ex ); } );
		
		$.get(relpgm+"f21getroadmap.php?", {idtournoi:idtournoi, axe:figs[0], num:figs[1]}, function(strjson) {
			$("#team").html("Feuille de route de la paire "+strjson.ref+"</br>"+strjson.team);
			$("#roadmap").html(strjson.html);
			elmnt = document.getElementById("team");
			elmnt.scrollIntoView();
		}, "json");
	});
});
$(document).on( "click", "td.seletui", function(event) {
	var id = $(this).parent().attr("id");
	const figs = id.split('_');
	console.log( "Etui ", figs[1] );
	actuel = parseInt( figs[1] );
	
	$("#section_donnes").show();
	$("#section_roadmap").hide();
	$("#etui").text(figs[1]);
	key_select( 0 );
});

$.mobile.loading().hide();		// suite ajout jquery.mobile-1.5.0-rc1.min.js

// ajout affichage résultat analyse - 30/072025
var pbnfile = false;
$(document).on( "click", "#showanalysis", function() { $("#makeableContracts").toggle(); });
function downloadFile(text, fileType, fileName) {
	var blob = new Blob([text], { type: fileType });
	var a = document.createElement('a');
	a.download = fileName;
	a.href = URL.createObjectURL(blob);
	a.click();
	a.remove();
}
function exportDDSolver() {
	console.log( "exportDDSolver" );
	if ( !pbnfile ) {
		var suits = ["NT"," S"," H"," D"," C"];
		var declarer = "NSEW";
		let validDealers = "-NESW";
		var i,j,k;
		var str="";
		var n = diagrammes.length;
		if ( n > 0 ) {
			str += "% PBN 2.1\r\n";
			str += "% EXPORT\r\n";
			str += "%Content-type: text/x-pbn; charset=ISO-8859-1\r\n";
			str += "%Creator: Bridge Solver Online\r\n";
			str += "[Site \""+ club +"\"]\r\n";
			str += "[Event \"Tournoi du "+ datef +"\"]\r\n";
			//str += "[Date \"\"]\r\n";
			
			for ( i=0; i< n; i++ ) 	{
				let diagramme = diagrammes[i];
				let boardName = diagramme.etui;
				
				let etui = etuis[boardName];
				let dealer = validDealers[etui[1]];
				let v = etui[2]+etui[3]*2;
				let vul;
				switch( v ) {
					case 0: vul="None";	break;
					case 1: vul="NS";	break;
					case 2: vul="EW";	break;
					case 3: vul="All";	break;
				}
				
				str += "[Board \"" + boardName + "\"]\r\n";
				//str += "[West \"\"]\r\n";
				//str += "[North \"\"]\r\n";
				//str += "[East \"\"]\r\n";
				//str += "[South \"\"]\r\n";
				str += "[Dealer \"" + dealer + "\"]\r\n";
				str += "[Vulnerable \"" + vul + "\"]\r\n";
				
				var deal = diagramme.deal.slice(2).split(' ');
				
				str += "[Deal \"" + dealer.charAt(0) + ":";
				
				//var dealer = g_hands.boards[i].Dealer.charAt(0);
				
				var index = 0;
				
				if (dealer=='N') index = 0;
				else if (dealer=='E') index = 1;
				else if (dealer=='S') index = 2;
				else index = 3;
				
				for (j=0;j<4;j++) {
					str += deal[index];
					if (j!=3) str += " ";
					index++;
					if (index==4) index=0;
				}
				
				str += "\"]\r\n";
				
				//str += "[Scoring \"\"]\r\n";
				//str += "[Declarer \"\"]\r\n";
				//str += "[Contract \"\"]\r\n";
				//str += "[Result \"\"]\r\n";

				str += "\r\n";
			}
			
			//log("button=save");
			downloadFile(str, "text/pbn", "boards.pbn");
			pbnfile = true;
			$("#btndds").show();
			$("#btnexpdds").hide();
		}
		else {
			alert("Aucun diagramme enregistré pour ce tournoi !");
		}
	}
}
function DDSolver() {
	open( "https://dds.bridgewebs.com/bsol_standalone/ddummy.htm", "_blank" );
}
</script>

<body>
	<div style="text-align: center">
	
	<script>
	screenw  = parseInt( "<?php echo $screenw; ?>" );
	if ( isNaN( screenw ) ) reload();
	</script>
	
	<?php
	displayResultatsTournoi( $idtournoi, $screenw );
	?>

	<div id="section_donnes">
	<p>Naviguez entre les différentes donnes</br>en balayant l'écran ou en cliquant sur les chiffres</p>
	
	<table border="0" style="width:90%; max-width: 300px; margin:auto;" id="tablenav"><tbody><tr>
	<td class='xNum2'><div id="etuim10">-10</div></td>
	<td class='xNum2'><div id="etuim1">-1</div></td>
	<td class='xNum2'><div id="etuip1">+1</div></td>
	<td class='xNum2'><div id="etuip10">+10</div></td>
	</tr><tbody></table>

	<div id="swipebox">
	<div id="section_resultat">
	<?php print htmlResultatDonne($idtournoi, 1, 0, 0, $ordre); ?>
	</div>
	<div id="section_diagramme"></div>
	<div id='makeableContracts' hidden></div>
	</div>
	
	<p><button class="myButton" onclick="$('#section_donnes').hide();$('#section_roadmap').show();">Affichage feuille de route</button></p>
	</div>
	
	<div id="section_roadmap" hidden>
	<h3 id="team" >Cliquez sur une paire</br>pour afficher sa feuille de route</h3>
	<div id="roadmap" >feuille de route</div>
	<p><button class="myButton" onclick="$('#section_donnes').show();$('#section_roadmap').hide();">Affichage donnes</button></p>
	</div>
	
	<p><button id="btnexpdds" onclick="exportDDSolver()">Exporte les donnes</button> <button id="btndds" onclick="DDSolver()">Ouvre DDSolver</button></p>
	
	<p><button class="mySmallButton" onclick="goto20()">Retour liste des tournois</button></p>
	<p><button class="mySmallButton" onclick="gotoindex()">Retour à l'accueil</button></p>
	<div class="return"><img src="images/icon_return.png" style="width:40px;" onclick="gotoindex()"/>
	</div>	

	<script>
	idtournoi  = parseInt( "<?php echo $idtournoi; ?>" );
	ndonnes = parseInt( "<?php echo $ndonnes; ?>" );
	key_select( 0 );	// affichage 1er diagramme
	$("#nsec_1").show();
	
	// ajout analyse
	datef  = "<?php echo $datef; ?>";
	$("#showanalysis").toggle();

	</script>
	</div>
</body>
</html>