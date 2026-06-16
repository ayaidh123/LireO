<?php
// Fichier: api/books/get_one.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('student');

header('Content-Type: application/json');

$isbn = $_GET['isbn'] ?? null;

if (!$isbn) {
    echo json_encode(null);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM livre WHERE isbn = ?");
$stmt->execute([$isbn]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($book);