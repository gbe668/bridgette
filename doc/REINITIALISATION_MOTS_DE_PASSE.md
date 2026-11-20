# Réinitialisation des mots de passe — 6 octobre 2026

Cette amélioration concerne les comptes joueurs et les demandes de récupération web/Android. Depuis le 7 octobre, les nouveaux mots de passe utilisent le stockage moderne décrit dans MODERNISATION_MOTS_DE_PASSE.md. La vérification historique des SHA-256 reste réservée à la transition des comptes existants.

## Comportement

- Le courriel contient un secret aléatoire de 32 octets, valable une heure. Seule son empreinte est stockée. Le lien est lié au joueur et au club ; une nouvelle demande autorisée remplace le précédent lien.
- L'affichage du formulaire ne consomme pas le lien. Le changement nécessite un POST avec le jeton CSRF de la session. Une suppression conditionnelle atomique du ticket précède la mise à jour : deux soumissions ne peuvent pas utiliser le même ticket, y compris avec des tables non transactionnelles. Un échec après consommation impose une nouvelle demande.
- Le compte doit toujours être actif, avec la même adresse et le même mot de passe qu'à la demande. Un changement effectué ailleurs rend l'ancien lien inutilisable. La mise à jour vérifie aussi ces conditions en SQL.
- Les demandes sont limitées à trois par adresse et vingt par IP sur une fenêtre d'une heure, par club. Les compteurs sont atomiques et indépendants des cookies ; REMOTE_ADDR est utilisé sans faire confiance aux en-têtes de proxy. Avec un proxy partagé, le plafond IP peut donc concerner plusieurs utilisateurs : à valider sur chaque hébergement.
- Les réponses ne révèlent pas l'existence du compte. Les comptes absents, désactivés ou avec une adresse dupliquée ne reçoivent pas de courriel. Cette mesure uniformise le message, sans garantir des durées de réponse identiques aux envois SMTP.
- Les liens conservent le paramètre de sélection du club `token`, distinct du secret de récupération `phrase`. Ils utilisent le domaine de configuration SERVER_NAME et le dossier du script, y compris avec le PATH_INFO Android `.php/`. HTTPS est utilisé hors localhost/127.0.0.1 ; HTTP local et port local conservés. Aucun changement du lanceur `appli_tst`.
- Les erreurs SMTP ne sont plus affichées dans la réponse. Un envoi signalé en échec invalide le ticket correspondant. Les erreurs sont journalisées sans adresse ni jeton.

## Stockage et compatibilité

La table technique `sessions` existante accueille les tickets `pwd_` et compteurs `rlm_`. Ces identifiants de 32 caractères comportent un caractère interdit dans les identifiants de session PHP, et le gestionnaire de sessions refuse explicitement leur lecture, modification et suppression comme sessions navigateur. Le stockage est séparé par club. Le nettoyage historique des sessions supprime aussi les anciennes entrées techniques ; aucune table ni colonne nouvelle et aucune migration SQL.

Les APK installés appellent `apk_sendmailpwd.php/` en GET et lisent `login`/`message` : contrat conservé, réponse discrète avec `login=0`. Les sources Android `MainActivity.java`, notamment `changePassword()`, ont été examinées. Les deux pages de changement web et `apk_` utilisent le même traitement sécurisé et affichent une invitation à se reconnecter, sans connexion automatique ni redirection imposée à Android. Les états de tournoi 20–29, 40–49 et 60–69, les politiques Non/M/P et les intégrations iframe Joomla ne sont pas modifiés.

## Installation ultérieure

Transférer ensemble les sept fichiers applicatifs de `FICHIERS_REINITIALISATION.txt`, en conservant les configurations de chaque serveur et club. Aucun fichier de tests n'est à transférer. Les anciennes configurations et les lanceurs restent compatibles.

**Tous les anciens liens de récupération sont volontairement refusés** : demander un nouveau courriel après la mise en place. Recharger les formulaires déjà ouverts. Les mots de passe actuels continuent de fonctionner. Les sessions déjà authentifiées conservent leur cycle existant : elles ne sont pas révoquées automatiquement par cette amélioration. Aucun déploiement ni changement de données de production n'a été réalisé.

## Vérifications

`tests/security_password_reset.php` vérifie la génération, l'empreinte stockée, l'expiration, le club, le remplacement, la consommation et le refus d'un perdant lors de la prise du ticket, le rejeu, les modifications du compte, les plafonds, la remise à zéro de fenêtre, l'échec d'envoi, les doublons et les chemins.

`tests/security_password_reset_http.py` exécute les véritables pages avec les protections CSRF réelles, une base simulée persistante et un enregistreur de courriels. Il couvre racine/sous-dossier, GET/JSON Android, refus CSRF, formulaire invalide, autre club, changement, rejeu, réponse uniforme et liens historiques malformés. Aucun appel SMTP ni accès à la base configurée.

Ces contrôles ne remplacent pas la validation de concurrence sur MySQL réel, du SMTP, d'un appareil Android installé ni des deux hébergements. À valider sur un club de test de chacun : réception du courriel, URL HTTPS et dossier, changement puis connexion web/Android, expiration/rejeu, et comportement derrière un éventuel proxy. Vérifier également que le domaine sélectionné par SERVER_NAME est bien le domaine public configuré.

## Correctif du formulaire après essai local

Le formulaire utilise `Referrer-Policy: origin` : les en-têtes Referer n'incluent pas le chemin ni le secret de récupération, et l'origine reste disponible pour le contrôle des POST. L'ancienne politique `no-referrer` pouvait conduire à `Origin: null` et au message « Origine de la requête absente ». Le jeton CSRF reste obligatoire et les origines étrangères/null restent refusées. Recharger la page de réinitialisation avant de réessayer. Si l'amélioration est déjà transférée, seul `appli/changeforgottenpwd.php` doit être resynchronisé pour ce correctif, qui bénéficie aussi à la variante Android.

Les tests HTTP ciblés passent désormais 36 contrôles, dont POST avec origine locale, refus de l'origine étrangère et reproduction du refus pour une origine null. Ils ne remplacent pas un essai dans un navigateur réel.