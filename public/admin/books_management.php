<?php
// Fichier: admin/books_management.php - STYLE HARMONISÉ
require_once __DIR__ . '/../../includes/auth.php'; 
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

$admin_user = $_SESSION['user'];

$stmt = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres - Lireo</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }

        /* Styles spécifiques au contenu */
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

        /* Main Content - Les styles de base sont dans sidebaradmin.php, 
           ici on gère juste le contenu spécifique */
        
        /* Add Book Button */
        .add-book-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .add-book-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .add-book-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.3);
        }

        /* Form Container */
        .form-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .form-container.show {
            display: block;
        }

        .form-title {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 25px;
        }

        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--white);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group select[multiple] {
            height: 150px;
        }

        .form-note {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
            font-style: italic;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
        }

        .submit-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
        }

        .cancel-btn {
            background-color: var(--gray-light);
            color: var(--dark);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background-color: #dee2e6;
        }

        /* Books List */
        .books-list-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }

        .books-list-title {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 25px;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Force scroll horizontal on small screens */
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
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            color: var(--dark);
        }

        tr:hover {
            background-color: rgba(42, 82, 152, 0.05);
        }

        .book-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .book-author {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .copies-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .available-copies.available { color: var(--success); font-weight: bold; }
        .available-copies.unavailable { color: var(--danger); font-weight: bold; }

        .actions {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }

        .edit-btn { background-color: var(--primary); }
        .edit-btn:hover { background-color: var(--primary-dark); }

        .delete-btn { background-color: var(--danger); }
        .delete-btn:hover { background-color: #c82333; }

        .loading-row, .empty-row, .error-row {
            text-align: center;
            color: var(--gray);
            padding: 30px;
        }
        
        .error-row { color: var(--danger); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
                    <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Gestion du Catalogue</h1>
                    <p style="color: #666; margin: 5px 0 0 0;">Ajoutez et gérez les livres</p>
                </div>
            </div>

            <div class="datetime" style="text-align: right;">
                <div class="time" id="current-time" style="font-size: 1.2rem; font-weight: bold; color: #2a5298;"></div>
                <div class="date" id="current-date" style="color: #666;"></div>
            </div>
        </header>


        <div class="add-book-container">
            <button onclick="toggleForm()" class="add-book-btn">
                <i class="fas fa-plus"></i> Ajouter un livre
            </button>
        </div>

        <div id="bookFormContainer" class="form-container">
            <h3 id="formTitle" class="form-title">Ajouter un Livre</h3>
            <form id="bookForm" onsubmit="handleBookSubmit(event)">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="original_isbn" id="originalIsbn">

                <div class="form-grid">
                    <div class="form-group">
                        <label>ISBN *</label>
                        <input type="text" name="isbn" id="bookIsbn" required>
                    </div>
                    <div class="form-group">
                        <label>Titre *</label>
                        <input type="text" name="titre" id="bookTitre" required>
                    </div>
                    <div class="form-group">
                        <label>Auteur *</label>
                        <input type="text" name="auteur" id="bookAuteur" required>
                    </div>
                    <div class="form-group">
                        <label>Année</label>
                        <input type="number" name="annee_publication" id="bookAnnee">
                    </div>
                    <div class="form-group">
                        <label>Pages</label>
                        <input type="number" name="nbre_de_pages" id="bookPages">
                    </div>
                    <div class="form-group">
                        <label>Copies totales *</label>
                        <input type="number" name="nbre_de_copie_total" id="bookTotalCopies" required>
                    </div>
                    <div class="form-group">
                        <label>Langue</label>
                        <input type="text" name="langue" id="bookLangue" value="Français">
                    </div>
                </div>

                <div class="form-group">
                    <label>Catégories</label>
                    <select name="categories[]" id="bookCategories" multiple>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>"><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-note">Maintenez Ctrl (Cmd sur Mac) pour sélectionner plusieurs catégories</p>
                </div>

                <div class="form-group">
                    <label>Image du livre</label>
                    <input type="text" name="image_livre" id="bookImage" placeholder="nom_image.jpg">
                    <p class="form-note">Laissez vide pour utiliser l'image par défaut</p>
                </div>

                <div class="form-group">
                    <label>Résumé</label>
                    <textarea name="resume" id="bookResume" placeholder="Résumé du livre..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submitButton" class="submit-btn">
                        Créer le Livre
                    </button>
                    <button type="button" onclick="resetForm()" class="cancel-btn">
                        Annuler
                    </button>
                </div>
            </form>
        </div>

        <section class="books-list-container">
            <h3 class="books-list-title">Liste des Livres</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ISBN</th>
                            <th>Titre / Auteur</th>
                            <th>Copies</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="booksTableBody">
                        <tr class="loading-row">
                            <td colspan="4">Chargement...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        // --- 1. SCRIPT SIDEBAR (Intégré pour que le bouton fonctionne) ---
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

        // Fonction Heure/Date
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

        // --- 2. SCRIPT GESTION DES LIVRES (Le tien) ---

        function toggleForm(show = null) {
            const container = document.getElementById('bookFormContainer');
            if (show === null) {
                container.classList.toggle('show');
            } else if (show) {
                container.classList.add('show');
            } else {
                container.classList.remove('show');
            }
        }

        function resetForm() {
            document.getElementById('bookForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('formTitle').textContent = 'Ajouter un Livre';
            document.getElementById('submitButton').textContent = 'Créer le Livre';
            document.getElementById('bookIsbn').disabled = false;
            toggleForm(false);
        }

        function loadBooks() {
            fetch('../../api/admin/book_crud.php?action=read')
                .then(res => res.json())
                .then(json => {
                    const tableBody = document.getElementById('booksTableBody');
                    tableBody.innerHTML = '';
                    
                    if (json.status !== 'success' || json.data.length === 0) {
                        tableBody.innerHTML = '<tr class="empty-row"><td colspan="4">Aucun livre dans le catalogue.</td></tr>';
                        return;
                    }

                    json.data.forEach(book => {
                        const row = tableBody.insertRow();
                        row.className = 'book-row';
                        
                        row.insertCell().textContent = book.isbn;
                        
                        const titleCell = row.insertCell();
                        titleCell.innerHTML = `
                            <div class="book-title">${escapeHtml(book.titre)}</div>
                            <div class="book-author">${escapeHtml(book.auteur)}</div>
                        `;
                        
                        const copiesCell = row.insertCell();
                        const isAvailable = book.nbre_de_copie_disponible > 0;
                        copiesCell.innerHTML = `
                            <div class="copies-info">
                                <span class="available-copies ${isAvailable ? 'available' : 'unavailable'}">
                                    ${book.nbre_de_copie_disponible}
                                </span>
                                <span>/</span>
                                <span>${book.nbre_de_copie_total}</span>
                            </div>
                        `;

                        const actionCell = row.insertCell();
                        actionCell.className = 'actions';
                        actionCell.innerHTML = `
                            <button onclick="editBook('${book.isbn}')" class="edit-btn">
                                Modifier
                            </button>
                            <button onclick="deleteBook('${book.isbn}')" class="delete-btn">
                                Supprimer
                            </button>
                        `;
                    });
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('booksTableBody').innerHTML = 
                        '<tr class="error-row"><td colspan="4">Erreur de chargement</td></tr>';
                });
        }

        function editBook(isbn) {
            fetch(`../../api/admin/book_crud.php?action=read_one&isbn=${isbn}`)
                .then(res => res.json())
                .then(json => {
                    if (json.status !== 'success') {
                        alert("Erreur: " + json.message);
                        return;
                    }
                    const book = json.data;

                    document.getElementById('bookIsbn').value = book.isbn;
                    document.getElementById('originalIsbn').value = book.isbn;
                    document.getElementById('bookTitre').value = book.titre;
                    document.getElementById('bookAuteur').value = book.auteur;
                    document.getElementById('bookAnnee').value = book.annee_publication;
                    document.getElementById('bookPages').value = book.nbre_de_pages;
                    document.getElementById('bookTotalCopies').value = book.nbre_de_copie_total;
                    document.getElementById('bookLangue').value = book.langue;
                    document.getElementById('bookImage').value = book.image_livre;
                    document.getElementById('bookResume').value = book.resume || '';
                    
                    document.getElementById('bookIsbn').disabled = true;

                    // Sélectionner les catégories
                    const select = document.getElementById('bookCategories');
                    Array.from(select.options).forEach(option => {
                        option.selected = book.categories && book.categories.includes(parseInt(option.value));
                    });

                    document.getElementById('formAction').value = 'update';
                    document.getElementById('formTitle').textContent = 'Modifier le Livre';
                    document.getElementById('submitButton').textContent = 'Sauvegarder';
                    toggleForm(true);
                });
        }

        function deleteBook(isbn) {
            if (!confirm(`Supprimer le livre ${isbn} ?\n\nCette action est irréversible.`)) return;

            fetch('../../api/admin/book_crud.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=delete&isbn=' + encodeURIComponent(isbn)
            })
            .then(res => res.json())
            .then(json => {
               if (json.status === 'success') {
                Swal.fire({
                icon: 'success',
                title: 'Supprimé !',
                text: 'Le livre a été supprimé avec succès.',
                timer: 2000,
                showConfirmButton: false
                });
     
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: json.message || 'Une erreur est survenue.'
                    });
}
                loadBooks();
            });
        }

        function handleBookSubmit(event) {
            event.preventDefault();
            
            const action = document.getElementById('formAction').value;
            const formData = new FormData(document.getElementById('bookForm'));
            
            if (action === 'update') {
                document.getElementById('bookIsbn').disabled = false;
            }

            const params = new URLSearchParams(formData);

            fetch('../../api/admin/book_crud.php', {
                method: 'POST',
                body: params
            })
            .then(res => res.json())
            .then(json => {
                if (json.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Supprimé !',
                        text: 'Le livre a été supprimé avec succès.',
                        timer: 2000,
                        showConfirmButton: false
                    });
    
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: json.message || 'Une erreur est survenue.'
                        });
}
                if (json.status === 'success') {
                    resetForm();
                    loadBooks();
                }
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