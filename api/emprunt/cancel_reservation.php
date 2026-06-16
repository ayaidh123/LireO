<?php
// Fichier: api/emprunt/cancel_reservation.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$id_reservation = $_POST['id_reservation'] ?? null;
$id_etudiant = $_SESSION['user']['id'];

if (!$id_reservation) {
    echo json_encode(['error' => 'ID réservation manquant.']);
    exit;
}

try {
    // Vérifier que la réservation appartient à l'étudiant
    $stmt_check = $pdo->prepare("
        SELECT r.id_reservation, l.titre, l.isbn
        FROM reserver r
        JOIN livre l ON r.isbn = l.isbn
        WHERE r.id_reservation = ? AND r.id_etudiant = ? AND r.statut_reservation = 'en_attente'
    ");
    $stmt_check->execute([$id_reservation, $id_etudiant]);
    $reservation = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(['error' => 'Réservation non trouvée ou déjà traitée.']);
        exit;
    }

    // Annuler la réservation
    $stmt_cancel = $pdo->prepare("DELETE FROM reserver WHERE id_reservation = ?");
    $stmt_cancel->execute([$id_reservation]);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Réservation annulée avec succès.'
    ]);

} catch (PDOException $e) {
    error_log("Erreur cancel_reservation: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur BD: ' . $e->getMessage()]);
}
?>