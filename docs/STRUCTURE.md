# Structure du Projet - Inventaire PC

## Organisation des Fichiers

```
projet_entreprise/
│
├── admin/                       # Pages d'administration
│   ├── users.php                # Gestion des utilisateurs et permissions
│   ├── options.php              # Gestion des options des listes déroulantes
│   ├── fields.php               # Gestion des champs personnalisés
│   ├── import.php               # Import CSV en masse
│   └── stats_utilisateurs.php  # Dashboard PCs par utilisateur (admin only)
│
├── includes/                    # Fichiers PHP internes (config, auth, helpers)
│   ├── config.php               # Configuration de la base de données
│   ├── db.php                   # Connexion PDO à la base de données
│   ├── auth.php                 # Garde de session + CSRF + e() + require_perm()
│   ├── helpers.php              # Fonctions utilitaires partagées (get_custom_fields)
│   ├── get_options.php          # Chargement des options depuis la BDD
│   └── config_options.php       # Configuration statique des options (fallback)
│
├── assets/                      # Ressources statiques
│   ├── css/
│   │   └── style.css            # Styles personnalisés (dark theme)
│   └── js/
│       └── pc_form.js           # JS partagé pour les formulaires PC
│
├── partials/                    # Composants réutilisables
│   ├── header.php               # En-tête + sidebar responsive
│   └── footer.php               # Pied de page + scripts
│
├── database/                    # Schémas SQL et données
│   ├── schema.sql               # Schéma principal (table pcs)
│   ├── schema_user.sql          # Table users + compte admin par défaut
│   ├── schema_custom_fields.sql # Table custom_fields
│   ├── schema_options.sql       # Table field_options + données initiales
│   └── inventaire_pc.sql        # Données de démonstration
│
├── docs/                        # Documentation
│   ├── STRUCTURE.md             # Ce fichier
│   ├── ADMIN.md                 # Guide d'administration
│   ├── MOBILE.md                # Guide mobile/responsive
│   └── PATCH_NOTES.md           # Notes de version
│
├── scripts/                     # Scripts de collecte USB
│   ├── collect_windows.ps1      # Collecte PowerShell (Windows)
│   └── collect_linux.sh         # Collecte Bash (Linux)
│
├── login.php                    # Page de connexion
├── logout.php                   # Déconnexion (détruit la session)
├── dashboard.php                # Tableau de bord
├── pcs.php                      # Page principale - Liste des PC
├── pc_add.php                   # Ajouter un nouveau PC
├── pc_edit.php                  # Modifier un PC existant
├── pc_delete.php                # Supprimer un PC
│
├── setup.sh                     # Script d'installation automatique
├── Dockerfile                   # Configuration Docker (PHP 8.2 + Apache)
├── docker-compose.yml           # Orchestration (app + MariaDB)
└── README.md                    # Documentation complète

```

## Description des Répertoires

### `/admin/`
Pages d'administration (accessibles uniquement aux admins)

- **`users.php`** : Gestion des utilisateurs et permissions (CRUD, reset password)
- **`options.php`** : Gestion des options des listes déroulantes (marques, modèles, OS, versions)
- **`fields.php`** : Gestion des champs personnalisés (visibilité, ordre, ajout/suppression)
- **`import.php`** : Import CSV en masse de PC
- **`stats_utilisateurs.php`** : Dashboard exclusif admin — nombre de PCs par utilisateur avec répartition par statut

### `/includes/`
Fichiers PHP internes (non accessibles directement par URL)

- **`config.php`** : Paramètres de connexion à la base de données
- **`db.php`** : Initialisation de la connexion PDO
- **`auth.php`** : Garde de session, protection CSRF (`csrf_token`, `csrf_field`, `csrf_check`), helper `require_perm()`, helper `e()` (échappement HTML)
- **`helpers.php`** : Fonctions utilitaires partagées — `get_custom_fields($pdo)` pour charger les champs personnalisés visibles
- **`get_options.php`** : Chargement des options des listes déroulantes depuis la BDD
- **`config_options.php`** : Configuration statique des options (fallback)

### `/assets/`
Ressources statiques (CSS, JavaScript)

- **`css/style.css`** : Styles personnalisés pour le thème sombre Bootstrap
- **`js/pc_form.js`** : JavaScript partagé pour les formulaires PC (ajout/modification)

### `/partials/`
Composants PHP réutilisables

- **`header.php`** : En-tête + sidebar responsive (navigation, meta tags, CSS)
- **`footer.php`** : Pied de page (Bootstrap JS, sidebar toggle, scripts custom)

### `/database/`
Schémas SQL et données d'initialisation

- **`schema.sql`** : Table principale `pcs`
- **`schema_user.sql`** : Table `users` avec compte admin par défaut
- **`schema_custom_fields.sql`** : Table `custom_fields`
- **`schema_options.sql`** : Table `field_options` avec données initiales
- **`inventaire_pc.sql`** : Données de démonstration

### `/docs/`
Documentation du projet

- **`STRUCTURE.md`** : Architecture détaillée (ce fichier)
- **`ADMIN.md`** : Guide d'administration des champs
- **`MOBILE.md`** : Guide mobile/responsive
- **`PATCH_NOTES.md`** : Notes de version

