# Inventaire PC

Application PHP simple pour gérer un inventaire de PC (CRUD - Create, Read, Update, Delete).

## Fonctionnalités

- ✅ Gestion complète des PC (ajout, modification, suppression, consultation)
- 🔍 Recherche avancée (hostname, serial, utilisateur, OS, domaine)
- 🎨 Interface moderne avec thème sombre
- 📊 Filtres par statut, architecture et marque
- 🔒 Protection contre les injections SQL (requêtes préparées PDO)
- 🐳 Déploiement facile avec Docker
- ⚙️ Panneau d'administration : gestion des champs et des options des listes déroulantes
- 🔐 Authentification par session avec système de permissions par utilisateur

## Prérequis

### Option 1 : Avec Docker (recommandé)
- Docker Engine (version 20.10+)
- Docker Compose (version 2.0+)

### Option 2 : Sans Docker
- PHP 8.0 ou supérieur
- Extension PHP : `pdo_mysql`
- MySQL 8.0+ ou MariaDB 10.5+
- Serveur web : Apache ou Nginx
- Ou un pack tout-en-un : XAMPP, WAMP, MAMP

## Installation

### Installation rapide (recommandée)

Un script d'installation automatique est fourni :

```bash
./setup.sh
```

Il démarre Docker, attend que la base de données soit prête, importe tous les schémas SQL et affiche les identifiants de connexion.

---

### Installation manuelle

### Option 1 : Avec Docker

#### Étape 1 : Cloner ou télécharger le projet
```bash
cd /chemin/vers/votre/projet
```

#### Étape 2 : Vérifier les fichiers
Assurez-vous que les fichiers suivants sont présents :
- `Dockerfile`
- `docker-compose.yml`
- `inventaire_pc.sql` (données de démonstration)
- Tous les fichiers PHP

#### Étape 3 : Résoudre les permissions Docker (Linux uniquement)

Si vous obtenez une erreur de permission, ajoutez votre utilisateur au groupe docker :

```bash
sudo usermod -aG docker $USER
newgrp docker
```

#### Étape 4 : Lancer les conteneurs Docker
```bash
docker compose up -d
```

**Note :** Utilisez `docker compose` (sans tiret) et non `docker-compose`. Les versions récentes de Docker intègrent Compose comme plugin CLI.

Cette commande va :
- Construire l'image PHP 8.2 avec Apache
- Télécharger l'image MariaDB 10.11
- Créer automatiquement la base de données `inventaire_pc`
- Importer les données de démonstration
- Démarrer les services

#### Étape 5 : Vérifier que les conteneurs sont actifs
```bash
docker compose ps
```

Vous devriez voir deux services en cours d'exécution :
- `projet_entreprise-app-1` (serveur web)
- `projet_entreprise-db-1` (base de données)

#### Étape 5b : Importer les schémas additionnels
```bash
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_custom_fields.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_options.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_user.sql
```

#### Étape 6 : Accéder à l'application
Ouvrez votre navigateur et accédez à :
```
http://localhost:8080/login.php
```

Connectez-vous avec les identifiants par défaut :
- **Utilisateur :** `admin`
- **Mot de passe :** `root`

> Changez le mot de passe dès la première connexion via `admin_users.php`.

#### Développement : Modification du code

Les fichiers PHP sont montés en volume, ce qui signifie que **vous pouvez les modifier et voir les changements immédiatement** :
- Éditez n'importe quel fichier `.php`
- Actualisez simplement votre navigateur
- Aucun redémarrage nécessaire !

**Quand faut-il reconstruire/redémarrer ?**
- ✅ **Fichiers PHP** : Changements automatiques (rafraîchir le navigateur)
- 🔄 **Dockerfile modifié** : `docker compose up -d --build`
- 🔄 **docker-compose.yml modifié** : `docker compose restart`
- 🔄 **Fichiers SQL modifiés** : Réimporter ou recréer la base

#### Commandes Docker utiles

**Arrêter les conteneurs :**
```bash
docker compose stop
```

