<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");
require __DIR__ . "/../includes/db.php";
require __DIR__ . "/../includes/helpers.php";

const UNASSIGNED = '— Non attribué —';

$stmtRows = $pdo->prepare("
    SELECT
        COALESCE(NULLIF(TRIM(utilisateur), ''), ?) AS utilisateur,
        COUNT(*) AS nb_pcs,
        SUM(statut = 'En service')    AS en_service,
        SUM(statut = 'En stock')      AS en_stock,
        SUM(statut = 'En réparation') AS en_reparation,
        SUM(statut = 'Retiré')        AS retire
    FROM pcs
    GROUP BY utilisateur
    ORDER BY nb_pcs DESC, utilisateur ASC
");
$stmtRows->execute([UNASSIGNED]);
$rows = $stmtRows->fetchAll();

$totalPcs       = array_sum(array_column($rows, 'nb_pcs'));
$nbUtilisateurs = count(array_filter($rows, fn($r) => $r['utilisateur'] !== UNASSIGNED));
$maxPcs         = !empty($rows) ? max(1, (int)$rows[0]['nb_pcs']) : 1;
$rowCount       = count($rows);

$pageTitle  = "Admin — Stats par utilisateur";
$activePage = "admin_stats_utilisateurs";
require __DIR__ . "/../partials/header.php";
?>

<div class="container-fluid py-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">
      <i class="bi bi-bar-chart-line text-primary"></i> PCs par utilisateur
    </h3>
    <a href="/pcs.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-list-ul"></i> Voir l'inventaire
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 p-3 bg-primary bg-opacity-10">
            <i class="bi bi-pc-display fs-3 text-primary"></i>
          </div>
          <div>
            <div class="text-muted small">Total PCs</div>
            <div class="fs-3 fw-bold"><?= $totalPcs ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 p-3 bg-success bg-opacity-10">
            <i class="bi bi-people fs-3 text-success"></i>
          </div>
          <div>
            <div class="text-muted small">Utilisateurs avec PCs</div>
            <div class="fs-3 fw-bold"><?= $nbUtilisateurs ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 p-3 bg-warning bg-opacity-10">
            <i class="bi bi-trophy fs-3 text-warning"></i>
          </div>
          <div>
            <div class="text-muted small">Maximum par utilisateur</div>
            <div class="fs-3 fw-bold"><?= $maxPcs ?> PC<?= $maxPcs > 1 ? 's' : '' ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        Répartition des PCs (<?= $rowCount ?> entrée<?= $rowCount > 1 ? 's' : '' ?>)
      </h6>
      <div class="input-group input-group-sm" style="max-width:260px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="searchInput" class="form-control" placeholder="Filtrer…">
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="statsTable">
          <thead>
            <tr>
              <th style="width:2rem">#</th>
              <th>Utilisateur</th>
              <th style="width:9rem" class="text-end pe-3">Nb PCs</th>
              <th>Répartition</th>
              <th>Statuts</th>
              <th class="text-center">Voir</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $r):
                $nb        = (int)$r['nb_pcs'];
                $pct       = round($nb / $maxPcs * 100);
                $isUnknown = ($r['utilisateur'] === UNASSIGNED);
            ?>
            <tr data-name="<?= e(strtolower($r['utilisateur'])) ?>">
              <td class="text-muted small"><?= $i + 1 ?></td>

              <td>
                <?php if ($isUnknown): ?>
                  <span class="text-muted fst-italic"><?= e($r['utilisateur']) ?></span>
                <?php else: ?>
                  <i class="bi bi-person-fill text-secondary me-1"></i>
                  <strong><?= e($r['utilisateur']) ?></strong>
                <?php endif; ?>
              </td>

              <td class="text-end pe-3 fw-bold"><?= $nb ?></td>

              <td style="min-width:140px">
                <div class="progress" style="height:8px" title="<?= $pct ?>% du maximum">
                  <div class="progress-bar <?= $isUnknown ? 'bg-secondary' : 'bg-primary' ?>"
                       style="width:<?= $pct ?>%"></div>
                </div>
              </td>

              <td>
                <div class="d-flex flex-wrap gap-1">
                  <?php if ((int)$r['en_service'] > 0): ?>
                    <span class="badge bg-<?= statut_badge_class('En service') ?>"><?= (int)$r['en_service'] ?> service</span>
                  <?php endif; ?>
                  <?php if ((int)$r['en_stock'] > 0): ?>
                    <span class="badge bg-<?= statut_badge_class('En stock') ?>"><?= (int)$r['en_stock'] ?> stock</span>
                  <?php endif; ?>
                  <?php if ((int)$r['en_reparation'] > 0): ?>
                    <span class="badge bg-<?= statut_badge_class('En réparation') ?>"><?= (int)$r['en_reparation'] ?> réparation</span>
                  <?php endif; ?>
                  <?php if ((int)$r['retire'] > 0): ?>
                    <span class="badge bg-<?= statut_badge_class('Retiré') ?>"><?= (int)$r['retire'] ?> retiré</span>
                  <?php endif; ?>
                </div>
              </td>

              <td class="text-center">
                <?php if (!$isUnknown): ?>
                  <a href="/pcs.php?q=<?= urlencode($r['utilisateur']) ?>"
                     class="btn btn-sm btn-outline-primary"
                     title="Voir les PCs de <?= e($r['utilisateur']) ?>">
                    <i class="bi bi-search"></i>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php
$pageScripts = <<<'JS'
<script>
document.getElementById('searchInput').addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();
  document.querySelectorAll('#statsTable tbody tr').forEach(tr => {
    tr.style.display = tr.dataset.name.includes(q) ? '' : 'none';
  });
});
</script>
JS;
require __DIR__ . "/../partials/footer.php";
