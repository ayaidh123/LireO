<?php
// ---------------------------------------------------------
// PARTIE LOGIQUE 
// ---------------------------------------------------------
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$success = false;
$message_success = '';

// Formulaire Étudiant (Activation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'student') {
    $email_academique = trim($_POST['email_academique'] ?? '');
    $pwd = $_POST['password'] ?? '';
    $pwd_confirm = $_POST['password_confirm'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9._-]+@umi\.ac\.ma$/', $email_academique)) {
        $errors[] = "Format de l'email invalide. Doit être @umi.ac.ma";
    }
    if (strlen($pwd) < 6) { $errors[] = "Le mot de passe doit contenir au moins 6 caractères."; }
    if ($pwd !== $pwd_confirm) { $errors[] = "Les mots de passe ne correspondent pas."; }

    if (!$errors) {
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $stmt_check = $pdo->prepare("SELECT id_etudiant, statut FROM etudiant WHERE email_academique = ? AND mot_de_passe IS NULL");
        $stmt_check->execute([$email_academique]);
        $etudiant = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($etudiant) {
            if ($etudiant['statut'] !== 'actif') {
                $errors[] = "Votre compte a été bloqué. Contactez l'administration.";
            } else {
                $stmt = $pdo->prepare("UPDATE etudiant SET mot_de_passe = ? WHERE id_etudiant = ?");
                try {
                    $stmt->execute([$hash, $etudiant['id_etudiant']]);
                    $success = true;
                    $message_success = "Compte activé avec succès!";
                } catch (PDOException $e) { $errors[] = "Erreur lors de l'activation."; }
            }
        } else { $errors[] = "Identifiant inconnu ou déjà activé."; }
    }
}