**Redémarrer les conteneurs :**
```bash
docker compose restart
```

**Arrêter et supprimer les conteneurs :**
```bash
docker compose down
```

**Reconstruire après modification du Dockerfile :**
```bash
docker compose up -d --build
```

**Voir les logs :**
```bash
docker compose logs -f
```

**Accéder au conteneur PHP :**
```bash
docker exec -it projet_entreprise-app-1 bash
```

**Accéder à MariaDB :**
```bash
docker exec -it projet_entreprise-db-1 mysql -uroot -proot inventaire_pc
```

**Importer un fichier SQL (ex: schema_options.sql) :**
```bash
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_options.sql
```

---

### Option 2 : Sans Docker (installation manuelle)

#### Étape 1 : Vérifier PHP et ses extensions

```bash
php -v
php -m | grep pdo_mysql
```

Si `pdo_mysql` n'est pas installé :
- **Debian/Ubuntu :** `sudo apt install php-mysql`
- **CentOS/RHEL :** `sudo yum install php-mysqlnd`
- **Windows (XAMPP) :** Décommenter `extension=pdo_mysql` dans `php.ini`

#### Étape 2 : Créer la base de données

**A. Se connecter à MySQL/MariaDB :**
```bash
mysql -u root -p
```

**B. Créer la base de données :**
```sql
CREATE DATABASE `inventaire_pc` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**C. Créer un utilisateur dédié (recommandé) :**
```sql
CREATE USER 'inventaire_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';
GRANT ALL PRIVILEGES ON inventaire_pc.* TO 'inventaire_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Étape 3 : Importer les schémas de base de données

**Avec Docker :**
```bash
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_custom_fields.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_options.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < schema_user.sql
```

**Sans Docker (MySQL installé localement) :**
```bash
mysql -u root -p inventaire_pc < schema.sql
mysql -u root -p inventaire_pc < schema_custom_fields.sql
mysql -u root -p inventaire_pc < schema_options.sql
mysql -u root -p inventaire_pc < schema_user.sql
```

#### Étape 4 : (Optionnel) Importer les données de démonstration

**Avec Docker :**
```bash
docker compose exec -T db mariadb -u root -proot inventaire_pc < inventaire_pc.sql
```

**Sans Docker :**
```bash
mysql -u root -p inventaire_pc < inventaire_pc.sql
```

#### Étape 5 : Configurer la connexion à la base de données

Éditez le fichier `config.php` :

```php
<?php
return [
  "host" => "localhost",          // ou "127.0.0.1"
  "dbname" => "inventaire_pc",
  "user" => "inventaire_user",    // votre utilisateur MySQL
  "pass" => "votre_mot_de_passe", // votre mot de passe
];
```

#### Étape 6 : Placer le projet dans le répertoire web

**Avec Apache (Linux) :**
```bash
sudo cp -r . /var/www/html/inventaire_pc/
sudo chown -R www-data:www-data /var/www/html/inventaire_pc/
```

**Avec XAMPP (Windows) :**
- Copier le dossier dans `C:\xampp\htdocs\inventaire_pc\`

**Avec MAMP (Mac) :**
- Copier le dossier dans `/Applications/MAMP/htdocs/inventaire_pc/`

#### Étape 7 : Configurer Apache (si nécessaire)

Si vous utilisez Apache directement, créez un VirtualHost :

```apache
<VirtualHost *:80>
    ServerName inventaire-pc.local
    DocumentRoot /var/www/html/inventaire_pc

    <Directory /var/www/html/inventaire_pc>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Puis ajoutez dans `/etc/hosts` :
```
127.0.0.1  inventaire-pc.local
```

#### Étape 8 : Accéder à l'application

Ouvrez votre navigateur :
- Avec VirtualHost : `http://inventaire-pc.local/login.php`
- Avec XAMPP : `http://localhost/inventaire_pc/login.php`
- Avec serveur PHP intégré :
  ```bash
  php -S localhost:8000
  # Puis accéder à http://localhost:8000/login.php
  ```

