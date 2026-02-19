<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";

// --- Stats ---
$total = (int) $pdo->query("SELECT COUNT(*) FROM pcs")->fetchColumn();

$statusCounts = $pdo->query(
    "SELECT statut, COUNT(*) AS cnt FROM pcs GROUP BY statut"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$enService   = (int) ($statusCounts["En service"] ?? 0);
$enStock     = (int) ($statusCounts["En stock"] ?? 0);
$enReparation = (int) ($statusCounts["En réparation"] ?? 0);
$retire      = (int) ($statusCounts["Retiré"] ?? 0);

// Architecture breakdown
$archCounts = $pdo->query(
    "SELECT architecture, COUNT(*) AS cnt FROM pcs GROUP BY architecture"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Top 5 brands
$topBrands = $pdo->query(
    "SELECT marque, COUNT(*) AS cnt FROM pcs GROUP BY marque ORDER BY cnt DESC LIMIT 5"
)->fetchAll();

// Top 5 OS
$topOs = $pdo->query(
    "SELECT os, COUNT(*) AS cnt FROM pcs GROUP BY os ORDER BY cnt DESC LIMIT 5"
)->fetchAll();

// Recently updated PCs (last 10)
$recentPcs = $pdo->query(
    "SELECT id, hostname, serial, utilisateur, statut, updated_at
     FROM pcs ORDER BY updated_at DESC LIMIT 10"
)->fetchAll();

// Recently added PCs (last 5)
$recentAdded = $pdo->query(
    "SELECT id, hostname, serial, marque, modele, created_at
     FROM pcs ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = "Dashboard";
$activePage = "dashboard";
require __DIR__ . "/partials/header.php";
?>

<div class="container-fluid py-4">

  <!-- Page header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-speedometer2 text-primary"></i> Dashboard</h3>
    <?php if (!empty($_SESSION["can_add"])): ?>
    <a class="btn btn-primary" href="/pc_add.php">
      <i class="bi bi-plus-circle"></i> Ajouter PC
    </a>
    <?php endif; ?>
  </div>

  <!-- Status cards row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-pc-display display-6 text-primary"></i>
          <h2 class="mt-2 mb-0"><?= $total ?></h2>
          <small class="text-muted">Total PC</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-check-circle display-6 text-success"></i>
          <h2 class="mt-2 mb-0"><?= $enService ?></h2>
          <small class="text-muted">En service</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-box-seam display-6 text-info"></i>
          <h2 class="mt-2 mb-0"><?= $enStock ?></h2>
          <small class="text-muted">En stock</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-tools display-6 text-warning"></i>
          <h2 class="mt-2 mb-0"><?= $enReparation ?></h2>
          <small class="text-muted">En réparation</small>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="card shadow-sm h-100">
        <div class="card-body text-center">
          <i class="bi bi-x-circle display-6 text-secondary"></i>
          <h2 class="mt-2 mb-0"><?= $retire ?></h2>
          <small class="text-muted">Retirés</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts row: Architecture + Brands + OS -->
  <div class="row g-3 mb-4">

    <!-- Architecture breakdown -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-cpu"></i> Architecture</h6>
        </div>
        <div class="card-body">
          <?php foreach ($archCounts as $arch => $cnt): ?>
          <?php $pct = $total > 0 ? round($cnt / $total * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="badge bg-secondary"><?= htmlspecialchars($arch) ?></span>
              <small class="text-muted"><?= $cnt ?> (<?= $pct ?>%)</small>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-primary" style="width: <?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($archCounts)): ?>
          <p class="text-muted text-center mb-0">Aucune donnée</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top brands -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-building"></i> Top marques</h6>
        </div>
        <div class="card-body">
          <?php foreach ($topBrands as $brand): ?>
          <?php $pct = $total > 0 ? round($brand["cnt"] / $total * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span><?= htmlspecialchars($brand["marque"]) ?></span>
              <small class="text-muted"><?= $brand["cnt"] ?> (<?= $pct ?>%)</small>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-info" style="width: <?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($topBrands)): ?>
          <p class="text-muted text-center mb-0">Aucune donnée</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top OS -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-windows"></i> Top OS</h6>
        </div>
        <div class="card-body">
          <?php foreach ($topOs as $os): ?>
          <?php $pct = $total > 0 ? round($os["cnt"] / $total * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span><?= htmlspecialchars($os["os"]) ?></span>
              <small class="text-muted"><?= $os["cnt"] ?> (<?= $pct ?>%)</small>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($topOs)): ?>
          <p class="text-muted text-center mb-0">Aucune donnée</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Bottom row: Recent activity + Recently added -->
  <div class="row g-3">

    <!-- Recently updated -->
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-clock-history"></i> Dernières modifications</h6>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
              <thead>
                <tr>
                  <th>Hostname</th>
                  <th>Serial</th>
                  <th>Utilisateur</th>
                  <th>Statut</th>
                  <th>Mis à jour</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($recentPcs as $pc): ?>
                <tr>
                  <td>
                    <?php if (!empty($_SESSION["can_edit"])): ?>
                    <a href="/pc_edit.php?id=<?= (int) $pc["id"] ?>" class="text-decoration-none">
                      <strong><?= htmlspecialchars($pc["hostname"]) ?></strong>
                    </a>
                    <?php else: ?>
                    <strong><?= htmlspecialchars($pc["hostname"]) ?></strong>
                    <?php endif; ?>
                  </td>
                  <td><code class="text-info"><?= htmlspecialchars($pc["serial"]) ?></code></td>
                  <td><?= htmlspecialchars($pc["utilisateur"]) ?></td>
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
                </tr>
              <?php endforeach; ?>
              <?php if (empty($recentPcs)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Aucun PC enregistré</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Recently added -->
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Derniers ajouts</h6>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
          <?php foreach ($recentAdded as $pc): ?>
            <li class="list-group-item bg-transparent border-secondary">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong><?= htmlspecialchars($pc["hostname"]) ?></strong>
                  <br>
                  <small class="text-muted">
                    <?= htmlspecialchars($pc["marque"]) ?>
                    <?= $pc["modele"] ? " - " . htmlspecialchars($pc["modele"]) : "" ?>
                  </small>
                </div>
                <small class="text-muted"><?= htmlspecialchars($pc["created_at"]) ?></small>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if (empty($recentAdded)): ?>
            <li class="list-group-item bg-transparent border-secondary text-center text-muted py-3">
              Aucun PC enregistré
            </li>
          <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

  </div>

</div>

<?php
require __DIR__ . "/partials/footer.php";
