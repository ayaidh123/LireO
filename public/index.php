<?php
require_once __DIR__ . '/../includes/db.php';

// Récupérer les livres populaires (les plus empruntés)
// Note: Assurez-vous que $pdo est bien défini dans includes/db.php
$stmt = $pdo->query("
    SELECT l.*, COUNT(e.id_emprunt) as nb_emprunts
    FROM livre l
    LEFT JOIN emprunt e ON l.isbn = e.isbn
    GROUP BY l.isbn
    ORDER BY nb_emprunts DESC, l.titre ASC
    LIMIT 8
");
$livres_populaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les nouvelles acquisitions
$stmt_nouveautes = $pdo->query("
    SELECT * FROM livre 
    ORDER BY annee_publication DESC, date_ajout DESC 
    LIMIT 6
");
$nouveautes = $stmt_nouveautes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lireo – Accueil</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ---------------------------------------------------------
           1. VARIABLES & RESET
           --------------------------------------------------------- */
        :root {
            --primary: #142e5aff;
            --primary-dark: #1e3c72;
            --text-dark: #333;
            --text-light: #666;
            --bg-body: #f5f7fa;
            --white: #ffffff;
            --success: #10b981;
            --danger: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        a { text-decoration: none; }
        ul { list-style: none; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ---------------------------------------------------------
           2. NAVBAR
           --------------------------------------------------------- */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(42, 82, 152, 0.1);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 82, 152, 0.3);
        }

        /* ---------------------------------------------------------
           3. HERO SECTION (Bannière avec Image & Recherche)
           --------------------------------------------------------- */
        .hero {
            text-align: center;
            padding: 100px 20px 120px;
            /* Image de fond avec filtre sombre pour lisibilité */
            background: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            border-bottom: 1px solid #1e3c72;
            color: white;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.25rem;
            color: #e2e8f0;
            max-width: 700px;
            margin: 0 auto 30px;
            font-weight: 500;
        }

        /* Barre de Recherche */
        .search-container {
            position: relative;
            max-width: 600px;
            margin: 30px auto 0;
        }

        .search-input {
            width: 100%;
            padding: 18px 60px 18px 25px;
            border-radius: 50px;
            border: none;
            font-size: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            outline: none;
            transition: transform 0.3s;
        }

        .search-input:focus {
            transform: scale(1.02);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 5px;
            height: 46px;
            width: 46px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .search-btn:hover {
            background: var(--primary-dark);
            transform: rotate(15deg);
        }

        /* ---------------------------------------------------------
           4. SECTIONS GENERALES (Grilles & Cartes)
           --------------------------------------------------------- */
        main { flex: 1; }
        
        section { padding: 60px 0; }

        .section-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 40px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .feature-card p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
        }

        .book-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .book-image-container {
            position: relative;
            width: 100%;
            padding-top: 150%; /* Aspect ratio 2:3 */
            overflow: hidden;
        }

        .book-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .book-card:hover .book-image {
            transform: scale(1.05);
        }

        .book-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .book-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-dark);
            margin-bottom: 5px;
            /* Propriétés pour limiter le titre à 2 lignes avec '...' */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
            height: 2.8em;
        }

        .book-author {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-available { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-unavailable { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .badge-borrow-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary);
            color: var(--white);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .stat-icon { font-size: 2rem; color: var(--primary); margin-bottom: 10px; }
        .stat-number { font-size: 2rem; font-weight: 800; color: var(--text-dark); }
        .stat-label { color: var(--text-light); font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }

        /* CTA */
        .cta-section {
            background-color: var(--white);
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: var(--shadow);
            margin-bottom: 0;
            border: 2px solid #f0f5ff;
        }

        /* ---------------------------------------------------------
           5. SECTION INFOS PRATIQUES & FOOTER
           --------------------------------------------------------- */
        .info-section {
            background-color: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 60px 0;
            margin-top: 60px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
        }

        .info-card {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .info-icon-wrapper {
            background: #eef2ff;
            padding: 15px;
            border-radius: 50%;
            color: var(--primary);
            min-width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .info-content h4 {
            color: var(--primary-dark);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .info-content p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        footer {
            background-color: var(--white);
            padding: 30px 0;
            text-align: center;
            color: var(--text-light);
            border-top: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .books-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            nav { flex-direction: column; height: auto; padding: 15px 0; gap: 15px; }
            .info-card { flex-direction: column; align-items: center; text-align: center; }
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">
                    <i class="fas fa-book-open"></i> Lireo
                </a>
                <div class="nav-buttons">
                    <a href="login.php" class="btn btn-outline">Se connecter</a>
                    <a href="register.php" class="btn btn-primary">S'inscrire</a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        
        <div class="hero">
            <div class="container">
                <h1>Bienvenue dans votre <br> Bibliothèque numérique Lireo</h1>
                <p>
                    Explorez, réservez et empruntez vos ouvrages de référence en quelques clics. 
                    Système de gestion académique unifié.
                </p>
                
                <form action="catalogue.php" method="GET" class="search-container">
                    <input type="text" name="q" class="search-input" placeholder="Titre, auteur, ISBN..." required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="container">
            
            <section>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-layer-group"></i></div>
                        <h3>Catalogue Étendu</h3>
                        <p>Des milliers d'ouvrages couvrant tous les domaines académiques : sciences, droit, littérature, etc.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-clock"></i></div>
                        <h3>Retrait Rapide</h3>
                        <p>Réservez en ligne et retirez vos livres physiquement à la bibliothèque universitaire sous 24h.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-sync-alt"></i></div>
                        <h3>Gestion Simplifiée</h3>
                        <p>Consultez vos prêts en cours, prolongez vos emprunts et gérez votre compte étudiant.</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="section-title">
                    <i class="fas fa-calendar-plus" style="color: var(--primary);"></i> Nouvelles Acquisitions
                </h2>
                
                <div class="books-grid">
                    <?php if (!empty($nouveautes)): ?>
                        <?php foreach ($nouveautes as $livre): ?>
                            <div class="book-card">
                                <div class="book-image-container">
                                    <img src="../public/<?= htmlspecialchars($livre['image_livre']) ?>" 
                                        alt="<?= htmlspecialchars($livre['titre']) ?>"
                                        class="book-image">
                                </div>
                                <div class="book-info">
                                    <div>
                                        <h4 class="book-title"><?= htmlspecialchars($livre['titre']) ?></h4>
                                        <p class="book-author"><?= htmlspecialchars($livre['auteur']) ?></p>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <?php if ($livre['nbre_de_copie_disponible'] > 0): ?>
                                            <span class="badge badge-available">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge badge-unavailable">Indisponible</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; width: 100%; grid-column: 1/-1;">Aucune nouvelle acquisition pour le moment.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section id="catalogue">
                <h2 class="section-title">
                    <i class="fas fa-chart-line" style="color: var(--primary);"></i> Ouvrages les plus consultés
                </h2>
                
                <div class="books-grid">
                    <?php if (!empty($livres_populaires)): ?>
                        <?php foreach ($livres_populaires as $livre): ?>
                            <div class="book-card">
                                <div class="book-image-container">
                                    <img src="../public/<?= htmlspecialchars($livre['image_livre']) ?>" 
                                        alt="<?= htmlspecialchars($livre['titre']) ?>"
                                        class="book-image">
                                         
                                    
                                    <?php if ($livre['nb_emprunts'] > 0): ?>
                                        <div class="badge-borrow-count">
                                            <?= $livre['nb_emprunts'] ?> emprunt<?= $livre['nb_emprunts'] > 1 ? 's' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <div>
                                        <h4 class="book-title"><?= htmlspecialchars($livre['titre']) ?></h4>
                                        <p class="book-author"><?= htmlspecialchars($livre['auteur']) ?></p>
                                    </div>
                                    <div style="margin-top: 10px;">
                                        <?php if ($livre['nbre_de_copie_disponible'] > 0): ?>
                                            <span class="badge badge-available">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge badge-unavailable">Indisponible</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; width: 100%; grid-column: 1/-1;">Aucun ouvrage disponible pour le moment.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section>
                <?php
                // Note: Assurez-vous que la connexion PDO est bonne
                $count_livres = $pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn();
                $count_etudiants = $pdo->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
                $count_emprunts = $pdo->query("SELECT COUNT(*) FROM emprunt WHERE date_retour_reel IS NULL")->fetchColumn();
                $count_categories = $pdo->query("SELECT COUNT(*) FROM categorie")->fetchColumn();
                
                $stats = [
                    ['count' => $count_livres, 'label' => 'Ouvrages', 'icon' => 'fa-book'],
                    ['count' => $count_etudiants, 'label' => 'Étudiants Inscrits', 'icon' => 'fa-user-graduate'],
                    ['count' => $count_emprunts, 'label' => 'Emprunts en cours', 'icon' => 'fa-book-reader'],
                    ['count' => $count_categories, 'label' => 'Disciplines', 'icon' => 'fa-tags']
                ];
                ?>
                <div class="stats-container">
                    <?php foreach ($stats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas <?= $stat['icon'] ?>"></i></div>
                            <div class="stat-number"><?= $stat['count'] ?></div>
                            <div class="stat-label"><?= $stat['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="cta-section">
                <h2>Prêt à commencer vos recherches ?</h2>
                <p style="margin: 15px 0 30px; color: #666;">
                    Rejoignez la communauté universitaire et accédez aux ressources documentaires.
                </p>
                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <a href="login.php" class="btn btn-primary">Se connecter</a>
                    <a href="register.php" class="btn btn-outline">Créer un compte</a>
                </div>
            </section>

        </div>
        
        <div class="info-section">
            <div class="container">
                <div class="info-grid">
                    
                    <div class="info-card">
                        <div class="info-icon-wrapper">
                            <i class="far fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h4>Horaires d'ouverture</h4>
                            <p>
                                Lun - Ven : 08h30 - 18h30<br>
                                Samedi : 09h00 - 12h00<br>
                                <span style="color: var(--danger); font-size: 0.85rem;">Dimanche : Fermé</span>
                            </p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Nous trouver</h4>
                            <p>
                                Bibliothèque Universitaire UMI<br>
                                Quartier Marjane 2<br>
                                Meknès, Maroc
                            </p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon-wrapper">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="info-content">
                            <h4>Besoin d'aide ?</h4>
                            <p>
                                Email : support@umi.ac.ma<br>
                                Tél : +212 5 35 00 00 00
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> MaBibliothèque - Université UMI. Tous droits réservés.</p>
        </div>
    </footer>

</body>
</html>