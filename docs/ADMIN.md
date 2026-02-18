# Guide d'Administration - Gestion des Champs

## Vue d'ensemble

Le système de gestion des champs personnalisés vous permet de :
- ✅ Afficher/masquer des colonnes dans l'interface
- ✅ Réorganiser l'ordre d'affichage des champs
- ✅ Ajouter de nouveaux champs personnalisés
- ✅ Supprimer des champs non-essentiels

## Installation

### 1. Importer le schéma des champs personnalisés

Après avoir importé `database/schema.sql`, importez également le schéma pour les champs personnalisés :

```bash
# Avec Docker
docker exec -i projet_entreprise-db-1 mysql -uroot -proot inventaire_pc < database/schema_custom_fields.sql

# Sans Docker
mysql -u root -p inventaire_pc < database/schema_custom_fields.sql
```

### 2. Accéder au panneau d'administration

Une fois le schéma importé, accédez au panneau d'administration :

```
http://localhost:8080/admin/fields.php
```

Ou cliquez sur le bouton **"⚙️ Gérer les champs"** dans la page principale.

## Fonctionnalités

### 1. Afficher/Masquer des Champs

Cliquez sur le bouton **👁️ Visible** / **👁️ Caché** pour afficher ou masquer un champ.

- **Visible** : Le champ apparaît dans les formulaires et la liste
- **Caché** : Le champ est masqué mais les données sont conservées

### 2. Réorganiser les Champs

Modifiez le numéro dans la colonne **"Ordre"** pour changer la position d'affichage :
- `1` = Premier champ
- `2` = Deuxième champ, etc.

Le changement est appliqué immédiatement.

### 3. Ajouter un Nouveau Champ Personnalisé

Utilisez le formulaire en bas de la page :

1. **Nom du champ** : Identifiant technique (ex: `localisation`)
   - Lettres minuscules et underscores uniquement
   - Doit être unique

2. **Libellé** : Texte affiché à l'utilisateur (ex: "Localisation")

3. **Type** : Choisissez le type de champ
   - **Texte** : Champ texte simple
   - **Nombre** : Champ numérique
   - **Date** : Sélecteur de date
   - **Textarea** : Zone de texte multi-lignes
   - **Liste déroulante** : Sélection parmi plusieurs options

4. **Obligatoire** : Le champ doit-il être rempli ?

### 4. Supprimer un Champ

Cliquez sur le bouton **🗑️** pour supprimer un champ personnalisé.

**Attention** : Les champs protégés ne peuvent pas être supprimés :
- hostname
- serial
- marque
- utilisateur
- os
- architecture
- statut

## Exemples de Champs Personnalisés

### Champ "Localisation"

```
Nom du champ: localisation
Libellé: Localisation
Type: Texte
Obligatoire: Non
```

### Champ "Date d'achat"

```
Nom du champ: date_achat
Libellé: Date d'achat
Type: Date
Obligatoire: Non
```

### Champ "Prix"

```
Nom du champ: prix
Libellé: Prix (€)
Type: Nombre
Obligatoire: Non
```

### Champ "Garantie"

```
Nom du champ: garantie
Libellé: Sous garantie
Type: Liste déroulante
Options: Oui, Non
Obligatoire: Non
```

## Structure Technique

### Tables Créées

#### 1. `custom_fields`
Stocke la définition des champs

| Colonne | Description |
|---------|-------------|
| `id` | Identifiant unique |
| `field_name` | Nom technique du champ |
| `field_label` | Libellé affiché |
| `field_type` | Type (text, number, select, etc.) |
| `field_options` | Options pour les listes déroulantes |
| `is_required` | Champ obligatoire ? |
| `is_visible` | Champ visible ? |
| `display_order` | Ordre d'affichage |

#### 2. `pc_custom_data`
Stocke les valeurs des champs personnalisés

| Colonne | Description |
|---------|-------------|
| `pc_id` | Référence au PC |
| `field_name` | Nom du champ |
| `field_value` | Valeur du champ |

### Architecture

```
┌─────────────────┐
│ custom_fields   │  ← Définition des champs
│  - field_name   │
│  - field_label  │
│  - field_type   │
│  - is_visible   │
└─────────────────┘
         │
         │ Utilise
         ↓
┌─────────────────┐      ┌──────────────────┐
│      pcs        │      │ pc_custom_data   │
│  - id           │←────→│  - pc_id         │
│  - hostname     │      │  - field_name    │
│  - serial       │      │  - field_value   │
│  - ...          │      └──────────────────┘
└─────────────────┘
  Champs standards       Champs personnalisés
```

## Cas d'Usage

### Scénario 1 : Masquer temporairement un champ

Vous ne voulez plus afficher le champ "Domaine" dans les formulaires :

1. Accédez à **Admin - Gestion des champs**
2. Trouvez la ligne "Domaine"
3. Cliquez sur **👁️ Visible** → devient **👁️ Caché**
4. Le champ disparaît des formulaires mais les données sont conservées

### Scénario 2 : Ajouter un champ "Salle"

Vous voulez suivre la salle où est situé chaque PC :

1. Accédez à **Admin - Gestion des champs**
2. Remplissez le formulaire :
   - Nom: `salle`
   - Libellé: `Salle`
   - Type: `Texte`
   - Obligatoire: `Non`
3. Cliquez sur **Ajouter le champ**
4. Le champ apparaît maintenant dans les formulaires d'ajout/modification

### Scénario 3 : Réorganiser l'affichage

Vous voulez que "Utilisateur" apparaisse avant "Marque" :

1. Accédez à **Admin - Gestion des champs**
2. Identifiez les numéros d'ordre actuels
3. Modifiez les valeurs dans la colonne "Ordre"
4. Les champs se réorganisent automatiquement

## Limitations Actuelles

⚠️ **Champs protégés** : Les champs essentiels au fonctionnement ne peuvent pas être supprimés

⚠️ **Types de champs** : Pour l'instant, les types "select" (liste déroulante) nécessitent une configuration manuelle dans le code

⚠️ **Validation** : Les validations personnalisées doivent être ajoutées manuellement

## Développements Futurs

🔄 **Prochaines fonctionnalités** :
- Configuration des options pour les listes déroulantes via l'interface
- Validations personnalisées (regex, longueur min/max)
- Import/Export de configurations de champs
- Groupes de champs et onglets
- Champs conditionnels (affichage selon d'autres valeurs)

## Support

Pour toute question ou problème :
1. Vérifier que `database/schema_custom_fields.sql` a bien été importé
2. Consulter les logs de la base de données
3. Vérifier les permissions sur la table `custom_fields`

## Sauvegardes

⚠️ **Important** : Avant de supprimer des champs, assurez-vous de faire une sauvegarde :

```bash
# Sauvegarder la configuration des champs
mysqldump -u root -p inventaire_pc custom_fields > backup_custom_fields.sql

# Restaurer si nécessaire
mysql -u root -p inventaire_pc < backup_custom_fields.sql
```
