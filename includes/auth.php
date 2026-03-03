<?php
// Ce fichier est inclus en PREMIER dans toutes les pages protégées.
// Il joue trois rôles : vérifier que l'utilisateur est connecté,
// fournir la protection CSRF, et exposer les helpers communs (e, require_perm).

// session_status() retourne PHP_SESSION_NONE si aucune session n'est active.
// On ne démarre la session que si elle n'existe pas encore,
// ce qui évite l'erreur "session already started" si le fichier est inclus deux fois.
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Démarre la session PHP (lit/écrit le cookie PHPSESSID)
}

// Si la clé 'user_id' n'existe pas en session, l'utilisateur n'est pas connecté.
// On l'envoie immédiatement vers la page de login avec un header HTTP Location.
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php'); // Redirection côté navigateur (code 302)
    exit; // OBLIGATOIRE après header() : stoppe l'exécution du reste du script
}

/**
 * Échappe une valeur pour un affichage HTML sécurisé.
 *
 * Convertit les caractères spéciaux HTML en entités, ce qui empêche
 * les attaques XSS (injection de balises ou de scripts dans la page).
 * Ex : e('<script>') retourne '&lt;script&gt;'
 */
function e($v): string
{
    // (string)$v : force la conversion en chaîne (sécurité si $v est null ou int)
    // ENT_QUOTES : échappe aussi les guillemets simples ' et doubles "
    // 'UTF-8'    : encodage utilisé pour l'interprétation des caractères
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère (ou retourne) le jeton CSRF de la session courante.
 *
 * Le CSRF (Cross-Site Request Forgery) est une attaque où un site malveillant
 * pousse l'utilisateur à soumettre un formulaire à son insu.
 * Le jeton est une valeur aléatoire secrète connue uniquement du serveur
 * et du navigateur légitime.
 */
function csrf_token(): string
{
    // empty() retourne true si la clé n'existe pas ou vaut '' / null / 0
    // On ne génère un nouveau jeton que si la session n'en a pas encore.
    if (empty($_SESSION['_csrf_token'])) {
        // random_bytes(32) génère 32 octets cryptographiquement aléatoires
        // bin2hex() les convertit en 64 caractères hexadécimaux lisibles
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    // On retourne toujours le même jeton pour toute la durée de la session
    return $_SESSION['_csrf_token'];
}

/**
 * Retourne un champ HTML caché contenant le jeton CSRF.
 *
 * Ce champ est inséré dans chaque formulaire POST via <?= csrf_field() ?>
 * Lors de la soumission, le serveur compare ce jeton avec celui en session.
 */
function csrf_field(): string
{
    // On construit manuellement la balise <input hidden> avec le jeton.
    // On échappe la valeur avec htmlspecialchars pour éviter tout XSS dans l'attribut.
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Vérifie le jeton CSRF de la requête POST en cours.
 * Bloque avec une erreur 403 si le jeton est absent ou invalide.
 */
function csrf_check(): void
{
    if (
        // Si le champ _csrf_token est absent du formulaire soumis...
        empty($_POST['_csrf_token']) ||
        // ...ou si la valeur ne correspond pas au jeton en session.
        // hash_equals() compare en temps constant pour éviter les attaques timing
        // (contrairement à == qui s'arrête dès la première différence).
        // L'opérateur ?? '' évite un warning si la clé session n'existe pas.
        !hash_equals($_SESSION['_csrf_token'] ?? '', $_POST['_csrf_token'])
    ) {
        http_response_code(403); // Envoie le code HTTP "Accès refusé"
        die("Jeton CSRF invalide. Veuillez rafraîchir la page et réessayer.");
        // die() stoppe immédiatement le script et affiche le message
    }
}

/**
 * Bloque l'accès avec une page 403 si l'utilisateur n'a pas la permission demandée.
 *
 * Les permissions possibles sont des colonnes de la table users :
 * 'can_view', 'can_add', 'can_edit', 'can_delete', 'is_admin'
 * Elles sont stockées en session lors de la connexion.
 */
function require_perm(string $perm): void
{
    // empty() retourne true si la permission vaut 0, '', null ou n'existe pas en session.
    // Cela couvre le cas où l'utilisateur n'a pas la permission (valeur 0 en BDD).
    if (empty($_SESSION[$perm])) {
        http_response_code(403); // Indique au navigateur que l'accès est interdit

        // On prépare les variables nécessaires à l'inclusion du header
        $pageTitle  = "Accès refusé";
        $activePage = ""; // Aucun lien du sidebar n'est mis en surbrillance

        // On affiche quand même le header (sidebar, CSS) pour une page cohérente
        require __DIR__ . "/../partials/header.php";

        // Affichage de la page d'erreur centrée avec une icône et un bouton retour
        echo '<div class="container py-5 text-center">';
        echo '<i class="bi bi-shield-lock display-1 text-danger"></i>';
        echo '<h1 class="mt-3">Accès refusé</h1>';
        echo '<p class="text-muted">Vous n\'avez pas la permission d\'accéder à cette page.</p>';
        echo '<a href="/pcs.php" class="btn btn-outline-secondary mt-2"><i class="bi bi-arrow-left"></i> Retour</a>';
        echo '</div>';

        require __DIR__ . "/../partials/footer.php"; // Fermeture HTML propre
        exit; // On stoppe le script : la page protégée ne s'exécute pas
    }
}
