// variables initialisées par php
var idtournoi;// = parseInt( "<?php echo $idtournoi; ?>" );
var pairesNS;//  = parseInt( "<?php echo $pairesNS; ?>" );
var pairesEO;//  = parseInt( "<?php echo $pairesEO; ?>" );
var njouees;//	  = parseInt( "<?php echo $njouees; ?>" );
	
var ntables;//  = parseInt( "<?php echo $ntables; ?>" );
var ndonnes;//  = parseInt( "<?php echo $ndonnes; ?>" );
var paquet;//   = parseInt( "<?php echo $paquet; ?>" );
var saut;//   	 = parseInt( "<?php echo $saut; ?>" );
var gueridon;// = parseInt( "<?php echo $gueridon; ?>" );

// autres variables
var notable = 1;
var nodonne = -1;
var vulns;
var vuleo;
var seletui;
var maxetuis = 36;	// Nb max de donnes figurant sur la feuille de marque

var tabid, tabetui, tabeo, tabcontrat, tabjouepar, tabentame, tabresultat, tabpoints;

function getSymboleCouleur( tt ) {
	let code = '<img src=';
	switch(tt) {
		case ("P"):
			code += '"images/pique.png"';
			break;
		case ("C"):
			code += '"images/coeur.png"';
			break;
		case ("K"):
			code += '"images/carreau.png"';
			break;
		case ("T"):
			code += '"images/trefle.png"';
			break;
		default:
			code += '"images/sans-atout.png"';
			break;
	}
	code += ' height="13" />';
	return code;
};
function htmlEntame( entame ) {
	let niv, col;
	if ( entame == "-" ) {
		// passe général, pas d'entame
		return entame;
	}
	niv = entame[0];
	if ( niv == 1 ) {
		niv = 10;
		col = getSymboleCouleur( entame[2] );
	}
	else {
		col = getSymboleCouleur( entame[1] );
	}
	return( niv + col );
}
function htmlContrat( contrat ) {
	const figs = contrat.split(' ');
	let niv = figs[0];
	if ( (niv == "-")||(niv == "P")||(niv == "M")||(niv == "N") ) {
		// passe général, pas d'entame
		return contrat;
	}
	let col = figs[1];
	let color = getSymboleCouleur( col[0] );
	let add = figs[2];
	return( niv + " " + color + " " + add );
}
function htmlResultat( result ) {
	if ( result == "-" ) {
		// passe général, pas d'entame
		return( "-" );
	}
	if ( result == "0" ) return( "=" );
	if ( result > 0 ) return( "+" + result );
	return( result );
}

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
function sel_table( nbr ) {
	//var actuel = parseInt( $("#table").text() );
	notable += parseInt( nbr );
	if ( notable < 1 ) notable = 1;
	if ( notable > pairesNS ) notable = pairesNS;
	$("#table").text( notable );
	affichemarquevierge();
	affichemarque( notable );
	getresultattable( notable );
};
function affichemarquevierge() {
	for( i=0; i<maxetuis; i++) {
		var nr = "#nr_" + i;
		var nr1 = nr + "_1";
		var nr2 = nr + "_2";
		var nr3 = nr + "_3";
		var nr4 = nr + "_4";
		var nr5 = nr + "_5";
		var nr6 = nr + "_6";
		var nr7 = nr + "_7";
		//$(nr).addClass( "xtr_invisible" );
		$(nr).addClass( "notused" );
		var j = i+1;	// n° étui
		$(nr1).text( j );
		if ( etuis[j][2] > 0 ) $(nr1).addClass( "vulnerable" );
		else $(nr1).addClass( "nonvulnerable" );
		if ( etuis[j][3] > 0 ) $(nr2).addClass( "vulnerable" );
		else $(nr2).addClass( "nonvulnerable" );
		$(nr2).text( " " );
		$(nr3).text( " " );
		$(nr4).text( " " );
		$(nr5).text( " " );
		$(nr6).text( " " );
		$(nr7).val( " " );
		if ( i < ndonnes )
			$(nr).removeClass( "xtr_invisible" );
	}
}
function affichemarque( table ) {
	console.log( ndonnes );
	var donne = (table-1)*paquet+1;	// 1ère donne jouée -1
	var posgueridon = ntables/2;	// si guéridon
	
	// test guéridon
	if ( ( table > posgueridon )&&( gueridon > 0 ) ) {
		donne += paquet;
		if ( donne > ndonnes ) donne -= ndonnes;
	}
	
	for( cpt=0; cpt<njouees; cpt++) {
		var position = Math.floor( cpt / paquet );
		var numEO = table - position;
		
		// test saut
		if ( ( position >= saut )&&( saut > 0) ) numEO --;
		if ( numEO < 1 ) numEO = numEO + Math.max( pairesNS, pairesEO );

		//console.log( "numEO", numEO, "cpt=", cpt, " donne=", donne, " position=", position, " posgueridon=", posgueridon );

		var j = donne -1;
		nr = "#nr_" + j;
		nr1 = nr + "_1";
		nr2 = nr + "_2";
		nr3 = nr + "_3";
		nr4 = nr + "_4";
		nr5 = nr + "_5";
		nr6 = nr + "_6";
		nr7 = nr + "_7";
		//nr8 = nr + "_8";
		//nr9 = nr + "_9";
		
		$(nr2).text( numEO );
		//$(nr3).text( position );
		$(nr4).text( " " );
		$(nr5).text( " " );
		$(nr6).text( " " );
		$(nr7).val( " " );
		$(nr).removeClass( "notused" );
		
		++donne;
		if ( donne > ndonnes ) donne -= ndonnes;
	}
};

