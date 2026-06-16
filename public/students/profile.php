<?php
// Fichier: students/profile.php
require_once __DIR__ . '/../../includes/auth.php'; 
require_once __DIR__ . '/../../includes/db.php';
require_role("student");

$user = $_SESSION['user'];
$messages = [];

// 1. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. CSRF Verification
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Erreur de sécurité : Jeton CSRF invalide.");
    }

    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    
    // --- Update Profile Info (Name/Surname) ---
    if (!empty($prenom) && !empty($nom)) {
        $stmt = $pdo->prepare("UPDATE etudiant SET prenom=?, nom=? WHERE id_etudiant=?");
        if ($stmt->execute([$prenom, $nom, $user['id']])) {
            $_SESSION['user']['prenom'] = $prenom;
            $_SESSION['user']['nom'] = $nom;
            // Only add success message if we aren't also failing a password update later
            $infoUpdated = true;
        }
    } else {
        $messages[] = "❌ Le prénom et le nom sont obligatoires";
    }

    // --- Update Password Logic ---
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    // Only attempt password update if fields are filled
    if (!empty($oldPwd) || !empty($newPwd) || !empty($confirmPwd)) {
        
        if (empty($oldPwd) || empty($newPwd) || empty($confirmPwd)) {
            $messages[] = "❌ Pour changer le mot de passe, tous les champs mot de passe sont requis.";
        } elseif ($newPwd !== $confirmPwd) {
            $messages[] = "❌ Les nouveaux mots de passe ne correspondent pas.";
        } elseif (strlen($newPwd) < 8) {
             $messages[] = "❌ Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } else {
            // Fetch current password hash from DB (Always fetch fresh data)
            $stmt = $pdo->prepare("SELECT mot_de_passe FROM etudiant WHERE id_etudiant = ?");
            $stmt->execute([$user['id']]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($currentUser && password_verify($oldPwd, $currentUser['mot_de_passe'])) {
                // Hash new password
                $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
                
                $updatePwd = $pdo->prepare("UPDATE etudiant SET mot_de_passe = ? WHERE id_etudiant = ?");
                if ($updatePwd->execute([$newHash, $user['id']])) {
                    $messages[] = "✅ Mot de passe mis à jour avec succès.";
                } else {
                    $messages[] = "❌ Erreur base de données lors de la mise à jour du mot de passe.";
                }
            } else {
                $messages[] = "❌ L'ancien mot de passe est incorrect.";
            }
        }
    }

    // Add generic success if info updated and no password errors occurred
    if (isset($infoUpdated) && empty($messages)) {
        $messages[] = "✅ Informations personnelles mises à jour.";
    }
}

