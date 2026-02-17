<?php
require __DIR__ . "/auth.php";
require_perm("can_delete");
require __DIR__ . "/db.php";

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

$stmt = $pdo->prepare("DELETE FROM pcs WHERE id = ?");
$stmt->execute([$id]);

header("Location: pcs.php");
exit;
