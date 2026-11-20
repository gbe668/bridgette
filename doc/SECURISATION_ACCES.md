# Sécurisation des accès — 16 septembre 2026

Mise à jour du 30 septembre 2026 : la migration SQL/POST/CSRF est décrite dans [ETAPE2_SQL_CSRF.md](ETAPE2_SQL_CSRF.md), avec la liste de transfert [FICHIERS_ETAPE2.txt](FICHIERS_ETAPE2.txt). Ce complément remplace les mentions ci-dessous présentant POST/CSRF et les interpolations SQL actives comme une étape future. Les mots de passe et les liens de récupération restent à traiter séparément.

## Périmètre

Cette étape porte sur les commandes de maintenance, la preuve d'identité et les autorisations des écritures. Les contrôles communs se trouvent dans `appli/security.php` et la liste explicite des routes protégées dans `appli/security_routes.php`, appelée à la fin du chargement de `bridgette_bdd.php`, avant l'exécution des actions des pages.

- Administration : création des comptes administrateur/directeur/formateur, suppression des comptes de direction et purge des anciens tournois.
- Direction, états 40–49 : création, préparation, démarrage, arrêt, clôture et suppression des tournois ; annuaire ; paramètres ; calendrier ; corrections des résultats ; envoi des résultats. Les anciennes opérations de contacts, sans propriétaire enregistré en base, sont réservées à la direction.
- Pré-inscription, états 20–29 : un joueur authentifié agit sur son inscription (`ida`) et ses coordonnées (`idjoueur`). La direction conserve les opérations sur les autres joueurs. La sélection d'une date peut créer le tournoi de pré-inscription : elle exige donc également une connexion.
- Joueurs, états 60–69 : l'accès web respecte les modes Non/M/P décrits ci-dessous. La saisie exige un tournoi en phase de jeu et, sur le web, le code lorsque sa vérification est configurée. Les diagrammes suivent la même politique ; le tournoi zéro reste réservé à la formation. Les compteurs de relais exigent des numéros de paires dans les limites du tournoi et un paquet conforme.
- Le sondage public `getpositionsjoueurs.php` ne recalcule plus les résultats en base. Le calcul est effectué après la dernière saisie autorisée ; les parcours directeur conservent leurs calculs. La mise à jour de fin de connexion depuis une page joueur applique également Non/M/P et reste limitée aux paires dont le compteur dépasse les positions prévues.

Les contrôles vérifient les droits réels ; le numéro d'une page n'est pas une autorisation. Les refus arrêtent le traitement côté serveur. Les identifiants numériques des mutations sont validés. Les connexions web/Android, les écritures de points et de résultats, les coordonnées et certaines opérations d'annuaire utilisent désormais des paramètres PDO liés.

## Compatibilité et reconnexion

Depuis le 17 septembre, les lignes de sessions sans activité depuis plus de 100 jours sont purgées automatiquement une fois par mois civil UTC, à la première visite sur chaque base d’hébergement. Le marqueur partagé `gc_monthly` et un verrou MySQL évitent une purge par club ou des exécutions concurrentes. Le nettoyage probabiliste PHP est désactivé pour le gestionnaire MySQL. Les tickets Android conservent leur expiration et leur nettoyage dédiés de cinq minutes ; cette maintenance ne modifie aucune durée d’autorisation. Déployer ensemble `configuration.php`, `session_maintenance.php` et `src/mysql.sessions.php`. Aucun changement de schéma requis ; seul un enregistrement technique est ajouté lors de la première purge réussie.

Le format des jetons de sélection du club et les lanceurs ne changent pas. Le jeton reste une sélection de club, sans valeur d'authentification. Son préfixe est validé avant de construire les chemins et noms de tables. Le préfixe est obligatoire : exactement trois caractères alphanumériques ASCII suivis de `_` (exemples : `tst_`, `abc_`, `a12_`). Cette règle commune est appliquée aux sélections web/Android, au superviseur et aux outils CLI.

