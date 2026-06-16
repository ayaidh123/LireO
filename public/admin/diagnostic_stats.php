<?php
// Fichier: public/admin/diagnostic_stats.php
// Diagnostic précis du problème des statistiques

require_once __DIR__ . '/../../includes/db.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <title>Diagnostic Statistiques</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/pageicon.png">
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #2a5298; }
        h2 { color: #1e3c72; border-bottom: 2px solid #2a5298; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2a5298; color: white; }
        .code { background: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <h1> Diagnostic des Statistiques du Dashboard</h1>

    <?php
    echo "<div class='box'>";
    echo "<h2> Test 1: Compter les livres</h2>";
    
    try {
        // Test avec différentes méthodes
        echo "<h3>Méthode 1: COUNT(*)</h3>";
        $sql1 = "SELECT COUNT(*) as total FROM livre";
        echo "<pre>$sql1</pre>";
        $result1 = $pdo->query($sql1);
        $count1 = $result1->fetch(PDO::FETCH_ASSOC);
        echo "<div class='success'>Résultat: " . var_export($count1, true) . "</div>";
        
        echo "<h3>Méthode 2: fetchColumn()</h3>";
        $sql2 = "SELECT COUNT(*) FROM livre";
        echo "<pre>$sql2</pre>";
        $count2 = $pdo->query($sql2)->fetchColumn();
        echo "<div class='success'>Résultat: $count2 (type: " . gettype($count2) . ")</div>";
        
        echo "<h3>Méthode 3: Compter toutes les lignes</h3>";
        $sql3 = "SELECT * FROM livre";
        echo "<pre>$sql3</pre>";
        $all_livres = $pdo->query($sql3)->fetchAll();
        $count3 = count($all_livres);
        echo "<div class='success'>Résultat: $count3 livres trouvés</div>";
        
        if ($count3 > 0) {
            echo "<h4>Premier livre (exemple):</h4>";
            echo "<pre>" . print_r($all_livres[0], true) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h2>👥 Test 2: Compter les étudiants</h2>";
    
    try {
        echo "<h3>Tous les étudiants</h3>";
        $sql1 = "SELECT COUNT(*) FROM etudiant";
        echo "<pre>$sql1</pre>";
        $count_all = $pdo->query($sql1)->fetchColumn();
        echo "<div class='success'>Total étudiants: $count_all</div>";
        
        echo "<h3>Vérifier la colonne 'statut'</h3>";
        $sql2 = "SELECT DISTINCT statut FROM etudiant";
        echo "<pre>$sql2</pre>";
        $statuts = $pdo->query($sql2)->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='warning'>Valeurs de statut trouvées: " . implode(', ', $statuts) . "</div>";
        
        echo "<h3>Étudiants avec statut = 'actif'</h3>";
        $sql3 = "SELECT COUNT(*) FROM etudiant WHERE statut = 'actif'";
        echo "<pre>$sql3</pre>";
        $count_actif = $pdo->query($sql3)->fetchColumn();
        echo "<div class='success'>Étudiants actifs: $count_actif</div>";
        
        // Tester d'autres variations
        foreach (['active', 'ACTIF', '1', 'Actif'] as $test_statut) {
            $sql = "SELECT COUNT(*) FROM etudiant WHERE statut = '$test_statut'";
            $test_count = $pdo->query($sql)->fetchColumn();
            if ($test_count > 0) {
                echo "<div class='success'>✅ Trouvé $test_count avec statut = '$test_statut'</div>";
            }
        }
        
        // Afficher quelques étudiants
        $etudiants = $pdo->query("SELECT * FROM etudiant LIMIT 3")->fetchAll();
        if (count($etudiants) > 0) {
            echo "<h4>Exemples d'étudiants:</h4>";
            echo "<pre>" . print_r($etudiants, true) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h2>📚 Test 3: Compter les emprunts</h2>";
    
    try {
        echo "<h3>Tous les emprunts</h3>";
        $sql1 = "SELECT COUNT(*) FROM emprunt";
        echo "<pre>$sql1</pre>";
        $count_all = $pdo->query($sql1)->fetchColumn();
        echo "<div class='success'>Total emprunts: $count_all</div>";
        
        echo "<h3>Vérifier les colonnes de la table emprunt</h3>";
        $columns = $pdo->query("DESCRIBE emprunt")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>Colonne</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td></tr>";
        }
        echo "</table>";
        
        // Chercher les colonnes possibles pour "date_retour_reel"
        $date_columns = array_filter($columns, function($col) {
            return strpos(strtolower($col['Field']), 'retour') !== false;
        });
        
        if (count($date_columns) > 0) {
            echo "<div class='warning'>Colonnes avec 'retour': ";
            foreach ($date_columns as $col) {
                echo "<strong>{$col['Field']}</strong>, ";
            }
            echo "</div>";
        }
        
        echo "<h3>Emprunts non retournés (date_retour_reel IS NULL)</h3>";
        $sql2 = "SELECT COUNT(*) FROM emprunt WHERE date_retour_reel IS NULL";
        echo "<pre>$sql2</pre>";
        
        try {
            $count_actifs = $pdo->query($sql2)->fetchColumn();
            echo "<div class='success'>Emprunts actifs: $count_actifs</div>";
        } catch (Exception $e) {
            echo "<div class='error'>Erreur avec date_retour_reel: " . $e->getMessage() . "</div>";
            
            // Tester d'autres noms de colonnes
            $test_columns = ['date_retour_effectif', 'date_retour', 'retour_date', 'returned_date'];
            foreach ($test_columns as $col_name) {
                try {
                    $test_sql = "SELECT COUNT(*) FROM emprunt WHERE $col_name IS NULL";
                    $test_count = $pdo->query($test_sql)->fetchColumn();
                    echo "<div class='success'>✅ Avec '$col_name IS NULL': $test_count emprunts</div>";
                } catch (Exception $e2) {
                    // Colonne n'existe pas
                }
            }
        }
        
        // Afficher quelques emprunts
        $emprunts = $pdo->query("SELECT * FROM emprunt LIMIT 3")->fetchAll();
        if (count($emprunts) > 0) {
            echo "<h4>Exemples d'emprunts:</h4>";
            echo "<pre>" . print_r($emprunts, true) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h2><i class='fas fa-bookmark'></i> Test 4: Compter les réservations</h2>";
    
    try {
        echo "<h3>Toutes les réservations</h3>";
        $sql1 = "SELECT COUNT(*) FROM reserver";
        echo "<pre>$sql1</pre>";
        $count_all = $pdo->query($sql1)->fetchColumn();
        echo "<div class='success'>Total réservations: $count_all</div>";
        
        echo "<h3>Vérifier les statuts de réservation</h3>";
        $sql2 = "SELECT DISTINCT statut_reservation FROM reserver";
        echo "<pre>$sql2</pre>";
        
        try {
            $statuts = $pdo->query($sql2)->fetchAll(PDO::FETCH_COLUMN);
            echo "<div class='warning'>Statuts trouvés: " . implode(', ', $statuts) . "</div>";
            
            foreach ($statuts as $statut) {
                $count = $pdo->query("SELECT COUNT(*) FROM reserver WHERE statut_reservation = '$statut'")->fetchColumn();
                echo "<div class='success'>Statut '$statut': $count réservations</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Erreur avec statut_reservation: " . $e->getMessage() . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h2><i class='fas fa-warning'></i> Test 5: Emprunts en retard</h2>";
    
    try {
        echo "<h3>Date actuelle du serveur</h3>";
        $current_date = $pdo->query("SELECT CURDATE() as today")->fetch();
        echo "<div class='success'>Date serveur: " . $current_date['today'] . "</div>";
        
        echo "<h3>Recherche des retards</h3>";
        $sql = "SELECT COUNT(*) FROM emprunt WHERE date_retour_prevue < CURDATE() AND date_retour_reel IS NULL";
        echo "<pre>$sql</pre>";
        
        try {
            $count_retards = $pdo->query($sql)->fetchColumn();
            echo "<div class='success'>Emprunts en retard: $count_retards</div>";
        } catch (Exception $e) {
            echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='box'>";
    echo "<h2>⚖️ Test 6: Sanctions actives</h2>";
    
    try {
        echo "<h3>Toutes les sanctions</h3>";
        $count_all = $pdo->query("SELECT COUNT(*) FROM sanction")->fetchColumn();
        echo "<div class='success'>Total sanctions: $count_all</div>";
        
        echo "<h3>Statuts de sanctions</h3>";
        $statuts = $pdo->query("SELECT DISTINCT statut FROM sanction")->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='warning'>Statuts trouvés: " . implode(', ', $statuts) . "</div>";
        
        foreach ($statuts as $statut) {
            $count = $pdo->query("SELECT COUNT(*) FROM sanction WHERE statut = '$statut'")->fetchColumn();
            echo "<div class='success'>Statut '$statut': $count sanctions</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> Erreur: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    // CODE CORRIGÉ À UTILISER
    echo "<div class='box' style='background: #e7f3ff; border-left: 4px solid #2a5298;'>";
    echo "<h2> Code Corrigé à Utiliser</h2>";
    echo "<p>Basé sur les tests ci-dessus, voici le code correct pour votre dashboard:</p>";
    echo "<div class='code'>";
    echo htmlspecialchars('
<?php
// À mettre dans dashboard.php après require_role(\'admin\');

try {
    // Livres - Total
    $count_livres = $pdo->query("SELECT COUNT(*) FROM livre")->fetchColumn();
    
    // Étudiants - Vérifiez le statut correct d\'après Test 2
    // Si le statut est \'actif\':
    $count_etudiants = $pdo->query("SELECT COUNT(*) FROM etudiant WHERE statut = \'actif\'")->fetchColumn();
    // OU si le statut est différent, utilisez la valeur trouvée
    
    // Emprunts actifs - Vérifiez le nom de colonne d\'après Test 3
    $count_emprunts = $pdo->query("SELECT COUNT(*) FROM emprunt WHERE date_retour_reel IS NULL")->fetchColumn();
    
    // Réservations en attente
    $count_reserves = $pdo->query("SELECT COUNT(*) FROM reserver WHERE statut_reservation = \'en_attente\'")->fetchColumn();
    
    // Retards
    $count_retards = $pdo->query("
        SELECT COUNT(*) FROM emprunt 
        WHERE date_retour_prevue < CURDATE() 
        AND date_retour_reel IS NULL
    ")->fetchColumn();
    
    // Sanctions actives
    $count_sanctions = $pdo->query("SELECT COUNT(*) FROM sanction WHERE statut = \'active\'")->fetchColumn();
    
    // Convertir en entiers
    $count_livres = (int)$count_livres;
    $count_etudiants = (int)$count_etudiants;
    $count_emprunts = (int)$count_emprunts;
    $count_reserves = (int)$count_reserves;
    $count_retards = (int)$count_retards;
    $count_sanctions = (int)$count_sanctions;
    
} catch (Exception $e) {
    error_log("Erreur stats dashboard: " . $e->getMessage());
    $count_livres = $count_etudiants = $count_emprunts = 0;
    $count_reserves = $count_retards = $count_sanctions = 0;
}
?>
    ');
    echo "</div>";
    echo "</div>";
    ?>

    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px;">
        <h3> Instructions:</h3>
        <ol>
            <li>Lisez attentivement les résultats des tests ci-dessus</li>
            <li>Notez les noms EXACTS des colonnes et valeurs de statut</li>
            <li>Copiez le code corrigé et adaptez-le selon vos résultats</li>
            <li>Remplacez le code dans votre dashboard.php</li>
            <li>Supprimez ce fichier diagnostic après utilisation</li>
        </ol>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="dashboard.php" style="display: inline-block; padding: 15px 40px; background: #2a5298; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
            Retour au Dashboard
        </a>
    </div>

</body>
</html>