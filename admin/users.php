<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";


// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    $action = $_POST["action"] ?? "";

    // Create user
    if ($action === "create") {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $errors   = [];

        if ($username === "")    $errors[] = "Nom d'utilisateur obligatoire.";
        if (strlen($password) < 4) $errors[] = "Mot de passe trop court (min. 4 caractères).";

        if (!$errors) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, is_admin, can_view, can_add, can_edit, can_delete)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $username,
                    $hash,
                    isset($_POST["is_admin"])   ? 1 : 0,
                    isset($_POST["can_view"])   ? 1 : 0,
                    isset($_POST["can_add"])    ? 1 : 0,
                    isset($_POST["can_edit"])   ? 1 : 0,
                    isset($_POST["can_delete"]) ? 1 : 0,
                ]);
                header("Location: /admin/users.php?msg=created");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === "23000") {
                    $errors[] = "Ce nom d'utilisateur existe déjà.";
                } else {
                    throw $e;
                }
            }
        }
    }

    // Update permissions
    if ($action === "update_perms") {
        $uid = (int)($_POST["user_id"] ?? 0);
        if ($uid === (int)$_SESSION["user_id"] && !isset($_POST["is_admin"])) {
            header("Location: /admin/users.php?msg=self_admin_error");
            exit;
        }
        if ($uid > 0) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET is_admin   = ?,
                    can_view   = ?,
                    can_add    = ?,
                    can_edit   = ?,
                    can_delete = ?
                WHERE id = ?
            ");
            $stmt->execute([
                isset($_POST["is_admin"])   ? 1 : 0,
                isset($_POST["can_view"])   ? 1 : 0,
                isset($_POST["can_add"])    ? 1 : 0,
                isset($_POST["can_edit"])   ? 1 : 0,
                isset($_POST["can_delete"]) ? 1 : 0,
                $uid,
            ]);
        }
        header("Location: /admin/users.php?msg=updated");
        exit;
    }

    // Delete user
    if ($action === "delete") {
        $uid = (int)($_POST["user_id"] ?? 0);
        if ($uid === (int)$_SESSION["user_id"]) {
            header("Location: /admin/users.php?msg=self_delete_error");
            exit;
        }
        if ($uid > 0) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        }
        header("Location: /admin/users.php?msg=deleted");
        exit;
    }

    // Reset password
    if ($action === "reset_password") {
        $uid      = (int)($_POST["user_id"] ?? 0);
        $password = $_POST["new_password"] ?? "";
        if ($uid > 0 && strlen($password) >= 4) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([$hash, $uid]);
            header("Location: /admin/users.php?msg=password_reset");
            exit;
        }
        header("Location: /admin/users.php?msg=password_error");
        exit;
    }
}

// ── Load users ────────────────────────────────────────────────────────────────
$users = $pdo->query("SELECT * FROM users ORDER BY is_admin DESC, username ASC")->fetchAll();

$messages = [
    "created"           => "Utilisateur créé avec succès.",
    "updated"           => "Permissions mises à jour.",
    "deleted"           => "Utilisateur supprimé.",
    "password_reset"    => "Mot de passe réinitialisé.",
    "password_error"    => "Mot de passe invalide (min. 4 caractères).",
    "self_delete_error" => "Vous ne pouvez pas supprimer votre propre compte.",
    "self_admin_error"  => "Vous ne pouvez pas retirer vos propres droits admin.",
];

