<?php
// Fichier: api/emprunt/request_extension.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$id_emprunt = $_POST['id_emprunt'] ?? null;
$id_etudiant = $_SESSION['user']['id'];

if (!$id_emprunt) {
    echo json_encode(['error' => 'ID emprunt manquant.']);
    exit;
}

try {
    // Vérifier que l'emprunt appartient à l'étudiant
    $stmt_check = $pdo->prepare("
        SELECT e.id_emprunt, e.date_retour_prevue, l.titre
        FROM emprunt e
        JOIN livre l ON e.isbn = l.isbn
        WHERE e.id_emprunt = ? AND e.id_etudiant = ? AND e.date_retour_reel IS NULL
    ");
    $stmt_check->execute([$id_emprunt, $id_etudiant]);
    $emprunt = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$emprunt) {
        echo json_encode(['error' => 'Emprunt non trouvé ou déjà retourné.']);
        exit;
    }

    // Vérifier s'il n'y a pas déjà une demande en attente
    $stmt_check_pending = $pdo->prepare("
        SELECT COUNT(*) FROM prolongation 
        WHERE emprunt_id = ? AND statut = 'en_attente'
    ");
    $stmt_check_pending->execute([$id_emprunt]);
    
    if ($stmt_check_pending->fetchColumn() > 0) {
        echo json_encode(['error' => 'Une demande de prolongation est déjà en attente pour cet emprunt.']);
        exit;
    }

    // Calculer la nouvelle date (7 jours supplémentaires)
    $nouvelle_date = date('Y-m-d', strtotime($emprunt['date_retour_prevue'] . ' +7 days'));

    $stmt = $pdo->prepare("
        INSERT INTO prolongation (emprunt_id, date_demande, nouvelle_date_prevue, statut)
        VALUES (?, NOW(), ?, 'en_attente')
    ");
    $stmt->execute([$id_emprunt, $nouvelle_date]);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Demande de prolongation envoyée. Nouvelle date proposée: ' . date('d/m/Y', strtotime($nouvelle_date))
    ]);

} catch (PDOException $e) {
    error_log("Erreur request_extension: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur BD: ' . $e->getMessage()]);
}
?>