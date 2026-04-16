<?php
if (!defined('PC_ARCH')) {
    define('PC_ARCH', ['x86', 'x64', 'arm64']);
}
if (!defined('PC_STATUTS')) {
    define('PC_STATUTS', ['En service', 'En stock', 'En réparation', 'Retiré']);
}
if (!defined('OS_FAMILIES')) {
    define('OS_FAMILIES', ['Windows', 'Linux', 'macOS', 'Autre']);
}
if (!defined('FIELD_TYPES')) {
    define('FIELD_TYPES', [
        'text'     => 'Texte',
        'number'   => 'Nombre',
        'date'     => 'Date',
        'textarea' => 'Textarea',
        'select'   => 'Liste déroulante',
    ]);
}

if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
}
if (!defined('LOGIN_MAX_ATTEMPTS')) {
    define('LOGIN_MAX_ATTEMPTS', 5);
}
if (!defined('LOGIN_LOCKOUT_SECONDS')) {
    define('LOGIN_LOCKOUT_SECONDS', 300);
}

function validate_password(string $password): array {
    $errors = [];
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Le mot de passe doit contenir au moins " . PASSWORD_MIN_LENGTH . " caractères.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    }
    return $errors;
}

function statut_badge_class(string $statut): string
{
    return match($statut) {
        'En service'    => 'success',
        'En stock'      => 'info',
        'En réparation' => 'warning',
        default         => 'secondary',
    };
}

function deriveOsGroup(string $os): string {
    $l = strtolower($os);
    if (str_contains($l, 'windows')) return 'Windows';
    if (str_contains($l, 'macos') || str_contains($l, 'mac os')) return 'macOS';
    if (str_contains($l, 'ubuntu') || str_contains($l, 'debian') || str_contains($l, 'fedora') ||
        str_contains($l, 'centos') || str_contains($l, 'red hat') || str_contains($l, 'rhel')  ||
        str_contains($l, 'arch')   || str_contains($l, 'opensuse')|| str_contains($l, 'linux') ||
        str_contains($l, 'mint')   || str_contains($l, 'suse'))    return 'Linux';
    return 'Autre';
}

function get_custom_fields(PDO $pdo): array
{
    return $pdo->query(
        "SELECT field_name, field_label, field_type, is_required
         FROM custom_fields
         WHERE field_name NOT IN (
             'hostname','serial','marque','modele','utilisateur',
             'os','os_version','architecture','domaine','statut','remarques'
         )
         AND is_visible = 1
         ORDER BY display_order"
    )->fetchAll();
}

function sql_placeholders(int $n): string {
    return $n > 0 ? implode(',', array_fill(0, $n, '?')) : '';
}

function delete_pcs(PDO $pdo, array $ids): int {
    $ids = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
    if (empty($ids)) return 0;
    try {
        $pdo->beginTransaction();
        $ph = sql_placeholders(count($ids));
        $pdo->prepare("DELETE FROM pc_custom_data WHERE pc_id IN ($ph)")->execute($ids);
        $stmt = $pdo->prepare("DELETE FROM pcs WHERE id IN ($ph)");
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();
        $pdo->commit();
        return $deleted;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("delete_pcs failed: " . $e->getMessage());
        throw new RuntimeException("Erreur lors de la suppression");
    }
}

function render_os_version_options(array $versionsByFamily, string $current): string {
    $html = '';
    foreach ($versionsByFamily as $family => $versions) {
        $group = $family !== '';
        if ($group) {
            $html .= '<optgroup label="' . e($family) . '">';
        }
        foreach ($versions as $version) {
            $selected = $current === $version ? ' selected' : '';
            $html .= '<option value="' . e($version) . '"' . $selected . '>' . e($version) . '</option>';
        }
        if ($group) {
            $html .= '</optgroup>';
        }
    }
    return $html;
}

function os_version_in_list(array $versionsByFamily, string $current): bool {
    foreach ($versionsByFamily as $versions) {
        if (in_array($current, $versions, true)) return true;
    }
    return false;
}

function log_activity(PDO $pdo, string $action, string $target_type, ?int $target_id, string $target_label, string $details = ''): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO activity_log (user_id, username, action, target_type, target_id, target_label, details)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $_SESSION['username'] ?? 'system',
        $action,
        $target_type,
        $target_id,
        $target_label,
        $details,
    ]);
}

function log_pc_history(PDO $pdo, int $pc_id, string $action, ?array $changes = null): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO pc_history (pc_id, user_id, username, action, changes)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $pc_id,
        $_SESSION['user_id'] ?? null,
        $_SESSION['username'] ?? 'system',
        $action,
        $changes !== null ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
    ]);
}