$pageTitle = "Admin - Utilisateurs";
$activePage = "admin_users";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Gestion des Utilisateurs</h3>
  </div>

  <?php if (isset($_GET["msg"]) && isset($messages[$_GET["msg"]])): ?>
    <div class="alert alert-<?= str_contains($_GET["msg"], "error") ? "danger" : "success" ?> alert-dismissible fade show">
      <?= e($messages[$_GET["msg"]]) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ── User list ──────────────────────────────────────────────────────── -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <h6 class="mb-0">Utilisateurs (<?= count($users) ?>)</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th class="text-center">Admin</th>
              <th class="text-center">Voir</th>
              <th class="text-center">Ajouter</th>
              <th class="text-center">Modifier</th>
              <th class="text-center">Supprimer</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <strong><?= e($u["username"]) ?></strong>
                  <?php if ($u["id"] == $_SESSION["user_id"]): ?>
                    <span class="badge bg-secondary ms-1">vous</span>
                  <?php endif; ?>
                </td>

                <?php
                $perms = ["is_admin","can_view","can_add","can_edit","can_delete"];
                foreach ($perms as $p):
                ?>
                  <td class="text-center">
                    <input type="checkbox" class="form-check-input perm-cb"
                           name="<?= $p ?>" value="1"
                           <?= $u[$p] ? "checked" : "" ?>
                           form="perm-form-<?= $u["id"] ?>"
                           <?= ($p === "is_admin" && $u["id"] == $_SESSION["user_id"]) ? "disabled" : "" ?>>
                  </td>
                <?php endforeach; ?>

                <td>
                  <form method="post" id="perm-form-<?= $u["id"] ?>" class="d-none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action"  value="update_perms">
                    <input type="hidden" name="user_id" value="<?= (int)$u["id"] ?>">
                  </form>

                  <div class="d-flex gap-1 flex-wrap">
                    <button class="btn btn-sm btn-outline-primary"
                            onclick="document.getElementById('perm-form-<?= $u["id"] ?>').submit()">
                      <i class="bi bi-save"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#modalPwd"
                            data-uid="<?= (int)$u["id"] ?>"
                            data-uname="<?= e($u["username"]) ?>">
                      <i class="bi bi-key"></i>
                    </button>

                    <?php if ($u["id"] != $_SESSION["user_id"]): ?>
                      <form method="post" class="d-inline"
                            onsubmit="return confirm('Supprimer <?= e($u["username"]) ?> ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action"  value="delete">
                        <input type="hidden" name="user_id" value="<?= (int)$u["id"] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Create user ────────────────────────────────────────────────────── -->
  <div class="card shadow-sm">
    <div class="card-header">
      <h6 class="mb-0">Créer un utilisateur</h6>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <div class="col-md-4">
          <label class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
          <input class="form-control" name="username" required
                 value="<?= e($_POST["username"] ?? "") ?>"
                 placeholder="ex: jean.dupont">
        </div>
        <div class="col-md-4">
          <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
          <input class="form-control" name="password" type="password" required
                 placeholder="Minimum 4 caractères">
        </div>

        <div class="col-md-4">
          <label class="form-label">Permissions</label>
          <div class="d-flex flex-wrap gap-3 pt-1">
            <?php
            $permLabels = [
              "is_admin"   => "Admin",
              "can_view"   => "Voir",
              "can_add"    => "Ajouter",
              "can_edit"   => "Modifier",
              "can_delete" => "Supprimer",
            ];
            foreach ($permLabels as $key => $label):
            ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $key ?>"
                       id="new_<?= $key ?>" value="1"
                       <?= ($key === "can_view") ? "checked" : "" ?>>
                <label class="form-check-label" for="new_<?= $key ?>"><?= $label ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-12">
          <hr>
          <button class="btn btn-success" type="submit">
            <i class="bi bi-person-plus"></i> Créer l'utilisateur
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<!-- ── Reset password modal ───────────────────────────────────────────────── -->
<div class="modal fade" id="modalPwd" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action"   value="reset_password">
        <input type="hidden" name="user_id"  id="modalPwdUid">
        <div class="modal-header">
          <h5 class="modal-title">Réinitialiser le mot de passe</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-2">Utilisateur : <strong id="modalPwdName"></strong></p>
          <input class="form-control" type="password" name="new_password"
                 placeholder="Nouveau mot de passe" required minlength="4">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-key"></i> Réinitialiser
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
// Populate password reset modal
document.getElementById('modalPwd').addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  document.getElementById('modalPwdUid').value  = btn.dataset.uid;
  document.getElementById('modalPwdName').textContent = btn.dataset.uname;
});
</script>
JS;
require __DIR__ . "/../partials/footer.php";
