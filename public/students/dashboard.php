<?php
// Fichier: students/dashboard.php - VERSION AVEC STYLE ADMIN
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer les emprunts en cours de l'étudiant (corrigé)
$stmt_emprunts = $pdo->prepare("
    SELECT 
        e.id_emprunt,
        e.isbn,
        l.titre,
        l.auteur,
        l.image_livre,
        e.date_emprunt,
        e.date_retour_prevue,
        e.statut_emprunt,
        ear.date_limite_retrait,
        CASE 
            WHEN e.statut_emprunt = 'en_attente_retrait' THEN 'en_attente_retrait'
            WHEN CURDATE() > e.date_retour_prevue THEN 'en_retard'
            ELSE 'en_cours'
        END AS statut_affichage
    FROM emprunt e
    JOIN livre l ON e.isbn = l.isbn
    LEFT JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
    WHERE e.id_etudiant = ? AND e.date_retour_reel IS NULL
    ORDER BY e.date_retour_prevue ASC
");
$stmt_emprunts->execute([$user['id']]);
$emprunts = $stmt_emprunts->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réservations (corrigé)
$stmt_reservations = $pdo->prepare("
    SELECT 
        r.id_reservation,
        r.isbn,
        l.titre,
        l.auteur,
        l.image_livre,
        r.date_reservation,
        l.nbre_de_copie_disponible
    FROM reserver r
    JOIN livre l ON r.isbn = l.isbn
    WHERE r.id_etudiant = ? AND r.statut_reservation = 'en_attente'
    ORDER BY r.date_reservation ASC
");
$stmt_reservations->execute([$user['id']]);
$reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les alertes non lues (corrigé)
$stmt_alertes = $pdo->prepare("
    SELECT * FROM alerte 
    WHERE id_etudiant = ? AND etat = 'non_lu'
    ORDER BY date_envoi DESC
    LIMIT 5
");
$stmt_alertes->execute([$user['id']]);
$alertes = $stmt_alertes->fetchAll(PDO::FETCH_ASSOC);

// Statistiques (corrigées)
$count_emprunts = count($emprunts);
$count_reservations = count($reservations);
$count_alertes = count($alertes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant - MaBibliothèque</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2a5298;
            --primary-dark: #1e3c72;
            --primary-light: #4a6fc1;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --sidebar-bg: #1a1f35;
            --sidebar-text: #b0b7c3;
            --sidebar-active: #2a5298;
            --content-bg: #f5f7fa;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: var(--white);
            padding: 15px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        /* Main Content */
        .main {
            flex: 1;
            padding: 0px 0px;
            margin-bottom: 30px;
            width:50px; /* Ajustement pour sidebar fermée par défaut ou ouverte */
            margin-left: 0px; /* Si la sidebar est fixed */
            margin-right : 10px;
        }
        
        /* Ajustement si sidebar ouverte (géré par JS via la classe .expand) */
        .main.expand {
            width: 0px;
            margin-left: 10px; /* Si la sidebar est fixed */
            margin-right : 100px;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
            gap: 15px;
        }
        .logo {
            padding: 0 25px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 2rem;
            color: var(--primary-light);
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
        }

        

        .user-info {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-details h3 {
            font-size: 0.9rem;
            color: var(--white);
            margin-bottom: 2px;
        }

        .user-details p {
            font-size: 0.8rem;
            color: var(--sidebar-text);
        }

        .logout-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--sidebar-text);
            padding: 10px 20px;
            margin: 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .topbar {
            background-color: var(--card-bg);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            z-index: 10;
        }

        .header-title h1 {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 700;
        }

        .header-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .datetime {
            text-align: right;
        }

        .time {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }

        .date {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .content {
            padding: 30px;
            flex: 1;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .stat-icon.green {
            background: linear-gradient(135deg, var(--success), #1e7e34);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, var(--warning), #e0a800);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #6f42c1, #4e2a8c);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Card Styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
            padding-bottom: 15px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Book Items */
        .book-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: var(--light);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark);
        }

        .book-item:hover {
            background-color: var(--gray-light);
            transform: translateX(5px);
        }

        .book-cover {
            width: 60px;
            height: 80px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 15px;
        }

        .book-info {
            flex: 1;
        }

        .book-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .book-author {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .book-date {
            color: var(--secondary);
            font-size: 0.85rem;
        }

        .book-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-ontime {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .status-late {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .status-waiting {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .status-pending {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        .book-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        /* Alert Items */
        .alert-item {
            padding: 15px;
            border-left: 4px solid;
            background-color: var(--light);
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            transform: translateX(5px);
        }

        .alert-item.warning {
            border-left-color: var(--warning);
            background-color: rgba(255, 193, 7, 0.1);
        }

        .alert-item.info {
            border-left-color: var(--info);
            background-color: rgba(23, 162, 184, 0.1);
        }

        .alert-item.success {
            border-left-color: var(--success);
            background-color: rgba(40, 167, 69, 0.1);
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .alert-type {
            font-weight: 600;
            color: var(--dark);
        }

        .alert-date {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .alert-message {
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .alert-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mark-read-btn {
            background-color: transparent;
            border: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .mark-read-btn:hover {
            background-color: rgba(42, 82, 152, 0.1);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            background-color: var(--card-bg);
            border: 2px solid var(--gray-light);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--dark);
        }

        .action-btn:hover {
            border-color: var(--primary);
            background-color: rgba(42, 82, 152, 0.05);
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 2rem;
            color: var(--primary);
        }

        .action-btn span {
            color: var(--dark);
            font-weight: 600;
            text-align: center;
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-light);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .empty-state p {
            margin-bottom: 20px;
        }

        .primary-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .primary-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Layout grid */
        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 20px 0;
            }

            .logo h1, .user-details, .nav-item span {
                display: none;
            }

            .logo {
                justify-content: center;
                padding: 0;
            }

            .user-info {
                justify-content: center;
                padding: 15px;
            }

            .nav-item {
                justify-content: center;
                padding: 15px;
            }

            .grid-2-col {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                padding: 15px;
                justify-content: space-between;
            }

            .logo {
                margin-bottom: 0;
            }

            .nav-menu {
                display: flex;
                flex: 0;
                padding: 0;
            }

            .nav-item {
                margin: 0 5px;
            }

            .user-info, .logout-btn {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .book-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .book-cover {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .book-actions {
                width: 100%;
                margin-top: 10px;
            }
        }

        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
        <?php include_once __DIR__ . '/../../includes/sidebarstudent.php'; ?>

        

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Tableau de Bord Étudiant</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Bienvenue, <?php echo htmlspecialchars($user['prenom']); ?>!</p>
            </div>
           
            <div class="datetime">
                <div class="time" id="current-time"></div>
                <div class="date" id="current-date"></div>
            </div>
        </header>

        <!-- Content -->
        <main class="content">
            <!-- Statistiques -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $count_emprunts ?></h3>
                        <p>Emprunts en cours</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $count_reservations ?></h3>
                        <p>Réservations</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $count_alertes ?></h3>
                        <p>Alertes non lues</p>
                    </div>
                </div>
            </section>

            <!-- Actions rapides -->
            <section class="card">
                <h2 class="card-title"><i class="fas fa-bolt"></i> Actions Rapides</h2>
                <div class="quick-actions">
                    <a href="books.php" class="action-btn">
                        <i class="fas fa-search"></i>
                        <span>Rechercher un livre</span>
                    </a>
                    
                    <a href="reservations.php" class="action-btn">
                        <i class="fas fa-list-alt"></i>
                        <span>Mes réservations</span>
                    </a>
                    
                    <a href="history.php" class="action-btn">
                        <i class="fas fa-history"></i>
                        <span>Historique d'emprunts</span>
                    </a>
                    
                    <a href="messages.php" class="action-btn">
                        <i class="fas fa-envelope"></i>
                        <span>Contacter l'admin</span>
                    </a>
                </div>
            </section>

            <div class="grid-2-col">
                <!-- Emprunts en cours -->
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-book-open"></i> Mes Emprunts en Cours</h2>
                        <a href="history.php" class="primary-btn" style="padding: 8px 15px; font-size: 0.9rem;">
                            Voir tout
                        </a>
                    </div>
                    
                    <?php if (empty($emprunts)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>Aucun emprunt en cours</h3>
                            <p>Explorez notre catalogue pour découvrir de nouveaux livres</p>
                            <a href="books.php" class="primary-btn">
                                Découvrir les livres
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="emprunts-list">
                            <?php foreach ($emprunts as $emprunt): ?>
                                <?php
                                $statusClass = 'status-ontime';
                                $statusText = 'À l\'heure';
                                
                                if ($emprunt['statut_affichage'] === 'en_retard') {
                                    $statusClass = 'status-late';
                                    $statusText = 'En retard';
                                } elseif ($emprunt['statut_affichage'] === 'en_attente_retrait') {
                                    $statusClass = 'status-waiting';
                                    $statusText = 'En attente de retrait';
                                }
                                ?>
                                <div class="book-item">
                                    <img src="../<?= htmlspecialchars($emprunt['image_livre']) ?>" 
                                         alt="<?= htmlspecialchars($emprunt['titre']) ?>"
                                         class="book-cover"
                                        > 
                                    <div class="book-info">
                                        <h3 class="book-title"><?= htmlspecialchars($emprunt['titre']) ?></h3>
                                        <p class="book-author"><?= htmlspecialchars($emprunt['auteur']) ?></p>
                                        <p class="book-date">
                                            À retourner le <?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?>
                                        </p>
                                        <?php if ($emprunt['statut_emprunt'] === 'en_attente_retrait' && $emprunt['date_limite_retrait']): ?>
                                            <p class="book-date" style="color: var(--warning);">
                                                ⏳ Retrait avant le <?= date('d/m/Y H:i', strtotime($emprunt['date_limite_retrait'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-actions">
                                        <span class="book-status <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Réservations et Alertes -->
                <div>
                    <!-- Réservations -->
                    <section class="card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-calendar-alt"></i> Mes Réservations</h2>
                            <a href="reservations.php" class="primary-btn" style="padding: 8px 15px; font-size: 0.9rem;">
                                Voir tout
                            </a>
                        </div>
                        
                        <?php if (empty($reservations)): ?>
                            <div class="empty-state" style="padding: 20px;">
                                <i class="fas fa-calendar-times"></i>
                                <p>Aucune réservation en attente</p>
                            </div>
                        <?php else: ?>
                            <div class="reservations-list">
                                <?php foreach ($reservations as $reservation): ?>
                                    <div class="book-item">
                                        <img src="../../assets/images/books/<?= htmlspecialchars($reservation['image_livre']) ?>" 
                                             alt="<?= htmlspecialchars($reservation['titre']) ?>"
                                             class="book-cover"
                                             onerror="this.src='../../assets/images/books/default.jpg'">
                                        <div class="book-info">
                                            <h3 class="book-title"><?= htmlspecialchars($reservation['titre']) ?></h3>
                                            <p class="book-author"><?= htmlspecialchars($reservation['auteur']) ?></p>
                                            <p class="book-date">
                                                Réservé le <?= date('d/m/Y', strtotime($reservation['date_reservation'])) ?>
                                            </p>
                                            <?php if ($reservation['nbre_de_copie_disponible'] > 0): ?>
                                                <span class="book-status status-ontime">Disponible</span>
                                            <?php else: ?>
                                                <span class="book-status status-pending">En attente</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="book-actions">
                                            <button onclick="cancelReservation(<?= $reservation['id_reservation'] ?>)" 
                                                    class="mark-read-btn" style="color: var(--danger);">
                                                Annuler
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Alertes récentes -->
                    <section class="card">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-bell"></i> Alertes Récentes</h2>
                            <a href="alerts.php" class="primary-btn" style="padding: 8px 15px; font-size: 0.9rem;">
                                Voir tout
                            </a>
                        </div>
                        
                        <?php if (empty($alertes)): ?>
                            <div class="empty-state" style="padding: 20px;">
                                <i class="fas fa-bell-slash"></i>
                                <p>Aucune alerte non lue</p>
                            </div>
                        <?php else: ?>
                            <div class="alerts-list">
                                <?php foreach ($alertes as $alerte): ?>
                                    <?php
                                    $alertTypeClass = 'info';
                                    $alertIcon = 'fas fa-info-circle';
                                    
                                    if (strpos($alerte['type_alerte'], 'retard') !== false) {
                                        $alertTypeClass = 'warning';
                                        $alertIcon = 'fas fa-exclamation-triangle';
                                    } elseif (strpos($alerte['type_alerte'], 'disponible') !== false) {
                                        $alertTypeClass = 'success';
                                        $alertIcon = 'fas fa-check-circle';
                                    }
                                    ?>
                                    <div class="alert-item <?= $alertTypeClass ?>">
                                        <div class="alert-header">
                                            <span class="alert-type">
                                                <i class="<?= $alertIcon ?>"></i>
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $alerte['type_alerte']))) ?>
                                            </span>
                                            <span class="alert-date"><?= date('d/m/Y', strtotime($alerte['date_envoi'])) ?></span>
                                        </div>
                                        <p class="alert-message"><?= htmlspecialchars($alerte['message']) ?></p>
                                        <div class="alert-actions">
                                            <button onclick="markAlertAsRead(<?= $alerte['id_alerte'] ?>)" 
                                                    class="mark-read-btn">
                                                Marquer comme lu
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mettre à jour l'heure et la date en temps réel
        function updateDateTime() {
            const now = new Date();
            
            // Format de l'heure
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            const timeString = `${hours}:${minutes} ${ampm}`;
            
            // Format de la date
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateString = now.toLocaleDateString('fr-FR', options);
            
            // Mettre à jour le DOM
            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }
         document.addEventListener("DOMContentLoaded", function() {
            let btn = document.querySelector("#header_toggle_btn");
            let sidebar = document.querySelector(".sidebar");
            let mainContent = document.querySelector(".main-content");

            if (btn && sidebar && mainContent) {
                btn.onclick = function() {
                    sidebar.classList.toggle("close");
                    mainContent.classList.toggle("expand");
                };
            }

           });
        // Mettre à jour toutes les secondes
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Animation des cartes de statistiques
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Animation des boutons d'action rapide
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-3px)';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0)';
            });
        });

        // Fonctions JavaScript
        function cancelReservation(id_reservation) {
            if (!confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) return;
            
            fetch('../../api/emprunt/cancel_reservation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_reservation=' + id_reservation
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('❌ ' + data.error);
                } else {
                    alert('✅ ' + data.message);
                    location.reload();
                }
            })
            .catch(error => {
                alert('❌ Erreur lors de l\'annulation');
                console.error(error);
            });
        }

        function markAlertAsRead(id_alerte) {
            fetch('../../api/alerts/mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_alerte=' + id_alerte
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
    </script>
</body>
</html>