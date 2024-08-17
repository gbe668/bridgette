// id tournoi
var idTournoi;
var idtype;
var genre;	//

// variables affichage tableaux
var nblignesNS;
var nblignesEO;
var max_tables;
var okns = 0;
var okeo = 0;

// variables saisie numéro du joueur
var calcul=0;
var numpaire;		// emplacement
var position;		// orientation
var vp1;
var vp2;

// variable saisie nom du joueur
var strname ="";
var idj  = Array();
var numj = Array();
var nomj = Array();

// variables permutation de paires
var src = 0, dst = 0;
var html1 = "Permutation de paires:<br/>cliquez sur une paire pour la sélectionner";
function annuledeplacement() {
	//console.log( "annule", src, dst );
	if ( src !== 0 ) {
		let idsel = "#" + src;
		$(idsel).removeClass( "xPairesel" );
		src = 0;
		dst = 0;
		$("#msg").text( html1 );
	}
}

function addligneNS() {
	if ( nblignesNS < max_tables ) {
		nblignesNS++;
		masquelignesNS();
		scrollPosition( 1 );
	}
}
function addligneEO() {
	if ( nblignesEO < max_tables ) {
		nblignesEO++;
		masquelignesEO();
		scrollPosition( 2 );
	}
}
function masquelignesNS() {
	let ideb = nblignesNS + 1;
	let ifin = max_tables + 1;
	for ( let i = 1; i < ideb; i++ ) {
		let idrowa = "#rowns" + i + "a";
		let idrowb = "#rowns" + i + "b";
		$(idrowa).removeClass( "xtr_invisible" );
		$(idrowb).removeClass( "xtr_invisible" );
	}
	for ( let i = ideb; i < ifin; i++ ) {
		let idrowa = "#rowns" + i + "a";
		let idrowb = "#rowns" + i + "b";
		$(idrowa).addClass( "xtr_invisible" );
		$(idrowb).addClass( "xtr_invisible" );
	}
}
function masquelignesEO() {
	let ideb = nblignesEO + 1;
	let ifin = max_tables + 1;
	for ( let i = 1; i < ideb; i++ ) {
		let idrowa = "#roweo" + i + "a";
		let idrowb = "#roweo" + i + "b";
		$(idrowa).removeClass( "xtr_invisible" );
		$(idrowb).removeClass( "xtr_invisible" );
	}
	for ( let i = ideb; i < ifin; i++ ) {
		let idrowa = "#roweo" + i + "a";
		let idrowb = "#roweo" + i + "b";
		$(idrowa).addClass( "xtr_invisible" );
		$(idrowb).addClass( "xtr_invisible" );
	}
}
function checktableaux() {
	$("#msgerr").html( "&nbsp;" );
	if ( idtype < min_type_mitchell ) {
		// type Howell
		checktableauxHowell()
	}
	else {
		// type Mitchell
		checktableauxMitchell();
	}
}
	
function checktableauxHowell() {
	switch ( okns ) {
		case 0:
		case -1:
			$("#statusns").text( "Tableau incomplet" );
			$("#endofdef").addClass( "section_invisible" );
			break;
		default:
			if ( (okns >= min_paires_howell)&&(okns <= max_paires_howell) ) {
				$("#statusns").text( "Nombre de paires OK" );
				$("#endofdef").removeClass( "section_invisible" );
				$("#statusdef").text( " " );
			}
			else {
				if ( okns < min_paires_howell ) $("#statusns").text( "Manque des paires, minimum " + min_paires_howell + " paires");
				if ( okns > max_paires_howell ) $("#statusns").text( "Trop de paires, maximum " + max_paires_howell + " paires" );
			}
			break;
	}
	//$("#statusdef").html( "En l'état " + okns + " paires" );
}
function checktableauxMitchell() {
	// test Howell possible
	switch ( okeo ) {
		case 0: 
		case -1:
			// tableau incomplet
			$("#statuseo").text( "Tableau EO incomplet" );
			$("#endofdef").addClass( "section_invisible" );
			break;
		default:
			// tableau complet pour tournoi Mitchell si assez de lignes
			if ( okeo >= min_tables_mitchell ) {
				//if ( okeo == nblignesEO ) addligneEO();
				$("#statuseo").text( "Tableau EO complet" );
			}
			else {
				$("#statuseo").text( "Tableau EO incomplet" );
				$("#endofdef").addClass( "section_invisible" );
			}
			break;
	}
	// 
	switch ( okns ) {
		case 0:
		case -1:
			$("#statusns").text( "Tableau NS incomplet" );
			$("#endofdef").addClass( "section_invisible" );
			break;
		default:
			// tableau complet pour tournoi Mitchell si assez de lignes
			if ( Math.max( okns, okeo ) >= min_tables_mitchell ) {
				let delta = okeo - okns;
				switch (delta) {
					case 0:
						$("#statusns").text( "Tableau complet" );
						$("#statuseo").text( "Tableau complet" );
						$("#endofdef").removeClass( "section_invisible" );
						break;
					case 1:		// relais EO
						$("#statusns").text( "Tableau complet" );
						$("#statuseo").text( "Tableau complet, relais EO" );
						$("#endofdef").removeClass( "section_invisible" );
						break;
					case -1:	// relais NS
						if ( (okns == 6)||(okns == 8) ) {
							$("#statusns").text( "Pas de relais NS avec ce nombre de paires !" );
							$("#endofdef").addClass( "section_invisible" );
						}
						else {
							$("#statusns").text( "Tableau complet, relais NS" );
							$("#statuseo").text( "Tableau complet" );
							$("#endofdef").removeClass( "section_invisible" );
						}
						break;
					default:
						if (delta>1) {
							$("#statusns").text( "Manque paire(s) dans le tableau NS" );
							$("#endofdef").addClass( "section_invisible" );
						}
						if (delta<-1){
							$("#statusns").text( "Manque paire(s) dans le tableau EO" );
							$("#endofdef").addClass( "section_invisible" );
						}
						break;
				}
			}
			else {
				$("#statusns").text( "Au moins un tableau incomplet" );
				$("#endofdef").addClass( "section_invisible" );
			};
			break;
	}
}

