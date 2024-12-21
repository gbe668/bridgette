//
// fonctions pré-inscription
//
var max_tables = parseInt( "<?php echo $max_tables; ?>" );
var tableauInscrits = [];

//
// paramètres de sélection date tournoi: datepicker, ...
//
const listeJours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' ];
const listeMois = [ "zéro", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" ];
function strdatet( d ) {	// d au format aaaa-mm-jj
	let j = d.slice( 8, 10 );
	let m = parseInt(d.slice( 5, 7 ));
	let a = d.slice( 0, 4 );
	let mm = listeMois[m];
	return( j+" "+mm+" "+a );
};
var tournoi, idtournoi;
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
	minDate: new Date(),
	maxDate: '+4W',	//new Date(),	//'+12M +0D',
	//showButtonPanel: true,
	isRTL: false
};
$.datepicker.setDefaults( $.datepicker.regional['fr'] );
$(document).ready(function() {		// sélection date tournoi
	datetournoi = $( "#datetournoi" ).datepicker({	// initialisation
		//var dateFormat = "mm/dd/yy",
		//defaultDate: +1,
		//numberOfMonths: 1
	})
	.datepicker('setDate', 'today')
	.datepicker( "option", "maxDate", '+4w' )
	.datepicker( "option", "beforeShowDay", function (date){
		let dd = date.getDay();		// de 0 (dimanche) à 6 (samedi)
		let jour = listeJours[dd];
		if ( parametres.opendays[jour] == '1' )
			return [ true, "", "" ]
		else
			return [ false, "closed", "Pas de tournoi ce jour" ]
	})
	.on( "change", function() {
		console.log( "change", $("#datetournoi").val() );
		selectTournoi( $("#datetournoi").val() );
	});
});
function selectTournoi( tournoi ) {
	tournoi = $("#datetournoi").val();
	$.get( relpgm+"inscriptiontournoi.php", { datetournoi:tournoi }, function(strjson) {
		//console.log( "id", strjson.idtournoi, "ret", strjson.ret, "lignes", strjson.lignes );
		idtournoi = strjson.idtournoi;
		$("#tabdujour").text( strdatet(tournoi) );
		$("#msgdatetournoi").html( strjson.ret );
		if ( idtournoi > 0 ) {
			updateTabInscrits( strjson.lignes );
			$("#section_tableau").show();
			$("#section_inscription").show();
		}
	},"json");
}
//
// affichage tableau des inscrits pour le tournoi sélectionné
//
var userinscrit;
function affiche_inscription() {
	$("#section_inscription").toggle();
}
function updateTabInscrits( lignes ) {
	tableauInscrits = lignes;
	
	let n = lignes.length;
	let str = '<table border="1" style="width:95%; max-width: 350px; margin:auto;">';
	str += '<tbody>';
	str += "<tr><td class='xTitre'>Joueurs pré-inscrits</td></tr>";
	for  ( i = 0; i < n; i++) {
		let ligne = lignes[i];
		let rowi = "ligne_"+i;
		
		if ( ligne.A.id > 0 ) {
			if ( (ligne.B.id > 0)||(ligne.A.id == userid) ) {
				str += '<tr id="' + rowi + '" >';
			}
			else {
				str += '<tr id="' + rowi + '" style="background-color:lightblue;" >';
			}
			if (ligne.B.id > 0) {
				str += '<td class="xNom">';
				str += ligne.A.nomcomplet + '</br>' + ligne.B.nomcomplet +'</td>';
			}
			else {
				str += '<td class="xNom clknom">';
				str += ligne.A.nomcomplet + '</br><em>cherche partenaire</em></td>';
			}
			str += '</tr>';
		}
		else {
			str += '<tr id="' + rowi + '" hidden >';
		}
	};
	str += "</tbody></table>";
	$("#tabinscrits").html( str );
	$("#msgtabinscrits").text( "" );
	
	$("#menu_noninscrit").show();
	$("#menu_inscrit").hide();
	userinscrit = false;
	// test si les joueurs figurent déjà dans une paire inscrite
	for  ( let i = 0; i < n; i++) {
		let ligne = tableauInscrits[i];
		if ( ligne.A.id == userid ) {
			$("#menu_noninscrit").hide();
			$("#menu_inscrit").show();
			userinscrit = true;
			break;
		}
		if ( ligne.B.id == userid ) {
			$("#menu_noninscrit").hide();
			$("#menu_inscrit").show();
			break;
		}
	}
	
}
$(document).on( "click", "td.clknom", function(event) {
	let id = $(this).parent().attr("id");
	console.log( "td.clknom parent", id );
	const figs = id.split('_');
	contactInscrit( figs[1] );
});
function sans_partenaire() {
	$("#section_clavier").hide();
	selPartenaire( 0 );
}
function avec_partenaire() {
	$("#msgclavier").text( txt1 );
	$("#section_clavier").show();
	elmnt = document.getElementById("section_tableau");
	elmnt.scrollIntoView();
}
function selPartenaire(idPart) {
	let cmd = ( userinscrit ) ? "mod" : "add";
	$.get( relpgm+"f25setpaire.php", { idtournoi:idtournoi, cmd:cmd, ida:userid, idb:idPart }, function(strjson) {
		console.log( strjson.ret );
		if ( strjson.ret == "ok" ) {
			// mise à jour tableau des inscrits
			updateTabInscrits( strjson.lignes );
		}
		else {
			$("#msgtabinscrits").text("La pré-inscription n'est plus possible !");
		}
	},"json")
	.fail( function( jqxhr,settings,ex ) {
		$("#msgtabinscrits").html('Erreur: '+ ex );
		console.log( cmd + " fail" );
	} );
};
function annule_inscription() {
	$.get( relpgm+"f25setpaire.php", { idtournoi:idtournoi, cmd:"del", ida:userid, idb:0 }, function(strjson) {
		console.log( strjson.ret );
		if ( strjson.ret == "ok" ) {
			// mise à jour tableau des inscrits
			updateTabInscrits( strjson.lignes );
		}
		else {
			$("#msgtabinscrits").text("Voyez avec le directeur de tournoi !");
		}
	},"json")
	.fail( function( jqxhr,settings,ex ) {
		$("#msgtabinscrits").html('Erreur: '+ ex );
		console.log( "del fail" );
	} );
	$("#section_clavier").hide();
}
function contactInscrit(k) {
	let ligne = tableauInscrits[k];
	let contact = ligne.A.nomcomplet;
	console.log( contact );
	if ( userid > 0 ) {
		$("#msgtabinscrits").html( "Contactez "+contact+" au "+ligne.A.telephone+"</br>ou par mail: "+ligne.A.email );
		/*
		$.get( relpgm+"getfiche.php", { idj:ligne.A.id }, function(fiche) {
			console.log(fiche);
			$("#msgtabinscrits").html( "Contactez "+contact+" au "+fiche.telephone+"</br>ou par mail: "+fiche.email );
		},"json")
		.fail( function( jqxhr,settings,ex ) {
			$("#msgtabinscrits").html('Erreur: '+ ex );
			console.log( "getfiche fail" );
		} );
		*/
	}
	else {
		$("#msgtabinscrits").html( "Connectez vous pour voir les moyens de contacter "+contact );
	}
}
//
// saisie nom du joueur
//
var txt1 = "Nom de votre partenaire ?";
var strname ="";
var tabidj = [];			// tableau des id joueurs
function masqueUsers() {
	for ( let i = 0; i < 10; i++ ) {
		let nr = "#nr_" + i;
		$(nr).hide();
	}
}
function lstJoueurs() {
	$.get( relpgm+"f41lstjoueurs.php", {strname:strname }, function(strjson) {
		masqueUsers();
		let nbl = strjson.nbl;
		if ( nbl > 0 ) {
			tabidj  = strjson.idj;
			let nomj = strjson.nomcomplet;
			for ( let i = 0; i < Math.min( nbl, 10); i++ ) {
				let nr = "#nr_" + i;
				$(nr).show();
				let ndnum = "#num_" + i;
				let ndnom = "#nom_" + i;
				$(ndnum).text( tabidj[i] );
				$(ndnom).text( nomj[i] );
			}
			for ( i = nbl; i < 10; i++ ) {
				let nr = "#nr_" + i;
				$(nr).hide();
			}
			$("#msgclavier").text( "Cliquez sur le nom du joueur !" );
		}
		else {
			$("#msgclavier").text( "non trouvé !" );
		}
	},"json")
	.fail( function( jqxhr,settings,ex ) {
		$("#msgclavier").html('Erreur: '+ ex );
		console.log( "f41lstjoueurs fail" );
	} );
};
function displayClavierSaisieJoueur() {
	let str = '<table style="width:100%; max-width: 350px; margin:auto;">';
	str += '<tbody>';
	/*
	str += '<tr>';
	str += '<td colspan="2" class="xNum2 clklet" id="n_ko"><img src="images/ko.png" alt="ko" height="30" class="clklet"/></td>';
	str += '<td>&nbsp;</p>';
	str += '<td colspan="3" class="xNum2 clklet" id="n_gt">Invité</td>';
	str += '<td colspan="4" class="xNum2 clklet" id="n_nv">Nouveau</td>';
	str += '</tr>';
	str += '<tr id="efface" class="xtr_invisible">';
	str += '<td>&nbsp;</p>';
	str += '<td>&nbsp;</p>';
	str += '<td>&nbsp;</p>';
	str += '<td colspan="7" class="xNum2 xNumSmall"><div onclick="enleveJoueur()">Efface joueur en place</div></td>';
	str += '</tr>';
	*/
	str += '<tr><td colspan="10" class="xDigit" style="border: 1px solid black"><div id="btnAlphabetic">&nbsp;</div></td></tr>';
	str += '<tr>';
	str += '<td colspan="10" class="xNum">';
	
	str += '<table style="width:100%; margin:auto;"><tbody>';
	// tableau des joueurs
	for  ( i=0; i<10; i++) {
		let nr = "nr_"+i;
		let ndnum = "num_"+i;
		let ndnom = "nom_"+i;
		str += '<tr id="'+nr+'" class="xtrsel" hidden>';
		str += '<td class="xTxt2 xtd_invisible" id="'+ndnum+'">numéro</td>';
		str += '<td class="xTxt2 clkrow" id="'+ndnom+'">nom du joueur</td>';
		str += '</tr>';
		};
	str += "</tbody></table>";
	
	str += '</td>';
	str += '</tr>';
	str += '<tr><td colspan="10"><span id="msgclavier">&nbsp;</span></td></tr>';
	str += '<tr>';
	str += '<td class="xNum2 clklet" id="n_a">a</td>';
	str += '<td class="xNum2 clklet" id="n_z">z</td>';
	str += '<td class="xNum2 clklet" id="n_e">e</td>';
	str += '<td class="xNum2 clklet" id="n_r">r</td>';
	str += '<td class="xNum2 clklet" id="n_t">t</td>';
	str += '<td class="xNum2 clklet" id="n_y">y</td>';
	str += '<td class="xNum2 clklet" id="n_u">u</td>';
	str += '<td class="xNum2 clklet" id="n_i">i</td>';
	str += '<td class="xNum2 clklet" id="n_o">o</td>';
	str += '<td class="xNum2 clklet" id="n_p">p</td>';
	str += '</tr>';
	str += '<tr>';
	str += '<td class="xNum2 clklet" id="n_q">q</td>';
	str += '<td class="xNum2 clklet" id="n_s">s</td>';
	str += '<td class="xNum2 clklet" id="n_d">d</td>';
	str += '<td class="xNum2 clklet" id="n_f">f</td>';
	str += '<td class="xNum2 clklet" id="n_g">g</td>';
	str += '<td class="xNum2 clklet" id="n_h">h</td>';
	str += '<td class="xNum2 clklet" id="n_j">j</td>';
	str += '<td class="xNum2 clklet" id="n_k">k</td>';
	str += '<td class="xNum2 clklet" id="n_l">l</td>';
	str += '<td class="xNum2 clklet" id="n_m">m</td>';
	str += '</tr>';
	str += '<tr>';
	str += '<td>&nbsp;</td>';
	str += '<td>&nbsp;</td>';
	str += '<td class="xNum2 clklet" id="n_w">w</td>';
	str += '<td class="xNum2 clklet" id="n_x">x</td>';
	str += '<td class="xNum2 clklet" id="n_c">c</td>';
	str += '<td class="xNum2 clklet" id="n_v">v</td>';
	str += '<td class="xNum2 clklet" id="n_b">b</td>';
	str += '<td class="xNum2 clklet" id="n_n">n</td>';
	str += '<td>&nbsp;</td>';
	str += '<td>&nbsp;</td>';
	str += '</tr>';
	str += '<tr>';
	str += '<td colspan="2" class="xNum2 clklet" id="n_cl">Clear</td>';
	str += '<td>&nbsp;</td>';
	str += '<td colspan="4" class="xNum2 clklet" id="n_space">espace</td>';
	str += '<td>&nbsp;</td>';
	str += '<td colspan="2" class="xNum2 clklet" id="n_bs">&larr;</td>';
	str += '</tr>';
	str += '</tbody>';
	str += '</table>';
	return str;
};
function process_touche( touche ) {
	//console.log( "touche: ", touche );
	switch( touche ) {
		case 'cl': {
			$("#msgclavier").text( txt1 );
			strname = "";
			masqueUsers();
			break;
		}
		case 'bs': {
			$("#msgclavier").text( txt1 );
			let len = strname.length
			strname = strname.slice(0,len-1);
			if ( strname.length > 2 ) lstJoueurs();
			else masqueUsers();
			break;
		}
		case 'ko': {
			escapePosition( position );
			return false;
			break;
		}
		case "ok":
			break;
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

//
// formulation utilisée pour le chargement de données statiques
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
			$("#section_clavier").hide();
			selPartenaire( tabidj[nbr] );
		}
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
});

//
// affichage annuaire
function affiche_annuaire() {
	$("#section_annuaire").toggle();
}
function masque_annuaire() {
	$("#section_annuaire").hide();
}
//
// routines mise à jour téléphone
//
function isNumeric(str) {
  return /^(\s*[0-9-]+\s*)+$/.test(str);
}
function oktel() {
	let phone = $("#phone").val();
	phone = phone.replaceAll( '-', ' ');
	phone = phone.trim();
	$("#phone").val( phone );
	if ( (phone.length > 0)&&(!isNumeric( phone)) ) {
		$("#msgperso").text( "Téléphone: caractères non numériques" );
	}
	else {
		$.get( relpgm+"f30updatetel.php", { idjoueur:userid, phone:phone },
		function(strjson) {
			$("#msgperso").text( strjson.msg );
		}, "json");
	}
	setTimeout(function() { $("#msgperso").text( " " ); }, 2000);
}
