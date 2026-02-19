<?php
// Loads dropdown options from the field_options table.
// Returns arrays for: marque, modele, os, os_version.

if (!isset($pdo)) {
    require __DIR__ . "/db.php";
}

$rows = $pdo->query(
    "SELECT field_name, option_group, option_value
     FROM field_options
     ORDER BY field_name, option_group, display_order, option_value"
)->fetchAll();

$marques = [];
$modeles = [];
$os      = [];
$os_version = [];

foreach ($rows as $row) {
    $fn  = $row['field_name'];
    $grp = $row['option_group'];
    $val = $row['option_value'];

    if ($fn === 'marque') {
        $marques[] = $val;
    } elseif ($fn === 'modele') {
        $modeles[$grp][] = $val;
    } elseif ($fn === 'os') {
        $os[$grp][] = $val;
    } elseif ($fn === 'os_version') {
        $os_version[] = $val;
    }
}

return [
    'marque'     => $marques,
    'modele'     => $modeles,
    'os'         => $os,
    'os_version' => $os_version,
];
