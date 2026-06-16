<?php
// Fichier: students/reservations.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer les réservations de l'étudiant
$stmt = $pdo->prepare("
    SELECT 
        r.id_reservation,
        r.isbn,
        l.titre,
        l.auteur,
        l.image_livre,
        l.nbre_de_copie_disponible,
        l.nbre_de_copie_total,
        r.date_reservation,
        r.statut_reservation,
        (SELECT COUNT(*) FROM reserver r2 
         WHERE r2.isbn = r.isbn 
         AND r2.statut_reservation = 'en_attente'
         AND r2.date_reservation < r.date_reservation) as position_file
    FROM reserver r
    JOIN livre l ON r.isbn = l.isbn
    WHERE r.id_etudiant = ?
    ORDER BY r.date_reservation DESC
");
$stmt->execute([$user['id']]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations - Lireo</title>
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

        body {
            min-height: 100vh;
            background-color: var(--content-bg);
        }

        /* Header */
        header {
            background-color: var(--white);
            padding: 15px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }
         /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        /* Main Content */
        main {
            flex: 1;
            padding: 0px 0px;
            margin-bottom: 30px;
            width:50px; /* Ajustement pour sidebar fermée par défaut ou ouverte */
            margin-left: 0px; /* Si la sidebar est fixed */
            margin-right : 10px;
        }
        .main.expand {
            width: 0px;
            margin-left: 10px; /* Si la sidebar est fixed */
            margin-right : 100px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2.2rem;
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .back-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }

        .catalog-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .catalog-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Reservations Container */
        .reservations-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Reservation Card */
        .reservation-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
        }

        .reservation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .reservation-content {
            display: flex;
            gap: 25px;
        }

        @media (max-width: 768px) {
            .reservation-content {
                flex-direction: column;
            }
        }

        /* Book Cover */
        .book-cover {
            width: 100px;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        @media (max-width: 480px) {
            .book-cover {
                width: 80px;
                height: 112px;
            }
        }

        /* Book Details */
        .book-details {
            flex: 1;
        }

        .book-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .book-header {
                flex-direction: column;
                gap: 15px;
            }
        }

        .book-title {
            font-size: 1.4rem;
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .book-author {
            color: var(--gray);
            font-size: 1rem;
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .status-soon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .status-waiting {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .status-other {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        /* Reservation Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--dark);
            font-weight: 500;
            font-size: 1rem;
        }

        .position-value {
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* Instructions Box */
        .instructions-box {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .instructions-available {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: var(--success);
        }

        .instructions-soon {
            background-color: rgba(255, 193, 7, 0.1);
            border-left-color: var(--warning);
        }

        .instructions-waiting {
            background-color: rgba(23, 162, 184, 0.1);
            border-left-color: var(--info);
        }

        .instructions-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .instructions-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .instructions-text {
            color: var(--dark);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Reservation Actions */
        .reservation-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }

        @media (max-width: 768px) {
            .reservation-actions {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
        }

        .isbn-info {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        .cancel-btn {
            background-color: var(--danger);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        .borrow-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
        }

        .borrow-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Statistics Section */
        .statistics-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-top: 30px;
        }

        .statistics-title {
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (min-width: 768px) {
            .statistics-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background-color: rgba(42, 82, 152, 0.05);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .stat-total {
            color: var(--primary);
        }

        .stat-ready {
            color: var(--success);
        }

        .stat-queue {
            color: var(--warning);
        }

        .stat-pending {
            color: var(--info);
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            nav {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .reservation-card,
            .statistics-section {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
  
        <?php include_once __DIR__ . '/../../includes/sidebarstudent.php'; ?>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="header" style="display: flex; justify-content: flex-start; align-items: center; gap: 20px;">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div class="page-header">
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Mes Reservations</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Gérez vos réservations de livres en attente</p>
            </div> 
        </header>
        

        <?php if (empty($reservations)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h2>Aucune réservation</h2>
                <p>Vous n'avez aucune réservation en cours.</p>
                <a href="books.php" class="catalog-btn">
                    <i class="fas fa-search"></i> Découvrir le catalogue
                </a>
            </div>
        <?php else: ?>
            <!-- Reservations List -->
            <div class="reservations-container">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-content">
                            <!-- Book Cover -->
                            <img src="../../assets/images/books/<?= htmlspecialchars($reservation['image_livre']) ?>" 
                                 alt="<?= htmlspecialchars($reservation['titre']) ?>"
                                 class="book-cover"
                                 onerror="this.src='../../assets/images/books/default.jpg'">
                            
                            <!-- Book Details -->
                            <div class="book-details">
                                <!-- Header with Title and Status -->
                                <div class="book-header">
                                    <div>
                                        <h3 class="book-title"><?= htmlspecialchars($reservation['titre']) ?></h3>
                                        <div class="book-author"><?= htmlspecialchars($reservation['auteur']) ?></div>
                                    </div>
                                    
                                    <!-- Status Badge -->
                                    <div>
                                        <?php if ($reservation['statut_reservation'] === 'en_attente'): ?>
                                            <?php if ($reservation['nbre_de_copie_disponible'] > 0 && $reservation['position_file'] == 0): ?>
                                                <div class="status-badge status-available">
                                                    <i class="fas fa-check-circle"></i> Disponible
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--success); margin-top: 5px; text-align: right;">
                                                    Vous êtes le premier!
                                                </div>
                                            <?php elseif ($reservation['nbre_de_copie_disponible'] > 0): ?>
                                                <div class="status-badge status-soon">
                                                    <i class="fas fa-hourglass-half"></i> Bientôt disponible
                                                </div>
                                            <?php else: ?>
                                                <div class="status-badge status-waiting">
                                                    <i class="fas fa-clock"></i> En attente
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="status-badge status-other">
                                                <i class="fas fa-info-circle"></i> <?= ucfirst($reservation['statut_reservation']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Reservation Information -->
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Réservé le:</span>
                                        <span class="info-value"><?= date('d/m/Y à H:i', strtotime($reservation['date_reservation'])) ?></span>
                                    </div>
                                    
                                    <div class="info-item">
                                        <span class="info-label">Position dans la file:</span>
                                        <span class="info-value position-value">
                                            <?php if ($reservation['position_file'] == 0): ?>
                                                <i class="fas fa-trophy"></i> Premier
                                            <?php else: ?>
                                                #<?= $reservation['position_file'] + 1 ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="info-item">
                                        <span class="info-label">Copies disponibles:</span>
                                        <span class="info-value">
                                            <?= $reservation['nbre_de_copie_disponible'] ?> / <?= $reservation['nbre_de_copie_total'] ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Instructions -->
                                <?php if ($reservation['statut_reservation'] === 'en_attente'): ?>
                                    <?php if ($reservation['nbre_de_copie_disponible'] > 0 && $reservation['position_file'] == 0): ?>
                                        <div class="instructions-box instructions-available">
                                            <div class="instructions-header">
                                                <i class="fas fa-gift"></i>
                                                <span class="instructions-title">Livre disponible !</span>
                                            </div>
                                            <div class="instructions-text">
                                                Le livre que vous avez réservé est maintenant disponible. 
                                                Vous avez 24 heures pour venir le retirer à la bibliothèque.
                                            </div>
                                        </div>
                                    <?php elseif ($reservation['nbre_de_copie_disponible'] > 0): ?>
                                        <div class="instructions-box instructions-soon">
                                            <div class="instructions-header">
                                                <i class="fas fa-book-reader"></i>
                                                <span class="instructions-title">Bientôt disponible</span>
                                            </div>
                                            <div class="instructions-text">
                                                Des copies sont disponibles, mais vous êtes position #<?= $reservation['position_file'] + 1 ?> dans la file d'attente.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="instructions-box instructions-waiting">
                                            <div class="instructions-header">
                                                <i class="fas fa-hourglass"></i>
                                                <span class="instructions-title">En attente de disponibilité</span>
                                            </div>
                                            <div class="instructions-text">
                                                Vous êtes position #<?= $reservation['position_file'] + 1 ?> dans la file d'attente. 
                                                Vous serez notifié lorsque le livre sera disponible.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Actions -->
                                <div class="reservation-actions">
                                    <div class="isbn-info">
                                        ISBN: <?= htmlspecialchars($reservation['isbn']) ?>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <?php if ($reservation['statut_reservation'] === 'en_attente'): ?>
                                            <button onclick="cancelReservation(<?= $reservation['id_reservation'] ?>)" 
                                                    class="cancel-btn">
                                                <i class="fas fa-times"></i> Annuler
                                            </button>
                                            
                                            <?php if ($reservation['nbre_de_copie_disponible'] > 0 && $reservation['position_file'] == 0): ?>
                                                <button onclick="borrowReservedBook('<?= $reservation['isbn'] ?>')" 
                                                        class="borrow-btn">
                                                    <i class="fas fa-book"></i> Emprunter
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Statistics Section -->
            <div class="statistics-section">
                <h3 class="statistics-title">
                    <i class="fas fa-chart-bar"></i> Statistiques de vos réservations
                </h3>
                <div class="statistics-grid">
                    <div class="stat-item">
                        <div class="stat-value stat-total"><?= count($reservations) ?></div>
                        <div class="stat-label">Total réservations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value stat-ready">
                            <?= count(array_filter($reservations, function($r) { 
                                return $r['nbre_de_copie_disponible'] > 0 && $r['position_file'] == 0; 
                            })) ?>
                        </div>
                        <div class="stat-label">Prêtes à retirer</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value stat-queue">
                            <?= count(array_filter($reservations, function($r) { 
                                return $r['nbre_de_copie_disponible'] > 0 && $r['position_file'] > 0; 
                            })) ?>
                        </div>
                        <div class="stat-label">En file d'attente</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value stat-pending">
                            <?= count(array_filter($reservations, function($r) { 
                                return $r['nbre_de_copie_disponible'] == 0; 
                            })) ?>
                        </div>
                        <div class="stat-label">En attente</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            let btn = document.querySelector("#header_toggle_btn");
            let sidebar = document.querySelector(".sidebar");
            let mainContent = document.querySelector(".main-content");

            if (btn && sidebar && mainContent) {
                btn.onclick = function() {
                    sidebar.classList.toggle("close");
                    mainContent.classList.toggle("expand");
                };
            }
            console.log('Page de réservations chargée');
        });

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

        function borrowReservedBook(isbn) {
            if (!confirm('Emprunter ce livre maintenant ?')) return;
            
            fetch('../../api/emprunt/request_borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'isbn=' + isbn
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
                alert('❌ Erreur lors de la demande d\'emprunt');
                console.error(error);
            });
        }
    </script>
</body>
</html>