## Structure du projet

```
projet_entreprise/
├── assets/                    # Ressources statiques
│   ├── css/
│   │   └── style.css         # Styles personnalisés (dark theme, custom properties --gh-*)
│   └── js/
│       └── pc_form.js        # JS partagé pour les formulaires PC (ajout/modif)
├── partials/                  # Composants réutilisables
│   ├── header.php            # En-tête + sidebar responsive (fixe desktop / offcanvas mobile)
│   └── footer.php            # Pied de page + JS sidebar toggle
├── scripts/                   # Scripts de collecte USB
│   ├── collect_windows.ps1    # Collecte PowerShell (Windows)
│   └── collect_linux.sh       # Collecte Bash (Linux)
├── config.php                 # Configuration de la base de données
├── db.php                     # Connexion PDO
├── get_options.php            # Chargement des options depuis la BDD
├── pcs.php                    # Page principale : liste des PC
├── pc_add.php                 # Ajouter un nouveau PC
├── pc_edit.php                # Modifier un PC existant
├── pc_delete.php              # Supprimer un PC
├── auth.php                   # Garde de session + helper require_perm()
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion (détruit la session)
├── admin_fields.php           # Admin : gestion des champs
├── admin_import.php           # Admin : import CSV en masse de PC
├── admin_options.php          # Admin : gestion des options des listes
├── admin_users.php            # Admin : gestion des utilisateurs et permissions
├── config_options.php         # Configuration statique des options (fallback)
├── schema.sql                 # Schéma principal de la base de données
├── schema_custom_fields.sql   # Schéma pour les champs personnalisés
├── schema_options.sql         # Schéma et données des options déroulantes
├── schema_user.sql            # Schéma de la table users + compte admin par défaut
├── inventaire_pc.sql          # Données de démonstration
├── Dockerfile                 # Configuration Docker
├── docker-compose.yml         # Orchestration Docker
├── setup.sh                   # Script d'installation automatique (Docker)
├── .dockerignore              # Fichiers exclus de Docker
├── README.md                  # Documentation complète
├── STRUCTURE.md               # Architecture détaillée du projet
├── ADMIN.md                   # Documentation administration
└── MOBILE.md                  # Documentation interface mobile
```

📖 **Pour plus de détails sur l'architecture**, consultez [STRUCTURE.md](STRUCTURE.md)

## Structure de la base de données

### Table `users`

Stocke les comptes utilisateurs avec leurs permissions individuelles.

| Colonne        | Type         | Description                                    |
|----------------|--------------|------------------------------------------------|
| id             | INT          | Identifiant unique                             |
| username       | VARCHAR(80)  | Nom d'utilisateur (unique)                     |
| password_hash  | VARCHAR(255) | Mot de passe hashé (bcrypt)                    |
| is_admin       | TINYINT(1)   | Accès au panneau d'administration              |
| can_view       | TINYINT(1)   | Peut consulter la liste des PC                 |
| can_add        | TINYINT(1)   | Peut ajouter des PC                            |
| can_edit       | TINYINT(1)   | Peut modifier des PC                           |
| can_delete     | TINYINT(1)   | Peut supprimer des PC                          |
| created_at     | TIMESTAMP    | Date de création                               |

### Table `field_options`

Stocke les valeurs des listes déroulantes gérables depuis l'admin.

| Colonne        | Type         | Description                                          |
|----------------|--------------|------------------------------------------------------|
| id             | INT          | Identifiant unique                                   |
| field_name     | VARCHAR(50)  | Nom du champ : `marque`, `modele`, `os`, `os_version` |
| option_group   | VARCHAR(100) | Groupe : famille OS ou marque (pour les modèles)     |
| option_value   | VARCHAR(255) | Valeur affichée dans la liste                        |
| display_order  | INT          | Ordre d'affichage                                    |

### Table `pcs`

