<?php
// Fichier: scripts/send_notifications.php
// Script pour envoyer des notifications (à exécuter via cron)
require_once __DIR__ . '/../includes/db.php';

try {
    echo "=== ENVOI DES NOTIFICATIONS - " . date('Y-m-d H:i:s') . " ===\n";
    
    $notifications_envoyees = 0;
    
    // 1. Notifications pour retards imminents (2 jours avant)
    $stmt_retards_imminents = $pdo->prepare("
        SELECT 
            e.id_emprunt,
            e.id_etudiant,
            et.email_academique,
            et.prenom,
            l.titre,
            e.date_retour_prevue,
            DATEDIFF(e.date_retour_prevue, CURDATE()) as jours_restants
        FROM emprunt e
        JOIN etudiant et ON e.id_etudiant = et.id_etudiant
        JOIN livre l ON e.isbn = l.isbn
        WHERE e.date_retour_reel IS NULL 
        AND e.statut_emprunt = 'en_cours'
        AND DATEDIFF(e.date_retour_prevue, CURDATE()) = 2
        AND NOT EXISTS (
            SELECT 1 FROM alerte a 
            WHERE a.id_etudiant = e.id_etudiant 
            AND a.type_alerte = 'information' 
            AND a.message LIKE CONCAT('%Rappel: ', l.titre, '%')
            AND DATE(a.date_envoi) = CURDATE()
        )
    ");
    $stmt_retards_imminents->execute();
    $retards_imminents = $stmt_retards_imminents->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($retards_imminents as $rappel) {
        $message = "📚 RAPPEL - Livre: {$rappel['titre']} \n";
        $message .= "Date de retour: " . date('d/m/Y', strtotime($rappel['date_retour_prevue'])) . " \n";
        $message .= "Pensez à retourner le livre dans les 2 jours pour éviter les retards.";
        
        $stmt_alerte = $pdo->prepare("
            INSERT INTO alerte (id_etudiant, type_alerte, message, date_envoi, etat)
            VALUES (?, 'information', ?, NOW(), 'non_lu')
        ");
        $stmt_alerte->execute([$rappel['id_etudiant'], $message]);
        $notifications_envoyees++;
        
        echo "Rappel envoyé à: {$rappel['prenom']} - {$rappel['titre']}\n";
    }
    
    // 2. Notifications pour nouvelles fonctionnalités (1 fois par mois)
    if (date('j') === '1') { // Le premier du mois
        $stmt_etudiants = $pdo->query("SELECT id_etudiant FROM etudiant WHERE statut = 'actif'");
        $etudiants = $stmt_etudiants->fetchAll(PDO::FETCH_COLUMN);
        
        $message_mensuel = "🎉 NOUVEAUTÉS DU MOIS \n";
        $message_mensuel .= "Découvrez les nouvelles fonctionnalités de votre bibliothèque: \n";
        $message_mensuel .= "• Système de réservation amélioré \n";
        $message_mensuel .= "• Notifications en temps réel \n";
        $message_mensuel .= "• Historique détaillé de vos emprunts \n";
        $message_mensuel .= "Merci d'utiliser notre service !";
        
        foreach ($etudiants as $id_etudiant) {
            $stmt_alerte = $pdo->prepare("
                INSERT INTO alerte (id_etudiant, type_alerte, message, date_envoi, etat)
                VALUES (?, 'information', ?, NOW(), 'non_lu')
            ");
            $stmt_alerte->execute([$id_etudiant, $message_mensuel]);
            $notifications_envoyees++;
        }
        
        echo "Notifications mensuelles envoyées à " . count($etudiants) . " étudiants\n";
    }
    
    echo "=== {$notifications_envoyees} NOTIFICATIONS ENVOYÉES AVEC SUCCÈS ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>