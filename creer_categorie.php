<?php
session_start();
include("connexion.php");

$message = '';
$error = '';

// Traitement de l'ajout de catégorie
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'ajouter') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($nom)) {
        $error = "Le nom de la catégorie est obligatoire";
    } else {
        // Vérifier si la catégorie existe déjà
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE nom = :nom");
        $checkStmt->execute([':nom' => $nom]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Cette catégorie existe déjà";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO categories (nom, description) VALUES (:nom, :description)");
                $stmt->execute([
                    ':nom' => $nom,
                    ':description' => $description
                ]);
                
                $message = "Catégorie créée avec succès !";
            } catch (PDOException $e) {
                $error = "Erreur lors de la création : " . $e->getMessage();
            }
        }
    }
}

// Traitement de la suppression
if (isset($_GET['supprimer'])) {
    $id = $_GET['supprimer'];
    
    try {
        // Vérifier si des articles utilisent cette catégorie
        $checkArticles = $conn->prepare("SELECT COUNT(*) FROM article WHERE id_categories = :id");
        $checkArticles->execute([':id' => $id]);
        
        if ($checkArticles->fetchColumn() > 0) {
            $error = "Impossible de supprimer cette catégorie car elle contient des articles";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id_categories = :id");
            $stmt->execute([':id' => $id]);
            $message = "Catégorie supprimée avec succès !";
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer toutes les catégories
$categoriesQuery = $conn->query("SELECT c.*, COUNT(a.id_article) as nb_articles 
                                 FROM categories c 
                                 LEFT JOIN article a ON c.id_categories = a.id_categories 
                                 GROUP BY c.id_categories 
                                 ORDER BY c.nom ASC");
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Catégories</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
            font-size: 1.1rem;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 2.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .form-section {
            background: #f0fdf4;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 2px solid #bbf7d0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .required {
            color: #ef4444;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .categories-list {
            background: #f9fafb;
            padding: 2rem;
            border-radius: 15px;
        }

        .category-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .category-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .category-info {
            flex: 1;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .category-description {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .category-stats {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .category-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-delete {
            background: #fecaca;
            color: #991b1b;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .content {
                padding: 1.5rem;
            }

            .category-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .category-actions {
                width: 100%;
            }

            .btn-delete {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="accueil.php" class="back-link">← Retour à l'accueil</a>

        <div class="card">
            <div class="header">
                <h1>📁 Gérer les Catégories</h1>
                <p>Organisez vos articles avec des catégories</p>
            </div>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        ✓ <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Formulaire d'ajout -->
                <div class="form-section">
                    <h2 class="section-title">➕ Ajouter une nouvelle catégorie</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="form-group">
                            <label for="nom">
                                Nom de la catégorie <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="nom" 
                                name="nom" 
                                placeholder="Ex: Technologie, Sport, Culture..."
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="description">
                                Description (optionnel)
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Décrivez cette catégorie..."
                            ></textarea>
                        </div>

                        <button type="submit" class="btn-primary">
                            ✓ Créer la catégorie
                        </button>
                    </form>
                </div>

                <!-- Liste des catégories -->
                <div class="categories-list">
                    <h2 class="section-title">📋 Catégories existantes (<?php echo count($categories); ?>)</h2>
                    
                    <?php if (count($categories) > 0): ?>
                        <?php foreach($categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <div class="category-name">
                                        <?php echo htmlspecialchars($cat['nom']); ?>
                                    </div>
                                    <?php if (!empty($cat['description'])): ?>
                                        <div class="category-description">
                                            <?php echo htmlspecialchars($cat['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="category-stats">
                                        📄 <?php echo $cat['nb_articles']; ?> article(s)
                                    </span>
                                </div>
                                
                                <div class="category-actions">
                                    <a href="?supprimer=<?php echo $cat['id_categories']; ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')">
                                        🗑️ Supprimer
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📁</div>
                            <h3>Aucune catégorie</h3>
                            <p>Créez votre première catégorie pour commencer à organiser vos articles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>