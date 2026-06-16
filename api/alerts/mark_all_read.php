<?php
// Fichier: api/alerts/mark_all_read.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$user = $_SESSION['user'];

try {
    $stmt = $pdo->prepare("
        UPDATE alerte 
        SET etat = 'lu' 
        WHERE id_etudiant = ? AND etat = 'non_lu'
    ");
    $stmt->execute([$user['id']]);

    $updated = $stmt->rowCount();
    
    echo json_encode([
        'status' => 'success', 
        'message' => "{$updated} alerte(s) marquée(s) comme lue(s)."
    ]);

} catch (PDOException $e) {
    error_log("Erreur mark_all_read: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur BD: ' . $e->getMessage()]);
}
?>