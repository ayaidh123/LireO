<p align="center">
  <img src="public/assets/images/pageicon.png" alt="Lireo" width="72" height="72">
</p>

<h1 align="center">Lireo</h1>

<p align="center">
  Système de gestion de bibliothèque universitaire<br>
  <sub>Conçu pour l'Université Moulay Ismail — Meknès, Maroc</sub>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Apache-2.4+-D22128?style=flat-square&logo=apache&logoColor=white" alt="Apache">
  <img src="https://img.shields.io/badge/Chart.js-4.x-FF6384?style=flat-square&logo=chartdotjs&logoColor=white" alt="Chart.js">
  <img src="https://img.shields.io/badge/Licence-Open_Source-22c55e?style=flat-square" alt="Licence">
</p>

---

## Présentation

Lireo est une application web complète qui permet de gérer l'ensemble du cycle de vie d'une bibliothèque universitaire : catalogue de livres, inscriptions étudiantes, emprunts, réservations, sanctions et messagerie interne.

L'application propose deux espaces distincts :

- **Espace Administrateur** — Tableau de bord avec KPIs et graphiques, gestion CRUD des livres et étudiants, suivi des emprunts et retours, sanctions, messagerie.
- **Espace Étudiant** — Recherche et consultation du catalogue, emprunt et réservation de livres, historique, alertes automatiques, profil personnel.

---

## Fonctionnalités

### Authentification et sécurité

- Connexion sécurisée avec hachage `bcrypt`
- Contrôle d'accès basé sur les rôles (RBAC) : Admin / Étudiant
- Activation de compte en deux étapes pour les étudiants
- Validation des emails académiques (`@umi.ac.ma`)
- Requêtes préparées PDO — protection contre les injections SQL
- Assainissement des sorties avec `htmlspecialchars()`

### Panel Administrateur

| Module | Description |
|--------|-------------|
| Dashboard | KPIs en temps réel, 5 graphiques interactifs via Chart.js |
| Gestion des livres | CRUD complet avec images de couverture et catégories |
| Gestion des étudiants | Création, modification, activation et blocage de comptes |
| Gestion des emprunts | Suivi des prêts, enregistrement des retours, prolongations |
| Sanctions | Bannissement temporaire avec dates de début et fin |
| Messagerie | Communication interne avec les étudiants |
| Statistiques | Emprunts par mois, statuts, top livres, retards, réservations vs emprunts |

### Espace Étudiant

| Module | Description |
|--------|-------------|
| Dashboard | Synthèse des emprunts actifs et alertes non lues |
| Catalogue | Recherche par titre, auteur ou ISBN avec disponibilité temps réel |
| Emprunt | Emprunt immédiat si le livre est disponible (durée : 14 jours) |
| Réservation | File d'attente automatique lorsque le livre est indisponible |
| Historique | Consultation de tous les emprunts passés et en cours |
| Alertes | Notifications automatiques : retards, disponibilités, rappels |
| Messages | Communication avec l'administration |
| Profil | Modification des données personnelles |

### Automatisation

Trois scripts PHP sont prévus pour être exécutés via des tâches planifiées (cron) :

| Script | Rôle |
|--------|------|
| `generate_alertes.php` | Génère les alertes quotidiennes (retards, disponibilités, rappels) |
| `cleanup_automatic.php` | Nettoie les données obsolètes (emprunts en attente expirés) |
| `send_notifications.php` | Déclenche l'envoi des notifications aux étudiants |

---

## Architecture

```
lireo/
│
├── api/                              # Endpoints REST
│   ├── admin/
│   │   ├── book_crud.php
│   │   ├── loan_management.php
│   │   ├── student_management.php
│   │   ├── manage_sanctions.php
│   │   ├── check_availability.php
│   │   ├── cleanup_pending_loans.php
│   │   └── stats_*.php               # 5 endpoints de statistiques
│   ├── alerts/                        # CRUD alertes, marquage lu
│   ├── books/                         # Recherche, détails
│   ├── emprunt/                       # Emprunt, réservation, prolongation
│   ├── messages/                      # Messagerie interne
│   └── users/                         # Liste étudiants
│
├── includes/                          # Modules partagés
│   ├── db.php                         # Connexion PDO MySQL
│   ├── auth.php                       # Authentification et sessions
│   ├── sidebaradmin.php               # Composant sidebar admin
│   └── sidebarstudent.php             # Composant sidebar étudiant
│
├── public/                            # Pages web (frontend)
│   ├── index.php                      # Page d'accueil publique
│   ├── login.php                      # Connexion
│   ├── register.php                   # Activation de compte
│   ├── logout.php                     # Déconnexion
│   ├── admin/                         # 6 pages admin
│   │   ├── dashboard.php
│   │   ├── books_management.php
│   │   ├── students_management.php
│   │   ├── loans_management.php
│   │   ├── sanctions.php
│   │   └── messages.php
│   ├── students/                      # 7 pages étudiant
│   │   ├── dashboard.php
│   │   ├── books.php
│   │   ├── reservations.php
│   │   ├── history.php
│   │   ├── alerts.php
│   │   ├── messages.php
│   │   └── profile.php
│   └── assets/
│       ├── images/                    # Images du site
│       └── images_livres/             # Couvertures de livres
│
├── scripts/                           # Tâches automatisées
│   ├── generate_alertes.php
│   ├── cleanup_automatic.php
│   └── send_notifications.php
│
├── .gitignore
└── README.md
```

