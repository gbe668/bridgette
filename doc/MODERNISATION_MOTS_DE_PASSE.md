# Modernisation des mots de passe — 7 octobre 2026

Les comptes joueurs et responsables utilisent maintenant un stockage lent et salé créé par `password_hash()` et vérifié par `password_verify()`. Les anciens SHA-256 restent lisibles pour permettre une transition progressive. Les mots de passe ne doivent pas tous être réinitialisés.

## Format et caractères

Le format versionné `b2:` contient un hachage bcrypt avec coût 12. Avant bcrypt, le mot de passe exact est converti en une empreinte SHA-384 binaire encodée en Base64 (64 caractères). Cela évite la troncature des mots de passe après 72 octets par bcrypt et permet les caractères multioctets et les octets nuls dans le traitement serveur. Le stockage final comprend le sel et le hachage bcrypt ; le SHA-384 rapide n'est pas utilisé seul pour le stockage.

Ce format compte 63 caractères et tient dans les colonnes historiques `joueurs.password` VARCHAR(64) et `directeurs.password` VARCHAR(100), confirmées dans les sources d'installation et les exports locaux. Aucune migration SQL ni modification automatique des colonnes. La taille des colonnes sur les serveurs réels reste à vérifier avant installation, notamment pour une installation ayant un schéma personnalisé. Une future modification de format ou d'algorithme devra réévaluer cette capacité.

Les nouveaux mots de passe sont non vides, limités à 1024 octets et hachés tels que reçus, sans `stripslashes()` ni `htmlspecialchars()`. Ils doivent être saisis identiquement lors des prochaines connexions. Ces transformations restent appliquées seulement lors de la vérification d'un ancien SHA-256, pour préserver les mots de passe historiques. Après migration, seule la chaîne exacte utilisée lors de la connexion réussie est reconnue ; les variantes autrefois équivalentes à cause de ces transformations ne le sont plus. Un ancien compte dont le mot de passe était vide reste lisible, mais aucun nouveau mot de passe vide n'est créé et ce cas n'est pas automatiquement migré.

## Migration des comptes

Une connexion vérifie un compte actif unique, et le rôle demandé pour les responsables. Après succès, un ancien hachage est remplacé par le nouveau format. La mise à jour compare le hachage précédent en binaire, l'identifiant et les conditions de compte/rôle : elle ne peut pas écraser un changement concurrent. Si elle perd cette concurrence, la connexion doit être renouvelée. Les connexions échouées, comptes supprimés, identités ambiguës et rôles incorrects ne provoquent pas de migration.

Les comptes nouveaux, changements de mot de passe responsables, réinitialisations joueurs et administrateurs initiaux créés par le superviseur utilisent directement le format moderne. Les mots de passe d'activation du superviseur restent dans leur format indépendant existant. Le cycle et l'isolation des sessions restent conservés ; un changement de mot de passe responsable retire les privilèges de sa session courante. Les autres sessions déjà établies ne sont pas toutes révoquées.

La réinitialisation garde son heure de validité, son usage unique et son CSRF. Une connexion qui migre le mot de passe invalide un lien de récupération déjà émis, puisque ce lien vérifie l'empreinte du stockage au moment de sa demande : demander alors un nouveau courriel.

## Android et intégrations

Les sources Android `MainActivity.java` ont été consultées, dont l'appel `apk_loguserin.php/` avec `mailuser` et `password`. Les APK installés continuent à transmettre le mot de passe ; sa vérification et sa migration sont côté serveur. GET natif, champs JSON `login`, `message`, `userid`, `username`, `club` et ticket de passage à usage unique conservés. Aucune nouvelle identité créée dans le cookie natif, aucune modification de l'APK exigée par le format de stockage.

**Limite existante de l'APK :** son URL est construite par concaténation sans encodage du mot de passe. Certains caractères d'URL (`&`, `+`, `#`, séquences `%xx`) peuvent donc être perdus ou transformés avant d'arriver au serveur. Le serveur préserve les caractères effectivement reçus, mais ne peut pas reconstruire un fragment `#` qui n'a jamais été transmis. Pour les utilisateurs de ces versions, conserver des mots de passe compatibles avec leur transport actuel. L'encodage de l'URL Android constituerait une correction distincte nécessitant une nouvelle version de l'application ; aucune modification silencieuse des sources Android ici.

Lanceurs et sélection du club inchangés, application commune ou hébergement dédié conservés. Les états 20–29, 40–49, 60–69 et leurs transitions restent identiques ; les autorisations de rôle et les politiques Non/M/P sont conservées. Aucun changement des iframes Joomla ou routes `api_`.

## Installation ultérieure

Transférer ensemble les quatorze fichiers de `FICHIERS_MOTS_DE_PASSE.txt`. La variante `apk_changeforgottenpwd.php` utilise déjà le formulaire commun ; aucun nouveau changement de ce wrapper n'est nécessaire. Conserver les configurations propres aux clubs et les lanceurs. Recharger les pages déjà ouvertes, car elles peuvent conserver les anciens traitements/formulaires.

Il n'y a aucune réécriture globale ni migration SQL. **Après les premières migrations de comptes, l'ancien code ne sait plus vérifier leurs mots de passe modernes.** Éviter une synchronisation partielle et ne pas revenir isolément à l'ancien code de connexion ; conserver une sauvegarde des fichiers et du schéma/données avant une installation autorisée. Ce changement local ne constitue pas un déploiement.

## Vérifications et limites

Les tests PHP vérifient le sel, le format et sa longueur, les caractères exacts, les mots de passe longs distingués après le 72e octet, les entrées invalides, les anciens hachages, la migration, le refus des comptes/rôles/clubs incorrects et la protection contre une mise à jour concurrente. Les tests HTTP exécutent les véritables pages à la racine et en sous-dossier : migration web et native, JSON/ticket Android, comptes responsables, création et changement, contrôles CSRF, caractères spéciaux et isolation. La récupération et la création d'un administrateur initial sont aussi vérifiées dans leurs suites existantes adaptées au nouveau stockage.

Ces tests utilisent des bases simulées et des serveurs HTTP PHP isolés. Aucune base applicative ni donnée de production modifiée, aucun courriel réel envoyé. Restent à valider sur un club de test de chaque hébergement : capacité des colonnes MySQL réelles, migration puis seconde connexion des comptes joueurs/responsables, récupération et réception du mail, connexions de l'APK installé et coût de vérification sur le serveur. L'exécution concurrente réelle sur MySQL n'est pas simulée par ces tests ; ils vérifient les conditions SQL et le comportement d'un conflit enregistré.
