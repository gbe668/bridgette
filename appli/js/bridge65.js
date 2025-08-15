var noligne = 1;
var joueur = 1;
var couleur = 0;
var niveaux = [ "A ", "R ", "D ", "V ", "10 ", "9 ", "8 ", "7 ", "6 ", "5 ", "4 ", "3 ", "2 " ];
var ptshonn = [ 4, 3, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];
var levels = [ "A", "K", "Q", "J", "T", "9", "8", "7", "6", "5", "4", "3", "2" ];

var cartesP = [ 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];	// 13 cartes, As au rang 0
var cartesC = [ 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];
var cartesK = [ 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];
var cartesT = [ 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ];
var cartes = [ cartesP, cartesC, cartesK, cartesT ];

var dealfield;
var dealtest = "N:.63.AKQ987.A9732 A8654.KQ5.T.QJT6 J973.J98742.3.K4 KQT2.AT.J6542.85";

const etuis = Array (
	Array (0,0,0,0),	// n°etui, donneur, vulns, vuleo 
	Array (1,1,0,0),  Array (2,2,1,0),  Array (3,3,0,1),  Array (4,4,1,1),
	Array (5,1,1,0),  Array (6,2,0,1),  Array (7,3,1,1),  Array (8,4,0,0),
	Array (9,1,0,1),  Array (10,2,1,1), Array (11,3,0,0), Array (12,4,1,0),
	Array (13,1,1,1), Array (14,2,0,0), Array (15,3,1,0), Array (16,4,0,1),
	Array (17,1,0,0), Array (18,2,1,0), Array (19,3,0,1), Array (20,4,1,1),
	Array (21,1,1,0), Array (22,2,0,1), Array (23,3,1,1), Array (24,4,0,0),
	Array (25,1,0,1), Array (26,2,1,1), Array (27,3,0,0), Array (28,4,1,0),
	Array (29,1,1,1), Array (30,2,0,0), Array (31,3,1,0), Array (32,4,0,1),
	Array (33,1,0,0), Array (34,2,1,0), Array (35,3,0,1), Array (36,4,1,1)
);

