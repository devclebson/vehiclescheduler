# Journal des modifications

Tous les changements notables de ce projet doivent etre documentes dans ce fichier.

Le format suit les principes de **Keep a Changelog** et le projet doit privilegier le **versionnage semantique** pour les releases.

> Les entrees historiques anterieures a cette base de documentation n'ont pas ete totalement reconstruites ici.
> Ajoutez les anciennes releases plus tard uniquement lorsqu'elles peuvent etre recuperees de maniere fiable.

## [Non publie]

### Notes non publiees

- Remplir les sections de release ci-dessous lorsque les versions taguees reelles sont confirmees.

## [28ABR26] - Chargement CSS et configuration GLPI en sous-repertoire

### 28ABR26 Modifie

- Restauration de la generation d'URLs du plugin compatible avec GLPI afin que les deploiements a la racine utilisent `/plugins/...` et les deploiements en sous-repertoire utilisent `/glpi/plugins/...`.

- Refonte du chargement CSS du plugin pour developper `public/css/app.css` et les imports de feuilles de style specifiques aux pages avant le rendu.

- Ajout d'une resolution des assets publics CSS basee sur le systeme de fichiers, en limitant les styles importes au repertoire `public/` du plugin.

- Clarification des exemples de deploiement Apache afin que `glpi-root.conf.example` corresponde a `http://server/` et `glpi-subdir.conf.example` a `http://server/glpi/`.

- Ajout d'une redirection de l'URL racine `/` vers `/glpi/` pour le scenario de deploiement en sous-repertoire.

- Suppression de la copie dupliquee `glpi.conf` au niveau du depot afin d'eviter la confusion avec les deux exemples de deploiement.

- Separation des instructions d'installation et de configuration Apache dans les fichiers dedies `INSTALL.md`, `INSTALL_pt-BR.md`, `INSTALL_fr.md` et `INSTALL_es.md`.

- Ajout de la documentation d'installation en espagnol dans `INSTALL_es.md`.

- Ajout de la documentation du changelog en francais dans `CHANGELOG_fr.md`.

- Ajout de la documentation README en espagnol dans `README_vehiclescheduler_es.md`.

- Standardisation des suffixes des fichiers de documentation en francais de `_fr-FR` vers `_fr`.

- Reduction des fichiers README a des vues d'ensemble orientees GitHub avec des liens relatifs vers les guides d'installation par langue.

- Modification des metadonnees et de la documentation de licence du projet vers PolyForm Noncommercial License 1.0.0.

- Ajout de `NOTICE` avec attribution a Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) et a la source originale du fork, l'utilisateur Telegram `@mendesmarcio`.

### 28ABR26 Corrige

- Correction des problemes de resolution des feuilles de style causes par des chemins CSS `@import` imbriques, tout en conservant la compatibilite avec les deploiements sous `/glpi/plugins/vehiclescheduler`.

- Correction du scenario `http://IP/` qui retournait Apache 403 lorsque `/var/www/html` ne contenait aucun fichier d'index.

### 28ABR26 Technique

- Ajout de l'expansion recursive des imports CSS locaux avec protection contre les fichiers dupliques afin d'eviter de charger la meme feuille de style plusieurs fois.

- Conservation du chargement precedent via `<link rel="stylesheet">` comme fallback lorsque les fichiers CSS ne peuvent pas etre resolus depuis le disque.

## [27ABR26] - Durcissement du MVP et finition des operations de flotte

### 27ABR26 Ajoute

- Ecran de configuration du plugin avec flags operationnels persistants, incluant le comportement automatique de checklist de depart apres approbation d'une reservation.

- Flux de signalement d'incident conducteur avec acces demandeur, liste de gestion, layout de formulaire et lien optionnel avec reservation/trajet.

- Prise en charge des flux de checklist de depart et de retour, avec ecrans de reponse alignes sur le workflow operationnel de flotte.

- Entree du module d'amendes reservee aux administrateurs dans l'acces rapide de gestion de flotte.

