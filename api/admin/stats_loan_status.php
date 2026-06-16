<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

$sql = "
    SELECT 
        CASE 
            WHEN statut_emprunt = 'en_cours' THEN 'En cours'
            WHEN statut_emprunt = 'en_attente_retrait' THEN 'En attente retrait'
            WHEN statut_emprunt = 'retourne' THEN 'Retourné'
            WHEN statut_emprunt = 'annule' THEN 'Annulé'
            ELSE statut_emprunt
        END AS label,
        COUNT(*) AS total
    FROM emprunt
    GROUP BY statut_emprunt
";

try {
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>