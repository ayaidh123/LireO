<!-- sidebar.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* --- VARIABLES --- */
    :root {
        --nav-width-open: 250px;
        --nav-width-close: 78px;
        --bg-color: #1a1f35;
        --text-color: #fff;
        --hover-bg: rgba(255,255,255,0.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f6fa;
    }

    /* --- SIDEBAR --- */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: var(--nav-width-open);
        background: var(--bg-color);
        padding: 6px 14px;
        z-index: 99;
        transition: all 0.5s ease;
        overflow-x: hidden;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    /* État Fermé */
    .sidebar.close {
        width: var(--nav-width-close);
    }

    /* Masquer texte quand fermé */
    .sidebar.close .logo_name,
    .sidebar.close .links_name {
        opacity: 0;
        pointer-events: none;
    }

    /* Logo Area */
    .logo-details {
        height: 60px;
        display: flex;
        align-items: center;
        position: relative;
    }
    
    .logo-details .icon {
        min-width: 50px;
        font-size: 24px;
        color: var(--text-color);
        text-align: center;
        line-height: 50px;
    }
    
    .logo-details .logo_name {
        color: var(--text-color);
        font-size: 20px;
        font-weight: 600;
        margin-left: 10px;
        transition: 0.3s ease;
        white-space: nowrap;
    }

    /* Navigation List */
    .nav-list {
        height: calc(100% - 60px);
        padding: 20px 0;
        list-style: none;
        display: flex;
        flex-direction: column;
    }
    
    .sidebar li {
        position: relative;
        margin: 8px 0;
    }
    
    .sidebar li a {
        display: flex;
        align-items: center;
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.4s ease;
        white-space: nowrap;
        padding: 5px;
    }
    
    .sidebar li a:hover {
        background: var(--hover-bg);
    }

    /* Lien Actif */
    .sidebar li a.active {
        background: rgba(42, 82, 152, 0.5);
    }

    .sidebar li i {
        height: 50px;
        min-width: 50px;
        line-height: 50px;
        text-align: center;
        font-size: 18px;
        color: #b0b7c3;
        transition: color 0.3s;
    }
    
    .sidebar li a:hover i,
    .sidebar li a.active i {
        color: var(--text-color);
    }
    
    .sidebar li .links_name {
        color: #b0b7c3;
        font-size: 15px;
        transition: all 0.4s ease;
    }
    
    .sidebar li a:hover .links_name,
    .sidebar li a.active .links_name {
        color: var(--text-color);
    }

    /* Pousser déconnexion en bas */
    .nav-list .logout-item {
        margin-top: auto;
    }

    /* --- CONTENU PRINCIPAL --- */
    .main-content {
        position: relative;
        min-height: 100vh;
        left: var(--nav-width-open);
        width: calc(100% - var(--nav-width-open));
        transition: all 0.5s ease;
        background: #f5f6fa;
    }

    /* Quand sidebar fermée */
    .sidebar.close ~ .main-content {
        left: var(--nav-width-close);
        width: calc(100% - var(--nav-width-close));
    }

    /* Header du contenu */
    .content-header {
        background: white;
        padding: 20px 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .menu-toggle {
        font-size: 24px;
        cursor: pointer;
        color: var(--bg-color);
        transition: color 0.3s;
    }

    .menu-toggle:hover {
        color: #2a5298;
    }

    .content-header h1 {
        font-size: 24px;
        color: #333;
        margin-left: 20px;
    }

    /* Zone de contenu */
    .content-body {
        padding: 30px;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 768px) {
        .sidebar {
            left: -250px;
        }
        
        .sidebar.close {
            left: 0;
        }
        
        .main-content {
            left: 0;
            width: 100%;
        }
        
        .sidebar.close ~ .main-content {
            left: 0;
            width: 100%;
        }
    }
</style>

<nav class="sidebar">
    <div class="logo-details">
        <i class="fas fa-book-reader icon"></i>
        <span class="logo_name">Lireo</span>
    </div>
    
    <ul class="nav-list">
        <li>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-th-large"></i>
                <span class="links_name">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="books_management.php" class="nav-link">
                <i class="fas fa-book"></i>
                <span class="links_name">Livres</span>
            </a>
        </li>
        <li>
            <a href="students_management.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span class="links_name">Étudiants</span>
            </a>
        </li>
        <li>
            <a href="loans_management.php" class="nav-link">
                <i class="fas fa-exchange-alt"></i>
                <span class="links_name">Emprunts</span>
            </a>
        </li>
        <li>
            <a href="sanctions.php" class="nav-link">
                <i class="fas fa-gavel"></i>
                <span class="links_name">Sanctions</span>
            </a>
        </li>
        <li>
            <a href="messages.php" class="nav-link">
                <i class="fas fa-envelope"></i>
                <span class="links_name">Messages</span>
            </a>
        </li>
        <li class="logout-item">
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span class="links_name">Déconnexion</span>
            </a>
        </li>
    </ul>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.querySelector(".sidebar");
        const menuToggle = document.querySelector(".menu-toggle");
        const navLinks = document.querySelectorAll(".nav-link");
        
        // Toggle Sidebar
        if (menuToggle) {
            menuToggle.addEventListener("click", () => {
                sidebar.classList.toggle("close");
                
                // Sauvegarder l'état dans localStorage
                const isClosed = sidebar.classList.contains("close");
                localStorage.setItem("sidebarClosed", isClosed);
            });
        }
        
        // Restaurer l'état de la sidebar
        const sidebarClosed = localStorage.getItem("sidebarClosed") === "true";
        if (sidebarClosed) {
            sidebar.classList.add("close");
        }
        
        // Marquer le lien actif
        const currentPage = window.location.pathname.split("/").pop();
        navLinks.forEach(link => {
            const linkPage = link.getAttribute("href");
            if (linkPage === currentPage) {
                link.classList.add("active");
            }
        });
    });
</script>