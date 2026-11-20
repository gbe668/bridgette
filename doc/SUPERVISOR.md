# Superviseur de maintenance Bridgette

`appli/supervisor.php` permet une maintenance depuis le navigateur, notamment sur un hébergement OVH Perso sans SSH. Il est **désactivé tant que son fichier d’activation n’est pas présent et valide**. Aucun compte administrateur de club ne donne accès au superviseur.

## 1. Préparer l’activation sur votre PC avec WampServer

Dans PowerShell, depuis `C:\wamp64\www\bridgette`, exécuter :

```powershell
& 'C:\wamp64\bin\php\php8.3.28\php.exe' tools/create_supervisor_config.php --host=localhost --prefix=tst_ --label="Club de test"
```

Adapter le chemin de PHP à la version installée. Cette commande locale ne se connecte pas à MySQL. Avec cet exemple, elle crée `appli/clubs/supervisor_access_localhost.php`, génère un mot de passe opérateur aléatoire et l’affiche une seule fois dans le terminal. Le conserver dans un gestionnaire de mots de passe. Seule son empreinte est enregistrée dans le fichier. L’activation expire au bout de **24 heures** ; la commande refuse d’écraser un fichier existant.

Pour OVH, utiliser comme `--host` le nom de serveur fourni par PHP (`SERVER_NAME`), également utilisé pour le fichier de connexion existant `clubs/conf_<hôte>.php`, sans `conf_` ni `.php`. Ce n’est pas le nom du serveur MySQL. Utiliser le préfixe réel du club, jamais automatiquement `tst_`. Exemple de syntaxe : `--host=applidev.coiffier.org --prefix=abc_ --label="Nom du club"`. Le préfixe est obligatoire : exactement trois caractères alphanumériques (lettres ASCII ou chiffres) suivis de `_`. Exemples valides : `tst_`, `abc_`, `a12_`. `abc`, `ab_` et `abcd_` sont refusés.

Pour plusieurs clubs dans la même base, éditer la liste `clubs` du fichier généré avant son dépôt :

```php
'clubs' => [
    'cla_' => 'Club A',
    'clb_' => 'Club B',
],
```

Les préfixes autorisés sont choisis par l’opérateur via FTP, jamais à partir des cookies ou d’un lanceur. Retirer les exemples inutiles. Ne pas publier ce fichier dans un dépôt ou une archive partagée. Un modèle désactivé est également fourni dans `appli/clubs/supervisor_access.example.php`.

Depuis la correction du 17 septembre, toute activation contenant un préfixe d’un autre format est refusée. Corriger sa liste de clubs avant utilisation. Le même contrôle s’applique aux lanceurs, jetons et cookies de sélection du club. Aucune table ni configuration n’est renommée automatiquement : une installation non conforme nécessite une migration coordonnée des noms de tables, fichiers du club et lanceurs. Déployer `club_prefix.php` avec les fichiers PHP modifiés qui l’utilisent (`security.php`, `supervisor_lib.php`, `maintenance.php` et le générateur local).

## 2. Déposer et ouvrir

### Synchronisation WinSCP de plusieurs serveurs

Chaque domaine possède son fichier d’activation : `clubs/supervisor_access_<domaine>.php`. Par exemple, `--host=applidev.coiffier.org` crée `supervisor_access_applidev.coiffier.org.php`, tandis que `--host=localhost` crée `supervisor_access_localhost.php`. Les noms sont normalisés en minuscules ; ne pas inclure `https://`, un port ou un chemin. Le champ `host` doit correspondre au domaine du fichier et à `SERVER_NAME`, comme pour les connexions MySQL existantes.

Exécuter le générateur une fois pour chaque serveur avec son domaine et son préfixe réel, puis compléter sa liste `clubs` si nécessaire. Les fichiers peuvent coexister dans `appli/clubs` et être synchronisés ensemble vers les deux serveurs. Chacun ne charge que son fichier, sans repli sur celui d’un autre domaine ou sur l’ancien nom générique. Les activations, mots de passe et échéances restent propres à chaque fichier. Pour un alias de domaine, utiliser un nom de serveur cohérent avec les fichiers `conf_<hôte>.php` existants.

**Migration :** renommer chaque ancien `clubs/supervisor_access.php` en `clubs/supervisor_access_<valeur-du-champ-host>.php`, sans modifier son contenu ni prolonger son expiration. Récupérer d’abord les configurations distinctes des deux serveurs si elles n’existent qu’à distance, afin de les conserver côte à côte dans la copie locale. L’ancien nom unique est désormais ignoré. Le fichier local présent a été renommé en `supervisor_access_localhost.php` lors de cette correction ; les fichiers distants n’ont pas été modifiés.

Après intervention, retirer également l’activation de la copie locale utilisée par WinSCP, afin qu’une synchronisation suivante ne la remette pas sur le serveur. Si WinSCP ne propage pas les suppressions, supprimer explicitement le fichier distant aussi. Aucun réglage WinSCP n’est modifié automatiquement.

