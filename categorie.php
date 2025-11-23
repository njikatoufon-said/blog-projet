<?php
include("connexion.php");

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Si un ID de catégorie est fourni, on affiche les articles de cette catégorie
if (isset($_GET['id'])) {
    $id_categorie = $_GET['id'];

    // Récupération des infos de la catégorie
    $categorieQuery = $conn->prepare("SELECT * FROM categories WHERE id_categories = ?");
    $categorieQuery->execute([$id_categorie]);
    $categorie = $categorieQuery->fetch(PDO::FETCH_ASSOC);

    if(!$categorie) {
        die("Catégorie introuvable.");
    }

    // Récupération des articles de cette catégorie
    $articlesQuery = $conn->prepare("SELECT article.id_article, article.titre, article.slug, article.contenu,
                        article.image_couverture, article.date_creation, article.date_modification, article.vues,
                        utilisateur.nom_complet AS auteur, categories.nom AS categorie
                        FROM article 
                        JOIN utilisateur ON article.id_utilisateur = utilisateur.id_utilisateur
                        JOIN categories ON article.id_categories = categories.id_categories
                        WHERE article.id_categories = ?
                        ORDER BY article.date_creation DESC");

    $articlesQuery->execute([$id_categorie]);
    $articles = $articlesQuery->fetchAll(PDO::FETCH_ASSOC);

    $pageTitle = "Catégorie : " . $categorie['nom'];
    $showArticles = true;

} else {
    // Sinon, on affiche toutes les catégories disponibles
    $categoriesQuery = $conn->query("SELECT categories.*, 
                                     COUNT(article.id_article) as nb_articles
                                     FROM categories 
                                     LEFT JOIN article ON categories.id_categories = article.id_categories
                                     GROUP BY categories.id_categories
                                     ORDER BY categories.nom ASC");
    $categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Toutes les catégories";
    $showArticles = false;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .category-description {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 800px;
        }

        .category-count {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        main {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 20px;
        }

        /* Styles pour la liste des catégories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .category-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            border-left: 5px solid #667eea;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .category-card h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-card .description {
            color: #555;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .category-card .meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #7f8c8d;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
        }

        .article-count {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-weight: 600;
        }

        /* Styles pour les articles d'une catégorie */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        article {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        article:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .article-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .article-content {
            padding: 1.5rem;
        }

        .article-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .article-excerpt {
            color: #555;
            margin-bottom: 1rem;
            line-height: 1.8;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .read-more:hover {
            color: #764ba2;
        }

        .no-content {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-content h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
            }

            .categories-grid,
            .articles-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="accueil.php" class="back-link">
                ← Retour à l'accueil
            </a>
            
            <?php if ($showArticles): ?>
                <!-- En-tête pour une catégorie spécifique -->
                <h1 class="page-title">📂 <?php echo htmlspecialchars($categorie['nom']); ?></h1>
                <?php if (!empty($categorie['description'])): ?>
                    <p class="category-description"><?php echo htmlspecialchars($categorie['description']); ?></p>
                <?php endif; ?>
                <span class="category-count"><?php echo count($articles); ?> article<?php echo count($articles) > 1 ? 's' : ''; ?></span>
            <?php else: ?>
                <!-- En-tête pour la liste des catégories -->
                <h1 class="page-title">📚 Toutes les catégories</h1>
                <p class="category-description">Explorez nos articles par thématique</p>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <?php if ($showArticles): ?>
            <!-- Affichage des articles de la catégorie -->
            <?php if (count($articles) > 0): ?>
                <div class="articles-grid">
                    <?php foreach($articles as $article): ?>
                        <article>
                            <?php if (!empty($article['image_couverture'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image_couverture']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                                     class="article-image">
                            <?php else: ?>
                                <div class="article-image"></div>
                            <?php endif; ?>
                            
                            <div class="article-content">
                                <h2 class="article-title">
                                    <?php echo htmlspecialchars($article['titre']); ?>
                                </h2>
                                
                                <div class="article-meta">
                                    <span class="meta-item">
                                        👤 <?php echo htmlspecialchars($article['auteur'] ?? 'Anonyme'); ?>
                                    </span>
                                    <span class="meta-item">
                                        📅 <?php echo date('d/m/Y', strtotime($article['date_creation'])); ?>
                                    </span>
                                    <span class="meta-item">
                                        👁️ <?php echo number_format($article['vues'] ?? 0); ?> vues
                                    </span>
                                </div>
                                
                                <p class="article-excerpt">
                                    <?php 
                                        $contenu = strip_tags($article['contenu'] ?? '');
                                        echo htmlspecialchars(substr($contenu, 0, 150)) . '...';
                                    ?>
                                </p>
                                
                                <a href="articles.php?id=<?php echo $article['id_article']; ?>" class="read-more">
                                    Lire la suite →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-content">
                    <h3>Aucun article dans cette catégorie</h3>
                    <p>Cette catégorie ne contient pas encore d'articles.</p>
                    <p style="margin-top: 1rem;"><a href="accueil.php" class="read-more">← Retour à l'accueil</a></p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Affichage de toutes les catégories -->
            <?php if (count($categories) > 0): ?>
                <div class="categories-grid">
                    <?php foreach($categories as $cat): ?>
                        <a href="categorie.php?id=<?php echo $cat['id_categories']; ?>" class="category-card">
                            <h2>📂 <?php echo htmlspecialchars($cat['nom']); ?></h2>
                            
                            <?php if (!empty($cat['description'])): ?>
                                <p class="description"><?php echo htmlspecialchars($cat['description']); ?></p>
                            <?php else: ?>
                                <p class="description"><em>Aucune description pour cette catégorie.</em></p>
                            <?php endif; ?>
                            
                            <div class="meta">
                                <span class="article-count">
                                    <?php echo $cat['nb_articles']; ?> article<?php echo $cat['nb_articles'] > 1 ? 's' : ''; ?>
                                </span>
                                <span>Voir les articles →</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-content">
                    <h3>Aucune catégorie disponible</h3>
                    <p>Aucune catégorie n'a été créée dans la base de données.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>