function diag_skeleton() {
	let str = '<div id="skeleton_diagramme">';
	str += '<table border="1" style="width:100%; max-width: 350px; margin:auto; border-collapse: collapse;" class="notranslate"><tbody>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td style="width:15%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_1">&nbsp;</td></tr>';
	str += '<tr><td rowspan="2" colspan="2"><button id="showanalysis">Analyse</button></td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_2">&nbsp;</td></tr>';
	str += '<tr>';
	str += '<td><img src="'+relimg+'carreau.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_3">&nbsp;</td></tr>';
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'trefle.png" height="18" style="vertical-align:middle" /></td><td colspan="2" class="xsmallDigit" id="ligne_4">&nbsp;</td></tr>';
	
	str += '<tr><td style="width:10%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td>';
	str += '<td class="xsmallDigit" id="ligne_13">&nbsp;</td>';
	str += '<td id="diagrowtop" style="border:2pt solid windowtext; border-bottom:none;">Nord</td>';
	str += '<td style="width:10%;"><img src="'+relimg+'pique.png" height="18" style="vertical-align:middle" /></td>';
	str += '<td style="width:35%;" class="xsmallDigit" id="ligne_5">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'coeur.png" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_14">&nbsp;</td>';
	str += '<td id="diagrowmid1" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_6">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'carreau.png" alt="carreau" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_15">&nbsp;</td>';
	str += '<td id="diagrowmid2" style="border:2pt solid windowtext; border-bottom:none; border-top:none">&nbsp;</td>';
	str += '<td><img src="'+relimg+'carreau.png" alt="carreau" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_7">&nbsp;</td></tr>';
	
	str += '<tr><td><img src="'+relimg+'trefle.png" alt="trefle" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_16">&nbsp;</td>';
	str += '<td id="diagrowbottom" style="border:2pt solid windowtext; border-top:none">Sud</td>';
	str += '<td><img src="'+relimg+'trefle.png" alt="trefle" height="18" /></td>';
	str += '<td class="xsmallDigit" id="ligne_8">&nbsp;</td></tr>';
	
	str += '<tr><td>&nbsp;</td><td>&nbsp;</td>';
	str += '<td><img src="'+relimg+'pique.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_9">&nbsp;</td></tr>';
	str += '<tr><td rowspan="3" colspan="2">';
		str += '<table border="1" style="margin:auto;" id="points_honneurs" hidden><tbody>';
		str += '<tr><td>&nbsp;</td><td id="ph_nord" class="phonn">NN</td><td>&nbsp;</td></tr>';
		str += '<tr><td id="ph_ouest" class="phonn">OO</td><td>&nbsp;</td><td id="ph_est" class="phonn">EE</td></tr>';
		str += '<tr><td>&nbsp;</td><td id="ph_sud" class="phonn">SS</td><td>&nbsp;</td></tr>';
		str += '</tbody></table>';
	str += '</td>';
	str += '<td><img src="'+relimg+'coeur.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_10">&nbsp;</td></tr>';
	str += '<tr>';
	str += '<td><img src="'+relimg+'carreau.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_11">&nbsp;</td></tr>';
	str += '<tr>';
	str += '<td><img src="'+relimg+'trefle.png" height="18" /></td><td colspan="2" class="xsmallDigit" id="ligne_12">&nbsp;</td></tr>';
	
	str += '</tbody></table>';
	str += '</div>';
	return str;
}
function diag_keyboard() {
	let str = "<table style='width:100%; max-width: 350px; margin:auto;'><tbody>";
	str += "<tr>";
	str += '<td rowspan="2" class="xNum65" id="ok_up" style="font-size: 3em;">▲</td>';
	str += '<td width="20pts">&nbsp;</td>';
	str += '<td class="xNum65"><div id="n_0">As</div></td>';
	str += '<td class="xNum65"><div id="n_1">R</div></td>';
	str += '<td class="xNum65"><div id="n_2">D</div></td>';
	str += '<td class="xNum65"><div id="n_3">V</div></td>';
	str += "</tr>";
	str += "<tr>";
	str += "<td>&nbsp;</td>";
	str += '<td class="xNum65"><div id="n_4">10</div></td>';
	str += '<td class="xNum65"><div id="n_5">9</div></td>';
	str += '<td class="xNum65"><div id="n_6">8</div></td>';
	str += '<td class="xNum65"><div id="n_7">7</div></td>';
	str += "</tr>";
	str += "<tr>";
	str += '<td rowspan="2" class="xNum65" id="ok_down" style="font-size: 3em;">▼</td>';
	str += "<td>&nbsp;</td>";
	str += '<td class="xNum65"><div id="n_8">6</div></td>';
	str += '<td class="xNum65"><div id="n_9">5</div></td>';
	str += '<td class="xNum65"><div id="n_10">4</div></td>';
	str += '<td class="xNum65"><div id="n_11">3</div></td>';
	str += "</tr>";
	str += "<tr>";
	str += "<td>&nbsp;</td>";
	str += '<td class="xNum65"><div id="n_12">2</div></td>';
	str += "<td>&nbsp;</td>";
	str += '<td colspan="2" class="xNum65" id="ok_next">suivant</td>';
	str += "</tr>";
	str += "</tbody></table>";
	return str;
}
function displaydeal( deal, etui ) {
	var ligne, enmain;
	var points = [0,0,0,0,0];
	let n = deal.length;
	if ( n != 69 ) {
		$("#msg").text( "longueur champ deal incorrecte" );
		return false;
	}
	if ( deal.substr(0, 2) != "N:" ) {
		$("#msg").text( "Err: " + deal );
		return false;
	}
	couleur = 0;
	joueur = 1;
	enmain = "";
	// raz des champs
	for ( let i = 1; i < 17; i++ ) {
		ligne = "#ligne_"+i;
		$(ligne).text( "" );
	}
	for ( let i = 2; i < n; i++ ) {
		let car = deal.charAt( i );
		if ( car == "." ) {
			ligne = "#ligne_" + parseInt( couleur + (joueur-1)*4 + 1 );
			$(ligne).text( enmain );
			couleur = ++couleur % 4;
			enmain = "";
			continue;
		}
		if ( car == " " ) {
			ligne = "#ligne_" + parseInt( couleur + (joueur-1)*4 + 1 );
			$(ligne).text( enmain );
			couleur = ++couleur % 4;
			++joueur;
			enmain = "";
			continue;
		}
		let j = levels.indexOf( car );
		cartes[couleur][j] = joueur;
		points[joueur] += ptshonn[j];
		enmain += niveaux[j];
		if ( i == 68 ) {
			// dernière ligne
			$("#ligne_16").text( enmain );
		}
	}
	// ajout 18 mai 2025
	let donneur = etuis[etui][1];
	$("#ph_nord") .css('background', '#FFFFFF' );
	$("#ph_est")  .css('background', '#FFFFFF' );
	$("#ph_sud")  .css('background', '#FFFFFF' );
	$("#ph_ouest").css('background', '#FFFFFF' );
	switch( donneur ) {
		case 1: $("#ph_nord") .css('background', 'yellow' ); break;
		case 2: $("#ph_est")  .css('background', 'yellow' ); break;
		case 3: $("#ph_sud")  .css('background', 'yellow' ); break;
		case 4: $("#ph_ouest").css('background', 'yellow' ); break;
	}
	
	$("#ph_nord").text( points[1] );
	$("#ph_est").text( points[2] );
	$("#ph_sud").text( points[3] );
	$("#ph_ouest").text( points[4] );
	$("#points_honneurs").show();
	$("#msg").text( "" );
	
	let colorns = etuis[etui][2] ? "red" : "green";
	let coloreo = etuis[etui][3] ? "red" : "green";
	
	$("#diagrowtop").css('border-top-color', colorns )
		.css('border-left-color', coloreo )
		.css('border-right-color', coloreo );
	$("#diagrowmid1").css('border-color', coloreo );
	$("#diagrowmid2").css('border-color', coloreo );
	$("#diagrowbottom").css('border-bottom-color', colorns )
		.css('border-left-color', coloreo )
		.css('border-right-color', coloreo );
	$("#ph_nord").css('border-color', colorns );
	$("#ph_sud").css('border-color', colorns );
	$("#ph_ouest").css('border-color', coloreo );	
	$("#ph_est").css('border-color', coloreo );	
	
	return true;
}
var edition = false;
function initcanselect() {
	var ligne;
	for ( let i = 1; i <= 12; i++ ) {
		ligne = "#ligne_" + i;
		$(ligne).addClass( "canselect" );
	}
	edition = true;
	$("#points_honneurs").hide();
	testtermine();
}
var leftwidth = '2.5pt';
function setfocus( n ) {
	if ( n > 12) return;
	var ligne, key;
	noligne = parseInt( n );
	couleur = (noligne-1) % 4;	// Pique = 0
	joueur = Math.trunc( (noligne-1)/4 ) + 1;		// joueur = 1 pour Nord
	// curseur sur la ligne sélectionnée
	for ( let i = 1; i <= 16; i++ ) {
		ligne = "#ligne_" + i;
		$(ligne).removeClass( "smallDigitfocus" );
	}
	ligne = "#ligne_" + n;
	$(ligne).addClass( "smallDigitfocus" ).css('border-left-width', leftwidth );
	// sélection des cartes en main
	for ( let i = 0; i < 13; i++ ) {
		key = "#n_" + i;
		$(key).removeClass( "oktogoon" );
		$(key).removeClass( "kotogoon" );
	}
	for ( let i = 0; i < 13; i++ ) {
		key = "#n_" + i;
		if ( cartes[couleur][i] == joueur ) {
			$(key).addClass( "oktogoon" );
		}
		else {
			if ( cartes[couleur][i] > 0 ) {
				$(key).addClass( "kotogoon" );
			}
		}
	}
	// test nombre de cartes en main pour le joueur
	testnbcartes( joueur );
}
function upfocus() {
	if ( noligne > 1 ) {
		noligne -= 1;
	}
	setfocus( noligne );
}
function downfocus() {
	if ( noligne < 12 ) {
		noligne +=1
	}
	setfocus( noligne );
}

