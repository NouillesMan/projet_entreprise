# Patch Notes — 2026-03-03

## 1. Dashboard admin — Stats PCs par utilisateur (`admin/stats_utilisateurs.php`)

Nouvelle page exclusive aux administrateurs affichant la répartition des PC par utilisateur.

**Fonctionnalités :**
- 3 cartes de synthèse : total PCs, nombre d'utilisateurs distincts, maximum par utilisateur
- Tableau classé par nombre de PCs décroissant avec barre de progression relative
- Badges de statut par utilisateur (En service, En stock, En réparation, Retiré)
- Filtre de recherche instantané côté client
- Bouton de lien direct vers l'inventaire filtré par utilisateur
- Les PCs sans utilisateur sont regroupés sous "— Non attribué —"

**Accès :** Lien "Stats utilisateurs" ajouté dans la section Administration du sidebar (`partials/header.php`)

**Fichiers ajoutés/modifiés :** `admin/stats_utilisateurs.php`, `partials/header.php`

---

## 2. Centralisation de `function e()` dans `includes/auth.php`

La fonction d'échappement HTML `e()` était redéfinie localement dans 6 fichiers distincts.
Elle est désormais déclarée une seule fois dans `includes/auth.php` (qui est inclus en premier
dans toutes les pages protégées) et supprimée des fichiers suivants :

**Fichiers modifiés :** `pc_add.php`, `pc_edit.php`, `admin/users.php`, `admin/options.php`,
`admin/import.php`, `admin/stats_utilisateurs.php`

---

## 3. Extraction de `get_custom_fields()` dans `includes/helpers.php`

La requête SQL de chargement des champs personnalisés visibles était dupliquée à l'identique
dans `pc_add.php` et `pc_edit.php`. Elle est désormais centralisée dans la fonction
`get_custom_fields(PDO $pdo)` définie dans le nouveau fichier `includes/helpers.php`.

**Fichiers ajoutés/modifiés :** `includes/helpers.php`, `pc_add.php`, `pc_edit.php`

---

## 4. Champs personnalisés dans les formulaires PC (`pc_add.php`, `pc_edit.php`)

Les champs créés via `admin/fields.php` sont désormais affichés, validés et sauvegardés
dans les formulaires d'ajout et de modification de PC.

- Les champs marqués `is_visible = 1` et hors champs protégés apparaissent dans le formulaire
- La validation des champs obligatoires est intégrée côté serveur
- Les valeurs sont persistées dans `pc_custom_data` (INSERT pour l'ajout, UPSERT pour la modification)

**Fichiers modifiés :** `pc_add.php`, `pc_edit.php`

---

## 5. Correction `pc_edit.php` — `os_version_custom` toujours pré-rempli

Le champ de saisie manuelle de la version OS était toujours pré-rempli avec la valeur
actuelle, même quand cette valeur était déjà sélectionnée dans la liste déroulante.
Désormais, le champ manuel est vide si la version est présente dans la liste.

**Fichier modifié :** `pc_edit.php`

---

# Patch Notes — 2026-02-17

## 1. Rename `admin_option.php` -> `admin_options.php` (maintenant `admin/options.php`)

Le fichier etait nomme `admin_option.php` mais tous les liens et redirections du projet
referencaient `admin_options.php` (avec un 's'). Renommage du fichier pour correspondre
a toutes les references. Cela corrige les erreurs 404 lors de la navigation vers la page
d'administration des options.

**Fichier concerne :** `admin_option.php` (renomme en `admin_options.php`, maintenant `admin/options.php`)SERIA

---

## 2. Protection CSRF (tous les formulaires)

Ajout d'un systeme complet de jetons CSRF dans `includes/auth.php` avec trois fonctions :
- `csrf_token()` — genere/recupere un jeton par session
- `csrf_field()` — affiche un `<input>` cache contenant le jeton
- `csrf_check()` — valide le jeton sur les requetes POST et renvoie une erreur 403 si invalide

Ajout de `csrf_check()` en haut de chaque gestionnaire POST et de `<?= csrf_field() ?>`
dans chaque `<form method="post">` (~15 formulaires au total).

**Fichiers modifies :**
- `includes/auth.php` (nouvelles fonctions)
- `pc_add.php`
- `pc_edit.php`
- `pc_delete.php`
- `admin/fields.php`
- `admin/options.php`
- `admin/users.php`
- `pcs.php` (formulaire de suppression inline)

---

## 3. `pc_delete.php` — verification d'existence avant suppression

Avant, le fichier executait directement `DELETE FROM pcs WHERE id = ?` sans verifier
si le PC existait. Desormais, un SELECT est effectue au prealable pour verifier que la
ligne existe, et renvoie "PC introuvable" si ce n'est pas le cas.

**Fichier modifie :** `pc_delete.php`

---

## 4. `admin/users.php` — structure HTML invalide corrigee

La balise `<form>` pour les checkboxes de permissions etait placee comme enfant direct
de `<tr>`, englobant des elements `<td>` — du HTML invalide pouvant casser sur certains
navigateurs. Correction effectuee :
- Deplacement du formulaire dans un `<td>` avec `class="d-none"` (masque)
- Utilisation de l'attribut HTML5 `form="perm-form-{id}"` sur chaque checkbox pour les
  associer a leur formulaire sans imbrication

**Fichier modifie :** `admin/users.php`

---

## 5. Correction de la fixation de session dans `login.php`

Ajout de `session_regenerate_id(true)` immediatement apres la verification du mot de passe
et avant le stockage des donnees utilisateur en session. Cela empeche les attaques par
fixation de session ou un attaquant pre-definit un identifiant de session.

**Fichier modifie :** `login.php`

---

## 6. `README.md` — correction du nom de fichier SQL

Toutes les references a `schema_users.sql` (2 occurrences) ont ete corrigees en
`schema_user.sql` pour correspondre au nom reel du fichier sur le disque.

**Fichier modifie :** `README.md`

---

## 7. `docs/STRUCTURE.md` — mise a jour complete

- Ajout de tous les fichiers manquants : `includes/auth.php`, `login.php`, `logout.php`,
  `includes/get_options.php`, `admin/fields.php`, `admin/options.php`, `admin/users.php`,
  `database/schema_custom_fields.sql`, `database/schema_options.sql`, `database/schema_user.sql`,
  `setup.sh`
- Ajout de nouvelles sections : "Authentification & Securite", "Administration"
- Mise a jour des descriptions existantes (ex: `pc_delete.php` mentionne maintenant
  la verification d'existence)
- Suppression de "Authentification" de la liste "Evolutions Futures" puisqu'elle est
  deja implementee

**Fichier modifie :** `docs/STRUCTURE.md`
