<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

$sql = "
    SELECT 
        DATE_FORMAT(date_emprunt, '%Y-%m') AS label,
        COUNT(*) AS total
    FROM emprunt
    WHERE date_emprunt >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(date_emprunt, '%Y-%m')
    ORDER BY label ASC
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
