<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

$stmt = $pdo->prepare("SELECT * FROM pcs WHERE id = ?");
$stmt->execute([$id]);
$pc = $stmt->fetch();
if (!$pc) { die("PC introuvable"); }

$customFields = get_custom_fields($pdo);
$customValues = [];
if (!empty($customFields)) {
    $stmtCv = $pdo->prepare("SELECT field_name, field_value FROM pc_custom_data WHERE pc_id = ?");
    $stmtCv->execute([$id]);
    foreach ($stmtCv->fetchAll() as $row) {
        $customValues[$row['field_name']] = $row['field_value'];
    }
}

$pageTitle  = e($pc['hostname']) . " — Fiche PC";
$activePage = "pcs";
require __DIR__ . "/partials/header.php";
?>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">
      <i class="bi bi-pc-display text-primary"></i>
      <?= e($pc['hostname']) ?>
      <span class="badge bg-<?= statut_badge_class($pc['statut']) ?> fs-6 align-middle ms-2"><?= e($pc['statut']) ?></span>
    </h3>
    <div class="d-flex gap-2">
      <?php if (!empty($_SESSION['can_edit'])): ?>
      <a href="/pc_edit.php?id=<?= (int)$pc['id'] ?>" class="btn btn-primary">
        <i class="bi bi-pencil"></i> Modifier
      </a>
      <?php endif; ?>
      <a href="/pcs.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
      </a>
    </div>
  </div>

  <div class="row g-4">

    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Identification</h6></div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <tbody>
              <tr><th class="ps-3" style="width:40%">Hostname</th><td><strong><?= e($pc['hostname']) ?></strong></td></tr>
              <tr><th class="ps-3">Serial</th><td><code class="text-info"><?= e($pc['serial']) ?></code></td></tr>
              <tr><th class="ps-3">Marque</th><td><?= e($pc['marque']) ?></td></tr>
              <tr><th class="ps-3">Modele</th><td><?= e($pc['modele'] ?: '—') ?></td></tr>
              <tr><th class="ps-3">Utilisateur</th><td><?= e($pc['utilisateur'] ?: '—') ?></td></tr>
              <tr><th class="ps-3">Domaine</th><td><?= e($pc['domaine'] ?: '—') ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-cpu"></i> Systeme</h6></div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <tbody>
              <tr><th class="ps-3" style="width:40%">OS</th><td><?= e($pc['os']) ?></td></tr>
              <tr><th class="ps-3">Version OS</th><td><?= e($pc['os_version'] ?: '—') ?></td></tr>
              <tr><th class="ps-3">Architecture</th><td><span class="badge bg-secondary"><?= e($pc['architecture']) ?></span></td></tr>
              <tr><th class="ps-3">Statut</th><td><span class="badge bg-<?= statut_badge_class($pc['statut']) ?>"><?= e($pc['statut']) ?></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if (!empty($pc['remarques'])): ?>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-left-text"></i> Remarques</h6></div>
        <div class="card-body">
          <p class="mb-0"><?= nl2br(e($pc['remarques'])) ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($customFields)): ?>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-columns-gap"></i> Champs personnalises</h6></div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <tbody>
              <?php foreach ($customFields as $cf): ?>
              <tr>
                <th class="ps-3" style="width:40%"><?= e($cf['field_label']) ?></th>
                <td><?= e($customValues[$cf['field_name']] ?? '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Dates</h6></div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <tbody>
              <tr><th class="ps-3" style="width:40%">Date de creation</th><td><?= e($pc['created_at'] ?? '—') ?></td></tr>
              <tr><th class="ps-3">Derniere modification</th><td><?= e($pc['updated_at'] ?? '—') ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
require __DIR__ . "/partials/footer.php";