// Formulaire Admin (Inscription)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['role'] ?? '') === 'admin') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pwd = $_POST['password'] ?? '';
    $pwd_confirm = $_POST['password_confirm'] ?? '';

    // --- SÉCURITÉ : VERIFICATION STRICTE SANS INDICE VISUEL ---
    if (!preg_match('/^[a-zA-Z0-9._-]+@edu\.umi\.pf\.ac\.ma$/', $email)) {
        $errors[] = "Impossible"; // Message générique pour la sécurité
    }
    
    if (strlen($pwd) < 6) { $errors[] = "Mot de passe trop court."; }
    if ($pwd !== $pwd_confirm) { $errors[] = "Les mots de passe ne correspondent pas."; }

    if (!$errors) {
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admin (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$nom, $prenom, $email, $hash]);
            $success = true;
            $message_success = "Compte admin créé avec succès!";
        } catch (PDOException $e) { $errors[] = "Erreur: Cet email ne peut pas être utilisé."; }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Bibliothèque</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { height: 100vh; width: 100%; display: flex; background-color: #fff; overflow: hidden; }
        .register-section { width: 45%; display: flex; flex-direction: column; padding: 40px 50px; background: white; overflow-y: auto; position: relative; z-index: 10; box-shadow: 5px 0 25px rgba(0,0,0,0.05); }
        .back-btn { position: absolute; top: 25px; left: 25px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.9rem; padding: 8px 12px; border-radius: 8px; z-index: 20; }
        .image-section { width: 55%; position: relative; background-color: #1e3c72; background-image: url('assets/images/library-b.jpg'); background-size: cover; background-position: center; }
        .image-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(42, 82, 152, 0.4), rgba(30, 60, 114, 0.8)); }
        .image-text { position: absolute; bottom: 60px; left: 60px; color: white; z-index: 2; max-width: 80%; }
        .header { margin-bottom: 25px; margin-top: 50px; }
        .logo { display: flex; align-items: center; margin-bottom: 15px; color: #2a5298; }
        .logo i { font-size: 2rem; margin-right: 12px; }
        .logo h1 { font-size: 2rem; font-weight: 800; }
        .alert-box { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; border-color: #dc2626; color: #b91c1c; }
        .alert-success { background: #f0fdf4; border-color: #16a34a; color: #15803d; }
        .user-type { display: flex; gap: 15px; margin-bottom: 25px; }
        .user-option { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 12px; border-radius: 12px; cursor: pointer; border: 2px solid #e2e8f0; background-color: #f8fafc; transition: 0.2s; }
        .user-option.active { border-color: #2a5298; background-color: #eff6ff; color: #2a5298; }
        .form-content { display: none; animation: fadeIn 0.4s ease; }
        .form-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .form-group { margin-bottom: 18px; }
        .form-row { display: flex; gap: 15px; }
        .form-group label { display: block; margin-bottom: 6px; color: #334155; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        .input-wrapper { position: relative; }
        .input-wrapper input { width: 100%; padding: 12px 16px 12px 42px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none; }
        .input-wrapper i.input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .submit-btn { background-color: #2a5298; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background-color: #1e3c72; transform: translateY(-2px); }
        .login-link { text-align: center; margin-top: 20px; color: #64748b; }
        .login-link a { color: #2a5298; text-decoration: none; font-weight: 700; }
        @media (max-width: 900px) { .register-section { width: 100%; } .image-section { display: none; } body { overflow: auto; height: auto; } }
    </style>
</head>
<body>

<div class="register-section">
    <a href="index.php" class="back-btn"><i class="fas fa-home"></i> Accueil</a>

    <div class="header">
        <div class="logo"><i class="fas fa-book-open"></i><h1>Bibliothèque</h1></div>
        <h2>Inscription</h2>
        <p>Rejoignez notre communauté</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert-box alert-error">
            <?php foreach ($errors as $error): ?>
                <div><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message_success) ?></div>
        <a href="login.php" class="submit-btn">Se connecter</a>
    <?php else: ?>
        <div class="user-type">
            <div class="user-option active" id="tab-student" onclick="switchForm('student')"><i class="fas fa-user-graduate"></i> Étudiant</div>
            <div class="user-option" id="tab-admin" onclick="switchForm('admin')"><i class="fas fa-user-tie"></i> Admin</div>
        </div>

        <form id="form-student" class="form-content active" method="POST">
            <input type="hidden" name="role" value="student">
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <input type="email" name="email_academique" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password" name="password" minlength="6" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmation</label>
                <div class="input-wrapper">
                    <input type="password" name="password_confirm" minlength="6" required>
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>
            <button type="submit" class="submit-btn">ACTIVER COMPTE</button>
        </form>

        <form id="form-admin" class="form-content" method="POST">
            <input type="hidden" name="role" value="admin">
            <div class="form-row">
                <div class="form-group"><label>Prénom</label><div class="input-wrapper"><input type="text" name="prenom" required><i class="fas fa-user input-icon"></i></div></div>
                <div class="form-group"><label>Nom</label><div class="input-wrapper"><input type="text" name="nom" required><i class="fas fa-user input-icon"></i></div></div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <input type="email" name="email" required> <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Mot de passe</label><div class="input-wrapper"><input type="password" name="password" minlength="6" required><i class="fas fa-lock input-icon"></i></div></div>
                <div class="form-group"><label>Confirmer</label><div class="input-wrapper"><input type="password" name="password_confirm" minlength="6" required><i class="fas fa-lock input-icon"></i></div></div>
            </div>
            <button type="submit" class="submit-btn">CRÉER COMPTE ADMIN</button>
        </form>

        <div class="login-link">Déjà inscrit ? <a href="login.php">Connectez-vous ici →</a></div>
    <?php endif; ?>
</div>

<div class="image-section">
    <div class="image-overlay"></div>
    <div class="image-text">
        <h2>Rejoignez-nous</h2>
        <p>Accédez à votre espace bibliothèque.</p>
    </div>
</div>

<script>
    function switchForm(role) {
        document.querySelectorAll('.user-option').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + role).classList.add('active');
        document.querySelectorAll('.form-content').forEach(el => el.classList.remove('active'));
        document.getElementById('form-' + role).classList.add('active');
    }
    const validateForm = (e) => {
        const f = e.target;
        if (f.querySelector('input[name="password"]').value !== f.querySelector('input[name="password_confirm"]').value) {
            e.preventDefault();
            alert("Les mots de passe ne correspondent pas.");
        }
    };
    document.getElementById('form-student').addEventListener('submit', validateForm);
    document.getElementById('form-admin').addEventListener('submit', validateForm);
    <?php if (($_POST['role'] ?? '') === 'admin'): ?> switchForm('admin'); <?php endif; ?>
</script>
</body>
</html>