<?php
// Fichier: api/emprunt/cancel_borrow.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$id_emprunt = $_POST['id_emprunt'] ?? null;
$id_etudiant = $_SESSION['user']['id'];

if (!$id_emprunt) {
    echo json_encode(['status' => 'error', 'message' => 'ID emprunt manquant.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Vérifier que l'emprunt appartient à l'étudiant et est en attente
    $stmt_check = $pdo->prepare("
        SELECT e.id_emprunt, e.statut_emprunt, l.titre, e.isbn
        FROM emprunt e
        JOIN livre l ON e.isbn = l.isbn
        WHERE e.id_emprunt = ? AND e.id_etudiant = ? 
        AND e.statut_emprunt = 'en_attente_retrait'
        AND e.date_retour_reel IS NULL
    ");
    $stmt_check->execute([$id_emprunt, $id_etudiant]);
    $emprunt = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$emprunt) {
        throw new Exception('Emprunt non trouvé, déjà traité ou ne vous appartient pas.');
    }

    // Supprimer d'abord la table d'attente de retrait
    $stmt_delete_attente = $pdo->prepare("DELETE FROM emprunt_attente_retrait WHERE id_emprunt = ?");
    $stmt_delete_attente->execute([$id_emprunt]);
    
    // Puis supprimer l'emprunt
    $stmt_delete_emprunt = $pdo->prepare("DELETE FROM emprunt WHERE id_emprunt = ?");
    $stmt_delete_emprunt->execute([$id_emprunt]);

    $pdo->commit();
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Demande d\'emprunt annulée avec succès.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur cancel_borrow: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>