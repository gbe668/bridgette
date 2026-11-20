# Installation et mise à jour

## Prérequis

Serveur PHP compatible avec le code livré (contrôles de syntaxe effectués avec PHP 8.3), MySQL/MariaDB, extensions PDO MySQL, mbstring et OpenSSL ; Apache avec réécriture pour `.htaccess`. Utiliser HTTPS sur les hébergements. Les navigateurs doivent prendre en charge JavaScript, Web Workers et WebAssembly pour DDS.

## Installation neuve

1. Déposer le contenu de `appli/` dans le répertoire applicatif choisi. Ne pas servir les outils et tests comme des pages publiques.
2. Créer une base et un utilisateur MySQL adaptés. Préparer `clubs/conf_<SERVER_NAME>.php` à partir du modèle, avec les valeurs de cet hébergement.
3. Choisir un préfixe de trois caractères alphanumériques suivis de `_` (par exemple `ttt_`). Préparer `clubs/<préfixe>configuration.php` à partir du modèle et renseigner notamment le SMTP. Ne jamais publier ces fichiers privés.
4. Préparer l'activation temporaire du superviseur avec l'outil CLI, puis créer les tables et l'administrateur depuis le superviseur. Suivre [sa documentation](doc/SUPERVISOR.md). La génération d'activation ne se connecte pas à MySQL.
5. Adapter un lanceur dans `launcher/` au club et à l'URL réelle. Le même serveur peut héberger un seul club ou plusieurs préfixes et lanceurs. La sélection du club ne vaut pas authentification.
6. Configurer les accès et paramètres du club, contrôler les permissions d'écriture, puis vérifier les parcours joueurs, directeurs et administrateurs.

## Mise à jour d'une installation existante

Sauvegarder les fichiers privés et la base ; essayer d'abord sur une copie de test. Conserver les configurations `clubs/`, les paramètres/calendriers, les préfixes, les lanceurs et les intégrations Joomla. Ne pas synchroniser les modèles sur des fichiers réels et ne pas supprimer les fichiers de club absents de ce dépôt.

Livrer ensemble les modules PHP/JS et leurs dépendances de sécurité. La nouvelle version remplace les anciens scripts applicatifs : les routes retirées ne doivent pas rester accessibles du seul fait qu'un transfert FTP ne supprime pas les anciens fichiers. Comparer la liste des suppressions de la pull request ; ne pas appliquer cette suppression au dossier privé `clubs/`.

La mise à jour depuis juillet comprend notamment les contrôles d'accès/CSRF, tickets Android, mots de passe et maintenance. Consulter les documents de migration dans `doc/` ; ne pas exécuter une installation ou un patch arbitraire sur une base existante. Le superviseur ne migre pas automatiquement les schémas existants. Valider les deux configurations d'hébergement, les APK déjà installées et les iframes Joomla avant une mise en production.

Les nouveaux tournois Howell utilisent un nombre explicite de paquets. Aucun tournoi déjà démarré n'est réparé automatiquement par cette livraison.

## Sources de la version utilisée

Conserver le commit de la version déployée. Fournir aux utilisateurs un lien vers les sources correspondant à cette version (commit ou tag GitHub), avec les modèles et scripts nécessaires à son installation. Des modifications locales supplémentaires exigent d'actualiser cet accès. Le dépôt ne contient ni les secrets ni les bases des clubs. Compléter la provenance et les sources de compilation de l'adaptation WebAssembly DDS.