- Catalogue d'infractions RENAINF genere a partir du tableur bresilien des infractions de circulation.

- Selecteur RENAINF compact et consultable pour les amendes conducteur, avec resultats controles dans la page, defilement interne complet et selection automatique du code/desdobramento.

- Metadonnees RENAINF persistantes pour les amendes : code d'infraction, desdobramento, base legale, contrevenant, autorite, gravite derivee et traitement des points.

- Prise en charge des infractions sans points conducteur comme `Sem pontuacao`.

- Exemples de deploiement Apache pour GLPI a la racine web et sous un sous-repertoire :

  - `glpi-root.conf.example`

  - `glpi-subdir.conf.example`

- Guide de compatibilite du chemin racine dans la documentation du projet pour les environnements utilisant `/` ou `/glpi`.

- Base generique de feedback flash reutilisable dans le projet :

  - `public/js/flash.js`

  - `public/css/core/flash.css`

  - pattern helper pour les messages semantiques de succes, avertissement, information et erreur.

- Grille compacte personnalisee de gestion des vehicules remplacant la liste de recherche GLPI par defaut dans `front/vehicle.php`.

- Filtrage client-side de la grille des vehicules par texte de recherche, statut actif et categorie CNH requise.

- Style compact de la grille des vehicules dans `public/css/pages/vehicle-grid.css`.

- Comportement de liste operationnelle des vehicules dans `public/js/vehicle-grid.js`.

- Variante README en portugais (`pt-BR`) pour la documentation du depot.

### 27ABR26 Modifie

- Refonte du layout du dashboard de gestion en console operationnelle plus professionnelle, incluant un acces rapide ameliore, une bande KPI et un meilleur placement des controles visuels.

- Standardisation de la liste des reservations et du layout du formulaire de reservation pour correspondre au pattern visuel actuel de gestion de flotte.

- Refonte de la liste des amendes, du formulaire d'amende et de l'onglet d'amende conducteur avec le layout operationnel compact utilise par le reste du plugin.

- La gravite et les points de l'amende sont maintenant derives de l'infraction RENAINF selectionnee au lieu d'etre modifiables manuellement.

- Amelioration du mode standalone du wallboard administrateur avec sortie UTF-8, horloge/compte a rebours fonctionnels et barre superieure GLPI masquee.

- Mise a jour du layout du formulaire de configuration pour suivre le meme langage visuel que les ecrans de liste operationnelle.

- Version du plugin portee a `2.0.5` pour couvrir les upgrades de schema.

- `front/management.php` et les travaux associes au dashboard ont ete ajustes vers un layout operationnel plus compact, incluant un espacement plus dense et des raffinements de l'acces rapide.

- La gestion des URLs du plugin a ete alignee avec les attentes de compatibilite racine/sous-repertoire au lieu de supposer `/glpi` comme base fixe.

- Le flux post-ajout de `front/driver.form.php` a ete ajuste pour revenir a `front/driver.php`.

- Le flux post-ajout et post-mise a jour de `front/vehicle.form.php` a ete ajuste pour revenir a `front/vehicle.php`.

- `front/vehicle.php` a ete redesigne pour suivre le meme pattern operationnel compact que la grille personnalisee de gestion des conducteurs.

- La presentation de la liste des vehicules a ete raffinee pour :

  - supprimer l'icone de recherche du champ de recherche

  - afficher un badge compact d'abreviation du vehicule

  - rendre marque et modele sur des lignes separees

  - garder les colonnes operationnelles centrees sur l'usage quotidien de la flotte

### 27ABR26 Corrige

- Correction du flux sauvegarde/permission de la configuration du plugin et des redirections pour acces non autorise.

- Correction du chemin de creation/upgrade de `glpi_plugin_vehiclescheduler_configs`.

- Correction de chaines cassees du dashboard standalone avec mojibake UTF-8.

- Correction du compte a rebours de rafraichissement et de l'initialisation de l'horloge du dashboard causes par l'ordre de chargement des scripts.