Transférer dans le dossier réel de l’application :

- `supervisor.php`, `supervisor_lib.php` et `club_prefix.php` ;
- `clubs/supervisor_access_<domaine>.php`, préparé pour chaque hébergement ;
- conserver `installtablesbdd.php` et `clubs/conf_<hôte>.php` déjà présents.

Le générateur `tools/create_supervisor_config.php` reste sur votre PC. Il n’est pas nécessaire sur OVH. Aucun changement des lanceurs n’est requis.

Pour Wamp, ouvrir `http://localhost/bridgette/appli/supervisor.php` si `bridgette` est bien sous la racine web Wamp. Pour le serveur, ouvrir l’URL HTTPS du dossier applicatif suivie de `/supervisor.php`. L’accès HTTP distant est refusé ; HTTP est permis uniquement depuis la machine locale. Une terminaison HTTPS par proxy doit être configurée par l’hébergement pour renseigner `HTTPS` côté PHP : les en-têtes transférés fournis par le client ne sont pas utilisés pour contourner ce contrôle.

Se connecter avec le mot de passe généré. Choisir le club et l’opération, puis **Vérifier avant exécution**. Relire le préfixe et la liste exacte des tables. Recopier le texte de confirmation et saisir à nouveau le mot de passe opérateur. Une confirmation expire après cinq minutes et ne peut servir qu’une fois.

## Opérations

| Opération | Effet |
| --- | --- |
| Installer / compléter | Crée les neuf tables du club si absentes. Initialise les invités et connexions uniquement dans les tables vides. Si la table des responsables est vide, crée le seul administrateur saisi dans le formulaire. Ne met pas à niveau un schéma existant. |
| Ajouter un administrateur au club | Ajoute un compte avec le pseudo et le mot de passe choisis, même si d'autres responsables existent. Refuse tout pseudo déjà utilisé, sans modifier ni promouvoir le compte existant. Cible uniquement la table des responsables du club sélectionné ; cette table doit déjà exister. |
| Supprimer les tables | Supprime uniquement les neuf tables listées, avec leur contenu. Ne supprime ni fichiers, ni table partagée `sessions`, ni tables supplémentaires éventuelles. |
| Purger les sessions | Supprime les lignes de `sessions` dont `access` date de plus de 100 jours, **pour tous les clubs de cette base**. |
| Exécuter le patch | Exécute uniquement `appli/patch.php`, déposé par l’opérateur et dont l’empreinte SHA-256 est autorisée dans le fichier d’activation. Désactivé par défaut. |

Depuis le 17 septembre, la purge des sessions est également **automatique une fois par mois civil (UTC)**, à la première visite sur l’application. Le bouton du superviseur reste disponible pour une intervention manuelle. Le seuil reste de 100 jours sans activité : la fréquence mensuelle n’est pas une durée de connexion. Sans visite, aucune purge ne s’exécute ; elle reprend à la prochaine visite, sans cumuler les mois manqués.

Déployer ensemble `configuration.php`, `session_maintenance.php` et `src/mysql.sessions.php` pour cette automatisation. Elle utilise la connexion existante et une ligne technique `gc_monthly` dans la table `sessions`, sans nouvelle table ni changement de schéma. Le marqueur appartient à la base partagée, pas à un club ou à un nom de domaine ; chaque base déployée sur un serveur dispose de son propre calendrier. Le gestionnaire PHP protège cette ligne des lectures/écritures de sessions normales. Un verrou MySQL empêche les exécutions concurrentes et une purge échouée peut être retentée lors d’une visite ultérieure sans bloquer la connexion des joueurs.

Le nettoyage probabiliste des sessions MySQL par PHP est désactivé et son seuil configuré à 100 jours. Les tickets de connexion Android gardent leur expiration de cinq minutes et leur nettoyage dédié. Les durées d’authentification des joueurs et responsables ne changent pas. Le calendrier mensuel n’affecte pas les sessions opérateur du superviseur, qui restent stockées dans des fichiers.

La base MySQL et ses identifiants doivent déjà exister. La première installation fonctionne sans tables de club ni compte administrateur. Les fichiers de configuration du club restent à préparer comme auparavant pour pouvoir utiliser ensuite Bridgette. La table partagée `sessions` sera créée par le démarrage normal de l’application si nécessaire.

Le nouvel administrateur utilise `bridgettePasswordHash()` (format `b2:`) et peut se connecter via `logadmin.php`. Pseudo de 1 à 32 caractères, hors compte réservé ; mot de passe de 14 à 256 caractères. Les comptes déjà présents ne sont ni remplacés ni supprimés. L'ajout nécessite POST, jeton CSRF opérateur, confirmation textuelle du club, nouvelle vérification du mot de passe opérateur et autorisation à usage unique valable cinq minutes. Aucun compte n'est créé à la simple prévisualisation.

