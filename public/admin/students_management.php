<?php
// Fichier: admin/students_management.php
require_once __DIR__ . '/../../includes/auth.php'; 
require_role('admin');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Lireo</title>
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
            --sidebar-bg: #1a1f35;
            --sidebar-text: #b0b7c3;
            --white: #ffffff;
            --bg: #f4f6fb;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --dark: #343a40;
            --card-bg: #ffffff;
            --modal-overlay: rgba(0, 0, 0, 0.5);
        }

        body {
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
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
        .main-content {
            flex: 1;
            padding: 0px 0px;
            margin-bottom: 30px;
            width:50px; /* Ajustement pour sidebar fermée par défaut ou ouverte */
            margin-left: 0px; /* Si la sidebar est fixed */
            margin-right : 10px;
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

        .add-btn {
            background-color: var(--success);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-btn:hover {
            background-color: #0b284eff;
            transform: translateY(-2px);
        }

        /* Table Container */
        .table-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        thead {
            background-color: var(--gray-light);
        }

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

        tr:hover {
            background-color: rgba(42, 82, 152, 0.05);
        }

        .student-name {
            font-weight: 600;
            color: var(--dark);
        }

        .status-badge {
            padding: 5px 12p 
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-active { background-color: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .status-blocked { background-color: rgba(244, 67, 54, 0.1); color: #F44336; }

        .account-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .account-activated { background-color: rgba(103, 58, 183, 0.1); color: #673AB7; }
        .account-not-activated { background-color: rgba(158, 158, 158, 0.1); color: #9E9E9E; }

        .actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn:hover { background-color: var(--primary-dark); }

        .delete-btn {
            background-color: var(--danger);
            color: var(--white);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-btn:hover { background-color: #c82333; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--modal-overlay);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000; padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        .modal-header { margin-bottom: 25px; }
        .modal-header h3 { font-size: 1.8rem; color: var(--primary); font-weight: 700; margin-bottom: 5px; }
        .modal-header p { color: var(--gray); font-size: 0.95rem; }

        .form-group { margin-bottom: 20px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 20px; }
        .form-row .form-group { flex: 1; margin-bottom: 0; }

        label { display: block; margin-bottom: 8px; color: var(--dark); font-weight: 500; font-size: 0.95rem; }
        input, select {
            width: 100%; padding: 12px 15px;
            border: 1px solid var(--gray-light); border-radius: 8px;
            font-size: 1rem; color: var(--dark); background-color: var(--white);
            transition: border-color 0.3s ease;
        }
        input:focus, select:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .form-actions {
            display: flex; justify-content: flex-end; gap: 15px;
            margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-light);
        }

        .cancel-btn {
            background-color: var(--gray); color: var(--white); border: none;
            padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .cancel-btn:hover { background-color: #5a6268; }

        .submit-btn {
            background-color: var(--primary); color: var(--white); border: none;
            padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .submit-btn:hover { background-color: var(--primary-dark); }

        .hidden { display: none !important; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsive Mobile */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .form-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column; }
            .submit-btn, .cancel-btn { width: 100%; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/sidebaradmin.php'; ?> 

    <main class="main-content">
        <header class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
                
                <div>
                    <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Gestion des étudiants</h1>
                    <p style="color: #666; margin: 5px 0 0 0;">Ajoutez et gérez les étudiants</p>
                </div>
            </div>

            <div class="datetime" style="text-align: right;">
                <div class="time" id="current-time" style="font-size: 1.2rem; font-weight: bold; color: #2a5298;"></div>
                <div class="date" id="current-date" style="color: #666;"></div>
            </div>
        </header>

        <div class="actions-bar">
            <button onclick="openModal('create')" class="add-btn">
                <i class="fas fa-plus"></i> Ajouter un étudiant
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Matricule</th>
                        <th>Nom & Prénom</th>
                        <th>Email académique</th>
                        <th>Compte</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr class="loading-row">
                        <td colspan="6" style="text-align: center; padding: 20px;">Chargement des étudiants...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <div id="studentModal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ajouter un Étudiant</h3>
                <p id="modalSubtitle">Remplissez les informations de l'étudiant</p>
            </div>
            
            <form id="studentForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id_etudiant" id="editIdEtudiant">

                <div class="form-row">
                    <div class="form-group">
                        <label for="matricule">Matricule</label>
                        <input type="text" name="matricule" id="matricule" placeholder="Ex: 20230001">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" name="prenom" id="prenom" required>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" name="nom" id="nom" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email_academique">Email académique *</label>
                    <input type="email" name="email_academique" id="email_academique" required placeholder="etudiant@universite.edu">
                </div>

                <div class="form-group">
                    <label for="promotion">Promotion</label>
                    <input type="text" name="promotion" id="promotion" placeholder="Ex: Licence 3 Informatique">
                </div>
                
                <div id="statutField" class="form-group hidden">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut">
                        <option value="actif">Actif</option>
                        <option value="bloque">Bloqué</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="cancel-btn">Annuler</button>
                    <button type="submit" class="submit-btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. GESTION DE LA SIDEBAR
            let btn = document.querySelector("#header_toggle_btn");
            let sidebar = document.querySelector(".sidebar");
            let mainContent = document.querySelector(".main-content");

            if (btn && sidebar && mainContent) {
                btn.onclick = function() {
                    sidebar.classList.toggle("close");
                    if(mainContent) mainContent.classList.toggle("expand");
                };
            }
            
            // 2. GESTION DU TEMPS
            updateTime();
            setInterval(updateTime, 1000);

            // 3. CHARGEMENT DES DONNÉES (Correction appliquée ici)
            console.log('Page chargée, chargement des étudiants...');
            loadStudents(); 
        });

        // --- Fonctions Utilitaires ---

       function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const dateString = now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if (timeElement) timeElement.textContent = timeString;
            if (dateElement) dateElement.textContent = dateString;
        }
        

        // --- Fonctions CRUD Étudiants ---

        function loadStudents() {
            const tableBody = document.getElementById('studentsTableBody');
            
            fetch('../../api/admin/student_management.php?action=read_all')
                .then(response => {
                    if (!response.ok) throw new Error('Erreur HTTP: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    if (data.status !== 'success') {
                        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Erreur: ' + data.message + '</td></tr>';
                        return;
                    }
                    
                    if (!data.data || data.data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; font-style:italic;">Aucun étudiant enregistré.</td></tr>';
                        return;
                    }
                    
                    tableBody.innerHTML = ''; // Vider le tableau
                    
                    data.data.forEach(student => {
                        const row = tableBody.insertRow();
                        
                        const accountClass = student.compte_active ? 'account-activated' : 'account-not-activated';
                        const accountText = student.compte_active ? 'Activé' : 'Non activé';
                        const statusClass = student.statut === 'actif' ? 'status-active' : 'status-blocked';
                        const statusText = student.statut === 'actif' ? 'Actif' : 'Bloqué';
                        
                        row.innerHTML = `
                            <td>${student.id_etudiant}</td>
                            <td>${escapeHtml(student.matricule || '-')}</td>
                            <td>
                                <div class="student-name">${escapeHtml(student.nom)} ${escapeHtml(student.prenom)}</div>
                                <div style="font-size: 0.8rem; color: var(--gray);">
                                    <span class="status-badge ${statusClass}">${statusText}</span>
                                </div>
                            </td>
                            <td>${escapeHtml(student.email_academique)}</td>
                            <td><span class="account-badge ${accountClass}">${accountText}</span></td>
                            <td class="actions">
                                <button onclick="openModal('update', ${JSON.stringify(student).replace(/"/g, '&quot;')})" class="edit-btn">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button onclick="deleteStudent(${student.id_etudiant}, '${escapeHtml(student.nom)} ${escapeHtml(student.prenom)}')" class="delete-btn">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </td>
                        `;
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Erreur de connexion serveur</td></tr>';
                });
        }

        function openModal(mode, data = null) {
            const modal = document.getElementById('studentModal');
            const title = document.getElementById('modalTitle');
            const subtitle = document.getElementById('modalSubtitle');
            const statutField = document.getElementById('statutField');
            
            document.getElementById('formAction').value = mode;
            document.getElementById('studentForm').reset();
            
            if (mode === 'create') {
                title.textContent = 'Ajouter un Étudiant';
                subtitle.textContent = 'Remplissez les informations du nouvel étudiant';
                statutField.classList.add('hidden');
                document.getElementById('editIdEtudiant').value = '';
            } else if (mode === 'update' && data) {
                title.textContent = 'Modifier l\'Étudiant';
                subtitle.textContent = 'Modifiez les informations de l\'étudiant';
                statutField.classList.remove('hidden');
                
                document.getElementById('editIdEtudiant').value = data.id_etudiant;
                document.getElementById('matricule').value = data.matricule || '';
                document.getElementById('prenom').value = data.prenom;
                document.getElementById('nom').value = data.nom;
                document.getElementById('email_academique').value = data.email_academique;
                document.getElementById('promotion').value = data.promotion || '';
                document.getElementById('statut').value = data.statut || 'actif';
            }
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        document.getElementById('studentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            
            fetch('../../api/admin/student_management.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params
            })
            .then(response => response.json())
            .then(data => {
                alert(data.status === 'success' ? '✅ ' + data.message : '❌ ' + data.message);
                if (data.status === 'success') {
                    closeModal();
                    loadStudents();
                }
            })
            .catch(error => {
                alert('❌ Erreur: ' + error.message);
            });
        });

        function deleteStudent(id_etudiant, studentName) {
            if (!confirm(`⚠️ Supprimer l'étudiant "${studentName}" ?\n\nCette action est irréversible.`)) return;
            
            fetch('../../api/admin/student_management.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete&id_etudiant=' + id_etudiant
            })
            .then(response => response.json())
            .then(data => {
                alert(data.status === 'success' ? '✅ ' + data.message : '❌ ' + data.message);
                loadStudents();
            })
            .catch(error => {
                alert('❌ Erreur: ' + error.message);
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