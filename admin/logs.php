<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";

$filterUser   = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';

$where  = "WHERE 1=1 ";
$params = [];

if ($filterUser !== '') {
    $where .= "AND username = :user ";
    $params[':user'] = $filterUser;
}
if ($filterAction !== '') {
    $where .= "AND action = :action ";
    $params[':action'] = $filterAction;
}
if ($dateFrom !== '') {
    $where .= "AND created_at >= :date_from ";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where .= "AND created_at <= :date_to ";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

// Pagination
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log $where");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
$page       = min($page, max(1, $totalPages));

$sql = "SELECT * FROM activity_log $where ORDER BY created_at DESC LIMIT :perPage OFFSET :offset";
$params[':perPage'] = $perPage;
$params[':offset']  = ($page - 1) * $perPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Filters data
$users   = $pdo->query("SELECT DISTINCT username FROM activity_log ORDER BY username")->fetchAll(PDO::FETCH_COLUMN);
$actions = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$actionLabels = [
    'add'          => 'Ajout',
    'edit'         => 'Modification',
    'delete'       => 'Suppression',
    'bulk_delete'  => 'Suppression en masse',
    'bulk_status'  => 'Changement statut',
    'import'       => 'Import CSV',
    'create_user'  => 'Création utilisateur',
    'update_perms' => 'Modification permissions',
    'delete_user'  => 'Suppression utilisateur',
    'reset_password' => 'Reset mot de passe',
];

$actionBadge = [
    'add'          => 'success',
    'edit'         => 'primary',
    'delete'       => 'danger',
    'bulk_delete'  => 'danger',
    'bulk_status'  => 'warning',
    'import'       => 'info',
    'create_user'  => 'success',
    'update_perms' => 'primary',
    'delete_user'  => 'danger',
    'reset_password' => 'warning',
];

$pageTitle  = "Admin - Logs";
$activePage = "admin_logs";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-journal-text text-primary"></i> Journal d'activite</h3>
    <span class="badge bg-secondary"><?= $total ?> entree<?= $total > 1 ? 's' : '' ?></span>
  </div>

  <!-- Filters -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form class="row g-3" method="get">
        <div class="col-md-3">
          <select class="form-select" name="user">
            <option value="">Tous les utilisateurs</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= e($u) ?>" <?= $filterUser === $u ? 'selected' : '' ?>><?= e($u) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="action">
            <option value="">Toutes les actions</option>
            <?php foreach ($actions as $a): ?>
              <option value="<?= e($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= e($actionLabels[$a] ?? $a) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>" placeholder="Du">
        </div>
        <div class="col-md-2">
          <input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>" placeholder="Au">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit">Filtrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Log table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Utilisateur</th>
              <th>Action</th>
              <th>Cible</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><small class="text-muted"><?= e($log['created_at']) ?></small></td>
              <td><strong><?= e($log['username']) ?></strong></td>
              <td>
                <span class="badge bg-<?= $actionBadge[$log['action']] ?? 'secondary' ?>">
                  <?= e($actionLabels[$log['action']] ?? $log['action']) ?>
                </span>
              </td>
              <td>
                <?php if ($log['target_label']): ?>
                  <?= e($log['target_label']) ?>
                  <?php if ($log['target_id'] && $log['target_type'] === 'pc'): ?>
                    <a href="/pc_view.php?id=<?= (int)$log['target_id'] ?>" class="text-decoration-none ms-1" title="Voir">
                      <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><small class="text-muted"><?= e($log['details']) ?></small></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Aucune activite enregistree</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

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
      <?= $total ?> entree<?= $total > 1 ? 's' : '' ?> — page <?= $page ?> / <?= $totalPages ?>
    </p>
  </nav>
  <?php endif; ?>
</div>

<?php
require __DIR__ . "/../partials/footer.php";
