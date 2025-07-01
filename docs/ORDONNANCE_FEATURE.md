# Gestion des Ordonnances

Ce document décrit la fonctionnalité de gestion des ordonnances dans l'application Carnet de Santé.

## Fonctionnalités

### Pour les Médecins
- Téléverser une ordonnance après une consultation
- Voir la liste des ordonnances émises
- Télécharger une ordonnance existante
- Supprimer une ordonnance (avec confirmation)

### Pour les Patients
- Voir la liste de toutes les ordonnances
- Télécharger une ordonnance au format PDF
- Consulter l'historique des ordonnances

## Sécurité

- Seul le médecin qui a créé une consultation peut y ajouter une ordonnance
- Seul le patient concerné peut voir et télécharger ses propres ordonnances
- Les fichiers sont stockés de manière sécurisée sur le serveur
- Validation des types de fichiers (uniquement PDF)
- Taille maximale des fichiers limitée à 2 Mo

## Structure des fichiers

- Les fichiers d'ordonnance sont stockés dans `/public/uploads/ordonnances/`
- Les noms des fichiers sont générés de manière aléatoire pour éviter les collisions
- Les métadonnées des ordonnances sont stockées dans la base de données

## API Endpoints

### Médecins
- `GET /medecin/ordonnance/upload/{id}` - Afficher le formulaire d'upload
- `POST /medecin/ordonnance/upload/{id}` - Traiter l'upload d'une ordonnance
- `DELETE /medecin/ordonnance/delete/{id}` - Supprimer une ordonnance

### Patients
- `GET /patient/ordonnance/list` - Lister toutes les ordonnances du patient
- `GET /patient/ordonnance/download/{id}` - Télécharger une ordonnance

## Tests

Des tests unitaires et fonctionnels sont disponibles pour assurer le bon fonctionnement de la fonctionnalité :

- `tests/Controller/Medecin/OrdonnanceControllerTest.php`
- `tests/Controller/Patient/OrdonnanceControllerTest.php`

Pour exécuter les tests :

```bash
php bin/phpunit tests/Controller/Medecin/OrdonnanceControllerTest.php
php bin/phpunit tests/Controller/Patient/OrdonnanceControllerTest.php
```

## Configuration requise

- PHP 8.1 ou supérieur
- Symfony 6.2 ou supérieur
- VichUploaderBundle
- Une base de données compatible avec Doctrine ORM

## Installation

1. Installer les dépendances :
   ```bash
   composer require vich/uploader-bundle
   ```

2. Configurer VichUploaderBundle dans `config/packages/vich_uploader.yaml`

3. Créer le répertoire d'upload :
   ```bash
   mkdir -p public/uploads/ordonnances
   chmod -R 777 public/uploads
   ```

4. Exécuter les migrations :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

## Maintenance

- Les fichiers d'ordonnance ne sont pas supprimés automatiquement lors de la suppression d'un compte utilisateur
- Une tâche planifiée peut être mise en place pour nettoyer les fichiers orphelins
- Les sauvegardes régulières des fichiers uploadés sont recommandées
