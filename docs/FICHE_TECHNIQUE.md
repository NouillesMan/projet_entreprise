# Fiche Technique — Inventaire PC

## Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Langage backend | PHP 8.0+ (natif, sans framework) |
| Base de données | MariaDB / MySQL (via PDO) |
| Frontend | Bootstrap 5.3, Bootstrap Icons |
| JavaScript | Vanilla JS (ES6+) |
| Drag & drop | SortableJS 1.15.6 (CDN) |
| Conteneurisation | Docker + Docker Compose |

---

## Architecture des Fichiers

```
/
├── index.php (→ redirect login ou dashboard)
├── login.php            — Page de connexion
├── logout.php           — Déconnexion (détruit la session)
├── dashboard.php        — Tableau de bord
├── pcs.php              — Liste / recherche / tri / pagination
├── pc_add.php           — Formulaire ajout PC
├── pc_edit.php          — Formulaire modification PC
├── pc_delete.php        — Suppression unitaire (POST)
├── pc_delete_bulk.php   — Suppression en lot (POST)
│
├── admin/
│   ├── fields.php           — Gestion des champs (ajout/modif/suppression/ordre)
│   ├── import.php           — Import CSV + téléchargement scripts
│   ├── options.php          — Gestion des listes déroulantes
│   ├── stats_utilisateurs.php — Stats PCs par utilisateur
│   ├── users.php            — Gestion des comptes
│   └── download_script.php  — Sert les scripts de collecte en téléchargement
│
├── includes/
│   ├── auth.php        — Session, authentification, CSRF, require_perm(), e()
│   ├── config.php      — Constantes de connexion BDD
│   ├── db.php          — Connexion PDO ($pdo)
│   ├── helpers.php     — Constantes, statut_badge_class(), deriveOsGroup(), get_custom_fields()
│   └── get_options.php — Charge les options des listes déroulantes depuis field_options
│
├── partials/
│   ├── header.php      — HTML head + navbar + sidebar
│   └── footer.php      — Bootstrap JS + $pageScripts + </body></html>
│
├── assets/
│   └── js/
│       └── pc_form.js  — Logique formulaire PC (cascade marque→modèle, OS→version, utilisateur custom)
│
├── database/
│   ├── schema.sql              — Table pcs
│   ├── schema_user.sql         — Table users
│   ├── schema_custom_fields.sql — Tables custom_fields + pc_custom_data
│   └── schema_options.sql      — Table field_options + données seed
│
├── scripts/
│   ├── collect_windows.ps1
│   └── collect_linux.sh
│
└── docs/                — Documentation
```

---

## Schéma de Base de Données

### Table `pcs` (inventaire principal)

| Colonne | Type | Notes |
|---------|------|-------|
| id | INT UNSIGNED AUTO_INCREMENT | PK |
| hostname | VARCHAR(100) | Indexé |
| serial | VARCHAR(100) UNIQUE | Clé naturelle |
| marque | VARCHAR(80) | |
| modele | VARCHAR(120) | Nullable |
| utilisateur | VARCHAR(120) | Indexé |
| domaine | VARCHAR(120) | Nullable |
| os | VARCHAR(80) | |
| os_version | VARCHAR(80) | Nullable |
| architecture | ENUM('x86','x64','arm64') | |
| statut | ENUM('En service','En stock','En réparation','Retiré') | |
| remarques | TEXT | Nullable |
| created_at | TIMESTAMP | Default: now() |
| updated_at | TIMESTAMP | Auto-update on change |

### Table `users` (comptes)

| Colonne | Type | Notes |
|---------|------|-------|
| id | INT UNSIGNED | PK |
| username | VARCHAR(80) UNIQUE | |
| password_hash | VARCHAR(255) | bcrypt |
| is_admin | TINYINT(1) | Flag admin |
| can_view | TINYINT(1) | |
| can_add | TINYINT(1) | |
| can_edit | TINYINT(1) | |
| can_delete | TINYINT(1) | |

### Table `custom_fields` (définition des champs perso)

| Colonne | Type | Notes |
|---------|------|-------|
| id | INT UNSIGNED | PK |
| field_name | VARCHAR(50) UNIQUE | Identifiant technique |
| field_label | VARCHAR(100) | Libellé affiché |
| field_type | ENUM(text,number,select,date,textarea) | |
| is_required | TINYINT(1) | |
| is_visible | TINYINT(1) | Masquable sans supprimer |
| display_order | INT | Tri d'affichage |

