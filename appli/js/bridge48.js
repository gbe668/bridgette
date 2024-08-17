// variables initialisées par php
var idtournoi;// = parseInt( "<?php echo $idtournoi; ?>" );
var pairesNS;//  = parseInt( "<?php echo $pairesNS; ?>" );
var pairesEO;//  = parseInt( "<?php echo $pairesEO; ?>" );
var njouees;//	  = parseInt( "<?php echo $njouees; ?>" );
var idtype;		// pour la feuille de suivi howell
var genre;		// distingo howell / mitchell
	
var ntables;//  = parseInt( "<?php echo $ntables; ?>" );
var ndonnes;//  = parseInt( "<?php echo $ndonnes; ?>" );
var paquet;//   = parseInt( "<?php echo $paquet; ?>" );
var saut;//   	 = parseInt( "<?php echo $saut; ?>" );
var relais;
var gueridon;// = parseInt( "<?php echo $gueridon; ?>" );

// autres variables
var nodonne = 1;
var vulns;
var vuleo;
var ndonnes;
var maxlignes;	// Nb max de lignes figurant sur la feuille de suivi
var nblignes;	// Nb de lignes figurant effectivement sur la feuille de suivi

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

// feuille de suivi tournoi Mitchell
function nav_mdonne( nbr ) {
	nodonne += parseInt( nbr );
	if ( nodonne < 1 ) nodonne = 1;
	if ( nodonne > ndonnes ) nodonne = ndonnes;
	$("#donne").text( nodonne );

	// 1ère paires à jouer la donne
	var numNS, numEO;
	numNS = Math.floor( (nodonne-1) / paquet ) + 1;
	numEO = numNS;
	var posgueridon = ntables/2;	// si guéridon

	// Relais NS
	relaisNS = 0;
	relaisEO = 0;
	if ( relais > 0 ) {
		if ( pairesNS > pairesEO ) relaisNS = relais;
		else relaisEO = relais;
	}
		
	// affichage de la séquence
	for( let position = 0; position < maxlignes; position++) {
		console.log( nbr, position, numNS, numEO );
		var nr = "#nr_" + position;
		var nr1 = nr + "_1";
		var nr2 = nr + "_2";
		var nr3 = nr + "_3";
		var nr4 = nr + "_4";
		var nr5 = nr + "_5";
		var nr6 = nr + "_6";
		var nr7 = nr + "_7";
		//$(nr).removeClass( "notused" );
		
		// relais NS
		if ( (relaisNS > 0)&&(numEO == pairesNS) ) {
			numNS --;
			if ( numNS < 1 ) numNS += Math.max( pairesNS, pairesEO );
			numEO -= 2;			
			if ( numEO < 1 ) numEO += Math.max( pairesNS, pairesEO );
		}

		// guéridon
		if ( (gueridon > 0 )&&(numNS == pairesEO) ) {
			// pas de donnes au guéridon
			numNS = 1;
		}
		
		$(nr1).text( numNS );
		$(nr2).text( numEO );
		$(nr3).text( " " );
		$(nr4).text( " " );
		$(nr5).text( " " );
		$(nr6).text( " " );
		$(nr7).val( " " );

		numNS --;
		if ( numNS < 1 ) numNS += Math.max( pairesNS, pairesEO );
		numEO -= 2;
		// test saut
		if ( ( (position+1) == saut )&&( saut > 0) ) numEO --;
		if ( numEO < 1 ) numEO += Math.max( pairesNS, pairesEO );
		
		// relais EO
		if ( (relaisEO > 0)&&(numNS == pairesEO) ) {
			numNS --;
			numEO -= 2;
			if ( numEO < 1 ) numEO += Math.max( pairesNS, pairesEO );
		}
	}
	getresultatdonne( nodonne );
};
// feuille de suivi tournoi Howell
function nav_hdonne( nbr ) {
	nodonne += parseInt( nbr );
	if ( nodonne < 1 ) nodonne = 1;
	if ( nodonne > ndonnes ) nodonne = ndonnes;
	$("#donne").text( nodonne );

	let first = Math.floor( (nodonne-1)/paquet )*paquet + 1;
	// récupération de la séquence
	//$feuille = getfeuillesuivihowell( $idtype, $paquet, first_of_paquet );
	$.get("getfeuillesuivihowell.php?", {idtype:idtype, paquet:paquet, first:first}, function(strjson) {
		feuille = strjson.feuille;
		console.log( "feuille ", feuille );
		nblignes = 0;
		for( let position = 0; position < maxlignes; position++) {
			if ( feuille[position]['found'] == 0 ) break;
			++nblignes;
			let nr = "#nr_" + position;
			let nr1 = nr + "_1";
			let nr2 = nr + "_2";
			let nr3 = nr + "_3";
			let nr4 = nr + "_4";
			let nr5 = nr + "_5";
			let nr6 = nr + "_6";
			let nr7 = nr + "_7";
			//$(nr).removeClass( "notused" );
		
			$(nr1).text( feuille[position]['NS'] );
			$(nr2).text( feuille[position]['EO'] );
			$(nr3).text( " " );
			$(nr4).text( " " );
			$(nr5).text( " " );
			$(nr6).text( " " );
			$(nr7).val( " " );
		}
		$.get("getresultatdonne.php?", {idtournoi:idtournoi, z_etui:nodonne}, function(strjson) {
			$("#msgdonne").text( strjson.info );
			let nbl = strjson.nbl;
			console.log( "nbl " + nbl );
			if( nbl > 0 ) {
				// au moins un résultat sur cette donne
				tabid = strjson.id;
				tabns = strjson.ns;
				tabeo = strjson.eo;
				tabcontrat = strjson.contrat;
				tabjouepar = strjson.jouepar;
				tabentame = strjson.entame;
				tabresultat = strjson.resultat;
				tabpoints = strjson.points;
				// peuplement des lignes sur la feuille de suivi
				for( let position = 0; position<nblignes; position++) {
					let nr = "#nr_" + position;
					let nr1 = nr + "_1";
					let nr2 = nr + "_2";
					let nr3 = nr + "_3";
					let nr4 = nr + "_4";
					let nr5 = nr + "_5";
					let nr6 = nr + "_6";
					let nr7 = nr + "_7";
				
					let ns = $(nr1).text();
					let eo = $(nr2).text();
				
					for( let i=0; i<nbl; i++) {
						if ( ns == tabns[i] ) {
							if ( eo == tabeo[i] ) {
								$(nr3).html( htmlContrat( tabcontrat[i] ) );
								$(nr4).text( tabjouepar[i] );
								$(nr5).html( htmlEntame( tabentame[i] ) );
								$(nr6).html( htmlResultat( tabresultat[i] ) );
								$(nr7).val( tabpoints[i] );
							}
						}
					}
				}
			}
			$("#nr_0_7").focus();
		},"json");
	}, "json");
};
function getresultatdonne( nodonne ) {
	// récupération des donnes
	$.get("getresultatdonne.php?", {idtournoi:idtournoi, z_etui:nodonne}, function(strjson) {
		$("#msgdonne").text( strjson.info );
		let nbl = strjson.nbl;
		//console.log( "nbl " + nbl );
		if( nbl > 0 ) {
			tabid = strjson.id;
			tabns = strjson.ns;
			tabeo = strjson.eo;
			tabcontrat = strjson.contrat;
			tabjouepar = strjson.jouepar;
			tabentame = strjson.entame;
			tabresultat = strjson.resultat;
			tabpoints = strjson.points;
			
			for( let i=0; i<nbl; i++) {
				// recherche du n° de ligne du résultat
				for ( let j=0; j<maxlignes; j++ ) {
					nr = "#nr_" + j;
					nr1 = nr + "_1";
					if ( $(nr1).text() == tabns[i] ) break;
				}
				//$(nr).removeClass( "xtr_invisible" );
				nr2 = nr + "_2";
				nr3 = nr + "_3";
				nr4 = nr + "_4";
				nr5 = nr + "_5";
				nr6 = nr + "_6";
				nr7 = nr + "_7";
				//console.log( "nr7 " + nr7 + " pts " + tabpoints[i] );
				$(nr1).text( tabns[i] );
				$(nr2).text( tabeo[i] );
				$(nr3).html( htmlContrat( tabcontrat[i] ) );
				$(nr4).text( tabjouepar[i] );
				$(nr5).html( htmlEntame( tabentame[i] ) );
				$(nr6).html( htmlResultat( tabresultat[i] ) );
				$(nr7).val( tabpoints[i] );
			}
		}
		$("#nr_0_7").focus();
	},"json");
};

