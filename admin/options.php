<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";


// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $field_name   = $_POST["field_name"]   ?? "";
        $option_group = trim($_POST["option_group"] ?? "") ?: null;
        $option_value = trim($_POST["option_value"] ?? "");

        $allowed = ["marque", "modele", "os", "os_version"];
        if (in_array($field_name, $allowed, true) && $option_value !== "") {
            $max = $pdo->prepare(
                "SELECT IFNULL(MAX(display_order), 0) + 1
                 FROM field_options
                 WHERE field_name = ? AND (option_group <=> ?)"
            );
            $max->execute([$field_name, $option_group]);
            $order = (int)$max->fetchColumn();

            $stmt = $pdo->prepare(
                "INSERT INTO field_options (field_name, option_group, option_value, display_order)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$field_name, $option_group, $option_value, $order]);
        }
        header("Location: /admin/options.php?tab=" . urlencode($field_name) . "&msg=added");
        exit;
    }

    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        $field_name = $_POST["field_name"] ?? "";
        if ($id > 0) {
            $pdo->prepare("DELETE FROM field_options WHERE id = ?")->execute([$id]);
        }
        header("Location: /admin/options.php?tab=" . urlencode($field_name) . "&msg=deleted");
        exit;
    }

    if ($action === "dedupe") {
        $field_name = $_POST["field_name"] ?? "";
        $allowed = ["marque", "modele", "os", "os_version"];
        if (in_array($field_name, $allowed, true)) {
            $pdo->prepare(
                "DELETE fo1 FROM field_options fo1
                 INNER JOIN field_options fo2
                 ON  fo1.field_name    = fo2.field_name
                 AND fo1.option_group <=> fo2.option_group
                 AND fo1.option_value  = fo2.option_value
                 AND fo1.id > fo2.id
                 WHERE fo1.field_name = ?"
            )->execute([$field_name]);
        }
        header("Location: /admin/options.php?tab=" . urlencode($field_name) . "&msg=deduped");
        exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$tab = $_GET["tab"] ?? "marque";
$allowed_tabs = ["marque", "modele", "os", "os_version"];
if (!in_array($tab, $allowed_tabs, true)) $tab = "marque";

$orderBy = $tab === 'os_version'
    ? "ISNULL(option_group), option_group, option_value"
    : "ISNULL(option_group), option_group, display_order, option_value";

$rows = $pdo->prepare(
    "SELECT id, option_group, option_value, display_order
     FROM field_options
     WHERE field_name = ?
     ORDER BY $orderBy"
);
$rows->execute([$tab]);
$options = $rows->fetchAll();

$marqueRows = $tab === 'modele' ? $pdo->query(
    "SELECT DISTINCT option_value FROM field_options WHERE field_name = 'marque' ORDER BY option_value"
)->fetchAll(PDO::FETCH_COLUMN) : [];

$osGroups = $tab === 'os' ? $pdo->query(
    "SELECT DISTINCT option_group FROM field_options WHERE field_name = 'os' AND option_group IS NOT NULL ORDER BY option_group"
)->fetchAll(PDO::FETCH_COLUMN) : [];

$pageTitle = "Admin - Options des listes";
$activePage = "admin_options";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Options des listes déroulantes</h3>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= match($_GET['msg']) {
        'added'   => 'Option ajoutée.',
        'deleted' => 'Option supprimée.',
        'deduped' => 'Doublons supprimés.',
        default   => '',
      } ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4">
    <?php
    $tabs = [
      "marque"     => "Marques",
      "modele"     => "Modèles",
      "os"         => "Systèmes d'exploitation",
      "os_version" => "Versions OS",
    ];
    foreach ($tabs as $key => $label):
    ?>
      <li class="nav-item">
        <a class="nav-link <?= $tab === $key ? 'active' : '' ?>"
           href="/admin/options.php?tab=<?= e($key) ?>">
          <?= e($label) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="row g-4">

    <!-- ── Left: existing values ─────────────────────────────────────────── -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><?= e($tabs[$tab]) ?></h6>
          <form method="post" class="d-inline"
                onsubmit="return confirm('Supprimer tous les doublons pour cet onglet ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action"     value="dedupe">
            <input type="hidden" name="field_name" value="<?= e($tab) ?>">
            <button class="btn btn-sm btn-outline-warning" type="submit">
              <i class="bi bi-scissors"></i> Supprimer les doublons
            </button>
          </form>
        </div>
        <div class="card-body p-0">

          <?php if (empty($options)): ?>
            <p class="text-muted p-3 mb-0">Aucune option. Ajoutez-en ci-contre.</p>
          <?php else: ?>

            <?php
            $grouped = [];
            foreach ($options as $row) {
                $g = $row['option_group'] ?? '';
                $grouped[$g][] = $row;
            }
            ?>

            <?php foreach ($grouped as $groupName => $items): ?>

              <?php if ($groupName !== ''): ?>
                <div class="px-3 pt-3">
                  <h6 class="text-muted mb-2">
                    <i class="bi bi-folder2-open"></i> <?= e($groupName) ?>
                  </h6>
                </div>
              <?php endif; ?>

              <ul class="list-group list-group-flush">
                <?php foreach ($items as $item): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= e($item['option_value']) ?></span>
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Supprimer cette option ?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action"     value="delete">
                      <input type="hidden" name="id"         value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="field_name" value="<?= e($tab) ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>

            <?php endforeach; ?>

          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ── Right: add form ───────────────────────────────────────────────── -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0">Ajouter une option</h6>
        </div>
        <div class="card-body">
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action"     value="add">
            <input type="hidden" name="field_name" value="<?= e($tab) ?>">

            <?php if ($tab === "modele"): ?>
              <div class="mb-3">
                <label class="form-label">Marque (groupe) <span class="text-danger">*</span></label>
                <select class="form-select" name="option_group" required>
                  <option value="">Sélectionner une marque...</option>
                  <?php foreach ($marqueRows as $m): ?>
                    <option value="<?= e($m) ?>"><?= e($m) ?></option>
                  <?php endforeach; ?>
                  <option value="__new__">+ Nouvelle marque...</option>
                </select>
                <input class="form-control mt-2" id="new_group_input" name="option_group_new"
                       placeholder="Nom de la nouvelle marque" style="display:none;">
              </div>

            <?php elseif ($tab === "os"): ?>
              <div class="mb-3">
                <label class="form-label">Famille OS (groupe) <span class="text-danger">*</span></label>
                <select class="form-select" name="option_group" id="os_group_select" required>
                  <option value="">Sélectionner une famille...</option>
                  <?php foreach ($osGroups as $g): ?>
                    <option value="<?= e($g) ?>"><?= e($g) ?></option>
                  <?php endforeach; ?>
                  <option value="__new__">+ Nouvelle famille...</option>
                </select>
                <input class="form-control mt-2" id="new_os_group_input" name="option_group_new"
                       placeholder="Nom de la nouvelle famille" style="display:none;">
              </div>

            <?php elseif ($tab === "os_version"): ?>
              <div class="mb-3">
                <label class="form-label">Famille OS (groupe)</label>
                <select class="form-select" name="option_group">
                  <option value="">Aucune (générique)</option>
                  <?php foreach (OS_FAMILIES as $f): ?>
                    <option value="<?= e($f) ?>"><?= e($f) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">
                <?php
                echo match($tab) {
                  'marque'     => 'Nom de la marque',
                  'modele'     => 'Nom du modèle',
                  'os'         => "Nom de l'OS",
                  'os_version' => 'Version',
                };
                ?>
                <span class="text-danger">*</span>
              </label>
              <input class="form-control" name="option_value" required
                     placeholder="<?= match($tab) {
                       'marque'     => 'ex: Asus',
                       'modele'     => 'ex: ThinkPad X1 Gen 10',
                       'os'         => 'ex: Windows 12',
                       'os_version' => 'ex: 24H2',
                     } ?>">
            </div>

            <button class="btn btn-success w-100" type="submit">
              <i class="bi bi-plus-circle"></i> Ajouter
            </button>
          </form>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</div>

<?php
$pageScripts = <<<'JS'
<script>
// Modele tab: toggle new brand input
const modeleGroupSelect = document.querySelector('select[name="option_group"]');
if (modeleGroupSelect) {
  modeleGroupSelect.addEventListener('change', function () {
    const newInput = document.getElementById('new_group_input') ||
                     document.getElementById('new_os_group_input');
    if (!newInput) return;
    if (this.value === '__new__') {
      newInput.style.display = 'block';
      newInput.required = true;
      this.removeAttribute('name');
      newInput.name = 'option_group';
    } else {
      newInput.style.display = 'none';
      newInput.required = false;
      this.name = 'option_group';
      newInput.name = 'option_group_new';
    }
  });
}
</script>
JS;
require __DIR__ . "/../partials/footer.php";