function scrollPosition( n ) {
	var elmnt;
	if ( parseInt( n % 2 ) > 0 ) { elmnt = document.getElementById("section_tableauns"); }
	else { elmnt = document.getElementById("section_tableaueo"); }
	elmnt.scrollIntoView();
}

function escapePosition( n ) {
	$("#section_clavierns").addClass( "section_invisible" );
	$("#section_tableauns").removeClass( "section_invisible" );
	$("#section_tableaueo").removeClass( "section_invisible" );
	$("#section_tableaux").removeClass( "section_invisible" );
	
	scrollPosition( n );
	return false;
};
function abortPosition() {
	$("#section_clavierns").addClass( "section_invisible" );
	$("#section_tableauns").addClass( "section_invisible" );
	$("#section_tableaueo").addClass( "section_invisible" );
	$("#section_tableaux").addClass( "section_invisible" );
	$("#endofdef").addClass( "section_invisible" );
	
	$("#msgerr").html( "<b>Oups! Tournoi déjà démarré !</b><br/>On ne peut plus modifier la composition des paires." );
	//setTimeout(function() { goto40(); }, 1000);
};

function masquechiffres() {
	for ( let i = 0; i < 10; i++ ) {
		let nr = "#nr_" + i;
		$(nr).addClass( "xtr_invisible" );
	}
}

var txt1 = "Entrez les premières lettres du nom du joueur";
function process_touche( touche ) {
	//console.log( "touche: ", touche );
	switch( touche ) {
		case 'cl': {
			$("#msgclavier").text( txt1 );
			strname = "";
			masquechiffres();
			break;
		}
		case 'bs': {
			$("#msgclavier").text( txt1 );
			let len = strname.length
			strname = strname.slice(0,len-1);
			if ( strname.length > 2 ) lstJoueurs();
			else masquechiffres();
			break;
		}
		case 'ko': {
			escapePosition( position );
			return false;
			break;
		}
		case "ok":
			break;
		case 'gt': {	// guest
			let calcul = parseInt(numpaire) * 4 + parseInt(position) -4;
			if ( position == 1 ) txtpos = "Nord";
			else if ( position == 2 ) txtpos = "Est";
			else if ( position == 3 ) txtpos = "Sud";
			else if ( position == 4 ) txtpos = "Ouest";
			strname = txtpos + " Table " + numpaire;
			console.log( numpaire, position, calcul, strname );
			selectionneJoueur( calcul );
			break;
		}
		case 'nv': {
			// nouveau joueur
			$("#msgclavier").text( "Fonction implémentée" );
			var nextstring = "bridge31.php";
			location.replace( nextstring );
			break;
		}
		case "space":
			touche = " ";
		default:
			strname = strname + touche;
			$("#btnAlphabetic").text( strname );
			if ( strname.length > 2 ) lstJoueurs();
	}		
	$("#btnAlphabetic").text( strname );
	return true;
}