## Architecture des Pages PHP

Chaque page suit cette structure :

```php
<?php
// 1. Inclusion de l'authentification et de la connexion DB
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";

// 2. Logique métier (traitement des formulaires, requêtes)
// ...

// 3. Définition du titre de la page
$pageTitle = "Titre de la page";
$activePage = "nom_page";

// 4. Inclusion du header
require __DIR__ . "/partials/header.php";
?>

<!-- 5. Contenu HTML de la page -->
<div class="container">
  <!-- ... -->
</div>

<?php
// 6. Scripts spécifiques (optionnel)
$pageScripts = <<<'JS'
<script>
  // JavaScript custom
</script>
JS;

// 7. Inclusion du footer
require __DIR__ . "/partials/footer.php";
?>
```

## Séparation des Responsabilités

### Configuration (`includes/`)
- `includes/config.php` : Paramètres de connexion à la base de données
- `includes/db.php` : Initialisation de la connexion PDO
- `includes/get_options.php` : Chargement des options des listes déroulantes depuis la BDD
- `includes/config_options.php` : Options statiques (fallback)

### Authentification & Sécurité (`includes/` + racine)
- `includes/auth.php` : Garde de session, protection CSRF (csrf_token, csrf_field, csrf_check), helper `require_perm()`, helper `e()` (échappement HTML centralisé)
- `includes/helpers.php` : Fonctions utilitaires partagées (`get_custom_fields`)
- `login.php` : Formulaire de connexion avec protection session fixation
- `logout.php` : Destruction de session et redirection

### Présentation
- `partials/header.php` : Structure HTML commune (head, CSS, sidebar responsive)
- `partials/footer.php` : Scripts communs (Bootstrap JS, sidebar toggle)
- `assets/css/style.css` : Styles visuels
- `assets/js/pc_form.js` : JavaScript des formulaires PC

### Logique Métier (racine)
- `dashboard.php` : Tableau de bord avec statistiques
- `pcs.php` : Affichage et filtrage de la liste
- `pc_add.php` : Validation et insertion
- `pc_edit.php` : Validation et mise à jour
- `pc_delete.php` : Vérification d'existence et suppression

### Administration (`admin/`)
- `admin/fields.php` : Gestion des champs personnalisés (visibilité, ordre, ajout/suppression)
- `admin/options.php` : Gestion des options des listes déroulantes (marques, modèles, OS, versions)
- `admin/users.php` : Gestion des utilisateurs et permissions (CRUD, reset password)
- `admin/import.php` : Import CSV en masse de PC
- `admin/stats_utilisateurs.php` : Dashboard nombre de PCs par utilisateur avec répartition par statut

### Données (`database/`)
- `database/schema.sql` : Table principale `pcs`
- `database/schema_custom_fields.sql` : Table `custom_fields`
- `database/schema_options.sql` : Table `field_options` avec données initiales
- `database/schema_user.sql` : Table `users` avec compte admin par défaut
- `database/inventaire_pc.sql` : Données de démonstration

## Technologies Utilisées

### Frontend
- **Bootstrap 5.3.3** : Framework CSS (mode dark)
- **Bootstrap Icons 1.11.3** : Icônes
- **Space Grotesk** : Police Google Fonts
- **CSS personnalisé** : Dégradés, scrollbar dark

### Backend
- **PHP 8.2** : Langage serveur
- **PDO** : Accès à la base de données
- **MariaDB 10.11** : Base de données

### DevOps
- **Docker** : Conteneurisation
- **Docker Compose** : Orchestration multi-conteneurs

## Bonnes Pratiques Implémentées

✅ **Séparation HTML/CSS** : Styles dans fichier externe
✅ **Composants réutilisables** : header.php, footer.php
✅ **Sécurité** : Requêtes préparées PDO, htmlspecialchars()
✅ **Responsive** : Bootstrap grid system
✅ **Maintenabilité** : Structure claire et documentée
✅ **Hot-reload** : Volume Docker pour développement rapide

## Ajouter de Nouvelles Fonctionnalités

### Ajouter une nouvelle page

1. Créer `ma_page.php` à la racine (ou dans `admin/` pour une page admin)
2. Suivre la structure standard (voir ci-dessus)
3. Inclure `require __DIR__ . "/includes/auth.php"` et `require __DIR__ . "/includes/db.php"`
4. Inclure `require __DIR__ . "/partials/header.php"` et `footer.php`

### Ajouter du CSS personnalisé

1. Éditer `assets/css/style.css`
2. Les changements sont visibles immédiatement (avec Docker)

### Ajouter du JavaScript

1. Créer `assets/js/script.js`
2. L'inclure dans `partials/footer.php` :
   ```php
   <script src="/assets/js/script.js"></script>
   ```

## Évolutions Futures Possibles

- 📁 **Séparation MVC** : Ajouter `controllers/`, `models/`, `views/`
- 📊 **API REST** : Exposer les données en JSON
- 🎨 **Thèmes multiples** : Light/Dark switcher
- 📱 **PWA** : Progressive Web App pour mobile
- 🧪 **Tests** : PHPUnit pour tests automatisés
