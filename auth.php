<?php
// Session guard — include at the top of every protected page.
// Also provides require_perm() to enforce specific permissions.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * Abort with a 403 page if the logged-in user lacks the given permission.
 * $perm can be: 'can_view', 'can_add', 'can_edit', 'can_delete', 'is_admin'
 */
function require_perm(string $perm): void
{
    if (empty($_SESSION[$perm])) {
        http_response_code(403);
        $pageTitle = "Accès refusé";
        require __DIR__ . "/partials/header.php";
        echo '<div class="container py-5 text-center">';
        echo '<i class="bi bi-shield-lock display-1 text-danger"></i>';
        echo '<h1 class="mt-3">Accès refusé</h1>';
        echo '<p class="text-muted">Vous n\'avez pas la permission d\'accéder à cette page.</p>';
        echo '<a href="pcs.php" class="btn btn-outline-secondary mt-2"><i class="bi bi-arrow-left"></i> Retour</a>';
        echo '</div>';
        require __DIR__ . "/partials/footer.php";
        exit;
    }
}
