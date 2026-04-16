# Explication du Code — Inventaire PC

Ce document explique le fonctionnement interne du projet fichier par fichier.

---

## Vue d'ensemble du flux d'exécution

Chaque page PHP suit le même cycle :

```
1. require includes/auth.php     → session, authentification, e(), csrf_*
2. require_perm("can_xxx")       → vérification de permission
3. require includes/db.php       → connexion PDO ($pdo)
4. [optionnel] require helpers.php, get_options.php
5. Traitement POST si applicable
6. $pageTitle = "..."
7. require partials/header.php   → tout le HTML head + navbar + sidebar
8. HTML de la page
9. require partials/footer.php   → JS Bootstrap + $pageScripts + </body>
```

---

## `includes/auth.php`

Inclus **en premier** sur toutes les pages protégées. Trois rôles :

### Session
```php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
```
Démarre la session PHP sans erreur si déjà active. Puis redirige vers `/login.php` si `$_SESSION['user_id']` est absent.

### `e($v): string`
Fonction d'échappement HTML utilisée partout pour l'affichage :
```php
return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
```
Convertit `<`, `>`, `"`, `'`, `&` en entités HTML → empêche les attaques XSS.

### Système CSRF
Le CSRF (Cross-Site Request Forgery) est une attaque où un site tiers pousse l'utilisateur à soumettre un formulaire à son insu.

- **`csrf_token()`** : génère (ou retourne) un token 64 chars hex stocké en session via `random_bytes(32)`.
- **`csrf_field()`** : retourne `<input type="hidden" name="_csrf_token" value="...">` à insérer dans chaque formulaire.
- **`csrf_check()`** : compare `$_POST['_csrf_token']` avec `$_SESSION['_csrf_token']` via `hash_equals()` (résistant aux attaques timing). Renvoie 403 si invalide.

### `require_perm(string $perm)`
Vérifie que `$_SESSION[$perm]` est truthy. Sinon affiche une page 403 et stoppe l'exécution. Les permissions possibles : `can_view`, `can_add`, `can_edit`, `can_delete`, `is_admin`.

---

## `includes/db.php`

Crée la connexion PDO avec les paramètres de `includes/config.php`. Expose la variable `$pdo`. Options activées : exceptions PDO (`ERRMODE_EXCEPTION`), résultats associatifs par défaut (`FETCH_ASSOC`).

---

## `includes/helpers.php`

Constantes et fonctions partagées entre plusieurs pages.

### Constantes
```php
PC_ARCH    = ['x86', 'x64', 'arm64']
PC_STATUTS = ['En service', 'En stock', 'En réparation', 'Retiré']
OS_FAMILIES = ['Windows', 'Linux', 'macOS', 'Autre']
```
Définies avec `if (!defined(...))` pour éviter les erreurs si le fichier est inclus deux fois.

### `statut_badge_class(string $statut): string`
Retourne la couleur Bootstrap correspondant au statut :
```php
'En service'    → 'success'   (vert)
'En stock'      → 'info'      (bleu)
'En réparation' → 'warning'   (orange)
default         → 'secondary' (gris)
```
Usage : `<span class="badge bg-<?= statut_badge_class($pc['statut']) ?>">`.

### `deriveOsGroup(string $os): string`
Déduit la famille OS depuis le nom de l'OS. Utile pour catégoriser les valeurs importées en CSV.
```php
"Windows 10" → "Windows"
"Ubuntu 22.04" → "Linux"
"macOS Sonoma" → "macOS"
"Autre chose" → "Autre"
```

### `get_custom_fields(PDO $pdo): array`
Retourne les champs personnalisés visibles (`is_visible = 1`) qui ne sont pas des champs fixes de la table `pcs`. Utilisé dans `pc_add.php` et `pc_edit.php` pour afficher les champs supplémentaires créés par l'admin.

---

## `includes/get_options.php`

