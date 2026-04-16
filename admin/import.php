<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";

$allowedArch   = PC_ARCH;
$allowedStatut = PC_STATUTS;
$requiredColumns = ["hostname", "serial", "marque", "utilisateur", "os", "architecture", "statut"];
$optionalColumns = ["modele", "domaine", "os_version", "remarques"];
$allColumns = array_merge($requiredColumns, $optionalColumns);

$imported     = 0;
$updated      = 0;
$optionsAdded = 0;
$errors       = [];


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
      $header = fgetcsv($handle, 8192, ",");
      if ($header === false) {
        $errors[] = "Le fichier CSV est vide.";
      } else {
        // Normalize header (strip BOM UTF-8 et UTF-16, trim + lowercase)
        $header = array_map(function ($col) {
          $col = preg_replace('/\x{FEFF}/u', '', $col);           // strip UTF-8 BOM
          $col = preg_replace('/^\xFF\xFE|^\xFE\xFF/', '', $col); // strip UTF-16 BOM
          return strtolower(trim($col));
        }, $header);

        // Check required columns exist
        $missingCols = array_diff($requiredColumns, $header);
        if ($missingCols) {
          $errors[] = "Colonnes manquantes : " . implode(", ", $missingCols);
        } else {
          $rowNum = 1; // header was row 1
          $toSync = ['marque' => [], 'modele' => [], 'os' => [], 'os_version' => []];

          try {
          $pdo->beginTransaction();

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

          while (($row = fgetcsv($handle, 8192, ",")) !== false) {
            $rowNum++;

            // Skip empty rows
            if (count($row) === 1 && trim($row[0]) === "") continue;

            // Avertir si la ligne a moins de colonnes que le header
            if (count($row) < count($header)) {
              $errors[] = "Ligne $rowNum : " . count($row) . " colonne(s) trouvée(s), " . count($header) . " attendue(s) — colonnes manquantes remplies avec vide";
            }

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
                if ($rc === 2)      $updated++;
                elseif ($rc === 1)  $imported++;
              } else {
                $imported++;
              }
              // Collecter les valeurs pour sync dans field_options
              $osGroup = deriveOsGroup($data['os']);
              if (!empty($data['marque']))     $toSync['marque'][$data['marque']] = null;
              if (!empty($data['modele']))     $toSync['modele'][$data['marque']][$data['modele']] = null;
              if (!empty($data['os']))         $toSync['os'][$osGroup][$data['os']] = null;
              if (!empty($data['os_version'])) $toSync['os_version'][$osGroup][$data['os_version']] = null;
            } catch (PDOException $e) {
              if (strval($e->getCode()) === "23000") {
                $errors[] = "Ligne $rowNum : serial « " . $data["serial"] . " » déjà existant (doublon ignoré)";
              } else {
                throw $e;
              }
            }
          }

          // Synchroniser les nouvelles valeurs dans field_options
          if ($imported > 0 || $updated > 0) {
            $candidates = [];
            foreach ($toSync['marque'] as $m => $_)         $candidates[] = ['marque',     null,         $m];
            foreach ($toSync['modele'] as $g => $modeles)   foreach ($modeles as $mo => $_) $candidates[] = ['modele', $g ?: null, $mo];
            foreach ($toSync['os'] as $g => $osVals)        foreach ($osVals as $os => $_)  $candidates[] = ['os',     $g,         $os];
            foreach ($toSync['os_version'] as $g => $vers)  foreach ($vers as $v => $_)     $candidates[] = ['os_version', $g ?: null, $v];

            $garbage = ['', 'n/a', 'to be filled by o.e.m.', 'system manufacturer',
                        'system product name', 'default string', 'none', 'not applicable',
                        'not specified', 'not available'];
            $candidates = array_values(array_filter($candidates,
              fn($c) => !in_array(strtolower(trim($c[2])), $garbage, true)
            ));

            if (!empty($candidates)) {
              $fns = array_values(array_unique(array_column($candidates, 0)));
              $ph  = sql_placeholders(count($fns));

              // 1 requête : charger les valeurs existantes + max display_order
              $rowsAll = $pdo->prepare(
                "SELECT field_name, option_group, option_value, display_order
                 FROM field_options WHERE field_name IN ($ph)"
              );
              $rowsAll->execute($fns);
              $existingSet = [];
              $maxOrders   = [];
              foreach ($rowsAll->fetchAll() as $r) {
                $existingSet[$r['field_name'] . '|' . ($r['option_group'] ?? '') . '|' . $r['option_value']] = true;
                $ordKey = $r['field_name'] . '|' . ($r['option_group'] ?? '');
                $maxOrders[$ordKey] = max($maxOrders[$ordKey] ?? 0, (int)$r['display_order']);
              }

              $stmtInsOpt = $pdo->prepare("INSERT INTO field_options (field_name, option_group, option_value, display_order) VALUES (?,?,?,?)");
              foreach ($candidates as [$fn, $grp, $val]) {
                $key    = $fn . '|' . ($grp ?? '') . '|' . $val;
                $ordKey = $fn . '|' . ($grp ?? '');
                if (!isset($existingSet[$key])) {
                  $maxOrders[$ordKey] = ($maxOrders[$ordKey] ?? 0) + 1;
                  $stmtInsOpt->execute([$fn, $grp, $val, $maxOrders[$ordKey]]);
                  $existingSet[$key] = true;
                  $optionsAdded++;
                }
              }
            }
          }

          if ($imported > 0 || $updated > 0) {
            $details = "$imported importe(s)";
            if ($updated > 0) $details .= ", $updated mis a jour";
            log_activity($pdo, 'import', 'pc', null, '', $details);
          }
          $pdo->commit();
          } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("CSV import failed: " . $e->getMessage());
            $errors[]     = "Erreur pendant l'import. Aucune donnée importée.";
            $imported     = 0;
            $updated      = 0;
            $optionsAdded = 0;
          }
        }
      }
      fclose($handle);
    }
  }
}


