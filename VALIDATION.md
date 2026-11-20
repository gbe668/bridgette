# Vérification de la synchronisation du 20 novembre 2026

Sources locales de référence : `app1/`, distribuées sous `appli/` dans ce dépôt. Contrôles effectués sur la copie de publication, sans connexion à une base de club ni déploiement.

- 242 contrôles de syntaxe PHP 8.3 et JavaScript : réussis.
- 35 suites PHP, Node.js et HTTP : 35 réussies après actualisation des cinq tests historiques. Le test de résultats passe après conservation de son URL de fixture historique, indépendamment du nom du répertoire des sources.
- Tests réussis : calculs, insertions, mises à jour, classements, 160 références de résultats, web/Android, export PBN, préparation et paquets Howell ; contrôles d'accès, identité, sessions, tickets et appareils Android, CSRF, paramètres, mots de passe/récupération, politique joueur, redirections, purge mensuelle et superviseur sur bases simulées et serveurs HTTP locaux.
- Aucun fichier privé `clubs/`, export SQL ou journal d'erreurs ajouté. Recherche des valeurs privées extraites des configurations locales dans les fichiers texte préparés : aucune correspondance. Ce contrôle ne constitue pas un audit exhaustif des secrets de tout l'historique ou des documents binaires.

## Actualisation des cinq tests historiques

- `business_movements.php` transmet maintenant le stock explicite au rendu Howell. Il contrôle les stocks et limites du catalogue, les étuis dans ce stock, les orientations/adversaires réciproques et la régression paire 6 EO au tour 4 (31–33). Les 46 références Howell modifiées et les deux tailles de paquet devenues interdites sont documentées dans `tests/fixtures/README.md`. Les empreintes Mitchell et la référence générale des étuis restent inchangées. Résultat : 12 259 contrôles et 147 cas de référence réussis.
- `android_phone.cjs` suit l'ordre email/message et conserve les contrôles avant confirmation, après annulation et après validation ; vérification supplémentaire de la fermeture par Échap sans appel.
- `security_android_version.php` recherche l'affectation exécutable en ignorant les commentaires. Dix cas couvrent les anciennes versions et les versions sous/au-dessus/égales au seuil courant 13, lors de la première connexion et d'un rafraîchissement.
- `security_sql.php` teste les fonctions courantes, les ordres autorisés et une entrée hostile, les filtres liés et les deux projections de l'annuaire (90 requêtes enregistrées). Il ne charge plus la route supprimée `getannuaire.php`.
- `security_sql_static.php` autorise le seul fragment interne email après vérification de son affectation à un choix fixe ; il ne l'ajoute pas à la liste globale des variables autorisées (289 modèles SQL contrôlés).

La suite `tests/run_security.py` inclut désormais également les contrôles téléphone, export PBN, paquets Howell et transport CSRF JavaScript. Exécution complète réussie sur les sources locales avec bases simulées et serveurs HTTP locaux. Cinq régressions volontaires ont été introduites dans des copies temporaires : chacune est bien rejetée par le test concerné (stock, appel prématuré, métadonnées de version absentes, ordre SQL non filtré et projection email arbitraire). Aucun code applicatif modifié par cette actualisation.

## Limites de publication

Pas de validation visuelle, de tournoi réel, de téléphone Android ou des deux hébergements. Aucune base de production modifiée. Les lanceurs privés restent hors dépôt ; les modèles conservent les champs de leurs contrats. Les documents et l'APK historiques sont conservés comme tels ; aucune APK reconstruite.

La provenance et les sources de compilation exactes du DDS WebAssembly restent à inventorier. Les notices et sources web améliorent la traçabilité mais ne constituent pas à elles seules un audit juridique complet des dépendances.

Les journaux précédemment versionnés sont retirés de l'arbre actuel ; l'historique Git n'a pas été réécrit. Les installations existantes conservent leurs fichiers privés et doivent suivre les précautions de migration documentées.
