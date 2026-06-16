<?php
// Fichier: api/messages/mark_read.php (COMPLET)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Non authentifié']);
    exit;
}

$user = $_SESSION['user'];
$id_message = $_POST['id_message'] ?? null;

if (!$id_message) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'ID message manquant']);
    exit;
}

try {
    if ($user['role'] === 'admin') {
        // Un admin marque un message comme traité (uniquement s'il est le récepteur)
        
        $stmt = $pdo->prepare("
            UPDATE message 
            SET statut = 'traite', 
                date_traitement = NOW()
            WHERE id_message = ? 
            AND (id_admin_recepteur = ? OR id_admin_recepteur IS NULL)
        ");
        
        $stmt->execute([$id_message, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Message marqué comme traité'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'error' => 'Message non trouvé, déjà traité ou non destiné à cet administrateur.'
            ]);
        }
        
    } else {
        // Les étudiants ne peuvent pas marquer leurs propres messages
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'error' => 'Action non autorisée'
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur mark_read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Erreur lors de la mise à jour du message'
    ]);
}
?>