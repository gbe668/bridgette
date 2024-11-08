var testdelai_1 = 2000;	//délai entrée résultat
var testdelai_2 = 1000;	//acceptation résultat par Est

// constantes définies dans bridge_bdd.php
const col_indefini = 0;
const col_trefle = 1;
const col_carreau = 2;
const col_coeur = 3;
const col_pique = 4;
const col_sansatout = 5;

const dbl_indefini = 0;
const dbl_simple = 1;
const dbl_contre = 2;
const dbl_surcontre = 3;

const pos_indefini = 0;
const pos_Nord = 1;
const pos_Est = 2;
const pos_Sud = 3;
const pos_Ouest = 4;

const txtcolors		= Array( "?","T","K","C","P","SA" );
const txtdouble		= Array( "?","","X","XX" );
const txtpositions	= Array( "?","N","E","S","O" );
const txtentames	= Array( "?","?","2","3","4","5","6","7","8","9","10","V","D","R","A" );

function txtCol( col ) {
	return txtcolors[col];
}
function txtDbl( dbl ) {
	return txtdouble[dbl];
}
function txtPos( pos ) {
		return txtpositions[pos];
	}
function txtEnt( ent ) {
		return txtentames[ent];
	}

function invtxtCol( txt ){
	let i = txtcolors.indexOf( txt ); 
	if ( i < 0 ) i =0;
	return i;
}

function imgCol( col ) {
	return imgColX( col, 13 );
}
function imgColX( cc, x ) {
	let html;
	let srcimg = "<img src='"+relimg;
	switch(cc) {
		case (col_sansatout): 	html = srcimg+"sans-atout.png' height='" + x +"' />"; break;
		case (col_pique):		html = srcimg+"pique.png' height='" + x +"' />"; break;
		case (col_coeur):		html = srcimg+"coeur.png' height='" + x +"' />"; break;
		case (col_carreau): 	html = srcimg+"carreau.png' height='" + x +"' />"; break;
		case (col_trefle):		html = srcimg+"trefle.png' height='" + x +"' />"; break;
		case (col_indefini): 	html = "??"; break;
	}
	return html;
}
function imgDbl( dbl ) {
		let html = "";
		switch(dbl) {
			case (dbl_indefini): 	html = "??"; break;
			case (dbl_simple):		html = ""; break;
			case (dbl_contre): 		html = "X"; break;
			case (dbl_surcontre): 	html = "XX"; break;
		}
		return html;
	}
function imgPos( pos ) {
		let html = "";
		switch(pos) {
			case (pos_indefini): 	html = "--"; break;
			case (pos_Nord): 		html = "Nord"; break;
			case (pos_Est): 		html = "Est"; break;
			case (pos_Sud): 		html = "Sud"; break;
			case (pos_Ouest): 		html = "Ouest"; break;
		}
		return html;
	}
function imgEnt( niv, col ) {
	let html = txtentames[niv];
	let srcimg = "<img src='"+relimg;
	switch(col) {
		case (col_pique):		html += srcimg+"pique.png' height='13' />"; break;
		case (col_coeur):		html += srcimg+"coeur.png' height='13' />"; break;
		case (col_carreau): 	html += srcimg+"carreau.png' height='13' />"; break;
		case (col_trefle):		html += srcimg+"trefle.png' height='13' />"; break;
		case (col_indefini): 	html += " ?"; break;
	}
	return html;
}

