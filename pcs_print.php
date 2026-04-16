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

$sql  = "SELECT hostname, serial, marque, modele, utilisateur, os, os_version, architecture, domaine, statut FROM pcs $where ORDER BY hostname ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pcs = $stmt->fetchAll();

// Build filter description
$filterDesc = [];
if ($q !== "")      $filterDesc[] = "Recherche : " . htmlspecialchars($q, ENT_QUOTES, 'UTF-8');
if ($statut !== "") $filterDesc[] = "Statut : " . htmlspecialchars($statut, ENT_QUOTES, 'UTF-8');
if ($arch !== "")   $filterDesc[] = "Arch : " . htmlspecialchars($arch, ENT_QUOTES, 'UTF-8');
if ($marque !== "") $filterDesc[] = "Marque : " . htmlspecialchars($marque, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Inventaire PC — Impression</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 9pt; color: #000; padding: 15px; }
    h1 { font-size: 14pt; margin-bottom: 2px; }
    .subtitle { color: #666; font-size: 9pt; margin-bottom: 12px; }
    .filters { color: #666; font-size: 8pt; margin-bottom: 8px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #333; padding: 3px 6px; text-align: left; }
    th { background: #eee; font-weight: 600; white-space: nowrap; }
    td { font-size: 8pt; }
    .count { font-weight: normal; color: #666; }
    .no-print { margin-top: 15px; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
      @page { margin: 10mm; }
    }
  </style>
</head>
<body>

<h1>Inventaire PC <span class="count">(<?= count($pcs) ?> PC<?= count($pcs) > 1 ? 's' : '' ?>)</span></h1>
<div class="subtitle">Imprime le <?= date('d/m/Y H:i') ?></div>
<?php if (!empty($filterDesc)): ?>
<div class="filters">Filtres : <?= implode(' | ', $filterDesc) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Hostname</th>
      <th>Serial</th>
      <th>Marque</th>
      <th>Modele</th>
      <th>Utilisateur</th>
      <th>OS</th>
      <th>Version</th>
      <th>Arch</th>
      <th>Domaine</th>
      <th>Statut</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($pcs as $pc): ?>
    <tr>
      <td><strong><?= htmlspecialchars($pc['hostname'], ENT_QUOTES, 'UTF-8') ?></strong></td>
      <td><?= htmlspecialchars($pc['serial'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['marque'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['modele'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['utilisateur'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['os'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['os_version'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['architecture'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['domaine'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($pc['statut'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($pcs)): ?>
    <tr><td colspan="10" style="text-align:center; color:#666; padding:12px;">Aucun PC</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<div class="no-print">
  <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Imprimer</button>
  <a href="/pcs.php" style="margin-left: 8px;">Retour</a>
</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
