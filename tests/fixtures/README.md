# Références des mouvements

Le 20 novembre 2026, 46 empreintes Howell de `movements_reference.json` ont été
actualisées après adaptation de l'appel à `htmlPositionHowell` au stock d'étuis
explicite et au catalogue courant. Les cas `34/4` et `35/4` ont été retirés : quatre
donnes dans chacun des onze paquets dépasseraient la limite de 36 étuis.

Les empreintes Mitchell et `boards/html/unknown` sont strictement conservées.
Le nouveau champ `npaquets` est contrôlé par des assertions indépendantes plutôt
qu'ajouté aux données historiques sérialisées. Les limites, les orientations et
adversaires réciproques, les étuis dans le stock et la régression Howell 11 paires,
tour 4, paire 6 EO, étuis 31–33 sont vérifiés avant la comparaison des empreintes.
Les tests dédiés `howell_packets.php` vérifient aussi le vrai démarrage sur base
simulée, les tailles interdites et les paramètres initiaux des mouvements.

Il n'y a pas d'option de régénération automatique : un écart doit être examiné
avant de modifier ces références.
