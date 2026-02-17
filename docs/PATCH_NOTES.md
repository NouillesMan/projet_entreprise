# Patch Notes — 2026-02-17

## 1. Rename `admin_option.php` -> `admin_options.php`

Le fichier etait nomme `admin_option.php` mais tous les liens et redirections du projet
referencaient `admin_options.php` (avec un 's'). Renommage du fichier pour correspondre
a toutes les references. Cela corrige les erreurs 404 lors de la navigation vers la page
d'administration des options.

**Fichier concerne :** `admin_option.php` (renomme en `admin_options.php`)

---

## 2. Protection CSRF (tous les formulaires)

Ajout d'un systeme complet de jetons CSRF dans `auth.php` avec trois fonctions :
- `csrf_token()` — genere/recupere un jeton par session
- `csrf_field()` — affiche un `<input>` cache contenant le jeton
- `csrf_check()` — valide le jeton sur les requetes POST et renvoie une erreur 403 si invalide

Ajout de `csrf_check()` en haut de chaque gestionnaire POST et de `<?= csrf_field() ?>`
dans chaque `<form method="post">` (~15 formulaires au total).

**Fichiers modifies :**
- `auth.php` (nouvelles fonctions)
- `pc_add.php`
- `pc_edit.php`
- `pc_delete.php`
- `admin_fields.php`
- `admin_options.php`
- `admin_users.php`
- `pcs.php` (formulaire de suppression inline)

---

## 3. `pc_delete.php` — verification d'existence avant suppression

Avant, le fichier executait directement `DELETE FROM pcs WHERE id = ?` sans verifier
si le PC existait. Desormais, un SELECT est effectue au prealable pour verifier que la
ligne existe, et renvoie "PC introuvable" si ce n'est pas le cas.

**Fichier modifie :** `pc_delete.php`

---

## 4. `admin_users.php` — structure HTML invalide corrigee

La balise `<form>` pour les checkboxes de permissions etait placee comme enfant direct
de `<tr>`, englobant des elements `<td>` — du HTML invalide pouvant casser sur certains
navigateurs. Correction effectuee :
- Deplacement du formulaire dans un `<td>` avec `class="d-none"` (masque)
- Utilisation de l'attribut HTML5 `form="perm-form-{id}"` sur chaque checkbox pour les
  associer a leur formulaire sans imbrication

**Fichier modifie :** `admin_users.php`

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

## 7. `STRUCTURE.md` — mise a jour complete

- Ajout de tous les fichiers manquants : `auth.php`, `login.php`, `logout.php`,
  `get_options.php`, `admin_fields.php`, `admin_options.php`, `admin_users.php`,
  `schema_custom_fields.sql`, `schema_options.sql`, `schema_user.sql`, `setup.sh`,
  `partials/mobile_nav.php`
- Ajout de nouvelles sections : "Authentification & Securite", "Administration"
- Mise a jour des descriptions existantes (ex: `pc_delete.php` mentionne maintenant
  la verification d'existence)
- Suppression de "Authentification" de la liste "Evolutions Futures" puisqu'elle est
  deja implementee

**Fichier modifie :** `STRUCTURE.md`
