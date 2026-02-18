<?php
require __DIR__ . "/auth.php";
require_perm("is_admin");
require __DIR__ . "/db.php";

$allowedArch = ["x86", "x64", "arm64"];
$allowedStatut = ["En service", "En stock", "En réparation", "Retiré"];
$requiredColumns = ["hostname", "serial", "marque", "utilisateur", "os", "architecture", "statut"];
$optionalColumns = ["modele", "domaine", "os_version", "remarques"];
$allColumns = array_merge($requiredColumns, $optionalColumns);

$imported = 0;
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();

  if (empty($_FILES["csv_file"]["tmp_name"]) || $_FILES["csv_file"]["error"] !== UPLOAD_ERR_OK) {
    $errors[] = "Veuillez sélectionner un fichier CSV valide.";
  } else {
    $handle = fopen($_FILES["csv_file"]["tmp_name"], "r");
    if ($handle === false) {
      $errors[] = "Impossible de lire le fichier.";
    } else {
      // Read header row
      $header = fgetcsv($handle, 0, ",");
      if ($header === false) {
        $errors[] = "Le fichier CSV est vide.";
      } else {
        // Normalize header (trim + lowercase)
        $header = array_map(function ($col) {
          return strtolower(trim($col));
        }, $header);

        // Check required columns exist
        $missingCols = array_diff($requiredColumns, $header);
        if ($missingCols) {
          $errors[] = "Colonnes manquantes : " . implode(", ", $missingCols);
        } else {
          $rowNum = 1; // header was row 1
          $stmt = $pdo->prepare("
            INSERT INTO pcs (hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, remarques)
            VALUES (:hostname, :serial, :marque, :modele, :utilisateur, :os, :os_version, :architecture, :domaine, :statut, :remarques)
          ");

          while (($row = fgetcsv($handle, 0, ",")) !== false) {
            $rowNum++;

            // Skip empty rows
            if (count($row) === 1 && trim($row[0]) === "") continue;

            // Map columns by header position
            $data = [];
            foreach ($header as $i => $col) {
              if (in_array($col, $allColumns)) {
                $data[$col] = trim($row[$i] ?? "");
              }
            }

            // Validate required fields
            $rowErrors = [];
            foreach ($requiredColumns as $col) {
              if (empty($data[$col])) {
                $rowErrors[] = "champ « $col » vide";
              }
            }

            // Validate enum values
            if (!empty($data["architecture"]) && !in_array($data["architecture"], $allowedArch, true)) {
              $rowErrors[] = "architecture « " . $data["architecture"] . " » invalide (x86, x64, arm64)";
            }
            if (!empty($data["statut"]) && !in_array($data["statut"], $allowedStatut, true)) {
              $rowErrors[] = "statut « " . $data["statut"] . " » invalide";
            }

            if ($rowErrors) {
              $errors[] = "Ligne $rowNum : " . implode("; ", $rowErrors);
              continue;
            }

            // Insert
            try {
              $stmt->execute([
                ":hostname"     => $data["hostname"],
                ":serial"       => $data["serial"],
                ":marque"       => $data["marque"],
                ":modele"       => $data["modele"] ?? "",
                ":utilisateur"  => $data["utilisateur"],
                ":os"           => $data["os"],
                ":os_version"   => $data["os_version"] ?? "",
                ":architecture" => $data["architecture"],
                ":domaine"      => $data["domaine"] ?? "",
                ":statut"       => $data["statut"],
                ":remarques"    => $data["remarques"] ?? "",
              ]);
              $imported++;
            } catch (PDOException $e) {
              if ($e->getCode() === "23000") {
                $errors[] = "Ligne $rowNum : serial « " . $data["serial"] . " » déjà existant (doublon ignoré)";
              } else {
                throw $e;
              }
            }
          }
        }
      }
      fclose($handle);
    }
  }
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$pageTitle = "Admin - Import CSV";
$activePage = "admin_import";
require __DIR__ . "/partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Import CSV</h3>
  </div>

  <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
    <?php if ($imported > 0): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <strong><?= $imported ?></strong> PC importé<?= $imported > 1 ? "s" : "" ?> avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> <?= count($errors) ?> erreur<?= count($errors) > 1 ? "s" : "" ?></h6>
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Upload form -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h6 class="mb-0">Importer des PC depuis un fichier CSV</h6>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Fichier CSV <span class="text-danger">*</span></label>
          <input type="file" class="form-control" name="csv_file" accept=".csv" required>
          <small class="text-muted">Encodage UTF-8 recommandé, séparateur virgule.</small>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-upload"></i> Importer
        </button>
      </form>
    </div>
  </div>

  <!-- Template download -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h6 class="mb-0">Modèle CSV</h6>
    </div>
    <div class="card-body">
      <p>Téléchargez le modèle CSV avec les colonnes attendues :</p>
      <a href="data:text/csv;charset=utf-8,hostname,serial,marque,utilisateur,os,architecture,statut,modele,domaine,os_version,remarques" download="template_import_pc.csv" class="btn btn-outline-secondary">
        <i class="bi bi-download"></i> Télécharger le modèle
      </a>
    </div>
  </div>

  <!-- Info -->
  <div class="alert alert-info alert-dismissible fade show">
    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Format attendu</h6>
    <ul class="mb-0">
      <li><strong>Colonnes obligatoires :</strong> hostname, serial, marque, utilisateur, os, architecture, statut</li>
      <li><strong>Colonnes optionnelles :</strong> modele, domaine, os_version, remarques</li>
      <li><strong>Architecture :</strong> x86, x64 ou arm64</li>
      <li><strong>Statut :</strong> En service, En stock, En réparation, Retiré</li>
      <li>Les numéros de série en doublon seront ignorés avec un message d'erreur.</li>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>

<?php
require __DIR__ . "/partials/footer.php";
