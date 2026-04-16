<?php
$pageTitle  = $pageTitle  ?? "Inventaire PC";
$bodyClass  = $bodyClass  ?? "";
$pageStyles = $pageStyles ?? "";
$activePage = $activePage ?? "";

if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION["user_id"]);

// Fallback : e() peut ne pas encore être définie (ex : login.php n'inclut pas auth.php)
if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
?>
<?php
$theme = $_COOKIE['theme'] ?? 'dark';
if (!in_array($theme, ['dark', 'light'])) $theme = 'dark';
?>
<!doctype html>
<html lang="fr" data-bs-theme="<?= $theme ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link href="/assets/css/style.css" rel="stylesheet">

  <?php if ($pageStyles !== ""): ?>
  <style><?= $pageStyles ?></style>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">

<?php if ($isLoggedIn): ?>

<!-- Mobile top bar -->
<div class="app-topbar">
  <button class="btn btn-sm btn-outline-secondary" type="button" id="sidebarToggle" aria-label="Menu">
    <i class="bi bi-list fs-5"></i>
  </button>
  <a class="topbar-brand" href="/dashboard.php">
    <i class="bi bi-pc-display text-primary"></i> Inventaire PC
  </a>
</div>

<!-- Backdrop for mobile sidebar -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<nav class="app-sidebar" id="appSidebar">
  <a href="/dashboard.php" class="sidebar-brand">
    <i class="bi bi-pc-display text-primary fs-4"></i>
    Inventaire PC
  </a>

  <div class="sidebar-nav">
    <!-- Main navigation -->
    <div class="nav-section">Navigation</div>
    <a href="/dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="/pcs.php" class="nav-link <?= $activePage === 'pcs' ? 'active' : '' ?>">
      <i class="bi bi-list-ul"></i> Inventaire
    </a>
    <?php if (!empty($_SESSION["can_add"])): ?>
    <a href="/pc_add.php" class="nav-link <?= $activePage === 'pc_add' ? 'active' : '' ?>">
      <i class="bi bi-plus-circle"></i> Ajouter un PC
    </a>
    <?php endif; ?>

    <?php if (!empty($_SESSION["is_admin"])): ?>
    <!-- Admin section -->
    <div class="nav-section mt-3">Administration</div>
    <a href="/admin/users.php" class="nav-link <?= $activePage === 'admin_users' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Utilisateurs
    </a>
    <a href="/admin/options.php" class="nav-link <?= $activePage === 'admin_options' ? 'active' : '' ?>">
      <i class="bi bi-list-check"></i> Options
    </a>
    <a href="/admin/fields.php" class="nav-link <?= $activePage === 'admin_fields' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i> Champs
    </a>
    <a href="/admin/import.php" class="nav-link <?= $activePage === 'admin_import' ? 'active' : '' ?>">
      <i class="bi bi-upload"></i> Import
    </a>
    <a href="/admin/stats_utilisateurs.php" class="nav-link <?= $activePage === 'admin_stats_utilisateurs' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-line"></i> Stats utilisateurs
    </a>
    <a href="/admin/logs.php" class="nav-link <?= $activePage === 'admin_logs' ? 'active' : '' ?>">
      <i class="bi bi-journal-text"></i> Logs
    </a>
    <?php endif; ?>
  </div>

  <!-- User info + logout at bottom -->
  <div class="sidebar-footer">
    <div class="user-info">
      <i class="bi bi-person-circle"></i>
      <?= e($_SESSION["username"] ?? "") ?>
    </div>
    <button class="btn btn-sm btn-outline-secondary w-100 mb-2" id="themeToggle" title="Changer le theme">
      <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon-stars' ?>"></i>
      <?= $theme === 'dark' ? 'Mode clair' : 'Mode sombre' ?>
    </button>
    <a href="/logout.php" class="btn btn-sm btn-outline-secondary w-100">
      <i class="bi bi-box-arrow-right"></i> Deconnexion
    </a>
  </div>
</nav>

<main class="app-main">

<?php endif; ?>
