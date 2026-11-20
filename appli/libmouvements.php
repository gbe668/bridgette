<?php
// Catalogue et mouvements communs aux pages web, Android et Joomla.
// Extraction sans changement des signatures, données ou rendus historiques.
// Les libellés st_position et st_vulnerable sont fournis par le fichier central.

// fonctions utilisant la table typetournoi
$t_unknown = 0;
$t_mitchell = 1;
$t_howell = 2;
$st_typetournoi = [
	$t_unknown	=> "???",
	$t_mitchell => "Mitchell",
	$t_howell	=> "Howell",
];
$def_genre = $t_howell;
$def_type_mitchell = 10;
$def_type_howell = 8;

$min_type_howell = 4;
$min_type_mitchell = 9;
$min_type_affimp = 5;

$min_tables_mitchell = 4;
$min_paires_howell = 4;
$max_paires_howell = 12;

$maxetuis = 36;	// Nb max de donnes figurant sur la feuille de marque

$warningPMR = "<span style='color:red'>la paire n°1 est à réserver aux joueurs à mobilité réduite.</span>";
//
// insertion 29 août 2025
class TypesTournois {
	protected array $base;
	// Paquets réellement utilisés par chaque mouvement, relais compris.
	// Distincts des positions jouées et du nombre de paires présentes.
	protected array $paquetsHowell = [4=>6, 5=>4, 6=>5, 7=>7, 8=>7, 32=>9, 33=>9, 34=>11, 35=>11];
	function __construct() {
		global $t_unknown, $t_mitchell, $t_howell;
		$this->base = array(
	//'1'		=>array( 1,12,20,20, 1,20 ,0, 0, 0,"pas de tournoi","Paquet de donnes libres"),
	//				[0]'npaires' 
	//				 | [1]'ntables' y compris les tables relais, non compris le guéridon ou = npaires pour howell
	//				 |  | [2]'paquet' de donnes par tables
	//				 |  |  | [3]'npositions' nb de positions
	//				 |  |  |  | [4]'saut' si >0 saut après la xième position
	//				 |  |  |  |  | [5] relais si >0, Howell: n°paire manquante, Mitchell: n°table relais
	//				 |  |  |  |  |  |  [6]guéridon si =1
	//				 |  |  |  |  |  |  |  [7] genre tournoi
	//				 |  |  |  |  |  |  |  |			[8] max positions
	//				 |  |  |  |  |  |  |  |			|		[9] description
	'4'		=>array( 4, 4, 4, 6, 0, 0, 0,$t_howell, 6,  "Equivalent Howell avec 2 tables, %np% positions, affichage en % ou IMP selon paramétrage"),
	'5'		=>array( 5, 5, 5, 4, 0, 6, 0,$t_howell, 4,	"Howell 5 paires avec 3 tables incomplètes, %np% positions, table relais variable en fonction du numéro de tour, affichage en % ou IMP selon paramétrage"),
	'6'		=>array( 6, 6, 5, 5, 0, 0, 0,$t_howell, 5,	"Howell 6 paires avec 3 tables, %np% positions, partage des donnes à la dernière position"),
	'7'		=>array( 7, 7, 3, 7, 0, 8, 0,$t_howell, 7,	"Howell 7 paires avec 4 tables incomplètes, %np% positions, table relais variable en fonction du numéro de tour"),
	'8'		=>array( 8, 8, 3, 7, 0, 0, 0,$t_howell, 7,  "Howell 8 paires avec 4 tables, %np% positions"),
	'32'	=>array( 9, 9, 3, 9, 0,10, 0,$t_howell, 9,  "Howell 9 paires avec 5 tables incomplètes, %np% positions"),
	'33'	=>array(10,10, 3, 9, 0, 0, 0,$t_howell, 9,  "Howell 10 paires avec 5 tables, %np% positions"),
	'34'	=>array(11,11, 3, 9, 0,12, 0,$t_howell, 11,  "Howell 11 paires avec 6 tables incomplètes, %np% positions"),	// ajout 27/08/2025
	'35'	=>array(12,12, 3, 9, 0, 0, 0,$t_howell, 11,  "Howell 12 paires avec 6 tables complètes, %np% positions"),	// ajout 27/08/2025
	
	'47'	=>array( 7, 4, 5, 4, 0, 4, 1,$t_mitchell, 4,	"Mitchell, 4 tables incomplètes, %np% positions, guéridon entre les tables 2 et 3, pas d'étuis table 4, relais EO uniquement"),
	'48'	=>array( 8, 4, 5, 4, 0, 0, 1,$t_mitchell, 4,	"Mitchell, 4 tables complètes, %np% positions, guéridon entre les tables 2 et 3, pas d'étuis table 4, les tables 1 et 4 se partagent les étuis qui arrivent à la table 1"),
	'9'		=>array( 9, 5, 5, 5, 0, 5, 0,$t_mitchell, 5,	"Mitchell, 5 tables incomplètes, %np% positions, relais NS variable ou relais EO table 5"),
	'10'	=>array(10, 5, 5, 5, 0, 0, 0,$t_mitchell, 5,	"Mitchell, 5 tables complètes, %np% positions"),
	'11'	=>array(11, 6, 4, 6, 0, 6, 1,$t_mitchell, 6,	"Mitchell, 6 tables incomplètes, %np% positions, guéridon entre les tables 3 et 4, pas d'étuis table 6, relais EO uniquement"),
	'12'	=>array(12, 6, 4, 6, 0, 0, 1,$t_mitchell, 6,	"Mitchell, 6 tables complètes, %np% positions, guéridon entre les tables 3 et 4, pas d'étuis table 6, les tables 1 et 6 se partagent les étuis qui arrivent à la table 1"),
	'13'	=>array(13, 7, 4, 7, 0, 7, 0,$t_mitchell, 7,	"Mitchell, 7 tables incomplètes, %np% positions, relais NS variable ou relais EO table 7"),
	'14'	=>array(14, 7, 4, 7, 0, 0, 0,$t_mitchell, 7,	"Mitchell, 7 tables complètes, %np% positions"),
	'15'	=>array(15, 8, 3, 8, 0, 8, 1,$t_mitchell, 8,	"Mitchell, 8 tables incomplètes, %np% positions, guéridon entre les tables 4 et 5, pas d'étuis table 8, relais EO uniquement"),
	'16'	=>array(16, 8, 3, 8, 0, 0, 1,$t_mitchell, 8,	"Mitchell, 8 tables complètes, %np% positions, guéridon entre les tables 4 et 5, pas d'étuis table 8, les tables 1 et 8 se partagent les étuis qui arrivent à la table 1"),
	'17'	=>array(17, 9, 3, 9, 0, 9, 0,$t_mitchell, 9,	"Mitchell, 9 tables incomplètes, %np% positions, relais NS variable ou relais EO table 9"),
	'18'	=>array(18, 9, 3, 9, 0, 0, 0,$t_mitchell, 9,	"Mitchell, 9 tables complètes, %np% positions"),
	'19'	=>array(19,10, 3, 9, 5,10, 0,$t_mitchell, 9,	"Mitchell, 10 tables incomplètes, %np% positions, saut après 5ème position, relais NS variable ou relais EO table 10"),
	'20'	=>array(20,10, 3, 9, 5, 0, 0,$t_mitchell, 9,	"Mitchell, 10 tables complètes, %np% positions, saut après 5ème position"),
	'21'	=>array(21,11, 3, 9, 0,11, 0,$t_mitchell,11,	"Mitchell, 11 tables incomplètes, %np% positions, relais NS variable ou relais EO table 11"),
	'22'	=>array(22,11, 3, 9, 0, 0, 0,$t_mitchell,11,	"Mitchell, 11 tables complètes, %np% positions"),
	'23'	=>array(23,12, 2,12, 0,12, 1,$t_mitchell,12,	"Mitchell, 12 tables incomplètes, %np% positions, guéridon entre les tables 6 et 7, pas d'étuis au relais EO table 12"),
	'24'	=>array(24,12, 2,11, 6, 0, 0,$t_mitchell,11,	"Mitchell, 12 tables complètes, %np% positions, saut après 6ème position"),
	'25'	=>array(25,13, 2,13, 0,13, 0,$t_mitchell,13,	"Mitchell, 13 tables incomplètes, %np% positions, relais NS variable ou relais EO table 13"),
	'26'	=>array(26,13, 2,13, 0, 0, 0,$t_mitchell,13,	"Mitchell, 13 tables complètes, %np% positions"),
	'27'	=>array(27,14, 2,13, 7,14, 0,$t_mitchell,13,	"Mitchell, 14 tables incomplètes, %np% positions, saut après 7ème position, relais NS variable ou relais EO table 14"),
	'28'	=>array(28,14, 2,13, 7, 0, 0,$t_mitchell,13,	"Mitchell, 14 tables complètes, %np% positions, saut après 7ème position"),
	'29'	=>array(29,15, 2,13, 0,15, 0,$t_mitchell,15,	"Mitchell, 15 tables incomplètes, %np% positions, relais NS variable ou relais EO table 15"),
	'30'	=>array(30,15, 2,13, 0, 0, 0,$t_mitchell,15,	"Mitchell, 15 tables complètes, %np% positions")
	//
	//				[0]'npaires' 
	//				 | [1]'ntables' y compris les tables relais, non compris le guéridon ou = npaires pour howell
	//				 |  | [2]'paquet' de donnes par tables
	//				 |  |  |  [3]'npositions' nb de positions
	//				 |  |  |  | [4]'saut' si >0 saut après la xième position
	//				 |  |  |  |  | [5] relais si >0, Howell: n°paire manquante, Mitchell: n°table relais
	//				 |  |  |  |  |  |  [6]guéridon si =1
	//				 |  |  |  |  |  |  |  [7] genre tournoi
	//				 |  |  |  |  |  |  |  |			[8] max positions
	//				 |  |  |  |  |  |  |  |			|		[9] description
	);
	}
	protected function get_tt( $id ) {
		global $maxetuis, $t_mitchell, $t_howell;
		$typetournoi = $this->base[$id];
		$tt = array(
			'npaires' 	=> $typetournoi[0],
			'ntables' 	=> $typetournoi[1],
			'paquet' 	=> $typetournoi[2],		// par défaut
			'npositions' => $typetournoi[3],
			'saut' 		=> $typetournoi[4],
			'relais' 	=> $typetournoi[5],
			'gueridon'	=> $typetournoi[6],
			'genre'	 	=> $typetournoi[7],
			'maxpos'	=> $typetournoi[8],
			'desc'	 	=> $typetournoi[9]
		);
		if( $tt['genre'] == $t_mitchell ) {
			$tt['maxpaquet'] = intval( $maxetuis/$tt['ntables'] );
		}
		else {
			$tt['npaquets'] = $this->paquetsHowell[$id];
			$tt['maxpaquet'] = intval( $maxetuis/$tt['npaquets'] );
		}
		return $tt;
		}
	function computetype( $pns, $peo ) {
		if ( $peo == 0 ) {
			// howell
			if ( $pns > 8 ) {
				return( $pns + 23 );
			}
			else {
				return $pns;
			}
		}
		else {
			// mitchell
			$np = $pns + $peo;
			if ( $np > 8 ) {
				return( $np );
			}
			else {
				return( $np + 40 );
			}
		}
	}	
	function gettypetournoi( $id ) {
		if ( array_key_exists( $id, $this->base ) ) {
			$tt = $this->get_tt( $id );
		}
		else {
			$tt = array(
				'npaires' 	=> 0,
				'ntables' 	=> 0,
				'paquet' 	=> 0,
				'maxpaquet'	=> 0,
				'npositions' => 0,
				'saut' 		=> 0,
				'relais' 	=> 0,
				'gueridon'	=> 0,
				'genre'	 	=> $t_unknown,
				'maxpos'	=> 0,
				'desc'	 	=> "type tournoi inconnu",
			);
		}
		return $tt;
	}
	function getnpaires( $id ) {
		if ( array_key_exists( $id, $this->base ) ) {
			$tt = $this->get_tt( $id );
			return $tt['npaires'];
		}
		else {
			return 0;
		}
	}
	function getmaxpositions( $id ) {
		if ( array_key_exists( $id, $this->base ) ) {
			$tt = $this->get_tt( $id );
			return $tt['maxpos'];
		}
		else {
			return 0;
		}
	}
	function gethtmltable() {
		global $warningPMR;
		$str = "<p>Pour les tournois de type Howell jusqu'à 10 paires, la paire n°1 à la table 1 ne bouge pas pendant le tournoi, $warningPMR</p>";
		$str .= '<table border="0"><tbody>';
		$str .= '<tr>';
		$str .= '<th class="xTypt">Nb<br/>paires</th>';
		$str .= '<th class="xTypt">Nb<br/>étuis<br/>(*)</th>';
		$str .= '<th style="text-align:left" class="xTypt">Description (**)</th>';
		$str .= '</tr>';
		foreach ( $this->base as $key => $value ) {
			$tt = $this->get_tt( $key );
			$str .= "<tr>";
			$npaires	= $tt['npaires'];
			$paquet		= $tt['paquet'];
			$desc		= $tt['desc'];
			$desc = str_replace("%np%", $tt['npositions'], $tt['desc']);

			$str .= "<td class='xTypt'><b>$npaires</b></td>";
			$str .= "<td class='xTypt'>$paquet</td>";
			$str .= "<td style='text-align:left;' class='xTypt'>$desc</td>";
			$str .= '</tr>';
		}
		$str .= '</tbody></table>';
		$str .= "<p>(*): valeur par défaut, modifiable <b>avant</b> le démarrage du tournoi<br/>(**): nombre de positions modifiable <b>après</b> le démarrage du tournoi</br>Avec au maximum 36 étuis en circulation.</p>";
		return $str;
	}
}
$typestournois = new TypesTournois();

