<?php
// Fichier: api/users/get_students.php 
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Accès refusé.']);
    exit;
}

try {
    // Récupère les étudiants nécessaires pour la liste déroulante
    $sql = "SELECT id_etudiant, nom, prenom, matricule FROM etudiant ORDER BY nom, prenom";
    $stmt = $pdo->query($sql);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $students]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur get_all_students: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'error' => 'Erreur SQL lors de la récupération des étudiants.']);
}
?>