// Refresh user data for display
$stmt = $pdo->prepare("SELECT * FROM etudiant WHERE id_etudiant=?");
$stmt->execute([$user['id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) {
    die("Erreur: Profil introuvable");
}

// Set default values for missing keys
$me['compte_active'] = $me['compte_active'] ?? 0;
$me['date_inscription'] = $me['date_inscription'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Lireo</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... [Keep your existing CSS exactly as it was] ... */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root { --primary: #2a5298; --primary-dark: #1e3c72; --primary-light: #4a6fc1; --secondary: #6c757d; --success: #28a745; --info: #17a2b8; --warning: #ffc107; --danger: #dc3545; --light: #f8f9fa; --dark: #343a40; --gray: #6c757d; --gray-light: #e9ecef; --white: #ffffff; --sidebar-bg: #1a1f35; --sidebar-text: #b0b7c3; --sidebar-active: #2a5298; --content-bg: #f5f7fa; --card-bg: #ffffff; --shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        body { min-height: 100vh; background-color: var(--content-bg); }
        header { background-color: var(--white); padding: 15px 30px; box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo i { font-size: 2rem; color: var(--primary); }
        .logo h1 { font-size: 1.8rem; font-weight: 800; color: var(--primary); }
        nav { display: flex; align-items: center; gap: 15px; }
        nav a { color: var(--gray); text-decoration: none; font-weight: 500; padding: 8px 12px; border-radius: 6px; transition: all 0.3s ease; font-size: 0.95rem; position: relative; }
        nav a:hover { color: var(--primary); background-color: rgba(42, 82, 152, 0.05); }
        nav a.active { color: var(--primary); font-weight: 600; }
        nav a[href="profile.php"] { color: var(--primary); font-weight: 600; }
        nav a[href="profile.php"]::after { content: ''; position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%); width: 6px; height: 6px; background-color: var(--primary); border-radius: 50%; }
        .logout-btn { background-color: var(--primary); color: var(--white); border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        main { max-width: 600px; margin: 0 auto; padding: 30px 20px; }
        .page-header { margin-bottom: 30px; text-align: center; }
        .page-header h1 { font-size: 2.2rem; color: var(--dark); font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .page-header p { color: var(--gray); font-size: 1.1rem; }
        .back-link { color: var(--gray); text-decoration: none; font-weight: 500; margin-bottom: 20px; display: inline-block; transition: color 0.3s ease; }
        .back-link:hover { color: var(--primary); }
        .messages-container { margin-bottom: 25px; }
        .message { padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        .message.success { background-color: rgba(40, 167, 69, 0.1); border: 1px solid var(--success); color: #155724; }
        .message.error { background-color: rgba(220, 53, 69, 0.1); border: 1px solid var(--danger); color: #721c24; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .profile-info { background-color: rgba(42, 82, 152, 0.05); border-radius: 10px; padding: 25px; margin-bottom: 30px; border-left: 4px solid var(--primary); }
        .info-title { color: var(--primary); font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.1rem; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .info-item { display: flex; flex-direction: column; }
        .info-label { color: var(--gray); font-size: 0.9rem; margin-bottom: 5px; }
        .info-value { color: var(--dark); font-weight: 500; font-size: 1rem; }
        .profile-form { background-color: var(--card-bg); border-radius: 12px; padding: 40px; box-shadow: var(--shadow); border: 1px solid var(--gray-light); }
        @media (max-width: 768px) { .profile-form { padding: 30px; } header { flex-direction: column; gap: 15px; padding: 15px; } nav { flex-wrap: wrap; justify-content: center; } .logout-btn { width: 100%; text-align: center; } .info-grid { grid-template-columns: 1fr; } }
        @media (max-width: 480px) { .profile-form { padding: 25px; } main { padding: 20px 15px; } }
        .form-group { margin-bottom: 25px; }
        .form-group:last-child { margin-bottom: 0; }
        label { display: block; margin-bottom: 10px; color: var(--dark); font-weight: 500; font-size: 1rem; }
        .field-label { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .field-label i { color: var(--primary); font-size: 0.9rem; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 14px 16px; border: 1px solid var(--gray-light); border-radius: 8px; font-size: 1rem; color: var(--dark); background-color: var(--white); transition: all 0.3s ease; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1); }
        input:disabled { background-color: var(--gray-light); color: var(--gray); cursor: not-allowed; border-color: var(--gray-light); }
        .field-info { display: block; margin-top: 6px; color: var(--gray); font-size: 0.85rem; font-style: italic; }
        .submit-btn { background-color: var(--primary); color: var(--white); border: none; padding: 16px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }
        .submit-btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 6px 12px rgba(42, 82, 152, 0.2); }
        .divider { border-top: 1px dashed var(--gray-light); margin: 30px 0; position: relative; }
        .divider span { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; color: var(--gray); font-size: 0.9rem; }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-book-open"></i>
            <h1>Lireo</h1>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">Catalogue</a>
            <a href="history.php">Historique</a>
            <a href="alerts.php">Alertes</a>
            <a href="messages.php">Messages</a>
            <a href="profile.php" class="active">Profil</a>
            <a href="../logout.php" class="logout-btn">Déconnexion</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            <p>Gérez vos informations personnelles</p>
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour au dashboard</a>
        </div>

        <div class="messages-container">
            <?php foreach ($messages as $m): ?>
                <div class="message <?= strpos($m, '✅') !== false ? 'success' : 'error' ?>">
                    <i class="fas <?= strpos($m, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($m) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="profile-info">
            <div class="info-title">
                <i class="fas fa-info-circle"></i> Informations du compte
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Identifiant étudiant</span>
                    <span class="info-value"><?= htmlspecialchars($me['id_etudiant']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Statut du compte</span>
                    <span class="info-value">
                        <?= isset($me['compte_active']) && $me['compte_active'] == 1 ? 'Activé' : 'Non activé' ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date d'inscription</span>
                    <span class="info-value">
                        <?= !empty($me['date_inscription']) ? date('d/m/Y', strtotime($me['date_inscription'])) : 'N/A' ?>
                    </span>
                </div>
            </div>
        </div>

        <form method="post" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label class="field-label"><i class="fas fa-user"></i> Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($me['prenom']) ?>" required placeholder="Votre prénom">
            </div>

            <div class="form-group">
                <label class="field-label"><i class="fas fa-user"></i> Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($me['nom']) ?>" required placeholder="Votre nom de famille">
            </div>
            
            <div class="form-group">
                <label class="field-label"><i class="fas fa-envelope"></i> Email académique</label>
                <input type="email" value="<?= htmlspecialchars($me['email_academique']) ?>" disabled>
                <span class="field-info">L'adresse email académique ne peut pas être modifiée</span>
            </div>

            <div class="divider"><span>Sécurité</span></div>

            <div class="form-group">
                <label class="field-label"><i class="fas fa-lock"></i> Ancien mot de passe</label>
                <input type="password" name="old_password" autocomplete="current-password" placeholder="Requis uniquement si vous changez de mot de passe">
            </div>

            <div class="form-group">
                <label class="field-label"><i class="fas fa-key"></i> Nouveau mot de passe</label>
                <input type="password" name="new_password" autocomplete="new-password" minlength="6" placeholder="Minimum 6 caractères">
            </div>

            <div class="form-group">
                <label class="field-label"><i class="fas fa-key"></i> Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" autocomplete="new-password" minlength="6" placeholder="Retapez le nouveau mot de passe">
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> Sauvegarder les modifications
            </button>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss success messages
            const successMessages = document.querySelectorAll('.message.success');
            successMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-10px)';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            });
            
            // Client-side validation logic
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const prenom = document.querySelector('input[name="prenom"]').value.trim();
                const nom = document.querySelector('input[name="nom"]').value.trim();
                const oldPwd = document.querySelector('input[name="old_password"]').value;
                const newPwd = document.querySelector('input[name="new_password"]').value;
                const confirmPwd = document.querySelector('input[name="confirm_password"]').value;
                
                if (!prenom || !nom) {
                    e.preventDefault();
                    alert('Veuillez remplir votre nom et prénom.');
                    return false;
                }

                // If any password field is filled, check the others
                if (oldPwd || newPwd || confirmPwd) {
                    if (!oldPwd || !newPwd || !confirmPwd) {
                        e.preventDefault();
                        alert('Pour changer le mot de passe, les 3 champs sont requis.');
                        return false;
                    }
                    if (newPwd !== confirmPwd) {
                        e.preventDefault();
                        alert('Les nouveaux mots de passe ne correspondent pas.');
                        return false;
                    }
                    if (newPwd.length < 8) {
                        e.preventDefault();
                        alert('Le mot de passe doit faire au moins 8 caractères.');
                        return false;
                    }
                }
                
                // Loading state
                const submitBtn = document.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                submitBtn.style.opacity = '0.8';
                
                // Re-enable if submission is blocked/cancelled (failsafe)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.style.opacity = '1';
                }, 5000);
            });
        });
    </script>
</body>
</html> 