Un changement de club par jeton **ou par cookie** efface les droits, l'identité joueur, le code du tournoi et les états de navigation de la session. Les rôles authentifiés sont liés explicitement au club. Un nouveau jeton du même club ne réactive pas un rôle expiré.

Après installation de cette correction, les joueurs et responsables doivent se reconnecter : les anciens cookies `bridgette_user` et les anciens rôles non liés au club ne sont pas convertis en identités fiables. Les identifiants de session sont renouvelés à la connexion. Le cookie de session devient HttpOnly et Secure sous HTTPS ; aucune nouvelle règle SameSite ou d'interdiction des iframes n'est imposée.

La consultation publique des résultats et les routes `api_displaytournoi.php` / `api_existetournoiclos.php` restent publiques. Le mode Non autorise le parcours de jeu sans identification selon le choix explicite du directeur. La pré-inscription et les modifications des coordonnées (états 20–29) restent soumises à une identité joueur ou aux droits du directeur ; le paramètre de sécurité présenté ici porte sur le tournoi en cours.

### Niveaux de sécurité du parcours joueur — correction du 17 septembre

| Paramètre web `checkuser` | Accès au tournoi en cours |
| --- | --- |
| Non (`0`) | Aucune identification joueur demandée ; choix de la paire dans le parcours habituel. |
| M (`1`) | Identification comme membre actif du club, même sans inscription au tournoi ; prêt du téléphone à un participant possible. |
| P (`2`) | Identification comme membre actif et inscription au tournoi. L'identité du propriétaire du téléphone ne restreint pas la saisie à sa seule paire. |

Le code `checkin` est indépendant de ces trois modes et reste exigé sur le web lorsqu'il est activé. Les contrôles s'appliquent aussi aux appels directs de saisie, diagrammes et relais, ainsi qu'aux pages de jeu avant leurs éventuelles mises à jour. Le statut d'administrateur/directeur reste protégé et ne peut pas être obtenu par un accès joueur anonyme.

L'identification conserve uniquement un identifiant de session opaque dans un cookie HttpOnly, Secure sous HTTPS. Sa durée et l'horodatage de l'identité serveur sont initialisés à la connexion puis renouvelés pour 30 jours à l'entrée dans les pages de jeu 61–65. Après 30 jours sans renouvellement, l'identité est refusée ; une simple consultation de l'accueil ou des résultats ne prolonge pas ce délai. Une identité déjà expirée n'est jamais réactivée par la navigation. La fermeture du navigateur conserve ce cookie, sauf suppression par le navigateur ou l'utilisateur. La déconnexion explicite et le changement de club continuent à révoquer l'identité.

Android conserve son authentification native et son parcours existant vers la paire du participant inscrit. Selon la précision du propriétaire, aucun code de tournoi supplémentaire n'y est demandé : cette exemption exige la preuve de connexion native conservée côté serveur, jamais un User-Agent Android ou un identifiant fourni dans l'URL. Les formats des tickets et les sources de l'APK ne changent pas.

Déployer ensemble les fichiers modifiés `security.php`, `security_routes.php`, `bridgette_bdd.php`, `bridge60.php`, `bridge36.php`, `mitch61.php`, `howell61.php` et `loguserout.php`, avec les dépendances déjà installées `club_prefix.php` et `security_android.php`. Aucun changement des paramètres enregistrés, du schéma ou des lanceurs. Les identités serveur encore valides sont conservées et leur cookie prolongé lors de la prochaine entrée dans le jeu ; les anciens cookies contenant seulement un identifiant joueur ne sont pas réhabilités.

### Android

Sources examinées : `MainActivity.java`, `MainPlayer.java` et `PlayTournoi.java` dans `C:/Android/BridgetteV2/app/src/main/java/org/coiffier/bridgette`.

La connexion native utilise Volley, puis les pages sont chargées avec `WebView.loadUrl()`. Aucun transfert de cookie de session Volley vers la WebView n'a été trouvé. Les paramètres `userid`, `paire` et le jeton de club ne prouvent pas l'identité du joueur.

