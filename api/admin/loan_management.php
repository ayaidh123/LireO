<?php
// Fichier: api/admin/loan_management.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

/**
 * Nettoyage automatique des emprunts expirés et problématiques
 * Exécuté à chaque appel de l'API admin
 */
function cleanup_pending_loans() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $cleaned_count = 0;
        
        // 1. Nettoyer les retraits expirés (délai de 24h dépassé)
        $stmt_expired = $pdo->prepare("
            UPDATE emprunt e
            JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
            SET e.statut_emprunt = 'annule', ear.statut = 'expire'
            WHERE ear.statut = 'en_attente' 
            AND ear.date_limite_retrait < NOW()
        ");
        $stmt_expired->execute();
        $cleaned_count += $stmt_expired->rowCount();
        
        // 2. Nettoyer les emprunts en attente sans copies disponibles
        $stmt_no_copies = $pdo->prepare("
            UPDATE emprunt e
            JOIN livre l ON e.isbn = l.isbn
            JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
            SET e.statut_emprunt = 'annule', ear.statut = 'expire'
            WHERE e.statut_emprunt = 'en_attente_retrait'
            AND ear.statut = 'en_attente'
            AND (SELECT COUNT(*) FROM emprunt e2 
                 WHERE e2.isbn = e.isbn 
                 AND e2.date_retour_reel IS NULL 
                 AND e2.statut_emprunt = 'en_cours') >= l.nbre_de_copie_total
        ");
        $stmt_no_copies->execute();
        $cleaned_count += $stmt_no_copies->rowCount();
        
        $pdo->commit();
        
        if ($cleaned_count > 0) {
            error_log("Cleanup automatique: $cleaned_count emprunts nettoyés");
        }
        
        return $cleaned_count;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur cleanup automatique: " . $e->getMessage());
        return 0;
    }
}

// Exécuter le nettoyage automatique à chaque appel
$cleanup_count = cleanup_pending_loans();

$action = $_REQUEST['action'] ?? null;
$id_admin = $_SESSION['user']['id']; 
$response = ['status' => 'error', 'message' => 'Action invalide.'];