function computetype($pns,$peo) { global $typestournois; return $typestournois->computetype( $pns, $peo ); }
function htmlTableTypeTournois(){ global $typestournois; return $typestournois->gethtmltable(); }
function getnpaires( $id )		{ global $typestournois; return $typestournois->getnpaires( $id ); }
function getmaxpositions( $id, $paquet )	{
	global $typestournois, $maxetuis;
	// le nombre de positions dépends du nombre de tables, du type de tournoi et du nombre d'étuis par table
	// Le nombre d'étui en circulation doit rester inférieur ou égale au nombre max d'étuis qui est de 36
	$tt = $typestournois->gettypetournoi( $id );
	$maxpos = min($tt['maxpos'], floor($maxetuis/$paquet) );
	return $maxpos;
}
function gettypetournoi( $id )	{ global $typestournois; return $typestournois->gettypetournoi( $id ); }
// fin insertion 29 août 2025
//
function getposhowell( $idtype, $numpaire, $notour, $paquet ) {
	// Tableau des positions successives pour les tournois Howell
	$posHowell = Array(		// NS/EO __ N°table
	// Duplicate tournant 4 paires - 3 positions - 6 tours
	//Paire n° --> 	1			2			3			4
	//	Table_ns/eo_paquet_adversaire
	'4_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3' ),	// Positions tour n°1
	'4_2'=>Array( '1_1_2_2', '1_2_2_1', '2_1_1_4', '2_2_1_3' ),	// Positions tour n°2
	'4_3'=>Array( '1_1_3_3', '2_2_4_4', '1_2_3_1', '2_1_4_2' ),	// Positions tour n°3
	'4_4'=>Array( '1_1_4_3', '2_2_3_4', '1_2_4_1', '2_1_3_2' ),	// Positions tour n°4
	'4_5'=>Array( '1_1_5_4', '2_1_6_3', '2_2_6_2', '1_2_5_1' ),	// Positions tour n°5
	'4_6'=>Array( '1_1_6_4', '2_1_5_3', '2_2_5_2', '1_2_6_1' ),	// Positions tour n°6
	
	// Howell 5 paires - 5 positions avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 	1			2			3			4		5 en relais
	//	Table_position_paquet_adversaire
	'5_1'=>Array( '1_1_1_2', '1_2_1_1', '2_2_4_0', '3_2_2_5', '3_1_2_4', '2_1_4_3' ),	// Positions tour n°1
	'5_2'=>Array( '1_1_2_3', '2_1_4_4', '1_2_2_1', '2_2_4_2', '3_2_3_0', '3_1_3_5' ),	// Positions tour n°2
	'5_3'=>Array( '1_1_3_4', '3_1_2_0', '2_1_1_5', '1_2_3_1', '2_2_1_3', '3_2_2_2' ),	// Positions tour n°3
	'5_4'=>Array( '1_1_4_5', '3_2_3_3', '3_1_3_2', '2_1_1_0', '1_2_4_1', '2_2_1_4' ),	// Positions tour n°4
	
	// Howell 6 paires - 5 positions avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 	1			2			3			4		5			6
	//	Table_position_paquet_adversaire
	'6_1'=>Array( '1_1_1_2', '1_2_1_1', '2_2_4_6', '3_2_2_5', '3_1_2_4', '2_1_4_3' ),	// Positions tour n°1
	'6_2'=>Array( '1_1_2_3', '2_1_4_4', '1_2_2_1', '2_2_4_2', '3_2_3_6', '3_1_3_5' ),	// Positions tour n°2
	'6_3'=>Array( '1_1_3_4', '3_1_2_6', '2_1_1_5', '1_2_3_1', '2_2_1_3', '3_2_2_2' ),	// Positions tour n°3
	'6_4'=>Array( '1_1_4_5', '3_2_3_3', '3_1_3_2', '2_1_1_6', '1_2_4_1', '2_2_1_4' ),	// Positions tour n°4
	'6_5'=>Array( '1_1_5_6', '2_2_5_5', '3_2_5_4', '3_1_5_3', '2_1_5_2', '1_2_5_1' ),	// Positions tour n°5, le dernier paquet est partagé entre les 3 tables
	
	// Howell 7 paires type anglais
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7
	//	Table_position_paquet_adversaire 					en relais
	'7_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3', '3_1_3_6', '3_2_3_5', '4_1_5_0', '4_2_5_7' ),	// tour n°1
	'7_2'=>Array( '1_1_2_6', '4_1_6_5', '2_2_3_7', '3_1_4_0', '4_2_6_2', '1_2_2_1', '2_1_3_3', '3_2_4_4' ),	// tour n°2
	'7_3'=>Array( '1_1_3_0', '2_1_4_7', '3_1_5_5', '4_2_7_6', '3_2_5_3', '4_1_7_4', '2_2_4_2', '1_2_3_1' ),	// tour n°3
	'7_4'=>Array( '1_1_4_5', '2_2_5_6', '4_2_1_0', '3_2_6_7', '1_2_4_1', '2_1_5_2', '3_1_6_4', '4_1_1_3' ),	// tour n°4
	'7_5'=>Array( '1_1_5_4', '3_1_7_3', '3_2_7_2', '1_2_5_1', '4_1_2_7', '2_2_6_0', '4_2_2_5', '2_1_6_6' ),	// tour n°5
	'7_6'=>Array( '1_1_6_3', '4_2_3_4', '1_2_6_1', '4_1_3_2', '2_1_7_0', '3_1_1_7', '3_2_1_6', '2_2_7_5' ),	// tour n°6
	'7_7'=>Array( '1_1_7_7', '3_2_2_0', '4_1_4_6', '2_1_1_5', '2_2_1_4', '4_2_4_3', '1_2_7_1', '3_1_2_2' ),	// tour n°7
	
	// Howell 8 paires type anglais avec la paire n°1 à la table 1 qui ne bouge pas
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8
	//	Table_position_paquet_adversaire 					en relais
	'8_1'=>Array( '1_1_1_2', '1_2_1_1', '2_1_2_4', '2_2_2_3', '3_1_3_6', '3_2_3_5', '4_1_5_8', '4_2_5_7' ),	// tour n°1
	'8_2'=>Array( '1_1_2_6', '4_1_6_5', '2_2_3_7', '3_1_4_8', '4_2_6_2', '1_2_2_1', '2_1_3_3', '3_2_4_4' ),	// tour n°2
	'8_3'=>Array( '1_1_3_8', '2_1_4_7', '3_1_5_5', '4_2_7_6', '3_2_5_3', '4_1_7_4', '2_2_4_2', '1_2_3_1' ),	// tour n°3
	'8_4'=>Array( '1_1_4_5', '2_2_5_6', '4_2_1_8', '3_2_6_7', '1_2_4_1', '2_1_5_2', '3_1_6_4', '4_1_1_3' ),	// tour n°4
	'8_5'=>Array( '1_1_5_4', '3_1_7_3', '3_2_7_2', '1_2_5_1', '4_1_2_7', '2_2_6_8', '4_2_2_5', '2_1_6_6' ),	// tour n°5
	'8_6'=>Array( '1_1_6_3', '4_2_3_4', '1_2_6_1', '4_1_3_2', '2_1_7_8', '3_1_1_7', '3_2_1_6', '2_2_7_5' ),	// tour n°6
	'8_7'=>Array( '1_1_7_7', '3_2_2_8', '4_1_4_6', '2_1_1_5', '2_2_1_4', '4_2_4_3', '1_2_7_1', '3_1_2_2' ),	// tour n°7
	
	// Howell 9 paires
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8			9		10
	//	Table_position_paquet_adversaire 					en relais
	'9_1'=>Array( '1_1_1_0', '5_1_9_5', '4_1_8_7', '3_2_3_6', '5_2_9_2', '3_1_3_4', '4_2_8_3', '2_1_2_9', '2_2_2_8', '1_2_1_1' ),	// tour n°1
	'9_2'=>Array( '1_1_2_2', '1_2_2_1', '5_1_1_6', '4_1_9_8', '3_2_4_7', '5_2_1_3', '3_1_4_5', '4_2_9_4', '2_1_3_0', '2_2_3_9' ),	// tour n°2
	'9_3'=>Array( '1_1_3_3', '2_2_4_0', '1_2_3_1', '5_1_2_7', '4_1_1_9', '3_2_5_8', '5_2_2_4', '3_1_5_6', '4_2_1_5', '2_1_4_2' ),	// tour n°3
	'9_4'=>Array( '1_1_4_4', '2_1_5_3', '2_2_5_2', '1_2_4_1', '5_1_3_8', '4_1_2_0', '3_2_6_9', '5_2_3_5', '3_1_6_7', '4_2_2_6' ),	// tour n°4
	'9_5'=>Array( '1_1_5_5', '4_2_3_7', '2_1_6_4', '2_2_6_3', '1_2_5_1', '5_1_4_9', '4_1_3_2', '3_2_7_0', '5_2_4_6', '3_1_7_8' ),	// tour n°5
	'9_6'=>Array( '1_1_6_6', '3_1_8_9', '4_2_4_8', '2_1_7_5', '2_2_7_4', '1_2_6_1', '5_1_5_0', '4_1_4_3', '3_2_8_2', '5_2_5_7' ),	// tour n°6
	'9_7'=>Array( '1_1_7_7', '5_2_6_8', '3_1_9_0', '4_2_5_9', '2_1_8_6', '2_2_8_5', '1_2_7_1', '5_1_6_2', '4_1_5_4', '3_2_9_3' ),	// tour n°7
	'9_8'=>Array( '1_1_8_8', '3_2_1_4', '5_2_7_9', '3_1_1_2', '4_2_6_0', '2_1_9_7', '2_2_9_6', '1_2_8_1', '5_1_7_3', '4_1_6_5' ),	// tour n°8
	'9_9'=>Array( '1_1_9_9', '4_1_7_6', '3_2_2_5', '5_2_8_0', '3_1_2_3', '4_2_7_2', '2_1_1_8', '2_2_1_7', '1_2_9_1', '5_1_8_4' ),	// tour n°9

	// Howell 10 paires
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8			9		10
	//	Table_position_paquet_adversaire 					en relais
	'10_1'=>Array( '1_1_1_10', '5_1_9_5', '4_1_8_7', '3_2_3_6', '5_2_9_2', '3_1_3_4', '4_2_8_3', '2_1_2_9', '2_2_2_8', '1_2_1_1' ),	// tour n°1
	'10_2'=>Array( '1_1_2_2', '1_2_2_1', '5_1_1_6', '4_1_9_8', '3_2_4_7', '5_2_1_3', '3_1_4_5', '4_2_9_4', '2_1_3_10', '2_2_3_9' ),	// tour n°2
	'10_3'=>Array( '1_1_3_3', '2_2_4_10', '1_2_3_1', '5_1_2_7', '4_1_1_9', '3_2_5_8', '5_2_2_4', '3_1_5_6', '4_2_1_5', '2_1_4_2' ),	// tour n°3
	'10_4'=>Array( '1_1_4_4', '2_1_5_3', '2_2_5_2', '1_2_4_1', '5_1_3_8', '4_1_2_10', '3_2_6_9', '5_2_3_5', '3_1_6_7', '4_2_2_6' ),	// tour n°4
	'10_5'=>Array( '1_1_5_5', '4_2_3_7', '2_1_6_4', '2_2_6_3', '1_2_5_1', '5_1_4_9', '4_1_3_2', '3_2_7_10', '5_2_4_6', '3_1_7_8' ),	// tour n°5
	'10_6'=>Array( '1_1_6_6', '3_1_8_9', '4_2_4_8', '2_1_7_5', '2_2_7_4', '1_2_6_1', '5_1_5_10', '4_1_4_3', '3_2_8_2', '5_2_5_7' ),	// tour n°6
	'10_7'=>Array( '1_1_7_7', '5_2_6_8', '3_1_9_10', '4_2_5_9', '2_1_8_6', '2_2_8_5', '1_2_7_1', '5_1_6_2', '4_1_5_4', '3_2_9_3' ),	// tour n°7
	'10_8'=>Array( '1_1_8_8', '3_2_1_4', '5_2_7_9', '3_1_1_2', '4_2_6_10', '2_1_9_7', '2_2_9_6', '1_2_8_1', '5_1_7_3', '4_1_6_5' ),	// tour n°8
	'10_9'=>Array( '1_1_9_9', '4_1_7_6', '3_2_2_5', '5_2_8_10', '3_1_2_3', '4_2_7_2', '2_1_1_8', '2_2_1_7', '1_2_9_1', '5_1_8_4' ),	// tour n°9

	// 27/08/2025: ajout howell 11 et 12 paires
	// Source 11/12 paires : howell 12paires ffb.xlsx, Feuil2!A3:Y13.
	// Paquet = numéro du paquet ; sa taille est fournie par $paquet au lancement.
	// Howell 11 paires: Howell Classique à 5 tables ½, 9 positions jouées, max 11
	// La paire 12 est absente : adversaire 0 pour la paire en relais.
	//Paire n° --> 1			2		 	3		  4		   		5		6			 7			8			9		10			11
	//	Table_position_paquet_adversaire	relais table 1
	'11_1' =>Array( '1_2_1_0', '4_1_8_3', '4_2_8_2', '6_1_11_7', '3_1_7_9', '2_1_3_11', '6_2_11_4', '5_2_9_10', '3_2_7_5', '5_1_9_8', '2_2_3_6', '1_1_1_1' ), 
	'11_2' =>Array( '2_2_4_7', '1_2_2_0', '4_1_9_4', '4_2_9_3', '6_1_1_8', '3_1_8_10', '2_1_4_1', '6_2_1_5', '5_2_10_11', '3_2_8_6', '5_1_10_9', '1_1_2_2' ), 
	'11_3' =>Array( '5_1_11_10', '2_2_5_8', '1_2_3_0', '4_1_10_5', '4_2_10_4', '6_1_2_9', '3_1_9_11', '2_1_5_2', '6_2_2_6', '5_2_11_1', '3_2_9_7', '1_1_3_3' ), 
	'11_4' =>Array( '3_2_10_8', '5_1_1_11', '2_2_6_9', '1_2_4_0', '4_1_11_6', '4_2_11_5', '6_1_3_10', '3_1_10_1', '2_1_6_3', '6_2_3_7', '5_2_1_2', '1_1_4_4' ),
	'11_5' =>Array( '5_2_2_3', '3_2_11_9', '5_1_2_1', '2_2_7_10', '1_2_5_0', '4_1_1_7', '4_2_1_6', '6_1_4_11', '3_1_11_2', '2_1_7_4', '6_2_4_8', '1_1_5_5' ),
	'11_6' =>Array( '6_2_5_9', '5_2_3_4', '3_2_1_10', '5_1_3_2', '2_2_8_11', '1_2_6_0', '4_1_2_8', '4_2_2_7', '6_1_5_1', '3_1_1_3', '2_1_8_5', '1_1_6_6' ),
	'11_7' =>Array( '2_1_9_6', '6_2_6_10', '5_2_4_5', '3_2_2_11', '5_1_4_3', '2_2_9_1', '1_2_7_0', '4_1_3_9', '4_2_3_8', '6_1_6_2', '3_1_2_4', '1_1_7_7' ),
	'11_8' =>Array( '3_1_3_5', '2_1_10_7', '6_2_7_11', '5_2_5_6', '3_2_3_1', '5_1_5_4', '2_2_10_2', '1_2_8_0', '4_1_4_10', '4_2_4_9', '6_1_7_3', '1_1_8_8' ),
	'11_9' =>Array( '6_1_8_4', '3_1_4_6', '2_1_11_8', '6_2_8_1', '5_2_6_7', '3_2_4_2', '5_1_6_5', '2_2_11_3', '1_2_9_0', '4_1_5_11', '4_2_5_10', '1_1_9_9' ),
	'11_10'=>Array( '4_2_6_11', '6_1_9_5', '3_1_5_7', '2_1_1_9', '6_2_9_2', '5_2_7_8', '3_2_5_3', '5_1_7_6', '2_2_1_4', '1_2_10_0', '4_1_6_1', '1_1_10_10' ),
	'11_11'=>Array( '4_1_7_2', '4_2_7_1', '6_1_10_6', '3_1_6_8', '2_1_2_10', '6_2_10_3', '5_2_8_9', '3_2_6_4', '5_1_8_7', '2_2_2_5', '1_2_11_0', '1_1_11_11' ),

	// Howell 12 paires: Howell Classique à 6 tables, 9 positions jouées, max 11
	//Paire n° --> 		1			2		 	3		  4		   		5		6			 7			8			9		10			11			12
	//	Table_position_paquet_adversaire
	'12_1' =>Array( '1_2_1_12', '4_1_8_3', '4_2_8_2', '6_1_11_7', '3_1_7_9', '2_1_3_11', '6_2_11_4', '5_2_9_10', '3_2_7_5', '5_1_9_8', '2_2_3_6', '1_1_1_1' ), 
	'12_2' =>Array( '2_2_4_7', '1_2_2_12', '4_1_9_4', '4_2_9_3', '6_1_1_8', '3_1_8_10', '2_1_4_1', '6_2_1_5', '5_2_10_11', '3_2_8_6', '5_1_10_9', '1_1_2_2' ), 
	'12_3' =>Array( '5_1_11_10', '2_2_5_8', '1_2_3_12', '4_1_10_5', '4_2_10_4', '6_1_2_9', '3_1_9_11', '2_1_5_2', '6_2_2_6', '5_2_11_1', '3_2_9_7', '1_1_3_3' ), 
	'12_4' =>Array( '3_2_10_8', '5_1_1_11', '2_2_6_9', '1_2_4_12', '4_1_11_6', '4_2_11_5', '6_1_3_10', '3_1_10_1', '2_1_6_3', '6_2_3_7', '5_2_1_2', '1_1_4_4' ),
	'12_5' =>Array( '5_2_2_3', '3_2_11_9', '5_1_2_1', '2_2_7_10', '1_2_5_12', '4_1_1_7', '4_2_1_6', '6_1_4_11', '3_1_11_2', '2_1_7_4', '6_2_4_8', '1_1_5_5' ),
	'12_6' =>Array( '6_2_5_9', '5_2_3_4', '3_2_1_10', '5_1_3_2', '2_2_8_11', '1_2_6_12', '4_1_2_8', '4_2_2_7', '6_1_5_1', '3_1_1_3', '2_1_8_5', '1_1_6_6' ),
	'12_7' =>Array( '2_1_9_6', '6_2_6_10', '5_2_4_5', '3_2_2_11', '5_1_4_3', '2_2_9_1', '1_2_7_12', '4_1_3_9', '4_2_3_8', '6_1_6_2', '3_1_2_4', '1_1_7_7' ),
	'12_8' =>Array( '3_1_3_5', '2_1_10_7', '6_2_7_11', '5_2_5_6', '3_2_3_1', '5_1_5_4', '2_2_10_2', '1_2_8_12', '4_1_4_10', '4_2_4_9', '6_1_7_3', '1_1_8_8' ),
	'12_9' =>Array( '6_1_8_4', '3_1_4_6', '2_1_11_8', '6_2_8_1', '5_2_6_7', '3_2_4_2', '5_1_6_5', '2_2_11_3', '1_2_9_12', '4_1_5_11', '4_2_5_10', '1_1_9_9' ),
	'12_10'=>Array( '4_2_6_11', '6_1_9_5', '3_1_5_7', '2_1_1_9', '6_2_9_2', '5_2_7_8', '3_2_5_3', '5_1_7_6', '2_2_1_4', '1_2_10_12', '4_1_6_1', '1_1_10_10' ),
	'12_11'=>Array( '4_1_7_2', '4_2_7_1', '6_1_10_6', '3_1_6_8', '2_1_2_10', '6_2_10_3', '5_2_8_9', '3_2_6_4', '5_1_8_7', '2_2_2_5', '1_2_11_12', '1_1_11_11' ),
	);
	$npaires = getnpaires($idtype);
	$itour = $npaires ."_".$notour;
	$tour  = $posHowell[ $itour ];
	$stri  = $tour[$numpaire - 1];
	
	$figs = explode( "_", $stri );
	$p = array(
		'table'	=> $figs[0],					// numéro de table
		'NS'	=> ($figs[1] == 1) ? 1 : 2,	// orientation
		'last'	=> ($figs[2]-1)*$paquet,		// dernière donne "jouée"
		'adversaire'=> $figs[3],
		);
	return $p;
}
function getrelaishowell( $idtype, $notour, $paquet ) {
	$tt = gettypetournoi( $idtype );
	if ($tt['relais'] == 0 ) return 0;
	$maxtour	= $tt['npositions'];
	$maxpaires	= $tt['ntables'];
	for ($i=1; $i<=$maxpaires; $i++) {
		$p = getposhowell( $idtype, $i, $notour, $paquet );
		$adversaire = intval( $p['adversaire'] );
		if ( $adversaire == 0 ) return $i;
	}
}
function getfeuillesuivihowell( $idtype, $paquet, $n ) {
	//	$idtype: le type de tournoi de howell
	//	$paquet = taille du paquet (choisi avant démarrage du tournoi)
	//	$n le numéro de de la 1ère donne du paquet
	$feuille = Array();
	// return un tableau avec:
	// n°tour(de 1 à maxtour) - n°paire(NS) - n°paire(EO)
	$tt = gettypetournoi( $idtype );
	$maxtour	= $tt['npositions'];
	$maxpaires	= $tt['ntables'];
	$k = 0;
	for ($i=1; $i<=$maxtour; $i++) {
		// recherche si donne $n jouée par une paire en NS
		for ($j=1; $j<=$maxpaires; $j++) {
			//getposhowell( $idtype, $numpaire, $notour, $paquet )
			$p = getposhowell( $idtype, $j, $i, $paquet );
			if ( $p['NS'] != 1 ) continue;
			if ( ($p['last']+1)!= $n ) continue;
			$adversaire = intval( $p['adversaire'] );
			if ( $adversaire == 0 ) continue;
			// trouvé
			$feuille[$k]['found'] = 1;
			$feuille[$k]['NS'] = $j;
			$feuille[$k]['EO'] = $adversaire;
			$k++;
		}
	}
	$feuille[$k]['found'] = 0;
	return $feuille;
}
function htmlPositionHowell($idtype, $pns, $npos, $paquet, $ndonnes) {
	$tab = "<table style='width:95%; max-width: 350px; margin:auto;'><tbody>";
	$tab .= "<tr><th class='xNum3' colspan='4'>Tour $npos</th></tr>";
	$tab .= "<tr><td class='xNum3'>Paire</td><td class='xNum3'>Table</td>";
	$tab .= "<td class='xNum3'>NS/EO</td><td class='xNum3'>Donnes</td></tr>";
	for ( $j = 1; $j <= $pns; $j++ ) {
		//$p = getposhowell( $idtype, $numpaire, $notour, $paquet );
		$p = getposhowell( $idtype, $j, $npos, $paquet );
		$t = $p['table'];	// numéro de table
		$o = ($p['NS'] == 1) ? "NS" : "EO"; 		// orientation: 1=NS, 2=EO
		$l1 = $p['last'] + 1;		// dernière donne "jouée"
		if ( $l1 > $ndonnes ) $l1 = 1;
		$l2 = $l1 + $paquet -1;
		if( $p['adversaire'] == 0 ) $etuis = "- - -";
		else $etuis = $l1."-".$l2;
		$tab .= "<tr><td class='xNum3'>$j</td><td class='xNum3'>$t</td><td class='xNum3'>$o</td><td class='xNum3'>$etuis</td></tr>";
	}
	$tab .= "</tbody></table>";
	return $tab;
}
// fonctions utilisant la table etuis
function getetui( $n ) {
	global $st_position, $st_vulnerable;
	$etuis = array ( array (0,0,0,0),
	array (1,1,0,0),  array (2,2,1,0),  array (3,3,0,1),  array (4,4,1,1),
	array (5,1,1,0),  array (6,2,0,1),  array (7,3,1,1),  array (8,4,0,0),
	array (9,1,0,1),  array (10,2,1,1), array (11,3,0,0), array (12,4,1,0),
	array (13,1,1,1), array (14,2,0,0), array (15,3,1,0), array (16,4,0,1),
	array (17,1,0,0), array (18,2,1,0), array (19,3,0,1), array (20,4,1,1),
	array (21,1,1,0), array (22,2,0,1), array (23,3,1,1), array (24,4,0,0),
	array (25,1,0,1), array (26,2,1,1), array (27,3,0,0), array (28,4,1,0),
	array (29,1,1,1), array (30,2,0,0), array (31,3,1,0), array (32,4,0,1),
	array (33,1,0,0), array (34,2,1,0), array (35,3,0,1), array (36,4,1,1)
	);
	$etui = [
		'n' 	=> $etuis[$n][0],
		'donneur' => $etuis[$n][1],
		'vulns' => $etuis[$n][2],
		'vuleo' => $etuis[$n][3],
		'info'	=> $st_position[ $etuis[$n][1] ] . " donneur - " . $st_vulnerable[ $etuis[$n][2] + 2*$etuis[$n][3] ],
	];
	return $etui;
};
function getinfoetui( $n ) {
	$etui = getetui( $n );
	return $etui['info'];
}
function liste_etuis( $encours, $paquet ) {
	$first = intval(($encours-1)/$paquet)*$paquet +1;
	$strhtml = "<table style='margin:auto;'><tbody><tr>";
	//$strhtml .= "<td>Etuis&nbsp;</td>";
	for ($i=0;$i<$paquet;$i++) {
		$etui = $first + $i;
		if ( $etui == $encours ) $bg="background-color:lightgreen;";
		else $bg = "";
		$strhtml .= "<td style='border:.5pt solid windowtext; min-width:30px; $bg'>$etui</td>";
	}
	$strhtml .= "</tr></tbody></table>";
	return $strhtml;
}

