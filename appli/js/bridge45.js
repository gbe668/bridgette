var nodonne = 1;	// numéro de donne
var noligne = -1;	// numéro ligne dans le tableau des résultats
var totalpts;
var nbpts;

var tabns = Array();
var tabeo = Array();
var tabcontrat = Array();
var tabjouepar = Array();
var tabentame = Array();
var tabresultat = Array();
var tabpoints = Array();
var tabrang = Array();
var tabnote = Array();
var tabhweo = Array();

function getSymboleCouleur( tt ) {
	let code = '<img src="'+relimg;
	switch(tt) {
		case ("P"):
			code += 'pique.png"';
			break;
		case ("C"):
			code += 'coeur.png"';
			break;
		case ("K"):
			code += 'carreau.png"';
			break;
		case ("T"):
			code += 'trefle.png"';
			break;
		default:
			code += 'sans-atout.png"';
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
		// passe général, moyenne générale, non jouée
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

function masquelignes() {
	for ( i = 0; i < maxtables; i++ ) {
		nr = "#nr_" + i;
		$(nr).addClass( "xtr_invisible" );
		$(nr).removeClass( "xtr_sel" );
	}
};
function demarquelignes() {
	for ( i = 0; i < maxtables; i++ ) {
		nr = "#nr_" + i;
		$(nr).removeClass( "xtr_sel" );
	}
};
function sel_etui( nbr ) {
	nodonne += parseInt( nbr );
	if ( nodonne < 1 ) nodonne = 1;
	if ( nodonne > ndonnes ) nodonne = ndonnes;
	$("#etui").text( nodonne );
	realdonne.setDonneurVul(nodonne);
	
	getresultatdonne( nodonne );
	demarquelignes();
};
function getresultatdonne( etui ) {
	$.get("getresultatdonne.php?", {idtournoi:idtournoi, z_etui:etui}, function(strjson) {
		let nbl = strjson.nbl;
		nbpts = 0;
		totalpts = 0;
		if( nbl > 0 ) {
			tabns = strjson.ns;
			tabeo = strjson.eo;
			tabcontrat = strjson.contrat;
			tabjouepar = strjson.jouepar;
			tabentame = strjson.entame;
			tabresultat = strjson.resultat;
			tabpoints = strjson.points;
			tabrang = strjson.rang;
			tabnote = strjson.note;
			tabhweo = strjson.hweo;
			
			for( i=0; i<nbl; i++) {
				nr = "#nr_" + i;
				nr1 = nr + "_1";
				nr2 = nr + "_2";
				nr3 = nr + "_3";
				nr4 = nr + "_4";
				nr5 = nr + "_5";
				nr6 = nr + "_6";
				nr7 = nr + "_7";
				nr8 = nr + "_8";
				nr9 = nr + "_9";
				$(nr1).text( tabns[i] );
				$(nr2).text( tabeo[i] );
				$(nr3).html( htmlContrat( tabcontrat[i] ) );
				$(nr4).text( tabjouepar[i] );
				$(nr5).html( htmlEntame( tabentame[i] ) );
				$(nr6).html( htmlResultat( tabresultat[i] ) );
				if ( tabcontrat[i] == "N J" ) {
					$(nr7).text( "-" );
					$(nr8).text( "-" );
					$(nr9).text( "-" );
				}
				else {
					$(nr7).text( tabpoints[i] );
					$(nr8).text( parseFloat(tabrang[i]).toFixed(1) );
					$(nr9).text( parseFloat(tabnote[i]).toFixed(2) );
				}
				if ( tabhweo[i] == 0 ) {
					$(nr).removeClass( "xtr_invisible" );
					totalpts += parseInt( tabpoints[i] );
					++nbpts;
				}
				else {
					$(nr).addClass( "xtr_invisible" );
				}
			}
			for( i=nbl; i<maxtables; i++) {
				nr = "#nr_" + i;
				$(nr).addClass( "xtr_invisible" );
			}
			$("#msgerr1").text( "" );
			$("#section_suppression").removeClass( "section_invisible" );
			if ( nbl > 1 ) $("#section_moyenne").removeClass( "section_invisible" );
			else $("#section_moyenne").addClass( "section_invisible" );
		}
		else {
			masquelignes();
			$("#msgerr1").html( "non trouvés,</br>peut-être déjà supprimés ?" );
			$("#section_suppression").addClass( "section_invisible" );
		}
		$("#section_correction").addClass( "section_invisible" );
	},"json");
};
function clickSuppressionDonne() {
	$("#section_confirme_suppression").removeClass( "section_invisible" );
}
function clickAnnulationSuppressionDonne() {
	$("#section_confirme_suppression").addClass( "section_invisible" );
}
function clickConfirmeSuppressionDonne() {
	console.log( "clickConfirmeSuppressionDonne", nodonne );
	$.get("erasedonne.php?", {idtournoi:idtournoi, z_etui:nodonne}, function(strjson) {
		nbl = strjson.nbl;
		if( nbl > 0 ) {
			$("#msgerr1").text( "supprimés" );
			goto44();
		}
		else $("#msgerr1").text( "non trouvés" );
	},"json");
};
function clickMoyenneDonne() {
	var dataString = 'updatedonne.php?idtournoi=' + idtournoi;
	dataString += '&donne='  + nodonne;
	dataString += '&ns=' + tabns[noligne];
	dataString += '&eo=' + tabeo[noligne];
	dataString += '&contrat=' + "M G";
	dataString += '&jouepar=' + "-";
	dataString += '&entame=' + "-";
	dataString += '&resultat=' + "-";
	dataString += '&points=' + $("#moyenne2").val();
	console.log( dataString );
	$.get( dataString, function(strtext) {
		$("#validok").text( strtext );
		sel_etui(0);
		console.log( strtext );
		setTimeout(function() { $("#validok").html( "&nbsp;" ); }, 1000);
	},"text");
};
function numMedian(a) {
	a = a.slice(0).sort(function(x, y) {
		return x - y;
	});
	var b = (a.length + 1) / 2;
	return (a.length % 2) ? a[b - 1] : (a[b - 1.5] + a[b - 0.5]) / 2;
}
function sel_donne( id ) {
	const figs = id.split('_');
	noligne = parseInt( figs[1] );
	
	// calcul moyenne hors ligne sélectionnée
	ztotalpts = totalpts - tabpoints[noligne];
	znbpts = nbpts - 1;
	var moyenne1 = parseInt( ztotalpts/znbpts );
	$("#moyenne1").text( moyenne1 );
	
	// calcul médiane hors ligne sélectionnée
	var tabmediane = Array();
	var j = 0;
	for ( let i=0; i<tabpoints.length; i++ ) {
		if ( i==noligne ) continue;
		tabmediane[j] = tabpoints[i];
		j++;
	}
	var mediane1 = Math.round( numMedian(tabmediane) );
	$("#mediane1").text( mediane1 );
	$("#moyenne2").val( mediane1 );
	
	demarquelignes();
	let nr = "#nr_" + noligne;
	$(nr).addClass( "xtr_sel" );
	$("#section_correction").removeClass( "section_invisible" );

	$("#msgerr2").text( "Résultat table NS " + tabns[noligne] + " avec EO " + tabeo[noligne] );

	console.log( tabcontrat[noligne] );
	realdonne.setContrat( tabcontrat[noligne] );
	realdonne.setDeclarant( tabjouepar[noligne] );
	realdonne.setEntame( tabentame[noligne] );
	realdonne.setResultat( tabresultat[noligne] );
};

function clickValidationCorrection() {
	// Enregistrement de la donne jouée
	var dataString = 'updatedonne.php?idtournoi=' + idtournoi;
	dataString += '&donne='  + nodonne;
	dataString += '&ns=' + tabns[noligne];
	dataString += '&eo=' + tabeo[noligne];
	dataString += '&contrat=' + realdonne.contrat;
	dataString += '&jouepar=' + realdonne.declarant;
	dataString += '&entame='  + realdonne.entame;
	dataString += '&resultat=' + realdonne.res;
	dataString += '&points=' + realdonne.points;
	console.log( dataString );
	$.get( dataString, function(strtext) {
		$("#validok").text( strtext );
		sel_etui(0);
		console.log( strtext );
		setTimeout(function() { $("#validok").html( "&nbsp;" ); }, 1000);
	},"text");
};

$(document).ready(function() {
	$("#etuim10").bind('click', function( event ){ sel_etui( -10 ); });
	$("#etuim1").bind('click', function( event ){ sel_etui( -1 ); });
	$("#etuip1").bind('click', function( event ){ sel_etui( 1 ); });
	$("#etuip10").bind('click', function( event ){ sel_etui( 10 ); });
});
	
$(document).on( "click", "td.xNum5", function(event) {
	console.log( "xNum5: ", event.target.id, "Parent: ", $(this).parent().attr("id") );
	sel_donne( $(this).parent().attr("id") );
	//sel_donne( event.target.id );
});
