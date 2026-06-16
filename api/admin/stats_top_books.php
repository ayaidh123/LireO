<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_role('admin');

$sql = "
    SELECT 
        l.titre AS label, 
        COUNT(e.id_emprunt) AS total
    FROM livre l
    LEFT JOIN emprunt e ON l.id_livre = e.id_livre
    GROUP BY l.id_livre, l.titre
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 5
";

try {
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si aucun livre n'a été emprunté
    if (empty($data)) {
        $data = [['label' => 'Aucun emprunt', 'total' => 0]];
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>