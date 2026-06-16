<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

try {
    $reservations = $pdo->query("SELECT COUNT(*) FROM reserver")->fetchColumn();
    $emprunts = $pdo->query("SELECT COUNT(*) FROM emprunt")->fetchColumn();

    $data = [
        ['label' => 'Réservations', 'total' => (int)$reservations],
        ['label' => 'Emprunts', 'total' => (int)$emprunts]
    ];

    header('Content-Type: application/json');
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>