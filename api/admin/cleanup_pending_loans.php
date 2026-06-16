<?php
// Fichier: api/admin/cleanup_pending_loans.php
// Script pour nettoyer les emprunts en attente quand le livre n'est plus disponible
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    // Trouver les emprunts en attente de retrait qui ne peuvent plus être honorés
    $stmt = $pdo->prepare("
        SELECT 
            e.id_emprunt,
            e.isbn,
            et.id_etudiant,
            et.prenom,
            et.nom,
            l.titre,
            (SELECT COUNT(*) FROM emprunt e2 
             WHERE e2.isbn = e.isbn 
             AND e2.date_retour_reel IS NULL 
             AND e2.statut_emprunt = 'en_cours') as emprunts_actuels,
            l.nbre_de_copie_total
        FROM emprunt e
        JOIN etudiant et ON e.id_etudiant = et.id_etudiant
        JOIN livre l ON e.isbn = l.isbn
        JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
        WHERE e.statut_emprunt = 'en_attente_retrait'
        AND ear.statut = 'en_attente'
        AND (SELECT COUNT(*) FROM emprunt e2 
             WHERE e2.isbn = e.isbn 
             AND e2.date_retour_reel IS NULL 
             AND e2.statut_emprunt = 'en_cours') >= l.nbre_de_copie_total
    ");
    
    $stmt->execute();
    $emprunts_a_annuler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultats = [];
    
    foreach ($emprunts_a_annuler as $emprunt) {
        // Annuler l'emprunt
        $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'annule' WHERE id_emprunt = ?")->execute([$emprunt['id_emprunt']]);
        $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'expire' WHERE id_emprunt = ?")->execute([$emprunt['id_emprunt']]);
        
        $resultats[] = [
            'id_emprunt' => $emprunt['id_emprunt'],
            'etudiant' => $emprunt['prenom'] . ' ' . $emprunt['nom'],
            'livre' => $emprunt['titre'],
            'action' => 'annulé'
        ];
    }

    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => count($resultats) . ' emprunts en attente annulés',
        'details' => $resultats
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erreur cleanup_pending_loans: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>