function calculpointscontrat( vh, vc, vd, vul, vr ) {
	var vr1, vr2, vrh, vhd, pts;
	// vh = niveauContrat;
	// vc = couleur
	// vd = contreContrat;
	// vul = vulnerable;
	// vr = resultatContrat;
	if ( vh == 0 ) { // passe général
		pts = 0;
	}
	else {
		if ( vr < 0 ) {
			// contrat chuté
			vr1 = -vr;
			if (vd == dbl_simple) {	// non contré
				if (vul == 1) pts = vr1*100; else pts = vr1*50;
				};
			if ( vr1 > 3 ) {
				vr2 = vr1 - 3;
				vr1 = 3;
			} else {
				vr2 = 0;
			}
			if (vd == dbl_contre) { // contré
				if (vul == 1) pts = vr1*300 + vr2*300 -100; 
				else pts = vr1*200 + vr2 * 300 - 100;
				};
			if (vd == dbl_surcontre) { // surcontré
				if (vul == 1) pts = vr1*600 + vr2*600 -200; 
				else pts = vr1*400 + vr2 * 600 - 200;
				};
			pts = -pts;
		}
		else {
			// contrat réalisé
			// vc = couleurContrat;
			if (vc == col_sansatout) { leveesa = 10; levee = 30; } else { leveesa = 0; };
			if ( (vc == col_coeur)  || (vc == col_pique) ) { levee = 30; };
			if ( (vc == col_trefle) || (vc == col_carreau) ) { levee = 20; };
			pts = levee * vh + leveesa;
			if (vd == 1) { vhd = 1; };
			if (vd == 2) { vhd = 2; };
			if (vd == 3) { vhd = 4; };
			pts = pts * vhd;
			
			if (pts < 100) {
				pts += 50;	// prime de contrat réussi
			} else {	// ajout points de manche
				if (vul == 1) pts += 500; else pts += 300;
				if (vh == 6) { // prime de petit chelem
					if (vul == 1) pts += 750; else pts += 500;
				}
				if (vh == 7) { // prime de grand chelem
					if (vul == 1) pts += 1500; else pts += 1000;
				}
			}
			// primes de contrat contré ou surcontré
			if (vd == dbl_contre) pts += 50;
			if (vd == dbl_surcontre) pts += 100;
			// levées en sus
			if ( vr > 0 ) {
				if (vd == dbl_simple) pts += vr * levee;
				if (vd == dbl_contre) {
					if (vul == 1 ) pts += vr * 200; else pts += vr * 100;
				};
				if (vd == dbl_surcontre) {
					if (vul == 1 ) pts += vr * 400; else pts += vr * 200;
				};
			};
		};
	};
	return pts;
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
const st_position = Array( "???", "Nord", "Est", "Sud", "Ouest" );
const st_vulnerable = Array( "Personne vulnérable", "Nord-Sud vulnerables", "Est-Ouest vulnerables", "Tous vulnérables" );

class Donnejouee {
	constructor(etui) {
		// section contrat
		this.niv = -1;				// non défini
		this.col = col_indefini;
		this.dbl = dbl_simple;
		this.pos = pos_indefini;	// déclarant
		this.contrat = null;		// pour stokage bdd
		this.declarant = null;		// pour stokage bdd
		// section entame
		this.entameur = pos_indefini;
		this.ent1 = 0;	// niv entame
		this.ent2 = 0;	// col entame
		this.entame = null;		// pour stokage bdd
		// section resultat
		this.res = 0;
		this.resok = false;	// résultat non défini
		this.points = 0;
		// donneur vulnérabilité
		this.setDonneurVul(etui);
	}
	// routines d'initialisation affichage
	setDonneurVul(etui) {
		this.etui = etui;
		if ( etui == 0 ) {
			// calcul de la marque, valeurs par défaut
			this.selAction("vul_0");	// non vulnérable
			this.selAction("pos_1");	// déclarant Nord
			this.selAction("ent_10_4");	// entame 10 de pique
		}
		else {
			this.donneur = etuis[etui][1];
			this.vulns   = etuis[etui][2];
			this.vuleo   = etuis[etui][3];
			this.texteDonneur = st_position[ this.donneur ] + " donneur - " + st_vulnerable[ this.vulns+2*this.vuleo ];
			$("#texteDonneur").html( this.texteDonneur );
		
			let colorns = etuis[etui][2] ? "red" : "green";
			let coloreo = etuis[etui][3] ? "red" : "green";
			$("#etui").css('border-top-color', colorns )
				.css('border-left-color', coloreo )
				.css('border-right-color', coloreo )
				.css('border-bottom-color', colorns );
		}
	}
	clearniv() {
		this.niv = 0;
		this.col = col_indefini;
		// raz bon numero
		$("#pas_0_passe").removeClass( "bonnumero" );
		$("#dnj_0_nonjoue").removeClass( "bonnumero" );
		for (let i = 1; i < 8; i++) {
			let idc5 = "#niv_" + i + "_5";
			let idc4 = "#niv_" + i + "_4";
			let idc3 = "#niv_" + i + "_3";
			let idc2 = "#niv_" + i + "_2";
			let idc1 = "#niv_" + i + "_1";
		$(idc5).removeClass( "bonnumero" );
		$(idc4).removeClass( "bonnumero" );
		$(idc3).removeClass( "bonnumero" );
		$(idc2).removeClass( "bonnumero" );
		$(idc1).removeClass( "bonnumero" );
		}
	}
	cleardbl() {
		this.dbl = dbl_indefini;
		// raz bon numero
		$("#dbl_1").removeClass( "bonnumero" );
		$("#dbl_2").removeClass( "bonnumero" );
		$("#dbl_3").removeClass( "bonnumero" );
	}
	clearpos() {
		this.pos = pos_indefini;	// déclarant
		// raz bon numero
		$("#pos_1").removeClass( "bonnumero" );
		$("#pos_2").removeClass( "bonnumero" );
		$("#pos_3").removeClass( "bonnumero" );
		$("#pos_4").removeClass( "bonnumero" );
	}
	clearent() {
		this.ent1 = 0;
		this.ent2 = 0;
		this.ent = pos_indefini;	// déclarant
		for  (let i = 14; i > 1; i--) {
			let idc4 = "#ent_" + i + "_4";
			let idc3 = "#ent_" + i + "_3";
			let idc2 = "#ent_" + i + "_2";
			let idc1 = "#ent_" + i + "_1";
			$(idc4).removeClass( "bonnumero" );
			$(idc3).removeClass( "bonnumero" );
			$(idc2).removeClass( "bonnumero" );
			$(idc1).removeClass( "bonnumero" );
		}
	}
	clearres() {
		this.res = 0;
		this.resok = false;	// résultat non défini
		$("#res_p_0").removeClass( "bonnumero" );
	
		$("#res_p_1").removeClass( "bonnumero" );
		$("#res_p_2").removeClass( "bonnumero" );
		$("#res_p_3").removeClass( "bonnumero" );
		$("#res_p_4").removeClass( "bonnumero" );
		$("#res_p_5").removeClass( "bonnumero" );
		$("#res_p_6").removeClass( "bonnumero" );
	
		$("#res_m_1").removeClass( "bonnumero" );
		$("#res_m_2").removeClass( "bonnumero" );
		$("#res_m_3").removeClass( "bonnumero" );
		$("#res_m_4").removeClass( "bonnumero" );
		$("#res_m_5").removeClass( "bonnumero" );
		$("#res_m_6").removeClass( "bonnumero" );
		$("#res_m_7").removeClass( "bonnumero" );
		$("#res_m_8").removeClass( "bonnumero" );
		$("#res_m_9").removeClass( "bonnumero" );
		$("#res_m_10").removeClass( "bonnumero" );
		$("#res_m_11").removeClass( "bonnumero" );
		$("#res_m_12").removeClass( "bonnumero" );
		$("#res_m_13").removeClass( "bonnumero" );
	}
	dspResultat() {
		// ne visualiser que les résultats possibles en fonction du contrat demandé
		$("#dsp_p1").removeClass( "section_invisible" );
		$("#dsp_p2").removeClass( "section_invisible" );
		$("#dsp_p3").removeClass( "section_invisible" );
		$("#dsp_p4").removeClass( "section_invisible" );
		$("#dsp_p5").removeClass( "section_invisible" );
		$("#dsp_p6").removeClass( "section_invisible" );
	
		$("#dsp_m13").addClass( "section_invisible" );
		$("#dsp_m12").addClass( "section_invisible" );
		$("#dsp_m11").addClass( "section_invisible" );
		$("#dsp_m10").addClass( "section_invisible" );
		$("#dsp_m9").addClass( "section_invisible" );
		$("#dsp_m8").addClass( "section_invisible" );
	
		switch( this.niv ) {
			case 7: $("#dsp_p1").addClass( "section_invisible" );
			case 6: $("#dsp_p2").addClass( "section_invisible" );
			case 5: $("#dsp_p3").addClass( "section_invisible" );
			case 4: $("#dsp_p4").addClass( "section_invisible" );
			case 3: $("#dsp_p5").addClass( "section_invisible" );
			case 2: $("#dsp_p6").addClass( "section_invisible" );
		}
		switch( this.niv ) {
			case 7: $("#dsp_m13").removeClass( "section_invisible" );
			case 6: $("#dsp_m12").removeClass( "section_invisible" );
			case 5: $("#dsp_m11").removeClass( "section_invisible" );
			case 4: $("#dsp_m10").removeClass( "section_invisible" );
			case 3: $("#dsp_m9").removeClass( "section_invisible" );
			case 2: $("#dsp_m8").removeClass( "section_invisible" );
			}
	}
	imgContrat() {
		return ( "Contrat " + this.niv + " " + imgCol(this.col) + " " + imgDbl(this.dbl) + " joué par " + imgPos(this.pos) );
	}
	shortContrat() {
		let big = 16;
		return ( this.niv + " " + imgColX(this.col, big) + " " + imgDbl(this.dbl) );
	}
	shortEntame() {
		let big = 16;
		return ( txtentames[this.ent1] + imgColX(this.ent2, big) );
	}
	imgEntame() {
		return( imgPos( this.entameur ) + " entame " + imgEnt( this.ent1, this.ent2 ) );
	}
	shortResultat() {
		let s_resultat;
		if ( this.res == 0 ) s_resultat = "égal";
		else {
			if ( this.res > 0 ) s_resultat = "+" + this.res;
			else s_resultat = this.res;
		}
		return( s_resultat );
	}
	imgResultat() {
		return( "Résultat " + this.shortResultat() );
	}
	resetDonne() {
		this.clearniv();
		this.cleardbl();
		this.clearpos();
		this.clearent();
		this.clearres();
		// valeurs par défaut
		this.dbl = dbl_simple;
		$("#dbl_1").addClass( "bonnumero" );
		this.entame  = "-";
		$("#entame1").html( "Entrez l'entame" );
		this.declarant = "-";
		this.res = 0;
		$("#resultat1").html( "Entrez le résultat" );
	}
	// routines utilisées pour corriger une donne
	setContrat( txt ) {
		// décode contrat au format bdd
		const figs = txt.split(' ');
		if ( figs[0] == "P" ) {
			// passe général
			this.selAction( "pas_0_passe" );
			return;
		}
		let idniv = "niv_"+ parseInt( figs[0] ) + "_" + invtxtCol( figs[1] );
		this.selAction( idniv );
		
		let iddbl;
		if ( figs[2] == "XX" ) iddbl = "dbl_3";
		else {
			if ( figs[2] == "X" ) iddbl = "dbl_2";
			else iddbl = "dbl_1";
			}
		this.selAction( iddbl );		
	}
	setDeclarant( txt ) {
		let idpos = txtpositions.indexOf( txt );
		if ( idpos > 0 ) this.selAction( "pos_"+idpos );
	}
	setEntame( txt ) {
		//console.log( txt, txt[0], txt[1] );
		let s = txt.length;
		if ( s < 2 ) return;
		if ( s == 2 )
			this.selAction( "ent_" + txtentames.indexOf(txt[0]) + "_" + txtcolors.indexOf(txt[1]) );
		if ( s == 3 )
			this.selAction( "ent_" + txtentames.indexOf(txt.slice(0,2)) + "_" + txtcolors.indexOf(txt[2]) );
	}
	setResultat( txt ) {
		//console.log( txt, txt[0], txt[1] );
		let s = parseInt( txt );
		if ( s < 0 ) this.selAction( "res_p_" + s );
		else {
			s = -s;
			this.selAction( "res_m_" + s );
		}
	}
	// routines de test
	isContratOK() {
		let ok = (this.niv == 0) || ( (this.niv > 0)&&(this.dbl > 0)&&(this.pos > 0) );
		if ( ok ) {
			$("#ok_contrat").removeClass( "kotogoon" );
			$("#ok_contrat").addClass( "oktogoon" );
		} else {
			$("#ok_contrat").removeClass( "oktogoon" );
			$("#ok_contrat").addClass( "kotogoon" );
		};
		return ok;
	}
	isEntameOK() {
		let ok = (this.niv == 0) || ( (this.entameur > 0)&&(this.ent1 > 0)&&(this.ent2 > 0) );
		if ( ok ) {
			$("#ok_entame").removeClass( "kotogoon" );
			$("#ok_entame").addClass( "oktogoon" );
		} else {
			$("#ok_entame").removeClass( "oktogoon" );
			$("#ok_entame").addClass( "kotogoon" );
		};
		return ok;
	}
	isResultatOK() {
		let ok = (this.niv == 0) || ( (this.entameur > 0)&&(this.ent1 > 0)&&(this.ent2 > 0)&&this.resok );
		if ( ok ) {
			$("#ok_resultat").removeClass( "kotogoon" );
			$("#ok_resultat").addClass( "oktogoon" );
		} else {
			$("#ok_resultat").removeClass( "oktogoon" );
			$("#ok_resultat").addClass( "kotogoon" );
		};
		return ok;
	}
	// dispatch
	selAction( id ) {
		const figs = id.split('_');
		//console.log( "ID " + figs[0] + " arg1: " + figs[1] + " arg2: " + figs[2] );
		var pid = "#" + id;
		switch( figs[0] ) {
			// section contrat
			case 'pas':	{
				this.resetDonne();
				// passe général
				this.contrat = "P G";
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#contrat1").html( "Passe général" );
				$("#section_contrat_joue").hide();
				break;
			}
			case 'dnj':	{
				this.resetDonne();
				// donne non jouée
				this.contrat = "N J";
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#contrat1").html( "Donne non jouée" );
				$("#section_contrat_joue").hide();
				break;
			}
			case 'niv':	{
				// annonce
				this.clearniv();
				this.niv = parseInt( figs[1] );
				this.col = parseInt( figs[2] );
				this.contrat = this.niv + " " + txtCol(this.col) + " " + txtDbl(this.dbl);
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#contrat1").html( this.imgContrat() );
				// $("#resultat1").html( "Entrez le résultat" );
				$("#section_contrat_joue").show();
				this.dspResultat();
				break;
			}
			case 'dbl':	{
				// contre ...
				this.cleardbl();
				this.dbl = parseInt( figs[1] );
				this.contrat = this.niv + " " + txtCol(this.col) + " " + txtDbl(this.dbl);
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#contrat1").html( this.imgContrat() );
				break;
			}
			case 'pos':	{
				// déclarant
				this.clearpos();
				this.pos = parseInt( figs[1] );
				this.declarant = txtPos( this.pos );
				if ( this.pos == pos_Ouest ) this.entameur = pos_Nord;
				else this.entameur = this.pos + 1;
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#contrat1").html( this.imgContrat() );
				$("#entame1").html( this.imgEntame() );
				break;
			}
			case 'vul':	{
				// vulnérabilité pour calcul de la marque en solo
				$("#vul_0").removeClass( "bonnumero" );
				$("#vul_1").removeClass( "bonnumero" );
				this.pos = pos_Nord;			// pour le calcul de la vunérabilité en solo
				this.vulns = parseInt( figs[1] );
				// affichage
				$(pid).addClass( "bonnumero" );
				break;
			}
			// section entame
			case 'ent':	{
				this.clearent();
				this.ent1 = parseInt( figs[1] );
				this.ent2 = parseInt( figs[2] );
				this.entame = txtEnt(this.ent1) + txtCol(this.ent2);
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#entame1").html( this.imgEntame() );
				this.closeEntame();
				break;
			}
			// section résultat
			case 'res':	{
				this.clearres();
				this.res = parseInt( figs[2] );
				if ( figs[1] == "m" ) this.res = -this.res;
				this.resok = true;
				// affichage
				$(pid).addClass( "bonnumero" );
				$("#resultat1").html( this.imgResultat() );
				this.closeResultat();
				break;
			}
			case 'etui': {
				this.setDonneurVul( parseInt( figs[1] ) );
				$("#etui").text(this.etui);
				$('#section_etuis').addClass( 'section_invisible' );
				break;
			}
			default: {
				console.log( "Erreur ID " + figs[0] + " arg1: " + figs[1] + " arg2: " + figs[2] ); 
			}
		}
		// test complétude des infos
		let ok = this.isContratOK() && this.isEntameOK() && this.isResultatOK();
		if ( ok ) {
			let html = "<span class='xDigit'><b>";
			if ( this.contrat == "N J" ) {
				this.points = 0;
				html += "Donne non jouée</b></span>";
			}
			else if ( this.contrat == "P G" ) html += "Passe général</b></span>";
			else {
				this.calculpoints();
				html += imgPos(this.pos)+": "+this.shortContrat()+" "+this.shortResultat()+"<br/>";
				html += "Points NS: " + this.points + "</b></span>";
			}
			$("#section_validation").removeClass( "section_invisible" );
			$("#textePoints").html( html );
		}
		else {
			$("#section_validation").addClass( "section_invisible" );
		}
	}
	openContrat() {
		$("#section_contrat").removeClass( "section_invisible" );
		var elmnt = document.getElementById("section_contrat");
		elmnt.scrollIntoView();
		this.isContratOK();
	}
	closeContrat() {
		if ( this.isContratOK() ) {
			$("#section_contrat").addClass( "section_invisible" );
			if ( !this.isEntameOK() ) {
				$("#section_entame").removeClass( "section_invisible" );
				var elmnt = document.getElementById("section_entame");
				elmnt.scrollIntoView();
			}
		}
	}
	openEntame() {
		$("#section_entame").removeClass( "section_invisible" );
		var elmnt = document.getElementById("section_entame");
		elmnt.scrollIntoView();
		this.isEntameOK();
	}
	closeEntame() {
		if ( this.isEntameOK() ) {
			$("#section_entame").addClass( "section_invisible" );
			if ( !this.isResultatOK() ) {
				$("#section_resultat").removeClass( "section_invisible" );
				var elmnt = document.getElementById("section_resultat");
				if ( elmnt !== null ) elmnt.scrollIntoView();
			}
		}
	}
	openResultat() {
		$("#section_resultat").removeClass( "section_invisible" );
		this.dspResultat();
		var elmnt = document.getElementById("section_resultat");
		elmnt.scrollIntoView();
		this.isResultatOK();
	}
	closeResultat() {
		if ( this.isResultatOK() ) {
			$("#section_resultat").addClass( "section_invisible" );
		}
	}
	calculpoints() {
		var vul;
		if ( (this.pos == pos_Nord) || (this.pos == pos_Sud  ) ) { vul = this.vulns; };
		if ( (this.pos == pos_Est ) || (this.pos == pos_Ouest) ) { vul = this.vuleo; };
		this.points = calculpointscontrat( this.niv, this.col, this.dbl, vul, this.res );
		if ( this.niv > 0 ) {
			if ( (this.pos == pos_Est ) || (this.pos == pos_Ouest) ) this.points = -this.points;
		}
	}
}
$(document).ready( function() {
	// saisie enchere
	$('td.xNum2').click(function(event) {
		//console.log( event.target.id );
		realdonne.selAction( event.target.id );
	});
	$('td.xPasse').click(function(event) {
		//console.log( event.target.id );
		realdonne.selAction( event.target.id );
	});
	$('span.numetui').click(function(event) {
		//console.log( event.target.id );
		if ( $("#section_etuis").hasClass( 'section_invisible' ) )
			$('#section_etuis').removeClass( 'section_invisible' );
		else
			$('#section_etuis').addClass( 'section_invisible' );
	});
	$('td.oklight').click(function(event) {
		//console.log( event.target.id );
		realdonne.selAction( event.target.id );
		//selEtui( event.target.id );
	});
});

function getRandomInt(max) {
  return Math.floor( max * Math.random() );
};

function autoValidation() {
	// simulation
	realdonne.niv = getRandomInt(7) +1;
	realdonne.col = getRandomInt(4) +1;
	realdonne.dbl = dbl_simple;
	realdonne.pos = getRandomInt(4) +1;
	realdonne.ent1 = getRandomInt(13) +2;
	realdonne.ent2 = getRandomInt(4) +1;
	realdonne.res = Math.min( realdonne.niv + getRandomInt(6) - 3, 7 ) - realdonne.niv;
	
	realdonne.contrat = realdonne.niv + " " + txtCol( realdonne.col ) + " " + txtDbl( realdonne.dbl );
	realdonne.declarant = txtPos( realdonne.pos );
	realdonne.entame = txtEnt(realdonne.ent1) + txtCol(realdonne.ent2);
	realdonne.calculpoints();
	clickValidation();
};