Depuis le 10 octobre 2026, `init_tab_directeurs()` est supprimée. L'installation CLI `maintenance.php --command=install` crée les tables et initialise invités/connexions, mais ne crée plus aucun responsable. Après cette installation, utiliser « Ajouter un administrateur au club » dans le superviseur. L'installation via le superviseur conserve son formulaire de premier administrateur, créé uniquement si la table des responsables est vide. Aucun compte historique n'est automatiquement supprimé ou modifié.

L'ajout verrouille en écriture uniquement la table des responsables du club pendant le contrôle du pseudo et l'insertion, puis la déverrouille en cas de succès ou d'erreur. Cela empêche une insertion concurrente ordinaire de contourner le contrôle des doublons, sans modifier le schéma ni imposer une transaction. Le compte MySQL doit avoir le droit `LOCK TABLES`. En cas d'échec après insertion ou de résultat réseau incertain, vérifier le compte avant de relancer ; un pseudo existant ne sera pas écrasé.

Pour cette évolution, transférer ensemble `supervisor.php`, `supervisor_lib.php`, `maintenance.php` et `installtablesbdd.php`, en conservant les autres modules de sécurité installés, notamment `security_passwords.php`. Mettre à jour l'appel CLI dans `maintenance.php` avant de retirer la fonction dans `installtablesbdd.php`. Aucun changement des fichiers d'activation, lanceurs, préfixes, tables ou sessions applicatives.

Avant une intervention sur les tables, sauvegarder la base et mettre le club hors ligne. Les opérations DDL MySQL peuvent laisser un résultat partiel en cas d’erreur : elles ne sont pas annulables automatiquement. Le verrou MySQL empêche deux opérations simultanées **entre superviseurs**, sans bloquer le site, la CLI ni d’autres outils. La suppression ne révoque pas les anciennes sessions applicatives : maintenir le club hors ligne et organiser leur révocation avant une remise en service avec des identifiants réutilisés.

## Patch autorisé

Le patch est du code PHP de confiance, capable d’agir sur toute la base. Vérifier son contenu et le protéger contre une exécution HTTP directe avant de le transférer. Le superviseur fournit `$dbh`, `$prefix` (global) et les neuf variables `$tab_…` locales/globales. Le contrat attendu est une fonction `executepatch($dbh)`. Il ne charge pas `configuration.php`, `bridgette_bdd.php`, les sessions de joueurs, ni les paramètres du club : un ancien patch dépendant de ce contexte doit être adapté et testé avant autorisation. Il ne doit pas appeler `exit`, envoyer de redirection ou gérer une session.

Calculer son empreinte localement avec PowerShell :

```powershell
(Get-FileHash .\appli\patch.php -Algorithm SHA256).Hash.ToLowerInvariant()
```

Reporter cette valeur dans `patch_sha256` du fichier d’activation et transférer les deux fichiers. Une modification du patch invalide son autorisation. Ses sorties sont masquées ; le résultat fonctionnel doit être vérifié selon le contenu du patch. Aucune interface de téléversement, d’édition PHP ou d’import SQL arbitraire n’est exposée.

## Fermeture et diagnostic

- Se déconnecter puis **supprimer le fichier `clubs/supervisor_access_<domaine>.php` concerné, localement et sur le serveur**. Les anciens accès sont alors refusés. Un changement de son contenu invalide également les sessions opérateur.
- L’accès expire après 15 minutes d’inactivité ou une heure de connexion, et à la date absolue du fichier d’activation. Ne pas remplacer `expires_at` par `time() + 86400` : cela repousserait indéfiniment l’expiration.
- Après cinq erreurs de mot de passe, les tentatives sont bloquées jusqu’à la fin de la fenêtre de 15 minutes, même avec un nouveau cookie. En cas de nécessité, régénérer une activation locale avec un nouveau mot de passe, puis la transférer.
- Les sessions opérateur utilisent les fichiers temporaires PHP, indépendamment de `mysql.sessions.php`. Le répertoire temporaire et `session.save_path` doivent être accessibles en écriture à PHP. Une erreur dans ces écritures interdit l’opération.
- Les journaux PHP enregistrent la référence, l’action, le préfixe et le résultat. Aucun mot de passe ou identifiant de connexion MySQL n’est affiché. En cas d’échec, contrôler également le schéma et les droits dans phpMyAdmin ; ne pas relancer aveuglément une opération partiellement exécutée.

Les anciennes URL `?cmd=install|patch|droptables|gc` restent refusées. Les parcours web, Android et iframe Joomla, leurs sessions et `$relpgm` ne sont pas modifiés.

## Validation

Tests automatiques dans `tests/security_supervisor.php` et `tests/security_supervisor_http.py` : configuration et expiration, isolation des préfixes et refus des préfixes ne respectant pas le format exact, installation avec administrateur choisi, périmètre de suppression, purge, authentification indépendante, CSRF, confirmation, refus de rejouer, révocation et limitation des tentatives. La base est simulée ; aucune donnée réelle n’est modifiée. La validation MySQL sous Wamp puis sur les deux configurations d’hébergement reste à faire sur des clubs de test sauvegardés.