var msgvalidok = "<span style='color:red'>Attention: le calcul des contrats possibles suivi de l'enregistrement des diagrammes peut durer plusieurs secondes ...</span>"
function testtermine() {
	// test nb cartes pour les 3 joueurs N, E, S
	let nb1 = calculnbcartes(1);
	let nb2 = calculnbcartes(2);
	let nb3 = calculnbcartes(3);
	setleftcolor(1, nb1);
	setleftcolor(2, nb2);
	setleftcolor(3, nb3);
	if ( (nb1 == 13) && (nb2 == 13) && (nb3 == 13 ) ) {
		// Définition de la main d'ouest
		buildouest();
		buildDealField();
		$("#msg").text( "Vérifier la main d'Ouest" );
		$("#section_validiags").show();
		$("#validok").html( msgvalidok );
		var elmnt = document.getElementById("msg");
		elmnt.scrollIntoView();
		return true;
	}
	else {
		$("#section_validiags").hide();
		$("#validok").html( "Attente fin d'entrée des diagrammes" );
		return false;
	}
}
function setleftcolor(jj, nb) {
	let leftcolor = (nb == 13) ? "green" : "red";
	let j = jj*4-3;
	for ( let i = 0; i < 4; i++ ) {
		let ligne = "#ligne_"+j;
		$(ligne).css('border-left-color', leftcolor )
		.css('border-left-width', leftwidth );
		j++;
	}
}
function testnbcartes( jj ) {
	var nb = calculnbcartes( jj );
	if ( nb < 13 ) $("#msg").text( nb + "/13, manque des cartes" );
	if ( nb > 13 ) $("#msg").text( "trop de cartes" );
	if ( nb == 13 )$("#msg").text( "ok" );
	return nb;
}
function calculnbcartes( joueur ) {
	var nb = 0;
	for ( let i = 0; i < 4; i++ ) {	// couleur
		for ( let j = 0; j < 13; j++ ) {	// niveau
			if ( cartes[i][j] == joueur ) nb++;
		}
	}
	return nb;
}
function selCarte( niveau ) {
	var enmain, ligne;
	var p1 = "#n_" + niveau;
	if ( cartes[ couleur ][ niveau ] == joueur ) {
		cartes[ couleur ][ niveau ] = 0;
		$(p1).removeClass( "oktogoon");
		$(p1).removeClass( "kotogoon");
	}
	else {
		cartes[ couleur ][ niveau ] = joueur;	// écrase l'ancien proprio si il y en avait un
		$(p1).addClass( "oktogoon");
		$(p1).removeClass( "kotogoon");
	}
	for ( let i = 1; i < 5; i++ ) {	// couleur
		enmain = "";
		for ( let j = 0; j < 13; j++ ) {
			if ( cartes[couleur][j] == i ) {	// niveau
				enmain += niveaux[j];
			}
		}
		ligne = "#ligne_" + parseInt( couleur + (i-1)*4 + 1 );
		$(ligne).text( enmain );
	}
	testnbcartes( joueur );
	testtermine();
};
function buildouest() {
	var enmain, ligne;
	for ( let i = 0; i < 4; i++ ) {	// couleur
		enmain = "";
		for ( let j = 0; j < 13; j++ ) {	// niveau
			let p1 = "#n_" + j;
			if ( (cartes[i][j] == 0)||(cartes[i][j] == 4) ) {
				cartes[i][j] = 4;
				enmain += niveaux[j];
				$(p1).addClass( "oktogoon");
			}
		}
		ligne = "#ligne_" + parseInt( i + 13 );
		$(ligne).text( enmain );
	}
}
function buildDealField() {
	dealfield = "N:";
	for ( let i = 1; i < 5; i++ ) {	// joueur
		for ( let j = 0; j < 4; j++ ) {	// couleur
			for ( let k = 0; k < 13; k++ ) {	// niveau
				if ( cartes[j][k] == i )
					dealfield += levels[k];
			}
			if ( j < 3 ) dealfield += ".";
			else {
				if ( i < 4 ) dealfield += " ";
			}
		}
	}
}
function clickValidiags( myCallback ) {
	$("#section_inputdiags").hide();
	$("#section_validiags").hide();
	
	let start = Date.now();
	
	// Calcul des contrats possibles, reformate la chaine pour avoir le donneur en premier
	let etui = etuis[donne];
	let deal = dealfield.slice(2).split(' ');
	let validDealers = "NESW";
	
	let index = etui[1]-1;	// N:0
	let str = validDealers[index];
	str += ":";
	for (let j=0; j<4; j++) {
		str += deal[index];
		if (j!=3) str += " ";
		index++;
		if (index==4) index=0;
	}
	/**
	 * board is a PBN-formatted string (e.g. 'N:AKQJ.T98.76.432 ...')
	 * Returns an object mapping strain -> player -> makeable tricks, e.g.
	 * {'N': {'N': 1, 'S': 2, 'E': 3, 'W': 4}, 'S': { ... }, ...}
	 */
	console.log( "deal reformatté", str );
	let DDTable = calcDDTable(str);
	console.log( DDTable );
	
	// codage pour la bdd
	let tabi = ['N','S', 'H', 'D', 'C'];
	let tabj = ['N', 'S', 'E', 'W'];
	let dds = "";
	for ( let j in tabj ) {
		for ( let i in tabi ) {
			let value = DDTable[tabi[i]][tabj[j]];
			dds += String.fromCharCode(97 + value);
		}
	}
	
	let delta = Date.now() - start;
	console.log( "dds", dds, "delta", delta );
	
	// Enregistrement du diagramme
	$.get( relpgm+"f65setdiagramme.php", { idtournoi:idtournoi, donne:donne, diagramme:dealfield, dds:dds },
	function(strjson) {
		$('#validok').html( strjson.display + " en "+delta+" ms" );
		setTimeout( function() { myCallback(); }, 1000 );
	},"json");
};

