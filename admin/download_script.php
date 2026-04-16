<?php
require __DIR__ . "/../includes/auth.php";
require_perm("is_admin");

$allowed = [
    'linux'   => ['path' => __DIR__ . '/../scripts/collect_linux.sh',         'name' => 'collect_linux.sh'],
    'windows' => ['path' => __DIR__ . '/../scripts/core/collect_windows.ps1', 'name' => 'collect_windows.ps1'],
    'bat'     => ['path' => __DIR__ . '/../scripts/lancer_collecte.bat',      'name' => 'lancer_collecte.bat'],
];

$script = $_GET['script'] ?? '';
if (!isset($allowed[$script])) {
    http_response_code(404);
    exit("Script introuvable.");
}

$file = $allowed[$script];
if (!is_file($file['path'])) {
    http_response_code(404);
    exit("Fichier introuvable sur le serveur.");
}

// Vider le buffer avant d'envoyer les headers de téléchargement
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
header('Content-Length: ' . filesize($file['path']));
readfile($file['path']);
exit;