try {
    switch ($action) {
        
        case 'read_loans':
            $stmt = $pdo->query("
                SELECT 
                    e.id_emprunt, e.isbn, e.date_emprunt, e.date_retour_prevue, e.date_retour_reel, e.statut_emprunt,
                    l.titre, 
                    et.nom, et.prenom, et.id_etudiant, et.statut
                FROM emprunt e
                JOIN livre l ON e.isbn = l.isbn
                JOIN etudiant et ON e.id_etudiant = et.id_etudiant
                WHERE e.date_retour_reel IS NULL 
                AND e.statut_emprunt IN ('en_cours', 'en_attente_retrait')
                ORDER BY e.date_retour_prevue ASC
            ");
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = [
                'status' => 'success', 
                'data' => $data,
                'cleanup_count' => $cleanup_count
            ];
            break;

        case 'read_retraits_attente':
            $stmt = $pdo->query("
                SELECT 
                    e.id_emprunt, e.isbn, e.date_emprunt,
                    l.titre, l.nbre_de_copie_disponible,
                    et.nom, et.prenom, et.id_etudiant,
                    ear.date_demande, ear.date_limite_retrait, ear.statut
                FROM emprunt e
                JOIN livre l ON e.isbn = l.isbn
                JOIN etudiant et ON e.id_etudiant = et.id_etudiant
                JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt
                WHERE ear.statut = 'en_attente'
                ORDER BY ear.date_limite_retrait ASC
            ");
            $response = [
                'status' => 'success', 
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'cleanup_count' => $cleanup_count
            ];
            break;

        case 'confirm_retrait':
            $id_emprunt = $_POST['id_emprunt'] ?? null;
            if (!$id_emprunt) {
                $response = ['status' => 'error', 'message' => 'ID manquant.'];
                break;
            }

            $pdo->beginTransaction();

            try {
                // Vérifier que c'est bien en attente avec verrouillage
                $stmt_check = $pdo->prepare("
                    SELECT 
                        e.isbn, 
                        ear.date_limite_retrait, 
                        l.nbre_de_copie_total,
                        l.nbre_de_copie_disponible,
                        (SELECT COUNT(*) FROM emprunt e2 
                         WHERE e2.isbn = e.isbn 
                         AND e2.date_retour_reel IS NULL 
                         AND e2.statut_emprunt = 'en_cours') as emprunts_actifs
                    FROM emprunt e 
                    JOIN emprunt_attente_retrait ear ON e.id_emprunt = ear.id_emprunt 
                    JOIN livre l ON e.isbn = l.isbn
                    WHERE e.id_emprunt = ? AND ear.statut = 'en_attente'
                    FOR UPDATE
                ");
                $stmt_check->execute([$id_emprunt]);
                $emprunt = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if (!$emprunt) {
                    throw new Exception("Emprunt non trouvé ou déjà traité");
                }

                // VÉRIFICATION CRITIQUE : Y a-t-il encore des copies disponibles ?
                $copies_disponibles = $emprunt['nbre_de_copie_total'] - $emprunt['emprunts_actifs'];
                
                if ($copies_disponibles <= 0) {
                    // Plus de copies disponibles - annuler cet emprunt
                    $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'annule' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                    $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'expire' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                    
                    $pdo->commit();
                    $response = [
                        'status' => 'error', 
                        'message' => 'Plus de copies disponibles. Cet emprunt a été annulé automatiquement.'
                    ];
                    break;
                }

                // Vérifier si le délai n'est pas dépassé
                if (strtotime($emprunt['date_limite_retrait']) < time()) {
                    $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'expire' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                    $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'annule' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                    throw new Exception("Délai de retrait dépassé. Emprunt annulé.");
                }

                // Confirmer le retrait
                $pdo->prepare("UPDATE emprunt_attente_retrait SET statut = 'retire' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                $pdo->prepare("UPDATE emprunt SET statut_emprunt = 'en_cours' WHERE id_emprunt = ?")->execute([$id_emprunt]);
                
                // Mettre à jour les copies disponibles
                $pdo->prepare("UPDATE livre SET nbre_de_copie_disponible = ? WHERE isbn = ?")->execute([
                    $emprunt['nbre_de_copie_disponible'] - 1,
                    $emprunt['isbn']
                ]);

                $pdo->commit();
                $response = [
                    'status' => 'success', 
                    'message' => 'Retrait confirmé. Emprunt activé.',
                    'copies_restantes' => $copies_disponibles - 1
                ];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
            break;

        case 'read_extensions':
            $stmt = $pdo->query("
                SELECT 
                    p.id_prolongation, p.date_demande, p.nouvelle_date_prevue, 
                    e.id_emprunt, e.isbn, e.date_retour_prevue,
                    l.titre,
                    et.nom, et.prenom, et.id_etudiant
                FROM prolongation p
                JOIN emprunt e ON p.emprunt_id = e.id_emprunt 
                JOIN livre l ON e.isbn = l.isbn
                JOIN etudiant et ON e.id_etudiant = et.id_etudiant
                WHERE p.statut = 'en_attente'
                ORDER BY p.date_demande ASC
            ");
            $response = [
                'status' => 'success', 
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'cleanup_count' => $cleanup_count
            ];
            break;

        case 'register_return':
            $id_emprunt = $_POST['id_emprunt'] ?? null;
            if (!$id_emprunt) {
                $response = ['status' => 'error', 'message' => 'ID manquant.'];
                break;
            }

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("UPDATE emprunt SET date_retour_reel = NOW(), statut_emprunt = 'retourne' WHERE id_emprunt = ?");
                $stmt->execute([$id_emprunt]);
                
                $stmt_isbn = $pdo->prepare("SELECT isbn FROM emprunt WHERE id_emprunt = ?");
                $stmt_isbn->execute([$id_emprunt]);
                $isbn = $stmt_isbn->fetchColumn();

                $pdo->prepare("UPDATE livre SET nbre_de_copie_disponible = nbre_de_copie_disponible + 1 WHERE isbn = ?")->execute([$isbn]);
                
                $pdo->commit();
                $response = ['status' => 'success', 'message' => 'Retour enregistré.'];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'approve_extension':
            $id_prolongation = $_POST['id_prolongation'] ?? null;
            if (!$id_prolongation) {
                $response = ['status' => 'error', 'message' => 'ID manquant.'];
                break;
            }

            $pdo->beginTransaction();

            try {
                $stmt_data = $pdo->prepare("SELECT emprunt_id, nouvelle_date_prevue FROM prolongation WHERE id_prolongation = ? AND statut = 'en_attente'");
                $stmt_data->execute([$id_prolongation]);
                $data = $stmt_data->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    throw new Exception("Prolongation introuvable.");
                }

                $pdo->prepare("UPDATE emprunt SET date_retour_prevue = ? WHERE id_emprunt = ?")->execute([
                    $data['nouvelle_date_prevue'],
                    $data['emprunt_id']
                ]);

                $pdo->prepare("UPDATE prolongation SET statut = 'acceptee', id_admin_approbateur = ?, date_reponse = NOW() WHERE id_prolongation = ?")->execute([
                    $id_admin, $id_prolongation
                ]);

                $pdo->commit();
                $response = ['status' => 'success', 'message' => 'Prolongation approuvée.'];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'refuse_extension':
            $id_prolongation = $_POST['id_prolongation'] ?? null;
            if (!$id_prolongation) {
                $response = ['status' => 'error', 'message' => 'ID manquant.'];
                break;
            }

            try {
                $pdo->prepare("UPDATE prolongation SET statut = 'refusee', id_admin_approbateur = ?, date_reponse = NOW() WHERE id_prolongation = ?")->execute([
                    $id_admin, $id_prolongation
                ]);

                $response = ['status' => 'success', 'message' => 'Prolongation refusée.'];
                
            } catch (Exception $e) {
                throw $e;
            }
            break;

        case 'check_availability':
            $id_emprunt = $_GET['id_emprunt'] ?? null;
            if (!$id_emprunt) {
                $response = ['status' => 'error', 'message' => 'ID manquant'];
                break;
            }

            try {
                // Vérifier la disponibilité pour cet emprunt
                $stmt = $pdo->prepare("
                    SELECT 
                        e.isbn,
                        l.titre,
                        l.nbre_de_copie_total,
                        (SELECT COUNT(*) FROM emprunt e2 
                         WHERE e2.isbn = e.isbn 
                         AND e2.date_retour_reel IS NULL 
                         AND e2.statut_emprunt = 'en_cours') as emprunts_actuels
                    FROM emprunt e
                    JOIN livre l ON e.isbn = l.isbn
                    WHERE e.id_emprunt = ?
                ");
                
                $stmt->execute([$id_emprunt]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    $response = ['status' => 'error', 'message' => 'Emprunt non trouvé'];
                    break;
                }

                $copies_disponibles = $data['nbre_de_copie_total'] - $data['emprunts_actuels'];
                
                if ($copies_disponibles > 0) {
                    $response = [
                        'status' => 'available',
                        'message' => 'Copie disponible. ' . $copies_disponibles . ' copie(s) restante(s).',
                        'copies_restantes' => $copies_disponibles
                    ];
                } else {
                    $response = [
                        'status' => 'unavailable',
                        'message' => 'Plus de copies disponibles. Toutes les copies sont déjà empruntées.',
                        'copies_restantes' => 0
                    ];
                }
                
            } catch (Exception $e) {
                error_log("Erreur check_availability: " . $e->getMessage());
                $response = ['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
            }
            break;

        case 'cleanup_pending':
            // Action manuelle pour forcer le nettoyage
            $forced_count = cleanup_pending_loans();
            $response = [
                'status' => 'success',
                'message' => 'Nettoyage manuel effectué. ' . $forced_count . ' emprunts nettoyés.',
                'cleaned_count' => $forced_count
            ];
            break;
        
        default:
            $response = [
                'status' => 'error', 
                'message' => 'Action non reconnue.',
                'cleanup_count' => $cleanup_count
            ];
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur loan_management: " . $e->getMessage());
    $response = [
        'status' => 'error', 
        'message' => 'Erreur: ' . $e->getMessage(),
        'cleanup_count' => $cleanup_count
    ];
}

echo json_encode($response);
?>