<?php
// Fichier: students/books.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('student');

$user = $_SESSION['user'];

// Récupérer les catégories pour le filtre
$stmt_categories = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue des Livres - Lireo</title>
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
            --primary: #00215bff;
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
        .main {
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
        .actions-bar {
            display: flex;
            justify-content: flex-end;
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

        /* Search Container */
        .search-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        @media (min-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr 1fr auto;
            }
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .search-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 150px;
        }

        .search-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
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

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (min-width: 640px) {
            .books-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .books-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .books-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Book Card */
        .book-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .book-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .book-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .book-card:hover .book-image {
            transform: scale(1.05);
        }

        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--white);
        }

        .available {
            background-color: var(--success);
        }

        .unavailable {
            background-color: var(--danger);
        }

        .book-content {
            padding: 20px;
        }

        .book-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .book-categories {
            color: var(--secondary);
            font-size: 0.8rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-details {
            display: flex;
            justify-content: space-between;
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .book-copies {
            color: var(--gray);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .book-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }

        .btn-details {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .btn-details:hover {
            background-color: #d1d9e0;
        }

        .btn-borrow {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-borrow:hover {
            background-color: var(--primary-dark);
        }

        .btn-reserve {
            background-color: var(--warning);
            color: var(--white);
        }

        .btn-reserve:hover {
            background-color: #e0a800;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }

        .pagination-btn {
            padding: 8px 15px;
            border: 1px solid var(--gray-light);
            background-color: var(--white);
            color: var(--dark);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            background-color: var(--gray-light);
        }

        .pagination-btn.active {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
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
            max-width: 900px;
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
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        @media (min-width: 768px) {
            .modal-body {
                grid-template-columns: 1fr 2fr;
            }
        }

        .modal-book-image {
            width: 100%;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .modal-details {
            color: var(--dark);
        }

        .modal-detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .modal-detail-item {
            margin-bottom: 10px;
        }

        .modal-detail-label {
            font-weight: 600;
            color: var(--dark);
            display: block;
            margin-bottom: 5px;
        }

        .modal-availability {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .modal-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .modal-unavailable {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .modal-summary {
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
        }

        .modal-btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .modal-btn-borrow {
            background-color: var(--primary);
            color: var(--white);
        }

        .modal-btn-borrow:hover {
            background-color: var(--primary-dark);
        }

        .modal-btn-reserve {
            background-color: var(--warning);
            color: var(--white);
        }

        .modal-btn-reserve:hover {
            background-color: #e0a800;
        }

        .modal-btn-close {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .modal-btn-close:hover {
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
            
            .search-grid {
                grid-template-columns: 1fr;
            }
            
            .book-actions {
                flex-direction: column;
            }
            
            .modal-body {
                grid-template-columns: 1fr;
            }
            
            .modal-detail-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .search-container,
            .book-content {
                padding: 20px;
            }
            
            .empty-state i {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include_once __DIR__ . '/../../includes/sidebarstudent.php'; ?>
    <!-- Main Content -->
    
        
    <main class="main-content">

       <header class="header" style="display: flex; justify-content: flex-start; align-items: center; gap: 20px;">
            <i class="fas fa-bars" id="header_toggle_btn" style="font-size: 24px; cursor: pointer; color: #2a5298;"></i>
            <div>
                <h1 style="margin: 0; font-size: 1.8rem; color: #2a5298;">Catalogue des Livres</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Découvrez et réservez les livres de la bibliothèque</p>
            </div>
        </header>
       
        

        <!-- Search Container -->
        <div class="search-container">
            <div class="search-grid">
                <div class="form-group">
                    <label class="form-label">Recherche</label>
                    <input type="text" id="searchInput" placeholder="Titre, auteur, ISBN..." class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Catégorie</label>
                    <select id="categoryFilter" class="form-input">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id_categorie'] ?>"><?= htmlspecialchars($category['nom_categorie']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button onclick="searchBooks()" class="search-btn">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="loading-spinner" style="display: none;">
            <div class="spinner"></div>
            <p>Recherche en cours...</p>
        </div>

        <!-- Books Grid -->
        <div id="booksContainer" class="books-grid">
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>Rechercher des livres</h3>
                <p>Utilisez la barre de recherche pour trouver des livres</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="pagination" style="display: none;"></div>
    </main>

    <!-- Book Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <div id="bookModalContent">
                <!-- Modal content will be loaded by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        
    let currentPage = 1;
    const booksPerPage = 12;

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
        // Recherche initiale
        searchBooks();
        
        // Recherche en temps réel
        document.getElementById('searchInput').addEventListener('input', debounce(searchBooks, 500));
        document.getElementById('categoryFilter').addEventListener('change', searchBooks);
        
        // Recherche avec Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBooks();
            }
        });
    });

    function searchBooks(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        
        const container = document.getElementById('booksContainer');
        const loading = document.getElementById('loadingIndicator');
        const pagination = document.getElementById('pagination');
        
        container.innerHTML = '';
        loading.style.display = 'block';
        pagination.style.display = 'none';
        
        const params = new URLSearchParams({
            q: search,
            cat: category,
            page: page,
            limit: booksPerPage
        });
        
        fetch(`../../api/books/search.php?${params}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                displayBooks(data.data || []);
                setupPagination(data.total || 0);
            })
            .catch(error => {
                loading.style.display = 'none';
                container.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Erreur lors de la recherche</h3>
                        <p>Veuillez réessayer plus tard</p>
                    </div>
                `;
                console.error('Erreur:', error);
            });
    }

    function displayBooks(books) {
        const container = document.getElementById('booksContainer');
        
        if (!books || books.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Aucun livre trouvé</h3>
                    <p>Essayez de modifier vos critères de recherche</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        books.forEach(book => {
            const isAvailable = book.nbre_de_copie_disponible > 0;
            
            html += `
                <div class="book-card">
                    <div class="book-image-container">
                        <img src="../${escapeHtml(book.image_livre)}" 
                             alt="${escapeHtml(book.titre)}"
                             class="book-image"
                             ">
                        <span class="availability-badge ${isAvailable ? 'available' : 'unavailable'}">
                            ${isAvailable ? 'Disponible' : 'Indisponible'}
                        </span>
                    </div>
                    <div class="book-content">
                        <h3 class="book-title">${escapeHtml(book.titre)}</h3>
                        <p class="book-author">${escapeHtml(book.auteur)}</p>
                        
                        ${book.categories_noms ? `
                            <p class="book-categories">${escapeHtml(book.categories_noms)}</p>
                        ` : ''}
                        
                        <div class="book-details">
                            <span>${book.nbre_de_pages || '?'} pages</span>
                            <span>${book.annee_publication || '?'}</span>
                        </div>
                        
                        <p class="book-copies">
                            ${book.nbre_de_copie_disponible}/${book.nbre_de_copie_total} copies
                        </p>
                        
                        <div class="book-actions">
                            <button onclick="viewBookDetails('${book.isbn}')" class="btn btn-details">
                                <i class="fas fa-info-circle"></i> Détails
                            </button>
                            ${isAvailable ? `
                                <button onclick="borrowBook('${book.isbn}')" class="btn btn-borrow">
                                    <i class="fas fa-book"></i> Emprunter
                                </button>
                            ` : `
                                <button onclick="reserveBook('${book.isbn}')" class="btn btn-reserve">
                                    <i class="fas fa-clock"></i> Réserver
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function setupPagination(totalBooks) {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(totalBooks / booksPerPage);
        
        if (totalPages <= 1) {
            pagination.style.display = 'none';
            return;
        }
        
        pagination.style.display = 'flex';
        
        let paginationHTML = '';
        
        // Bouton précédent
        if (currentPage > 1) {
            paginationHTML += `
                <button onclick="searchBooks(${currentPage - 1})" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i> Précédent
                </button>
            `;
        }
        
        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                paginationHTML += `
                    <button class="pagination-btn active">${i}</button>
                `;
            } else {
                paginationHTML += `
                    <button onclick="searchBooks(${i})" class="pagination-btn">${i}</button>
                `;
            }
        }
        
        // Bouton suivant
        if (currentPage < totalPages) {
            paginationHTML += `
                <button onclick="searchBooks(${currentPage + 1})" class="pagination-btn">
                    Suivant <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }
        
        pagination.innerHTML = paginationHTML;
    }

    function viewBookDetails(isbn) {
        const modal = document.getElementById('bookModal');
        const content = document.getElementById('bookModalContent');
        
        content.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Chargement...</p>
            </div>
        `;
        
        modal.style.display = 'flex';
        
        fetch(`../../api/books/get_one.php?isbn=${isbn}`)
            .then(response => response.json())
            .then(book => {
                if (!book) {
                    content.innerHTML = `
                        <div class="error-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Livre non trouvé</h3>
                            <button onclick="closeBookModal()" class="modal-btn modal-btn-close">
                                Fermer
                            </button>
                        </div>
                    `;
                    return;
                }
                
                const isAvailable = book.nbre_de_copie_disponible > 0;
                
                content.innerHTML = `
                    <div class="modal-header">
                        <h2>${escapeHtml(book.titre)}</h2>
                        <p class="book-author">${escapeHtml(book.auteur)}</p>
                    </div>
                    <div class="modal-body">
                        <div>
                            <img src="../${escapeHtml(book.image_livre)}" 
                                 alt="${escapeHtml(book.titre)}"
                                 class="modal-book-image"
                                 onerror="this.src='../../assets/images_livres/books/1984.jpg'">
                            <div class="modal-availability ${isAvailable ? 'modal-available' : 'modal-unavailable'}">
                                ${isAvailable ? 
                                    `${book.nbre_de_copie_disponible} copie(s) disponible(s)` : 
                                    'Indisponible'
                                }
                            </div>
                        </div>
                        <div class="modal-details">
                            <div class="modal-detail-row">
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">ISBN:</span>
                                    <p>${escapeHtml(book.isbn)}</p>
                                </div>
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">Pages:</span>
                                    <p>${book.nbre_de_pages || 'Non spécifié'}</p>
                                </div>
                            </div>
                            <div class="modal-detail-row">
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">Année:</span>
                                    <p>${book.annee_publication || 'Non spécifié'}</p>
                                </div>
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">Langue:</span>
                                    <p>${escapeHtml(book.langue || 'Français')}</p>
                                </div>
                            </div>
                            ${book.resume ? `
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">Résumé:</span>
                                    <p class="modal-summary">${escapeHtml(book.resume)}</p>
                                </div>
                            ` : ''}
                            <div class="modal-actions">
                                ${isAvailable ? `
                                    <button onclick="borrowBook('${book.isbn}', true)" class="modal-btn modal-btn-borrow">
                                        <i class="fas fa-book"></i> Emprunter ce livre
                                    </button>
                                ` : `
                                    <button onclick="reserveBook('${book.isbn}', true)" class="modal-btn modal-btn-reserve">
                                        <i class="fas fa-clock"></i> Réserver ce livre
                                    </button>
                                `}
                                <button onclick="closeBookModal()" class="modal-btn modal-btn-close">
                                    <i class="fas fa-times"></i> Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Erreur lors du chargement</h3>
                        <button onclick="closeBookModal()" class="modal-btn modal-btn-close">
                            Fermer
                        </button>
                    </div>
                `;
                console.error('Erreur:', error);
            });
    }

    function closeBookModal() {
        document.getElementById('bookModal').style.display = 'none';
    }

    function borrowBook(isbn, fromModal = false) {
        if (!confirm('Êtes-vous sûr de vouloir emprunter ce livre ?')) return;
        
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
                if (fromModal) {
                    closeBookModal();
                }
                searchBooks(currentPage); // Recharger les résultats
            }
        })
        .catch(error => {
            alert('❌ Erreur lors de la demande d\'emprunt');
            console.error(error);
        });
    }

    function reserveBook(isbn, fromModal = false) {
        if (!confirm('Êtes-vous sûr de vouloir réserver ce livre ?')) return;
        
        fetch('../../api/emprunt/request_reserve.php', {
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
                if (fromModal) {
                    closeBookModal();
                }
                searchBooks(currentPage); // Recharger les résultats
            }
        })
        .catch(error => {
            alert('❌ Erreur lors de la réservation');
            console.error(error);
        });
    }

    // Fonctions utilitaires
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    </script>
</body>
</html>