La solution initiale de redirection vers une connexion web a été remplacée après validation utilisateur : les WebViews de cette version ne définissent pas de `WebViewClient` et peuvent ouvrir les redirections dans le navigateur. Les pages Android ne redirigent donc plus vers `loguserin.php`.

`apk_loguserin.php` conserve les noms des champs JSON. Après authentification, **`userid` contient désormais une chaîne opaque `apk1.…`, et non un identifiant numérique**. La compatibilité a été vérifiée dans les sources fournies : `getString("userid")`, transmission via Intent, puis concaténation dans l'URL, sans conversion numérique. Les versions différentes de ces sources qui interpréteraient ce champ comme un nombre nécessiteraient une adaptation ; aucune compilation Android n'a été effectuée.

Le ticket contient 256 bits aléatoires, expire après cinq minutes et n'est utilisable qu'une fois pour créer une session WebView. Son empreinte, le club et l'identifiant réel du joueur sont conservés temporairement dans la table `sessions`, avec des identifiants réservés `apk_…` de 32 caractères. Le gestionnaire des sessions exclut explicitement ces lignes de ses lectures/écritures/suppressions normales. Aucun changement de schéma n'est requis. La consommation utilise une suppression conditionnelle atomique, sans dépendre du moteur transactionnel de la table. Un ticket d'un autre club ou déjà consommé est refusé.

La première page Android consomme le ticket, renouvelle l'identifiant de session et établit l'identité serveur sans redirection. Elle efface les éventuels droits d'un utilisateur précédent. Les visites suivantes de l'accueil et de la pré-inscription avec le même ticket utilisent la session déjà établie ; le JavaScript des pages reçoit l'identifiant numérique réel. Les pages de jeu utilisent le cookie partagé entre WebViews. Si la session a disparu, un message dans l'application demande une reconnexion native, sans ouvrir le site web. Les réponses concernées sont non stockables en cache et les pages limitent Referer à l'origine pour ne pas transmettre le ticket dans cet en-tête.

Installer ensemble `security.php`, `security_android.php`, `security_routes.php`, `apk_loguserin.php`, `apk_bridgette.php`, `apk_bridge25.php` et `src/mysql.sessions.php`, puis utiliser le menu Déconnexion de l'APK et se reconnecter pour obtenir un nouveau ticket. Aucun déploiement distant n'a été effectué.

Ne pas supprimer `__DIR__` : celui-ci concerne uniquement le chargement du fichier PHP sur le serveur. `$relpgm` est conservé. La fonction `securityAppPath()` reste disponible pour les redirections web, mais n'est plus utilisée pour authentifier les pages Android.

### Origine des mutations

Les anciens appels GET sont conservés à cette étape. Les écritures exigent un en-tête Origin, ou à défaut Referer, correspondant exactement au schéma, à l'hôte et au port de l'application. Une origine absente ou étrangère est refusée ; une réponse de mutation n'est pas mise en cache. Cela préserve les AJAX et formulaires servis par l'application, y compris dans les WebViews.

À vérifier sur chaque hébergement : transmission de HTTPS par le serveur, éventuel proxy et politiques supprimant Referer. Ne pas désactiver le contrôle pour contourner un défaut de configuration. Une migration complète vers POST et jetons CSRF reste à faire ; cette protection d'origine ne remplace pas le traitement des failles XSS.

## Maintenance hors HTTP

Les commandes URL `install`, `patch`, `droptables` et `gc` sont refusées avant toute connexion à la base. Le lanceur peut encore transmettre `cmd`, mais ne déclenche plus de maintenance.

Sur un hébergement sans SSH (notamment OVH Perso), utiliser désormais `appli/supervisor.php` : accès opérateur indépendant, activation temporaire déposée par FTP, clubs explicitement autorisés, POST avec CSRF et confirmation à usage unique. Le guide `SUPERVISOR.md` décrit la préparation locale avec Wamp, l’installation et la fermeture de l’accès. Aucun compte administrateur de club ne permet à lui seul d’y entrer. Les anciennes commandes URL restent bloquées.

Lorsqu’un accès shell est disponible, PHP en ligne de commande reste utilisable :

