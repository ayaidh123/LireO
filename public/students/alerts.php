<?php
// Fichier: students/alerts.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer toutes les alertes de l'étudiant
$stmt = $pdo->prepare("
    SELECT * FROM alerte 
    WHERE id_etudiant = ?
    ORDER BY date_envoi DESC
");
$stmt->execute([$user['id']]);
$alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les alertes non lues
$unread_count = count(array_filter($alertes, function($alerte) {
    return $alerte['etat'] === 'non_lu';
}));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Alertes - Lireo</title>
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

        /* Header Styles adaptés pour la sidebar */
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
            margin-top: 10px;
            margin-left: 550px;
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-total {
            color: var(--primary);
        }

        .stat-unread {
            color: var(--danger);
        }

        .stat-read {
            color: var(--success);
        }

        .stat-label {
            color: var(--gray);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Filters Container */
        .filters-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .filters-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .filters-content {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background-color: var(--gray-light);
            color: var(--dark);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background-color: var(--secondary);
            color: var(--white);
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
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
        }

        .mark-all-btn {
            background-color: var(--success);
            color: var(--white);
        }

        .mark-all-btn:hover {
            background-color: #218838;
        }

        .delete-read-btn {
            background-color: var(--danger);
            color: var(--white);
        }

        .delete-read-btn:hover {
            background-color: #c82333;
        }

        /* Alerts Container */
        .alerts-container {
            margin-bottom: 30px;
        }

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
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Alert Item */
        .alert-item {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
        }

        .alert-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .alert-item.unread {
            border-left: 4px solid var(--primary);
            background-color: rgba(42, 82, 152, 0.05);
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .alert-title-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(42, 82, 152, 0.1);
            border-radius: 10px;
            color: var(--primary);
        }

        .alert-title-content h3 {
            font-size: 1.3rem;
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: capitalize;
        }

        .alert-date {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .alert-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-new {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .status-read {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .alert-menu {
            position: relative;
        }

        .menu-button {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .menu-button:hover {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--card-bg);
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 180px;
            z-index: 100;
            overflow: hidden;
        }

        .dropdown-menu.hidden {
            display: none;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 12px 15px;
            border: none;
            background: none;
            text-align: left;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background-color: rgba(42, 82, 152, 0.05);
        }

        .dropdown-item.read {
            color: var(--success);
        }

        .dropdown-item.delete {
            color: var(--danger);
        }

        /* Alert Body */
        .alert-body {
            background-color: rgba(42, 82, 152, 0.05);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .alert-message {
            color: var(--dark);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .alert-footer {
            display: flex;
            justify-content: flex-end;
        }

        .mark-read-btn {
            background-color: var(--success);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mark-read-btn:hover {
            background-color: #218838;
        }

        .mark-read-btn.hidden {
            display: none;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }

        .pagination button {
            background-color: var(--card-bg);
            border: 1px solid var(--gray-light);
            color: var(--dark);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination button:hover {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .pagination button.active {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        /* Hidden utility */
        .hidden {
            display: none !important;
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
            
            .alert-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .alert-actions {
                justify-content: space-between;
            }
            
            .filter-buttons,
            .action-buttons {
                justify-content: center;
            }
            
            .filter-btn,
            .action-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .filters-container,
            .alert-item {
                padding: 20px;
            }
            
            .alert-title-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .pagination button {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/sidebarstudent.php'; ?>
    <main class="main-content"><!-- Header -->
       <header class="header" style="display: flex; justify-content: flex-start; align-items: center; gap: 20px;">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Mes Alertes</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Consultez et gérez vos alertes et notifications</p>
            </div>
        </header>

   
        
        

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value stat-total"><?= count($alertes) ?></div>
                <div class="stat-label">Total alertes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-unread"><?= $unread_count ?></div>
                <div class="stat-label">Non lues</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-read"><?= count($alertes) - $unread_count ?></div>
                <div class="stat-label">Lues</div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="filters-container">
            <div class="filters-content">
                <div class="filter-buttons">
                    <button onclick="filterAlerts('all')" class="filter-btn active">
                        Toutes
                    </button>
                    <button onclick="filterAlerts('unread')" class="filter-btn">
                        Non lues
                    </button>
                    <button onclick="filterAlerts('read')" class="filter-btn">
                        Lues
                    </button>
                </div>
                
                <div class="action-buttons">
                    <button onclick="markAllAsRead()" class="action-btn mark-all-btn">
                        <i class="fas fa-check"></i> Tout marquer comme lu
                    </button>
                    <button onclick="deleteAllRead()" class="action-btn delete-read-btn">
                        <i class="fas fa-trash"></i> Supprimer les lues
                    </button>
                </div>
            </div>
        </div>

        <!-- Alerts List -->
        <div id="alertsContainer" class="alerts-container">
            <?php if (empty($alertes)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <h2>Aucune alerte</h2>
                    <p>Vous n'avez reçu aucune alerte pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($alertes as $alerte): ?>
                    <div class="alert-item <?= $alerte['etat'] === 'non_lu' ? 'unread' : '' ?>" 
                         data-state="<?= $alerte['etat'] ?>">
                        <div class="alert-header">
                            <div class="alert-title-container">
                                <div class="alert-icon">
                                    <?php 
                                    $icons = [
                                        'retard'              => '<i class="fas fa-clock text-danger"></i>',            
                                        'rappel_reservation'  => '<i class="fas fa-calendar-check text-warning"></i>',  
                                        'disponibilite_livre' => '<i class="fas fa-check-circle text-success"></i>',    
                                        'sanction'            => '<i class="fas fa-gavel text-danger"></i>',           
                                        'information'         => '<i class="fas fa-info-circle text-info"></i>'         
                                    ];

                                    echo $icons[$alerte['type_alerte']] ?? '<i class="fas fa-bell text-secondary"></i>';
                                    ?>
                                </div>
                                <div class="alert-title-content">
                                    <h3><?= str_replace('_', ' ', $alerte['type_alerte']) ?></h3>
                                    <div class="alert-date">
                                        <?= date('d/m/Y à H:i', strtotime($alerte['date_envoi'])) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert-actions">
                                <span class="status-badge <?= $alerte['etat'] === 'non_lu' ? 'status-new' : 'status-read' ?>">
                                    <?= $alerte['etat'] === 'non_lu' ? 'Nouveau' : 'Lu' ?>
                                </span>
                                
                                <div class="alert-menu">
                                    <button class="menu-button" onclick="toggleAlertMenu(<?= $alerte['id_alerte'] ?>)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    
                                    <div id="menu-<?= $alerte['id_alerte'] ?>" class="dropdown-menu hidden">
                                        <?php if ($alerte['etat'] === 'non_lu'): ?>
                                            <button onclick="markAlertAsRead(<?= $alerte['id_alerte'] ?>)" class="dropdown-item read">
                                                <i class="fas fa-check"></i> Marquer comme lu
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="deleteAlert(<?= $alerte['id_alerte'] ?>)" class="dropdown-item delete">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert-body">
                            <div class="alert-message"><?= htmlspecialchars($alerte['message']) ?></div>
                        </div>
                        
                        <?php if ($alerte['etat'] === 'non_lu'): ?>
                            <div class="alert-footer">
                                <button onclick="markAlertAsRead(<?= $alerte['id_alerte'] ?>)" class="mark-read-btn">
                                    <i class="fas fa-check"></i> Marquer comme lu
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (count($alertes) > 10): ?>
            <div class="pagination">
                <button><i class="fas fa-chevron-left"></i> Précédent</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button>Suivant <i class="fas fa-chevron-right"></i></button>
            </div>
        <?php endif; ?>
    </main>

    <script>
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
            
            // Lancer le chargement des livres
            loadBooks();
            updateTime();
            setInterval(updateTime, 1000);
        });
        function updateTime() {
            const now = new Date();
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if(timeElement) {
                timeElement.textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            }
            if(dateElement) {
                dateElement.textContent = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            }
        }

        let currentFilter = 'all';

        function filterAlerts(filter) {
            currentFilter = filter;
            const alerts = document.querySelectorAll('.alert-item');
            
            alerts.forEach(alert => {
                const state = alert.getAttribute('data-state');
                
                switch(filter) {
                    case 'all':
                        alert.style.display = 'block';
                        break;
                    case 'unread':
                        alert.style.display = state === 'non_lu' ? 'block' : 'none';
                        break;
                    case 'read':
                        alert.style.display = state === 'lu' ? 'block' : 'none';
                        break;
                }
            });
            
            // Update filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function toggleAlertMenu(alertId) {
            const menu = document.getElementById(`menu-${alertId}`);
            const allMenus = document.querySelectorAll('.dropdown-menu');
            
            // Close all other menus
            allMenus.forEach(m => {
                if (m.id !== `menu-${alertId}`) {
                    m.classList.add('hidden');
                }
            });
            
            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.alert-menu') && !e.target.matches('.menu-button, .menu-button *')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        function markAlertAsRead(alertId) {
            fetch('../../api/alerts/mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_alerte=' + alertId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update the UI
                    const alertElement = document.querySelector(`.alert-item[data-state="non_lu"]`);
                    if (alertElement) {
                        alertElement.setAttribute('data-state', 'lu');
                        alertElement.classList.remove('unread');
                        
                        // Update the badge
                        const badge = alertElement.querySelector('.status-badge');
                        if (badge) {
                            badge.classList.remove('status-new');
                            badge.classList.add('status-read');
                            badge.textContent = 'Lu';
                        }
                        
                        // Hide the "Mark as read" button
                        const button = alertElement.querySelector('.mark-read-btn');
                        if (button) {
                            button.classList.add('hidden');
                        }
                    }
                    
                    // Refilter if necessary
                    if (currentFilter === 'unread') {
                        filterAlerts('unread');
                    }
                    
                    // Update statistics
                    updateStats();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors du marquage de l\'alerte');
            });
        }

        function markAllAsRead() {
            if (!confirm('Marquer toutes les alertes comme lues ?')) return;
            
            fetch('../../api/alerts/mark_all_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Toutes les alertes ont été marquées comme lues');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors du marquage des alertes');
            });
        }

        function deleteAlert(alertId) {
            if (!confirm('Supprimer cette alerte ?')) return;
            
            fetch('../../api/alerts/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_alerte=' + alertId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove element from DOM
                    const alertElement = document.querySelector(`.alert-item`);
                    if (alertElement) {
                        alertElement.remove();
                    }
                    
                    // Reload if no more alerts
                    if (document.querySelectorAll('.alert-item').length === 0) {
                        location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors de la suppression de l\'alerte');
            });
        }

        function deleteAllRead() {
            if (!confirm('Supprimer toutes les alertes lues ?')) return;
            
            fetch('../../api/alerts/delete_all_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Toutes les alertes lues ont été supprimées');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur lors de la suppression des alertes');
            });
        }

        function updateStats() {
            // This function could update counters in real time
            // For now, we reload the page to simplify
            // In a future version, we could make AJAX calls to update counters
            location.reload();
        }
    </script>
</body>
</html>