function lstJoueurs() {
	$.get("f41lstjoueurs.php", {strname:strname}, function(strjson) {
		masquechiffres();
		let nbl = strjson.nbl;
		if ( nbl > 0 ) {
			idj  = strjson.idj;
			nomj = strjson.nomcomplet;
			for ( let i = 0; i < Math.min( nbl, 10); i++ ) {
				nr = "#nr_" + i;
				$(nr).removeClass( "xtr_invisible" );
				ndnum = "#num_" + i;
				ndnom = "#nom_" + i;
				$(ndnum).text( idj[i] );
				$(ndnom).text( nomj[i] );
			}
			for ( i = nbl; i < 10; i++ ) {
				nr = "#nr_" + i;
				$(nr).addClass( "xtr_invisible" );
			}
			$("#msgclavier").text( "Cliquez sur le nom du joueur !" );
		}
		else {
			$("#msgclavier").text( "non trouvé !" );
		}
	},"json");
};

function setPosJoueur( place ) {	// Affiche le clavier
	var figs = place.split('_');
	numpaire = figs[1];
	position = figs[2];
	//console.log( "table: " + figs[1] + " position: " + figs[2] );
	
	if ( position == 1 ) txtpos = "Nord";
	else if ( position == 2 ) txtpos = "Est";
	else if ( position == 3 ) txtpos = "Sud";
	else if ( position == 4 ) txtpos = "Ouest";
	var strprompt = 'Joueur en ' + txtpos + ' table ' + numpaire;
	$("#lignetitre1").text( strprompt );
	
	$("#section_tableauns").addClass( "section_invisible" );
	$("#section_tableaueo").addClass( "section_invisible" );
	$("#section_tableaux").addClass( "section_invisible" );
	$("#endofdef").addClass( "section_invisible" );
	
	vp1 = "#idnum_" + numpaire + "_" + position;
	vp2 = "#idnom_" + numpaire + "_" + position;
	console.log( "vp1", vp1, $(vp1).text(), vp2, $(vp2).text() );
	if ( $(vp1).text() !== "0" ) $("#efface").removeClass( "xtr_invisible" );
	else $("#efface").addClass( "xtr_invisible" );
	
	$("#section_clavierns").removeClass( "section_invisible" );
	annuledeplacement();	// annule éventuel permutation de paires
	masquechiffres();
	
	process_touche( "cl" );
}

function selectionneJoueur( idjoueur ) {
	// test id joueur entré identique au précédent en place
	let actuel = $(vp1).text();
	if ( actuel == idjoueur ) {
		escapePosition( position );
	}
	else {
		// sélection joueur parmi la liste des joueurs affichés
		$.get( "f41setjoueur.php",
		{ idtournoi:idtournoi, numpaire:numpaire, position:position, idjoueur:idjoueur },
		function(strjson) {
			console.log( "setjoueur.php?", strjson.reponse );
			if ( strjson.reponse < 0 ) {	// tournoi non en phase préparation
				abortPosition();
			}
			if ( strjson.reponse == 0 ) {	// n° inconnu
				$("#msgclavier").text( "erreur: n° inconnu" );
			}
			if ( strjson.reponse > 0 ) { 		// n° connu
				$(vp1).text( strjson.idjoueur );
				$(vp2).text( strjson.nomcomplet );

				if ( strjson.prevpos > 0 ) {		// déjà placé ailleurs
					let npaire = strjson.prevpaire;
					let nposition = strjson.prevposition;
					let idprev1 = '#idnum_' + npaire + "_" + nposition;
					let idprev2 = '#idnom_' + npaire + "_" + nposition;
	
					$(idprev1).text( "0");
					$(idprev2).text( "" );
				};
				okns = parseInt( strjson.oktabns );
				okeo = parseInt( strjson.oktabeo );
				checktableaux();
				escapePosition( position );
			}
		},"json");
	}
};
function permuteJoueurs( src, dst ) {
	// préparation permutation de l'affichage
	let tsrc = src.substr( 0, 2 );
	let nsrc = src.substr( 2 );
	var srcia, srciaa, srcib, srcibb;
	if ( tsrc == "NS" ) {
		srcia  = "#idnum_" + nsrc + "_1";
		srciaa = "#idnom_" + nsrc + "_1";
		srcib  = "#idnum_" + nsrc + "_3";
		srcibb = "#idnom_" + nsrc + "_3";
	 }
	 else {
		srcia  = "#idnum_" + nsrc + "_2";
		srciaa = "#idnom_" + nsrc + "_2";
		srcib  = "#idnum_" + nsrc + "_4";
		srcibb = "#idnom_" + nsrc + "_4";
	 }

	let tdst = dst.substr( 0, 2 );
	let ndst = dst.substr( 2 );
	var dstia, dstiaa, dstib, dstibb;
	if ( tdst == "NS" ) {
		dstia  = "#idnum_" + ndst + "_1";
		dstiaa = "#idnom_" + ndst + "_1";
		dstib  = "#idnum_" + ndst + "_3";
		dstibb = "#idnom_" + ndst + "_3";
	}
	else {
		dstia  = "#idnum_" + ndst + "_2";
		dstiaa = "#idnom_" + ndst + "_2";
		dstib  = "#idnum_" + ndst + "_4";
		dstibb = "#idnom_" + ndst + "_4";
	}
	
	 // permutation dans la base de données
	$.get( "f41permutejoueurs.php", { idtournoi:idtournoi, src:src, dst:dst },
	function(strjson) {
		//console.log( "permutejoueurs.php?", strjson.reponse );
		if ( strjson.reponse < 0 ) {	// tournoi non en phase préparation
			abortPosition();
		}
		if ( strjson.reponse > 0 ) { 		// ok
			// permutation de l'affichage
			let savia  = $(dstia).text();
			let saviaa = $(dstiaa).text();
			let savib  = $(dstib).text();
			let savibb = $(dstibb).text();
			
			$(dstia).text( $(srcia).text() );
			$(dstiaa).text( $(srciaa).text() );
			$(dstib).text( $(srcib).text() );
			$(dstibb).text( $(srcibb).text() );
			
			$(srcia).text( savia );
			$(srciaa).text( saviaa );
			$(srcib).text( savib );
			$(srcibb).text( savibb );
			
			okns = parseInt( strjson.oktabns );
			okeo = parseInt( strjson.oktabeo );
			checktableaux();
		}
	},"json");
};
function enleveJoueur() {
	 // joueur de la paire x à la position y enlevé du tableau
	$.get( "f41unsetjoueur.php", 
	{ idtournoi:idtournoi, numpaire:numpaire, position:position },
	function(strjson) {
		if ( strjson.reponse < 0 ) {	// tournoi non en phase préparation
			abortPosition();
		}
		if ( strjson.reponse > 0 ) { 		// n° connu
			okns = parseInt( strjson.oktabns );
			okeo = parseInt( strjson.oktabeo );
		
			$(vp1).text( "0" );
			$(vp2).text( " " );
			
			checktableaux();
			escapePosition( position );
		}
	},"json");
};

