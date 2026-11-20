# Vérification de la synchronisation du 20 novembre 2026

Sources locales de référence : `app1/`, distribuées sous `appli/` dans ce dépôt. Contrôles effectués sur la copie de publication, sans connexion à une base de club ni déploiement.

- 242 contrôles de syntaxe PHP 8.3 et JavaScript : réussis.
- 35 suites PHP, Node.js et HTTP : 30 réussies, 5 en échec déjà reproductible sur la copie locale de référence. Le test de résultats passe après conservation de son URL de fixture historique, indépendamment du nom du répertoire des sources.
- Tests réussis : calculs, insertions, mises à jour, classements, 160 références de résultats, web/Android, export PBN, préparation et paquets Howell ; contrôles d'accès, identité, sessions, tickets et appareils Android, CSRF, paramètres, mots de passe/récupération, politique joueur, redirections, purge mensuelle et superviseur sur bases simulées et serveurs HTTP locaux.
- Aucun fichier privé `clubs/`, export SQL ou journal d'erreurs ajouté. Recherche des valeurs privées extraites des configurations locales dans les fichiers texte préparés : aucune correspondance. Ce contrôle ne constitue pas un audit exhaustif des secrets de tout l'historique ou des documents binaires.

## Échecs existants à traiter séparément

| Test | Cause observée |
| --- | --- |
| `business_movements.php` | Appel de `htmlPositionHowell` à quatre arguments alors que la fonction en attend cinq ; références historiques à réexaminer. Les tests dédiés `howell_packets` passent. |
| `android_phone.cjs` | Le test suppose le message avant l'email ; le code actuel affiche l'email avant le message. |
| `security_android_version.php` | Le test extrait une ancienne affectation commentée à 9 ; le seuil courant est 13. |
| `security_sql.php` | Le test charge `getannuaire.php`, supprimé des sources actuelles. |
| `security_sql_static.php` | L'allowlist du test ne connaît pas le fragment interne `$champEmail` (`', email'` ou chaîne vide). |

Ces tests n'ont pas été neutralisés ni leurs références régénérées pour faire passer la synchronisation. `tests/run_security.py` s'arrête au premier échec historique ; les suites ont été exécutées individuellement pour ce bilan. Les chemins des tests et de l'outil opérateur ont été adaptés à `appli/` dans la copie publique.

## Limites de publication

Pas de validation visuelle, de tournoi réel, de téléphone Android ou des deux hébergements. Aucune base de production modifiée. Les lanceurs privés restent hors dépôt ; les modèles conservent les champs de leurs contrats. Les documents et l'APK historiques sont conservés comme tels ; aucune APK reconstruite.

La provenance et les sources de compilation exactes du DDS WebAssembly restent à inventorier. Les notices et sources web améliorent la traçabilité mais ne constituent pas à elles seules un audit juridique complet des dépendances.

Les journaux précédemment versionnés sont retirés de l'arbre actuel ; l'historique Git n'a pas été réécrit. Les installations existantes conservent leurs fichiers privés et doivent suivre les précautions de migration documentées.
