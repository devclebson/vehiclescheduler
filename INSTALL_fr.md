# Installation

Ce guide couvre la configuration necessaire pour executer **SisViaturas / Vehiclescheduler** comme plugin GLPI 11.

## Prerequis

- GLPI 11 installe et fonctionnel
- PHP 8.1 ou plus recent
- Composer
- Apache ou un autre serveur web configure pour GLPI
- Acces shell au serveur GLPI

## Installation du plugin

Placez le plugin dans le repertoire des plugins GLPI :

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Installez les dependances PHP :

```bash
composer install
```

Utilisez `composer update` uniquement lorsque vous souhaitez volontairement mettre a jour les versions des dependances.

## Activation dans GLPI

1. Ouvrez GLPI dans le navigateur.
2. Allez dans **Configuration > Plugins**.
3. Installez **SisViaturas / Vehicle Scheduler**.
4. Activez le plugin.

## Configuration des profils

L'acces demandeur et l'acces administrateur/approbateur sont configures dans l'ecran natif des profils GLPI.

Ouvrez le profil cible dans GLPI et utilisez l'onglet **Gestao de Frota** ajoute par le plugin. Le formulaire est rendu par `PluginVehicleschedulerProfile` et enregistre via `front/profile.form.php`.

Droits disponibles du plugin :

- **Acesso ao Portal de Reservas** : permet aux utilisateurs de creer des reservations et de signaler des incidents.
- **Acesso a Gestao de Frota** : permet l'acces au tableau de bord, aux vehicules, conducteurs, maintenances, rapports et enregistrements. Il peut etre configure sans acces, en lecture seule ou en acces ecriture/CRUD.
- **Aprovar/Rejeitar Reservas** : permet aux utilisateurs d'approuver ou de rejeter les demandes de reservation.

## Configuration Apache

Le depot inclut deux exemples de configuration Apache. Utilisez une seule configuration active sur le serveur.

### GLPI sur `http://server/`

Utilisez :

```text
glpi-root.conf.example
```

Dans ce mode, les routes GLPI et les routes du plugin sont servies sans le prefixe `/glpi` :

```text
http://server/plugins/vehiclescheduler/front/management.php
```

### GLPI sur `http://server/glpi/`

Utilisez :

```text
glpi-subdir.conf.example
```

Dans ce mode, `http://server/` redirige vers `http://server/glpi/`, et les routes GLPI/plugin utilisent le prefixe `/glpi` :

```text
http://server/glpi/plugins/vehiclescheduler/front/management.php
```

## Validation Apache

Apres avoir place la configuration Apache choisie dans le repertoire de configuration du serveur, validez et redemarrez Apache :

```bash
apachectl configtest
systemctl restart httpd
systemctl is-active httpd
```

Sur Debian/Ubuntu, le service peut s'appeler `apache2` au lieu de `httpd`.

## Mise a jour

Depuis le repertoire du plugin :

```bash
cd /var/www/glpi/plugins/vehiclescheduler
git pull
composer install
```

Utilisez `composer update` uniquement lorsque vous souhaitez volontairement mettre a jour les versions des dependances.
