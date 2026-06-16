<?php
// Fichier: api/admin/student_management.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? null;
$response = ['status' => 'error', 'message' => 'Action invalide.'];

try {
    switch ($action) {

        // ================= CREATE =================
        case 'create':
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email_academique'] ?? '');
            $matricule = trim($_POST['matricule'] ?? '');
            $promotion = trim($_POST['promotion'] ?? '');

            if (!$prenom || !$nom || !$email) {
                $response = ['status' => 'error', 'message' => 'Données manquantes.'];
                break;
            }

            // Validation email académique UMI
            if (!preg_match('/^[a-zA-Z0-9._-]+@umi\.ac\.ma$/', $email)) {
                $response = ['status' => 'error', 'message' => 'Email académique invalide. Doit être @umi.ac.ma'];
                break;
            }

            $stmt = $pdo->prepare("
                INSERT INTO etudiant (prenom, nom, email_academique, matricule, promotion, mot_de_passe, statut)
                VALUES (?, ?, ?, ?, ?, NULL, 'actif')
            ");
            $stmt->execute([$prenom, $nom, $email, $matricule, $promotion]);

            $response = ['status' => 'success', 'message' => "Étudiant créé. Code: $email"];
            break;

        // ================= READ =================
        case 'read_all':
            $stmt = $pdo->query("
                SELECT 
                    id_etudiant, prenom, nom, email_academique, matricule,
                    promotion, statut, (mot_de_passe IS NOT NULL) AS compte_active,
                    date_creation
                FROM etudiant 
                ORDER BY nom, prenom ASC
            ");
            $response = ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;

        // ================= UPDATE =================
        case 'update':
            $id = $_POST['id_etudiant'] ?? null;
            $prenom = trim($_POST['prenom'] ?? '');
            $nom = trim($_POST['nom'] ?? '');
            $matricule = trim($_POST['matricule'] ?? '');
            $promotion = trim($_POST['promotion'] ?? '');
            $statut = $_POST['statut'] ?? 'actif';

            if (!$id || !$prenom || !$nom) {
                $response = ['status' => 'error', 'message' => 'Données manquantes.'];
                break;
            }

            $stmt = $pdo->prepare("
                UPDATE etudiant 
                SET prenom=?, nom=?, matricule=?, promotion=?, statut=? 
                WHERE id_etudiant = ?
            ");
            $stmt->execute([$prenom, $nom, $matricule, $promotion, $statut, $id]);

            $response = ['status' => 'success', 'message' => 'Étudiant mis à jour.'];
            break;

        // ================= DELETE =================
        case 'delete':
            $id = $_POST['id_etudiant'] ?? null;

            if (!$id) {
                $response = ['status' => 'error', 'message' => 'ID manquant.'];
                break;
            }

            // Vérifier emprunts en cours
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) FROM emprunt 
                WHERE id_etudiant = ? AND date_retour_reel IS NULL
            ");
            $stmt_check->execute([$id]);

            if ($stmt_check->fetchColumn() > 0) {
                $response = ['status' => 'error', 'message' => '❌ Impossible: emprunts en cours.'];
                break;
            }

            $pdo->beginTransaction();

            // Supprimer les prolongations liées aux emprunts
            $stmt_del_prolongations = $pdo->prepare("
                DELETE p FROM prolongation p
                INNER JOIN emprunt e ON p.emprunt_id = e.id_emprunt
                WHERE e.id_etudiant = ?
            ");
            $stmt_del_prolongations->execute([$id]);

            // Supprimer les emprunts
            $pdo->prepare("DELETE FROM emprunt WHERE id_etudiant = ?")->execute([$id]);

            // Supprimer les réservations
            $pdo->prepare("DELETE FROM reserver WHERE id_etudiant = ?")->execute([$id]);

            // Supprimer les sanctions
            $pdo->prepare("DELETE FROM sanction WHERE id_etudiant = ?")->execute([$id]);

            // Supprimer les messages
            $pdo->prepare("DELETE FROM message WHERE id_etudiant_expediteur = ?")->execute([$id]);

            // Supprimer l'étudiant
            $pdo->prepare("DELETE FROM etudiant WHERE id_etudiant = ?")->execute([$id]);

            $pdo->commit();
            $response = ['status' => 'success', 'message' => '✅ Étudiant supprimé.'];
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Action non reconnue.'];
            break;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23000') {
        $response = ['status' => 'error', 'message' => '❌ Email déjà utilisé.'];
    } else {
        error_log("Erreur BD student_management: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => '❌ Erreur BD: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>