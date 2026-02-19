<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";

$allowedArch = ["x86", "x64", "arm64"];
$allowedStatut = ["En service", "En stock", "En réparation", "Retiré"];
$requiredColumns = ["hostname", "serial", "marque", "utilisateur", "os", "architecture", "statut"];
$optionalColumns = ["modele", "domaine", "os_version", "remarques"];
$allColumns = array_merge($requiredColumns, $optionalColumns);

$imported = 0;
$updated  = 0;
$errors   = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();

  $updateExisting = !empty($_POST["update_existing"]);

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
        // Normalize header (strip BOM + trim + lowercase)
        $header = array_map(function ($col) {
          $col = preg_replace('/\x{FEFF}/u', '', $col); // strip UTF-8 BOM
          return strtolower(trim($col));
        }, $header);

        // Check required columns exist
        $missingCols = array_diff($requiredColumns, $header);
        if ($missingCols) {
          $errors[] = "Colonnes manquantes : " . implode(", ", $missingCols);
        } else {
          $rowNum = 1; // header was row 1

          if ($updateExisting) {
            $stmt = $pdo->prepare("
              INSERT INTO pcs (hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, remarques)
              VALUES (:hostname, :serial, :marque, :modele, :utilisateur, :os, :os_version, :architecture, :domaine, :statut, :remarques)
              ON DUPLICATE KEY UPDATE
                hostname     = VALUES(hostname),
                marque       = VALUES(marque),
                modele       = VALUES(modele),
                utilisateur  = VALUES(utilisateur),
                os           = VALUES(os),
                os_version   = VALUES(os_version),
                architecture = VALUES(architecture),
                domaine      = VALUES(domaine),
                statut       = VALUES(statut),
                remarques    = VALUES(remarques)
            ");
          } else {
            $stmt = $pdo->prepare("
              INSERT INTO pcs (hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, remarques)
              VALUES (:hostname, :serial, :marque, :modele, :utilisateur, :os, :os_version, :architecture, :domaine, :statut, :remarques)
            ");
          }

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

            // Serial manquant : auto-generer depuis le hostname
            if (empty($data["serial"]) && !empty($data["hostname"])) {
              $data["serial"] = "NOSERIAL-" . $data["hostname"];
            }

            // Validate required fields (ignorer les champs vides, juste skipper la ligne si hostname absent)
            $rowErrors = [];
            if (empty($data["hostname"])) {
              $rowErrors[] = "champ « hostname » vide";
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

            // Insert / upsert
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
              if ($updateExisting) {
                $rc = $stmt->rowCount();
                if ($rc === 2)      $updated++;   // ligne mise à jour
                elseif ($rc === 1)  $imported++;  // nouvelle ligne
                // rc === 0 : aucun changement, ignoré
              } else {
                $imported++;
              }
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
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Import CSV</h3>
  </div>

  <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
    <?php if ($imported > 0 || $updated > 0): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i>
        <?php if ($imported > 0): ?>
          <strong><?= $imported ?></strong> PC importé<?= $imported > 1 ? "s" : "" ?>
        <?php endif; ?>
        <?php if ($imported > 0 && $updated > 0): ?> — <?php endif; ?>
        <?php if ($updated > 0): ?>
          <strong><?= $updated ?></strong> PC mis à jour
        <?php endif; ?>
        avec succès.
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

  <!-- Workflow USB -->
  <div class="card shadow-sm mb-4 border-info">
    <div class="card-header bg-info bg-opacity-10">
      <h6 class="mb-0"><i class="bi bi-usb-drive"></i> Collecte via cle USB</h6>
    </div>
    <div class="card-body">
      <p class="mb-2">Des scripts de collecte automatique sont fournis dans le dossier <code>scripts/</code> du projet :</p>
      <ol class="mb-0">
        <li>Copier <code>collect_windows.ps1</code> et/ou <code>collect_linux.sh</code> sur une cle USB</li>
        <li>Executer le script sur chaque machine a inventorier (admin/sudo requis)</li>
        <li>Recuperer le fichier <code>inventaire.csv</code> genere sur la cle USB</li>
        <li>Importer le CSV ci-dessous</li>
      </ol>
    </div>
  </div>

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
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" name="update_existing" id="update_existing" value="1">
          <label class="form-check-label" for="update_existing">
            Mettre à jour les PC existants (par numéro de série)
          </label>
          <div class="form-text text-warning">
            <i class="bi bi-exclamation-triangle"></i> Si coché, les données des PC déjà présents seront écrasées par celles du CSV.
          </div>
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
      <li>Si le numéro de série est absent, il sera auto-généré à partir du hostname (<code>NOSERIAL-hostname</code>).</li>
      <li>Par défaut, les numéros de série en doublon sont ignorés. Cochez "Mettre à jour" pour écraser les données existantes.</li>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>

<?php
require __DIR__ . "/../partials/footer.php";
