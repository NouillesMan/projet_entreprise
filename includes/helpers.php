<?php
// Shared utility functions.

/**
 * Load visible custom fields (excluding core fields), ordered for display.
 */
function get_custom_fields(PDO $pdo): array
{
    return $pdo->query(
        "SELECT field_name, field_label, field_type, is_required
         FROM custom_fields
         WHERE field_name NOT IN (
             'hostname','serial','marque','modele','utilisateur',
             'os','os_version','architecture','domaine','statut','remarques'
         )
         AND is_visible = 1
         ORDER BY display_order"
    )->fetchAll();
}