$(document).ready(function() {
	// click ligne
	$('td.xsmallDigit').click(function(event) {
		if ( edition ) {
			var id = event.target.id;
			//console.log( "id", id );
			const figs = id.split('_');
			switch ( figs[0] ) {
				case "ligne": {
					setfocus(figs[1]);
					break;
				}
			}
		}
	});
	// saisie carte
	$('td.xNum65').click(function(event) {
		var id = event.target.id;
		//console.log( "id", id );
		const figs = id.split('_');
		switch ( figs[0] ) {
			case "n": {
				selCarte( figs[1] );
				break;
			}
			case "ok": {
				switch ( figs[1] ) {
					case "up":
						upfocus();
						break;
					case "down":
					case "next":
						downfocus();
						break;
				}
				break;
			}
		}
	});
});

function display_resultat(etui) {
	$.get( relpgm+"f64getresultatdonne.php?", {idtournoi:idtournoi, etui:etui, ns:numNS, eo:0}, function(strjson) {
		$("#section_resultat").html(strjson.result);
		if ( strjson.diags == null ) {
			$("#section_diagramme").html('<p><button class="myButton" onclick="goto65()">Entrez les diagrammes</button></p>');
			$("#section_diagramme").append("<p><em>Si vous n'avez pas le temps de les entrer</br>laissez aux suivants !</em></p>");
		}
		else {
			$("#section_diagramme").html( diag_skeleton() );
			dealfield = strjson.diags;
			displaydeal( strjson.diags, etui );
			console.log( strjson.dds );
			if ( strjson.dds == null ) {
				$("#showanalysis").hide();
			}
			else {
				$("#makeableContracts").html( htmlMCTable(strjson.dds) );
				$("#makeableContracts").hide();
				$("#showanalysis").show();
			}
		}
	}, "json");
}
function swipeleft() {
	if ( enableswipe > 0 ) {
		let first = Math.floor( (donne-1)/paquet )*paquet +1;
		let max = first + paquet -1;
		console.log("swipeleft", "first", first, "max", max, "donne", donne );
		if ( donne < max ) {
			donne = donne+1;
			display_resultat(donne)
		}
	}
}
function swiperight() {
	if ( enableswipe > 0 ) {
		let first = Math.floor( (donne-1)/paquet )*paquet +1;
		let max = first + paquet -1;
		console.log("swiperight", "first", first, "max", max, "donne", donne );
		if ( donne > first ) {
			donne = donne-1;
			display_resultat(donne)
		}
	}
}

