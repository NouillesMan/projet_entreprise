# Contenu à coller dans l'Annexe 6 — Tableau de synthèse E5 (SLAM)

Session 2026 — Option SLAM

---

## Informations candidat

- **Nom :** [À COMPLÉTER]
- **Prénom :** [À COMPLÉTER]
- **N° candidat :** [À COMPLÉTER]
- **Établissement :** [À COMPLÉTER]

---

## Réalisation professionnelle n°1

**Intitulé :** Application web de gestion d'inventaire du parc informatique

**Contexte :** Développement interne pour l'équipe IT d'une organisation ne disposant d'aucun outil centralisé de suivi de son parc (PCs, laptops, serveurs). Les informations étaient auparavant dispersées dans des fichiers tableur non partagés.

**Modalité :** Seul(e)

**Période :** [À COMPLÉTER — dates de réalisation]

**Environnement technologique :**
- PHP 8.0 natif, MariaDB, PDO
- Bootstrap 5.3, JavaScript ES6, SortableJS
- Docker Compose (Apache/PHP + MariaDB)
- Git, Visual Studio Code

**Compétences mobilisées (bloc 2 — SLAM) :**
- ☑ Concevoir et développer une solution applicative
- ☑ Assurer la maintenance corrective ou évolutive d'une solution applicative
- ☑ Gérer les données

**Résumé des fonctionnalités :**
Authentification sécurisée avec permissions granulaires (5 rôles indépendants), CRUD complet sur les postes avec filtres multi-critères et tri, suppression en lot multi-pages, import CSV en UPSERT, tableau de bord statistique, administration des champs personnalisés et des listes déroulantes (drag & drop), scripts de collecte automatique PowerShell et Bash.

**Sécurité :** sessions PHP avec `session_regenerate_id()`, bcrypt, tokens CSRF (`random_bytes` + `hash_equals`), 100 % PDO paramétré, `htmlspecialchars` systématique, whitelist des colonnes de tri, vérification des permissions côté serveur.

---

## Réalisation professionnelle n°2

**Intitulé :** [À COMPLÉTER — deuxième réalisation requise pour couvrir l'ensemble des compétences du bloc 2]

**Contexte :** [À COMPLÉTER]

**Modalité :** [À COMPLÉTER]

**Période :** [À COMPLÉTER]

**Environnement technologique :** [À COMPLÉTER]

**Compétences mobilisées (bloc 2 — SLAM) :**
- ☐ Concevoir et développer une solution applicative
- ☐ Assurer la maintenance corrective ou évolutive d'une solution applicative
- ☐ Gérer les données

**Résumé :** [À COMPLÉTER]

---

## Couverture des compétences du bloc 2 (SLAM)

| Compétence | Réa. 1 | Réa. 2 |
|---|:---:|:---:|
| Concevoir et développer une solution applicative | ☑ | ☐ |
| Assurer la maintenance corrective ou évolutive d'une solution applicative | ☑ | ☐ |
| Gérer les données | ☑ | ☐ |

> ⚠️ Les deux réalisations doivent **ensemble** couvrir toutes les compétences du bloc 2. Cocher la réalisation 2 en conséquence.
