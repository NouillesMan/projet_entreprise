<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";

$protectedFields = ['hostname', 'serial', 'marque', 'utilisateur', 'os', 'architecture', 'statut'];

// Traitement des actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  $action = $_POST["action"] ?? "";

  if ($action === "toggle_visibility") {
    $fieldId = (int)($_POST["field_id"] ?? 0);
    $isVisible = (int)($_POST["is_visible"] ?? 0);

    $stmt = $pdo->prepare("UPDATE custom_fields SET is_visible = ? WHERE id = ?");
    $stmt->execute([$isVisible, $fieldId]);

    header("Location: /admin/fields.php?msg=updated");
    exit;
  }


  if ($action === "add_field") {
    $fieldName = trim($_POST["field_name"] ?? "");
    $fieldLabel = trim($_POST["field_label"] ?? "");
    $fieldType = $_POST["field_type"] ?? "text";
    $isRequired = (int)($_POST["is_required"] ?? 0);

    if (!preg_match('/^[a-z_]+$/', $fieldName)) {
      $error = "Nom de champ invalide : lettres minuscules et underscores uniquement.";
    } elseif (!array_key_exists($fieldType, FIELD_TYPES)) {
      $error = "Type de champ invalide.";
    } else {
      try {
        $stmt = $pdo->prepare("
          INSERT INTO custom_fields (field_name, field_label, field_type, is_required, is_visible, display_order)
          VALUES (?, ?, ?, ?, 1, (SELECT IFNULL(MAX(display_order), 0) + 1 FROM custom_fields AS cf))
        ");
        $stmt->execute([$fieldName, $fieldLabel, $fieldType, $isRequired]);

        header("Location: /admin/fields.php?msg=added");
        exit;
      } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout du champ. Vérifiez que le nom n'existe pas déjà.";
      }
    }
  }

  if ($action === "reorder_fields") {
    $ids = array_values(array_map('intval', json_decode($_POST["ids"] ?? "[]", true) ?: []));
    if (empty($ids)) {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['success' => false]);
      exit;
    }
    $stmt = $pdo->prepare("UPDATE custom_fields SET display_order = ? WHERE id = ?");
    try {
      $pdo->beginTransaction();
      foreach ($ids as $position => $id) {
        $stmt->execute([$position + 1, $id]);
      }
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
      http_response_code(500);
      header('Content-Type: application/json');
      echo json_encode(['success' => false]);
      exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
  }

  if ($action === "edit_field") {
    $fieldId    = (int)($_POST["field_id"] ?? 0);
    $fieldLabel = trim($_POST["field_label"] ?? "");
    $fieldType  = $_POST["field_type"] ?? "text";
    $isRequired = (int)($_POST["is_required"] ?? 0);

    if (empty($fieldLabel) || !array_key_exists($fieldType, FIELD_TYPES)) {
      $error = "Données invalides.";
    } else {
      $stmt = $pdo->prepare("SELECT field_name FROM custom_fields WHERE id = ?");
      $stmt->execute([$fieldId]);
      $row = $stmt->fetch();

      if (!$row) {
        $error = "Champ introuvable.";
      } else {
        $pdo->prepare("UPDATE custom_fields SET field_label = ?, field_type = ?, is_required = ? WHERE id = ?")
            ->execute([$fieldLabel, $fieldType, $isRequired, $fieldId]);

        header("Location: /admin/fields.php?msg=updated");
        exit;
      }
    }
  }

  if ($action === "delete_field") {
    $fieldId = (int)($_POST["field_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT field_name FROM custom_fields WHERE id = ?");
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();

    if ($field && in_array($field['field_name'], $protectedFields)) {
      header("Location: /admin/fields.php?msg=protected");
      exit;
    }

    $stmt = $pdo->prepare("DELETE FROM custom_fields WHERE id = ?");
    $stmt->execute([$fieldId]);

    header("Location: /admin/fields.php?msg=deleted");
    exit;
  }
}

// Récupérer tous les champs
$stmt = $pdo->query("SELECT * FROM custom_fields ORDER BY display_order ASC, id ASC");
$fields = $stmt->fetchAll();