| Colonne       | Type                                             | Description                          |
|---------------|--------------------------------------------------|--------------------------------------|
| id            | INT (AUTO_INCREMENT)                             | Identifiant unique                   |
| hostname      | VARCHAR(100)                                     | Nom d'hôte du PC                     |
| serial        | VARCHAR(100) UNIQUE                              | Numéro de série (unique)             |
| marque        | VARCHAR(80)                                      | Marque (Dell, HP, Lenovo, etc.)      |
| modele        | VARCHAR(120)                                     | Modèle du PC                         |
| utilisateur   | VARCHAR(120)                                     | Utilisateur assigné                  |
| domaine       | VARCHAR(120)                                     | Domaine réseau                       |
| os            | VARCHAR(80)                                      | Système d'exploitation               |
| os_version    | VARCHAR(80)                                      | Version de l'OS                      |
| architecture  | ENUM('x86','x64','arm64')                        | Architecture du processeur           |
| statut        | ENUM('En service','En stock','En réparation','Retiré') | Statut du PC              |
| remarques     | TEXT                                             | Notes supplémentaires                |
| created_at    | TIMESTAMP                                        | Date de création                     |
| updated_at    | TIMESTAMP                                        | Date de dernière modification        |

## Utilisation

### Page principale (`pcs.php`)
- Liste tous les PC avec pagination (limite 200)
- Filtres disponibles :
  - Recherche textuelle (hostname, serial, utilisateur, OS, domaine)
  - Statut (En service, En stock, En réparation, Retiré)
  - Architecture (x86, x64, arm64)
  - Marque
- Menu d'actions par ligne : Modifier / Supprimer

### Ajouter un PC (`pc_add.php`)
Champs obligatoires :
- Hostname
- Numéro de série (doit être unique)
- Marque
- Utilisateur
- OS
- Architecture
- Statut

### Modifier un PC (`pc_edit.php`)
- Accès via le menu "Actions" sur chaque ligne
- Tous les champs sont modifiables
- Le numéro de série reste unique

### Supprimer un PC (`pc_delete.php`)
- Confirmation requise avant suppression
- Suppression définitive de la base de données

### Gestion des champs (`admin_fields.php`)
- Afficher/masquer des champs dans les formulaires
- Réordonner les champs
- Ajouter des champs personnalisés supplémentaires

### Gestion des options (`admin_options.php`)
- 4 onglets : **Marques**, **Modèles**, **OS**, **Versions OS**
- Ajouter ou supprimer des valeurs dans chaque liste déroulante
- Les modèles sont groupés par marque, les OS par famille
- Les modifications sont immédiates dans les formulaires d'ajout/modification

### Collecte USB (`scripts/`)

Scripts de collecte automatique a executer depuis une cle USB :

**Windows** (PowerShell, en administrateur) :
```powershell
.\collect_windows.ps1
```

**Linux** (necessite sudo pour dmidecode) :
```bash
sudo bash collect_linux.sh
```

Les scripts collectent : hostname, serial, marque, modele, utilisateur, OS, version OS, architecture, domaine. Chaque execution ajoute une ligne dans `inventaire.csv` (meme dossier que le script). Le fichier CSV resultant peut etre importe directement via `admin_import.php`.

### Import CSV (`admin_import.php`) *(admin uniquement)*
- Importer des PC en masse depuis un fichier CSV
- Colonnes obligatoires : hostname, serial, marque, utilisateur, os, architecture, statut
- Colonnes optionnelles : modele, domaine, os_version, remarques
- Les doublons (serial déjà existant) sont ignorés avec un message d'erreur
- Validation des valeurs d'architecture (x86, x64, arm64) et de statut
- Modèle CSV téléchargeable depuis la page

### Gestion des utilisateurs (`admin_users.php`) *(admin uniquement)*
- Tableau de tous les comptes avec leurs permissions
- Créer un utilisateur avec username, mot de passe et permissions
- Modifier les permissions d'un utilisateur (checkboxes par ligne)
- Réinitialiser le mot de passe d'un utilisateur
- Supprimer un utilisateur (impossible de se supprimer soi-même)

