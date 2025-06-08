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
		$("#section_validiags").removeClass( "section_invisible" );
		$("#validok").html( "Attente enregistrement des diagrammes" );
		var elmnt = document.getElementById("msg");
		elmnt.scrollIntoView();
		return true;
	}
	else {
		$("#section_validiags").addClass( "section_invisible" );
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
// function clickValidiags() // différent selon 64 et 64 relais !!!

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
