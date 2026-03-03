<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_edit");
require __DIR__ . "/includes/db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

$allowedArch = ["x86","x64","arm64"];
$allowedStatut = ["En service","En stock","En réparation","Retiré"];

// Charger les options de configuration (marques, modèles, OS, versions)
$options = require __DIR__ . "/includes/get_options.php";

// Charge les fonctions utilitaires partagées, notamment get_custom_fields()
require __DIR__ . "/includes/helpers.php";

// Récupérer les utilisateurs existants
$stmtUsers = $pdo->query("SELECT DISTINCT utilisateur FROM pcs ORDER BY utilisateur");
$existingUsers = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les marques existantes
$stmtBrands = $pdo->query("SELECT DISTINCT marque FROM pcs ORDER BY marque");
$existingBrands = $stmtBrands->fetchAll(PDO::FETCH_COLUMN);
$allBrands = array_unique(array_merge($options['marque'], $existingBrands));

// Récupère les champs personnalisés visibles définis par l'admin.
$customFields = get_custom_fields($pdo);

// Charge les valeurs actuelles des champs custom pour CE PC, afin de pré-remplir le formulaire.
$customValues = []; // Tableau associatif : field_name => valeur actuelle en BDD
if (!empty($customFields)) {
    // Requête préparée : le ? sera remplacé par $id lors de execute()
    $stmtCv = $pdo->prepare("SELECT field_name, field_value FROM pc_custom_data WHERE pc_id = ?");
    $stmtCv->execute([$id]); // Exécute avec l'ID du PC en cours d'édition

    // On transforme le tableau de lignes en tableau associatif pour un accès rapide :
    // $customValues['localisation'] = 'Salle B' plutôt que chercher dans un tableau de lignes
    foreach ($stmtCv->fetchAll() as $row) {
        $customValues[$row['field_name']] = $row['field_value'];
    }
}

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
  // Validation des champs personnalisés obligatoires (même logique que pc_add.php)
  foreach ($customFields as $cf) {
    if ($cf['is_required'] && trim($_POST["cf_" . $cf['field_name']] ?? "") === "") {
      $errors[] = $cf['field_label'] . " obligatoire";
    }
  }

  if (!$errors) {
    try {
      $stmt = $pdo->prepare("
        UPDATE pcs
        SET hostname = :hostname,
            serial = :serial,
            marque = :marque,
            modele = :modele,
            utilisateur = :utilisateur,
            os = :os,
            os_version = :os_version,
            architecture = :architecture,
            domaine = :domaine,
            statut = :statut,
            remarques = :remarques
        WHERE id = :id
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
        ":id" => $id,
      ]);

      // Sauvegarde des champs personnalisés via un UPSERT (INSERT ou UPDATE selon existence).
      if (!empty($customFields)) {
        $stmtCf = $pdo->prepare(
            // INSERT ... ON DUPLICATE KEY UPDATE : c'est la syntaxe MySQL pour un "upsert".
            // Si la combinaison (pc_id, field_name) existe déjà → on met à jour field_value.
            // Si elle n'existe pas encore → on insère une nouvelle ligne.
            // C'est plus robuste qu'un DELETE + INSERT car ça gère les deux cas en une seule requête.
            "INSERT INTO pc_custom_data (pc_id, field_name, field_value) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)"
            // VALUES(field_value) fait référence à la valeur qu'on tentait d'insérer
        );
        foreach ($customFields as $cf) {
          // On utilise $id (l'ID du PC en cours de modification) et non lastInsertId()
          // car on ne crée pas un nouveau PC ici, on met à jour un existant.
          $stmtCf->execute([$id, $cf['field_name'], trim($_POST["cf_" . $cf['field_name']] ?? "")]);
        }
      }

      header("Location: /pcs.php");
      exit;
    } catch (PDOException $e) {
      if ($e->getCode() === "23000") {
        $errors[] = "Ce numéro de série existe déjà.";
      } else {
        throw $e;
      }
    }
  }
}

// Charger le PC (pour affichage / pré-remplissage)
$stmt = $pdo->prepare("SELECT * FROM pcs WHERE id = ?");
$stmt->execute([$id]);
$pc = $stmt->fetch();
if (!$pc) { die("PC introuvable"); }


