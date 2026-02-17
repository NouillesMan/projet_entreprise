<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → go to inventory
if (isset($_SESSION['user_id'])) {
    header('Location: pcs.php');
    exit;
}

require __DIR__ . "/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username !== "" && $password !== "") {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            // Store all permissions in session
            $_SESSION["user_id"]    = $user["id"];
            $_SESSION["username"]   = $user["username"];
            $_SESSION["is_admin"]   = (bool)$user["is_admin"];
            $_SESSION["can_view"]   = (bool)$user["can_view"];
            $_SESSION["can_add"]    = (bool)$user["can_add"];
            $_SESSION["can_edit"]   = (bool)$user["can_edit"];
            $_SESSION["can_delete"] = (bool)$user["can_delete"];

            header("Location: pcs.php");
            exit;
        }
    }

    $error = "Identifiants incorrects.";
}
?>
<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — Inventaire PC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .login-card { width: 100%; max-width: 400px; }
  </style>
</head>
<body class="bg-dark">

<div class="login-card px-3">
  <div class="text-center mb-4">
    <i class="bi bi-pc-display display-4 text-primary"></i>
    <h1 class="h3 mt-2 fw-bold">Inventaire PC</h1>
    <p class="text-muted">Connectez-vous pour continuer</p>
  </div>

  <div class="card shadow">
    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2">
          <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label" for="username">Nom d'utilisateur</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input id="username" class="form-control" type="text" name="username"
                   value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
