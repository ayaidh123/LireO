<?php
// Fichier: api/books/search.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$search = trim($_GET['q'] ?? '');
$category = $_GET['cat'] ?? '';

$query = "SELECT DISTINCT l.isbn, l.titre, l.auteur, l.image_livre, l.nbre_de_copie_disponible, 
                  l.nbre_de_copie_total, l.resume, l.annee_publication, l.nbre_de_pages, l.langue
          FROM livre l
          LEFT JOIN posseder p ON l.isbn = p.isbn
          LEFT JOIN categorie c ON p.id_categorie = c.id_categorie";

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "(l.titre LIKE ? OR l.auteur LIKE ? OR l.isbn LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category !== '') {
    $conditions[] = "p.id_categorie = ?";
    $params[] = $category;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY l.titre ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $books]);
?>