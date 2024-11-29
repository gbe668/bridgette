<?php
require("configuration.php");
require("bridgette_bdd.php");

$file_version = "version.txt";
if ( file_exists( $file_version ) ) {
	$version = file_get_contents( $file_version );
}
else $version = "inconnue";
?>

<!DOCTYPE HTML>
<html>
    <head>	
        <title>Bridg'ette</title>	
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
        <meta name="viewport" content="width=device-width, initial-scale=1">	
        <link rel="stylesheet" href="css/bridgestylesheet.css" />
		<link rel="icon" type="image/x-icon" href="images/favicon.ico">
	<script src="js/jquery-3.6.0.min.js"></script>
    </head>
<script>
function goto20() {
	var nextstring = "bridge20.php";
	location.replace( nextstring );
};
function goto25() {
	var nextstring = "bridge25.php";
	location.replace( nextstring );
};
function goto40() {
	var nextstring = "bridge40.php";
	location.replace( nextstring );
};
function goto60() {
	var nextstring = "bridge60.php";
	location.replace( nextstring );
};
function gotoformation() {
	var nextstring = "bridgform10.php";
	location.replace( nextstring );
};
function apropos() {
	var nextstring = "apropos_bridgette.php";
	location.replace( nextstring );
};
function loguserout() {
	var nextstring = "loguserout.php";
	location.replace( nextstring );
};
function gotobigscreen() {
	var nextstring = "bigscreen.php";
	location.replace( nextstring );
}
function gotodownloadapk() {
	var nextstring = "bridge01.php";
	location.replace( nextstring );
}
</script>

<body>	
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" />	</p>	
	<?php
	echo "<h2>$titre</h2>";
	// test installation application
	connectBDD();
	
	$resultIdent = getIdent();
	if ( $resultIdent['status'] == $ID_CORRECT ) {
		$userid = $resultIdent['userid'];
		$joueur = getJoueur( $userid );
		print "<h3>Hello ".$joueur['nomcomplet']."</h3>";
	}
	else {
		//print "<h3>Vous n'êtes pas identifié !</h3>";
	}
	?>
	<p><button class="myBigButton" onclick="goto60()">Rejoindre le tournoi en cours<br>pour les joueurs</button></p>	
	<p>&nbsp;
	<button id="section_apk" class='mButton' onclick="gotodownloadapk()">Appli android</button>
	</p>
	<p><button class="mySmallButton" onclick="goto20()">Affichage derniers résultats</button></p>
	<p><button class="mySmallButton" onclick="goto25()">Pré-inscription tournoi</br>Recherche partenaire</button></p>
	<p>&nbsp;</p>
	<p><button class="myBigButton" onclick="goto40()">Organisation du tournoi<br>réservé directeur tournoi</button></p>
	<p><button class="mySmallButton" onclick='gotoformation()'>Espace formation</button></p>
	<p><button class="mySmallButton" onclick="apropos()">Mode d'emploi / A propos</button></p>
	<p>version <?php echo $version; ?></p>
	<p><button class='mButton' onclick="gotobigscreen()">Grand écran</button></p>
	
	<?php
	if ( $resultIdent['status'] == $ID_CORRECT ) {
		print "<p><button class='mButton' onclick='loguserout()'>Déconnexion</button></p>";
	}
	if ( $config['site'] == 1 ) {
		print "<p>base: $base_url</p>";
	}
	?>
	<script>
	//if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
	if( /Android/i.test(navigator.userAgent) ) {
		$("#section_apk").show();
	}
	else {
		$("#section_apk").hide();
	}
	</script>
	</div>
</body>
</html>