$(document).keydown(function(event) {
	var touche = event.key;
	if ( touche >= 'a' && touche <= 'z' )
		process_touche( event.key );
	else {
		switch( touche ) {
			case 'Backspace':
				process_touche( "bs" );
				break;
			case 'Escape':
				process_touche( "ko" );
				break;
			case ' ':
				process_touche( " " );
				break;
		}
	}
});
$(document).ready(function() {
	$('td.clkrow').click(function(event) {
		var e = event.target.id;
		var figs = e.split('_');
		var nbr = parseInt( figs[1] );
		console.log( event.target.nodeName, "e", e, "nbr", nbr );
		if ( isNaN(nbr) ) {
			// bug
			$("#msgclavier").text( "td.clkrow bug numero NaN, re-click !" );
			return
		}
		else {
			selectionneJoueur( idj[nbr] );
		}
	});
	
	$('td.clknom').click(function(event) {
		//console.log( event.target.id );
		setPosJoueur( event.target.id );
	});
	
	$('td.clklet').click(function(event) {
		//console.log( event.target.id );
		var figs = event.target.id.split('_');
		var touche = figs[1];
		process_touche( touche );
	});
	
	$('img.clklet').click(function(event) {
		process_touche( "ko" );
	});
	
	// permutation de paires
	$('td.xPaire').click(function( event ) {
		sel = event.target.id;
		idsel = "#" + sel;
		if ( $(idsel).hasClass( "xPairesel" ) ) {
			// annulation déplacement
			src = 0;
			$(idsel).removeClass( "xPairesel" );
			$("#msg").html( html1 );
		}
		else {
			// test src ou dst
			if ( src == 0 ) {
				// click sur 1ère paire
				src = sel;
				$(idsel).addClass( "xPairesel" );
				$("#msg").html( "Paire " + sel + " sélectionnée</br>Sélectionnez la 2ème paire</br>pour permuter les joueurs</br>ou recliquez sur la 1ère paire pour annuler" );
			}
			else {
				// click sur 2ème paire
				dst = sel;
				$(idsel).addClass( "xPairesel" );
				$("#msg").html( "2ème paire " + sel + " sélectionnée</br>Permutation des joueurs ..." );
				permuteJoueurs( src, dst );
				// à la fin
				idsrc = "#" + src;
				$(idsrc).removeClass( "xPairesel" );
				$(idsel).removeClass( "xPairesel" );
				src = 0;
				dst = 0;
				$("#msg").html( html1 );
			}
		}
	});
});
