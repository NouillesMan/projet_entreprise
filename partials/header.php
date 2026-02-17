<?php
$pageTitle = $pageTitle ?? "Inventaire PC";
$bodyClass = $bodyClass ?? "bg-dark";
$pageStyles = $pageStyles ?? "";
?>
<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="assets/css/style.css" rel="stylesheet">

  <!-- Page-specific styles -->
  <?php if ($pageStyles !== ""): ?>
  <style><?= $pageStyles ?></style>
  <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, "UTF-8") ?>">

<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<?php if (isset($_SESSION["user_id"])): ?>
<nav class="navbar navbar-dark bg-black border-bottom border-secondary px-3 py-2">
  <a class="navbar-brand fw-bold" href="pcs.php">
    <i class="bi bi-pc-display text-primary"></i> Inventaire PC
  </a>
  <div class="d-flex align-items-center gap-3">
    <?php if (!empty($_SESSION["is_admin"])): ?>
      <a href="admin_users.php" class="text-decoration-none text-muted small">
        <i class="bi bi-people"></i> Utilisateurs
      </a>
    <?php endif; ?>
    <span class="text-muted small">
      <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION["username"] ?? "", ENT_QUOTES, "UTF-8") ?>
    </span>
    <a href="logout.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-box-arrow-right"></i> Déconnexion
    </a>
  </div>
</nav>
<?php endif; ?>
