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
