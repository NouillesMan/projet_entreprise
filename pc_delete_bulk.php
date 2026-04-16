<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_delete");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

csrf_check();

$ids = (array)($_POST['ids'] ?? []);

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

header("Location: /pcs.php?msg=deleted&n=" . $deleted);
exit;
