<?php
// Inclusion de la garde de session + helpers communs (e, require_perm, csrf_*)
require __DIR__ . "/../includes/auth.php";

// Vérifie que l'utilisateur connecté est administrateur.
// Si ce n'est pas le cas, affiche une page 403 et stoppe l'exécution.
require_perm("is_admin");

// Ouvre la connexion PDO à la base de données (variable $pdo disponible ensuite)
require __DIR__ . "/../includes/db.php";


// ── Constante sentinelle ───────────────────────────────────────────────────────
// Valeur utilisée pour représenter les PCs sans utilisateur attribué.
// Définie ici comme constante pour éviter les fautes de frappe si on la compare
// plusieurs fois dans la page (PHP lèverait une erreur si on se trompe sur une constante,
// contrairement à une chaîne littérale).
const UNASSIGNED = '— Non attribué —';


// ── Requête principale ─────────────────────────────────────────────────────────
// Une seule requête SQL récupère tout ce qu'on affiche :
// nombre de PCs par utilisateur + décompte par statut.
// La constante UNASSIGNED est passée en paramètre PDO pour éviter de dupliquer
// la chaîne littérale dans le SQL (elle est liée deux fois : SELECT + GROUP BY).
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


// ── Calcul des métriques résumé ────────────────────────────────────────────────

// array_column($rows, 'nb_pcs') extrait uniquement la colonne 'nb_pcs' du tableau $rows.
// array_sum() additionne tous ses éléments → total de PCs sans requête SQL supplémentaire.
$totalPcs = array_sum(array_column($rows, 'nb_pcs'));

// array_filter() garde uniquement les lignes qui passent la fonction de test.
// Ici : on garde les lignes dont l'utilisateur n'est PAS la valeur sentinelle UNASSIGNED.
// count() sur le résultat donne le nombre d'utilisateurs réels (hors "Non attribué").
$nbUtilisateurs = count(array_filter($rows, fn($r) => $r['utilisateur'] !== UNASSIGNED));

// $rows est déjà trié par nb_pcs DESC, donc $rows[0] est l'utilisateur avec le plus de PCs.
// (int) convertit la valeur string retournée par PDO en entier PHP.
// Si le tableau est vide (aucun PC en BDD), on met 1 par défaut pour éviter une division par zéro plus loin.
$maxPcs   = !empty($rows) ? (int)$rows[0]['nb_pcs'] : 1;
$rowCount = count($rows);


// ── Préparation de l'affichage ─────────────────────────────────────────────────
$pageTitle  = "Admin — Stats par utilisateur"; // Titre affiché dans l'onglet navigateur
$activePage = "admin_stats_utilisateurs";       // Indique au sidebar quel lien mettre en surbrillance
require __DIR__ . "/../partials/header.php";    // Affiche le HTML de début de page + sidebar
?>

