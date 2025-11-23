<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic complet du blog</h1>";
echo "<style>
    body { font-family: Arial; max-width: 1200px; margin: 30px auto; padding: 20px; background: #f5f5f5; }
    h1 { color: #2c3e50; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    h2 { color: #667eea; margin-top: 30px; background: white; padding: 15px; border-radius: 5px; }
    .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; border-radius: 5px; }
    .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin: 10px 0; border-radius: 5px; }
    pre { background: #2c3e50; color: #fff; padding: 15px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #667eea; color: white; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn:hover { background: #5568d3; }
    .fix-btn { background: #28a745; }
</style>";

include("connexion.php");

// ============================================
// 1. TEST DE CONNEXION
// ============================================
echo "<h2>1️⃣ Connexion à la base de données</h2>";
try {
    $conn->query("SELECT 1");
    echo "<div class='success'>✅ Connexion réussie</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
    die();
}

// ============================================
// 2. VÉRIFICATION DES TABLES
// ============================================
echo "<h2>2️⃣ Vérification des tables</h2>";
$required_tables = ['utilisateur', 'article', 'categories', 'setting'];
$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($required_tables as $table) {
    if (in_array($table, $tables)) {
        $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<div class='success'>✅ Table '$table' existe ($count lignes)</div>";
    } else {
        echo "<div class='error'>❌ Table '$table' manquante</div>";
    }
}

// ============================================
// 3. STRUCTURE DE LA TABLE ARTICLE
// ============================================
echo "<h2>3️⃣ Structure de la table 'article'</h2>";
try {
    $columns = $conn->query("DESCRIBE article")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    $expected_columns = ['id_article', 'titre', 'slug', 'contenu', 'image_couverture', 'id_utilisateur', 'id_categories', 'vues', 'date_creation'];
    $existing_columns = array_column($columns, 'Field');
    
    foreach ($columns as $col) {
        echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td><td>" . $col['Null'] . "</td><td>" . $col['Key'] . "</td></tr>";
    }
    echo "</table>";
    
    // Vérifier les colonnes manquantes
    $missing = array_diff($expected_columns, $existing_columns);
    if (!empty($missing)) {
        echo "<div class='warning'>⚠️ Colonnes manquantes : " . implode(', ', $missing) . "</div>";
        echo "<div class='info'>Pour corriger, exécutez ces requêtes SQL :</div>";
        echo "<pre>";
        foreach ($missing as $col) {
            switch($col) {
                case 'vues':
                    echo "ALTER TABLE article ADD COLUMN vues INT DEFAULT 0;\n";
                    break;
                case 'id_categories':
                    echo "ALTER TABLE article ADD COLUMN id_categories INT;\n";
                    break;
            }
        }
        echo "</pre>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
}

// ============================================
// 4. VÉRIFICATION DES CLÉS ÉTRANGÈRES
// ============================================
echo "<h2>4️⃣ Vérification des clés étrangères</h2>";
try {
    // Vérifier article -> categories
    $query = "SELECT article.id_article, article.titre, article.id_categories, categories.nom AS cat_nom
              FROM article 
              LEFT JOIN categories ON article.id_categories = categories.id_categories
              WHERE article.id_categories IS NOT NULL
              LIMIT 5";
    
    $result = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($result) > 0) {
        echo "<div class='success'>✅ Liaison article ↔ categories fonctionne</div>";
        echo "<table><tr><th>ID Article</th><th>Titre</th><th>ID Catégorie</th><th>Nom Catégorie</th></tr>";
        foreach ($result as $row) {
            $status = $row['cat_nom'] ? '✅' : '❌';
            echo "<tr><td>$status {$row['id_article']}</td><td>" . htmlspecialchars($row['titre']) . "</td><td>{$row['id_categories']}</td><td>" . htmlspecialchars($row['cat_nom'] ?? 'NON TROUVÉE') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucun article avec catégorie</div>";
    }
    
    // Vérifier article -> utilisateur
    $query2 = "SELECT article.id_article, article.titre, article.id_utilisateur, utilisateur.nom_complet
               FROM article 
               LEFT JOIN utilisateur ON article.id_utilisateur = utilisateur.id_utilisateur
               LIMIT 5";
    
    $result2 = $conn->query($query2)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($result2) > 0) {
        echo "<div class='success'>✅ Liaison article ↔ utilisateur fonctionne</div>";
        echo "<table><tr><th>ID Article</th><th>Titre</th><th>ID User</th><th>Auteur</th></tr>";
        foreach ($result2 as $row) {
            $status = $row['nom_complet'] ? '✅' : '❌';
            echo "<tr><td>$status {$row['id_article']}</td><td>" . htmlspecialchars($row['titre']) . "</td><td>{$row['id_utilisateur']}</td><td>" . htmlspecialchars($row['nom_complet'] ?? 'NON TROUVÉ') . "</td></tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
    echo "<div class='warning'>La colonne 'id_categories' n'existe probablement pas. Voir section 3 pour la corriger.</div>";
}

// ============================================
// 5. TEST DE LA REQUÊTE ARTICLE COMPLET
// ============================================
echo "<h2>5️⃣ Test de la requête article complet</h2>";
try {
    $test_article = $conn->query("SELECT id_article FROM article LIMIT 1")->fetch();
    
    if ($test_article) {
        $id = $test_article['id_article'];
        
        $requete = $conn->prepare("SELECT article.id_article, article.titre, article.slug, article.contenu,
                            article.image_couverture, article.date_creation, article.vues,
                            utilisateur.nom_complet AS auteur, categories.nom AS categorie
                            FROM article 
                            JOIN utilisateur ON article.id_utilisateur = utilisateur.id_utilisateur
                            JOIN categories ON article.id_categories = categories.id_categories
                            WHERE article.id_article = ?");
        
        $requete->execute([$id]);
        $article = $requete->fetch(PDO::FETCH_ASSOC);
        
        if ($article) {
            echo "<div class='success'>✅ Requête fonctionne pour l'article #$id</div>";
            echo "<table>";
            echo "<tr><th>Champ</th><th>Valeur</th></tr>";
            foreach ($article as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value ?? '', 0, 100)) . "</td></tr>";
            }
            echo "</table>";
            
            echo "<div class='info'>";
            echo "🔗 <a href='articles.php?id=$id' class='btn' target='_blank'>Tester articles.php?id=$id</a>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ La requête ne retourne aucun résultat</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Aucun article dans la base pour tester</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur SQL : " . $e->getMessage() . "</div>";
    
    if (strpos($e->getMessage(), 'id_categories') !== false) {
        echo "<div class='warning'>⚠️ La colonne 'id_categories' n'existe pas. Correction :</div>";
        echo "<pre>ALTER TABLE article ADD COLUMN id_categories INT;</pre>";
        echo "<a href='?fix_id_categories=1' class='btn fix-btn'>🔧 Corriger automatiquement</a>";
    }
}

// ============================================
// 6. CORRECTION AUTOMATIQUE
// ============================================
if (isset($_GET['fix_id_categories'])) {
    echo "<h2>🔧 Correction automatique</h2>";
    try {
        // Vérifier si la colonne existe
        $cols = array_column($conn->query("DESCRIBE article")->fetchAll(), 'Field');
        
        if (!in_array('id_categories', $cols)) {
            $conn->exec("ALTER TABLE article ADD COLUMN id_categories INT");
            echo "<div class='success'>✅ Colonne 'id_categories' ajoutée</div>";
            
            // Assigner une catégorie par défaut
            $default_cat = $conn->query("SELECT id_categories FROM categories LIMIT 1")->fetchColumn();
            if ($default_cat) {
                $conn->exec("UPDATE article SET id_categories = $default_cat WHERE id_categories IS NULL");
                echo "<div class='success'>✅ Catégorie par défaut assignée</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ La colonne existe déjà</div>";
        }
        
        echo "<a href='diagnostic_blog.php' class='btn'>🔄 Recharger le diagnostic</a>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
    }
}

// ============================================
// 7. LISTE DES ARTICLES
// ============================================
echo "<h2>7️⃣ Liste des articles disponibles</h2>";
try {
    $articles = $conn->query("SELECT id_article, titre, date_creation FROM article ORDER BY date_creation DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($articles) > 0) {
        echo "<table><tr><th>ID</th><th>Titre</th><th>Date</th><th>Actions</th></tr>";
        foreach ($articles as $art) {
            echo "<tr>";
            echo "<td>#{$art['id_article']}</td>";
            echo "<td>" . htmlspecialchars($art['titre']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($art['date_creation'])) . "</td>";
            echo "<td><a href='articles.php?id={$art['id_article']}' class='btn' target='_blank'>👁️ Voir</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucun article dans la base</div>";
        echo "<a href='admin.php' class='btn'>➕ Créer un article</a>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
}

// ============================================
// LIENS UTILES
// ============================================
echo "<hr><h2>🔗 Liens utiles</h2>";
echo "<a href='index.php' class='btn'>🏠 Accueil</a>";
echo "<a href='admin.php' class='btn'>🎛️ Admin</a>";
echo "<a href='login.php' class='btn'>🔐 Login</a>";
echo "<a href='diagnostic_blog.php' class='btn'>🔄 Recharger</a>";

echo "<hr>";
echo "<p style='color: #e74c3c; font-weight: bold;'>⚠️ SUPPRIMEZ ce fichier après résolution des problèmes !</p>";
?>