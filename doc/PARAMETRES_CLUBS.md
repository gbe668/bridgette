# Fiabilisation des paramètres des clubs — 7 octobre 2026

Installer ensemble les quatre fichiers listés dans `FICHIERS_PARAMETRES.txt`. Aucun changement SQL, des lanceurs, du préfixe de club, des sessions ou de l’APK. Les fichiers JSON existants ne sont pas réécrits au chargement. Les huit fichiers présents localement sont compatibles.

## Lecture et validation

`club_parameters.php` centralise les valeurs par défaut et la validation. Le chargement commun dans `bridgette_bdd.php` bénéficie aussi aux variantes Android et aux pages Joomla. Pas de variante `apk_saveparams.php` ou `apk_bridge36.php` dans les sources. Le parcours modifié est la page de paramètres 36 et son enregistrement ; les transitions 20–29, 40–49 et 60–69 restent identiques.

Un fichier absent conserve le comportement historique d’un nouveau club (valeurs par défaut, accès joueur Non et code web désactivé). Un fichier existant doit être un objet JSON valide, au plus 16 Kio, sans clé inconnue. `checkin` et `checkuser` sont obligatoires pour ne pas réduire silencieusement une protection ; les autres options absentes sont complétées. Le défaut historique `affposprov=1` pour un fichier existant est conservé, contrairement au défaut 0 sans fichier. Les chaînes numériques historiques sont acceptées et normalisées ; booléens, null, tableaux, nombres non finis et valeurs hors limites sont refusés.

Bornes issues du formulaire : donne 5–15 minutes ; diagrammes et installation 0–5 ; tournois affichés 1–100 ; largeur deux colonnes ≥300, tableau réduit ≥100 ; chronomètre 20–80 % ; performances 3–100 joueurs, 1–12 mois, 4–100 tournois ; pré-inscriptions 1–13 semaines (fraction conservée comme dans le formulaire). Les options binaires acceptent 0/1 et l’identité joueur 0/1/2. Les sept jours d’ouverture sont requis quand `opendays` est fourni. Adresse de copie vide ou email valide ≤254 octets, sans caractères de contrôle ni caractères de balisage.

`param1` accepte trois modes précisés par le propriétaire : 0 = fonctionnement normal ; 1 = test avec remplissage automatique et changement de position automatique ; 2 = test avec remplissage automatique et changement de position manuel. Cette description détaillée reste dans la documentation et les commentaires du code ; le formulaire conserve son libellé court historique. `param2`, inutilisé, est supprimé des paramètres actifs et du formulaire. Sa présence dans un ancien fichier ou envoi est ignorée ; il disparaît à la prochaine sauvegarde validée, sans réécriture automatique au chargement ; `maxbj`/`maxdel` sont conservés, 1–100. Les largeurs sont limitées techniquement à un entier signé 32 bits. Aucune relation nouvelle n’est imposée entre les largeurs.

## Erreurs et récupération

Un fichier existant invalide ou illisible bloque la requête avec HTTP 503 et un message générique, sans retour aux accès permissifs. Restaurer le fichier du club via FTP ou outil local depuis une copie valide, en préservant ses protections. Ne pas simplement le supprimer : l’absence de fichier applique les défauts permissifs historiques. Le formulaire 36 dépend du même chargement et ne permet donc pas de réparer lui-même un fichier corrompu. Aucune modification automatique des fichiers invalides.

## Enregistrement

Les contrôles directeur, POST, origine et CSRF existants sont conservés. La cible provient uniquement du club sélectionné côté serveur. Validation complète avant écriture ; temporaire aléatoire créé exclusivement dans le même dossier, permissions existantes conservées (0640 pour un nouveau fichier), écriture complète puis renommage. Si le remplacement échoue, le fichier précédent reste en place et le temporaire est supprimé. Le dossier `clubs` doit permettre création et renommage, pas seulement l’écriture dans un fichier existant. Les lecteurs voient un fichier complet ; deux enregistrements concurrents valides restent selon la règle du dernier remplacement réussi (pas de fusion des modifications).

JSON invalide : HTTP 400 avec motif lisible dans le formulaire ; problème d’écriture : HTTP 500 générique. Le journal d’événement suit le remplacement : une erreur de journalisation peut signaler un échec après une sauvegarde effective. Aucun secret ou contenu JSON n’est ajouté aux journaux par cette correction.

## Vérifications

`tests/security_parameters.php` : fichiers existants en lecture seule, ancien format, valeurs absentes et invalides, bornes, isolation, remplacement, conservation après refus et nettoyage après échec de renommage. `tests/security_parameters_http.py` : véritables page 36/enregistrement et bloc de chargement, gardes réelles, racine/sous-dossier ; stockage temporaire et journal simulé, sans MySQL. Intégration dans `tests/run_security.py`.

À valider sur chaque hébergement avec un club de test : permissions/renommage du dossier, formulaire navigateur, consultation Android et Joomla, options de jeu Non/M/P et code web, enregistrement puis rechargement. Ces tests locaux ne valident pas la configuration réelle des hébergeurs. Aucun déploiement ou changement de données applicatives effectué.
