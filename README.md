# Bridg’ette

Application web de gestion des tournois pour les clubs de bridge : joueurs, pré-inscriptions, mouvements Mitchell et Howell, saisie et correction des résultats, classement et analyse des donnes.

Cette mise à jour rassemble les sources web locales au 20 novembre 2026. Le dossier `appli/` correspond au dossier de développement local `app1/` ; son nom de déploiement reste configurable. Les dossiers privés des clubs ne sont pas distribués.

- [Installation et mise à jour](INSTALLATION.md)
- [Lanceur par club](launcher/README.md)
- [Validation de cette livraison](VALIDATION.md)
- [Licences et composants tiers](appli/license_bridgette.txt)
- [Maintenance opérateur](doc/SUPERVISOR.md)

## Hébergements

L'application peut être installée pour un seul club sur son hébergement, ou partagée entre plusieurs clubs disposant chacun de leur lanceur. Conserver la séparation des préfixes, configurations, tables et sessions. Les pages `api_*.php` sont également utilisées dans des iframes Joomla ; les pages `apk_*.php` servent les applications Android existantes.

## Android

Les sources natives Android sont maintenues séparément et ne sont pas incluses dans cette livraison web. L'APK déjà présente dans l'historique du dépôt est conservée ; elle n'a pas été reconstruite ni certifiée comme correspondant à cette livraison. Aucun nouveau binaire Android n'est publié ici.

## Licence

Le code original est sous GNU GPL version 3 ou ultérieure. Les composants tiers conservent leurs licences : notamment PHP-MySQL-Sessions sous AGPLv3, PHPMailer sous LGPLv2.1 et DDS sous Apache 2.0. L'accès aux sources de l'ensemble combiné relève également des dispositions réseau de l'article 13 de l'AGPLv3. Voir les notices et textes complets dans `appli/`.

L'inventaire précis de l'adaptation WebAssembly DDS et de ses sources de compilation reste à compléter ; la présente synchronisation ne constitue pas un audit exhaustif de toutes les dépendances.