```text
php appli/maintenance.php --host=HOTE_CONFIGURE --prefix=PREFIXE_CLUB --command=install
php appli/maintenance.php --host=HOTE_CONFIGURE --prefix=PREFIXE_CLUB --command=patch
php appli/maintenance.php --host=HOTE_CONFIGURE --prefix=PREFIXE_CLUB --command=gc
```

`HOTE_CONFIGURE` désigne le fichier `clubs/conf_HOTE_CONFIGURE.php` existant. Le préfixe doit correspondre à la configuration du club. Le patch doit déjà être présent localement dans `appli/patch.php`.

La suppression des tables exige en plus `--confirm=PREFIXE_CLUB`, identique au préfixe sélectionné. Sauvegarder et contrôler le club ciblé avant cette opération. Aucune commande de maintenance n'a été exécutée pendant cette correction.

Le nettoyage appelle maintenant `gc()` avec une durée en secondes (100 jours). La table `sessions` étant commune, ce nettoyage concerne les sessions anciennes de l'ensemble de la base, pas uniquement celles du club sélectionné.

L'import SQL brut `importbddpart.php` est désactivé : un administrateur de club ne doit pas pouvoir exécuter du SQL arbitraire sur les tables des autres clubs. Utiliser un outil opérateur hors HTTP pour une restauration. Les anciennes copies exécutables et les scripts `oldpatches` sont refusés par HTTP, mais conservés sur disque.

## Vérifications et limites

Commande reproductible, avec le chemin du PHP local en argument si nécessaire :

```text
python tests/run_security.py CHEMIN_VERS_PHP
```

Vérifications réalisées avec PHP 8.3.28 :

- Syntaxe de 220 fichiers PHP, tests compris.
- 151 cas de contrôle d'accès : rôles admis et refusés, expiration, changement de club, identité usurpée par cookie, coordonnées d'un autre joueur, compte supprimé, participation, formation, origines étrangères/absentes et identifiants malformés. Base simulée, aucune écriture réelle.
- 7 cas utilisant les vraies fonctions d'identité : cookie ignoré, identité serveur, rôle lié au club, reprise du même club, changement de club, anciens rôles et expiration.
- 10 requêtes HTTP locales vers les entrées de maintenance, import et anciennes copies : refus 403 avant chargement de la configuration/base.

Pas de connexion à la base applicative, de modification de données existantes, d'envoi de mail ou de déploiement. Aucun tournoi complet ni appareil Android n'a été testé.

Un test de cycle de vie des sessions a ensuite été ajouté (`tests/security_session_lifecycle.php`) après le signalement WampServer : il reproduisait l'échec d'écriture après renouvellement d'identifiant. Le gestionnaire rouvre maintenant sa connexion lors de `open()` ; le test vérifie la persistance des droits et la révocation lors du changement de club, sans base réelle.

La vérification Android a ensuite été complétée : 12 cas de sécurité des tickets (falsification, expiration, rejeu, mauvais club et nettoyage isolé), 18 cas HTTP avec deux clients à cookies séparés (connexion native puis WebView), et exclusion des tickets par le gestionnaire de sessions. Les parcours HTTP refusent toute redirection et couvrent racine et sous-dossier. Authentification et stockage sont simulés ; il reste à confirmer le fonctionnement sur l'APK et MySQL réels. La suite complète passe, avec 225 fichiers PHP contrôlés.

Avant déploiement, valider sur une base isolée : connexion/reconnexion et changement de club, pré-inscription et partenaire, tournoi Mitchell et Howell avec relais, saisie depuis NS et EO, dernière donne et classements, correction par le directeur, diagrammes/formation, clôture, interfaces Joomla et connexion dans une WebView Android installée. Vérifier les deux configurations d'hébergement.

Il ne s'agit pas d'un audit exhaustif : poursuivre la migration POST/CSRF, l'audit des autres requêtes SQL et des paramètres métier (notamment adversaire/étui/mouvement et concurrence), la migration des mots de passe et des liens de réinitialisation, ainsi que la protection des fichiers du déploiement. Aucun changement de schéma n'est nécessaire pour cette étape.
