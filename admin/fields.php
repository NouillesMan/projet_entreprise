<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";

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

  if ($action === "update_order") {
    $fieldId = (int)($_POST["field_id"] ?? 0);
    $order = (int)($_POST["display_order"] ?? 0);

    $stmt = $pdo->prepare("UPDATE custom_fields SET display_order = ? WHERE id = ?");
    $stmt->execute([$order, $fieldId]);

    header("Location: /admin/fields.php?msg=updated");
    exit;
  }

  if ($action === "add_field") {
    $fieldName = trim($_POST["field_name"] ?? "");
    $fieldLabel = trim($_POST["field_label"] ?? "");
    $fieldType = $_POST["field_type"] ?? "text";
    $isRequired = (int)($_POST["is_required"] ?? 0);

    try {
      $stmt = $pdo->prepare("
        INSERT INTO custom_fields (field_name, field_label, field_type, is_required, is_visible, display_order)
        VALUES (?, ?, ?, ?, 1, (SELECT IFNULL(MAX(display_order), 0) + 1 FROM custom_fields AS cf))
      ");
      $stmt->execute([$fieldName, $fieldLabel, $fieldType, $isRequired]);

      header("Location: /admin/fields.php?msg=added");
      exit;
    } catch (PDOException $e) {
      $error = "Erreur: " . $e->getMessage();
    }
  }

  if ($action === "delete_field") {
    $fieldId = (int)($_POST["field_id"] ?? 0);

    $stmt = $pdo->prepare("SELECT field_name FROM custom_fields WHERE id = ?");
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();

    $protectedFields = ['hostname', 'serial', 'marque', 'utilisateur', 'os', 'architecture', 'statut'];
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

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php
      $messages = [
        'updated' => 'Champ mis à jour avec succès',
        'added' => 'Nouveau champ ajouté avec succès',
        'deleted' => 'Champ supprimé avec succès',
        'protected' => 'Ce champ est protégé et ne peut pas être supprimé'
      ];
      echo htmlspecialchars($messages[$_GET['msg']] ?? 'Opération effectuée');
      ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

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
              <th>Ordre</th>
              <th>Nom du champ</th>
              <th>Libellé</th>
              <th>Type</th>
              <th>Obligatoire</th>
              <th>Visible</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fields as $field): ?>
              <tr>
                <td>
                  <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_order">
                    <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                    <input type="number" name="display_order" value="<?= $field['display_order'] ?>"
                           class="form-control form-control-sm" style="width: 80px;"
                           onchange="this.form.submit()">
                  </form>
                </td>
                <td><code><?= htmlspecialchars($field['field_name']) ?></code></td>
                <td><?= htmlspecialchars($field['field_label']) ?></td>
                <td><span class="badge bg-info"><?= htmlspecialchars($field['field_type']) ?></span></td>
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
                  $protectedFields = ['hostname', 'serial', 'marque', 'utilisateur', 'os', 'architecture', 'statut'];
                  $isProtected = in_array($field['field_name'], $protectedFields);
                  ?>
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
            <option value="text">Texte</option>
            <option value="number">Nombre</option>
            <option value="date">Date</option>
            <option value="textarea">Textarea</option>
            <option value="select">Liste déroulante</option>
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

  <div class="alert alert-info alert-dismissible fade show mt-4">
    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> Information</h6>
    <ul class="mb-0">
      <li>Les champs obligatoires (hostname, serial, marque, utilisateur, OS, architecture, statut) ne peuvent pas être supprimés.</li>
      <li>Vous pouvez cacher temporairement un champ en cliquant sur le bouton "Visible/Caché".</li>
      <li>Les nouveaux champs personnalisés seront stockés séparément et affichés dans les formulaires.</li>
      <li>Changez l'ordre d'affichage en modifiant les numéros dans la colonne "Ordre".</li>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>

<?php
require __DIR__ . "/../partials/footer.php";
