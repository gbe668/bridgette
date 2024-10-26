<?php
require("configuration.php");
require("bridgette_bdd.php");

$liste = 1;	// uniquement les joueurs actifs
$ordre = "nom";
$filtre = isset( $_GET['filtre'] ) ? htmlspecialchars( $_GET['filtre'] ) : "";

function getAnnuaire($liste, $ordre, $filtre) {
	global $tab_joueurs, $min_noclub;
	$dbh = connectBDD();
	if ( $liste == 1 ) $groupe = "and datesupp = 0";	// actifs
	if ( $liste == 2 ) $groupe = "and datesupp > 0";	// inactifs
	if ( $liste == 3 ) $groupe = "";	// tous
	
	if ( $ordre == "tournoi") {
		$selordre = "maxtournoi desc, nom";
	}
	else $selordre = $ordre;
	
	if ( strlen($filtre) > 0) {
		$likename = $filtre . "%";
		$filtrage = "and nom like '$likename'";
	}
	else $filtrage = "";
	
	$sql = "SELECT id, prenom, nom, telephone
			FROM $tab_joueurs
			WHERE numero >= $min_noclub $groupe $filtrage ORDER BY $selordre;";
	
	$str = "<table border='1' style='margin:auto;'><tbody>";
	$str .= "<tr>";
	$str .= "<td class='xTxt1'>Joueur</td>";
	$str .= "<td class='xTxt1'>Téléphone</td>";
	$str .= "</tr>";
	foreach  ($dbh->query($sql) as $row) {
		$str .= "<tr>";
		$str .= "<td class='xTxt1'>" . $row['prenom'] ." ". $row['nom'] . "</td>";
		$str .= "<td class='xTxt1'>" . $row['telephone'] . "</td>";
		$str .= "</tr>";
	}
	$str .= "</tbody></table>";
	$dbh = null;
	return $str;
};
//echo "<p>Annuaire des joueurs actifs</p>";
echo getAnnuaire($liste, $ordre, $filtre);
?>
