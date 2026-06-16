<?php
// Fichier: includes/db.php
// Configuration de la connexion à la base de données

define('DB_HOST', 'localhost');
define('DB_NAME', 'database_final');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=3307;dbname="  . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("❌ ERREUR DE CONNEXION BD: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}
?>  