# Lanceur de club

Copier ce modèle dans un dossier propre à chaque club, à côté du dossier commun de l'application ou sur son hébergement prévu. Adapter `$prefix` et `$location` dans les deux fichiers. Ne pas recopier les valeurs d'un club de test vers les autres déploiements.

`index.php` transmet `token` (JSON encodé en Base64 contenant `prefix` et l'horodatage `timex`) et, si présent, `cmd` vers `bridgette.php`. `apkconnect.php` conserve exactement les deux champs JSON `token` et `server`. Le jeton sélectionne le club ; il n'accorde aucun rôle. Les commandes de maintenance ne sont pas autorisées par ce jeton.

Les lanceurs existants ne doivent pas être remplacés automatiquement : conserver leurs URLs, préfixes et présentation. Le modèle n'introduit pas de changement de signature de jeton. Voir [installation](../INSTALLATION.md).