#### Permissions disponibles

| Permission    | Description                                        |
|---------------|----------------------------------------------------|
| **Admin**     | Accès à tous les panneaux d'administration         |
| **Voir**      | Peut consulter la liste des PC                     |
| **Ajouter**   | Peut créer de nouveaux PC                          |
| **Modifier**  | Peut éditer les PC existants                       |
| **Supprimer** | Peut supprimer des PC                              |

## Dépannage

### Erreur de permission Docker (Linux)

**Symptôme :** `permission denied while trying to connect to the Docker daemon socket`

**Solutions :**
```bash
# Ajouter votre utilisateur au groupe docker
sudo usermod -aG docker $USER

# Se déconnecter et se reconnecter, OU utiliser :
newgrp docker

# Vérifier que ça fonctionne
docker info
```

### Commande `docker-compose` introuvable

**Symptôme :** `Command 'docker-compose' not found`

**Solution :** Les versions récentes de Docker utilisent `docker compose` (sans tiret) au lieu de `docker-compose`. Remplacez toutes les commandes :
- ❌ `docker-compose up -d`
- ✅ `docker compose up -d`

### Erreur de connexion à la base de données

**Symptôme :** `SQLSTATE[HY000] [2002] Connection refused`

**Solutions :**
1. Vérifier que MySQL/MariaDB est démarré :
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```
2. Vérifier les identifiants dans `config.php`
3. Avec Docker : vérifier que le service `db` est actif

### Port 8080 déjà utilisé (Docker)

**Symptôme :** `Bind for 0.0.0.0:8080 failed: port is already allocated`

**Solution :** Modifier le port dans `docker-compose.yml` :
```yaml
services:
  app:
    ports:
      - "8081:80"  # Changer 8080 en 8081
```

### Page blanche sans erreur

**Solutions :**
1. Activer l'affichage des erreurs dans `php.ini` :
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
2. Vérifier les logs Apache :
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```
3. Avec Docker :
   ```bash
   docker compose logs app
   ```

### Erreur "duplicate serial number"

**Symptôme :** "Ce numéro de série existe déjà"

**Solution :** Le numéro de série doit être unique. Vérifiez qu'aucun autre PC n'utilise ce serial.

## Sécurité

### En production, pensez à :

1. **Changer les mots de passe par défaut** :
   - Compte admin de l'application : via `admin_users.php`
   - Dans `config.php` (mot de passe DB)
   - Dans `docker-compose.yml` (`MARIADB_ROOT_PASSWORD`)

2. **Ajouter HTTPS** avec Let's Encrypt ou un certificat SSL

3. **Restreindre l'accès à la base de données** :
   - Ne pas exposer le port 3307 en production
   - Utiliser des mots de passe forts

4. **Authentification** :
   - Un système de login par session est en place
   - Les permissions sont gérées par utilisateur depuis `admin_users.php`
   - Les mots de passe sont hashés avec bcrypt

5. **Limiter les permissions des fichiers** :
   ```bash
   chmod 644 *.php
   chmod 600 config.php
   ```

## Personnalisation

### Modifier le thème
Les styles sont centralisés dans `assets/css/style.css` avec des custom properties CSS.
Pour personnaliser les couleurs, modifiez les variables `--gh-*` dans `:root` (ex: `--gh-canvas`, `--gh-accent-blue`, `--gh-border-default`).

### Gérer les options des listes déroulantes
Accéder à `admin_options.php` depuis le panneau d'administration :
- Ajouter/supprimer des marques, modèles, OS, versions OS
- Aucune modification de code requise

### Ajouter des champs personnalisés
Accéder à `admin_fields.php` pour ajouter des champs supplémentaires sans toucher au code.

## Licence

Ce projet est un exemple éducatif sans licence spécifique.

## Support

Pour toute question ou problème :
1. Vérifier la section Dépannage
2. Consulter les logs (`docker compose logs` ou logs Apache)
3. Vérifier la configuration de la base de données
