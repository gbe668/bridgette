<?php
// identification du club
$prefix = "ttt_";	// trigramme identification du club 
$tt = time();		// heure connexion
// autres paramètres à venir ...
$json = json_encode( array( 'prefix'=>$prefix, 'timex'=>$tt ) );
$encryption = base64_encode( $json );

// Transfert
$location = "https://SERVER_NAME/";

$transfer = $location."/bridgette.php?token=".$encryption;
if ( isset($_GET['cmd']) ) {
	$cmd = $_GET['cmd'];
	$transfer .= "&cmd=".$cmd;
}
?>
<!DOCTYPE HTML>
<html>
    <head>	
        <title>Bridg'ette</title>	
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
        <meta name="viewport" content="width=device-width, initial-scale=1">	
        <link rel="stylesheet" href="https://SERVER_NAME/css/bridgestylesheet.css" />
		<link rel="icon" type="image/x-icon" href="https://SERVER_NAME/images/favicon.ico">
    </head>
<script>
function gotobridgette() {
	var nextstring = "<?php echo $transfer; ?>";
	location.replace( nextstring );
};
</script>

<body>	
	<div style="text-align: center">
	<p><img src="https://SERVER_NAME/images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2>Club de bridge</h2>
	<h3>Cliquez sur le bouton<br>pour accéder au site de l'application</h3>
	<p><button class="myStartButton" onclick="gotobridgette()">Application<br>bridgette</button></p>
	<p><img src="logo.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	</div>
</body>
</html>