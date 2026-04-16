<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";


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
        $errors = array_merge($errors, validate_password($password));

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
                log_activity($pdo, 'create_user', 'user', (int)$pdo->lastInsertId(), $username);
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
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$uid]);
        $uName = $uStmt->fetchColumn() ?: "User #$uid";
        log_activity($pdo, 'update_perms', 'user', $uid, $uName);
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

        $adminPassword = $_POST["admin_password"] ?? "";
        $adminStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $adminStmt->execute([$_SESSION["user_id"]]);
        $adminUser = $adminStmt->fetch();

        if (!$adminUser || !password_verify($adminPassword, $adminUser["password_hash"])) {
            header("Location: /admin/users.php?msg=admin_auth_error");
            exit;
        }

        if ($uid > 0) {
            $delStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $delStmt->execute([$uid]);
            $delName = $delStmt->fetchColumn() ?: "User #$uid";
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            log_activity($pdo, 'delete_user', 'user', $uid, $delName);
        }
        header("Location: /admin/users.php?msg=deleted");
        exit;
    }

    // Reset password
    if ($action === "reset_password") {
        $uid          = (int)($_POST["user_id"] ?? 0);
        $password     = $_POST["new_password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";
        $adminPassword   = $_POST["admin_password"] ?? "";

        // Verify admin's own password
        $adminStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $adminStmt->execute([$_SESSION["user_id"]]);
        $adminUser = $adminStmt->fetch();

        if (!$adminUser || !password_verify($adminPassword, $adminUser["password_hash"])) {
            header("Location: /admin/users.php?msg=admin_auth_error");
            exit;
        }

        if ($password !== $confirmPassword) {
            header("Location: /admin/users.php?msg=password_mismatch");
            exit;
        }

        $pwdErrors = validate_password($password);
        if ($uid > 0 && empty($pwdErrors)) {
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

$msgMap = [
    "created"           => ['success', "Utilisateur créé avec succès."],
    "updated"           => ['success', "Permissions mises à jour."],
    "deleted"           => ['success', "Utilisateur supprimé."],
    "password_reset"    => ['success', "Mot de passe réinitialisé."],
    "password_error"    => ['danger',  "Mot de passe invalide (min. 8 caractères, 1 majuscule, 1 chiffre)."],
    "password_mismatch" => ['danger',  "Les deux mots de passe ne correspondent pas."],
    "admin_auth_error"  => ['danger',  "Votre mot de passe administrateur est incorrect."],
    "self_delete_error" => ['danger',  "Vous ne pouvez pas supprimer votre propre compte."],
    "self_admin_error"  => ['danger',  "Vous ne pouvez pas retirer vos propres droits admin."],
];

$flash = [];
if (isset($_GET["msg"]) && isset($msgMap[$_GET["msg"]])) {
    [$type, $text] = $msgMap[$_GET["msg"]];
    $flash[] = ['type' => $type, 'msg' => e($text)];
}
if (!empty($errors)) {
    foreach ($errors as $err) {
        $flash[] = ['type' => 'danger', 'msg' => e($err)];
    }
}

$pageTitle = "Admin - Utilisateurs";
$activePage = "admin_users";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Gestion des Utilisateurs</h3>
  </div>

  <?php require __DIR__ . "/../partials/flash.php"; ?>

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
                      <button class="btn btn-sm btn-outline-danger"
                              data-bs-toggle="modal"
                              data-bs-target="#modalDelete"
                              data-uid="<?= (int)$u["id"] ?>"
                              data-uname="<?= e($u["username"]) ?>">
                        <i class="bi bi-trash"></i>
                      </button>
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
                 minlength="8" placeholder="Min. 8 car., 1 majuscule, 1 chiffre">
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
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="resetPwdForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action"   value="reset_password">
        <input type="hidden" name="user_id"  id="modalPwdUid">
        <div class="modal-header">
          <h5 class="modal-title">Réinitialiser le mot de passe</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-3">Utilisateur : <strong id="modalPwdName"></strong></p>

          <div class="mb-3">
            <label class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
            <input class="form-control" type="password" name="new_password" id="newPwd"
                   placeholder="Min. 8 car., 1 majuscule, 1 chiffre" required minlength="8">
            <div id="pwdStrength" class="form-text"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
            <input class="form-control" type="password" name="confirm_password" id="confirmPwd"
                   placeholder="Retapez le mot de passe" required minlength="8">
            <div id="pwdMatch" class="form-text"></div>
          </div>

          <hr>

          <div class="mb-0">
            <label class="form-label"><i class="bi bi-shield-lock"></i> Votre mot de passe admin <span class="text-danger">*</span></label>
            <input class="form-control" type="password" name="admin_password"
                   placeholder="Confirmez votre identité" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning" id="resetSubmitBtn">
            <i class="bi bi-key"></i> Réinitialiser
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Delete user modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="modalDelete" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="post" id="deleteUserForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action"  value="delete">
        <input type="hidden" name="user_id" id="modalDeleteUid">
        <div class="modal-header">
          <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Supprimer un utilisateur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Supprimer l'utilisateur <strong id="modalDeleteName"></strong> ?</p>
          <p class="text-muted small">Cette action est irréversible.</p>
          <hr>
          <div class="mb-0">
            <label class="form-label"><i class="bi bi-shield-lock"></i> Votre mot de passe admin <span class="text-danger">*</span></label>
            <input class="form-control" type="password" name="admin_password"
                   placeholder="Confirmez votre identité" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Supprimer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
(function() {
  var modal      = document.getElementById('modalPwd');
  var newPwd     = document.getElementById('newPwd');
  var confirmPwd = document.getElementById('confirmPwd');
  var strength   = document.getElementById('pwdStrength');
  var matchMsg   = document.getElementById('pwdMatch');

  // Populate modal
  modal.addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('modalPwdUid').value = btn.dataset.uid;
    document.getElementById('modalPwdName').textContent = btn.dataset.uname;
    // Reset fields
    document.getElementById('resetPwdForm').reset();
    strength.textContent = '';
    matchMsg.textContent = '';
  });

  // Live password strength check
  newPwd.addEventListener('input', function() {
    var v = newPwd.value;
    var issues = [];
    if (v.length < 8) issues.push('8 caractères min.');
    if (!/[A-Z]/.test(v)) issues.push('1 majuscule');
    if (!/[0-9]/.test(v)) issues.push('1 chiffre');
    if (issues.length > 0) {
      strength.textContent = 'Manque : ' + issues.join(', ');
      strength.className = 'form-text text-danger';
    } else {
      strength.textContent = 'Mot de passe valide';
      strength.className = 'form-text text-success';
    }
    checkMatch();
  });

  // Live match check
  confirmPwd.addEventListener('input', checkMatch);

  function checkMatch() {
    if (confirmPwd.value === '') { matchMsg.textContent = ''; return; }
    if (newPwd.value === confirmPwd.value) {
      matchMsg.textContent = 'Les mots de passe correspondent.';
      matchMsg.className = 'form-text text-success';
    } else {
      matchMsg.textContent = 'Les mots de passe ne correspondent pas.';
      matchMsg.className = 'form-text text-danger';
    }
  }

  // Populate delete modal
  var delModal = document.getElementById('modalDelete');
  delModal.addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('modalDeleteUid').value = btn.dataset.uid;
    document.getElementById('modalDeleteName').textContent = btn.dataset.uname;
    document.getElementById('deleteUserForm').reset();
  });
})();
</script>
JS;
require __DIR__ . "/../partials/footer.php";
