var idtournoi, etat, genre, st_phase_jeu;
var t_mitchell = 1;
var t_howell = 2;

var max_tables;
var ntables;
var ndonnes;
var paquet;
var relais;
var maxpositions;
var npositions;
var startseq;
var dureefirstposition;
var dureenextposition;

var stconn = Array();
var numEO = Array();
var numdonne = Array();
var cpt = Array();
var pos = Array();
var audiofinposition = 0;

function masquelignesconnexions() {
	for ( i = 1; i < max_tables+1; i++ ) {
		nr = "#row_" + i;
		$(nr).addClass( "xtr_invisible" );
	}
}

function affichelignesconnexions() {
	for ( i = 1; i < ntables+1; i++ ) {
		nr = "#row_" + i;
		$(nr).removeClass( "xtr_invisible" );
	}
}

function afficheconnexions() {
	let donnesjouees = 0;
	for  ( i = 1; i < max_tables+1; i++ ) {
		rowi = "#row_" + i ;
		case_0 = rowi + "_0";
		case_1 = rowi + "_1";
		case_2 = rowi + "_2";
		case_3 = rowi + "_3";
		case_4 = rowi + "_4";
		
		// raz
		$(case_0).removeClass( "xCnxtel" );
		$(case_0).removeClass( "xCnxtelKo" );
		$(case_0).removeClass( "xCnxtelOk" );
		
		stcnx = stconn[ i-1 ];
		if ( stcnx == 1 ) $(case_0).addClass( "xCnxtel" );
		if ( stcnx == 2 ) $(case_0).addClass( "xCnxtelKo" );
		if ( stcnx == 3 ) $(case_0).addClass( "xCnxtelOk" );
		$(case_1).text( numEO[i-1] );
		$(case_2).text( numdonne[i-1] );
		$(case_3).text( cpt[i-1] );
		$(case_4).text( pos[i-1] );
		
		// test au moins une donne jouée
		donnesjouees += cpt[i-1];
		};
	if ( donnesjouees > 0 )
		$("#back241").addClass( "section_invisible" );
}

function tstfintournoi() {
	let nbfinis = 0;
	if (genre == t_mitchell) {	// tournoi mitchell
		for  ( let i = 1; i < ntables+1; i++ ) {
			if ( stconn[ i-1 ] == 3 ) ++nbfinis;
			};
		let xrelais = 0;
		if ( relais > 0 ) xrelais = 1;
		if ( nbfinis == ntables-xrelais ) return true;
		else return false;
	}
	else {	// tournoi Howell
		for  ( let i = 1; i < ntables+1; i++ ) {
			if ( stconn[ i-1 ] == 3 ) ++nbfinis;
			};
		if ( nbfinis == ntables ) return true;
		else return false;
	}
};

function lstConnexions() {
	$.get("lstconnexions.php?", {idtournoi:idtournoi}, function(strjson) {
		stconn = strjson.stconn;
		numEO = strjson.numEO;
		numdonne = strjson.numdonne;
		cpt = strjson.cpt;
		pos = strjson.pos;
		afficheconnexions();
	}, "json");
};
function changenpositions( valeur ) {
	newpositions = npositions + valeur;
	if ( (newpositions > maxpositions ) || (newpositions < (maxpositions - 2) ) ) {
		$("#msgerr").text( "Non autorisé ..." );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1500);
	}
	else {
		npositions = newpositions;
		njouees = npositions * paquet;
		fintournoi = startseq + dureefirstposition + dureenextposition * (npositions-1);
		heure = new Date( fintournoi * 1000 );
		//strfintournoi = heure.getHours() + ":" + heure.getMinutes();
		strfintournoi = heure.toLocaleTimeString();

		$("#npositions").text(npositions);
		$("#njouees").text(njouees);
		$("#fintournoi").text(strfintournoi);
		$("#msgerr").text( "Enregistrement en cours ..." );
		$.get("setnpositions.php", {idtournoi:idtournoi, npositions:npositions}, function(texte) {
			$("#msgerr").text( "Enregistrement terminé." );
			//$("#msgerr").text( texte );
			setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1500);
		},"text");
	}
};
function sel_etui( nbr ) {
	actuel = parseInt( $("#etui").text() );
	actuel += parseInt( nbr );
	if ( actuel < 1 ) actuel = 1;
	if ( actuel > ndonnes ) actuel = ndonnes;
	$("#etui").text( actuel );
};
$(document).ready(function() {
	$("#signemoins").bind('click', function( event ){ changenpositions( -1 ); });
	$("#signeplus").bind('click', function( event ){ changenpositions( 1 ); });
	
	$("#etuim10").bind('click', function( event ){ sel_etui( -10 ); });
	$("#etuim1").bind('click', function( event ){ sel_etui( -1 ); });
	$("#etuip1").bind('click', function( event ){ sel_etui( 1 ); });
	$("#etuip10").bind('click', function( event ){ sel_etui( 10 ); });
});
