<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_delete");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

csrf_check();

$ids = (array)($_POST['ids'] ?? []);

// Fetch hostnames before deletion for logging
$cleanIds = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
$hostnames = [];
if (!empty($cleanIds)) {
    $ph = sql_placeholders(count($cleanIds));
    $stmtNames = $pdo->prepare("SELECT id, hostname FROM pcs WHERE id IN ($ph)");
    $stmtNames->execute($cleanIds);
    foreach ($stmtNames->fetchAll() as $row) {
        $hostnames[] = $row['hostname'];
        log_pc_history($pdo, (int)$row['id'], 'deleted');
    }
}

try {
    $deleted = delete_pcs($pdo, $ids);
} catch (RuntimeException $e) {
    header("Location: /pcs.php?msg=delete_error");
    exit;
}

if ($deleted === 0) {
    header("Location: /pcs.php");
    exit;
}

log_activity($pdo, 'bulk_delete', 'pc', null, '', "Suppression de $deleted PC(s) : " . implode(', ', $hostnames));

header("Location: /pcs.php?msg=deleted&n=" . $deleted);
exit;
