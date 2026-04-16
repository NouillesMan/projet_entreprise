<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_check(): void
{
    if (
        empty($_POST['_csrf_token']) ||
        !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'])
    ) {
        http_response_code(403);
        die("Jeton CSRF invalide. Veuillez rafraîchir la page et réessayer.");
    }
}

function require_perm(string $perm): void
{
    if (empty($_SESSION[$perm])) {
        http_response_code(403);

        $pageTitle  = "Accès refusé";
        $activePage = "";
        require __DIR__ . "/../partials/header.php";

        echo '<div class="container py-5 text-center">';
        echo '<i class="bi bi-shield-lock display-1 text-danger"></i>';
        echo '<h1 class="mt-3">Accès refusé</h1>';
        echo '<p class="text-muted">Vous n\'avez pas la permission d\'accéder à cette page.</p>';
        echo '<a href="/pcs.php" class="btn btn-outline-secondary mt-2"><i class="bi bi-arrow-left"></i> Retour</a>';
        echo '</div>';

        require __DIR__ . "/../partials/footer.php";
        exit;
    }
}