Charge les options de toutes les listes déroulantes depuis `field_options`. Retourne un tableau :
```php
[
  'marque'     => ['Dell', 'HP', 'Lenovo', ...],          // flat
  'modele'     => ['Dell' => ['Latitude 7490', ...], ...], // groupé par marque
  'os'         => ['Windows' => ['Windows 11', ...], ...], // groupé par famille
  'os_version' => ['' => ['23H2', 'Pro', ...],             // '' = générique
                   'Windows' => ['23H2', ...], ...],        // groupé par famille OS
]
```
Utilisé via `$options = require __DIR__ . "/includes/get_options.php";`

---

## `partials/header.php`

Génère le début de chaque page HTML. Points clés :

- Définit un fallback `e()` au cas où la page appelante n'a pas inclus `auth.php` (ex : `login.php`).
- Lit `$isLoggedIn`, `$pageTitle`, `$activePage`, `$bodyClass`, `$pageStyles` depuis le scope parent.
- Affiche la navbar Bootstrap avec les liens selon les permissions en session.
- Affiche le sidebar latéral sur les pages protégées.

## `partials/footer.php`

Ferme le HTML (`</body></html>`), inclut le bundle Bootstrap JS. Lit `$pageScripts` si défini — ce mécanisme permet aux pages d'injecter du JS en fin de page :
```php
$pageScripts = '<script>/* mon JS */</script>';
require __DIR__ . "/partials/footer.php";
```

---

## `login.php`

Page publique (pas de `require auth.php`). Flux :

1. Si déjà connecté (`$_SESSION['user_id']` existe) → redirect `/dashboard.php`.
2. POST : récupère `username` + `password`, cherche l'utilisateur en BDD, vérifie le hash bcrypt avec `password_verify()`.
3. Si succès : `session_regenerate_id(true)` (prévient le session fixation), charge toutes les permissions en session, redirect `/dashboard.php`.
4. Si échec : `$error = "Identifiants incorrects."` (message volontairement vague).

---

## `logout.php`

Détruit la session (`session_destroy()`), redirige vers `/login.php`.

---

## `dashboard.php`

Tableau de bord avec 6 requêtes SQL au chargement :

| Variable | Requête | Usage |
|----------|---------|-------|
| `$statusCounts` | `GROUP BY statut` | Compteurs par statut + total |
| `$total` | `array_sum($statusCounts)` | Pas de requête séparée |
| `$archCounts` | `GROUP BY architecture` | Barres architecture |
| `$topBrands` | `GROUP BY marque LIMIT 5` | Barres top marques |
| `$topOs` | `GROUP BY os LIMIT 5` | Barres top OS |
| `$recentPcs` | `ORDER BY updated_at DESC LIMIT 10` | Tableau dernières modifs |
| `$recentAdded` | `ORDER BY created_at DESC LIMIT 5` | Liste derniers ajouts |

---

## `pcs.php`

Liste principale avec filtres, tri et pagination.

### Filtrage
Construit dynamiquement une clause `WHERE` avec des paramètres PDO selon les filtres actifs (`$q`, `$statut`, `$arch`, `$marque`). Le filtre texte `$q` cherche dans 8 colonnes avec `LIKE`.

### Tri
La colonne de tri est validée contre un tableau `$allowedSort` (whitelist) avant d'être injectée dans le SQL. La direction (`asc`/`desc`) est validée par comparaison stricte. Cela empêche toute injection SQL via les paramètres de tri.

```php
$allowedSort = ['hostname' => 'hostname', 'serial' => 'serial', ...];
$sort = isset($allowedSort[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'updated_at';
```

### Pagination côté serveur
1. Une requête `COUNT(*)` donne le nombre total.
2. La requête principale utilise `LIMIT :perPage OFFSET :offset`.
3. `$page` est clampée entre 1 et `$totalPages`.

### Suppression en lot (bulk delete)
La sélection survit aux changements de page via `localStorage` :
- Chaque case cochée ajoute/retire l'ID dans un `Set`.
- Le Set est sérialisé en JSON dans `localStorage` sous la clé `pc_selection`.
- Au chargement d'une nouvelle page, les cases de la page courante sont restaurées depuis le Set.
- À la soumission du formulaire, les IDs hors-page (non présents comme checkboxes dans le DOM) sont injectés comme `<input type="hidden">`.