function getresultattable( table ) {
	$.get("getresultattable.php?", {idtournoi:idtournoi, table:table, adversaire:0}, function(strjson) {
		//console.log( strjson );
		nbl = strjson.nbl;
		if( nbl > 0 ) {
			tabid = strjson.id;
			tabetui = strjson.etui;
			tabeo = strjson.eo;
			tabcontrat = strjson.contrat;
			tabjouepar = strjson.jouepar;
			tabentame = strjson.entame;
			tabresultat = strjson.resultat;
			tabpoints = strjson.points;
			//tabrang = strjson.rang;
			//tabnote = strjson.note;
			
			for( let i=0; i<nbl; i++) {
				//nr = "#nr_" + i;
				let inr = tabetui[i]-1;
				nr = "#nr_" + inr;
				$(nr).removeClass( "notused" );
				//$(nr).removeClass( "xtr_invisible" );
				nr1 = nr + "_1";
				nr2 = nr + "_2";
				nr3 = nr + "_3";
				nr4 = nr + "_4";
				nr5 = nr + "_5";
				nr6 = nr + "_6";
				nr7 = nr + "_7";
				//nr8 = nr + "_8";
				//nr9 = nr + "_9";
				//$(nr1).text( tabns[i] );
				$(nr2).text( tabeo[i] );
				$(nr3).html( htmlContrat( tabcontrat[i] ) );
				$(nr4).text( tabjouepar[i] );
				$(nr5).html( htmlEntame( tabentame[i] ) );
				$(nr6).html( htmlResultat( tabresultat[i] ) );
				$(nr7).val( tabpoints[i] );
				//$(nr8).text( tabrang[i] );
				//$(nr9).text( parseFloat(tabnote[i]).toFixed(2) );
			}
			$("#msgerr1").text( "" );
		}
		else {
			$("#msgerr1").html( "non trouvés" );
		}
	},"json");
};

function set_points( etui ) {
	nodonne = parseInt( etui );
	nr = "#nr_" + nodonne;
	nr1 = nr + "_1";
	nr2 = nr + "_2";
	nr7 = nr + "_7";

	var nn = parseInt( $(nr1).text() );
	var eo = parseInt( $(nr2).text() );
	var pts = parseInt( $(nr7).val() );
	if ( isNaN( pts ) ) {
		$("#msgerr").html( "pts: valeur invalide" );
	}
	else {
		$("#msgerr").html( "&nbsp;" );

		// Enregistrement de la donne jouée
		var datastring = 'setpoints.php?tournoi='+idtournoi+'&etui=' + nn;
		datastring += '&ns=' + notable + '&eo=' + eo;
		datastring += '&points=' + pts;
		console.log( datastring );
		$.get( datastring, function(strjson) {
			$("#validok").text( strjson );
		},"json");
		// focus ligne suivante
		etui ++;
		if ( etui < maxetuis ) {
			var idnolig = "#nr_" + etui + "_7";
			$(idnolig).focus();
		}
	}
};

$(document).ready(function() {
	$('input.xNum5').keydown(function(event) {
		//console.log( event.target.id );
		// enter has keyCode = 13, change it if you want to use another button
		if (event.keyCode == 13) {
			const figs = event.target.id.split('_');
			var nolig = parseInt( figs[1] );
			//console.log( "enter " + nolig );
			set_points( nolig );
		}
	});
	$('img.clkok').click(function(event) {
		var e = event.target.id;
		var figs = e.split('_');
		var nbr = parseInt( figs[1] );
		console.log( event.target.nodeName, "e", e, "nbr", nbr );
		if ( isNaN(nbr) ) {
			// bug
			$("#msgclavier").text( "img.clkok bug numero NaN, re-click !" );
			return
		}
		else {
			set_points( nbr );
		}
	});
	
	$("#tabm5").bind('click', function( event ){ sel_table( -5 ); });
	$("#tabm1").bind('click', function( event ){ sel_table( -1 ); });
	$("#tabp1").bind('click', function( event ){ sel_table( 1 ); });
	$("#tabp5").bind('click', function( event ){ sel_table( 5 ); });
	
});
