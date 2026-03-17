<?php
// Fonctions utilitaires partagées entre plusieurs pages.
// Ce fichier est inclus manuellement avec require() dans les pages qui en ont besoin
// (pc_add.php, pc_edit.php), contrairement à auth.php qui est inclus partout.

if (!defined('PC_ARCH')) {
    define('PC_ARCH', ['x86', 'x64', 'arm64']);
}
if (!defined('PC_STATUTS')) {
    define('PC_STATUTS', ['En service', 'En stock', 'En réparation', 'Retiré']);
}

/**
 * Retourne la classe Bootstrap bg-* correspondant au statut d'un PC.
 */
function statut_badge_class(string $statut): string
{
    return match($statut) {
        'En service'   => 'success',
        'En stock'     => 'info',
        'En réparation' => 'warning',
        'Retiré'       => 'secondary',
        default        => 'secondary',
    };
}

/**
 * Charge les champs personnalisés visibles depuis la base de données,
 * dans l'ordre d'affichage défini par l'admin.
 *
 * Les "champs personnalisés" sont des colonnes supplémentaires créées par l'admin
 * via admin/fields.php. Leurs valeurs sont stockées dans la table pc_custom_data,
 * séparément des colonnes fixes de la table pcs.
 *
 * @param PDO $pdo  La connexion base de données (injectée pour éviter une globale)
 * @return array    Tableau de lignes : [field_name, field_label, field_type, is_required]
 */
function get_custom_fields(PDO $pdo): array
{
    // On sélectionne uniquement les colonnes utiles pour afficher et valider le formulaire.
    //
    // NOT IN (...) : exclut les champs dits "noyau", ceux déjà présents comme colonnes
    // fixes dans la table pcs. Les inclure ici doublerait les champs dans le formulaire.
    //
    // is_visible = 1 : l'admin a activé ce champ via admin/fields.php.
    // Un champ desactivé (is_visible = 0) reste en BDD mais n'apparaît pas.
    //
    // ORDER BY display_order : ordre numérique défini par l'admin pour trier les champs.
    //
    // fetchAll() retourne tous les résultats sous forme de tableau PHP indexé.
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
