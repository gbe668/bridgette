# Configuration privée

Ce dossier ne contient que des modèles. Ne jamais versionner les configurations réelles, exports SQL, paramètres/calendriers des clubs ou activations du superviseur.

- Copier `conf_SERVER_NAME.php` vers `conf_<SERVER_NAME>.php`, selon la valeur effective de PHP, et renseigner la connexion MySQL.
- Copier `ttt_configuration.php` vers `<préfixe>configuration.php` pour chaque club. Le préfixe comporte exactement trois caractères alphanumériques suivis de `_`.
- Renseigner localement les paramètres SMTP et les coordonnées du club.
- Les fichiers `<préfixe>parametres.json` et `<préfixe>calendrier.json` sont propres au club. Les préserver lors des mises à jour. L'absence du fichier paramètres applique les valeurs initiales historiques ; ne jamais supprimer un fichier existant pour contourner une erreur.
- Le processus PHP doit pouvoir écrire les paramètres, calendriers et fichiers temporaires nécessaires. Protéger les sauvegardes et fichiers privés contre le téléchargement HTTP selon l'hébergement.

Voir [installation](../../INSTALLATION.md) et [superviseur](../../doc/SUPERVISOR.md).
