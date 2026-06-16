<?php
// Fichier: api/messages/get_messages.php (COMPLET et CORRIGÉ)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Non authentifié']);
    exit;
}

$user = $_SESSION['user'];
$type = $_GET['type'] ?? 'received';

try {
    if ($user['role'] === 'student') {
        // Logique Étudiant (Messages envoyés par l'étudiant)
        $stmt = $pdo->prepare("
            SELECT 
                m.id_message,
                m.sujet,
                m.contenu,
                m.date_envoi,
                m.statut,
                m.date_traitement,
                a.nom as admin_nom,
                a.prenom as admin_prenom,
                a.email as admin_email
            FROM message m
            LEFT JOIN admin a ON m.id_admin_recepteur = a.id_admin
            WHERE m.id_etudiant_expediteur = ?
            ORDER BY m.date_envoi DESC
        ");
        $stmt->execute([$user['id']]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $messages,
            'type' => 'sent' 
        ]);
        
    } else if ($user['role'] === 'admin') {
        $id_admin = $user['id'];
        
        if ($type === 'received') {
            // Messages Reçus : Admin est le récepteur, Étudiant est l'expéditeur
            $sql = "
                SELECT 
                    m.*,
                    e.nom as etudiant_nom,
                    e.prenom as etudiant_prenom,
                    e.email_academique as etudiant_email,
                    e.matricule as etudiant_matricule
                FROM message m
                JOIN etudiant e ON m.id_etudiant_expediteur = e.id_etudiant
                WHERE m.id_admin_recepteur = :id_admin OR m.id_admin_recepteur IS NULL
                ORDER BY m.date_envoi DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_admin', $id_admin, PDO::PARAM_INT);
            
        } else if ($type === 'sent') {
            // Messages Envoyés : Admin est l'expéditeur, Étudiant est le récepteur
            $sql = "
                SELECT 
                    m.*,
                    m.id_etudiant_recepteur as id_etudiant_expediteur,  -- CORRECTION CLÉ : Ajout de l'alias pour la fonction JS
                    e.nom as etudiant_nom,
                    e.prenom as etudiant_prenom,
                    e.email_academique as etudiant_email,
                    e.matricule as etudiant_matricule
                FROM message m
                JOIN etudiant e ON m.id_etudiant_recepteur = e.id_etudiant
                WHERE m.id_admin_expediteur = :id_admin
                ORDER BY m.date_envoi DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_admin', $id_admin, PDO::PARAM_INT);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'error' => 'Type de message invalide.']);
            exit;
        }
        
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $messages
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur get_messages: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Erreur lors de la récupération des messages'
    ]);
}
?>