$pageTitle = "Admin - Import CSV";
$activePage = "admin_import";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Import CSV</h3>
  </div>

  <?php
  $flash = [];
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($imported > 0 || $updated > 0) {
      $parts = [];
      if ($imported > 0) $parts[] = "<strong>$imported</strong> PC importé" . ($imported > 1 ? "s" : "");
      if ($updated > 0)  $parts[] = "<strong>$updated</strong> PC mis à jour";
      $msg = implode(' — ', $parts) . ' avec succès.';
      if ($optionsAdded > 0) {
        $msg .= '<br><small>' . $optionsAdded . ' nouvelle' . ($optionsAdded > 1 ? 's' : '') . ' option' . ($optionsAdded > 1 ? 's' : '') . ' ajoutée' . ($optionsAdded > 1 ? 's' : '') . ' aux listes déroulantes.</small>';
      }
      $flash[] = ['type' => 'success', 'msg' => $msg];
    }
    foreach ($errors as $err) {
      $flash[] = ['type' => 'danger', 'msg' => e($err)];
    }
  }
  require __DIR__ . "/../partials/flash.php";
  ?>

  <!-- Scripts de collecte -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h6 class="mb-0"><i class="bi bi-download"></i> Scripts de collecte automatique</h6>
    </div>
    <div class="card-body">
      <p class="text-muted mb-3">
        Exécutez un script sur chaque machine à inventorier, récupérez le <code>inventaire.csv</code> généré, puis importez-le ci-dessous.
      </p>

      <!-- Tabs OS -->
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-windows" type="button" role="tab">
            <i class="bi bi-windows"></i> Windows
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-linux" type="button" role="tab">
            <i class="bi bi-terminal"></i> Linux
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-windows" role="tabpanel">
          <p class="mb-3">
            Téléchargez les deux fichiers dans le <strong>même dossier</strong> (ex : clé USB), puis double-cliquez sur <code>lancer_collecte.bat</code>.
            <br><small class="text-muted">Droits administrateur requis.</small>
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a href="/admin/download_script.php?script=bat" class="btn btn-outline-primary">
              <i class="bi bi-download"></i> lancer_collecte.bat
            </a>
            <a href="/admin/download_script.php?script=windows" class="btn btn-outline-secondary">
              <i class="bi bi-download"></i> collect_windows.ps1
            </a>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-linux" role="tabpanel">
          <p class="mb-3">
            Un seul script universel. Installez <code>dmidecode</code> si absent, puis exécutez avec <code>sudo</code>.
            <br><small class="text-muted">Droits sudo requis pour lire le numéro de série matériel.</small>
          </p>

          <a href="/admin/download_script.php?script=linux" class="btn btn-outline-primary mb-3">
            <i class="bi bi-download"></i> collect_linux.sh
          </a>

          <div class="row g-2">
            <?php foreach ([
              ['icon' => 'bi-ubuntu', 'label' => 'Ubuntu / Debian / Mint',  'cmd' => 'sudo apt install -y dmidecode'],
              ['icon' => 'bi-linux',  'label' => 'Fedora / RHEL / CentOS',  'cmd' => 'sudo dnf install -y dmidecode'],
              ['icon' => 'bi-linux',  'label' => 'openSUSE',                'cmd' => 'sudo zypper install -y dmidecode'],
              ['icon' => 'bi-linux',  'label' => 'Arch Linux / Manjaro',    'cmd' => 'sudo pacman -S --noconfirm dmidecode'],
            ] as $d): ?>
            <div class="col-md-6">
              <div class="rounded border p-2 d-flex align-items-start gap-2">
                <i class="bi <?= $d['icon'] ?> text-secondary mt-1"></i>
                <div class="flex-fill">
                  <div class="small fw-semibold"><?= $d['label'] ?></div>
                  <code class="small text-muted"><?= $d['cmd'] ?></code>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-3 text-muted small">
            Puis : <code>sudo bash collect_linux.sh</code>
          </div>
        </div>
      </div>

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
          <div id="drop-zone" class="border rounded p-4 text-center mb-2"
               style="border-style: dashed !important; cursor: pointer; transition: border-color .15s, color .15s;">
            <i class="bi bi-cloud-upload fs-2 text-secondary"></i>
            <p class="mt-2 mb-1 text-secondary">Glissez votre fichier CSV ici</p>
            <span class="text-muted small">ou cliquez pour parcourir</span>
          </div>
          <div id="file-info" class="d-none alert alert-secondary py-2 mb-2">
            <i class="bi bi-file-earmark-spreadsheet"></i> <span id="file-name"></span>
          </div>
          <input type="file" id="csv-input" name="csv_file" accept=".csv" required class="d-none">
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
$pageScripts = '
<script>
(function() {
  const zone    = document.getElementById("drop-zone");
  const input   = document.getElementById("csv-input");
  const info    = document.getElementById("file-info");
  const nameEl  = document.getElementById("file-name");

  let dragCounter = 0;

  function showFile(file) {
    if (!file || !file.name.toLowerCase().endsWith(".csv")) {
      alert("Veuillez sélectionner un fichier CSV (.csv).");
      return;
    }
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;

    nameEl.textContent = file.name + " (" + (file.size / 1024).toFixed(1) + " Ko)";
    info.classList.remove("d-none");
    zone.classList.remove("border-primary");
    zone.classList.add("border-success");
  }

  zone.addEventListener("click", () => input.click());

  zone.addEventListener("dragenter", e => {
    e.preventDefault();
    if (++dragCounter === 1) zone.classList.add("border-primary");
  });

  zone.addEventListener("dragleave", () => {
    if (--dragCounter === 0) zone.classList.remove("border-primary");
  });

  zone.addEventListener("dragover", e => e.preventDefault());

  zone.addEventListener("drop", e => {
    e.preventDefault();
    dragCounter = 0;
    showFile(e.dataTransfer.files[0]);
  });

  input.addEventListener("change", () => {
    if (input.files[0]) showFile(input.files[0]);
  });
})();
</script>
';
require __DIR__ . "/../partials/footer.php";
