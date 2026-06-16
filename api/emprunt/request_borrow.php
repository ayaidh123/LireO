<?php
// Fichier: api/emprunt/request_borrow.php - VERSION CORRIGÉE
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$isbn = $_POST['isbn'] ?? null;
$id_etudiant = $_SESSION['user']['id'];

if (!$isbn) {
    echo json_encode(['error' => 'ISBN manquant.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Vérifier la disponibilité RÉELLE avec verrouillage
    $stmt_check = $pdo->prepare("
        SELECT 
            l.nbre_de_copie_disponible, 
            l.titre,
            l.nbre_de_copie_total,
            (SELECT COUNT(*) FROM emprunt e2 
             WHERE e2.isbn = l.isbn 
             AND e2.date_retour_reel IS NULL 
             AND e2.statut_emprunt = 'en_cours') as emprunts_actifs
        FROM livre l 
        WHERE l.isbn = ?
        FOR UPDATE
    ");
    $stmt_check->execute([$isbn]);
    $livre = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$livre) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Livre introuvable.']);
        exit;
    }

    // Calculer les copies VÉRITABLEMENT disponibles
    $copies_reellement_disponibles = $livre['nbre_de_copie_total'] - $livre['emprunts_actifs'];
    
    // Vérifier limite d'emprunts (max 2)
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) FROM emprunt 
        WHERE id_etudiant = ? AND date_retour_reel IS NULL 
        AND statut_emprunt IN ('en_cours', 'en_attente_retrait')
    ");
    $stmt_count->execute([$id_etudiant]);
    $count_emprunts = $stmt_count->fetchColumn();

    if ($count_emprunts >= 2) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Vous avez déjà 2 emprunts en cours. Maximum autorisé.']);
        exit;
    }

    // Vérifier si l'étudiant a déjà emprunté ce livre
    $stmt_check_duplicate = $pdo->prepare("
        SELECT COUNT(*) FROM emprunt 
        WHERE id_etudiant = ? AND isbn = ? AND date_retour_reel IS NULL 
        AND statut_emprunt IN ('en_cours', 'en_attente_retrait')
    ");
    $stmt_check_duplicate->execute([$id_etudiant, $isbn]);
    if ($stmt_check_duplicate->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Vous avez déjà emprunté ce livre.']);
        exit;
    }

    if ($copies_reellement_disponibles > 0) {
        // Créer l'emprunt avec statut d'attente de retrait
        $date_retour = date('Y-m-d', strtotime('+14 days'));
        $date_limite_retrait = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt_borrow = $pdo->prepare("
            INSERT INTO emprunt (id_etudiant, isbn, date_emprunt, date_retour_prevue, statut_emprunt)
            VALUES (?, ?, NOW(), ?, 'en_attente_retrait')
        ");
        $stmt_borrow->execute([$id_etudiant, $isbn, $date_retour]);

        $id_emprunt = $pdo->lastInsertId();

        // Ajouter à la table d'attente de retrait
        $stmt_attente = $pdo->prepare("
            INSERT INTO emprunt_attente_retrait (id_emprunt, date_limite_retrait)
            VALUES (?, ?)
        ");
        $stmt_attente->execute([$id_emprunt, $date_limite_retrait]);

        $pdo->commit();
        
        echo json_encode([
            'status' => 'en_attente_retrait', 
            'message' => 'Emprunt en attente de retrait physique. Vous avez 24h pour retirer le livre à la bibliothèque.',
            'date_limite' => $date_limite_retrait
        ]);
        
    } else {
        // Livre indisponible - proposer la réservation
        $stmt_reserve = $pdo->prepare("
            INSERT INTO reserver (id_etudiant, isbn, date_reservation, statut_reservation)
            VALUES (?, ?, NOW(), 'en_attente')
            ON DUPLICATE KEY UPDATE date_reservation = NOW()
        ");
        $stmt_reserve->execute([$id_etudiant, $isbn]);

        $pdo->commit();
        
        echo json_encode([
            'status' => 'queued', 
            'message' => 'Livre actuellement indisponible. Vous avez été ajouté à la file d\'attente.'
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur request_borrow: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur système. Veuillez réessayer.']);
}
?>