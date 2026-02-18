<?php
require __DIR__ . "/auth.php";
require_perm("can_add");
require __DIR__ . "/db.php";

$allowedArch = ["x86","x64","arm64"];
$allowedStatut = ["En service","En stock","En réparation","Retiré"];

// Charger les options de configuration
$options = require __DIR__ . "/get_options.php";

// Récupérer les utilisateurs existants
$stmtUsers = $pdo->query("SELECT DISTINCT utilisateur FROM pcs ORDER BY utilisateur");
$existingUsers = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les marques existantes
$stmtBrands = $pdo->query("SELECT DISTINCT marque FROM pcs ORDER BY marque");
$existingBrands = $stmtBrands->fetchAll(PDO::FETCH_COLUMN);
$allBrands = array_unique(array_merge($options['marque'], $existingBrands));

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

      header("Location: pcs.php");
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

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$pageTitle = "Ajouter un PC";
$activePage = "pc_add";
require __DIR__ . "/partials/header.php";
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Ajouter un PC</h3>
    <a class="btn btn-outline-secondary" href="pcs.php">
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
            <?php foreach (["x86","x64","arm64"] as $a): ?>
              <?php $cur = $_POST["architecture"] ?? ""; ?>
              <option value="<?= e($a) ?>" <?= $cur === $a ? "selected" : "" ?>><?= e($a) ?></option>
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
            <?php foreach (["En service","En stock","En réparation","Retiré"] as $s): ?>
              <?php $cur = $_POST["statut"] ?? ""; ?>
              <option value="<?= e($s) ?>" <?= $cur === $s ? "selected" : "" ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Remarques</label>
          <textarea class="form-control" name="remarques" rows="3"
                    placeholder="Notes supplémentaires..."><?= e($_POST["remarques"] ?? "") ?></textarea>
        </div>

        <div class="col-12">
          <hr>
          <div class="d-flex justify-content-end gap-2">
            <a href="pcs.php" class="btn btn-secondary">Annuler</a>
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
             . '<script src="assets/js/pc_form.js"></script>';
require __DIR__ . "/partials/footer.php";
