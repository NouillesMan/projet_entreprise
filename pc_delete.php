<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_delete");
require __DIR__ . "/includes/db.php";

csrf_check();

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

$stmt = $pdo->prepare("SELECT id FROM pcs WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    die("PC introuvable");
}

$pdo->prepare("DELETE FROM pcs WHERE id = ?")->execute([$id]);

header("Location: /pcs.php");
exit;
