# Mise à jour publique — 20 novembre 2026

Synchronisation des sources web actuelles depuis app1 vers appli, sans configurations privées ni données. Ajout des modules et tests locaux, des modèles de configuration/lanceur, des textes de licence et des instructions d'installation. Retrait des routes obsolètes absentes des sources actuelles et des journaux suivis par erreur. Les dossiers de lanceurs des clubs ne sont ni copiés ni supprimés.

Les changements depuis juillet comprennent les contrôles d'accès et CSRF, la gestion des mots de passe et sessions, les échanges web/Android, les paramètres de club, les rendus/calculs et les paquets Howell, ainsi que les crédits GPL/AGPL et composants tiers. Consulter [VALIDATION.md](../VALIDATION.md) pour les vérifications et l’actualisation des cinq tests historiques.

Préparation dans une branche dédiée pour revue. Aucun déploiement ni migration de données effectué. Les deux configurations d'hébergement et les APK existantes restent à valider réellement avant livraison.

## Actualisation des tests

Les cinq tests historiques sont corrigés sans changement du code applicatif. Les références Howell concernées ont été examinées et actualisées ; les références Mitchell sont conservées. La suite complète locale passe et cinq régressions volontaires sur copies temporaires sont bien détectées. La copie publique est vérifiée séparément pour ses chemins `appli/`. Voir VALIDATION.md pour le bilan final.
