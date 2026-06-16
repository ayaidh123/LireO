<?php
// Fichier: api/messages/send_message.php (COMPLET et CORRIGÉ)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php'; // Assurez-vous que ce fichier gère la session et l'objet $user.

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Accès non autorisé ou mauvaise méthode.']);
    exit;
}

$user = $_SESSION['user'];
$sujet = trim($_POST['sujet'] ?? '');
$contenu = trim($_POST['contenu'] ?? '');
// Pour l'Admin
$id_etudiant_recepteur = $_POST['id_etudiant_recepteur'] ?? null; 
// Pour l'Étudiant
$id_admin_recepteur = $_POST['id_admin_recepteur'] ?? null; 


// Validation de base
if (empty($sujet) || empty($contenu)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Le sujet et le contenu sont obligatoires.']);
    exit;
}

try {
    if ($user['role'] === 'student') {
        // Logique : Étudiant envoie à Admin
        
        $stmt = $pdo->prepare("
            INSERT INTO message (
                id_etudiant_expediteur,
                id_admin_recepteur,
                sujet,
                contenu,
                date_envoi,
                statut
            ) VALUES (?, ?, ?, ?, NOW(), 'non_lu')
        ");
        
        // id_admin_recepteur peut être NULL si vous permettez d'envoyer à tous les admins
        $stmt->execute([
            $user['id'],
            $id_admin_recepteur, 
            $sujet,
            $contenu
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Message envoyé avec succès à l\'administrateur'
        ]);
        
    } else if ($user['role'] === 'admin') {
        // Logique : ADMIN envoie à Étudiant
        if (empty($id_etudiant_recepteur)) {
             http_response_code(400);
             echo json_encode(['status' => 'error', 'error' => 'L\'ID de l\'étudiant destinataire est requis.']);
             exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO message (
                id_admin_expediteur,
                id_etudiant_recepteur,
                sujet,
                contenu,
                date_envoi,
                statut
            ) VALUES (?, ?, ?, ?, NOW(), 'non_lu')
        ");
        
        $stmt->execute([
            $user['id'], // id_admin
            $id_etudiant_recepteur,
            $sujet,
            $contenu
        ]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Message envoyé avec succès à l\'étudiant.',
            'id_message' => $pdo->lastInsertId()
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur send_message: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Erreur lors de l\'envoi du message'
    ]);
}
?>