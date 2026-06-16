<?php
// Fichier: api/admin/book_crud.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? null;
$response = ['status' => 'error', 'message' => 'Action invalide.'];

try {
    switch ($action) {
        
        case 'create':
            $isbn = $_POST['isbn'] ?? null;
            $titre = $_POST['titre'] ?? null;
            $auteur = $_POST['auteur'] ?? null;
            $nbre_copies = (int)($_POST['nbre_de_copie_total'] ?? 0);
            $categories = $_POST['categories'] ?? [];
            
            if (!$isbn || !$titre || !$auteur || $nbre_copies <= 0) {
                $response = ['status' => 'error', 'message' => 'Données manquantes.'];
                break;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO livre (isbn, titre, auteur, nbre_de_pages, annee_publication, 
                                   image_livre, resume, langue, nbre_de_copie_total, nbre_de_copie_disponible)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $isbn, $titre, $auteur,
                $_POST['nbre_de_pages'] ?? null,
                $_POST['annee_publication'] ?? null,
                $_POST['image_livre'] ?? 'default.jpg',
                $_POST['resume'] ?? null,
                $_POST['langue'] ?? 'Français',
                $nbre_copies, $nbre_copies
            ]);
            
            // Gestion des catégories
            if (!empty($categories)) {
                foreach ($categories as $cat_id) {
                    $stmt_cat = $pdo->prepare("INSERT INTO posseder (isbn, id_categorie) VALUES (?, ?)");
                    $stmt_cat->execute([$isbn, $cat_id]);
                }
            }

            $pdo->commit();
            $response = ['status' => 'success', 'message' => 'Livre créé.'];
            break;

        case 'read':
            $stmt = $pdo->query("
                SELECT l.*, 
                       GROUP_CONCAT(DISTINCT c.nom_categorie) as categories_noms
                FROM livre l
                LEFT JOIN posseder p ON l.isbn = p.isbn
                LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                GROUP BY l.isbn
                ORDER BY l.titre ASC
            ");
            $response = ['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            break;
            
        case 'read_one':
            $isbn = $_GET['isbn'] ?? null;
            if (!$isbn) {
                $response = ['status' => 'error', 'message' => 'ISBN manquant.'];
                break;
            }
            $stmt = $pdo->prepare("SELECT * FROM livre WHERE isbn = ?");
            $stmt->execute([$isbn]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($book) {
                $stmt_cat = $pdo->prepare("SELECT id_categorie FROM posseder WHERE isbn = ?");
                $stmt_cat->execute([$isbn]);
                $book['categories'] = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
                $response = ['status' => 'success', 'data' => $book];
            } else {
                $response = ['status' => 'error', 'message' => 'Livre introuvable.'];
            }
            break;

        case 'update':
            $isbn = $_POST['original_isbn'] ?? null;
            $titre = $_POST['titre'] ?? null;
            $nbre_copies = (int)($_POST['nbre_de_copie_total'] ?? 0);
            $categories = $_POST['categories'] ?? [];
            
            if (!$isbn || !$titre || $nbre_copies <= 0) {
                $response = ['status' => 'error', 'message' => 'Données manquantes.'];
                break;
            }
            
            $pdo->beginTransaction();

            // Calculer les copies disponibles
            $stmt_current = $pdo->prepare("SELECT nbre_de_copie_total, nbre_de_copie_disponible FROM livre WHERE isbn = ?");
            $stmt_current->execute([$isbn]);
            $current = $stmt_current->fetch(PDO::FETCH_ASSOC);
            
            $difference = $nbre_copies - $current['nbre_de_copie_total'];
            $new_disponible = $current['nbre_de_copie_disponible'] + $difference;

            $stmt = $pdo->prepare("
                UPDATE livre SET titre=?, auteur=?, nbre_de_pages=?, annee_publication=?, 
                                image_livre=?, resume=?, langue=?, nbre_de_copie_total=?, nbre_de_copie_disponible=?
                WHERE isbn = ?
            ");
            $stmt->execute([
                $titre, $_POST['auteur'] ?? null,
                $_POST['nbre_de_pages'] ?? null,
                $_POST['annee_publication'] ?? null,
                $_POST['image_livre'] ?? 'default.jpg',
                $_POST['resume'] ?? null,
                $_POST['langue'] ?? 'Français',
                $nbre_copies, $new_disponible, $isbn
            ]);

            // Mise à jour des catégories
            $pdo->prepare("DELETE FROM posseder WHERE isbn = ?")->execute([$isbn]);
            if (!empty($categories)) {
                foreach ($categories as $cat_id) {
                    $stmt_cat = $pdo->prepare("INSERT INTO posseder (isbn, id_categorie) VALUES (?, ?)");
                    $stmt_cat->execute([$isbn, $cat_id]);
                }
            }

            $pdo->commit();
            $response = ['status' => 'success', 'message' => 'Livre mis à jour.'];
            break;
            
        case 'delete':
            $isbn = $_POST['isbn'] ?? null;
            if (!$isbn) {
                $response = ['status' => 'error', 'message' => 'ISBN manquant.'];
                break;
            }

            // Vérifier s'il y a des emprunts en cours
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM emprunt WHERE isbn = ? AND date_retour_reel IS NULL");
            $stmt_check->execute([$isbn]);
            if ($stmt_check->fetchColumn() > 0) {
                $response = ['status' => 'error', 'message' => 'Impossible de supprimer: des emprunts sont en cours pour ce livre.'];
                break;
            }

            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM reserver WHERE isbn = ?")->execute([$isbn]);
            $pdo->prepare("DELETE FROM emprunt WHERE isbn = ?")->execute([$isbn]);
            $pdo->prepare("DELETE FROM posseder WHERE isbn = ?")->execute([$isbn]);
            $pdo->prepare("DELETE FROM livre WHERE isbn = ?")->execute([$isbn]);

            $pdo->commit();
            $response = ['status' => 'success', 'message' => 'Livre supprimé.'];
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Action non reconnue.'];
            break;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur BD book_crud: " . $e->getMessage());
    $response = ['status' => 'error', 'message' => 'Erreur BD: ' . $e->getMessage()];
}

echo json_encode($response);
?>