<?php
// Fichier: api/admin/check_availability.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

$id_emprunt = $_GET['id_emprunt'] ?? null;

if (!$id_emprunt) {
    echo json_encode(['status' => 'error', 'message' => 'ID manquant']);
    exit;
}

try {
    // Vérifier la disponibilité pour cet emprunt
    $stmt = $pdo->prepare("
        SELECT 
            e.isbn,
            l.titre,
            l.nbre_de_copie_total,
            (SELECT COUNT(*) FROM emprunt e2 
             WHERE e2.isbn = e.isbn 
             AND e2.date_retour_reel IS NULL 
             AND e2.statut_emprunt = 'en_cours') as emprunts_actuels
        FROM emprunt e
        JOIN livre l ON e.isbn = l.isbn
        WHERE e.id_emprunt = ?
    ");
    
    $stmt->execute([$id_emprunt]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Emprunt non trouvé']);
        exit;
    }

    $copies_disponibles = $data['nbre_de_copie_total'] - $data['emprunts_actuels'];
    
    if ($copies_disponibles > 0) {
        echo json_encode([
            'status' => 'available',
            'message' => 'Copie disponible. ' . $copies_disponibles . ' copie(s) restante(s).',
            'copies_restantes' => $copies_disponibles
        ]);
    } else {
        echo json_encode([
            'status' => 'unavailable',
            'message' => 'Plus de copies disponibles. Toutes les copies sont déjà empruntées.',
            'copies_restantes' => 0
        ]);
    }

} catch (Exception $e) {
    error_log("Erreur check_availability: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>