### Table `pc_custom_data` (valeurs des champs perso)

| Colonne | Type | Notes |
|---------|------|-------|
| pc_id | INT UNSIGNED | FK → pcs.id (CASCADE DELETE) |
| field_name | VARCHAR(50) | |
| field_value | TEXT | |
| Clé unique | (pc_id, field_name) | Un seul enregistrement par champ/PC |

### Table `field_options` (valeurs des listes déroulantes)

| Colonne | Type | Notes |
|---------|------|-------|
| id | INT UNSIGNED | PK |
| field_name | VARCHAR(50) | 'marque', 'modele', 'os', 'os_version' |
| option_group | VARCHAR(100) | NULL ou groupe (marque pour modèles, famille OS) |
| option_value | VARCHAR(255) | |
| display_order | INT | Ordre d'affichage |

---

## Sécurité

### Authentification
- Session PHP démarrée dans `auth.php` (inclus en 1er sur chaque page)
- Si `$_SESSION['user_id']` absent → redirect `/login.php` + `exit`
- Connexion : `password_verify()` + `password_hash(PASSWORD_BCRYPT)`
- Les permissions sont chargées en session à la connexion depuis la table `users`

### CSRF
- Token généré avec `random_bytes(32)` → `bin2hex()` → stocké en session
- Inséré dans chaque formulaire POST via `<?= csrf_field() ?>`
- Vérifié avec `hash_equals()` (résistant aux attaques timing)
- `csrf_check()` appelé en début de chaque handler POST

### XSS
- Toutes les sorties HTML passent par `e($v)` = `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`
- Les attributs HTML dynamiques également (data-* inclus)

### Injections SQL
- 100% PDO avec prepared statements et paramètres bindés
- Les colonnes de tri dans `pcs.php` sont validées contre une whitelist `$allowedSort`
- Les valeurs d'enum (statut, architecture) sont validées contre `in_array(..., true)`

---

## Flux de Données Typiques

### Ajout d'un PC

```
POST pc_add.php
  → csrf_check()
  → validation (required fields, enum whitelist)
  → INSERT INTO pcs (...)
  → foreach $customFields: UPSERT INTO pc_custom_data
  → redirect pcs.php?msg=added
```

### Import CSV

```
POST admin/import.php (multipart/form-data)
  → csrf_check()
  → fopen / fgetcsv()
  → validate headers
  → foreach row:
      → normalize + auto-serial if missing
      → validate required columns
      → validate enum fields
      → INSERT ... ON DUPLICATE KEY UPDATE (UPSERT)
  → sync new values to field_options (2 queries batch)
  → display results
```

### Cascade JS Marque → Modèle

```
window.modelsByMarque = { "Dell": ["Latitude 7490", ...], ... }  // injecté PHP
#marque change event
  → rebuild #modele <select> from modelsByMarque[selectedMarque]
  → preserve previously selected value if still valid
```

### Cascade JS OS → Version OS

```
window.versionsByOsFamily = { "Windows": ["23H2", ...], "": ["Pro", ...] }
#os change event
  → getOsFamily() lit le label de l'<optgroup> parent de l'option sélectionnée
  → populateOsVersions() rebuild #os_version avec <optgroup> pour la famille + flat pour génériques
```

---

## Déploiement Docker

```yaml
# docker-compose.yml (simplifié)
services:
  app:    # PHP + Apache, port 8080
  db:     # MariaDB, port 3306
```

Initialisation :
```bash
docker compose up -d
docker compose exec -T db mariadb -u root -proot inventaire_pc < database/schema.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < database/schema_user.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < database/schema_custom_fields.sql
docker compose exec -T db mariadb -u root -proot inventaire_pc < database/schema_options.sql
```

Compte par défaut : `admin` / `root`

---

## Dépendances Externes (CDN)

| Bibliothèque | Version | Usage |
|--------------|---------|-------|
| Bootstrap CSS | 5.3 | UI entière |
| Bootstrap Icons | 1.x | Icônes |
| Bootstrap JS (bundle) | 5.3 | Modals, dropdowns, alerts |
| SortableJS | 1.15.6 | Drag & drop admin/fields et admin/options |
