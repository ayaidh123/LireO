<?php
// Fichier: api/messages/get_stats.php (COMPLET et CORRIGÉ)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Non authentifié']);
    exit;
}

$user = $_SESSION['user'];

try {
    if ($user['role'] === 'student') {
        // Logique Étudiant
        // Tous les messages envoyés
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM message WHERE id_etudiant_expediteur = ?");
        $stmt->execute([$user['id']]);
        $total_sent = $stmt->fetchColumn();
        
        // Messages non lus (non traités)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM message WHERE id_etudiant_expediteur = ? AND statut = 'non_lu'");
        $stmt->execute([$user['id']]);
        $unread = $stmt->fetchColumn();
        
        // Messages traités
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM message WHERE id_etudiant_expediteur = ? AND statut IN ('en_cours', 'traite')");
        $stmt->execute([$user['id']]);
        $treated = $stmt->fetchColumn();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_sent' => (int)$total_sent,
                'unread' => (int)$unread,
                'treated' => (int)$treated,
            ]
        ]);
        
    } else if ($user['role'] === 'admin') {
        $id_admin = $user['id'];
        
        // 1. Messages REÇUS (Admin est récepteur)
        $stmt_received = $pdo->prepare("
            SELECT COUNT(*) FROM message 
            WHERE id_admin_recepteur = ? OR id_admin_recepteur IS NULL
        ");
        $stmt_received->execute([$id_admin]);
        $total_received = $stmt_received->fetchColumn();
        
        // 2. Messages NON LUS (Reçus par l'Admin, statut 'non_lu')
        $stmt_unread = $pdo->prepare("
            SELECT COUNT(*) FROM message 
            WHERE (id_admin_recepteur = ? OR id_admin_recepteur IS NULL) 
            AND statut = 'non_lu'
        ");
        $stmt_unread->execute([$id_admin]);
        $unread = $stmt_unread->fetchColumn();
        
        // 3. Messages ENVOYÉS (Admin est expéditeur) - NOUVEAU
        $stmt_sent = $pdo->prepare("
            SELECT COUNT(*) FROM message 
            WHERE id_admin_expediteur = ?
        ");
        $stmt_sent->execute([$id_admin]);
        $total_sent = $stmt_sent->fetchColumn();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_received' => (int)$total_received,
                'unread' => (int)$unread,
                'total_sent' => (int)$total_sent,
            ]
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur get_stats: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Erreur lors de la récupération des statistiques'
    ]);
}
?>