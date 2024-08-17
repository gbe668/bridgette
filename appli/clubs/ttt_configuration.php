<?php
// utilisation OVH
$config = array(
	// Site developpement = 1 / démonstration = 2 / déployé =3
	'site'	=> 2,
	'titre' => "Site exemple",
	'urlbridgette' => 'https://exemple.domain.org',
	'sitename' => 'Bridgette',
	
	// seuil de la taille de base de données générant un message d'avertissement
	'maxSizeBDD' => 10000,		// kilo octets

	// Mailing des résultats et warnings
	'mail_Host'		=> "ssl0.ovh.net",  
	'mail_Username'	=> "bridgette@domain.org",
	'mail_Password'	=> "password",
	'mail_Port'		=> 465,
	'mail_setFrom'	=> "bridgette@domain.org",
	'mail_admin'	=> "bridgette@domain.org",	// administrateur
	
	// Dimensionnement selon abonnement du club
	'max_noclub'	=> 200,		// tenir compte de $min_noclub = 100
);
?>