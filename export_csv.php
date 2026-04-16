<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

$q      = trim($_GET["q"] ?? "");
$statut = $_GET["statut"] ?? "";
$arch   = $_GET["arch"] ?? "";
$marque = $_GET["marque"] ?? "";

$where  = "WHERE 1=1 ";
$params = [];

if ($q !== "") {
    $where .= "AND (
        hostname LIKE :q1 OR serial LIKE :q2 OR marque LIKE :q3 OR modele LIKE :q4 OR utilisateur LIKE :q5
        OR os LIKE :q6 OR os_version LIKE :q7 OR domaine LIKE :q8
    ) ";
    $qVal = "%{$q}%";
    for ($i = 1; $i <= 8; $i++) $params[":q$i"] = $qVal;
}

if (in_array($statut, PC_STATUTS, true) && $statut !== "") {
    $where .= "AND statut = :statut ";
    $params[":statut"] = $statut;
}

if (in_array($arch, PC_ARCH, true) && $arch !== "") {
    $where .= "AND architecture = :arch ";
    $params[":arch"] = $arch;
}

if ($marque !== "") {
    $where .= "AND marque = :marque ";
    $params[":marque"] = $marque;
}

$sql  = "SELECT id, hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut, remarques, created_at, updated_at FROM pcs $where ORDER BY updated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pcs = $stmt->fetchAll();

// Load custom fields
$customFields = get_custom_fields($pdo);
$customData   = [];
if (!empty($customFields) && !empty($pcs)) {
    $pcIds = array_column($pcs, 'id');
    $ph    = sql_placeholders(count($pcIds));
    $stmtCf = $pdo->prepare("SELECT pc_id, field_name, field_value FROM pc_custom_data WHERE pc_id IN ($ph)");
    $stmtCf->execute($pcIds);
    foreach ($stmtCf->fetchAll() as $row) {
        $customData[$row['pc_id']][$row['field_name']] = $row['field_value'];
    }
}

// CSV output
$filename = "inventaire_" . date("Y-m-d") . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$out = fopen("php://output", "w");
// BOM for Excel
fwrite($out, "\xEF\xBB\xBF");

// Header row
$headers = ['hostname', 'serial', 'marque', 'modele', 'utilisateur', 'os', 'os_version', 'architecture', 'domaine', 'statut', 'remarques', 'created_at', 'updated_at'];
foreach ($customFields as $cf) {
    $headers[] = $cf['field_label'];
}
fputcsv($out, $headers);

// Data rows
foreach ($pcs as $pc) {
    $row = [
        $pc['hostname'], $pc['serial'], $pc['marque'], $pc['modele'],
        $pc['utilisateur'], $pc['os'], $pc['os_version'], $pc['architecture'],
        $pc['domaine'], $pc['statut'], $pc['remarques'],
        $pc['created_at'], $pc['updated_at'],
    ];
    foreach ($customFields as $cf) {
        $row[] = $customData[$pc['id']][$cf['field_name']] ?? '';
    }
    fputcsv($out, $row);
}

fclose($out);
exit;
