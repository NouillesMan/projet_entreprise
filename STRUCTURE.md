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
│   └── footer.php              # Pied de page HTML (scripts)
│
├── config.php                   # Configuration de la base de données
├── db.php                       # Connexion PDO à la base de données
│
├── pcs.php                      # Page principale - Liste des PC
├── pc_add.php                   # Ajouter un nouveau PC
├── pc_edit.php                  # Modifier un PC existant
├── pc_delete.php                # Supprimer un PC
│
├── schema.sql                   # Schéma de la base de données
├── inventaire_pc.sql            # Données de démonstration
│
├── Dockerfile                   # Configuration Docker (PHP 8.2 + Apache)
├── docker-compose.yml           # Orchestration (app + MariaDB)
├── .dockerignore               # Fichiers exclus de Docker
│
├── README.md                    # Documentation complète
└── STRUCTURE.md                # Ce fichier

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

### Présentation
- `partials/header.php` : Structure HTML commune (head, CSS)
- `partials/footer.php` : Scripts communs (Bootstrap JS)
- `assets/css/style.css` : Styles visuels

### Logique Métier
- `pcs.php` : Affichage et filtrage de la liste
- `pc_add.php` : Validation et insertion
- `pc_edit.php` : Validation et mise à jour
- `pc_delete.php` : Suppression

### Données
- `schema.sql` : Structure de la base de données
- `inventaire_pc.sql` : Données de test

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
- 🔐 **Authentification** : Ajouter un système de login
- 📊 **API REST** : Exposer les données en JSON
- 🎨 **Thèmes multiples** : Light/Dark switcher
- 📱 **PWA** : Progressive Web App pour mobile
- 🧪 **Tests** : PHPUnit pour tests automatisés
