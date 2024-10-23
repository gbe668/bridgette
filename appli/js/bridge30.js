// variable saisie nom du joueur
var idjoueur, gender, fname, lname, phone, email;
var strname = "";
var idj  = Array();
var numj = Array();
var jouj = Array();
var genj = Array();
var prej = Array();
var nomj = Array();
var phoj = Array();
var emaj = Array();
var oldjoueurs = Array();
var datesupp = Array();

function razfields() {
	radiobtn = document.getElementById("female");
	radiobtn.checked = false;
	radiobtn = document.getElementById("male");
	radiobtn.checked = false;

	$("#joueur").val("");
	$("#fname").val("");
	$("#lname").val("");
	$("#phone").val("");
	$("#email").val("");
	$("#noclub").val("");
	$("#msgerr").text( "" );
	$("#msgerr1").text( "" );	// msg création et modification
	$("#msgerr2").text( "" );	// msg suppression
};

function isAlpha(str) {
  return /^[a-zA-Zéèêëç\s-]+$/.test(str);
}
function isNumeric(str) {
  return /^(\s*[0-9]+\s*)+$/.test(str);
}
function isEmail(email) {
  const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}

function verifSyntaxe() {
	var rbs = document.querySelectorAll('input[name="gender"]');
	gender = "XY";
	for (const rb of rbs) {
		if (rb.checked) {
			gender = rb.value;
			break;
		}
	}
	if ( gender == "XY" ) {
		$("#msgerr1").text( "Genre non précisé" );
		return false;
	}
	
	fname = $("#fname").val();
	fname = fname.trim();
	$("#fname").val( fname );
	if ( fname == "" ) {
		$("#msgerr1").text( "Prénom non précisé" );
		return false;
	}
	if ( !isAlpha( fname ) ) {
		$("#msgerr1").text( "Prénom: caractères non alphabétiques" );
		return false;
	}
	
	lname = $("#lname").val();
	lname = lname.trim();
	$("#lname").val( lname );
	if ( lname == "" ) {
		$("#msgerr1").text( "Nom non précisé" );
		return false;
	}
	if ( !isAlpha( lname) ) {
		$("#msgerr1").text( "Nom: caractères non alphabétiques" );
		return false;
	}
	
	phone = $("#phone").val();
	phone = phone.trim();
	$("#phone").val( phone );
	if ( (phone.length > 0)&&(!isNumeric( phone)) ) {
		$("#msgerr1").text( "Téléphone: caractères non numériques" );
		return false;
	}
	$("#phone").val( phone );
	
	// mise en forme de l'email
	email = $("#email1").val();
	email = email.trim( email );
	email = email.toLowerCase();
	$("#email1").val( email );
	return true;
}

function creerjoueur() {
	if ( !verifSyntaxe() ) return;
	email = $("#email1").val();
	if ( !isEmail( email) ) {
		$("#msgerr1").text( "email incorrect" );
		email = "";	//pour ne pas stocker en bdd un email erroné
	}
	$.get("f30createjoueur.php",
		{ gender:gender, fname:fname, lname:lname, phone:phone, email:email },
		function(strjson) {
			$("#noclub").val( strjson.numero );
			$("#msgerr1").html( strjson.msg );
	},"json");
};
function modifierjoueur() {
	if ( !verifSyntaxe() ) return;
	$("#email").val( email );
	if ( !isEmail( email) ) {
		$("#msgerr1").text( "email incorrect" );
		email = "";	//pour ne pas stocker en bdd un email erroné
	}
	$.get("f30updatejoueur.php", 
		{ idjoueur:idjoueur, gender:gender, fname:fname, lname:lname, phone:phone, email:email },
		function(strjson) {
			$("#msgerr1").text( strjson.msg );
		}, "json");
};
