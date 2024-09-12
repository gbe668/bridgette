//
// routines recherche de partenaire
//
function strDate(dd) {
	const strmois = Array( "zéro", "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" );
	var figs = dd.split('-');
	return( figs[2] +" "+ strmois[parseInt(figs[1])] +" "+ figs[0] );
};
function afficheJoueursEnRecherche(dd, strjson) {
	if ( strjson.nbl == 0 ) {
		html = "<p>Pas de joueurs en attente de partenaire pour le tournoi du <b>"+strDate(dd)+ "</b></p>";
	}
	else {
		html = "<p>" + strjson.nbl + " joueur(s) en attente de partenaire pour le tournoi du <b>"+strDate(dd)+ "</b></p>";
		html += "<table border='0' style='width:100%; max-width: 350px;'><tbody>";
		html += "<tr><td class='xContact'>Nom</td><td class='xContact'>Contact</td></tr>";
		for ( i=0; i<strjson.nbl; i++ ) {
			let idrow = "sel_"+strjson.ids[i];
			html += "<tr><td class='xContact cansel' id='" +idrow+ "_name'>" + strjson.names[i] + "</td><td class='xContact' id='" +idrow+ "_contact'>" + strjson.contacts[i] +"</td></tr>";
			if ((strjson.memos[i] != null)&&(strjson.memos[i] != "")) {
				html += "<tr><td class='xMemo' colspan='2' id='" +idrow+ "_memo'>"+strjson.memos[i]+"</td></tr>";
			}
		}
		html += "</tbody></table>";
		html += "<p>Vous êtes inscrit ? Vous avez trouvé un partenaire ? Vous voulez modifier votre inscription ou vous désinscrire ?</br><b>Alors, cliquez sur votre nom !</b></p>";
	}
	$("#msg").html(html);
	$("#section_inscription").show();
	$("#section_edition").hide();
	elmnt = document.getElementById("msg");
	elmnt.scrollIntoView();
};

var idselected = 0;
function annuleEraseContact() {
	$("#section_edition").hide();
	$("#section_inscription").show();
	idselected = 0;
}
function listeJoueursEnRecherche(dd) {
	var url = prefdir + "listecontacts.php";
	$.get( url, { datetournoi:dd }, function(strjson) {
		afficheJoueursEnRecherche(dd, strjson);
	},"json");
}
function eraseContact() {
	var dd = $("#datetournoi").val();
	var url = prefdir + "erasecontact.php";
	$.get( url, { id:idselected }, function(strjson) {
		$("#msgerr").html( strjson.res );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		listeJoueursEnRecherche(dd);
	},"json");
}
function insertContact() {
	var name = $("#name").val().trim();
	$("#name").val(name);
	if ( name == "" ) {
		$("#msgerr").text( "Entrez votre nom !" );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		return;
	}
	var contact = $("#contact").val().trim();
	$("#contact").val(contact);
	if ( contact == "" ) {
		$("#msgerr").text( "Entrez un moyen de vous contacter !" );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		return;
	}
	
	var dd = $("#datetournoi").val();
	var name = $("#name").val();
	var contact = $("#contact").val().trim();
	var memo = $("#memo").val();
	$("#msgerr").text( "Enregistrement en cours ..." );
	var url = prefdir + "insertcontact.php";
	$.get( url, { datetournoi:dd, name:name, contact:contact, memo:memo }, function(strjson) {
		$("#msgerr").html( strjson.res );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		listeJoueursEnRecherche(dd);
	},"json");
}
function updateContact() {
	var contact = $("#contact2").val().trim();
	$("#contact2").val(contact);
	if ( contact == "" ) {
		$("#msgerr").text( "Entrez un moyen de vous contacter !" );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		return;
	}
	
	var dd = $("#datetournoi").val();
	var contact2 = $("#contact2").val().trim();
	var memo2 = $("#memo2").val();
	$("#msgerr").text( "Mise à jour en cours ..." );
	var url = prefdir + "updatecontact.php";
	$.get( url, { id:idselected, contact:contact2, memo:memo2 }, function(strjson) {
		$("#msgerr").html( strjson.res );
		setTimeout(function() { $("#msgerr").html( "&nbsp;" ); }, 1000);
		listeJoueursEnRecherche(dd);
	},"json");
}
//
// paramètres datepicker
//
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

$( function() {
	//var dateFormat = "mm/dd/yy",
	datetournoi = $( "#datetournoi" ).datepicker({
			//defaultDate: +1,
			//numberOfMonths: 1
		})
		.on( "change", function() {
			//console.log( "change", $("#datetournoi").val() );
			listeJoueursEnRecherche( $("#datetournoi").val() );
			$("#inscription").show();
		});
} );
const listeJours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' ];
function noTournois(date){
	//console.log( parametres.opendays );
	var dd = date.getDay();		// de 0 (dimanche) à 6 (samedi)
	var jour = listeJours[dd];
	if ( parametres.opendays[jour] == '1' )
	//if ( (dd === 1)||(dd === 2)||(dd === 4)||(dd === 5) )  /* Monday */
		return [ true, "", "" ]
	else
		return [ false, "closed", "Pas de tournoi ce jour" ]
}

$(document).on( "click", "td.cansel", function(event) {
	var id = event.target.id;
	const figs = id.split('_');
	idselected = figs[1];
	var sid_name = '#sel_'+idselected+'_name';
	var sid_contact = '#sel_'+idselected+'_contact';
	var sid_memo = '#sel_'+idselected+'_memo';
	console.log( "id", id, "sid_name", sid_name );
	$("#nomjoueur").text( $(sid_name).text() );
	$("#section_edition").show();
	$("#name2").val($(sid_name).text());
	$("#contact2").val($(sid_contact).text());
	$("#memo2").val($(sid_memo).text());
	$("#section_inscription").hide();
});
