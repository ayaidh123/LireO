<?php
// Fichier: public/admin/messages.php - VERSION RETRAVAILLÉE
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin'); // Assure que seul un admin peut accéder

$admin_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Lireo Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ======================================== */
        /* BASE & VARIABLES */
        /* ======================================== */
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
            --sidebar-bg: #1a1f35; /* Non utilisé ici mais gardé */
            --sidebar-text: #b0b7c3; /* Non utilisé ici mais gardé */
            --sidebar-active: #2a5298; /* Non utilisé ici mais gardé */
            --content-bg: #f5f7fa;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            min-height: 100vh;
            background-color: var(--content-bg);
        }

        /* ======================================== */
        /* UTILS & COMPONENTS */
        /* ======================================== */

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1;
        }

        .badge-new {
            background-color: var(--danger);
            animation: pulse 1s infinite alternate;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            100% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
        }

        .btn-submit {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-close {
            background-color: var(--gray);
            color: var(--white);
        }

        .btn-close:hover {
            background-color: var(--dark);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(42, 82, 152, 0.2);
        }

        /* ======================================== */
        /* MODAL */
        /* ======================================== */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none; /* Cache par défaut */
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 15px;
            max-width: 90%;
            width: 700px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: fadeIn 0.3s ease-out;
            margin: 40px 0; /* Pour l'espace en haut/bas si le contenu est grand */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray-light);
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 1.6rem;
        }

        .modal-body {
            padding: 30px;
        }

        .message-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            background-color: var(--gray-light);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .message-content {
            white-space: pre-wrap;
            background-color: var(--content-bg);
            padding: 20px;
            border-radius: 8px;
            line-height: 1.7;
            border-left: 5px solid var(--primary-light);
            margin-top: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px dashed var(--gray-light);
            margin-top: 20px;
        }
        
        /* Styles existants conservés */
        body {
            /* ... (keep body styles) ... */
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
         .main-content {
            position: relative;
            background-color: var(--content-bg);
            min-height: 100vh;
            top: 0;
            left: 78px; /* Largeur sidebar fermée par défaut */
            width: calc(100% - 78px);
            transition: all 0.5s ease;
            padding: -20px 20px;
        }
        .sidebar.active ~ .main-content { 
           /* Ajuste selon ton CSS sidebar global */
        }
        /* Ajustement si sidebar ouverte (géré par JS via la classe .expand) */
        .main-content.expand {
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

        /* Grid Layout */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 1024px) {
            .grid-layout {
                grid-template-columns: 300px 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .new-message-btn {
            background-color: var(--success);
            color: var(--white);
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
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
            background-color: #218838;
            transform: translateY(-2px);
        }

        .sidebar-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .sidebar-card h3 {
            font-size: 1.3rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .filter-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-btn {
            background: none;
            border: none;
            padding: 12px 15px;
            text-align: left;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-btn:hover {
            background-color: rgba(42, 82, 152, 0.1);
        }

        .filter-btn.active {
            background-color: rgba(42, 82, 152, 0.15);
            color: var(--primary);
            font-weight: 600;
        }

        .stats-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-light);
        }

        .stat-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .stat-label {
            color: var(--gray);
        }

        .stat-value {
            font-weight: 600;
            color: var(--dark);
        }

        .stat-value.unread {
            color: var(--danger);
        }

        /* Main Content Area */
        .content-area {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .content-header {
            padding: 25px;
            border-bottom: 2px solid var(--gray-light);
        }

        .content-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .content-header p {
            color: var(--gray);
            font-size: 1rem;
        }

        .messages-container {
            padding: 25px;
            min-height: 400px;
        }

        /* Message Items */
        .message-item {
            padding: 20px;
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.3s ease;
            cursor: pointer; /* Ajout du curseur pour indiquer la clicabilité */
        }

        .message-item:hover {
            background-color: rgba(42, 82, 152, 0.05);
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .message-sender {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .message-date {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .message-subject {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .message-preview {
            color: var(--gray);
            line-height: 1.5;
            margin-bottom: 15px;
            /* Limiter la prévisualisation à une ligne */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .message-btn {
            background: none;
            border: 1px solid var(--gray-light);
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .read-btn {
            color: var(--primary);
            border-color: var(--primary);
        }

        .read-btn:hover {
            background-color: rgba(42, 82, 152, 0.1);
        }

        .delete-btn {
            color: var(--danger);
            border-color: var(--danger);
        }

        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
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

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .loading-state i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
            
            .grid-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .content-area {
                order: 1;
            }
            
            .content-header,
            .messages-container {
                padding: 20px;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .message-header > div:last-child {
                text-align: left !important;
            }
            
            .message-actions {
                flex-direction: row; /* Garde les boutons côte à côte */
                flex-wrap: wrap;
            }

            .modal-content {
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .sidebar-card,
            .content-area {
                padding: 20px;
            }
            .message-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Messagerie</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Gérez les messages avec les étudiants</p>
            </div>
            <a href="dashboard.php"><i class="fas fa-home"></i> Accueil</a>
    
        </div>
    
            
    </header>

    <main>
        

        <div class="grid-layout">
            <div class="sidebar">
                <button onclick="openNewMessageModal()" class="new-message-btn">
                    <i class="fas fa-paper-plane"></i> Nouveau message
                </button>

                <div class="sidebar-card">
                    <h3><i class="fas fa-filter"></i> Boîtes</h3>
                    <div class="filter-buttons">
                        <button id="filterReceived" onclick="loadMessages('received', this)" class="filter-btn active">
                            <i class="fas fa-inbox"></i> Messages reçus
                        </button>
                        <button id="filterSent" onclick="loadMessages('sent', this)" class="filter-btn">
                            <i class="fas fa-paper-plane"></i> Messages envoyés
                        </button>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-label">Reçus (Total):</span>
                            <span id="statsReceived" class="stat-value">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Envoyés (Admin):</span>
                            <span id="statsSent" class="stat-value">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Non lus:</span>
                            <span id="statsUnread" class="stat-value unread">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <div class="content-header">
                    <h2 id="messagesTitle"><i class="fas fa-inbox"></i> Messages reçus</h2>
                    <p id="messagesSubtitle">Chargement...</p>
                </div>
                
                <div id="messagesContainer" class="messages-container">
                    </div>
            </div>
        </div>
    </main>

    <div id="newMessageModal" class="modal">
        <div class="modal-content">
            <div style="position: relative;">
                <div class="modal-header">
                    <h2><i class="fas fa-paper-plane"></i> Nouveau message à un étudiant</h2>
                </div>
                <button onclick="closeMessageModal('newMessageModal')" class="btn btn-close" style="position: absolute; top: 20px; right: 20px;">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
            <form id="sendMessageForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="recipientStudent">Destinataire (Étudiant)</label>
                        <select id="recipientStudent" name="id_etudiant_recepteur" required>
                            <option value="">-- Chargement des étudiants... --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="messageSubject">Sujet</label>
                        <input type="text" id="messageSubject" name="sujet" required maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="messageContent">Contenu</label>
                        <textarea id="messageContent" name="contenu" required></textarea>
                    </div>

                    <div class="modal-actions" style="border-top: none; margin-top: 0; justify-content: flex-end;">
                        <button type="submit" class="btn btn-submit" id="sendMessageBtn">
                            <i class="fas fa-paper-plane"></i> Envoyer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // ========================================
        // JavaScript pour la page messages admin (Rework)
        // ========================================

        let messages = [];
        let currentFilter = 'received';
        const MESSAGE_API_URL = '../../api/messages';
        const USER_API_URL = '../../api/users'; // Assurez-vous d'avoir cet endpoint

        document.addEventListener('DOMContentLoaded', function() {
            // Utiliser le bouton 'received' pour l'initialisation et la classe 'active'
            const initialButton = document.getElementById('filterReceived');
            loadMessages('received', initialButton);
            loadStats();
            
            // Initialiser le formulaire d'envoi
            document.getElementById('sendMessageForm').addEventListener('submit', handleSendMessage);
        });

        function loadMessages(type, clickedButton) {
            currentFilter = type;
            const container = document.getElementById('messagesContainer');
            const title = document.getElementById('messagesTitle');
            const subtitle = document.getElementById('messagesSubtitle');
            
            // Mise à jour des titres
            if (type === 'received') {
                title.innerHTML = '<i class="fas fa-inbox"></i> Messages reçus';
                subtitle.textContent = 'Messages envoyés par les étudiants';
            } else if (type === 'sent') {
                title.innerHTML = '<i class="fas fa-paper-plane"></i> Messages envoyés';
                subtitle.textContent = 'Messages envoyés aux étudiants';
            }

            // Mise à jour des boutons de filtre
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (clickedButton) {
                clickedButton.classList.add('active');
            }

            // Affichage du loading
            container.innerHTML = `
                <div class="loading-state">
                    <i class="fas fa-spinner"></i>
                    <p>Chargement des messages...</p>
                </div>
            `;

            fetch(`${MESSAGE_API_URL}/get_messages.php?type=${type}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau ou du serveur');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        messages = data.data || [];
                        subtitle.textContent = `${messages.length} message(s) ${type === 'received' ? 'des étudiants' : 'envoyé(s)'}`;
                        displayMessages(messages);
                        loadStats();
                    } else {
                        throw new Error(data.error || 'Erreur inconnue');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    container.innerHTML = `
                        <div class="empty-state" style="color: var(--danger);">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Erreur lors du chargement</h3>
                            <p>${error.message}</p>
                            <button onclick="loadMessages('${type}', document.getElementById('filter${type.charAt(0).toUpperCase() + type.slice(1)}'))" class="btn btn-submit" style="margin-top: 20px;">
                                <i class="fas fa-redo"></i> Réessayer
                            </button>
                        </div>
                    `;
                    subtitle.textContent = 'Erreur de chargement.';
                });
        }

        function displayMessages(messagesToDisplay) {
            const container = document.getElementById('messagesContainer');
            
            if (!messagesToDisplay || messagesToDisplay.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucun message</h3>
                        <p>Aucun message ${currentFilter === 'received' ? 'des étudiants à traiter' : 'envoyé par vous'} pour le moment.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            
            messagesToDisplay.forEach(message => {
                const isUnread = message.statut === 'non_lu';
                const statusLabel = getStatusLabel(message.statut);
                const statusColor = getStatusColor(message.statut);
                
                // Affichage conditionnel de l'expéditeur/destinataire
                let primaryUser = '';
                let primaryUserId = '';

                if (currentFilter === 'received') {
                    // Messages reçus (Étudiant -> Admin)
                    primaryUser = message.etudiant_prenom ? escapeHtml(message.etudiant_prenom + ' ' + message.etudiant_nom) : 'Étudiant inconnu';
                    primaryUserId = message.etudiant_matricule ? `<span style="color: var(--gray); font-size: 0.9rem;"> (${escapeHtml(message.etudiant_matricule)})</span>` : '';
                    primaryUserLabel = '<i class="fas fa-user"></i> De: ';
                } else if (currentFilter === 'sent') {
                    // Messages envoyés (Admin -> Étudiant)
                    primaryUser = message.etudiant_prenom ? escapeHtml(message.etudiant_prenom + ' ' + message.etudiant_nom) : 'Étudiant inconnu';
                    primaryUserId = message.etudiant_matricule ? `<span style="color: var(--gray); font-size: 0.9rem;"> (Matricule: ${escapeHtml(message.etudiant_matricule)})</span>` : '';
                    primaryUserLabel = '<i class="fas fa-reply"></i> À: ';
                }

                html += `
                    <div class="message-item" onclick="viewMessage(${message.id_message})">
                        <div class="message-header">
                            <div style="flex: 1;">
                                <div class="message-sender">
                                    ${primaryUserLabel} ${primaryUser} ${primaryUserId}
                                    ${isUnread && currentFilter === 'received' ? '<span class="badge badge-new" style="margin-left: 10px;">NOUVEAU</span>' : ''}
                                </div>
                                <div class="message-subject">
                                    ${escapeHtml(message.sujet)}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="message-date">${formatDate(message.date_envoi)}</div>
                                <span class="badge" style="background-color: ${statusColor}; margin-top: 5px; font-size: 0.75rem;">
                                    ${statusLabel}
                                </span>
                            </div>
                        </div>
                        <div class="message-preview">${escapeHtml(message.contenu)}</div>
                        <div class="message-actions">
                            <button onclick="event.stopPropagation(); viewMessage(${message.id_message})" class="message-btn read-btn">
                                <i class="fas fa-eye"></i> Lire
                            </button>
                            ${(currentFilter === 'received' && isUnread) ? `
                                <button onclick="event.stopPropagation(); markAsRead(${message.id_message})" class="message-btn btn-success">
                                    <i class="fas fa-check"></i> Marquer traité
                                </button>
                            ` : ''}
                            ${currentFilter === 'received' ? `
                                <button onclick="event.stopPropagation(); openNewMessageModal(${message.id_etudiant_expediteur})" class="message-btn btn-submit">
                                    <i class="fas fa-reply"></i> Répondre
                                </button>
                            ` : ''}
                            </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function viewMessage(messageId) {
            const message = messages.find(msg => msg.id_message == messageId);
            if (!message) return;

            const statusLabel = getStatusLabel(message.statut);
            const statusColor = getStatusColor(message.statut);
            const isUnread = message.statut === 'non_lu' && currentFilter === 'received';

            const modal = document.createElement('div');
            modal.id = 'messageDetailModal';
            modal.className = 'modal';
            modal.style.display = 'flex';
            
            // Affichage conditionnel de l'expéditeur/destinataire pour la modale
            let detailHeader = '';

            if (currentFilter === 'received') {
                detailHeader = `
                    <strong>De:</strong> ${message.etudiant_prenom ? escapeHtml(message.etudiant_prenom + ' ' + message.etudiant_nom) : 'Étudiant inconnu'}
                    ${message.etudiant_matricule ? `(${escapeHtml(message.etudiant_matricule)})` : ''}
                    <br>
                    ${message.etudiant_email ? `<small style="color: var(--gray);">${escapeHtml(message.etudiant_email)}</small>` : ''}
                `;
            } else if (currentFilter === 'sent') {
                detailHeader = `
                    <strong>À:</strong> ${message.etudiant_prenom ? escapeHtml(message.etudiant_prenom + ' ' + message.etudiant_nom) : 'Étudiant inconnu'}
                    ${message.etudiant_matricule ? `(${escapeHtml(message.etudiant_matricule)})` : ''}
                    <br>
                    ${message.etudiant_email ? `<small style="color: var(--gray);">${escapeHtml(message.etudiant_email)}</small>` : ''}
                `;
            }

            modal.innerHTML = `
                <div class="modal-content">
                    <div style="position: relative;">
                        <div class="modal-header">
                            <h2><i class="fas fa-envelope-open"></i> ${escapeHtml(message.sujet)}</h2>
                        </div>
                        <button onclick="closeMessageModal('messageDetailModal')" class="btn btn-close" style="position: absolute; top: 20px; right: 20px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="message-detail-header">
                            <div>${detailHeader}</div>
                            <div>
                                <strong>Date:</strong> ${formatDateTime(message.date_envoi)}
                            </div>
                        </div>
                        
                        <div style="margin: 15px 0;">
                            <strong>Statut:</strong> 
                            <span class="badge" style="background-color: ${statusColor};">${statusLabel}</span>
                            ${message.date_traitement && currentFilter === 'received' ? `<div style="margin-top: 5px; color: var(--gray); font-size: 0.9rem;">Traité le: ${formatDateTime(message.date_traitement)}</div>` : ''}
                        </div>
                        
                        <div class="message-content">
                            ${escapeHtml(message.contenu)}
                        </div>
                        
                        <div class="modal-actions">
                            ${(currentFilter === 'received' && isUnread) ? `
                                <button onclick="markAsRead(${message.id_message}, true)" class="btn btn-success">
                                    <i class="fas fa-check"></i> Marquer comme traité
                                </button>
                            ` : '<div></div>'}
                            <div style="display: flex; gap: 10px;">
                                ${currentFilter === 'received' ? `
                                    <button onclick="closeMessageModal('messageDetailModal'); openNewMessageModal(${message.id_etudiant_expediteur})" class="btn btn-submit">
                                        <i class="fas fa-reply"></i> Répondre
                                    </button>
                                ` : ''}
                                <button onclick="closeMessageModal('messageDetailModal')" class="btn btn-close">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Tenter de marquer comme lu/en cours si c'est un message reçu non lu (pour ne pas fausser les stats si l'utilisateur ne clique pas sur "traité")
            if (isUnread) {
                 // Optionnel: Mettre à jour le statut en 'en_cours' ou 'traite' immédiatement.
                 // Pour la simplicité, on laisse le statut 'non_lu' jusqu'à ce que l'admin clique sur "traité".
                 // Si vous voulez le marquer "lu" sans le traiter, il faudrait un nouvel endpoint.
                 // Je garde le comportement original qui est de le marquer 'traité' uniquement sur action explicite.
            }

            // Fermer avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('messageDetailModal')) {
                    closeMessageModal('messageDetailModal');
                }
            }, { once: true });
        }

        function closeMessageModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.remove();
            }
        }

        function markAsRead(messageId, reload = false) {
            fetch(`${MESSAGE_API_URL}/mark_read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_message=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (reload) {
                        closeMessageModal('messageDetailModal');
                        loadMessages(currentFilter, document.getElementById(`filter${currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1)}`));
                    } else {
                        // Mettre à jour localement l'état et redessiner
                        const message = messages.find(msg => msg.id_message == messageId);
                        if (message) {
                            message.statut = 'traite';
                            message.date_traitement = new Date().toISOString();
                        }
                        displayMessages(messages);
                        loadStats();
                    }
                    alert('✅ Message marqué comme traité !');
                } else {
                    alert('❌ ' + (data.error || 'Erreur lors du traitement du message.'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur: ' + error.message);
            });
        }

        function loadStats() {
            fetch(`${MESSAGE_API_URL}/get_stats.php`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const stats = data.data;
                        document.getElementById('statsReceived').textContent = stats.total_received || 0;
                        document.getElementById('statsSent').textContent = stats.total_sent || 0; // Utilisation de la nouvelle stat 'total_sent'
                        document.getElementById('statsUnread').textContent = stats.unread || 0;
                    }
                })
                .catch(error => console.error('Erreur stats:', error));
        }

        /* ======================================== */
        /* NOUVEAU MESSAGE LOGIC */
        /* ======================================== */

        function fetchStudentsForSelect(preselectedStudentId = null) {
            const select = document.getElementById('recipientStudent');
            select.innerHTML = '<option value="">-- Chargement en cours... --</option>';
            select.disabled = true;

            // Simuler un appel API pour récupérer tous les étudiants
            // REMARQUE: Vous devez créer un endpoint API comme api/users/get_students.php
            fetch(`${USER_API_URL}/get_students.php`) 
                .then(response => {
                    if (!response.ok) throw new Error('Erreur de chargement des étudiants');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.data) {
                        select.innerHTML = '<option value="">Sélectionner un étudiant...</option>';
                        data.data.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id_etudiant;
                            option.textContent = `${escapeHtml(student.prenom)} ${escapeHtml(student.nom)} (${escapeHtml(student.matricule)})`;
                            if (preselectedStudentId == student.id_etudiant) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                        select.disabled = false;
                    } else {
                        select.innerHTML = '<option value="">Erreur: Aucun étudiant trouvé ou erreur API</option>';
                    }
                })
                .catch(error => {
                    console.error('Erreur étudiants:', error);
                    select.innerHTML = '<option value="">Erreur de chargement (API non trouvée)</option>';
                });
        }

        function openNewMessageModal(replyToStudentId = null) {
            const modal = document.getElementById('newMessageModal');
            const form = document.getElementById('sendMessageForm');
            
            // Réinitialiser le formulaire
            form.reset();
            document.getElementById('sendMessageBtn').disabled = false;
            document.getElementById('sendMessageBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
            
            // Charger les étudiants et présélectionner si c'est une réponse
            fetchStudentsForSelect(replyToStudentId);

            modal.style.display = 'flex';

            // Fermer avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('newMessageModal').style.display === 'flex') {
                    closeMessageModal('newMessageModal');
                }
            }, { once: true });
        }

        function handleSendMessage(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const sendBtn = document.getElementById('sendMessageBtn');
            
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

            fetch(`${MESSAGE_API_URL}/send_message.php`, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Message envoyé avec succès à l\'étudiant !');
                    closeMessageModal('newMessageModal');
                    // Recharger la boîte d'envoi et les stats
                    loadMessages('sent', document.getElementById('filterSent'));
                } else {
                    alert('❌ Erreur lors de l\'envoi: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur réseau lors de l\'envoi: ' + error.message);
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
            });
        }
        
        // Laisser la suppression à l'admin (non implémenté côté serveur ici, juste une simulation)
        function deleteMessage(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.')) {
                // Fonctionnalité à implémenter côté API/DB
                alert('Fonctionnalité de suppression à implémenter pour le message #' + id);
            }
        }


        // ========================================
        // UTILS
        // ========================================

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
                'non_lu': '#ffc107',    // Jaune (Warning)
                'en_cours': '#17a2b8',  // Bleu (Info)
                'traite': '#28a745'     // Vert (Success)
            };
            return colors[statut] || '#6c757d'; // Gris par défaut
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            try {
                // Utiliser la date actuelle pour des comparaisons comme "Aujourd'hui" si nécessaire.
                const date = new Date(dateString);
                const now = new Date();
                
                const isToday = date.toDateString() === now.toDateString();
                const isYesterday = new Date(date.getTime() + 86400000).toDateString() === now.toDateString();

                if (isToday) {
                    return 'Aujourd\'hui à ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                } else if (isYesterday) {
                    return 'Hier à ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                }
                
                return date.toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }) + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            } catch (e) {
                return dateString.split(' ')[0] || 'Date invalide';
            }
        }

        function formatDateTime(dateString) {
            if (!dateString) return '';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
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