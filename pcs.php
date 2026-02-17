<?php
require __DIR__ . "/auth.php";
require_perm("can_view");
require __DIR__ . "/db.php";

$q = trim($_GET["q"] ?? "");
$statut = $_GET["statut"] ?? "";
$arch = $_GET["arch"] ?? "";
$marque = $_GET["marque"] ?? "";

$allowedStatut = ["", "En service", "En stock", "En réparation", "Retiré"];
$allowedArch = ["", "x86", "x64", "arm64"];
$brands = $pdo->query("SELECT DISTINCT marque FROM pcs ORDER BY marque")
  ->fetchAll(PDO::FETCH_COLUMN);
$sql = "SELECT id, hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, updated_at
        FROM pcs
        WHERE 1=1 ";
$params = [];

if ($q !== "") {
  $sql .= "AND (
    hostname LIKE :q OR serial LIKE :q OR marque LIKE :q OR modele LIKE :q OR utilisateur LIKE :q
    OR os LIKE :q OR os_version LIKE :q OR domaine LIKE :q
  ) ";
  $params[":q"] = "%{$q}%";
}

if (in_array($statut, $allowedStatut, true) && $statut !== "") {
  $sql .= "AND statut = :statut ";
  $params[":statut"] = $statut;
}

if (in_array($arch, $allowedArch, true) && $arch !== "") {
  $sql .= "AND architecture = :arch ";
  $params[":arch"] = $arch;
}

if ($marque !== "") {
  $sql .= "AND marque = :marque ";
  $params[":marque"] = $marque;
}

$sql .= "ORDER BY updated_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pcs = $stmt->fetchAll();

$pageTitle = "Inventaire PC";
require __DIR__ . "/partials/header.php";
?>

<!-- Mobile Navigation -->
<nav class="mobile-nav">
  <div class="mobile-nav-content">
    <h1 class="mobile-nav-title">Inventaire PC</h1>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
      <i class="bi bi-list"></i>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a class="mobile-menu-item" href="pcs.php">
      <i class="bi bi-list-ul"></i> Liste des PC
    </a>
    <a class="mobile-menu-item" href="pc_add.php">
      <i class="bi bi-plus-circle"></i> Ajouter un PC
    </a>
    <a class="mobile-menu-item" href="admin_fields.php">
      <i class="bi bi-gear"></i> Gérer les champs
    </a>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="h2 mb-0">Inventaire PC</h1>
        <div class="d-flex gap-2">
          <?php if (!empty($_SESSION["is_admin"])): ?>
          <a class="btn btn-outline-info" href="admin_fields.php">
            <i class="bi bi-gear"></i> Gérer les champs
          </a>
          <?php endif; ?>
          <?php if (!empty($_SESSION["can_add"])): ?>
          <a class="btn btn-primary" href="pc_add.php">
            <i class="bi bi-plus-circle"></i> Ajouter PC
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-4">
          <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Recherche (hostname, serial, user, OS...)">
        </div>
        <div class="col-md-2">
          <select class="form-select" name="statut">
            <?php foreach ($allowedStatut as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>" <?= $statut === $s ? "selected" : "" ?>>
                <?= $s === "" ? "Tous statuts" : htmlspecialchars($s) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="arch">
            <?php foreach ($allowedArch as $a): ?>
              <option value="<?= htmlspecialchars($a) ?>" <?= $arch === $a ? "selected" : "" ?>>
                <?= $a === "" ? "Toute archi" : htmlspecialchars($a) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="marque">
            <option value="">Toutes marques</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= $marque === $b ? "selected" : "" ?>>
                <?= htmlspecialchars($b) ?>
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

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead>
            <tr>
              <th>Hostname</th>
              <th>Serial</th>
              <th>Marque</th>
              <th>Modèle</th>
              <th>Utilisateur</th>
              <th>OS</th>
              <th>Version</th>
              <th>Arch</th>
              <th>Domaine</th>
              <th>Statut</th>
              <th>Mise à jour</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pcs as $pc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($pc["hostname"]) ?></strong></td>
              <td><code class="text-info"><?= htmlspecialchars($pc["serial"]) ?></code></td>
              <td><?= htmlspecialchars($pc["marque"]) ?></td>
              <td><?= htmlspecialchars($pc["modele"] ?? "-") ?></td>
              <td><?= htmlspecialchars($pc["utilisateur"]) ?></td>
              <td><?= htmlspecialchars($pc["os"]) ?></td>
              <td><?= htmlspecialchars($pc["os_version"] ?? "-") ?></td>
              <td><span class="badge bg-secondary"><?= htmlspecialchars($pc["architecture"]) ?></span></td>
              <td><?= htmlspecialchars($pc["domaine"] ?? "-") ?></td>
              <td>
                <?php
                $statusClass = match($pc["statut"]) {
                  "En service" => "success",
                  "En stock" => "info",
                  "En réparation" => "warning",
                  "Retiré" => "secondary",
                  default => "secondary"
                };
                ?>
                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($pc["statut"]) ?></span>
              </td>
              <td><small class="text-muted"><?= htmlspecialchars($pc["updated_at"]) ?></small></td>
              <td>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                          data-bs-toggle="dropdown" data-bs-auto-close="true"
                          data-bs-boundary="viewport" aria-expanded="false">
                    Actions
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (!empty($_SESSION["can_edit"])): ?>
                    <li>
                      <a class="dropdown-item" href="pc_edit.php?id=<?= (int)$pc["id"] ?>">
                        <i class="bi bi-pencil"></i> Modifier
                      </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION["can_delete"])): ?>
                    <?php if (!empty($_SESSION["can_edit"])): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
                    <li>
                      <form method="post" action="pc_delete.php" class="d-inline"
                            onsubmit="return confirm('Supprimer ce PC ?');">
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
</div>
<?php
$pageScripts = <<<'JS'
<script>
function toggleMobileMenu() {
  const menu = document.getElementById('mobileMenu');
  menu.classList.toggle('show');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
  const nav = document.querySelector('.mobile-nav');
  const menu = document.getElementById('mobileMenu');
  const toggle = document.querySelector('.mobile-menu-toggle');

  if (menu && menu.classList.contains('show') &&
      !nav.contains(event.target)) {
    menu.classList.remove('show');
  }
});
</script>
JS;
require __DIR__ . "/partials/footer.php";
