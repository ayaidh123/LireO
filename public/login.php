<?php
// ---------------------------------------------------------
// PARTIE LOGIQUE (Intacte)
// ---------------------------------------------------------
require_once __DIR__ . '/../includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation email UMI pour les étudiants
    if ($role === 'student' && !preg_match('/^[a-zA-Z0-9._-]+@umi\.ac\.ma$/', $email)) {
        $errors[] = "Pour les étudiants, l'email doit être @umi.ac.ma";
    }

    if (empty($errors) && $role === 'student') {
        global $pdo;
        $stmt = $pdo->prepare("SELECT mot_de_passe, statut FROM etudiant WHERE email_academique = ? LIMIT 1");
        $stmt->execute([$email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && $student['mot_de_passe'] === null) {
            $errors[] = "Votre compte n'est pas encore activé. Veuillez l'activer depuis la page d'inscription.";
        } elseif ($student && $student['statut'] !== 'actif') {
            $errors[] = "Votre compte a été bloqué. Contactez l'administration.";
        }
    }

    if (empty($errors) && attempt_login($email, $password, $role)) {
        if ($role === 'student') {
            header('Location: students/dashboard.php');
        } else {
            header('Location: admin/dashboard.php');
        }
        exit;
    } else if (empty($errors)) {
        $errors[] = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIREO - Connexion</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ---------------------------------------------------------
           STYLE GLOBAL
           --------------------------------------------------------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            height: 100vh;
            width: 100%;
            display: flex;
            background-color: #fff;
            overflow: hidden; /* Empêche le scroll global sur PC */
        }
        
        /* ---------------------------------------------------------
           SECTION GAUCHE : FORMULAIRE
           --------------------------------------------------------- */
        .login-section {
            width: 40%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 60px;
            background: white;
            overflow-y: auto; /* Scroll si l'écran est petit */
            position: relative;
            z-index: 10;
            box-shadow: 5px 0 25px rgba(0,0,0,0.05);
        }

        /* --- BOUTON RETOUR --- */
        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            background-color: transparent;
        }

        .back-btn:hover {
            color: #2a5298;
            background-color: #f1f5f9;
        }

        .back-btn i {
            font-size: 0.85rem;
            transition: transform 0.2s;
        }

        .back-btn:hover i {
            transform: translateX(-3px); /* Petite animation flèche */
        }

        /* ---------------------------------------------------------
           SECTION DROITE : IMAGE
           --------------------------------------------------------- */
        .image-section {
            width: 60%;
            position: relative;
            background-color: #1e3c72; /* Couleur de fond de secours */
            
            /* --- CHEMIN VERS TON IMAGE --- */
            background-image: url('assets/images/library-b.jpg'); 
            
            background-size: cover;
            background-position: center;
        }

        /* Overlay bleu pour l'harmonie et la lisibilité du texte */
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(42, 82, 152, 0.4), rgba(30, 60, 114, 0.7));
        }

        .image-text {
            position: absolute;
            bottom: 60px;
            left: 60px;
            color: white;
            z-index: 2;
            max-width: 80%;
        }

        .image-text h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .image-text p {
            font-size: 1.1rem;
            opacity: 0.95;
            line-height: 1.6;
            font-weight: 300;
        }
        
        /* ---------------------------------------------------------
           HEADER & LOGO
           --------------------------------------------------------- */
        .header {
            margin-bottom: 35px;
            margin-top: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo i {
            font-size: 2rem;
            margin-right: 12px;
            color: #2a5298;
        }
        
        .logo h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #2a5298;
            letter-spacing: -0.5px;
        }
        
        .header h2 {
            color: #1e293b;
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: #64748b;
            font-size: 1rem;
        }
        
        /* ---------------------------------------------------------
           COMPOSANTS FORMULAIRE
           --------------------------------------------------------- */
        
        /* Messages d'erreur */
        .alert-box {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 0.9rem;
        }
        .alert-box ul { list-style: none; }
        .alert-box li { margin-bottom: 5px; }
        
        /* Sélecteur de rôle (Cards) */
        .user-type {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .user-option {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid #e2e8f0;
            background-color: #f8fafc;
            position: relative;
        }
        
        .user-option:hover {
            border-color: #cbd5e1;
            background-color: #f1f5f9;
        }

        .user-option.active {
            border-color: #2a5298;
            background-color: #eff6ff;
            color: #2a5298;
            box-shadow: 0 4px 6px -1px rgba(42, 82, 152, 0.1);
        }
        
        .user-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .user-option i {
            font-size: 1.4rem;
            margin-bottom: 6px;
            color: #64748b;
            transition: color 0.2s;
        }
        .user-option.active i { color: #2a5298; }
        
        .user-option span {
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
        }
        .user-option.active span { color: #2a5298; }
        
        /* Inputs */
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper { position: relative; }
        
        .input-wrapper input {
            width: 100%;
            padding: 14px 16px;
            padding-left: 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
            color: #333;
        }
        
        .input-wrapper i.input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.3s;
            pointer-events: none;
        }
        
        .input-wrapper input:focus {
            border-color: #2a5298;
            outline: none;
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.1);
        }

        .input-wrapper input:focus + i.input-icon { color: #2a5298; }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }
        .password-toggle:hover { color: #2a5298; }
        
        /* Bouton Login */
        .login-btn {
            background-color: #2a5298;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(42, 82, 152, 0.2);
        }
        
        .login-btn:hover {
            background-color: #1e3c72;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(42, 82, 152, 0.3);
        }
        
        /* Liens */
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .register-link a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 700;
        }
        .register-link a:hover { text-decoration: underline; }
        
        /* ---------------------------------------------------------
           RESPONSIVE MOBILE (max-width: 900px)
           --------------------------------------------------------- */
        @media (max-width: 900px) {
            .login-section { width: 100%; padding: 40px 25px; }
            .image-section { display: none; } /* Cache l'image sur mobile */
            .back-btn { top: 20px; left: 20px; }
            body { overflow: auto; height: auto; min-height: 100vh; }
        }
    </style>
</head>
<body>
    
    <div class="login-section">
        
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Retour</span>
        </a>

        <div class="header">
            <div class="logo">
                <i class="fas fa-book-reader"></i>
                <h1>Bibliothèque</h1>
            </div>
            <h2>Bienvenue 👋</h2>
            <p>Connectez-vous pour accéder à votre espace.</p>
        </div>
        
        <?php if ($errors): ?>
            <div class="alert-box">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            
            <div class="user-type">
                <div class="user-option <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'active' : ''; ?>" id="student-option">
                    <input type="radio" id="role-student" name="role" value="student" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : ''; ?>>
                    <i class="fas fa-user-graduate"></i>
                    <span>Étudiant</span>
                </div>
                
                <div class="user-option <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'active' : ''; ?>" id="admin-option">
                    <input type="radio" id="role-admin" name="role" value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : ''; ?>>
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Adresse Email</label>
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                        placeholder="votre.email@umi.ac.ma" 
                        required 
                    >
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required
                    >
                    <i class="fas fa-lock input-icon"></i>
                    <span class="password-toggle" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="login-btn">Se connecter</button>
        </form>
        
        <div class="register-link">
            Pas encore inscrit ? <br>
            <a href="register.php">Créer un compte étudiant</a>
        </div>
    </div>

    <div class="image-section">
        <div class="image-overlay"></div>
        <div class="image-text">
            <h2>L'Espace du Savoir</h2>
            <p>Votre portail vers la connaissance. Accédez à des milliers de ressources depuis votre espace personnel.</p>
        </div>
    </div>

    <script>
        // Gestion visuelle des boutons radio (Etudiant / Admin)
        const studentOption = document.getElementById('student-option');
        const adminOption = document.getElementById('admin-option');
        const roleStudentRadio = document.getElementById('role-student');
        const roleAdminRadio = document.getElementById('role-admin');
        const emailInput = document.getElementById('email');

        function updateRoleVisuals() {
            if(roleStudentRadio.checked) {
                studentOption.classList.add('active');
                adminOption.classList.remove('active');
                emailInput.placeholder = 'votre.email@umi.ac.ma';
                emailInput.pattern = '[a-zA-Z0-9._-]+@umi\\.ac\\.ma';
            } else {
                adminOption.classList.add('active');
                studentOption.classList.remove('active');
                emailInput.placeholder = 'admin@bibliotheque.fr';
                emailInput.removeAttribute('pattern');
            }
        }

        // Événements clic sur les divs
        studentOption.addEventListener('click', () => {
            roleStudentRadio.checked = true;
            updateRoleVisuals();
        });

        adminOption.addEventListener('click', () => {
            roleAdminRadio.checked = true;
            updateRoleVisuals();
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', updateRoleVisuals);
        
        // Afficher / Masquer le mot de passe
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const eyeIcon = togglePassword.querySelector('i');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>