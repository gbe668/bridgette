<?php
$errmsg = $_GET['errmsg'];
?>

<!DOCTYPE HTML>
<html>
    <head>	
        <title>Bridg'ette</title>	
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">	
        <meta name="viewport" content="width=device-width, initial-scale=1">	
        <link rel="stylesheet" href="css/bridgestylesheet.css" />
		<link rel="icon" type="image/x-icon" href="images/favicon.ico">
    </head>

<body>	
	<div style="text-align: center">
	<p><img src="images/bridgette.png" alt="bridge" style="width:90%; max-width:350px;" /></p>
	<h2>Erreur application</h2>
	<?php echo "<p>$errmsg</p>"; ?>
	<p>Pour plus d'explications: <a href="https://www.coiffier.org/bridgette"> site bridgette</a></p>
	</div>
</body>
</html>