// ajout affichage résultat analyse - 30/072025
function htmlMCTable(cvector) {
	let tabi = ['N','S', 'H', 'D', 'C'];
	let tabj = ['N', 'S', 'E', 'O'];
	
	let str = "<table style='margin:auto;'><tbody>";
	str += "<tr><td></td><td>SA</td>";
	str += "<td>&#9824;</td><td><Font color=Red>&#9829;</Font><td><Font color=Red>&#9830;</Font></td></td><td>&#9827;</td></tr>";
	let k = 0;
	for ( let j in tabj ) {
		str += "<tr><td>" + tabj[ Math.floor(k/4) ] + "</td>";
		for ( let i in tabi ) {
			let value = cvector.charCodeAt(k) - 97;
			if (value<7)	value = "-";
			else 	value = value - 6;
			str += "<td><button style='width:42px'>" + value + "</button></td>";
			k++;
		}
		str += "</tr>";
	}
	str += "</tbody></table>";
	return str;
}
function showresultat( strjson ) {
	$("#section_resultat").html(strjson.result);
	$("#makeableContracts").hide();
	if ( strjson.diags == null ) {
		$("#section_diagramme").html("<p>Diagrammes non enregistrés</p>");
	}
	else {
		$("#section_diagramme").html( diag_skeleton() );
		dealfield = strjson.diags;
		displaydeal( strjson.diags, actuel );
		if ( strjson.dds == null ) {
			$("#showanalysis").hide();
		}
		else {
			$("#makeableContracts").html( htmlMCTable(strjson.dds) );
			$("#showanalysis").show();
		}
	}
	elmnt = document.getElementById("tablenav");
	elmnt.scrollIntoView();
}

