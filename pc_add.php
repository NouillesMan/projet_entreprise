<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_add");
require __DIR__ . "/includes/db.php";

// Charger les options de configuration (marques, modèles, OS, versions)
// require avec retour de valeur : get_options.php exécute son code et retourne un tableau
$options = require __DIR__ . "/includes/get_options.php";

// Charge les fonctions utilitaires partagées, notamment get_custom_fields() et les constantes
require __DIR__ . "/includes/helpers.php";

$allowedArch   = PC_ARCH;
$allowedStatut = PC_STATUTS;

// Récupérer les utilisateurs existants
$stmtUsers = $pdo->query("SELECT DISTINCT utilisateur FROM pcs ORDER BY utilisateur");
$existingUsers = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les marques existantes
$stmtBrands = $pdo->query("SELECT DISTINCT marque FROM pcs ORDER BY marque");
$existingBrands = $stmtBrands->fetchAll(PDO::FETCH_COLUMN);
$allBrands = array_unique(array_merge($options['marque'], $existingBrands));

// Récupère les champs personnalisés visibles définis par l'admin via admin/fields.php.
// Ce sont des colonnes supplémentaires à afficher et sauvegarder en plus des champs fixes.
$customFields = get_custom_fields($pdo);

$errors = [];
// Si formulaire envoyé
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  $hostname = trim($_POST["hostname"] ?? "");
  $serial = trim($_POST["serial"] ?? "");
  $marque = trim($_POST["marque"] ?? "");
  $modele = trim($_POST["modele"] ?? "");
  $utilisateur = trim($_POST["utilisateur"] ?? "");
  $os = trim($_POST["os"] ?? "");
  $os_version = trim($_POST["os_version"] ?? "");
  $architecture = $_POST["architecture"] ?? "";
  $domaine = trim($_POST["domaine"] ?? "");
  $statut = $_POST["statut"] ?? "";
  $remarques = trim($_POST["remarques"] ?? "");

  if ($hostname === "") $errors[] = "Hostname obligatoire";
  if ($serial === "") $errors[] = "Serial obligatoire";
  if ($marque === "") $errors[] = "Marque obligatoire";
  if ($utilisateur === "") $errors[] = "Utilisateur obligatoire";
  if ($os === "") $errors[] = "OS obligatoire";
  if (!in_array($architecture, $allowedArch, true)) $errors[] = "Architecture invalide";
  if (!in_array($statut, $allowedStatut, true)) $errors[] = "Statut invalide";
  // Validation des champs personnalisés obligatoires.
  // On boucle sur chaque champ custom chargé depuis la BDD.
  foreach ($customFields as $cf) {
    // is_required : flag BDD (1 = obligatoire, 0 = facultatif)
    // Les valeurs des champs custom sont postées sous le préfixe "cf_" + field_name
    // Ex : le champ "localisation" arrive dans $_POST["cf_localisation"]
    // L'opérateur ?? "" évite un warning si la clé n'existe pas dans $_POST
    if ($cf['is_required'] && trim($_POST["cf_" . $cf['field_name']] ?? "") === "") {
      $errors[] = $cf['field_label'] . " obligatoire"; // field_label = texte lisible par l'humain
    }
  }

  if (!$errors) {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO pcs (hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, remarques)
        VALUES (:hostname, :serial, :marque, :modele, :utilisateur, :os, :os_version, :architecture, :domaine, :statut, :remarques)
      ");
      $stmt->execute([
        ":hostname" => $hostname,
        ":serial" => $serial,
        ":marque" => $marque,
        ":modele" => $modele,
        ":utilisateur" => $utilisateur,
        ":os" => $os,
        ":os_version" => $os_version,
        ":architecture" => $architecture,
        ":domaine" => $domaine,
        ":statut" => $statut,
        ":remarques" => $remarques,
      ]);

      // Sauvegarde des champs personnalisés dans la table pc_custom_data.
      // On ne fait ce bloc que s'il y a effectivement des champs custom (optimisation).
      if (!empty($customFields)) {
        // lastInsertId() retourne l'id AUTO_INCREMENT du PC qu'on vient d'insérer.
        // (int) : on force en entier pour la sécurité (évite une injection si jamais).
        $lastId = (int)$pdo->lastInsertId();

        // On prépare la requête UNE SEULE FOIS en dehors de la boucle.
        // PDO réutilise la requête compilée à chaque execute() → plus performant que N prepare().
        // Les ? sont des paramètres positionnels (placeholders) : remplacés par les vraies valeurs à l'exécution.
        $stmtCf = $pdo->prepare("INSERT INTO pc_custom_data (pc_id, field_name, field_value) VALUES (?, ?, ?)");

        foreach ($customFields as $cf) {
          // execute() envoie les valeurs dans l'ordre des ? de la requête préparée.
          // trim() supprime les espaces parasites autour de la valeur saisie.
          $stmtCf->execute([$lastId, $cf['field_name'], trim($_POST["cf_" . $cf['field_name']] ?? "")]);
        }
      }

      header("Location: /pcs.php");
      exit;
    } catch (PDOException $e) {
      if ($e->getCode() === "23000") {
        $errors[] = "Ce numéro de série existe déjà";
      } else {
        throw $e;
      }
    }
  }
}


