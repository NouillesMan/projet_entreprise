# Cahier des Charges — Inventaire PC d'Entreprise

## 1. Contexte et Objectif

Développement d'une application web interne permettant de gérer l'inventaire du parc informatique d'une entreprise. L'outil doit permettre à une équipe IT de suivre l'état, l'attribution et les caractéristiques de tous les postes de travail (PC, laptops, serveurs).

---

## 2. Périmètre Fonctionnel

### 2.1 Gestion de l'inventaire (CRUD)

- **Lister** tous les PCs avec filtres, tri par colonne, pagination (50 par page)
- **Ajouter** un PC manuellement via formulaire
- **Modifier** un PC existant
- **Supprimer** un PC individuellement ou en sélection multiple (suppression en lot)

### 2.2 Champs d'un PC

| Champ | Type | Obligatoire |
|-------|------|-------------|
| Hostname | Texte | Oui |
| Numéro de série | Texte (unique) | Oui |
| Marque | Liste déroulante | Oui |
| Modèle | Liste (filtrée par marque) | Non |
| Utilisateur | Texte / liste | Oui |
| OS | Liste déroulante (groupée) | Oui |
| Version OS | Liste (filtrée par OS) | Non |
| Architecture | Enum : x86 / x64 / arm64 | Oui |
| Domaine | Texte | Non |
| Statut | Enum (4 valeurs) | Oui |
| Remarques | Textarea | Non |
| Champs personnalisés | Variables | Selon config |

### 2.3 Statuts possibles

- En service
- En stock
- En réparation
- Retiré

### 2.4 Tableau de bord

- Compteurs par statut
- Répartition par architecture (barres de progression)
- Top 5 marques et OS
- 10 dernières modifications
- 5 derniers ajouts

### 2.5 Import CSV

- Import d'un fichier CSV pour créer ou mettre à jour des PCs en lot
- Colonnes obligatoires : hostname, serial, marque, utilisateur, os, architecture, statut
- Colonnes optionnelles : modele, domaine, os_version, remarques
- Mode "mise à jour des existants" activable
- Détection des doublons sur le numéro de série (UPSERT)
- Numéro de série auto-généré si absent (`NOSERIAL-{hostname}`)
- Synchronisation automatique des nouvelles valeurs dans les listes déroulantes après import

### 2.6 Scripts de collecte automatique

- Script PowerShell Windows (`collect_windows.ps1`) + lanceur `.bat`
- Script Bash Linux (`collect_linux.sh`)
- Téléchargeables directement depuis l'interface admin
- Génèrent un fichier `inventaire.csv` importable

### 2.7 Administration

#### Gestion des champs
- Afficher/masquer des champs dans les formulaires
- Réordonner les champs par drag & drop
- Ajouter des champs personnalisés (types : text, number, date, textarea, select)
- Modifier le libellé, le type et le caractère obligatoire d'un champ
- Supprimer des champs non protégés
- Les champs "noyau" (colonnes fixes de la table `pcs`) sont protégés

#### Gestion des listes déroulantes
- 4 listes gérables : Marques, Modèles (groupés par marque), OS (groupés par famille), Versions OS (groupées par famille)
- Ajout, suppression, réordonnancement par drag & drop
- Filtrage adaptatif : le select Modèle filtre selon la marque choisie, le select Version OS filtre selon l'OS choisi (côté client)

#### Gestion des utilisateurs
- Créer/modifier/supprimer des comptes
- Système de permissions granulaire (5 flags indépendants)
- Protection contre la désélection de son propre rôle admin

#### Statistiques utilisateurs
- Tableau : nombre de PCs par utilisateur avec décompte par statut
- Barre de progression relative
- Recherche live dans le tableau
- Lien vers l'inventaire filtré sur l'utilisateur

---

## 3. Exigences Non Fonctionnelles

### 3.1 Sécurité

- Authentification obligatoire sur toutes les pages (sauf `login.php`)
- Protection CSRF sur tous les formulaires POST
- Échappement HTML systématique via `e()` (prévention XSS)
- Permissions vérifiées côté serveur pour chaque action
- Mots de passe hashés en bcrypt

### 3.2 Accessibilité et Ergonomie

- Interface responsive (Bootstrap 5)
- Feedback utilisateur après chaque action (alertes dismissibles)
- Confirmation avant toute suppression
- Sélection multi-pages persistée dans `localStorage`
- Tri cliquable sur toutes les colonnes de l'inventaire

### 3.3 Performance

- Requêtes SQL paramétrées (PDO prepared statements)
- Pagination côté serveur (pas de chargement de tout l'inventaire)
- Optimisations : dérivation du total par `array_sum` plutôt qu'une requête dédiée, chargement conditionnel des listes de référence

### 3.4 Déploiement

- Conteneurisable via Docker Compose (PHP + MariaDB)
- Schema SQL modulaire (schema.sql + extensions séparées)
- Fichier de config isolé (`includes/config.php`)

---

## 4. Système de Permissions

| Permission | Rôle concerné | Accès donné |
|------------|---------------|-------------|
| `can_view` | Lecteur | Voir l'inventaire et le dashboard |
| `can_add` | Technicien | Ajouter des PCs |
| `can_edit` | Technicien | Modifier des PCs |
| `can_delete` | Manager | Supprimer des PCs |
| `is_admin` | Admin | Accès à tout le panneau admin |

---

## 5. Contraintes Techniques

- PHP 8.0+ (utilisation de `match`, `str_contains`, arrow functions)
- MariaDB / MySQL 5.7+
- Pas de framework PHP (code natif)
- Pas de bibliothèque JS externe pour la logique métier (sauf SortableJS pour le drag & drop admin)
- Bootstrap 5 et Bootstrap Icons via CDN
