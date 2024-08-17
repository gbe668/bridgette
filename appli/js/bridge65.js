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

function displaydeal( deal ) {
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
	$("#ph_nord").text( points[1] );
	$("#ph_est").text( points[2] );
	$("#ph_sud").text( points[3] );
	$("#ph_ouest").text( points[4] );
	$("#points_honneurs").show();
	$("#msg").text( "" );
	return true;
}
function initcanselect() {
	var ligne;
	for ( let i = 1; i <= 12; i++ ) {
		ligne = "#ligne_" + i;
		$(ligne).addClass( "canselect" );
	}
}
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
	$(ligne).addClass( "smallDigitfocus" );
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
	if ( (calculnbcartes(1) == 13) && (calculnbcartes(2) == 13) && (calculnbcartes(3) == 13 ) ) {
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
function testnbcartes( jj ) {
	var nb = calculnbcartes( jj );
	if ( nb < 13 ) {
		$("#msg").text( nb + "/13, manque des cartes" );
		return false;
	}
	if ( nb > 13 ) {
		$("#msg").text( "trop de cartes" );
		return false;
	}
	$("#msg").text( "ok" );
	return true;
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
		var id = event.target.id;
		//console.log( "id", id );
		const figs = id.split('_');
		switch ( figs[0] ) {
			case "ligne": {
				setfocus(figs[1]);
				break;
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
