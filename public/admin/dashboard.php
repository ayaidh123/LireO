<?php


// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

// PAS de session_start ici, auth.php s’en occupe


// Vérifier le rôle
require_role('admin');




$admin_user = $_SESSION['user'];

/* ===================== STATS ===================== */
try {
    // Total Livres (30 livres dans votre base)
    $count_livres = $pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn();
    
    // Étudiants Actifs (3 étudiants: yassmine, julia, aya)
    $count_etudiants = $pdo->query("SELECT COUNT(*) FROM etudiant WHERE statut = 'actif'")->fetchColumn();
    
    // Emprunts Actifs - CORRECTION: statut = 'en_cours' ou 'en_attente_retrait'
    // Dans votre base: 2 emprunts avec statut 'en_attente_retrait'
    $count_emprunts = $pdo->query("
        SELECT COUNT(*) FROM emprunt 
        WHERE date_retour_reel IS NULL 
        AND statut_emprunt IN ('en_cours', 'en_attente_retrait')
    ")->fetchColumn();
    
    // Réservations - CORRECTION: votre table a une ligne avec statut vide
    // Il faut compter les réservations 'active' ou qui ne sont pas annulées/expirées
    $count_reserves = $pdo->query("
        SELECT COUNT(*) FROM reserver 
        WHERE statut_reservation IN ('active', '') 
        OR statut_reservation IS NULL
    ")->fetchColumn();
    
    // Emprunts en retard
    $count_retards = $pdo->query("
        SELECT COUNT(*) FROM emprunt 
        WHERE date_retour_prevue < CURDATE() 
        AND date_retour_reel IS NULL
        AND statut_emprunt = 'en_cours'
    ")->fetchColumn();
    
    // Sanctions actives - CORRECTION: votre table sanction n'a PAS de colonne statut
    // On compte toutes les sanctions dont la date de fin n'est pas dépassée
    $count_sanctions = $pdo->query("
        SELECT COUNT(*) FROM sanction 
        WHERE (date_fin_bannissement IS NULL OR date_fin_bannissement >= CURDATE())
    ")->fetchColumn();
    
    // Convertir en entiers pour éviter les problèmes d'affichage
    $count_livres = (int)$count_livres;
    $count_etudiants = (int)$count_etudiants;
    $count_emprunts = (int)$count_emprunts;
    $count_reserves = (int)$count_reserves;
    $count_retards = (int)$count_retards;
    $count_sanctions = (int)$count_sanctions;
    
    // Debug - À supprimer après vérification
    error_log("STATS DASHBOARD - Livres: $count_livres, Étudiants: $count_etudiants, Emprunts: $count_emprunts, Réservations: $count_reserves, Retards: $count_retards, Sanctions: $count_sanctions");
    
} catch (PDOException $e) {
    error_log("Erreur SQL Dashboard: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    $count_livres = $count_etudiants = $count_emprunts = $count_reserves = $count_retards = $count_sanctions = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Dashboard Admin - Lireo</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --sidebar-bg: #1a1f35;
            --sidebar-text: #b0b7c3;
            --white: #ffffff;
            --bg: #f4f6fb;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            display: flex;
            min-height: 90vh;
        }

        

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            flex: 1;
            margin-left: 0px;
            margin-right: 100px;
            padding: 10px 10px;
        }

        .header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .header h1 {
            font-size: 2rem;
            color: var(--primary-dark);
        }

        .datetime {
            text-align: right;
        }

        .time {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .date {
            font-size: 0.9rem;
            color: #666;
        }

        /* ==================== STATS CARDS ==================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--blue);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--white);
        }

        .stat-icon.blue { background: linear-gradient(135deg, #2a5298, #4a6fc1); }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.orange { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stat-icon.purple { background: linear-gradient(135deg, #6f42c1, #e83e8c); }
        .stat-icon.red { background: linear-gradient(135deg, #dc3545, #c82333); }
        .stat-icon.cyan { background: linear-gradient(135deg, #17a2b8, #20c997); }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 0.95rem;
        }

        /* ==================== CHARTS ==================== */
        .charts-section {
            margin-bottom: 40px;
        }

        .chart-full {
            background: var(--white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .chart-full h2 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-size: 1.4rem;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .chart-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .chart-card:hover {
            transform: translateY(-3px);
        }

        .chart-card h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        canvas {
            max-height: 280px;
        }

        /* ==================== QUICK ACTIONS ==================== */
        .section-title {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .action-btn {
            background: var(--white);
            padding: 25px 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--primary-dark);
            border: 2px solid transparent;
        }

        .action-btn:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(42,82,152,0.15);
        }

        .action-btn i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .action-btn p {
            font-weight: 600;
            font-size: 1rem;
        }

        .action-btn.green i { color: var(--success); }
        .action-btn.orange i { color: var(--warning); }
        .action-btn.red i { color: var(--danger); }
        .action-btn.cyan i { color: var(--info); }

        /* ==================== MANAGEMENT GRID ==================== */
        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .management-card {
            background: var(--white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            text-decoration: none;
            color: var(--primary-dark);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .management-card:hover {
            transform: translateX(5px);
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(42,82,152,0.15);
        }

        .management-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #e6cab4ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--white);
        }

        .management-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .management-info p {
            font-size: 0.85rem;
            color: #666;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }

            .sidebar-header h1,
            .user-details,
            .nav-item span,
            .logout-btn span {
                display: none;
            }

            .sidebar-header {
                padding: 20px 10px;
            }

            .logo {
                justify-content: center;
            }

            .nav-item {
                justify-content: center;
            }

            .user-info {
                justify-content: center;
                padding: 10px;
            }

            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        /* Animation de chargement */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- ==================== SIDEBAR ==================== -->
    
        <?php require_once __DIR__ . '/../../includes/sidebaradmin.php'; ?> 
    <!-- ==================== MAIN CONTENT ==================== -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                
                <div class="welcome-text">
                    <h1 class="fas fa-bars menu-toggle">  Bienvenue, <?= htmlspecialchars($admin_user['prenom']) ?> </h1>
                    <i class="fas fa-hands-helping fa-2x" style="margin-left: 10px;"></i>
                    <p>Voici un aperçu de votre bibliothèque</p>
                </div>
            </div>

            <div class="datetime">
                <div class="time" id="current-time"></div>
                <div class="date" id="current-date"></div>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_livres ?></h3>
                    <p>Total Livres</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_etudiants ?></h3>
                    <p>Étudiants Actifs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_emprunts ?></h3>
                    <p>Emprunts Actifs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_reserves ?></h3>
                    <p>Réservations</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_retards ?></h3>
                    <p>En Retard</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon cyan">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="stat-info">
                    <h3><?= (int)$count_sanctions ?></h3>
                    <p>Sanctions Actives</p>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="charts-section">
            <div class="chart-full">
                <h2><i class="fas fa-chart-line"></i> Évolution des Emprunts dans le Temps</h2>
                <canvas id="chartLoans"></canvas>
            </div>

            <div class="charts-grid">
                <div class="chart-card">
                    <h2><i class="fas fa-chart-pie"></i> Statuts des Emprunts</h2>
                    <canvas id="chartStatus"></canvas>
                </div>

                <div class="chart-card">
                    <h2><i class="fas fa-book"></i> Top 5 Livres Populaires</h2>
                    <canvas id="chartTopBooks"></canvas>
                </div>

                <div class="chart-card">
                    <h2><i class="fas fa-clock"></i> Retards & Sanctions</h2>
                    <canvas id="chartLate"></canvas>
                </div>

                <div class="chart-card">
                    <h2><i class="fas fa-exchange-alt"></i> Réservations vs Emprunts</h2>
                    <canvas id="chartReserve"></canvas>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Actions Rapides
            </h2>
            <div class="quick-actions">
                <a href="students_management.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <p>Ajouter Étudiant</p>
                </a>

                <a href="books_management.php" class="action-btn green">
                    <i class="fas fa-book-medical"></i>
                    <p>Ajouter Livre</p>
                </a>

                <a href="loans_management.php" class="action-btn orange">
                    <i class="fas fa-handshake"></i>
                    <p>Nouveau Prêt</p>
                </a>

                <a href="messages.php" class="action-btn cyan">
                    <i class="fas fa-envelope"></i>
                    <p>Messages</p>
                </a>

                <a href="sanctions.php" class="action-btn red">
                    <i class="fas fa-gavel"></i>
                    <p>Sanctions</p>
                </a>

                <a href="settings.php" class="action-btn">
                    <i class="fas fa-cog"></i>
                    <p>Paramètres</p>
                </a>
            </div>
        </section>

        <!-- Management Section -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-tasks"></i>
                Gestion du Système
            </h2>
            <div class="management-grid">
                <a href="books_management.php" class="management-card">
                    <div class="management-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="management-info">
                        <h3>Gestion des Livres</h3>
                        <p>Gérer le catalogue de livres</p>
                    </div>
                </a>

                <a href="students_management.php" class="management-card">
                    <div class="management-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="management-info">
                        <h3>Gestion des Étudiants</h3>
                        <p>Gérer les comptes étudiants</p>
                    </div>
                </a>

                <a href="loans_management.php" class="management-card">
                    <div class="management-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="management-info">
                        <h3>Gestion des Prêts</h3>
                        <p>Gérer emprunts et retours</p>
                    </div>
                </a>

                <a href="sanctions.php" class="management-card">
                    <div class="management-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div class="management-info">
                        <h3>Gestion des Sanctions</h3>
                        <p>Gérer les sanctions actives</p>
                    </div>
                </a>
            </div>
        </section>
    </main>

    <script>
        // ==================== HORLOGE TEMPS RÉEL ====================
        function updateDateTime() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            
            document.getElementById('current-time').textContent = `${hours}:${minutes}`;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('fr-FR', options);
        }

        updateDateTime();
        setInterval(updateDateTime, 60000);

        // ==================== FONCTION GÉNÉRIQUE POUR CHARTS ====================
        function createChart(url, canvasId, type, options = {}) {
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.json();
                })
                .then(data => {
                    const ctx = document.getElementById(canvasId);
                    if (!ctx) return;

                    const labels = data.map(d => d.label || d.mois || 'N/A');
                    const values = data.map(d => d.total || 0);

                    const colors = [
                        '#2a5298', '#4a6fc1', '#6f42c1', '#17a2b8', 
                        '#28a745', '#ffc107', '#dc3545', '#e83e8c'
                    ];

                    new Chart(ctx, {
                        type: type,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: options.label || 'Données',
                                data: values,
                                backgroundColor: type === 'line' ? 'rgba(42, 82, 152, 0.1)' : colors,
                                borderColor: type === 'line' ? '#2a5298' : colors,
                                borderWidth: 2,
                                borderRadius: type === 'bar' ? 8 : 0,
                                tension: 0.4,
                                fill: type === 'line'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: type !== 'line',
                                    position: 'bottom'
                                }
                            },
                            scales: type === 'line' || type === 'bar' ? {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0 }
                                }
                            } : {}
                        }
                    });
                })
                .catch(error => {
                    console.error(`Erreur chargement ${canvasId}:`, error);
                    const ctx = document.getElementById(canvasId);
                    if (ctx) {
                        const parent = ctx.parentElement;
                        parent.innerHTML += '<p style="color: #dc3545; text-align: center; margin-top: 20px;">Erreur de chargement des données</p>';
                    }
                });
        }

        // ==================== CHARGEMENT DES GRAPHIQUES ====================
        document.addEventListener('DOMContentLoaded', function() {
            createChart('../../api/admin/stats_loans_by_month.php', 'chartLoans', 'line', { label: 'Emprunts' });
            createChart('../../api/admin/stats_loan_status.php', 'chartStatus', 'doughnut');
            createChart('../../api/admin/stats_top_books.php', 'chartTopBooks', 'bar', { label: 'Emprunts' });
            createChart('../../api/admin/stats_retards_sanctions.php', 'chartLate', 'pie');
            createChart('../../api/admin/stats_reservation_vs_loans.php', 'chartReserve', 'bar', { label: 'Nombre' });
        });
    </script>
</body>
</html>