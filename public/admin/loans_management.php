<?php
// Fichier: admin/loans_management.php
require_once __DIR__ . '/../../includes/auth.php'; 
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prêts - Lireo</title>
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
            --secondary: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --sidebar-width: 260px; /* Largeur du menu ouvert */
            --sidebar-width-collapsed: 78px; /* Largeur du menu fermé */
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


        body {
            min-height: 100vh;
            background-color: #f5f7fa;
            position: relative;
        }

        /* --- LOGIQUE DU CONTENU PRINCIPAL (Lié au JS) --- */
        .main-content {
            flex: 1;
            padding: 0px 0px;
            margin-bottom: 30px;
            width:50px; /* Ajustement pour sidebar fermée par défaut ou ouverte */
            margin-left: 0px; /* Si la sidebar est fixed */
            margin-right : 10px;
        }

        /* Quand le menu est fermé (la classe .expand est ajoutée par le JS) */
        

        /* Header interne */
        header {
            background-color: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 2.2rem;
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Tabs */
        .tabs-container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .tabs-nav {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--gray-light);
            margin-bottom: 25px;
        }

        .tab-button {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        .tab-button:hover, .tab-button.active {
            color: var(--primary);
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--primary);
            border-radius: 2px;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        /* Table Styles */
        .table-container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: var(--gray-light);
        }

        th {
            padding: 15px;
            text-align: left;
            color: var(--dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            color: var(--dark);
            vertical-align: middle;
        }

        tr:hover {
            background-color: rgba(42, 82, 152, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-on-time { background-color: rgba(40, 167, 69, 0.1); color: var(--success); }
        .status-late { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .status-pending { background-color: rgba(255, 193, 7, 0.1); color: #856404; }
        .status-expired { background-color: rgba(108, 117, 125, 0.1); color: var(--gray); }

        /* Buttons */
        button[class$="-btn"] {
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            margin-right: 5px;
        }
        .return-btn { background-color: var(--primary); }
        .confirm-btn, .approve-btn { background-color: var(--success); }
        .cancel-btn, .refuse-btn { background-color: var(--danger); }
        
        button[class$="-btn"]:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .alert.warning {
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid var(--warning);
            color: #856404;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }


        /* Responsive Mobile */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .main-content { left: 0; width: 100%; padding: 10px; }
            .main-content.expand { left: 0; width: 100%; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    
    <?php require_once __DIR__ . '/../../includes/sidebaradmin.php'; ?> 
    
    <div class="main-content">
        
        
        <header class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
                
                <div>
                    <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Gestion des Prets</h1>
                    <p style="color: #666; margin: 5px 0 0 0;">Ajoutez et gérez les prêts</p>
                </div>
            </div>

            <div class="datetime" style="text-align: right;">
                <div class="time" id="current-time" style="font-size: 1.2rem; font-weight: bold; color: #2a5298;"></div>
                <div class="date" id="current-date" style="color: #666;"></div>
            </div>
        </header>
        

        <div id="cleanupAlert" class="alert warning"></div>

        <div class="tabs-container">
            <nav class="tabs-nav">
                <button onclick="showTab('retraits')" class="tab-button active"><i class='fas fa-box-open'></i> Retraits en Attente</button>
                <button onclick="showTab('loans')" class="tab-button"><i class='fas fa-book'></i>Prêts en Cours</button>
                <button onclick="showTab('extensions')" class="tab-button"><i class='fas fa-sync-alt'></i>Prolongations</button>
            </nav>

            <div id="content-retraits" class="tab-content active">
                <div class="tab-header">
                    <h2>Retraits à Confirmer</h2>
                    <p>Confirmez les retraits physiques des livres dans les 24h</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Livre</th><th>Étudiant</th><th>Demandé le</th><th>Limite</th><th>Statut</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="retraitsTableBody">
                            <tr class="loading-row"><td colspan="6">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="content-loans" class="tab-content">
                <div class="tab-header">
                    <h2>Prêts en Cours</h2>
                    <p>Gérez les prêts actuellement en cours</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Livre</th><th>Étudiant</th><th>Prêt le</th><th>À retourner</th><th>Statut</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="loansTableBody">
                            <tr class="loading-row"><td colspan="6">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="content-extensions" class="tab-content">
                <div class="tab-header">
                    <h2>Prolongations</h2>
                    <p>Gérez les demandes de prolongation</p>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Livre</th><th>Étudiant</th><th>Retour actuel</th><th>Retour demandé</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="extensionsTableBody">
                            <tr class="loading-row"><td colspan="5">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // --- 1. GESTION DE LA SIDEBAR (Ton script) ---
            let btn = document.querySelector("#header_toggle_btn"); // Assure-toi que ce ID existe dans sidebaradmin.php
            let sidebar = document.querySelector(".sidebar");       // Assure-toi que cette classe existe dans sidebaradmin.php
            let mainContent = document.querySelector(".main-content");

            if (btn && sidebar && mainContent) {
                btn.onclick = function() {
                    sidebar.classList.toggle("close");
                    mainContent.classList.toggle("expand"); // C'est ici que la magie opère pour le CSS
                };
            }
            updateTime();
            setInterval(updateTime, 1000);

            // 3. CHARGEMENT DES DONNÉES (Correction appliquée ici)
            console.log('Page chargée, chargement des étudiants...');
            loadStudents(); 
            // --- 2. GESTION DU TEMPS (Petit helper) ---
        
        });
        function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                const div = document.getElementById('time-display');
                if(div) div.textContent = timeString;
            }
            updateTime();
            setInterval(updateTime, 1000);

            // --- 3. CHARGEMENT DES DONNÉES ---
            console.log('Page chargée, chargement des données...');
            loadAllData(); // Appel de la fonction principale de cette page

        // --- FONCTIONS MÉTIERS EXISTANTES ---

        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
            document.getElementById('content-' + tabName).classList.add('active');
            // Trouver le bouton cliqué (hack simple ou passer 'this' dans onclick)
            event.target.classList.add('active');
        }

        function loadAllData() {
            loadRetraits();
            loadLoans();
            loadExtensions();
        }

        // --- API CALLS (Tes fonctions existantes inchangées) ---

        function loadLoans() {
            const tableBody = document.getElementById('loansTableBody');
            fetch('../../api/admin/loan_management.php?action=read_loans')
                .then(r => r.json())
                .then(data => {
                    if (data.cleanup_count > 0) showCleanupAlert(data.cleanup_count + ' emprunts nettoyés');
                    if (!data.data || data.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">Aucun prêt en cours.</td></tr>';
                        return;
                    }
                    tableBody.innerHTML = '';
                    data.data.forEach(loan => {
                        const isLate = new Date(loan.date_retour_prevue) < new Date();
                        const statusClass = isLate ? 'status-late' : 'status-on-time';
                        const statusText = isLate ? 'En retard' : 'À l\'heure';
                        
                        tableBody.innerHTML += `
                            <tr>
                                <td>
                                    <div style="font-weight:bold">${escapeHtml(loan.titre)}</div>
                                    <div style="font-size:0.85em;color:#666">${escapeHtml(loan.isbn)}</div>
                                </td>
                                <td>${escapeHtml(loan.prenom)} ${escapeHtml(loan.nom)}</td>
                                <td>${new Date(loan.date_emprunt).toLocaleDateString('fr-FR')}</td>
                                <td>${new Date(loan.date_retour_prevue).toLocaleDateString('fr-FR')}</td>
                                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                <td>
                                    <button onclick="registerReturn(${loan.id_emprunt})" class="return-btn">📚 Retour</button>
                                </td>
                            </tr>`;
                    });
                })
                .catch(e => console.error(e));
        }

        function loadRetraits() {
            const tableBody = document.getElementById('retraitsTableBody');
            fetch('../../api/admin/loan_management.php?action=read_retraits_attente')
                .then(r => r.json())
                .then(data => {
                    if (!data.data || data.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">Aucun retrait en attente.</td></tr>';
                        return;
                    }
                    tableBody.innerHTML = '';
                    data.data.forEach(retrait => {
                        const isExpired = new Date(retrait.date_limite_retrait) < new Date();
                        
                        let actions = !isExpired 
                            ? `<button onclick="confirmRetrait(${retrait.id_emprunt})" class="confirm-btn">✅ Confirmer</button>`
                            : `<button onclick="cancelRetrait(${retrait.id_emprunt})" class="cancel-btn">Annuler</button>`;

                        tableBody.innerHTML += `
                            <tr>
                                <td>
                                    <div style="font-weight:bold">${escapeHtml(retrait.titre)}</div>
                                </td>
                                <td>${escapeHtml(retrait.prenom)} ${escapeHtml(retrait.nom)}</td>
                                <td>${new Date(retrait.date_emprunt).toLocaleDateString('fr-FR')}</td>
                                <td style="color:${isExpired ? 'red':'inherit'}">${new Date(retrait.date_limite_retrait).toLocaleString('fr-FR')}</td>
                                <td><span class="status-badge ${isExpired ? 'status-expired':'status-pending'}">${isExpired ? 'Expiré':'En attente'}</span></td>
                                <td>${actions}</td>
                            </tr>`;
                    });
                });
        }

        function loadExtensions() {
            const tableBody = document.getElementById('extensionsTableBody');
            fetch('../../api/admin/loan_management.php?action=read_extensions')
                .then(r => r.json())
                .then(data => {
                    if (!data.data || data.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">Aucune prolongation.</td></tr>';
                        return;
                    }
                    tableBody.innerHTML = '';
                    data.data.forEach(ext => {
                        tableBody.innerHTML += `
                            <tr>
                                <td>${escapeHtml(ext.titre)}</td>
                                <td>${escapeHtml(ext.prenom)} ${escapeHtml(ext.nom)}</td>
                                <td>${new Date(ext.date_retour_prevue).toLocaleDateString('fr-FR')}</td>
                                <td style="color:var(--success);font-weight:bold">${new Date(ext.nouvelle_date_prevue).toLocaleDateString('fr-FR')}</td>
                                <td>
                                    <button onclick="handleExtension(${ext.id_prolongation}, 'approve')" class="approve-btn">✅</button>
                                    <button onclick="handleExtension(${ext.id_prolongation}, 'refuse')" class="refuse-btn">❌</button>
                                </td>
                            </tr>`;
                    });
                });
        }

        // --- ACTION HANDLERS ---
        
        function confirmRetrait(id) {
            if(confirm('Confirmer le retrait ?')) postAction('confirm_retrait', {id_emprunt: id});
        }
        function cancelRetrait(id) {
            if(confirm('Annuler ?')) postAction('register_return', {id_emprunt: id});
        }
        function registerReturn(id) {
            if(confirm('Enregistrer retour ?')) postAction('register_return', {id_emprunt: id});
        }
        function handleExtension(id, action) {
            const apiAction = action === 'approve' ? 'approve_extension' : 'refuse_extension';
            if(confirm(action + ' ?')) postAction(apiAction, {id_prolongation: id});
        }

        function postAction(actionName, params) {
            let body = `action=${actionName}`;
            for(let key in params) body += `&${key}=${params[key]}`;

            fetch('../../api/admin/loan_management.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                loadAllData();
            })
            .catch(e => alert('Erreur: ' + e));
        }

        function showCleanupAlert(msg) {
            const el = document.getElementById('cleanupAlert');
            el.textContent = '🔄 ' + msg;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 5000);
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