- Correction de regressions de layout du selecteur de theme dans les controles visuels de gestion.

- Correction de l'overflow de layout du combobox RENAINF natif en le remplacant par un selecteur controle dans la page.

### 27ABR26 Documentation

- Refonte du contenu README en anglais et en portugais a partir de la base existante du projet.

- Documentation du perimetre MVP, des prerequis d'installation/execution, du setup des dependances Composer et de la configuration des profils GLPI pour acces demandeur/admin-approbateur.

- Correction des problemes markdownlint `MD032/blanks-around-lists` dans les fichiers README.

- Base documentaire pour l'orientation du projet cote depot.

- `AGENTS.md` comme ensemble normatif de regles pour IA/generation de code.

- `CODEX_HANDOFF.md` comme guide pratique d'implementation pour Codex.

- `README.md` restructure pour separer le contexte public du projet des regles internes de generation.

- Regles explicites pour namespaces, imports `use`, layout PSR-4 et coexistence avec le legacy `inc/*.class.php`.

- Regles explicites de compatibilite base de donnees GLPI 11 pour la gestion du SQL brut.

- Guide explicite pour `setup.php`, `hook.php`, upgrades de schema idempotents et increments de version conscients des upgrades.

- Les responsabilites documentaires sont maintenant separees par objectif au lieu de concentrer les regles operationnelles et architecturales dans un seul fichier.

- `AGENTS.md` est maintenant volontairement concis et normatif.

- `README.md` est maintenant oriente public et depot.

- `CODEX_HANDOFF.md` est maintenant operationnel et oriente implementation.

### 27ABR26 Technique

- Les consignes du projet ont ete renforcees autour de l'utilisation de helpers d'URL compatibles GLPI, tels que :

  - `plugin_vehiclescheduler_get_root_doc()`

  - `plugin_vehiclescheduler_get_front_url()`

  - helpers d'URL pour assets publics

- Le rendu de la liste des vehicules est reste dans la couche `front/`, avec normalisation des donnees backend/domaine attendue dans l'entite/service vehicule.

- La gestion des flashs a ete structuree afin que les pages de destination de redirection rendent le feedback visuel, en gardant le flux controller explicite et previsible.

- La documentation a ete mise a jour pour clarifier les attentes de deploiement en environnements GLPI bases sur Apache.

### 27ABR26 Notes

- Cette entree consolide les principaux ajustements d'implementation et de documentation produits pendant la conversation de developpement actuelle.

- Certaines idees discutees etaient exploratoires ; ce changelog capture les sorties concretes et les artefacts de projet generes plutot que les etapes de depannage terminal ou les operations locales de recuperation Git.

## [0.1.0] - 2026-04-27 13:46 BRT

### 0.1.0 Ajoute

- Version publique initiale du plugin SisViaturas / Vehiclescheduler pour GLPI 11.

- Flux de demande de reservation de vehicule.

- Flux d'approbation et de rejet des reservations.

- Fonctionnalites operationnelles d'affectation de vehicule et conducteur.

- Validation de conflit date/heure pour les reservations.

- Ecrans de dashboard de gestion, operationnel et executif.

- Visibilite demandeur et gestion controlee par les permissions du plugin.

- Assets front-end pour UI operationnelle compacte.

- Structure de localisation pour les libelles du plugin.

- Metadonnees initiales du depot :

  - `.gitignore`

  - `.gitattributes`

  - `README.md`

  - `CHANGELOG.md`

### 0.1.0 Technique

- Projet prepare comme plugin GLPI 11.

- Depot initialise avec `main` comme branche par defaut.

- Fins de ligne normalisees en LF via `.gitattributes`.

- Dossiers locaux de developpement, cache, build, release et dependances ignores via `.gitignore`.

- Workflow initial de publication GitHub prepare avec remote HTTPS.

### 0.1.0 Notes

- Cette entree represente la version de base destinee a etre publiee comme etat initial du depot.
