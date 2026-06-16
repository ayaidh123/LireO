<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

try {
    $retards = $pdo->query("
        SELECT COUNT(*) 
        FROM emprunt
        WHERE date_retour_prevue < CURDATE()
        AND date_retour_reel IS NULL
    ")->fetchColumn();

    $sanctions = $pdo->query("
        SELECT COUNT(*) 
        FROM sanction
        WHERE statut = 'active'
    ")->fetchColumn();

    $data = [
        ['label' => 'Retards', 'total' => (int)$retards],
        ['label' => 'Sanctions', 'total' => (int)$sanctions]
    ];

    header('Content-Type: application/json');
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>