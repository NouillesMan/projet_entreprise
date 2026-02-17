# Structure du Projet - Inventaire PC

## Organisation des Fichiers

```
projet_entreprise/
│
├── assets/                      # Ressources statiques
│   ├── css/
│   │   └── style.css           # Styles personnalisés (dark theme)
│   └── js/                     # JavaScript (vide pour l'instant)
│
├── partials/                    # Composants réutilisables
│   ├── header.php              # En-tête HTML (meta, CSS, navigation)
│   ├── footer.php              # Pied de page HTML (scripts)
│   └── mobile_nav.php          # Navigation mobile
│
├── config.php                   # Configuration de la base de données
├── db.php                       # Connexion PDO à la base de données
├── get_options.php              # Chargement des options depuis la BDD
│
├── auth.php                     # Garde de session + CSRF + helper require_perm()
├── login.php                    # Page de connexion
├── logout.php                   # Deconnexion (detruit la session)
│
├── pcs.php                      # Page principale - Liste des PC
├── pc_add.php                   # Ajouter un nouveau PC
├── pc_edit.php                  # Modifier un PC existant
├── pc_delete.php                # Supprimer un PC
│
├── admin_fields.php             # Admin : gestion des champs personnalises
├── admin_options.php            # Admin : gestion des options des listes
├── admin_users.php              # Admin : gestion des utilisateurs et permissions
│
├── schema.sql                   # Schema principal (table pcs)
├── schema_custom_fields.sql     # Schema pour les champs personnalises
├── schema_options.sql           # Schema et donnees des options deroulantes
├── schema_user.sql              # Schema de la table users + compte admin
├── inventaire_pc.sql            # Donnees de demonstration
│
├── setup.sh                     # Script d'installation automatique
├── Dockerfile                   # Configuration Docker (PHP 8.2 + Apache)
├── docker-compose.yml           # Orchestration (app + MariaDB)
│
├── README.md                    # Documentation complete
└── STRUCTURE.md                 # Ce fichier

```

## Description des Répertoires

### `/assets/`
Ressources statiques (CSS, JavaScript, images)

- **`css/`** : Feuilles de style
  - `style.css` : Styles personnalisés pour le thème sombre Bootstrap
- **`js/`** : Scripts JavaScript (actuellement vide, Bootstrap JS est chargé via CDN)

### `/partials/`
Composants PHP réutilisables

- **`header.php`** : En-tête commun de toutes les pages
  - Déclarations HTML, meta tags
  - Chargement Bootstrap CSS, Bootstrap Icons, Google Fonts
  - Lien vers le CSS personnalisé
- **`footer.php`** : Pied de page commun
  - Chargement Bootstrap JS
  - Scripts spécifiques aux pages (optionnel)

## Architecture des Pages PHP

Chaque page suit cette structure :

```php
<?php
// 1. Inclusion de la connexion DB
require __DIR__ . "/db.php";

// 2. Logique métier (traitement des formulaires, requêtes)
// ...

// 3. Définition du titre de la page
$pageTitle = "Titre de la page";

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

### Configuration
- `config.php` : Paramètres de connexion à la base de données
- `db.php` : Initialisation de la connexion PDO
- `get_options.php` : Chargement des options des listes déroulantes depuis la BDD

### Authentification & Sécurité
- `auth.php` : Garde de session, protection CSRF (csrf_token, csrf_field, csrf_check), helper require_perm()
- `login.php` : Formulaire de connexion avec protection session fixation
- `logout.php` : Destruction de session et redirection

### Présentation
- `partials/header.php` : Structure HTML commune (head, CSS)
- `partials/footer.php` : Scripts communs (Bootstrap JS)
- `partials/mobile_nav.php` : Navigation mobile
- `assets/css/style.css` : Styles visuels

### Logique Métier
- `pcs.php` : Affichage et filtrage de la liste
- `pc_add.php` : Validation et insertion
- `pc_edit.php` : Validation et mise à jour
- `pc_delete.php` : Vérification d'existence et suppression

### Administration
- `admin_fields.php` : Gestion des champs personnalisés (visibilité, ordre, ajout/suppression)
- `admin_options.php` : Gestion des options des listes déroulantes (marques, modèles, OS, versions)
- `admin_users.php` : Gestion des utilisateurs et permissions (CRUD, reset password)

### Données
- `schema.sql` : Table principale `pcs`
- `schema_custom_fields.sql` : Table `custom_fields`
- `schema_options.sql` : Table `field_options` avec données initiales
- `schema_user.sql` : Table `users` avec compte admin par défaut
- `inventaire_pc.sql` : Données de démonstration

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

1. Créer `ma_page.php` à la racine
2. Suivre la structure standard (voir ci-dessus)
3. Utiliser `require __DIR__ . "/partials/header.php"` et `footer.php`

### Ajouter du CSS personnalisé

1. Éditer `assets/css/style.css`
2. Les changements sont visibles immédiatement (avec Docker)

### Ajouter du JavaScript

1. Créer `assets/js/script.js`
2. L'inclure dans `footer.php` :
   ```php
   <script src="assets/js/script.js"></script>
   ```

## Évolutions Futures Possibles

- 📁 **Séparation MVC** : Créer `controllers/`, `models/`, `views/`
- 📊 **API REST** : Exposer les données en JSON
- 🎨 **Thèmes multiples** : Light/Dark switcher
- 📱 **PWA** : Progressive Web App pour mobile
- 🧪 **Tests** : PHPUnit pour tests automatisés