---

## Base de données

Le schéma MySQL repose sur les tables suivantes :

| Table | Rôle |
|-------|------|
| `admin` | Comptes administrateurs |
| `etudiant` | Comptes étudiants (statut : actif / bloqué) |
| `categorie` | Catégories de livres |
| `livre` | Catalogue (ISBN, titre, auteur, copies disponibles, image) |
| `posseder` | Relation many-to-many entre livres et catégories |
| `emprunt` | Suivi des emprunts (dates, statut, retour) |
| `reserver` | File d'attente des réservations |
| `prolongation` | Demandes de prolongation d'emprunt |
| `sanction` | Bannissements temporaires |
| `message` | Messagerie interne admin / étudiant |
| `alerte` | Notifications automatiques |

---

## Installation

### Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Apache 2.4+ avec `mod_rewrite` activé
- Un environnement local tel que **XAMPP**, **WAMP**, **Laragon** ou équivalent

### Mise en place

**1 — Cloner le dépôt**

```bash
git clone https://github.com/ayaidh123/lireo.git
```

**2 — Créer la base de données**

```sql
CREATE DATABASE lireo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis importer le schéma :

```bash
mysql -u root -p lireo_db < library.sql
```

**3 — Configurer la connexion**

Ouvrir `includes/db.php` et adapter les paramètres :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lireo_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

> Le port MySQL par défaut est `3306`. Modifier la ligne de connexion PDO si nécessaire.

**4 — Déployer sur le serveur web**

Placer le dossier `lireo/` dans le répertoire racine de votre serveur :

| Environnement | Chemin |
|---------------|--------|
| XAMPP (Windows) | `C:\xampp\htdocs\lireo\` |
| WAMP | `C:\wamp64\www\lireo\` |
| Linux / Apache | `/var/www/html/lireo/` |

**5 — Accéder à l'application**

```
http://localhost/lireo/public/
```

---

## Compte de test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Administrateur | `admin@test.com` | `admin123` |

Pour créer un compte étudiant :
1. Se connecter en tant qu'administrateur
2. Accéder à « Gestion des Étudiants »
3. Ajouter un étudiant avec un email `@umi.ac.ma`
4. L'étudiant active son compte depuis la page d'inscription

---

## Tâches planifiées

Pour activer l'automatisation, configurer les cron jobs suivants :

```bash
# Alertes quotidiennes (retards, disponibilités) — chaque jour à 8h
0 8 * * * php /chemin/vers/lireo/scripts/generate_alertes.php

# Nettoyage des données obsolètes — chaque jour à minuit
0 0 * * * php /chemin/vers/lireo/scripts/cleanup_automatic.php

# Envoi des notifications — toutes les 6 heures
0 */6 * * * php /chemin/vers/lireo/scripts/send_notifications.php
```

---

## Stack technique

| Couche | Technologies |
|--------|-------------|
| Backend | PHP 7.4+ — PDO, Sessions |
| Base de données | MySQL 5.7+ |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Graphiques | Chart.js |
| Icônes | Font Awesome 6 |
| Serveur | Apache 2.4+ |

---

## Dépannage

| Problème | Solution |
|----------|----------|
| Erreur de connexion à la base de données | Vérifier les identifiants dans `includes/db.php` et le port MySQL |
| Pages introuvables (404) | S'assurer que le projet est bien placé dans le répertoire du serveur web |
| Images de livres non affichées | Vérifier la présence des fichiers dans `public/assets/images_livres/` |
| Session perdue entre les pages | Redémarrer Apache, vérifier la configuration `session` dans `php.ini` |
| Graphiques ne chargent pas | Vérifier l'accès aux endpoints `api/admin/stats_*.php` |

---

## Licence

Projet open source — libre d'utilisation, de modification et de redistribution.

---

<p align="center">
  <sub></sub>
</p>
