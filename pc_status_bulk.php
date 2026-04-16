<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_edit");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

csrf_check();

$ids       = (array)($_POST['ids'] ?? []);
$newStatut = $_POST['statut'] ?? '';

if (!in_array($newStatut, PC_STATUTS, true)) {
    header("Location: /pcs.php?msg=status_error");
    exit;
}

$cleanIds = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
if (empty($cleanIds)) {
    header("Location: /pcs.php");
    exit;
}

$ph   = sql_placeholders(count($cleanIds));
$stmt = $pdo->prepare("UPDATE pcs SET statut = ? WHERE id IN ($ph)");
$stmt->execute(array_merge([$newStatut], $cleanIds));
$updated = $stmt->rowCount();

// Log history for each PC
foreach ($cleanIds as $pcId) {
    log_pc_history($pdo, $pcId, 'updated', ['statut' => ['old' => '(bulk)', 'new' => $newStatut]]);
}
log_activity($pdo, 'bulk_status', 'pc', null, '', "$updated PC(s) -> $newStatut");

header("Location: /pcs.php?msg=status_updated&n=$updated&s=" . urlencode($newStatut));
exit;