const col_trefle = 1;
const col_carreau = 2;
const col_coeur = 3;
const col_pique = 4;
const col_sansatout = 5;
const fcols = ['0', 'T', 'K', 'C', 'P'];	// en anglais
const fvals = ['0', '2', '3', '4', '5', '6', '7', '8', '9', 'T', 'V', 'D', 'R', 'A'];
const ecols = ['0', 'C', 'D', 'H', 'S'];	// en anglais
const evals = ['0', '2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A'];
class Card {
	constructor( icol, ival) {
		this.icol = icol;	// indice couleur
		this.ival = ival;	// indice valeur
		this.sval = evals[ival];
		this.face = fvals[ival] + fcols[icol];
		this.id = "card" + this.face;
	}
}
class Deck {
	constructor() {
		this.cards = [];
		// card( 0, 0 ) = carte fantome
		for (let suit = 1; suit < 5; suit++) {	// couleur
			for (let j = 1; j < 14; j++) {		// niveau
				let carte = new Card( suit, j );
				this.cards.push(carte);
				//console.log( carte.face );
			}
		}
		this.dealfield = [];	// distribution codé au format BDD
	}
	shuffle() {
		let tmp, i, j, k, n, m;
		for ( k = 0; k < 10; k++ ) {
			// mélange x fois de suite
			for ( i = this.cards.length -1; i > 0; i--) {
				j = Math.floor( Math.random() * (i+1) );
				tmp = this.cards[i];
				this.cards[i] = this.cards[j];
				this.cards[j] = tmp;
			}
		}
		n = 13;
		// tri des cartes en main de chaque joueur
		for ( i = 0; i < 4; i++) {
			m = i*n;
			for (j = 0; j < n-1; j++) {
				//console.log( " i ", i, " m ", m, " j ", j );
				for (k = 0; k < n-j-1; k++) {
					if ( this.cards[m+k].icol < this.cards[m+k+1].icol ) {
						tmp = this.cards[m+k];
						this.cards[m+k] = this.cards[m+k+1];
						this.cards[m+k+1] = tmp;
					}
				}
			}
			for (j = 0; j < n-1; j++) {
				for (k = 0; k < n-j-1; k++) {
					if ( this.cards[m+k].icol == this.cards[m+k+1].icol ) {
						if ( this.cards[m+k].ival < this.cards[m+k+1].ival ) {
							tmp = this.cards[m+k];
							this.cards[m+k] = this.cards[m+k+1];
							this.cards[m+k+1] = tmp;
						}
					}
				}
			}
		}
		// construit dealfield après le tri
		this.dealfield = "N:";
		for ( i = 0; i < 4; i++ ) {	// joueur
			m = i*n;
			let suit = col_pique;
			for ( k = m; k < m+n; ) {	// carte
				if ( this.cards[k].icol == suit ) {
					this.dealfield += this.cards[k].sval;
					k++;
				}
				else {
					this.dealfield += "."; // séparateur entre couleurs
					--suit;
				}
			}
			// test chicanes
			if ( suit == col_carreau ) {
				this.dealfield += "."; // chicane à trèfle
			}
			if ( suit == col_coeur ) {
				this.dealfield += ".."; // chicanes à trèfle et carreau
			}
			if ( suit == col_pique ) {
				this.dealfield += "..."; // chicanes à trèfle, carreau et coeur
			}
			
			if ( i < 3 ) {
				this.dealfield += " "; // séparateur entre mains de joueurs
			}
		}
		//console.log( "Après tri: ", this.dealfield );
	}
	isValide( deal ) {
		if ( deal == null ) return false;
		let n = deal.length;
		if ( n != 69 ) {
			//$("#msgerr").text( "longueur champ deal incorrecte" );
			return false;
		}
		if ( deal.substr(0, 2) != "N:" ) {
			//$("#msgerr").text( "Err: " + deal );
			return false;
		}
		return true;
	}
	loadDeck( deal ) {
		this.cards = [];
		this.dealfield = deal;
		let n = deal.length;
		if ( n != 69 ) {
			$("#msgerr").text( "longueur champ deal incorrecte" );
			return false;
		}
		if ( deal.substr(0, 2) != "N:" ) {
			$("#msgerr").text( "Err: " + deal );
			return false;
		}
		let suit = col_pique;	// pique en premier
		for (let i = 2; i < n; i++ ) {
			let car = deal.charAt( i );
			if ( car == "." ) {
				--suit;
				continue;
			}
			if ( car == " " ) {
				suit = col_pique;
				continue;
			}
			let j = 0;
			switch ( car ) {
				case 'A':
					j = 13;
					break;
				case 'K':
					j = 12;
					break;
				case 'Q':
					j = 11;
					break;
				case 'J':
					j = 10;
					break;
				case 'T':
					j = 9;
					break;
				default:
					j = parseInt( car )-1;
				}
			// console.log( suit, car, j );
			this.cards.push( new Card( suit, j ) );
		}
	}
}
function autoDiagramme() {
	var deck = new Deck();
	deck.shuffle();
	//console.log( deck.dealfield );
	displaydeal( deck.dealfield, 0 )
	initcanselect();
	setfocus( 1 );
}