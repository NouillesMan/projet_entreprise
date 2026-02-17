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

  // validations mini
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
      // Erreur serial unique
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
require __DIR__ . "/partials/header.php";
?>
<div class="container py-4">
  <div class="row mb-4">
    <div class="col">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h2 mb-0">Ajouter un PC</h1>
        <a class="btn btn-outline-secondary" href="pcs.php">
          <i class="bi bi-arrow-left"></i> Retour
        </a>
      </div>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <h5 class="alert-heading">Erreurs de validation</h5>
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
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
            <option value="__nouveau__">➕ Nouveau utilisateur</option>
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
          <div class="d-grid gap-2 d-md-flex justify-content-md-end">
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
$pageScripts = <<<'JS'
<script>
// Configuration des modèles par marque
const modelesByBrand = <?= json_encode($options['modele']) ?>;

// Gérer le changement de marque pour mettre à jour les modèles
document.getElementById('marque').addEventListener('change', function() {
  const brand = this.value;
  const modeleSelect = document.getElementById('modele');

  // Réinitialiser le select
  modeleSelect.innerHTML = '<option value="">Sélectionner...</option>';

  // Ajouter les modèles de la marque sélectionnée
  if (modelesByBrand[brand]) {
    modelesByBrand[brand].forEach(model => {
      const option = document.createElement('option');
      option.value = model;
      option.textContent = model;
      modeleSelect.appendChild(option);
    });
  }

  // Ajouter l'option "Autre"
  const otherOption = document.createElement('option');
  otherOption.value = '__custom__';
  otherOption.textContent = '➕ Modèle personnalisé';
  modeleSelect.appendChild(otherOption);
});

// Gérer le modèle personnalisé
document.getElementById('modele').addEventListener('change', function() {
  const customInput = document.getElementById('modele_custom');
  if (this.value === '__custom__') {
    customInput.style.display = 'block';
    customInput.required = true;
  } else {
    customInput.style.display = 'none';
    customInput.required = false;
  }
});

// Gérer l'utilisateur personnalisé
document.getElementById('utilisateur').addEventListener('change', function() {
  const customInput = document.getElementById('utilisateur_custom');
  if (this.value === '__nouveau__') {
    customInput.style.display = 'block';
    customInput.required = true;
    this.removeAttribute('name'); // Le custom input prendra le nom
    customInput.setAttribute('name', 'utilisateur');
  } else {
    customInput.style.display = 'none';
    customInput.required = false;
    this.setAttribute('name', 'utilisateur');
    customInput.removeAttribute('name');
  }
});

// Gérer la soumission du formulaire pour les champs personnalisés
document.querySelector('form').addEventListener('submit', function(e) {
  const modeleSelect = document.getElementById('modele');
  const modeleCustom = document.getElementById('modele_custom');

  // Si un modèle personnalisé est saisi, l'utiliser
  if (modeleCustom.value.trim() !== '') {
    modeleSelect.value = '';
    // Créer un champ caché avec la valeur personnalisée
    const hiddenModele = document.createElement('input');
    hiddenModele.type = 'hidden';
    hiddenModele.name = 'modele';
    hiddenModele.value = modeleCustom.value;
    this.appendChild(hiddenModele);
    modeleCustom.removeAttribute('name');
  } else if (modeleSelect.value && modeleSelect.value !== '__custom__') {
    // Utiliser la valeur du select
    modeleSelect.setAttribute('name', 'modele');
  }

  // Même chose pour os_version
  const osVersionSelect = document.querySelector('select[name="os_version"]');
  const osVersionCustom = document.querySelector('input[name="os_version_custom"]');

  if (osVersionCustom.value.trim() !== '') {
    osVersionSelect.value = '';
    const hiddenVersion = document.createElement('input');
    hiddenVersion.type = 'hidden';
    hiddenVersion.name = 'os_version';
    hiddenVersion.value = osVersionCustom.value;
    this.appendChild(hiddenVersion);
    osVersionCustom.removeAttribute('name');
  } else if (osVersionSelect.value) {
    osVersionSelect.setAttribute('name', 'os_version');
  }
});
</script>
JS;
require __DIR__ . "/partials/footer.php";