function set_points( ligne ) {
	noligne = parseInt( ligne );
	nr = "#nr_" + noligne;
	nr1 = nr + "_1";
	nr2 = nr + "_2";
	nr7 = nr + "_7";

	var ns  = parseInt( $(nr1).text() );
	var eo  = parseInt( $(nr2).text() );
	var pts = parseInt( $(nr7).val() );
	if ( isNaN( pts ) ) {
		$("#msgerr").html( "pts: valeur invalide" );
	}
	else {
		$("#msgerr").html( "&nbsp;" );

		// Enregistrement de la donne jouée
		var datastring1 = 'setpoints.php?tournoi='+idtournoi+'&etui=' + nodonne;
		datastring1 += '&ns=' + ns + '&eo=' + eo;
		datastring1 += '&points=' + pts;
		console.log( datastring1 );
		$.get( datastring1, function(strjson) {
			$("#validok").text( strjson );
		},"json");
		if ( genre == 2 ) {
			//type howell
			pts = -pts;
			var datastring2 = 'setpoints.php?tournoi='+idtournoi+'&etui=' + nodonne;
			datastring2 += '&ns=' + eo + '&eo=' + ns;
			datastring2 += '&points=' + pts;
			console.log( datastring2 );
			$.get( datastring2, function(strjson) {
				$("#validok").text( strjson );
			},"json");
		}
		// focus ligne suivante
		ligne ++;
		if ( ligne < maxlignes ) {
			var idnolig = "#nr_" + ligne + "_7";
			$(idnolig).focus();
		}
		else {
			$("#nextdonne").focus();
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
		var nolig = parseInt( figs[1] );
		console.log( event.target.nodeName, "e", e, "nolig", nolig );
		if ( isNaN(nolig) ) {
			// bug
			$("#msgclavier").text( "img.clkok bug numero NaN, re-click !" );
			return
		}
		else {
			set_points( nolig );
		}
	});
	
	// Mitchell
	$("#tabmm5").bind('click', function( event ){ nav_mdonne( -5 ); });
	$("#tabmm1").bind('click', function( event ){ nav_mdonne( -1 ); });
	$("#tabmp1").bind('click', function( event ){ nav_mdonne( 1 ); });
	$("#tabmp5").bind('click', function( event ){ nav_mdonne( 5 ); });
	// Howell
	$("#tabhm5").bind('click', function( event ){ nav_hdonne( -5 ); });
	$("#tabhm1").bind('click', function( event ){ nav_hdonne( -1 ); });
	$("#tabhp1").bind('click', function( event ){ nav_hdonne( 1 ); });
	$("#tabhp5").bind('click', function( event ){ nav_hdonne( 5 ); });
});
