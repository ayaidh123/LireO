<?php
// Fichier: includes/auth.php
// Système d'authentification complet

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Récupère un utilisateur par email et rôle
 */
function get_user_by_email_and_role(string $email, string $role) {
    global $pdo;

    if ($role === 'admin') {
        $sql = "SELECT id_admin AS user_id, email, mot_de_passe AS password_hash, 'admin' AS role, 
                       CONCAT(prenom, ' ', nom) AS name, prenom, nom
                FROM admin
                WHERE email = :email
                LIMIT 1";
    } else { // student
        $sql = "SELECT id_etudiant AS user_id, email_academique AS email, mot_de_passe AS password_hash, 
                       'student' AS role, CONCAT(prenom, ' ', nom) AS name, prenom, nom, statut
                FROM etudiant
                WHERE email_academique = :email
                LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
}

/**
 * Tentative de connexion
 */
function attempt_login(string $identifier, string $password, string $role = 'student'): bool {
    $identifier = trim($identifier);
    if ($identifier === '' || $password === '') return false;

    $user = get_user_by_email_and_role($identifier, $role);
    if (!$user) return false;

    // Vérifier que le compte étudiant est actif
    if ($role === 'student' && $user['statut'] !== 'actif') {
        return false;
    }

    // Vérifier que le compte est activé (mot de passe défini)
    if (empty($user['password_hash'])) return false;

    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['user_id'],
            'email' => $user['email'],
            'role' => $role,
            'name' => $user['name'] ?? null,
            'prenom' => $user['prenom'] ?? null,
            'nom' => $user['nom'] ?? null
        ];
        $_SESSION['logged_at'] = time();
        return true;
    }

    return false;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

/**
 * Retourne l'utilisateur courant
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Exige un rôle spécifique
 */
function require_role(string $role) {
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== $role) {
        logout();
        header('Location: /bib_full_project/public/login.php');
        exit;
    }
}

/**
 * Déconnexion
 */
function logout() {
    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
?>