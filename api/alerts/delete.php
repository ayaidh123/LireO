<?php
// Fichier: api/alerts/delete.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$user = $_SESSION['user'];
$id_alerte = $_POST['id_alerte'] ?? null;

if (!$id_alerte) {
    echo json_encode(['status' => 'error', 'message' => 'ID alerte manquant.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        DELETE FROM alerte 
        WHERE id_alerte = ? AND id_etudiant = ?
    ");
    $stmt->execute([$id_alerte, $user['id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Alerte supprimée.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Alerte non trouvée.']);
    }

} catch (PDOException $e) {
    error_log("Erreur delete alerte: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur BD: ' . $e->getMessage()]);
}
?>