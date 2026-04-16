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

// Handle note actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['can_edit'])) {
    csrf_check();
    $noteAction = $_POST['note_action'] ?? '';

    if ($noteAction === 'add') {
        $content = trim($_POST['note_content'] ?? '');
        if ($content !== '') {
            $stmtNote = $pdo->prepare("INSERT INTO pc_notes (pc_id, user_id, username, content) VALUES (?, ?, ?, ?)");
            $stmtNote->execute([$id, $_SESSION['user_id'], $_SESSION['username'], $content]);
        }
        header("Location: /pc_view.php?id=$id#notes");
        exit;
    } elseif ($noteAction === 'delete') {
        $noteId = (int)($_POST['note_id'] ?? 0);
        $delWhere = "id = ? AND pc_id = ?";
        $delParams = [$noteId, $id];
        if (empty($_SESSION['is_admin'])) {
            $delWhere .= " AND user_id = ?";
            $delParams[] = $_SESSION['user_id'];
        }
        $pdo->prepare("DELETE FROM pc_notes WHERE $delWhere")->execute($delParams);
        header("Location: /pc_view.php?id=$id#notes");
        exit;
    }
}

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
      <?php if (!empty($_SESSION['can_add'])): ?>
      <a href="/pc_add.php?duplicate=<?= (int)$pc['id'] ?>" class="btn btn-outline-info">
        <i class="bi bi-copy"></i> Dupliquer
      </a>
      <?php endif; ?>
      <a href="/pc_print.php?id=<?= (int)$pc['id'] ?>" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-printer"></i> Imprimer
      </a>
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

    <!-- Notes -->
    <?php
    $notesStmt = $pdo->prepare("SELECT * FROM pc_notes WHERE pc_id = ? ORDER BY created_at DESC");
    $notesStmt->execute([$id]);
    $notes = $notesStmt->fetchAll();
    ?>
    <div class="col-12" id="notes">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-sticky"></i> Notes (<?= count($notes) ?>)</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($_SESSION['can_edit'])): ?>
          <form method="post" class="mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="note_action" value="add">
            <div class="mb-2">
              <textarea class="form-control" name="note_content" rows="2" placeholder="Ajouter une note..." required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-circle"></i> Ajouter
            </button>
          </form>
          <?php endif; ?>

          <?php if (empty($notes)): ?>
            <p class="text-muted mb-0">Aucune note</p>
          <?php else: ?>
            <?php foreach ($notes as $note): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong><?= e($note['username']) ?></strong>
                  <small class="text-muted ms-2"><?= e($note['created_at']) ?></small>
                </div>
                <?php if (!empty($_SESSION['is_admin']) || ($note['user_id'] == ($_SESSION['user_id'] ?? 0))): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette note ?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="note_action" value="delete">
                  <input type="hidden" name="note_id" value="<?= (int)$note['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </div>
              <p class="mb-0 mt-2"><?= nl2br(e($note['content'])) ?></p>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- History -->
    <?php
    $historyStmt = $pdo->prepare("SELECT * FROM pc_history WHERE pc_id = ? ORDER BY created_at DESC LIMIT 50");
    $historyStmt->execute([$id]);
    $history = $historyStmt->fetchAll();
    ?>
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historique (<?= count($history) ?>)</h6>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <p class="text-muted mb-0">Aucun historique</p>
          <?php else: ?>
            <?php foreach ($history as $h): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                  <strong><?= e($h['username']) ?></strong>
                  <?php
                  $actionLabel = match($h['action']) {
                      'created' => '<span class="badge bg-success">Cree</span>',
                      'updated' => '<span class="badge bg-primary">Modifie</span>',
                      'deleted' => '<span class="badge bg-danger">Supprime</span>',
                      default   => '<span class="badge bg-secondary">' . e($h['action']) . '</span>',
                  };
                  echo $actionLabel;
                  ?>
                </div>
                <small class="text-muted"><?= e($h['created_at']) ?></small>
              </div>
              <?php if ($h['action'] === 'updated' && $h['changes']): ?>
                <?php $changes = json_decode($h['changes'], true); ?>
                <?php if (!empty($changes)): ?>
                <table class="table table-sm table-bordered mt-2 mb-0">
                  <thead>
                    <tr><th>Champ</th><th>Avant</th><th>Apres</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($changes as $field => $vals): ?>
                    <tr>
                      <td><code><?= e($field) ?></code></td>
                      <td class="text-danger"><?= e($vals['old'] ?? '') ?></td>
                      <td class="text-success"><?= e($vals['new'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
require __DIR__ . "/partials/footer.php";
