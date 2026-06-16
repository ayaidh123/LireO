<?php
// Fichier: students/messages.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer les administrateurs pour l'envoi de messages
$stmt_admins = $pdo->query("SELECT id_admin, nom, prenom, email FROM admin ORDER BY nom, prenom");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Lireo</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 2rem;
            color: var(--primary);
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
        }

        nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: var(--primary);
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
            max-width: 1400px;
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

        /* Layout */
        .messages-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 1024px) {
            .messages-layout {
                grid-template-columns: 300px 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Cards */
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .card h3 {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* New Message Button */
        .new-message-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .new-message-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-btn {
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            background-color: var(--light);
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-btn:hover {
            background-color: var(--gray-light);
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: var(--white);
        }

        /* Statistics */
        .stats-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: var(--gray);
            font-weight: 500;
        }

        .stat-value {
            font-weight: 600;
            color: var(--dark);
        }

        .stat-unread {
            color: var(--danger);
        }

        /* Messages Container */
        .messages-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            min-height: 500px;
        }

        .messages-header {
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }

        .messages-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .messages-header p {
            color: var(--gray);
            font-size: 1rem;
        }

        .messages-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Message Item */
        .message-item {
            background-color: var(--light);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--gray-light);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .message-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message-item.unread {
            background-color: rgba(42, 82, 152, 0.05);
            border-left-color: var(--primary);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .message-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .message-sender {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .message-preview {
            color: var(--dark);
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-date {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .read-btn {
            background-color: var(--success);
            color: var(--white);
        }

        .read-btn:hover {
            background-color: #218838;
        }

        .view-btn {
            background-color: var(--primary);
            color: var(--white);
        }

        .view-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Badge */
        .badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--white);
            display: inline-block;
        }

        .badge-new {
            background-color: var(--danger);
        }

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-light);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Error State */
        .error-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--danger);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            margin-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 15px;
        }

        .modal-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 700;
        }

        .modal-body {
            color: var(--dark);
        }

        /* Message Detail */
        .message-detail-header {
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .message-content {
            background-color: var(--light);
            border-radius: 8px;
            padding: 20px;
            line-height: 1.6;
            white-space: pre-wrap;
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .btn-cancel:hover {
            background-color: #d1d9e0;
        }

        .btn-submit {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-close {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .btn-close:hover {
            background-color: #d1d9e0;
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
            
            .messages-layout {
                grid-template-columns: 1fr;
            }
            
            .card,
            .messages-container {
                padding: 20px;
            }
            
            .empty-state i {
                font-size: 3rem;
            }
            
            .modal-content {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .message-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .message-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .message-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <h1>Lireo</h1>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">Catalogue</a>
            <a href="history.php">Historique</a>
            <a href="profile.php">Profil</a>
            <a href="messages.php" style="color: var(--primary);">Messagerie</a>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-envelope"></i> Messagerie</h1>
            <p>Communiquez avec les administrateurs de la bibliothèque</p>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour au dashboard</a>
        </div>

        <div class="messages-layout">
            <!-- Sidebar -->
            <div class="sidebar">
                <button onclick="openNewMessageModal()" class="new-message-btn">
                    <i class="fas fa-plus"></i> Nouveau message
                </button>

                <div class="card">
                    <h3><i class="fas fa-filter"></i> Filtres</h3>
                    <div class="filter-buttons">
                        <button onclick="loadMessages('received')" class="filter-btn active" data-filter="received">
                            <i class="fas fa-inbox"></i> Messages reçus
                        </button>
                        <button onclick="loadMessages('sent')" class="filter-btn" data-filter="sent">
                            <i class="fas fa-paper-plane"></i> Messages envoyés
                        </button>
                        <button onclick="loadUnreadMessages()" class="filter-btn">
                            <i class="fas fa-bell"></i> Messages non lus
                        </button>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Reçus:</span>
                            <span id="statsReceived" class="stat-value">...</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Envoyés:</span>
                            <span id="statsSent" class="stat-value">...</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Non lus:</span>
                            <span id="statsUnread" class="stat-value stat-unread">...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="messages-container">
                <div class="messages-header">
                    <h2 id="messagesTitle">Messages reçus</h2>
                    <p id="messagesSubtitle">Chargement...</p>
                </div>
                
                <div id="messagesContainer">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Chargement des messages...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- New Message Modal -->
    <div id="newMessageModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header"><i class="fas fa-envelope"></i> Nouveau message</h2>
            <form id="newMessageForm" class="modal-body">
                <div class="form-group">
                    <label class="form-label">Destinataire</label>
                    <select name="id_admin_recepteur" id="id_admin_recepteur" required class="form-select">
                        <option value="">Sélectionner un administrateur</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?= $admin['id_admin'] ?>">
                                <?= htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) ?> - <?= htmlspecialchars($admin['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Sujet</label>
                    <input type="text" name="sujet" id="sujet" required class="form-input" placeholder="Sujet du message">
                </div>

                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="contenu" id="contenu" required rows="6" class="form-textarea" placeholder="Votre message..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeNewMessageModal()" class="btn btn-cancel">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-submit">
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // ========================================
// JavaScript pour la page messages étudiant (à remplacer dans le <script>)
// ========================================

let messages = [];

document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    loadStats();
    setupEventListeners();
});

function setupEventListeners() {
    // Form submission
    document.getElementById('newMessageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });
    
    // Fermer la modal en cliquant en dehors
    document.getElementById('newMessageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeNewMessageModal();
        }
    });
}

function loadMessages() {
    const container = document.getElementById('messagesContainer');
    const title = document.getElementById('messagesTitle');
    const subtitle = document.getElementById('messagesSubtitle');

    // Pour les étudiants, on charge seulement les messages envoyés
    title.textContent = '📤 Mes messages';
    subtitle.textContent = 'Chargement...';

    container.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Chargement des messages...</p>
        </div>
    `;

    fetch('../../api/messages/get_messages.php')
        .then(response => {
            if (!response.ok) throw new Error('Erreur réseau');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                messages = data.data || [];
                subtitle.textContent = `${messages.length} message(s) envoyé(s)`;
                displayMessages(messages);
                loadStats(); // Recharger les stats
            } else {
                throw new Error(data.error || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erreur lors du chargement</h3>
                    <p>${error.message}</p>
                    <button onclick="loadMessages()" class="btn btn-submit" style="margin-top: 20px;">
                        Réessayer
                    </button>
                </div>
            `;
        });
}

function displayMessages(messagesToDisplay) {
    const container = document.getElementById('messagesContainer');
    
    if (!messagesToDisplay || messagesToDisplay.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-envelope"></i>
                <h3>Aucun message</h3>
                <p>Vous n'avez pas encore envoyé de message aux administrateurs</p>
                <button onclick="openNewMessageModal()" class="btn btn-submit" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Envoyer un message
                </button>
            </div>
        `;
        return;
    }

    let html = '<div class="messages-list">';
    
    messagesToDisplay.forEach(message => {
        const statusLabel = getStatusLabel(message.statut);
        const statusColor = getStatusColor(message.statut);
        
        html += `
            <div class="message-item ${message.statut === 'non_lu' ? 'unread' : ''}" onclick="viewMessage(${message.id_message})">
                <div class="message-header">
                    <div style="flex: 1;">
                        <h4 class="message-title">${escapeHtml(message.sujet)}</h4>
                        <div class="message-sender">
                            À: ${message.admin_nom ? escapeHtml(message.admin_prenom + ' ' + message.admin_nom) : 'Administration'}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="message-date">${formatDate(message.date_envoi)}</div>
                        <span class="badge" style="background-color: ${statusColor}; margin-top: 5px;">
                            ${statusLabel}
                        </span>
                    </div>
                </div>
                <p class="message-preview">${escapeHtml(message.contenu)}</p>
                <div class="message-footer">
                    <div class="message-actions">
                        <button onclick="event.stopPropagation(); viewMessage(${message.id_message})" class="action-btn view-btn">
                            <i class="fas fa-eye"></i> Voir détails
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewMessage(messageId) {
    const message = messages.find(msg => msg.id_message == messageId);
    if (!message) return;

    const statusLabel = getStatusLabel(message.statut);
    const statusColor = getStatusColor(message.statut);

    const modalContent = `
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-envelope"></i> ${escapeHtml(message.sujet)}</h2>
            </div>
            <div class="modal-body">
                <div class="message-detail-header">
                    <div>
                        <strong>À:</strong> ${message.admin_nom ? escapeHtml(message.admin_prenom + ' ' + message.admin_nom) : 'Administration'}
                    </div>
                    <div>
                        <strong>Date d'envoi:</strong> ${formatDateTime(message.date_envoi)}
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Statut:</strong> 
                    <span class="badge" style="background-color: ${statusColor};">${statusLabel}</span>
                    ${message.date_traitement ? `<div style="margin-top: 5px; color: var(--gray); font-size: 0.9rem;">Traité le: ${formatDateTime(message.date_traitement)}</div>` : ''}
                </div>
                
                <div class="message-content">
                    ${escapeHtml(message.contenu)}
                </div>
                
                <div class="modal-actions" style="justify-content: flex-end;">
                    <button onclick="closeMessageModal()" class="btn btn-close">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                </div>
            </div>
        </div>
    `;

    const modal = document.createElement('div');
    modal.id = 'messageDetailModal';
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = modalContent;
    document.body.appendChild(modal);

    window.closeMessageModal = function() {
        modal.remove();
    };
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('messageDetailModal')) {
            closeMessageModal();
        }
    });
}

function sendMessage() {
    const form = document.getElementById('newMessageForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Désactiver le bouton
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
    
    fetch('../../api/messages/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau');
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Succès
            alert('✅ ' + data.message);
            closeNewMessageModal();
            form.reset();
            loadMessages(); // Recharger les messages
        } else {
            // Erreur
            alert('❌ ' + (data.error || 'Erreur lors de l\'envoi'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur: ' + error.message);
    })
    .finally(() => {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
    });
}

function loadStats() {
    fetch('../../api/messages/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const stats = data.data;
                document.getElementById('statsReceived').textContent = stats.total_received || 0;
                document.getElementById('statsSent').textContent = stats.total_sent || 0;
                document.getElementById('statsUnread').textContent = stats.unread || 0;
            }
        })
        .catch(error => console.error('Erreur stats:', error));
}

function openNewMessageModal() {
    document.getElementById('newMessageModal').style.display = 'flex';
    document.getElementById('newMessageForm').reset();
}

function closeNewMessageModal() {
    document.getElementById('newMessageModal').style.display = 'none';
}

function getStatusLabel(statut) {
    const labels = {
        'non_lu': 'Non lu',
        'en_cours': 'En cours',
        'traite': 'Traité'
    };
    return labels[statut] || statut;
}

function getStatusColor(statut) {
    const colors = {
        'non_lu': '#ffc107',   // Jaune
        'en_cours': '#17a2b8',  // Bleu
        'traite': '#28a745'     // Vert
    };
    return colors[statut] || '#6c757d';
}

function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
    </script>
</body>
</html>