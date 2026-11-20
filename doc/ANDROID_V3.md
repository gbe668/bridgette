# Bridgette V3 — connexion persistante compatible avec V2

## Fonctionnement

Version Android 3.2/code 12, projet `C:/Android/BridgetteV3`, compilé dans `outputs/BridgetteV3`. V2 reste intacte. La variante release conserve `org.coiffier.bridgette`. La variante debug de test utilise `org.coiffier.bridgette.test`, le nom « Bridgette V3 Test » et le suffixe de version `-test` : elle peut être installée à côté de V2, avec ses propres données et cookies. Les clubs et connexions de V2 ne sont pas importés dans cette application distincte.

La première connexion à chaque club nécessite le mot de passe en POST sur HTTPS. V3 conserve ensuite un jeton aléatoire chiffré AES-GCM avec une clé Android Keystore non exportable. Aucun mot de passe dans les préférences ; les trois anciennes clés sont effacées à l'entrée de l'écran principal. Adresse, mail et nom du club restent mémorisés.

Lors d'une sélection ultérieure du club, V3 présente le jeton dans un corps POST. Le serveur vérifie le compte, le club et l'expiration, renouvelle sa validité à trente jours et émet un nouveau ticket WebView de cinq minutes. Cette reconnexion ne dépend pas de la conservation du cookie WebView ni du processus Android. Le retour au premier plan de l'accueil ou du tournoi renouvelle également le jeton, sans modifier la page ni émettre de ticket inutilisé. Une utilisation hebdomadaire avec accès réseau réussi conserve l'accès sans mot de passe.

Jetons séparés par lanceur, serveur et mail ; aucun secret dans les URL ou les Intent. Le serveur conserve uniquement l'empreinte du jeton. Le secret reste stable pendant sa durée de vie afin qu'une réponse HTTP perdue n'invalide pas la copie locale. Le renouvellement de l'enregistrement utilise une comparaison atomique de sa version, sans dépendre d'un moteur transactionnel MySQL.

Mot de passe demandé si aucun jeton n'existe, après trente jours sans renouvellement réussi, révocation, changement du mot de passe/mail, suppression/désactivation du compte, perte de la clé Keystore ou suppression des données Android. Une panne réseau/SQL conserve le jeton et invite à réessayer. Sauvegarde de l'application désactivée et préférences exclues des règles de sauvegarde/transfert ; les sauvegardes historiques ne sont pas supprimées.

## Retour et déconnexion

Le bouton Retour Android et le menu « Changer de club » reviennent au choix des clubs sans supprimer aucune connexion mémorisée. Après une première identification dans chaque club, passer du club du lundi à celui du mardi puis revenir au premier ne demande plus le mot de passe tant que leurs jetons restent valides. Sur un serveur commun, changer de club peut remplacer la session WebView ; le jeton persistant propre à chaque club permet d'en ouvrir une nouvelle sans mot de passe.

Une action distincte « Supprimer la connexion mémorisée » supprime celle du club courant de l'appareil, demande sa révocation serveur, invalide sa session WebView lorsque son cookie est envoyé au serveur, puis efface les cookies WebView de l'application. Les jetons des autres clubs restent mémorisés. RAZ d'un club supprime aussi son jeton local et demande sa révocation. Les versions V3 de test 3.1 utilisaient « Se déconnecter du serveur » pour cette suppression : ce choix imposait un nouveau mot de passe au retour dans le club.

Si le serveur est inaccessible, la suppression locale a lieu et un message précise que la révocation serveur n'est pas confirmée. Le jeton serveur non révoqué reste soumis à son expiration et au changement du mot de passe. Changer le mot de passe invalide les jetons persistants à leur prochaine utilisation ; cela ne révoque pas globalement les sessions WebView déjà authentifiées.

## Compatibilité et lanceurs

V2 continue en GET avec exactement les cinq champs JSON existants et le même ticket `userid`. POST sans option conserve ces champs. Seul POST avec `remember_device=1` reçoit le champ supplémentaire `device_token`, après vérification du mot de passe. Aucune mise à jour immédiate de V2 imposée ; seuil minimum de version APK inchangé.

