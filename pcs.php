<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

$q = trim($_GET["q"] ?? "");
$statut = $_GET["statut"] ?? "";
$arch = $_GET["arch"] ?? "";
$marque = $_GET["marque"] ?? "";

$allowedSort = [
  'hostname'     => 'hostname',
  'serial'       => 'serial',
  'marque'       => 'marque',
  'modele'       => 'modele',
  'utilisateur'  => 'utilisateur',
  'os'           => 'os',
  'os_version'   => 'os_version',
  'architecture' => 'architecture',
  'domaine'      => 'domaine',
  'statut'       => 'statut',
  'updated_at'   => 'updated_at',
];
$sort = isset($allowedSort[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'updated_at';
$dir  = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$allowedStatut = array_merge([''], PC_STATUTS);
$allowedArch   = array_merge([''], PC_ARCH);
$brands = $pdo->query("SELECT DISTINCT marque FROM pcs ORDER BY marque")
  ->fetchAll(PDO::FETCH_COLUMN);

$where  = "WHERE 1=1 ";
$params = [];

if ($q !== "") {
  $where .= "AND (
    hostname LIKE :q1 OR serial LIKE :q2 OR marque LIKE :q3 OR modele LIKE :q4 OR utilisateur LIKE :q5
    OR os LIKE :q6 OR os_version LIKE :q7 OR domaine LIKE :q8
  ) ";
  $qVal = "%{$q}%";
  $params[":q1"] = $qVal;
  $params[":q2"] = $qVal;
  $params[":q3"] = $qVal;
  $params[":q4"] = $qVal;
  $params[":q5"] = $qVal;
  $params[":q6"] = $qVal;
  $params[":q7"] = $qVal;
  $params[":q8"] = $qVal;
}

if (in_array($statut, $allowedStatut, true) && $statut !== "") {
  $where .= "AND statut = :statut ";
  $params[":statut"] = $statut;
}

if (in_array($arch, $allowedArch, true) && $arch !== "") {
  $where .= "AND architecture = :arch ";
  $params[":arch"] = $arch;
}

if ($marque !== "") {
  $where .= "AND marque = :marque ";
  $params[":marque"] = $marque;
}

$perPage = 50;
$page    = max(1, (int)($_GET["page"] ?? 1));

// Compter le total pour la pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pcs $where");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
$page       = min($page, max(1, $totalPages));

$sortCol = $allowedSort[$sort];
$sql = "SELECT id, hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, updated_at
        FROM pcs $where ORDER BY $sortCol $dir LIMIT :perPage OFFSET :offset";
$params[":perPage"] = $perPage;
$params[":offset"]  = ($page - 1) * $perPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pcs = $stmt->fetchAll();

$can_delete = !empty($_SESSION['can_delete']);

$pageTitle = "Inventaire PC";
$activePage = "pcs";
require __DIR__ . "/partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Inventaire PC</h3>
    <?php if (!empty($_SESSION["can_add"])): ?>
    <a class="btn btn-primary" href="/pc_add.php">
      <i class="bi bi-plus-circle"></i> Ajouter PC
    </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle"></i>
    <?php $nDel = max(1, (int)($_GET['n'] ?? 1)); ?>
    <strong><?= $nDel ?></strong> PC<?= $nDel > 1 ? 's supprimés' : ' supprimé' ?>.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'delete_error'): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle"></i> Erreur lors de la suppression.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-4">
          <input class="form-control" name="q" value="<?= e($q) ?>"
                 placeholder="Recherche (hostname, serial, user, OS...)">
        </div>
        <div class="col-md-2">
          <select class="form-select" name="statut">
            <?php foreach ($allowedStatut as $s): ?>
              <option value="<?= e($s) ?>" <?= $statut === $s ? "selected" : "" ?>>
                <?= $s === "" ? "Tous statuts" : e($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="arch">
            <?php foreach ($allowedArch as $a): ?>
              <option value="<?= e($a) ?>" <?= $arch === $a ? "selected" : "" ?>>
                <?= $a === "" ? "Toute archi" : e($a) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="marque">
            <option value="">Toutes marques</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= e($b) ?>" <?= $marque === $b ? "selected" : "" ?>>
                <?= e($b) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <button class="btn btn-primary w-100" type="submit">Filtrer</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($can_delete)): ?>
  <form method="post" action="/pc_delete_bulk.php" id="bulkForm">
    <?= csrf_field() ?>
  <?php endif; ?>
  <div class="card shadow-sm">
    <?php if (!empty($can_delete)): ?>
    <div class="card-header d-flex justify-content-end align-items-center gap-2 py-2">
      <button type="button" id="bulkClear" class="btn btn-sm btn-outline-secondary" style="display:none">
        <i class="bi bi-x-circle"></i> Tout désélectionner
      </button>
      <button type="submit" id="bulkDelBtn" class="btn btn-sm btn-danger" disabled>
        <i class="bi bi-trash"></i> Supprimer la sélection<span id="bulkCount"></span>
      </button>
    </div>
    <?php endif; ?>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <?php
            function sortLink(string $col, string $label, string $currentSort, string $currentDir, array $get): string {
              $nextDir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
              $params  = array_merge($get, ['sort' => $col, 'dir' => $nextDir, 'page' => 1]);
              $url     = '?' . http_build_query($params);
              $icon    = '';
              if ($currentSort === $col) {
                $icon = $currentDir === 'asc' ? ' ▲' : ' ▼';
              }
              return '<a href="' . e($url) . '" class="text-decoration-none text-reset fw-bold">'
                   . e($label) . '<span class="text-primary">' . $icon . '</span></a>';
            }
          ?>
          <thead>
            <tr>
              <?php if (!empty($can_delete)): ?>
              <th class="ps-3" style="width:2.5rem">
                <input type="checkbox" class="form-check-input" id="selectAll" title="Tout sélectionner">
              </th>
              <?php endif; ?>
              <th><?= sortLink('hostname',     'Hostname',    $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('serial',       'Serial',      $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('marque',       'Marque',      $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('modele',       'Modèle',      $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('utilisateur',  'Utilisateur', $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('os',           'OS',          $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('os_version',   'Version',     $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('architecture', 'Arch',        $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('domaine',      'Domaine',     $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('statut',       'Statut',      $sort, $dir, $_GET) ?></th>
              <th><?= sortLink('updated_at',   'Mise à jour', $sort, $dir, $_GET) ?></th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pcs as $pc): ?>
            <tr>
              <?php if (!empty($can_delete)): ?>
              <td class="ps-3">
                <input type="checkbox" class="form-check-input row-check" name="ids[]" value="<?= (int)$pc['id'] ?>">
              </td>
              <?php endif; ?>
              <td><a href="/pc_view.php?id=<?= (int)$pc['id'] ?>" class="text-decoration-none text-reset"><strong><?= e($pc["hostname"]) ?></strong></a></td>
              <td><code class="text-info"><?= e($pc["serial"]) ?></code></td>
              <td><?= e($pc["marque"]) ?></td>
              <td><?= e($pc["modele"] ?? "-") ?></td>
              <td><?= e($pc["utilisateur"]) ?></td>
              <td><?= e($pc["os"]) ?></td>
              <td><?= e($pc["os_version"] ?? "-") ?></td>
              <td><span class="badge bg-secondary"><?= e($pc["architecture"]) ?></span></td>
              <td><?= e($pc["domaine"] ?? "-") ?></td>
              <td>
                <span class="badge bg-<?= statut_badge_class($pc["statut"]) ?>"><?= e($pc["statut"]) ?></span>
              </td>
              <td><small class="text-muted"><?= e($pc["updated_at"]) ?></small></td>
              <td>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                          data-bs-toggle="dropdown" data-bs-auto-close="true"
                          data-bs-boundary="viewport" aria-expanded="false">
                    Actions
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="/pc_view.php?id=<?= (int)$pc["id"] ?>">
                        <i class="bi bi-eye"></i> Voir
                      </a>
                    </li>
                    <?php if (!empty($_SESSION["can_edit"])): ?>
                    <li>
                      <a class="dropdown-item" href="/pc_edit.php?id=<?= (int)$pc["id"] ?>">
                        <i class="bi bi-pencil"></i> Modifier
                      </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($can_delete)): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" action="/pc_delete.php" class="d-inline"
                            onsubmit="return confirm('Supprimer ce PC ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$pc["id"] ?>">
                        <button class="dropdown-item text-danger" type="submit">
                          <i class="bi bi-trash"></i> Supprimer
                        </button>
                      </form>
                    </li>
                    <?php endif; ?>
                  </ul>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php if (!empty($can_delete)): ?></form><?php endif; ?>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">«</a>
      </li>
      <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
      </li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">»</a>
      </li>
    </ul>
    <p class="text-center text-muted small">
      <?= $total ?> PC au total — page <?= $page ?> / <?= $totalPages ?>
    </p>
  </nav>
  <?php endif; ?>
</div>

<?php
$pageScripts = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('bulkForm');
  const selAll    = document.getElementById('selectAll');
  const delBtn    = document.getElementById('bulkDelBtn');
  const countSpan = document.getElementById('bulkCount');
  const clearBtn  = document.getElementById('bulkClear');
  if (!form || !selAll || !delBtn) return;

  const STORAGE_KEY = 'pc_selection';
  const boxes = [...document.querySelectorAll('.row-check')];

  // Charger la sélection persistée (survit aux changements de page)
  let selected;
  try { selected = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')); }
  catch { selected = new Set(); }

  // Nettoyer la sélection après une suppression réussie
  if (new URLSearchParams(location.search).get('msg') === 'deleted') {
    selected.clear();
    localStorage.removeItem(STORAGE_KEY);
  }

  function save() {
    selected.size > 0
      ? localStorage.setItem(STORAGE_KEY, JSON.stringify([...selected]))
      : localStorage.removeItem(STORAGE_KEY);
  }

  function updateSelectAll() {
    const n = boxes.filter(c => c.checked).length;
    selAll.checked       = boxes.length > 0 && n === boxes.length;
    selAll.indeterminate = n > 0 && n < boxes.length
                        || (n === 0 && selected.size > 0); // sélection hors-page
  }

  function updateBtn() {
    const n = selected.size;
    delBtn.disabled       = n === 0;
    countSpan.textContent = n > 0 ? ` (${n})` : '';
    if (clearBtn) clearBtn.style.display = n > 0 ? '' : 'none';
  }

  // Restaurer les cases cochées de la page courante
  boxes.forEach(cb => { if (selected.has(cb.value)) cb.checked = true; });
  updateSelectAll();
  updateBtn();

  selAll.addEventListener('change', () => {
    boxes.forEach(cb => {
      cb.checked = selAll.checked;
      selAll.checked ? selected.add(cb.value) : selected.delete(cb.value);
    });
    save(); updateSelectAll(); updateBtn();
  });

  boxes.forEach(cb => cb.addEventListener('change', () => {
    cb.checked ? selected.add(cb.value) : selected.delete(cb.value);
    save(); updateSelectAll(); updateBtn();
  }));

  if (clearBtn) clearBtn.addEventListener('click', () => {
    selected.clear();
    boxes.forEach(cb => { cb.checked = false; });
    save(); updateSelectAll(); updateBtn();
  });

  // Vider la sélection si les filtres changent (évite de supprimer des IDs hors filtre)
  document.querySelector('form[method="get"]')?.addEventListener('submit', () => {
    selected.clear();
    localStorage.removeItem(STORAGE_KEY);
  });

  form.addEventListener('submit', e => {
    const n = selected.size;
    if (n === 0) { e.preventDefault(); return; }
    if (!confirm(`Supprimer ${n} PC${n > 1 ? 's' : ''} ? Cette action est irréversible.`)) {
      e.preventDefault(); return;
    }
    // Injecter les IDs hors-page (non présents comme checkboxes dans le DOM)
    const pageIds = new Set(boxes.map(cb => cb.value));
    selected.forEach(id => {
      if (!pageIds.has(id)) {
        const inp = Object.assign(document.createElement('input'), { type: 'hidden', name: 'ids[]', value: id });
        form.appendChild(inp);
      }
    });
  });
});
</script>
JS;
require __DIR__ . "/partials/footer.php";
