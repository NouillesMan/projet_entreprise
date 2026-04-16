<?php
/**
 * Panneau de notifications repliable.
 *
 * Les pages alimentent le tableau $flash avant d'inclure ce partial :
 *   $flash[] = ['type' => 'success', 'msg' => 'Texte...'];
 *   $flash[] = ['type' => 'danger',  'msg' => 'Erreur...'];
 *
 * Types supportes : success, danger, warning, info
 */
if (empty($flash)) return;

$iconMap = [
    'success' => 'bi-check-circle-fill',
    'danger'  => 'bi-exclamation-triangle-fill',
    'warning' => 'bi-exclamation-circle-fill',
    'info'    => 'bi-info-circle-fill',
];

$hasErrors  = false;
$hasSuccess = false;
foreach ($flash as $f) {
    if ($f['type'] === 'danger' || $f['type'] === 'warning') $hasErrors = true;
    else $hasSuccess = true;
}

if ($hasErrors && $hasSuccess) {
    $panelIcon  = 'bi-bell-fill';
    $panelClass = 'warning';
    $panelTitle = count($flash) . ' notification' . (count($flash) > 1 ? 's' : '');
} elseif ($hasErrors) {
    $panelIcon  = 'bi-exclamation-triangle-fill';
    $panelClass = 'danger';
    $panelTitle = count($flash) . ' erreur' . (count($flash) > 1 ? 's' : '');
} else {
    $panelIcon  = 'bi-check-circle-fill';
    $panelClass = 'success';
    $panelTitle = count($flash) . ' notification' . (count($flash) > 1 ? 's' : '');
}

$collapseId = 'flashPanel';
?>
<div class="flash-panel mb-3">
  <div class="d-flex align-items-center gap-2 flash-toggle text-<?= $panelClass ?>"
       role="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>"
       aria-expanded="false" aria-controls="<?= $collapseId ?>">
    <i class="bi <?= $panelIcon ?>"></i>
    <strong><?= e($panelTitle) ?></strong>
    <i class="bi bi-chevron-down flash-chevron ms-auto"></i>
    <button type="button" class="btn-close btn-close-white ms-2 flash-dismiss" aria-label="Fermer"></button>
  </div>
  <div class="collapse mt-2" id="<?= $collapseId ?>">
    <div class="list-group list-group-flush flash-list">
      <?php foreach ($flash as $f): ?>
        <div class="list-group-item bg-transparent border-secondary border-opacity-25 py-2 d-flex align-items-start gap-2">
          <i class="bi <?= $iconMap[$f['type']] ?? 'bi-info-circle-fill' ?> text-<?= e($f['type']) ?> mt-1 flex-shrink-0"></i>
          <div><?= $f['msg'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
