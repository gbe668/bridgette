<?php
// identification du club
$prefix = "ttt_";	// trigramme identification du club 
$tt = time();		// heure connexion
// autres paramètres à venir ...
$json = json_encode( array( 'prefix'=>$prefix, 'timex'=>$tt ) );
$encryption = base64_encode( $json );

// Transfert
$location = "https://SERVER_NAME/";

echo json_encode( array( 'token'=>$encryption, 'server'=>$location ) );
?>
