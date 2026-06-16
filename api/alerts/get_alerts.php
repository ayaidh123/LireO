<?php
// Fichier: api/alerts/get_alerts.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$user = $_SESSION['user'];
$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM alerte 
        WHERE id_etudiant = ?
        ORDER BY date_envoi DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], (int)$limit, (int)$offset]);
    $alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le total
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM alerte WHERE id_etudiant = ?");
    $stmt_count->execute([$user['id']]);
    $total = $stmt_count->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data' => $alertes,
        'total' => $total,
        'unread_count' => count(array_filter($alertes, function($a) { return $a['etat'] === 'non_lu'; }))
    ]);

} catch (PDOException $e) {
    error_log("Erreur get_alerts: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur BD: ' . $e->getMessage()]);
}
?>