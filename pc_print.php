<?php
require __DIR__ . "/includes/auth.php";
require_perm("can_view");
require __DIR__ . "/includes/db.php";
require __DIR__ . "/includes/helpers.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) { die("ID invalide"); }

$stmt = $pdo->prepare("SELECT * FROM pcs WHERE id = ?");
$stmt->execute([$id]);
$pc = $stmt->fetch();
if (!$pc) { die("PC introuvable"); }

$customFields = get_custom_fields($pdo);
$customValues = [];
if (!empty($customFields)) {
    $stmtCv = $pdo->prepare("SELECT field_name, field_value FROM pc_custom_data WHERE pc_id = ?");
    $stmtCv->execute([$id]);
    foreach ($stmtCv->fetchAll() as $row) {
        $customValues[$row['field_name']] = $row['field_value'];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Fiche PC — <?= htmlspecialchars($pc['hostname'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; padding: 20px; }
    h1 { font-size: 16pt; margin-bottom: 4px; }
    .subtitle { color: #666; font-size: 10pt; margin-bottom: 16px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
    th, td { border: 1px solid #333; padding: 6px 10px; text-align: left; }
    th { background: #eee; width: 35%; font-weight: 600; }
    .section-title { font-size: 12pt; font-weight: 600; margin: 16px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #333; }
    .no-print { margin-top: 20px; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
    }
  </style>
</head>
<body>

<h1><?= htmlspecialchars($pc['hostname'], ENT_QUOTES, 'UTF-8') ?></h1>
<div class="subtitle">Fiche PC — Imprime le <?= date('d/m/Y H:i') ?></div>

<div class="section-title">Identification</div>
<table>
  <tr><th>Hostname</th><td><?= htmlspecialchars($pc['hostname'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Serial</th><td><?= htmlspecialchars($pc['serial'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Marque</th><td><?= htmlspecialchars($pc['marque'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Modele</th><td><?= htmlspecialchars($pc['modele'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Utilisateur</th><td><?= htmlspecialchars($pc['utilisateur'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Domaine</th><td><?= htmlspecialchars($pc['domaine'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>

<div class="section-title">Systeme</div>
<table>
  <tr><th>OS</th><td><?= htmlspecialchars($pc['os'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Version OS</th><td><?= htmlspecialchars($pc['os_version'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Architecture</th><td><?= htmlspecialchars($pc['architecture'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Statut</th><td><?= htmlspecialchars($pc['statut'], ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>

<?php if (!empty($pc['remarques'])): ?>
<div class="section-title">Remarques</div>
<p><?= nl2br(htmlspecialchars($pc['remarques'], ENT_QUOTES, 'UTF-8')) ?></p>
<?php endif; ?>

<?php if (!empty($customFields)): ?>
<div class="section-title">Champs personnalises</div>
<table>
  <?php foreach ($customFields as $cf): ?>
  <tr>
    <th><?= htmlspecialchars($cf['field_label'], ENT_QUOTES, 'UTF-8') ?></th>
    <td><?= htmlspecialchars($customValues[$cf['field_name']] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<div class="section-title">Dates</div>
<table>
  <tr><th>Date de creation</th><td><?= htmlspecialchars($pc['created_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><th>Derniere modification</th><td><?= htmlspecialchars($pc['updated_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>

<div class="no-print">
  <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer;">Imprimer</button>
  <a href="/pc_view.php?id=<?= (int)$pc['id'] ?>" style="margin-left: 8px;">Retour</a>
</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