Les lanceurs `index.php` et `apkconnect.php` restent inchangés : mêmes champs `server`/`token` et préfixes. `server` doit être l'adresse HTTPS réelle de l'application commune, racine ou sous-dossier, avec ou sans barre finale. Ne pas diffuser les valeurs locales du lanceur de test aux autres clubs. Le jeton de club sélectionne une configuration ; il ne remplace pas le secret persistant ni le ticket WebView.

La route native `apk_resume.php` accepte uniquement POST avec `device_token`. Actions : `resume` (défaut, renouvelle et délivre un ticket), `touch` (renouvelle sans ticket), `revoke` (supprime cette connexion). Aucun droit accordé à partir d'un cookie seul. Les formulaires navigateur et leur CSRF restent inchangés. HTTPS exigé hors requêtes locales de test ; sur un hébergement à proxy, vérifier que PHP reçoit un indicateur HTTPS fiable. Aucun en-tête proxy client n'est accepté automatiquement.

Enregistrements techniques `adr_` dans la table `sessions` existante, identifiants de 32 caractères, empreinte du mot de passe courant pour l'invalidation. Le gestionnaire PHP ignore ce namespace en lecture, écriture, destruction et rafraîchissement. Nettoyage limité aux jetons persistants inactifs depuis plus de trente jours. Aucune migration du schéma ou des tables de clubs.

## Installation serveur avant Android

Installer ensemble les six fichiers de `FICHIERS_ANDROID_PERSISTANT.txt` sur le serveur commun de chacun des deux hébergements. Archive : `outputs/correctif-android-persistant.zip`, arborescence `appli` conservée.

Déposer d'abord les nouveaux `security_android_devices.php` et `apk_resume.php`, puis le gestionnaire de sessions et les routes, puis `security_android.php` et `apk_loguserin.php`. Les autres modules de sécurité déjà installés restent nécessaires. V2 fonctionne pendant la transition. Aucun lanceur à retransférer.

Ouvrir `C:/Android/BridgetteV3` dans Android Studio et générer la distribution release avec la clé de signature existante. Vérifier que le code 12 est supérieur aux versions distribuées. `outputs/BridgetteV3-debug.apk` sert aux essais et s'installe comme une application distincte à côté de V2 ; elle ne met pas V2 à jour. V3 ne force plus la signature debug pour release ; utiliser la configuration existante / Google Play App Signing. Ne pas désinstaller V2 pour installer l'application de test.

GET restera accepté jusqu'à une étape explicite de retrait. Les identifiants GET de V2 peuvent encore apparaître dans les journaux d'URL. Pour V3, exclure également les corps de connexion/reconnexion et les cookies des journaux sensibles.

## Vérifications

Tests locaux : compatibilité V2 GET/POST/JSON, émission opt-in V3, reconnexion sans cookie natif, ticket consommé par une WebView distincte, renouvellement hebdomadaire, expiration, révocation de session, isolation des clubs/appareils, secret absent des enregistrements, méthodes/entrées invalides, HTTPS hors local, erreurs SQL et conflits de renouvellement sur base simulée. Namespace testé dans le gestionnaire. Tests Android d'encodage/adresses et AES-GCM : altération, mauvaise clé, mauvais club, format tronqué et IV différent. Compilation debug et Android Lint réussis, avertissements hérités présents.

À valider sur appareil et un club de test des deux hébergements : mise à jour avec bonne signature, première connexion, redémarrage du téléphone, Retour/menu, alternance de clubs/comptes, usage hebdomadaire, perte réseau, RAZ/Déconnexion, récupération/changement du mot de passe, pré-inscription (25), jeu Mitchell/Howell (60–69), web directeur (40–49), résultats (20–29) et Joomla. Vérifier certificats, chemins, journaux et MySQL réel.

Aucun déploiement distant, accès à MySQL applicatif, courriel réel ou installation sur téléphone. Les tests locaux ne valident pas le Keystore/cookies d'un appareil réel ni la concurrence MySQL des hébergements.

