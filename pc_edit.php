<?php
require __DIR__ . "/auth.php";
require_perm("can_edit");
require __DIR__ . "/db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

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

      header("Location: pcs.php");
      exit;
    } catch (PDOException $e) {
      // Erreur serial unique
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

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$pageTitle = "Modifier un PC";
require __DIR__ . "/partials/header.php";
?>
<div class="container py-4">
  <div class="row mb-4">
    <div class="col">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h2 mb-0">Modifier le PC</h1>
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
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Informations du PC</h5>
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
                 value="<?= e($currentModele) ?>" placeholder="Modèle personnalisé">
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
            <option value="__nouveau__">➕ Nouveau utilisateur</option>
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
          <select class="form-select" name="os_version">
            <option value="">Aucune</option>
            <?php foreach ($options['os_version'] as $version): ?>
              <?php $currentVersion = $_POST["os_version"] ?? $pc["os_version"]; ?>
              <option value="<?= e($version) ?>" <?= $currentVersion === $version ? "selected" : "" ?>>
                <?= e($version) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Ou saisir manuellement:</small>
          <input class="form-control form-control-sm mt-1" name="os_version_custom"
                 value="<?= e($currentVersion) ?>" placeholder="Version personnalisée">
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

        <div class="col-12">
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              Créé le: <?= e($pc["created_at"]) ?> | Modifié le: <?= e($pc["updated_at"]) ?>
            </small>
            <div class="d-grid gap-2 d-md-flex">
              <a href="pcs.php" class="btn btn-secondary">Annuler</a>
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
    this.removeAttribute('name');
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

  if (modeleCustom.value.trim() !== '') {
    modeleSelect.value = '';
    const hiddenModele = document.createElement('input');
    hiddenModele.type = 'hidden';
    hiddenModele.name = 'modele';
    hiddenModele.value = modeleCustom.value;
    this.appendChild(hiddenModele);
    modeleCustom.removeAttribute('name');
  } else if (modeleSelect.value && modeleSelect.value !== '__custom__') {
    modeleSelect.setAttribute('name', 'modele');
  }

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