### Fonction `sortLink()`
Définie inline dans le template, elle génère un lien avec les flèches de tri ▲/▼ en préservant tous les filtres courants via `http_build_query(array_merge($_GET, [...]))`.

---

## `pc_add.php`

Formulaire d'ajout de PC.

### Chargement initial
```php
$options = require "./includes/get_options.php";  // toutes les listes déroulantes
$customFields = get_custom_fields($pdo);           // champs perso visibles
$existingUsers = ...;                              // pour l'autocomplete utilisateur
```

### Traitement POST
1. CSRF check.
2. Validation des champs obligatoires et des valeurs d'enum.
3. Validation des champs personnalisés obligatoires (postés sous le préfixe `cf_`).
4. INSERT dans `pcs`.
5. Pour chaque champ custom : `INSERT ... ON DUPLICATE KEY UPDATE` dans `pc_custom_data`.
6. Redirect `pcs.php?msg=added`.

### Données passées au JS
```php
<script>
window.modelesByBrand = <?= json_encode($modelesByBrand) ?>;
window.versionsByOsFamily = <?= json_encode($options['os_version']) ?>;
</script>
```
Ces objets JSON alimentent les cascades JS.

---

## `pc_edit.php`

Même logique que `pc_add.php` mais en modification. Différences :

- Charge le PC existant par `$_GET['id']` (404 si non trouvé).
- Charge les valeurs des champs custom existants pour pré-remplir le formulaire.
- Utilise `UPDATE pcs ... WHERE id = ?` + `INSERT ... ON DUPLICATE KEY UPDATE` pour les custom data.
- Le numéro de série est modifiable mais validé comme unique.

---

## `pc_delete.php`

POST uniquement. Lit `$_POST['id']`, exécute `DELETE FROM pcs WHERE id = ?`. Les `pc_custom_data` associés sont supprimés automatiquement par la contrainte `ON DELETE CASCADE`.

## `pc_delete_bulk.php`

