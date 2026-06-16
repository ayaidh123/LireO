<?php
// Fichier: scripts/cleanup_automatic.php
// À exécuter via cron toutes les heures
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();
    
    echo "=== NETTOYAGE AUTOMATIQUE DES EMPRUNTS - " . date('Y-m-d H:i:s') . " ===\n";
    
    // 1. Nettoyer les retraits expirés
    $stmt_expired = $pdo->prepare("
        SELECT ear.id_emprunt 
        FROM emprunt_attente_retrait ear
        WHERE ear.statut = 'en_attente' 
        AND ear.date_limite_retrait < NOW()
    ");
    $stmt_expired->execute();
    $expired = $stmt_expired->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($expired) > 0) {
        $placeholders = str_repeat('?,', count($expired) - 1) . '?';
        $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'annule' WHERE id_emprunt IN ($placeholders)")->execute($expired);
        $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'expire' WHERE id_emprunt IN ($placeholders)")->execute($expired);
        echo "✓ " . count($expired) . " retraits expirés nettoyés\n";
    }
    
    // 2. Nettoyer les emprunts en attente sans copies disponibles
    $stmt_no_copies = $pdo->prepare("
        SELECT e.id_emprunt
        FROM emprunt e
        JOIN livre l ON e.isbn = l.isbn
        JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
        WHERE e.statut_emprunt = 'en_attente_retrait'
        AND ear.statut = 'en_attente'
        AND (SELECT COUNT(*) FROM emprunt e2 
             WHERE e2.isbn = e.isbn 
             AND e2.date_retour_reel IS NULL 
             AND e2.statut_emprunt = 'en_cours') >= l.nbre_de_copie_total
    ");
    $stmt_no_copies->execute();
    $no_copies = $stmt_no_copies->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($no_copies) > 0) {
        $placeholders = str_repeat('?,', count($no_copies) - 1) . '?';
        $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'annule' WHERE id_emprunt IN ($placeholders)")->execute($no_copies);
        $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'expire' WHERE id_emprunt IN ($placeholders)")->execute($no_copies);
        echo "✓ " . count($no_copies) . " emprunts sans copies nettoyés\n";
    }
    
    $pdo->commit();
    echo "=== NETTOYAGE TERMINÉ ===\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>