$pageTitle = "Ajouter un PC";
$activePage = "pc_add";
require __DIR__ . "/partials/header.php";
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Ajouter un PC</h3>
    <a class="btn btn-outline-secondary" href="/pcs.php">
      <i class="bi bi-arrow-left"></i> Retour
    </a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <h5 class="alert-heading">Erreurs de validation</h5>
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header">
      <h6 class="mb-0">Informations du PC</h6>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
          <label class="form-label">Hostname <span class="text-danger">*</span></label>
          <input class="form-control" name="hostname" value="<?= e($_POST["hostname"] ?? "") ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Numéro de série <span class="text-danger">*</span></label>
          <input class="form-control" name="serial" value="<?= e($_POST["serial"] ?? "") ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Marque <span class="text-danger">*</span></label>
          <select class="form-select" name="marque" id="marque" required>
            <option value="">Sélectionner...</option>
            <?php foreach ($allBrands as $brand): ?>
              <option value="<?= e($brand) ?>" <?= ($_POST["marque"] ?? "") === $brand ? "selected" : "" ?>>
                <?= e($brand) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Modèle</label>
          <select class="form-select" name="modele" id="modele">
            <option value="">Sélectionner d'abord une marque...</option>
          </select>
          <small class="text-muted">Ou saisir manuellement:</small>
          <input class="form-control form-control-sm mt-1" name="modele_custom" id="modele_custom"
                 value="<?= e($_POST["modele"] ?? "") ?>" placeholder="Modèle personnalisé">
        </div>
        <div class="col-md-4">
          <label class="form-label">Utilisateur <span class="text-danger">*</span></label>
          <select class="form-select" name="utilisateur" id="utilisateur" required>
            <option value="">Sélectionner...</option>
            <?php foreach ($existingUsers as $user): ?>
              <option value="<?= e($user) ?>" <?= ($_POST["utilisateur"] ?? "") === $user ? "selected" : "" ?>>
                <?= e($user) ?>
              </option>
            <?php endforeach; ?>
            <option value="__nouveau__">+ Nouveau utilisateur</option>
          </select>
          <input class="form-control mt-1" name="utilisateur_custom" id="utilisateur_custom"
                 placeholder="Nom du nouvel utilisateur" style="display:none;">
        </div>

        <div class="col-md-4">
          <label class="form-label">OS <span class="text-danger">*</span></label>
          <select class="form-select" name="os" id="os" required>
            <option value="">Sélectionner...</option>
            <?php foreach ($options['os'] as $osFamily => $osList): ?>
              <optgroup label="<?= e($osFamily) ?>">
                <?php foreach ($osList as $osName): ?>
                  <option value="<?= e($osName) ?>" <?= ($_POST["os"] ?? "") === $osName ? "selected" : "" ?>>
                    <?= e($osName) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Version OS</label>
          <select class="form-select" name="os_version">
            <option value="">Aucune</option>
            <?php foreach ($options['os_version'] as $version): ?>
              <option value="<?= e($version) ?>" <?= ($_POST["os_version"] ?? "") === $version ? "selected" : "" ?>>
                <?= e($version) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Ou saisir manuellement:</small>
          <input class="form-control form-control-sm mt-1" name="os_version_custom"
                 value="<?= e($_POST["os_version"] ?? "") ?>" placeholder="Version personnalisée">
        </div>
        <div class="col-md-4">
          <label class="form-label">Architecture <span class="text-danger">*</span></label>
          <select class="form-select" name="architecture" required>
            <option value="">Sélectionner...</option>
            <?php $curArch = $_POST["architecture"] ?? ""; ?>
            <?php foreach ($allowedArch as $a): ?>
              <option value="<?= e($a) ?>" <?= $curArch === $a ? "selected" : "" ?>><?= e($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Domaine</label>
          <input class="form-control" name="domaine" value="<?= e($_POST["domaine"] ?? "") ?>"
                 placeholder="Ex: corp.example.com">
        </div>
        <div class="col-md-6">
          <label class="form-label">Statut <span class="text-danger">*</span></label>
          <select class="form-select" name="statut" required>
            <option value="">Sélectionner...</option>
            <?php $curStatut = $_POST["statut"] ?? ""; ?>
            <?php foreach ($allowedStatut as $s): ?>
              <option value="<?= e($s) ?>" <?= $curStatut === $s ? "selected" : "" ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Remarques</label>
          <textarea class="form-control" name="remarques" rows="3"
                    placeholder="Notes supplémentaires..."><?= e($_POST["remarques"] ?? "") ?></textarea>
        </div>

        <?php if (!empty($customFields)): // N'affiche ce bloc que si des champs custom existent ?>
        <?php foreach ($customFields as $cf): // On boucle sur chaque champ custom ?>
        <div class="col-md-6"><!-- Chaque champ prend la moitié de la largeur -->

          <label class="form-label">
            <?= e($cf['field_label']) ?><!-- Libellé lisible ex : "Localisation" -->
            <?php if ($cf['is_required']): ?>
              <span class="text-danger">*</span><!-- Astérisque rouge si obligatoire -->
            <?php endif; ?>
          </label>

          <?php if ($cf['field_type'] === 'textarea'): ?>
            <!-- Cas spécial : le type "textarea" nécessite une balise différente de <input> -->
            <textarea class="form-control"
                      name="cf_<?= e($cf['field_name']) ?>"<!-- Préfixe "cf_" pour distinguer des champs fixes -->
                      rows="3"<?= $cf['is_required'] ? ' required' : '' ?>
                      ><?= e($_POST["cf_" . $cf['field_name']] ?? "") ?></textarea>
                      <!-- Pré-remplissage si le formulaire a été soumis avec des erreurs -->

          <?php else: ?>
            <!-- Pour tous les autres types : text, number, date -->
            <input class="form-control"
                   <?php
                   // in_array() vérifie que le type est bien dans la liste des types HTML valides.
                   // Si l'admin avait rentré un type inconnu en BDD, on fallback sur 'text' (sécurité).
                   // On n'échappe pas la valeur ici car in_array() garantit déjà qu'elle est sûre.
                   ?>
                   type="<?= in_array($cf['field_type'], ['text','number','date']) ? e($cf['field_type']) : 'text' ?>"
                   name="cf_<?= e($cf['field_name']) ?>"
                   value="<?= e($_POST["cf_" . $cf['field_name']] ?? "") ?>"
                   <?= $cf['is_required'] ? ' required' : '' ?>>
                   <!-- L'attribut HTML "required" active la validation native du navigateur -->
          <?php endif; ?>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
          <hr>
          <div class="d-flex justify-content-end gap-2">
            <a href="/pcs.php" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-plus-circle"></i> Ajouter le PC
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
$pageScripts = '<script>window.modelesByBrand = ' . json_encode($options['modele']) . ';</script>'
             . '<script src="/assets/js/pc_form.js"></script>';
require __DIR__ . "/partials/footer.php";