$pageTitle = "Admin - Gestion des champs";
$activePage = "admin_fields";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Gestion des Champs</h3>
  </div>

  <?php
  $flash = [];
  if (isset($_GET['msg'])) {
    $msgMap = [
      'updated'   => ['success', 'Champ mis à jour avec succès.'],
      'added'     => ['success', 'Nouveau champ ajouté avec succès.'],
      'deleted'   => ['success', 'Champ supprimé avec succès.'],
      'protected' => ['warning', 'Ce champ est protégé et ne peut pas être supprimé.'],
    ];
    [$t, $m] = $msgMap[$_GET['msg']] ?? ['success', 'Opération effectuée.'];
    $flash[] = ['type' => $t, 'msg' => e($m)];
  }
  if (isset($error)) {
    $flash[] = ['type' => 'danger', 'msg' => e($error)];
  }
  require __DIR__ . "/../partials/flash.php";
  ?>

  <!-- Liste des champs existants -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h6 class="mb-0">Champs Existants</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:40px;"></th>
              <th>Nom du champ</th>
              <th>Libellé</th>
              <th>Type</th>
              <th>Obligatoire</th>
              <th>Visible</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="fields-sortable">
            <?php foreach ($fields as $field): ?>
              <tr data-id="<?= $field['id'] ?>">
                <td class="drag-handle text-center text-secondary" style="cursor: grab;">
                  <i class="bi bi-grip-vertical fs-5"></i>
                </td>
                <td><code><?= e($field['field_name']) ?></code></td>
                <td><?= e($field['field_label']) ?></td>
                <td><span class="badge bg-info"><?= e($field['field_type']) ?></span></td>
                <td>
                  <?php if ($field['is_required']): ?>
                    <span class="badge bg-danger">Oui</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Non</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_visibility">
                    <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                    <input type="hidden" name="is_visible" value="<?= $field['is_visible'] ? 0 : 1 ?>">
                    <button type="submit" class="btn btn-sm <?= $field['is_visible'] ? 'btn-success' : 'btn-secondary' ?>">
                      <i class="bi bi-<?= $field['is_visible'] ? 'eye' : 'eye-slash' ?>"></i>
                      <?= $field['is_visible'] ? 'Visible' : 'Caché' ?>
                    </button>
                  </form>
                </td>
                <td>
                  <?php
                  $isProtected = in_array($field['field_name'], $protectedFields);
                  ?>
                  <button type="button" class="btn btn-sm btn-outline-primary me-1"
                          data-bs-toggle="modal" data-bs-target="#editFieldModal"
                          data-field-id="<?= $field['id'] ?>"
                          data-field-label="<?= e($field['field_label']) ?>"
                          data-field-type="<?= e($field['field_type']) ?>"
                          data-field-required="<?= (int)$field['is_required'] ?>"
                          data-field-protected="<?= $isProtected ? '1' : '0' ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <?php if (!$isProtected): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Supprimer ce champ ?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete_field">
                      <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Protégé</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulaire d'ajout de nouveau champ -->
  <div class="card shadow-sm">
    <div class="card-header">
      <h6 class="mb-0">Ajouter un Nouveau Champ Personnalisé</h6>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_field">

        <div class="col-md-4">
          <label class="form-label">Nom du champ (technique) <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="field_name" required
                 pattern="[a-z_]+" placeholder="ex: custom_field_1"
                 title="Uniquement lettres minuscules et underscores">
          <small class="text-muted">Lettres minuscules et _ uniquement</small>
        </div>

        <div class="col-md-4">
          <label class="form-label">Libellé (affiché) <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="field_label" required
                 placeholder="ex: Localisation">
        </div>

        <div class="col-md-2">
          <label class="form-label">Type <span class="text-danger">*</span></label>
          <select class="form-select" name="field_type" required>
            <?php foreach (FIELD_TYPES as $type => $label): ?>
              <option value="<?= e($type) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Obligatoire ?</label>
          <select class="form-select" name="is_required">
            <option value="0">Non</option>
            <option value="1">Oui</option>
          </select>
        </div>

        <div class="col-12">
          <hr>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Ajouter le champ
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal modification de champ -->
  <div class="modal fade" id="editFieldModal" tabindex="-1" aria-labelledby="editFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="edit_field">
          <input type="hidden" name="field_id" id="editFieldId">
          <div class="modal-header">
            <h5 class="modal-title" id="editFieldModalLabel">Modifier le champ</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Libellé affiché <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="field_label" id="editFieldLabel" required>
            </div>
            <div class="mb-3" id="editTypeGroup">
              <label class="form-label">Type</label>
              <select class="form-select" name="field_type" id="editFieldType">
                <?php foreach (FIELD_TYPES as $type => $label): ?>
                  <option value="<?= e($type) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-0" id="editRequiredGroup">
              <label class="form-label">Obligatoire ?</label>
              <select class="form-select" name="is_required" id="editFieldRequired">
                <option value="0">Non</option>
                <option value="1">Oui</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg"></i> Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="alert alert-info alert-dismissible fade show mt-4">
    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Information</h6>
    <ul class="mb-0">
      <li>Les champs obligatoires (hostname, serial, marque, utilisateur, OS, architecture, statut) ne peuvent pas être supprimés.</li>
      <li>Vous pouvez cacher temporairement un champ en cliquant sur le bouton "Visible/Caché".</li>
      <li>Les nouveaux champs personnalisés seront stockés séparément et affichés dans les formulaires.</li>
      <li>Glissez-déposez les lignes avec l'icône <i class="bi bi-grip-vertical"></i> pour réordonner les champs.</li>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function() {
  // ── Modal modifier champ ──────────────────────────────────────────────────
  var modal = document.getElementById("editFieldModal");
  if (modal) {
    modal.addEventListener("show.bs.modal", function(e) {
      var btn = e.relatedTarget;
      document.getElementById("editFieldId").value       = btn.dataset.fieldId;
      document.getElementById("editFieldLabel").value    = btn.dataset.fieldLabel;
      document.getElementById("editFieldType").value     = btn.dataset.fieldType;
      document.getElementById("editFieldRequired").value = btn.dataset.fieldRequired;
    });
  }

  // ── Drag & drop réordonnancement ─────────────────────────────────────────
  const tbody = document.getElementById("fields-sortable");
  if (!tbody) return;

  const csrfToken = ' . json_encode(csrf_token()) . ';

  Sortable.create(tbody, {
    handle: ".drag-handle",
    animation: 150,
    ghostClass: "table-active",
    onEnd: function() {
      const ids = [...tbody.querySelectorAll("tr[data-id]")].map(tr => tr.dataset.id);

      const formData = new FormData();
      formData.append("action", "reorder_fields");
      formData.append("_csrf_token", csrfToken);
      formData.append("ids", JSON.stringify(ids));

      fetch("/admin/fields.php", { method: "POST", body: formData })
        .then(r => r.json())
        .then(data => { if (!data.success) alert("Erreur lors de la sauvegarde de l\'ordre."); })
        .catch(() => alert("Erreur réseau."));
    }
  });
})();
</script>
';
require __DIR__ . "/../partials/footer.php";
