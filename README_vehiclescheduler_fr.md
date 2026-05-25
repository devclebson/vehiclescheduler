# SisViaturas

Plugin de gestion de flotte et de planification de vehicules pour **GLPI 11**.

> [!WARNING]
> **Ce plugin est en developpement actif.**
> Le code, les ecrans, le modele de donnees, les traductions, la structure CSS et le module de maintenance changent frequemment. Ne considerez pas la branche actuelle comme une version stable de production sans validation prealable.

**SisViaturas** (`vehiclescheduler`) prend en charge les demandes de reservation de vehicules, le flux d'approbation, l'affectation operationnelle, la validation des conflits et la visibilite par tableaux de bord pour les operations quotidiennes de flotte.

## Perimetre MVP actuel

- CRUD des vehicules
- CRUD des conducteurs
- flux de reservation/demande
- tableau de bord

Des modules operationnels supplementaires peuvent etre presents ou en cours d'evolution, notamment la maintenance, les incidents, les rapports, les checklists, les amendes, les sinistres d'assurance et les helpers de theme/UI.

## Orientation du Module de Maintenance

Le module de maintenance est cadre comme un MVP simple et operationnel :

- ordres de service lies aux vehicules existants
- registre simple des ateliers internes et accredites
- specialites de l'atelier utilisees comme filtre auxiliaire
- couts estime et final enregistres dans l'ordre de service
- flux essentiel d'ouverture, analyse, affectation de l'atelier, diagnostic/devis, approbation, execution, conclusion et liberation du vehicule

Le controle des contrats des ateliers accredites est prevu pour une phase future. Le MVP ne gere pas le solde contractuel, la consommation par ordre de service, le gestionnaire du contrat, le controleur administratif ou le controleur technique.

Voir [docs/modulo_manutencao_viaturas_markdown.md](docs/modulo_manutencao_viaturas_markdown.md) pour la specification du module de maintenance.

## Documentation

- [INSTALL_fr.md](INSTALL_fr.md) : installation, mise a jour, activation GLPI et deploiement Apache
- [INSTALL.md](INSTALL.md) : guide d'installation en anglais
- [INSTALL_pt-BR.md](INSTALL_pt-BR.md) : guide d'installation en portugais bresilien
- [INSTALL_es.md](INSTALL_es.md) : guide d'installation en espagnol
- [README.md](README.md) : README en anglais
- [README_vehiclescheduler_pt-BR.md](README_vehiclescheduler_pt-BR.md) : README en portugais bresilien
- [README_vehiclescheduler_es.md](README_vehiclescheduler_es.md) : README en espagnol
- [CHANGELOG.md](CHANGELOG.md) : historique des versions et changements notables
- [CHANGELOG_pt-BR.md](CHANGELOG_pt-BR.md) : changelog en portugais bresilien
- [CHANGELOG_fr.md](CHANGELOG_fr.md) : changelog en francais
- [CHANGELOG_es.md](CHANGELOG_es.md) : changelog en espagnol
- [AGENTS.md](AGENTS.md) : regles normatives pour l'IA/la generation de code
- [CODEX_HANDOFF.md](CODEX_HANDOFF.md) : guide pratique d'implementation pour Codex

## Prerequis

- GLPI 11 installe et fonctionnel
- PHP 8.1 ou plus recent
- Composer
- Apache ou un autre serveur web configure pour GLPI

## Installation rapide

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
composer install
```

Ensuite, ouvrez GLPI, allez dans **Configuration > Plugins**, installez **SisViaturas / Vehicle Scheduler** et activez le plugin.

Pour les exemples Apache et les etapes completes, consultez [INSTALL_fr.md](INSTALL_fr.md).

## Orientation technique

Le projet suit une separation stricte entre logique metier et rendu UI :

- `src/` : emplacement prefere pour le backend/domaine nouveau ou refactorise
- `front/` : points d'entree PHP legers et rendu des pages
- `ajax/` : endpoints asynchrones legers
- `public/css/` : styles
- `public/js/` : comportement client
- `locales/` : traductions
- `inc/` : classes compatibles legacy pendant la migration

Les classes backend/domaine ne doivent pas contenir de mise en page d'ecran, CSS inline, JavaScript inline, composition de page ou markup de boutons.

## Modes de deploiement Apache

Le depot inclut deux exemples Apache. Gardez un seul fichier actif dans le repertoire de configuration Apache du serveur :

- [glpi-root.conf.example](glpi-root.conf.example) : GLPI sur `http://server/`
- [glpi-subdir.conf.example](glpi-subdir.conf.example) : GLPI sur `http://server/glpi/`

Les URLs du plugin doivent s'appuyer sur des helpers compatibles GLPI au lieu d'hypotheses codees en dur sur `/glpi`.

## Licence et attribution

SisViaturas / Vehiclescheduler est distribue sous la [PolyForm Noncommercial License 1.0.0](LICENSE).

Le projet est maintenu par Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) et provient d'un fork d'un travail de l'utilisateur Telegram `@mendesmarcio`. L'attribution aux deux doit etre preservee dans les forks, redistributions et travaux derives. L'utilisation commerciale n'est pas autorisee sans permission ecrite prealable de Vinicius Lopes.

Voir [NOTICE](NOTICE) pour les avis d'attribution obligatoires.