Reçoit `$_POST['ids']` (tableau d'IDs depuis la sélection multi-pages). Valide chaque ID en entier, puis exécute `DELETE FROM pcs WHERE id IN (...)` avec placeholders générés dynamiquement.

---

## `assets/js/pc_form.js`

Script partagé entre `pc_add.php` et `pc_edit.php`. Tout est dans un IIFE `(function() { ... })()` pour ne pas polluer le scope global.

### Cascade Marque → Modèle
- Écoute `change` sur `#marque`.
- Reconstruit `#modele` depuis `window.modelesByBrand[selectedBrand]`.
- Ajoute toujours une option `+ Modèle personnalisé` (valeur `__custom__`).
- Si `__custom__` sélectionné : affiche `#modele_custom` (input texte).

### Cascade OS → Version OS
- `getOsFamily()` lit le label de l'`<optgroup>` parent de l'option sélectionnée dans `#os`.
- `addOptgroup(label, versions, current)` / `addFlat(versions, current)` : helpers pour construire le `<select>`.
- `populateOsVersions(selectedVersion)` : reconstruit `#os_version` selon la famille de l'OS choisi. Si famille connue dans `versByFamily` : affiche les versions spécifiques + les versions génériques (sans doublons via `Set`). Sinon affiche tout groupé.
- Appelé au chargement (pour `pc_edit.php` qui pré-remplit l'OS) et à chaque `change` de `#os`.

### Toggle utilisateur personnalisé
- Option `__nouveau__` dans `#utilisateur` → affiche `#utilisateur_custom`.
- Swap du nom HTML (`name="utilisateur"`) entre le `<select>` et l'`<input>` selon la sélection, pour que le POST envoie toujours un seul champ `utilisateur`.

### Soumission formulaire
Résout les champs personnalisés juste avant le POST :
- Si `#modele_custom` a une valeur → l'injecte comme `<input hidden name="modele">`.
- Si `#os_version_custom` a une valeur → l'injecte comme `<input hidden name="os_version">`.

---

## `admin/fields.php`

Gestion des champs de l'inventaire.

### Actions POST
| `action` | Effet |
|----------|-------|
| `toggle_visibility` | Toggle `is_visible` en BDD |
| `add_field` | INSERT dans `custom_fields` avec `display_order` auto (MAX+1) |
| `edit_field` | UPDATE `field_label`, `field_type`, `is_required` d'un champ |
| `reorder_fields` | UPDATE `display_order` en lot depuis un JSON `["id1","id2",...]` |
| `delete_field` | DELETE si le champ n'est pas dans `$protectedFields` |

### Champs protégés
```php
$protectedFields = ['hostname', 'serial', 'marque', 'utilisateur', 'os', 'architecture', 'statut'];
```
Ces champs correspondent aux colonnes obligatoires de la table `pcs`. Le bouton Supprimer est masqué et l'action delete les ignore.

### Modal d'édition
Le bouton Modifier porte les données en attributs `data-*`. Le JS écoute `show.bs.modal` et copie ces valeurs dans le formulaire modal :
```javascript
modal.addEventListener("show.bs.modal", function(e) {
  var btn = e.relatedTarget;
  document.getElementById("editFieldId").value = btn.dataset.fieldId;
  // ...
});
```

### Drag & drop (SortableJS)
```javascript
new Sortable(tbody, {
  onEnd: function(evt) {
    var ids = [...tbody.querySelectorAll('tr')].map(tr => tr.dataset.id);
    fetch('/admin/fields.php', { method: 'POST', body: ... });
  }
});
```
À chaque réordonnancement, envoie une requête AJAX avec le nouvel ordre des IDs. Le serveur met à jour `display_order` pour chaque champ.

---

## `admin/options.php`

Gestion des valeurs des 4 listes déroulantes : Marques, Modèles, OS, Versions OS.

### Navigation par onglets
`$tab = $_GET['tab'] ?? 'marque'` — validé contre `$allowed_tabs`.

### Chargement conditionnel
```php
$marqueRows = $tab === 'modele' ? $pdo->query(...)->fetchAll() : [];
$osGroups   = $tab === 'os'    ? $pdo->query(...)->fetchAll() : [];
```
On ne charge les données de référence que si l'onglet actif en a besoin.

### Ordre de tri
- `os_version` : trié par `option_group`, `option_value` (sans `display_order` car les versions sont textuelles).
- Autres : trié par `option_group`, `display_order`, `option_value`.

### Actions POST
| `action` | Effet |
|----------|-------|
| `add` | INSERT dans `field_options` avec `display_order` = MAX+1 |
| `delete` | DELETE par id |
| `dedupe` | Supprime les doublons (garde MIN(id) par field_name + option_group + option_value) |
| `reorder` | UPDATE `display_order` en lot via JSON |

---

## `admin/import.php`

Import CSV avec synchronisation automatique des listes.

### Parsing CSV
```php
$handle = fopen($_FILES['csv']['tmp_name'], 'r');
$headers = fgetcsv($handle);  // 1ère ligne = en-têtes
while (($row = fgetcsv($handle)) !== false) { ... }
```

### Validation par ligne
- Colonnes obligatoires manquantes → erreur de ligne (la ligne est ignorée mais le CSV continue).
- `serial` absent → auto-généré : `NOSERIAL-{hostname}`.
- Valeurs enum (`architecture`, `statut`) → validées contre les constantes PHP.

### UPSERT
```sql
INSERT INTO pcs (...) VALUES (...)
ON DUPLICATE KEY UPDATE hostname=VALUES(hostname), ...
```
`rowCount()` retourne 1 si INSERT, 2 si UPDATE, 0 si aucun changement.

### Synchronisation des listes déroulantes
Après import, les valeurs importées sont ajoutées à `field_options` si elles n'y sont pas encore :
1. Collecte des valeurs uniques par champ dans `$toSync` (pendant la boucle CSV).
2. Aplatissement en `$candidates = [[field, group, value], ...]`.
3. Filtrage des valeurs firmware parasites (`$garbage`).
4. 2 requêtes batch : récupère les valeurs existantes + les max display_order.
5. INSERT uniquement des nouvelles valeurs (avec déduplication intra-import).

---

## `admin/stats_utilisateurs.php`

Stats PCs par utilisateur. Une seule requête SQL :

```sql
SELECT
    COALESCE(NULLIF(TRIM(utilisateur), ''), ?) AS utilisateur,
    COUNT(*) AS nb_pcs,
    SUM(statut = 'En service')    AS en_service,
    SUM(statut = 'En stock')      AS en_stock,
    SUM(statut = 'En réparation') AS en_reparation,
    SUM(statut = 'Retiré')        AS retire
FROM pcs
GROUP BY utilisateur
ORDER BY nb_pcs DESC, utilisateur ASC
```

- `NULLIF(TRIM(utilisateur), '')` → null si vide.
- `COALESCE(..., ?)` → remplace null par la constante `UNASSIGNED`.
- `SUM(statut = 'En service')` → compte les lignes où la condition est vraie (MySQL/MariaDB retourne 1/0 pour les booléens).

La barre de progression utilise `$maxPcs` (le premier résultat, déjà le max car ORDER BY nb_pcs DESC) pour calculer `$pct = round($nb / $maxPcs * 100)`.

Le filtrage live côté client est fait par JS sur l'attribut `data-name` de chaque `<tr>`.

---

## `admin/users.php`

Gestion des comptes.

### Création
Hash bcrypt via `password_hash($password, PASSWORD_BCRYPT)`. INSERT avec toutes les permissions issues des checkboxes POST.

### Mise à jour des permissions
Protection contre la désélection de son propre rôle admin :
```php
if ($uid === (int)$_SESSION["user_id"] && !isset($_POST["is_admin"])) {
    header("Location: /admin/users.php?msg=self_admin_error");
    exit;
}
```

### Suppression
Interdit de se supprimer soi-même.

---

## `admin/download_script.php`

Sert les scripts de collecte en téléchargement forcé :
```php
header('Content-Disposition: attachment; filename="..."');
header('Content-Type: application/octet-stream');
readfile($scriptPath);
```
Paramètre `?script=windows|linux|bat` — validé contre une whitelist.

---

## `database/`

### `schema.sql`
Crée la table principale `pcs` avec :
- `UNIQUE KEY uk_pcs_serial` → garantit l'unicité des numéros de série (fondement de l'UPSERT).
- `INDEX idx_pcs_hostname` + `idx_pcs_utilisateur` → accélère les recherches fréquentes.

### `schema_user.sql`
Table `users`. Compte par défaut `admin`/`root` inséré avec `ON DUPLICATE KEY UPDATE username = username` (no-op si déjà présent → idempotent).

### `schema_custom_fields.sql`
- `custom_fields` : définition des champs (nom, type, ordre, visibilité).
- `pc_custom_data` : valeurs avec `FOREIGN KEY (pc_id) REFERENCES pcs(id) ON DELETE CASCADE` → suppression automatique des données custom quand un PC est supprimé.

### `schema_options.sql`
Table `field_options` avec données seed (marques, modèles, OS, versions).

---

## Patterns récurrents

### Flash messages via `?msg=`
Après un POST réussi, redirect avec `?msg=xxx`. La page lit `$_GET['msg']` et affiche une alerte Bootstrap dismissible. Cela évite le re-POST si l'utilisateur rafraîchit.

### Validation enum côté PHP
```php
if (!in_array($architecture, PC_ARCH, true)) $errors[] = "Architecture invalide";
```
Le troisième argument `true` de `in_array` force la comparaison stricte (type + valeur). Important car `in_array(0, ['x64'], false)` retournerait `true` (coercition de type).

### Préparation de requêtes réutilisée
```php
$stmt = $pdo->prepare("INSERT ... VALUES (?,?,?)");
foreach ($items as $item) { $stmt->execute([...]); }
```
`prepare()` est appelé une seule fois, puis `execute()` est appelé en boucle. Plus efficace que de recréer la requête à chaque itération.
