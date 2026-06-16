<?php
// Fichier: api/admin/manage_sanctions.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

// Récupération de l'action (GET ou POST)
$action = $_REQUEST['action'] ?? null;
// Pour les requêtes JSON (DELETE, PUT), on lit le corps
if (!$action) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
}

$response = ['status' => 'error', 'message' => 'Action invalide.'];

try {
    switch ($action) {
        
        case 'get_sanctions':
            // CORRECTION : Utilisation des noms de colonnes de votre image
            $stmt = $pdo->query("
                SELECT 
                    s.id_sanction,
                    s.type_sanction,
                    s.details_faute,
                    s.date_debut_sanction,
                    s.date_fin_bannissement,
                    s.statut,
                    e.nom as etudiant_nom,
                    e.prenom as etudiant_prenom,
                    e.email_academique as etudiant_email
                FROM sanction s
                JOIN etudiant e ON s.id_etudiant = e.id_etudiant
                ORDER BY s.date_debut_sanction DESC
            ");
            $response = ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;
        case 'imposer_sanction':
    // 1. Récupération des données envoyées par le JS
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id_etudiant = $input['id_etudiant'] ?? null;
    $type_sanction = $input['type_sanction'] ?? null;
    $details = $input['details_faute'] ?? '';
    $date_fin = $input['date_fin'] ?? null;
    
    // On suppose que l'ID de l'admin est dans la session
    $id_admin = $_SESSION['user']['id'] ?? 1; // Mettez 1 par défaut si la session est vide pour tester

    if (!$id_etudiant || !$type_sanction || !$date_fin) {
        $response = ['status' => 'error', 'message' => 'Veuillez remplir tous les champs obligatoires.'];
        break;
    }

    // 2. Insertion en base de données (Colonnes exactes de votre image)
    $stmt = $pdo->prepare("
        INSERT INTO sanction 
        (id_etudiant, type_sanction, details_faute, date_debut_sanction, date_fin_bannissement, id_admin_sanction, statut)
        VALUES (?, ?, ?, CURDATE(), ?, ?, 'active')
    ");

    $stmt->execute([
        $id_etudiant, 
        $type_sanction, 
        $details, 
        $date_fin, 
        $id_admin
    ]);

    $response = ['status' => 'success', 'message' => 'Sanction ajoutée avec succès.'];
    break;    

        

        case 'lever_sanction':
            // 1. Récupérer l'ID
            $input = json_decode(file_get_contents('php://input'), true);
            // On gère les deux cas : soit via POST direct, soit via JSON (fetch)
            $id_sanction = $input['id_sanction'] ?? $_POST['id_sanction'] ?? null;
            
            if (!$id_sanction) throw new Exception('ID sanction manquant.');

            // 2. Trouver l'étudiant concerné
            $stmt = $pdo->prepare("SELECT id_etudiant FROM sanction WHERE id_sanction = ?");
            $stmt->execute([$id_sanction]);
            $sanction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sanction) {
                // 3. Mettre la sanction en 'levée'
                $pdo->prepare("UPDATE sanction SET statut = 'levee' WHERE id_sanction = ?")
                    ->execute([$id_sanction]);
                
                // 4. DÉBLOQUER L'ÉTUDIANT (C'est la ligne importante !)
                $pdo->prepare("UPDATE etudiant SET statut = 'actif' WHERE id_etudiant = ?")
                    ->execute([$sanction['id_etudiant']]);
            }

            $response = ['status' => 'success', 'message' => 'Sanction levée et compte réactivé.'];
            break;

        case 'delete_sanction':
            $input = json_decode(file_get_contents('php://input'), true);
            $id_sanction = $input['id_sanction'] ?? null;

            if (!$id_sanction) throw new Exception('ID manquant');

            // ÉTAPE 1 : On récupère l'ID de l'étudiant AVANT de supprimer la sanction
            // (Sinon on ne saura plus qui débloquer après la suppression)
            $stmt = $pdo->prepare("SELECT id_etudiant FROM sanction WHERE id_sanction = ?");
            $stmt->execute([$id_sanction]);
            $sanction = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sanction) {
                // ÉTAPE 2 : On supprime la sanction
                $pdo->prepare("DELETE FROM sanction WHERE id_sanction = ?")->execute([$id_sanction]);

                // ÉTAPE 3 : On débloque l'étudiant (remettre statut à 'actif')
                // Cela assure que si on supprime une suspension, l'élève peut revenir
                $pdo->prepare("UPDATE etudiant SET statut = 'actif' WHERE id_etudiant = ?")
                    ->execute([$sanction['id_etudiant']]);
            }

            $response = ['status' => 'success', 'message' => 'Sanction supprimée et compte débloqué.'];
            break;
            
       case 'extend_sanction':
            $input = json_decode(file_get_contents('php://input'), true);
            $id_sanction = $input['id_sanction'] ?? null;
            $new_date = $input['new_date'] ?? null;

            if (!$id_sanction || !$new_date) throw new Exception('Données manquantes (ID ou Date).');

            // ÉTAPE 1 : Mettre à jour la date ET forcer le statut à 'active'
            // (Si la sanction était finie hier, et qu'on la prolonge aujourd'hui, elle redevient active)
            $stmt = $pdo->prepare("
                UPDATE sanction 
                SET date_fin_bannissement = ?, statut = 'active' 
                WHERE id_sanction = ?
            ");
            $stmt->execute([$new_date, $id_sanction]);

            // ÉTAPE 2 : Vérifier le type de sanction pour bloquer l'étudiant si nécessaire
            $stmtInfo = $pdo->prepare("SELECT id_etudiant, type_sanction FROM sanction WHERE id_sanction = ?");
            $stmtInfo->execute([$id_sanction]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            // Si c'est une Suspension ou Exclusion, on force le blocage du compte étudiant
            if ($info && ($info['type_sanction'] === 'Suspension' || $info['type_sanction'] === 'Exclusion')) {
                $pdo->prepare("UPDATE etudiant SET statut = 'bloque' WHERE id_etudiant = ?")
                    ->execute([$info['id_etudiant']]);
            }

            $response = ['status' => 'success', 'message' => 'Sanction prolongée et compte mis à jour.'];
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Action inconnue: ' . $action];
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ['status' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
}

echo json_encode($response);
?>
