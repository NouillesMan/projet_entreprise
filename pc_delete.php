<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_delete");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

csrf_check();

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

// Fetch PC data before deletion for logging
$stmtPc = $pdo->prepare("SELECT hostname FROM pcs WHERE id = ?");
$stmtPc->execute([$id]);
$pcData = $stmtPc->fetch();

try {
    log_pc_history($pdo, $id, 'deleted');
    $deleted = delete_pcs($pdo, [$id]);
} catch (RuntimeException $e) {
    header("Location: /pcs.php?msg=delete_error");
    exit;
}

if ($deleted === 0) { die("PC introuvable"); }

log_activity($pdo, 'delete', 'pc', $id, $pcData['hostname'] ?? "PC #$id");

header("Location: /pcs.php?msg=deleted");
exit;
