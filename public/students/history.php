<?php
// Fichier: students/history.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer les emprunts avec les détails
$stmt = $pdo->prepare("
    SELECT 
        e.id_emprunt,
        e.isbn,
        l.titre,
        l.auteur,
        l.image_livre,
        e.date_emprunt,
        e.date_retour_prevue,
        e.date_retour_reel,
        e.statut_emprunt,
        CASE 
            WHEN e.date_retour_reel IS NOT NULL THEN 'Retourné'
            WHEN e.statut_emprunt = 'en_attente_retrait' THEN 'En attente retrait'
            WHEN CURDATE() > e.date_retour_prevue THEN 'En retard'
            ELSE 'En cours'
        END AS statut_affichage,
        (SELECT COUNT(*) FROM prolongation p WHERE p.emprunt_id = e.id_emprunt AND p.statut = 'en_attente') as prolongation_en_attente
    FROM emprunt e
    JOIN livre l ON e.isbn = l.isbn
    WHERE e.id_etudiant = ?
    ORDER BY e.date_emprunt DESC
");
$stmt->execute([$user['id']]);
$emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Emprunts - Lireo</title>
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
            width: calc(100% - 260px);
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
        /* Configuration de base du contenu principal */
        .main-content {
            margin-left: 0px; /* Largeur de votre sidebar ouverte */
            padding: 30px 20px;
            transition: all 0.5s ease; /* Animation fluide quand le menu bouge */
            min-height: 100vh;
        }

        /* Si le menu est fermé (classe gérée par votre JS) */
        .main-content.expand {
            margin-left: 0px; /* Largeur de la sidebar fermée (ajustez selon votre sidebar) */
        }

        /* Responsive : Sur mobile, on enlève la marge car le menu disparaît souvent */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
        /* Ajustement si sidebar ouverte (géré par JS via la classe .expand) */
        .main.expand {
            width: 0px;
            margin-left: 0px; /* Si la sidebar est fixed */
            margin-right : 0px;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            color: var(--dark);
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: rgba(42, 82, 152, 0.05);
        }

        

        .logout-btn {
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

        .logout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Main Content */
        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
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
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Loans Container */
        .loans-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Loan Item */
        .loan-item {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
        }

        .loan-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .loan-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        @media (max-width: 768px) {
            .loan-content {
                flex-direction: column;
                gap: 20px;
            }
        }

        /* Book Info */
        .book-info {
            display: flex;
            gap: 20px;
            flex: 1;
        }

        @media (max-width: 480px) {
            .book-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }

        .book-cover {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .book-details {
            flex: 1;
        }

        .book-title {
            font-size: 1.3rem;
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .book-author {
            color: var(--gray);
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .loan-dates {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .date-item i {
            color: var(--primary);
            width: 16px;
        }

        /* Loan Actions */
        .loan-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
            min-width: 200px;
        }

        @media (max-width: 768px) {
            .loan-actions {
                align-items: stretch;
                width: 100%;
            }
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .status-returned {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        .status-late {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .status-active {
            background-color: rgba(42, 82, 152, 0.1);
            color: var(--primary);
        }

        /* Action Buttons */
        .extension-btn {
            background-color: var(--info);
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
            width: 100%;
        }

        .extension-btn:hover {
            background-color: #138496;
        }

        .pending-badge {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
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
            width: 100%;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            
            .header {
                margin-left: 0;
                width: 100%;
            }

            .user-info {
                flex-direction: column;
                gap: 15px;
                width: 100%;
            }
            
            nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .logout-btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .loan-item {
                padding: 20px;
            }
            
            .book-cover {
                width: 80px;
                height: 120px;
            }
            
            .book-title {
                font-size: 1.1rem;
            }
            
            .status-badge {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            
            
            .user-name {
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/sidebarstudent.php'; ?>
    

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <!-- Header -->
        <header class="header" style="display: flex; justify-content: flex-start; align-items: center; gap: 20px;">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Historique</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Consultez l'historique de vos emprunts</p>
            </div>
        </header>
        <!-- Loans List -->
        <?php if (empty($emprunts)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h2>Aucun emprunt</h2>
                <p>Vous n'avez effectué aucun emprunt pour le moment.</p>
                <a href="books.php" style="margin-top: 20px; display: inline-block;" class="extension-btn">
                    <i class="fas fa-search"></i> Explorer le catalogue
                </a>
            </div>
        <?php else: ?>
            <div class="loans-container">
                <?php foreach ($emprunts as $emp): ?>
                    <?php
                    // Determine status class and icon
                    $statusClass = 'status-active';
                    $statusIcon = 'fas fa-clock';
                    $statusText = $emp['statut_affichage'];
                    
                    if ($emp['statut_affichage'] === 'Retourné') {
                        $statusClass = 'status-returned';
                        $statusIcon = 'fas fa-check-circle';
                    } elseif ($emp['statut_affichage'] === 'En retard') {
                        $statusClass = 'status-late';
                        $statusIcon = 'fas fa-exclamation-triangle';
                    } elseif ($emp['statut_affichage'] === 'En attente retrait') {
                        $statusClass = 'status-pending';
                        $statusIcon = 'fas fa-hourglass-half';
                    } elseif ($emp['statut_affichage'] === 'En cours') {
                        $statusClass = 'status-active';
                        $statusIcon = 'fas fa-book-open';
                    }
                    ?>
                    
                    <div class="loan-item">
                        <div class="loan-content">
                            <div class="book-info">
                                <img src="../<?= htmlspecialchars($emp['image_livre']) ?>" 
                                     alt="<?= htmlspecialchars($emp['titre']) ?>"
                                     class="book-cover"
                                     >
                                <div class="book-details">
                                    <h3 class="book-title"><?= htmlspecialchars($emp['titre']) ?></h3>
                                    <div class="book-author">Auteur: <?= htmlspecialchars($emp['auteur']) ?></div>
                                    
                                    <div class="loan-dates">
                                        <div class="date-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            Emprunté le <?= date('d/m/Y', strtotime($emp['date_emprunt'])) ?>
                                        </div>
                                        <div class="date-item">
                                            <i class="fas fa-calendar-check"></i>
                                            À retourner le <?= date('d/m/Y', strtotime($emp['date_retour_prevue'])) ?>
                                        </div>
                                        <?php if ($emp['date_retour_reel']): ?>
                                            <div class="date-item">
                                                <i class="fas fa-calendar-minus"></i>
                                                Retourné le <?= date('d/m/Y', strtotime($emp['date_retour_reel'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="loan-actions">
                                <div class="status-badge <?= $statusClass ?>">
                                    <i class="<?= $statusIcon ?>"></i>
                                    <?= $statusText ?>
                                </div>
                                
                                <?php if ($emp['statut_affichage'] === 'En cours' && !$emp['prolongation_en_attente']): ?>
                                    <button onclick="requestExtension(<?= $emp['id_emprunt'] ?>)" 
                                            class="extension-btn">
                                        <i class="fas fa-calendar-plus"></i> Demander une prolongation
                                    </button>
                                <?php elseif ($emp['prolongation_en_attente']): ?>
                                    <div class="pending-badge">
                                        <i class="fas fa-hourglass-half"></i> Prolongation en attente
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($emp['statut_emprunt'] === 'en_attente_retrait'): ?>
                                    <button onclick="cancelBorrowRequest(<?= $emp['id_emprunt'] ?>)" 
                                            class="cancel-btn">
                                        <i class="fas fa-times"></i> Annuler la demande
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
        });
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page chargée, démarrage de l\'historique des emprunts...');
        });

        function cancelBorrowRequest(id_emprunt) {
            if (!confirm('Annuler cette demande d\'emprunt ?')) return;
            
            fetch('../../api/emprunt/cancel_borrow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_emprunt=' + id_emprunt
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Erreur lors de l\'annulation');
                console.error(error);
            });
        }

        function requestExtension(id_emprunt) {
            if (!confirm("Demander une prolongation de 7 jours pour cet emprunt ?")) return;
            
            fetch('../../api/emprunt/request_extension.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_emprunt=' + id_emprunt
            })
            .then(res => res.json())
            .then(json => {
                if (json.error) {
                    alert("❌ " + json.error);
                } else {
                    alert("✅ " + json.message);
                    location.reload();
                }
            })
            .catch(error => {
                alert("❌ Erreur lors de la demande de prolongation");
                console.error(error);
            });
        }
    </script>
</body>
</html>