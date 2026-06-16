<?php
// Fichier: public/admin/sanctions.php - STYLE ÉTUDIANT
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

$admin_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Sanctions - Lireo</title>
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
            /* Transition fluide pour le body si besoin */
            transition: all 0.5s ease; 
        }

        /* Header */
        header {
            background-color: var(--white);
            padding: 20px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Main Content - AJOUT POUR LA SIDEBAR */
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
        
        /* Quand la sidebar est ouverte (si géré par CSS global, ajuster ici) */
        .sidebar.active ~ .main-content { 
           /* Ajuste selon ton CSS sidebar global */
        }
        
        /* Expand class gérée par ton script JS */
        .main-content.expand {
            left: 250px; /* Largeur sidebar ouverte */
            width: calc(100% - 250px);
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

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (min-width: 768px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Card Styles */
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

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .action-btn {
            border: none;
            padding: 12px 15px;
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

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .add-sanction-btn { background-color: var(--danger); color: var(--white); }
        .add-sanction-btn:hover { background-color: #c82333; }
        .check-delays-btn { background-color: var(--warning); color: var(--white); }
        .check-delays-btn:hover { background-color: #e0a800; }
        .refresh-btn { background-color: var(--info); color: var(--white); }
        .refresh-btn:hover { background-color: #138496; }

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

        .stat-item:last-child { border-bottom: none; }
        .stat-label { color: var(--gray); font-weight: 500; }
        
        .stat-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
        }

        .stat-active { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .stat-lifted { background-color: rgba(40, 167, 69, 0.1); color: var(--success); }
        .stat-warnings { background-color: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .stat-suspensions { background-color: rgba(255, 102, 0, 0.1); color: #ff6600; }

        /* Info List */
        .info-list { list-style: none; }
        .info-list li {
            padding: 8px 0;
            color: var(--gray);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .info-list li::before { content: "•"; color: var(--primary); font-weight: bold; }

        /* Sanctions Table */
        .sanctions-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .sanctions-header {
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }

        .sanctions-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sanctions-header p { color: var(--gray); font-size: 1rem; }
        .sanctions-body { min-height: 200px; position: relative; }

        /* Loading Spinner */
        .loading-spinner { text-align: center; padding: 60px 20px; color: var(--gray); }
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
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray); }
        .empty-state i { font-size: 4rem; color: var(--gray-light); margin-bottom: 20px; }
        .empty-state h3 { font-size: 1.5rem; margin-bottom: 10px; color: var(--gray); }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: var(--gray-light); }
        th {
            padding: 15px;
            text-align: left;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            color: var(--dark);
        }
        tr:hover { background-color: rgba(42, 82, 152, 0.05); }

        .student-info { font-weight: 600; margin-bottom: 5px; }
        .student-email { font-size: 0.9rem; color: var(--gray); }

        .sanction-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .type-warning { background-color: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .type-suspension { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .type-ban { background-color: rgba(108, 117, 125, 0.1); color: var(--gray); }

        .sanction-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-active { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .status-lifted { background-color: rgba(40, 167, 69, 0.1); color: var(--success); }
        .status-expired { background-color: rgba(108, 117, 125, 0.1); color: var(--gray); }

        .sanction-actions { display: flex; gap: 10px; }
        
        .lift-btn, .extend-btn, .delete-btn {
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--white);
        }
        .lift-btn { background-color: var(--success); }
        .lift-btn:hover { background-color: #218838; }
        .extend-btn { background-color: var(--warning); }
        .extend-btn:hover { background-color: #e0a800; }
        .delete-btn { background-color: var(--danger); }
        .delete-btn:hover { background-color: #c82333; }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content { left: 0; width: 100%; padding: 15px; }
            header { flex-direction: column; gap: 15px; align-items: flex-start; }
            .cards-grid { grid-template-columns: 1fr; }
            .sanction-actions { flex-direction: column; gap: 8px; }
            table { display: block; overflow-x: auto; }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .btn-submit { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/sidebaradmin.php'; ?> 

    <main class="main-content">
        
        <header class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
                
                <div>
                    <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Gestion des Sanctions</h1>
                    <p style="color: #666; margin: 5px 0 0 0;">Gérez les sanctions et avertissements des étudiants</p>
                </div>
            </div>

            <div class="datetime" style="text-align: right;">
                <div class="time" id="current-time" style="font-size: 1.2rem; font-weight: bold; color: #2a5298;"></div>
                <div class="date" id="current-date" style="color: #666;"></div>
            </div>
        </header>
        

        <div class="cards-grid">
            <div class="card">
                <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
                <div class="quick-actions">
                    <button onclick="openSanctionModal()" class="action-btn add-sanction-btn">
                        <i class="fas fa-plus"></i> Imposer une sanction
                    </button>
                    <button onclick="checkAutoSanctions()" class="action-btn check-delays-btn">
                        <i class="fas fa-search"></i> Vérifier retards automatiques
                    </button>
                    <button onclick="loadSanctions()" class="action-btn refresh-btn">
                        <i class="fas fa-sync"></i> Actualiser la liste
                    </button>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Sanctions actives</span>
                        <span id="statsActive" class="stat-badge stat-active">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Sanctions levées</span>
                        <span id="statsLifted" class="stat-badge stat-lifted">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Avertissements</span>
                        <span id="statsWarnings" class="stat-badge stat-warnings">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Suspensions</span>
                        <span id="statsSuspensions" class="stat-badge stat-suspensions">0</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Informations</h3>
                <ul class="info-list">
                    <li>Les sanctions automatiques sont appliquées pour les retards sévères</li>
                    <li>Les suspensions bloquent l'accès à la bibliothèque</li>
                    <li>Les avertissements n'affectent pas l'accès</li>
                    <li>Vérifiez régulièrement les retards automatiques</li>
                </ul>
            </div>
        </div>

        <div class="sanctions-container">
            <div class="sanctions-header">
                <h2><i class="fas fa-list"></i> Liste des Sanctions</h2>
                <p>Gérez les sanctions des étudiants</p>
            </div>
            
            <div id="sanctionsTableBody" class="sanctions-body">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Chargement des sanctions...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Chemin vers votre API
    const API_URL = '../../api/admin/manage_sanctions.php';

    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. GESTION DE LA SIDEBAR (TON SCRIPT AJOUTÉ ICI) ---
        let btn = document.querySelector("#header_toggle_btn");
        let sidebar = document.querySelector(".sidebar"); // Supposant que sidebaradmin.php contient une classe .sidebar
        let mainContent = document.querySelector(".main-content");

        if (btn && sidebar && mainContent) {
            btn.onclick = function() {
                sidebar.classList.toggle("close");
                mainContent.classList.toggle("expand");
            };
        }

        // --- 2. GESTION DU TEMPS (FONCTION AJOUTÉE) ---
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const dateString = now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if (timeElement) timeElement.textContent = timeString;
            if (dateElement) dateElement.textContent = dateString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        // --- 3. CHARGEMENT DES DONNÉES ---
        console.log('Page chargée, chargement des sanctions...');
        loadSanctions();
    });

    // ---------------- FONCTIONS EXISTANTES POUR LES SANCTIONS ----------------

    // 1. Charger les données depuis la BDD
    function loadSanctions() {
        const tableBody = document.getElementById('sanctionsTableBody');
        tableBody.innerHTML = '<div class="loading-spinner"><div class="spinner"></div><p>Chargement...</p></div>';

        fetch(`${API_URL}?action=get_sanctions`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderSanctions(data.data);
                    updateStats(data.data);
                } else {
                    tableBody.innerHTML = `<p class="error">Erreur: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                tableBody.innerHTML = '<p class="error">Erreur de connexion au serveur.</p>';
            });
    }

    // 2. Afficher le tableau HTML
    function renderSanctions(sanctions) {
        const tableBody = document.getElementById('sanctionsTableBody');
        
        if (sanctions.length === 0) {
            tableBody.innerHTML = '<div class="empty-state"><h3>Aucune sanction trouvée</h3></div>';
            return;
        }

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Type</th>
                        <th>Raison</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        sanctions.forEach(s => {
            const nomComplet = `${s.etudiant_nom} ${s.etudiant_prenom}`;
            const typeClass = s.type_sanction === 'suspension' ? 'type-suspension' : 'type-warning';
            const statut = s.statut || 'active'; 
            const statusClass = statut === 'levee' ? 'status-lifted' : 'status-active';
            const dateFin = s.date_fin_bannissement ? new Date(s.date_fin_bannissement).toLocaleDateString('fr-FR') : '-';

            html += `
                <tr>
                    <td>
                        <div class="student-info">${nomComplet}</div>
                        <div class="student-email">${s.etudiant_email || ''}</div>
                    </td>
                    <td>
                        <span class="sanction-type ${typeClass}">${s.type_sanction}</span>
                    </td>
                    <td>${s.details_faute || 'Aucun détail'}</td>
                    <td>${new Date(s.date_debut_sanction).toLocaleDateString('fr-FR')}</td>
                    <td>${dateFin}</td>
                    <td>
                        <span class="sanction-status ${statusClass}">${statut}</span>
                    </td>
                    <td>
                        <div class="sanction-actions">
                            ${statut === 'active' ? `
                                <button onclick="liftSanction(${s.id_sanction})" class="lift-btn">
                                    <i class="fas fa-check"></i> Lever
                                </button>
                                <button onclick="extendSanction(${s.id_sanction})" class="extend-btn">
                                    <i class="fas fa-calendar-plus"></i> Étendre
                                </button>
                            ` : ''}
                            <button onclick="deleteSanction(${s.id_sanction})" class="delete-btn">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        tableBody.innerHTML = html;
    }

    // 3. Action : Lever une sanction
    function liftSanction(id) {
        if (!confirm('Voulez-vous vraiment lever cette sanction ?')) return;

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'lever_sanction', id_sanction: id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') loadSanctions();
            else alert(data.message);
        });
    }

    // 4. Action : Supprimer
    function deleteSanction(id) {
        if (!confirm('Supprimer définitivement cette sanction ?')) return;

        fetch(API_URL, {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_sanction', id_sanction: id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') loadSanctions();
            else alert(data.message);
        });
    }

    // 5. Action : Étendre
    function extendSanction(id) {
        const newDate = prompt("Nouvelle date de fin (AAAA-MM-JJ) :");
        if (!newDate) return;

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'extend_sanction', id_sanction: id, new_date: newDate })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') loadSanctions();
            else alert(data.message);
        });
    }

    // Calcul rapide des stats côté client
    function updateStats(data) {
        const active = data.filter(s => s.statut === 'active' || !s.statut).length;
        const levee = data.filter(s => s.statut === 'levee').length;
        document.getElementById('statsActive').textContent = active;
        document.getElementById('statsLifted').textContent = levee;
    }

    // --- MODAL FUNCTIONS ---
    function openSanctionModal() {
        document.getElementById('sanctionModal').style.display = 'flex';
    }

    function closeSanctionModal() {
        document.getElementById('sanctionModal').style.display = 'none';
    }

    function submitSanction(event) {
        event.preventDefault(); 

        const data = {
            action: 'imposer_sanction',
            id_etudiant: document.getElementById('newIdEtudiant').value,
            type_sanction: document.getElementById('newTypeSanction').value,
            date_fin: document.getElementById('newDateFin').value,
            details_faute: document.getElementById('newDetails').value
        };

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert('Sanction ajoutée !');
                closeSanctionModal();
                loadSanctions(); 
                document.getElementById('formSanction').reset(); 
            } else {
                alert('Erreur : ' + result.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de communication avec le serveur.');
        });
    }
    </script>

    <div id="sanctionModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Imposer une nouvelle sanction</h3>
                <button onclick="closeSanctionModal()" class="close-btn">&times;</button>
            </div>
            <form id="formSanction" onsubmit="submitSanction(event)">
                <div class="form-group">
                    <label>ID Étudiant :</label>
                    <input type="number" id="newIdEtudiant" required placeholder="Ex: 12">
                </div>
                
                <div class="form-group">
                    <label>Type de sanction :</label>
                    <select id="newTypeSanction" required>
                        <option value="Avertissement">Avertissement</option>
                        <option value="Suspension">Suspension</option>
                        <option value="Exclusion">Exclusion</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date de fin (ou fin de bannissement) :</label>
                    <input type="date" id="newDateFin" required>
                </div>

                <div class="form-group">
                    <label>Détails de la faute :</label>
                    <textarea id="newDetails" rows="3" required placeholder="Ex: Retard de 2 mois..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeSanctionModal()" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-submit">Valider la sanction</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>