<div class="container-fluid py-4"><!-- Conteneur pleine largeur avec padding vertical -->

  <!-- En-tête de page : titre à gauche, bouton retour à droite -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">
      <i class="bi bi-bar-chart-line text-primary"></i> PCs par utilisateur
    </h3>
    <a href="/pcs.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-list-ul"></i> Voir l'inventaire
    </a>
  </div>

  <!-- ── Cartes résumé ────────────────────────────────────────────────────── -->
  <!-- Bootstrap grid : 3 cartes côte à côte sur grand écran, empilées sur mobile -->
  <div class="row g-3 mb-4"><!-- g-3 = gutter (espacement) entre colonnes -->

    <!-- Carte 1 : Total PCs -->
    <div class="col-sm-6 col-lg-4"><!-- Demi-largeur sur sm, tiers sur lg -->
      <div class="card shadow-sm h-100"><!-- h-100 : hauteur égale entre les cartes -->
        <div class="card-body d-flex align-items-center gap-3"><!-- Icône + texte côte à côte -->
          <div class="rounded-3 p-3 bg-primary bg-opacity-10"><!-- Fond bleu transparent arrondi -->
            <i class="bi bi-pc-display fs-3 text-primary"></i><!-- Icône Bootstrap Icons -->
          </div>
          <div>
            <div class="text-muted small">Total PCs</div>
            <div class="fs-3 fw-bold"><?= $totalPcs ?></div><!-- Valeur calculée en PHP -->
          </div>
        </div>
      </div>
    </div>

    <!-- Carte 2 : Utilisateurs distincts ayant au moins un PC -->
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

    <!-- Carte 3 : Maximum de PCs pour un seul utilisateur -->
    <div class="col-sm-6 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-3 p-3 bg-warning bg-opacity-10">
            <i class="bi bi-trophy fs-3 text-warning"></i>
          </div>
          <div>
            <div class="text-muted small">Maximum par utilisateur</div>
            <div class="fs-3 fw-bold">
              <?= $maxPcs ?> PC<?= $maxPcs > 1 ? 's' : '' ?>
              <!-- Pluriel conditionnel : "PC" si 1, "PCs" si > 1 -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Tableau principal ─────────────────────────────────────────────────── -->
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        Répartition des PCs
        <!-- Affichage du nombre de lignes avec accord pluriel -->
        (<?= $rowCount ?> entrée<?= $rowCount > 1 ? 's' : '' ?>)
      </h6>

      <!-- Champ de recherche live : filtrage des lignes sans rechargement de page -->
      <div class="input-group input-group-sm" style="max-width:260px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <!-- id="searchInput" est ciblé par le JavaScript en bas de page -->
        <input type="text" id="searchInput" class="form-control" placeholder="Filtrer…">
      </div>
    </div>

    <div class="card-body p-0"><!-- p-0 : pas de padding pour que le tableau soit bord à bord -->
      <div class="table-responsive"><!-- Ajoute un scroll horizontal sur petits écrans -->
        <!-- id="statsTable" ciblé par le JS pour le filtrage -->
        <table class="table table-hover align-middle mb-0" id="statsTable">
          <thead>
            <tr>
              <th style="width:2rem">#</th><!-- Numéro de rang, largeur fixe courte -->
              <th>Utilisateur</th>
              <th style="width:9rem" class="text-end pe-3">Nb PCs</th><!-- Aligné à droite -->
              <th>Répartition</th><!-- Barre de progression -->
              <th>Statuts</th><!-- Badges par statut -->
              <th class="text-center">Voir</th><!-- Bouton lien vers inventaire filtré -->
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $r):
                // $i est l'index 0-based du tableau PHP → +1 pour afficher 1, 2, 3...
                $nb = (int)$r['nb_pcs'];

                // Calcule le pourcentage par rapport au maximum pour la barre de progression.
                // Exemple : si l'utilisateur a 3 PCs et le max est 10 → 30%.
                // $maxPcs est toujours >= 1 (garanti ligne 40), pas de risque de division par zéro.
                $pct = round($nb / $maxPcs * 100);

                // Détecte si cette ligne représente les PCs sans utilisateur attribué.
                // On compare à la constante UNASSIGNED pour éviter une faute dans la chaîne.
                $isUnknown = ($r['utilisateur'] === UNASSIGNED);
            ?>
            <tr
              <?php // data-name stocke le nom en minuscules pour le filtrage JS insensible à la casse ?>
              data-name="<?= e(strtolower($r['utilisateur'])) ?>"
            >
              <!-- Colonne rang -->
              <td class="text-muted small"><?= $i + 1 ?></td>

              <!-- Colonne utilisateur : style différent selon connu ou non attribué -->
              <td>
                <?php if ($isUnknown): ?>
                  <!-- Style italique grisé pour "Non attribué" -->
                  <span class="text-muted fst-italic"><?= e($r['utilisateur']) ?></span>
                <?php else: ?>
                  <!-- Icône personne + nom en gras pour les vrais utilisateurs -->
                  <i class="bi bi-person-fill text-secondary me-1"></i>
                  <strong><?= e($r['utilisateur']) ?></strong>
                  <!-- e() échappe le nom pour éviter tout XSS si le nom contient des caractères spéciaux -->
                <?php endif; ?>
              </td>

              <!-- Colonne nombre de PCs, aligné à droite, en gras -->
              <td class="text-end pe-3 fw-bold"><?= $nb ?></td>

              <!-- Colonne barre de progression relative -->
              <td style="min-width:140px">
                <!-- title affiche le pourcentage en tooltip au survol -->
                <div class="progress" style="height:8px" title="<?= $pct ?>% du maximum">
                  <!-- La largeur CSS est calculée dynamiquement selon le ratio de cet utilisateur -->
                  <!-- bg-secondary pour "Non attribué", bg-primary pour les vrais utilisateurs -->
                  <div class="progress-bar <?= $isUnknown ? 'bg-secondary' : 'bg-primary' ?>"
                       style="width:<?= $pct ?>%"></div>
                </div>
              </td>

              <!-- Colonne badges de statut : on n'affiche un badge que si le compteur est > 0 -->
              <td>
                <div class="d-flex flex-wrap gap-1"><!-- flex-wrap permet le retour à la ligne sur petit écran -->
                  <?php if ((int)$r['en_service'] > 0): ?>
                    <!-- (int) : la valeur SQL SUM() revient comme string, on force en entier -->
                    <span class="badge bg-success"><?= (int)$r['en_service'] ?> service</span>
                  <?php endif; ?>
                  <?php if ((int)$r['en_stock'] > 0): ?>
                    <span class="badge bg-info text-dark"><?= (int)$r['en_stock'] ?> stock</span>
                  <?php endif; ?>
                  <?php if ((int)$r['en_reparation'] > 0): ?>
                    <span class="badge bg-warning text-dark"><?= (int)$r['en_reparation'] ?> réparation</span>
                  <?php endif; ?>
                  <?php if ((int)$r['retire'] > 0): ?>
                    <span class="badge bg-secondary"><?= (int)$r['retire'] ?> retiré</span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Colonne bouton "Voir" : uniquement pour les vrais utilisateurs -->
              <td class="text-center">
                <?php if (!$isUnknown): ?>
                  <!-- urlencode() encode le nom pour une URL valide (espaces → %20, etc.) -->
                  <!-- Ce lien ouvre pcs.php avec le champ de recherche pré-rempli -->
                  <a href="/pcs.php?search=<?= urlencode($r['utilisateur']) ?>"
                     class="btn btn-sm btn-outline-primary"
                     title="Voir les PCs de <?= e($r['utilisateur']) ?>">
                    <i class="bi bi-search"></i>
                  </a>
                <?php endif; ?>
                <!-- Pas de bouton pour "Non attribué" : la recherche vide ne filtrerait rien d'utile -->
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
// $pageScripts est lu par partials/footer.php juste avant </body>.
// On utilise un heredoc (<<<'JS' ... JS) pour écrire du JS multi-lignes sans échapper les guillemets.
$pageScripts = <<<'JS'
<script>
// Filtrage instantané du tableau sans rechargement de page.
// On écoute l'événement 'input' (déclenché à chaque frappe dans le champ).
document.getElementById('searchInput').addEventListener('input', function () {
  // this.value : texte saisi par l'utilisateur
  // toLowerCase().trim() : on normalise pour une recherche insensible à la casse
  const q = this.value.toLowerCase().trim();

  // querySelectorAll retourne tous les <tr> du corps du tableau
  document.querySelectorAll('#statsTable tbody tr').forEach(tr => {
    // data-name est l'attribut HTML qu'on a mis sur chaque <tr> (nom en minuscules)
    // includes(q) retourne true si le nom contient la saisie → on affiche la ligne
    // '' = visible (valeur par défaut), 'none' = ligne cachée
    tr.style.display = tr.dataset.name.includes(q) ? '' : 'none';
  });
});
</script>
JS;
require __DIR__ . "/../partials/footer.php"; // Affiche le footer HTML + Bootstrap JS + $pageScripts
