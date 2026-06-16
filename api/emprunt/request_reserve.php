<?php
// Fichier: api/emprunt/request_reserve.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$isbn = $_POST['isbn'] ?? null;
$id_etudiant = $_SESSION['user']['id'];

if (!$isbn) {
    echo json_encode(['error' => 'ISBN manquant.']);
    exit;
}

try {
    // Vérifier si l'étudiant a déjà réservé ce livre
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) FROM reserver 
        WHERE id_etudiant = ? AND isbn = ? AND statut_reservation = 'en_attente'
    ");
    $stmt_check->execute([$id_etudiant, $isbn]);
    
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['error' => 'Vous avez déjà réservé ce livre.']);
        exit;
    }

    // Vérifier si l'étudiant a déjà emprunté ce livre
    $stmt_check_emprunt = $pdo->prepare("
        SELECT COUNT(*) FROM emprunt 
        WHERE id_etudiant = ? AND isbn = ? AND date_retour_reel IS NULL
    ");
    $stmt_check_emprunt->execute([$id_etudiant, $isbn]);
    
    if ($stmt_check_emprunt->fetchColumn() > 0) {
        echo json_encode(['error' => 'Vous avez déjà emprunté ce livre.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO reserver (id_etudiant, isbn, date_reservation, statut_reservation)
        VALUES (?, ?, NOW(), 'en_attente')
    ");
    $stmt->execute([$id_etudiant, $isbn]);

    echo json_encode(['status' => 'reserved', 'message' => 'Livre réservé avec succès.']);

} catch (PDOException $e) {
    error_log("Erreur request_reserve: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur BD: ' . $e->getMessage()]);
}
?>