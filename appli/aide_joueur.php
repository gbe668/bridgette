<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afficher un lien PDF</title>
</head>
<body>
 
<?php
// Chemin vers le fichier PDF
$pdfFilePath = 'aide_joueur.pdf';
 
// Vérifie si le fichier PDF existe
if (file_exists($pdfFilePath)) {
    // Affiche le lien vers le fichier PDF
    echo '<a href="' . $pdfFilePath . '" target="_blank">Cliquez ici pour ouvrir le PDF</a>';
} else {
    // Si le fichier n'existe pas, affiche un message d'erreur
    echo 'Le fichier PDF n\'existe pas.';
}
?>
 
</body>
</html>