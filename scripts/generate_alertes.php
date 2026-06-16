<?php
// Fichier: scripts/generate_alertes.php
// À exécuter quotidiennement via cron ou manuellement
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->beginTransaction();
    
    echo "=== GÉNÉRATION DES ALERTES - " . date('Y-m-d H:i:s') . " ===\n";
    
    $alertes_generes = 0;
    
    // 1. Alertes pour retards de livres
    $stmt_retards = $pdo->prepare("
        SELECT 
            e.id_emprunt,
            e.id_etudiant,
            et.nom,
            et.prenom,
            et.email_academique,
            l.titre,
            l.isbn,
            DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
        FROM emprunt e
        JOIN etudiant et ON e.id_etudiant = et.id_etudiant
        JOIN livre l ON e.isbn = l.isbn
        WHERE e.date_retour_reel IS NULL 
        AND e.statut_emprunt = 'en_cours'
        AND e.date_retour_prevue < CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM alerte a 
            WHERE a.id_etudiant = e.id_etudiant 
            AND a.type_alerte = 'retard' 
            AND a.message LIKE CONCAT('%', l.isbn, '%')
            AND DATE(a.date_envoi) = CURDATE()
        )
    ");
    $stmt_retards->execute();
    $retards = $stmt_retards->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($retards as $retard) {
        $message = "📚 RETARD - Livre: {$retard['titre']} \n";
        $message .= "Vous avez {$retard['jours_retard']} jour(s) de retard. \n";
        $message .= "Veuillez retourner le livre au plus vite pour éviter des sanctions.";
        
        $stmt_alerte = $pdo->prepare("
            INSERT INTO alerte (id_etudiant, type_alerte, message, date_envoi, etat)
            VALUES (?, 'retard', ?, NOW(), 'non_lu')
        ");
        $stmt_alerte->execute([$retard['id_etudiant'], $message]);
        $alertes_generes++;
        
        echo "Alerte retard pour: {$retard['prenom']} {$retard['nom']} - {$retard['titre']}\n";
    }
    
    // 2. Alertes pour rappel de réservation (livre disponible)
    $stmt_rappels = $pdo->prepare("
        SELECT 
            r.id_reservation,
            r.id_etudiant,
            et.nom,
            et.prenom,
            l.titre,
            l.isbn
        FROM reserver r
        JOIN etudiant et ON r.id_etudiant = et.id_etudiant
        JOIN livre l ON r.isbn = l.isbn
        WHERE r.statut_reservation = 'en_attente'
        AND l.nbre_de_copie_disponible > 0
        AND NOT EXISTS (
            SELECT 1 FROM alerte a 
            WHERE a.id_etudiant = r.id_etudiant 
            AND a.type_alerte = 'rappel_reservation' 
            AND a.message LIKE CONCAT('%', l.isbn, '%')
            AND DATE(a.date_envoi) = CURDATE()
        )
    ");
    $stmt_rappels->execute();
    $rappels = $stmt_rappels->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rappels as $rappel) {
        $message = "📖 RAPPEL - Livre disponible: {$rappel['titre']} \n";
        $message .= "Le livre que vous avez réservé est maintenant disponible. \n";
        $message .= "Vous avez 24h pour venir le retirer à la bibliothèque.";
        
        $stmt_alerte = $pdo->prepare("
            INSERT INTO alerte (id_etudiant, type_alerte, message, date_envoi, etat)
            VALUES (?, 'rappel_reservation', ?, NOW(), 'non_lu')
        ");
        $stmt_alerte->execute([$rappel['id_etudiant'], $message]);
        $alertes_generes++;
        
        echo "Rappel réservation pour: {$rappel['prenom']} {$rappel['nom']} - {$rappel['titre']}\n";
    }
    
    // 3. Alertes pour disponibilité de livres (file d'attente)
    $stmt_disponibilite = $pdo->prepare("
        SELECT DISTINCT
            r.id_etudiant,
            et.nom,
            et.prenom,
            l.titre,
            l.isbn
        FROM reserver r
        JOIN etudiant et ON r.id_etudiant = et.id_etudiant
        JOIN livre l ON r.isbn = l.isbn
        WHERE r.statut_reservation = 'en_attente'
        AND l.nbre_de_copie_disponible > 0
        AND r.id_reservation = (
            SELECT MIN(r2.id_reservation) 
            FROM reserver r2 
            WHERE r2.isbn = l.isbn 
            AND r2.statut_reservation = 'en_attente'
        )
        AND NOT EXISTS (
            SELECT 1 FROM alerte a 
            WHERE a.id_etudiant = r.id_etudiant 
            AND a.type_alerte = 'disponibilite_livre' 
            AND a.message LIKE CONCAT('%', l.isbn, '%')
            AND DATE(a.date_envoi) = CURDATE()
        )
    ");
    $stmt_disponibilite->execute();
    $disponibilites = $stmt_disponibilite->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($disponibilites as $dispo) {
        $message = "🎉 DISPONIBLE - Livre: {$dispo['titre']} \n";
        $message .= "Le livre que vous attendiez est maintenant disponible. \n";
        $message .= "Vous êtes le premier dans la file d'attente.";
        
        $stmt_alerte = $pdo->prepare("
            INSERT INTO alerte (id_etudiant, type_alerte, message, date_envoi, etat)
            VALUES (?, 'disponibilite_livre', ?, NOW(), 'non_lu')
        ");
        $stmt_alerte->execute([$dispo['id_etudiant'], $message]);
        $alertes_generes++;
        
        echo "Alerte disponibilité pour: {$dispo['prenom']} {$dispo['nom']} - {$dispo['titre']}\n";
    }
    
    $pdo->commit();
    echo "=== {$alertes_generes} ALERTES GÉNÉRÉES AVEC SUCCÈS ===\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>