$pageTitle = "Modifier un PC";
$activePage = "pcs";
require __DIR__ . "/partials/header.php";
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Modifier le PC</h3>
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
          <input class="form-control" name="hostname" value="<?= e($_POST["hostname"] ?? $pc["hostname"]) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Numéro de série <span class="text-danger">*</span></label>
          <input class="form-control" name="serial" value="<?= e($_POST["serial"] ?? $pc["serial"]) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Marque <span class="text-danger">*</span></label>
          <select class="form-select" name="marque" id="marque" required>
            <?php foreach ($allBrands as $brand): ?>
              <?php $currentMarque = $_POST["marque"] ?? $pc["marque"]; ?>
              <option value="<?= e($brand) ?>" <?= $currentMarque === $brand ? "selected" : "" ?>>
                <?= e($brand) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Modèle</label>
          <select class="form-select" name="modele" id="modele">
            <option value="">Sélectionner...</option>
            <?php
            $currentMarque = $_POST["marque"] ?? $pc["marque"];
            $currentModele = $_POST["modele"] ?? $pc["modele"];
            $modeleInList  = in_array($currentModele, $options['modele'][$currentMarque] ?? [], true);
            if (isset($options['modele'][$currentMarque])):
              foreach ($options['modele'][$currentMarque] as $model):
            ?>
              <option value="<?= e($model) ?>" <?= $currentModele === $model ? "selected" : "" ?>>
                <?= e($model) ?>
              </option>
            <?php
              endforeach;
            endif;
            ?>
          </select>
          <small class="text-muted">Ou saisir manuellement:</small>
          <input class="form-control form-control-sm mt-1" name="modele_custom" id="modele_custom"
                 value="<?= $modeleInList ? '' : e($currentModele) ?>" placeholder="Modèle personnalisé">
        </div>
        <div class="col-md-4">
          <label class="form-label">Utilisateur <span class="text-danger">*</span></label>
          <select class="form-select" name="utilisateur" id="utilisateur" required>
            <?php foreach ($existingUsers as $user): ?>
              <?php $currentUser = $_POST["utilisateur"] ?? $pc["utilisateur"]; ?>
              <option value="<?= e($user) ?>" <?= $currentUser === $user ? "selected" : "" ?>>
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
            <?php foreach ($options['os'] as $osFamily => $osList): ?>
              <optgroup label="<?= e($osFamily) ?>">
                <?php foreach ($osList as $osName): ?>
                  <?php $currentOS = $_POST["os"] ?? $pc["os"]; ?>
                  <option value="<?= e($osName) ?>" <?= $currentOS === $osName ? "selected" : "" ?>>
                    <?= e($osName) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Version OS</label>
          <?php
          $currentVersion  = $_POST["os_version"] ?? $pc["os_version"] ?? "";
          $osVersionInList = in_array($currentVersion, $options['os_version'], true);
          ?>
          <select class="form-select" name="os_version">
            <option value="">Aucune</option>
            <?php foreach ($options['os_version'] as $version): ?>
              <option value="<?= e($version) ?>" <?= $currentVersion === $version ? "selected" : "" ?>>
                <?= e($version) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Ou saisir manuellement:</small>
          <input class="form-control form-control-sm mt-1" name="os_version_custom"
                 value="<?= $osVersionInList ? '' : e($currentVersion) ?>" placeholder="Version personnalisée">
        </div>
        <div class="col-md-4">
          <label class="form-label">Architecture <span class="text-danger">*</span></label>
          <select class="form-select" name="architecture" required>
            <?php foreach (["x86","x64","arm64"] as $a): ?>
              <?php $cur = $_POST["architecture"] ?? $pc["architecture"]; ?>
              <option value="<?= e($a) ?>" <?= $cur === $a ? "selected" : "" ?>><?= e($a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Domaine</label>
          <input class="form-control" name="domaine" value="<?= e($_POST["domaine"] ?? $pc["domaine"]) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Statut <span class="text-danger">*</span></label>
          <select class="form-select" name="statut" required>
            <?php foreach (["En service","En stock","En réparation","Retiré"] as $s): ?>
              <?php $cur = $_POST["statut"] ?? $pc["statut"]; ?>
              <option value="<?= e($s) ?>" <?= $cur === $s ? "selected" : "" ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Remarques</label>
          <textarea class="form-control" name="remarques" rows="3"><?= e($_POST["remarques"] ?? $pc["remarques"]) ?></textarea>
        </div>

        <?php if (!empty($customFields)): ?>
        <?php foreach ($customFields as $cf): ?>
        <?php
          // Priorité de la valeur affichée (de haute en basse) :
          // 1. $_POST["cf_..."] : si le formulaire a été re-soumis avec des erreurs, on conserve la saisie
          // 2. $customValues[...] : valeur actuelle enregistrée en BDD pour ce PC
          // 3. "" : chaîne vide si le champ n'a jamais été renseigné
          $cfVal = $_POST["cf_" . $cf['field_name']] ?? ($customValues[$cf['field_name']] ?? "");
        ?>
        <div class="col-md-6">
          <label class="form-label">
            <?= e($cf['field_label']) ?>
            <?php if ($cf['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
          </label>
          <?php if ($cf['field_type'] === 'textarea'): ?>
            <textarea class="form-control" name="cf_<?= e($cf['field_name']) ?>"
                      rows="3"<?= $cf['is_required'] ? ' required' : '' ?>><?= e($cfVal) ?></textarea>
          <?php else: ?>
            <input class="form-control"
                   type="<?= in_array($cf['field_type'], ['text','number','date']) ? e($cf['field_type']) : 'text' ?>"
                   name="cf_<?= e($cf['field_name']) ?>"
                   value="<?= e($cfVal) ?>"<?= $cf['is_required'] ? ' required' : '' ?>>
            <!-- Différence avec pc_add.php : $cfVal contient la valeur BDD existante ($customValues),
                 alors que pc_add.php utilise uniquement $_POST (pas de valeur pré-existante). -->
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              Créé le: <?= e($pc["created_at"]) ?> | Modifié le: <?= e($pc["updated_at"]) ?>
            </small>
            <div class="d-flex justify-content-end gap-2">
              <a href="/pcs.php" class="btn btn-secondary">Annuler</a>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save"></i> Enregistrer les modifications
              </button>
            </div>
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
