<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Rate limiting
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockUntil = $_SESSION['login_lock_until'] ?? 0;

    if ($attempts >= LOGIN_MAX_ATTEMPTS && time() < $lockUntil) {
        $remaining = (int)ceil(($lockUntil - time()) / 60);
        $error = "Trop de tentatives. Réessayez dans $remaining minute" . ($remaining > 1 ? 's' : '') . ".";
    } elseif ($username !== "" && $password !== "") {
        if (time() >= $lockUntil) {
            $_SESSION['login_attempts'] = 0;
            $attempts = 0;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['login_lock_until']);
            session_regenerate_id(true);
            $_SESSION["user_id"]    = $user["id"];
            $_SESSION["username"]   = $user["username"];
            $_SESSION["is_admin"]   = (bool)$user["is_admin"];
            $_SESSION["can_view"]   = (bool)$user["can_view"];
            $_SESSION["can_add"]    = (bool)$user["can_add"];
            $_SESSION["can_edit"]   = (bool)$user["can_edit"];
            $_SESSION["can_delete"] = (bool)$user["can_delete"];

            header("Location: /dashboard.php");
            exit;
        }

        $attempts++;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_lock_until'] = time() + LOGIN_LOCKOUT_SECONDS;
            $error = "Trop de tentatives. Compte verrouillé pendant 5 minutes.";
        } else {
            $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
            $error = "Identifiants incorrects. $remaining tentative" . ($remaining > 1 ? 's' : '') . " restante" . ($remaining > 1 ? 's' : '') . ".";
        }
    } else {
        $error = "Identifiants incorrects.";
    }
}

$pageTitle = "Connexion — Inventaire PC";
$bodyClass = "login-page";
require __DIR__ . "/partials/header.php";
?>

<div class="login-wrapper">
  <div class="text-center mb-4">
    <i class="bi bi-pc-display display-4 text-primary"></i>
    <h1 class="h3 mt-2 fw-bold">Inventaire PC</h1>
    <p class="text-muted">Connectez-vous pour continuer</p>
  </div>

  <div class="card shadow">
    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2">
          <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label" for="username">Nom d'utilisateur</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input id="username" class="form-control" type="text" name="username"
                   value="<?= e($_POST["username"] ?? "") ?>"
                   autofocus autocomplete="username" required>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="password">Mot de passe</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input id="password" class="form-control" type="password" name="password"
                   autocomplete="current-password" required>
          </div>
        </div>

        <button class="btn btn-primary w-100" type="submit">
          <i class="bi bi-box-arrow-in-right"></i> Connexion
        </button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
