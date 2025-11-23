<?php
include("connexion.php");

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Pagination
$articlesParPage = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articlesParPage;

// Filtrage par catégorie
$categorieFiltre = isset($_GET['categorie']) ? $_GET['categorie'] : null;

// Recherche
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : null;

// Construction de la requête
$whereConditions = [];
$params = [];

if ($categorieFiltre) {
    $whereConditions[] = "categories.id_categories = ?";
    $params[] = $categorieFiltre;
}

if ($recherche) {
    $whereConditions[] = "(article.titre LIKE ? OR article.contenu LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Comptage total des articles
$countQuery = $conn->prepare("SELECT COUNT(*) as total 
                               FROM article 
                               JOIN categories ON article.id_categories = categories.id_categories
                               $whereClause");
$countQuery->execute($params);
$totalArticles = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalArticles / $articlesParPage);

// Gestion de la suppression
if(isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $idSupprimer = $_GET['id'];
    
    // Récupération de l'image pour la supprimer du serveur
    $imageQuery = $conn->prepare("SELECT image_couverture FROM article WHERE id_article = ?");
    $imageQuery->execute([$idSupprimer]);
    $imageData = $imageQuery->fetch(PDO::FETCH_ASSOC);
    
    // Suppression de l'article
    $deleteQuery = $conn->prepare("DELETE FROM article WHERE id_article = ?");
    if($deleteQuery->execute([$idSupprimer])) {
        // Suppression de l'image si elle existe
        if(!empty($imageData['image_couverture']) && file_exists($imageData['image_couverture'])) {
            unlink($imageData['image_couverture']);
        }
        $message = "Article supprimé avec succès !";
        $messageType = "success";
    } else {
        $message = "Erreur lors de la suppression de l'article.";
        $messageType = "error";
    }
}

// Récupération des articles
$articlesQuery = $conn->prepare("SELECT article.id_article, article.titre, article.slug, article.contenu,
                                 article.image_couverture, article.date_creation, article.vues,
                                 utilisateur.nom_complet AS auteur, categories.nom AS categorie,
                                 categories.id_categories
                                 FROM article 
                                 JOIN utilisateur ON article.id_utilisateur = utilisateur.id_utilisateur
                                 JOIN categories ON article.id_categories = categories.id_categories
                                 $whereClause
                                 ORDER BY article.date_creation DESC
                                 LIMIT $articlesParPage OFFSET $offset");
$articlesQuery->execute($params);
$articles = $articlesQuery->fetchAll(PDO::FETCH_ASSOC);

// Récupération de toutes les catégories
$categoriesQuery = $conn->query("SELECT * FROM categories ORDER BY nom ASC");
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    <meta name="description" content="Découvrez tous nos articles et publications">
    
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
            padding: 3rem 0;
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
            margin-bottom: 2rem;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .search-filter-section {
            max-width: 1200px;
            margin: -2rem auto 3rem;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .search-filter-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-box {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            display: inline-block;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .search-btn {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: transform 0.3s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
        }

        main {
            max-width: 1200px;
            margin: 0 auto 3rem;
            padding: 0 20px;
        }

        .results-info {
            margin-bottom: 2rem;
            font-size: 1.1rem;
            color: #666;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .article-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .article-image-placeholder {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .article-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .article-category {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
            align-self: flex-start;
        }

        .article-title {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            color: #2c3e50;
            line-height: 1.3;
        }

        .article-excerpt {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
            flex: 1;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.6rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .pagination a:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .no-articles {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .no-articles h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .no-articles p {
            color: #666;
            font-size: 1.1rem;
        }

        .message {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .article-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ecf0f1;
        }

        .btn-action {
            flex: 1;
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-modifier {
            background: #ffc107;
            color: #000;
        }

        .btn-modifier:hover {
            background: #ffb300;
            transform: scale(1.05);
        }

        .btn-supprimer {
            background: #dc3545;
            color: white;
        }

        .btn-supprimer:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .btn-voir {
            background: #667eea;
            color: white;
            width: 100%;
            justify-content: center;
            margin-top: 0.5rem;
            padding: 0.8rem 1rem;
            font-weight: 600;
        }

        .btn-voir:hover {
            background: #5568d3;
            transform: scale(1.02);
        }

        /* Modal pour afficher l'article complet */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            max-width: 900px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px 12px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-header h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .modal-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .close {
            color: white;
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: transform 0.3s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .modal-contenu {
            font-size: 1.1rem;
            line-height: 1.9;
            color: #2c3e50;
            white-space: pre-wrap;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            header h1 {
                font-size: 2rem;
            }

            .search-filter-container {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .filter-box {
                width: 100%;
            }

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
            <h1>📚 Tous nos articles</h1>
            <p>Découvrez notre collection d'articles et publications</p>
        </div>
    </header>

    <?php if(isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <section class="search-filter-section">
        <form method="GET" action="" class="search-filter-container">
            <div class="search-box">
                <input type="text" 
                       name="recherche" 
                       placeholder="🔍 Rechercher un article..." 
                       value="<?php echo htmlspecialchars($recherche ?? ''); ?>">
            </div>
            
            <div class="filter-box">
                <a href="articles.php" class="filter-btn <?php echo !$categorieFiltre ? 'active' : ''; ?>">
                    Toutes
                </a>
                <?php foreach($categories as $cat): ?>
                    <a href="?categorie=<?php echo $cat['id_categories']; ?>" 
                       class="filter-btn <?php echo $categorieFiltre == $cat['id_categories'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['nom']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="search-btn">Rechercher</button>
        </form>
    </section>

    <main>
        <?php if ($recherche || $categorieFiltre): ?>
            <div class="results-info">
                <?php echo $totalArticles; ?> article<?php echo $totalArticles > 1 ? 's' : ''; ?> trouvé<?php echo $totalArticles > 1 ? 's' : ''; ?>
            </div>
        <?php endif; ?>

        <?php if (count($articles) > 0): ?>
            <div class="articles-grid">
                <?php foreach($articles as $article): ?>
                    <div class="article-card">
                        <?php if (!empty($article['image_couverture'])): ?>
                            <img src="<?php echo htmlspecialchars($article['image_couverture']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                                 class="article-image">
                        <?php else: ?>
                            <div class="article-image-placeholder">📄</div>
                        <?php endif; ?>
                        
                        <div class="article-content">
                            <span class="article-category">
                                <?php echo htmlspecialchars($article['categorie']); ?>
                            </span>
                            
                            <h2 class="article-title">
                                <?php echo htmlspecialchars($article['titre']); ?>
                            </h2>
                            
                            <p class="article-excerpt">
                                <?php echo htmlspecialchars(substr(strip_tags($article['contenu']), 0, 150)) . '...'; ?>
                            </p>
                            
                            <div class="article-meta">
                                <span class="meta-item">
                                    📅 <?php echo date('d/m/Y', strtotime($article['date_creation'])); ?>
                                </span>
                                <span class="meta-item">
                                    👁️ <?php echo number_format($article['vues']); ?>
                                </span>
                            </div>

                            <div class="article-actions">
                                <a href="modifier_article.php?id=<?php echo $article['id_article']; ?>" 
                                   class="btn-action btn-modifier">
                                    ✏️ Modifier
                                </a>
                                <a href="?action=supprimer&id=<?php echo $article['id_article']; ?><?php echo $categorieFiltre ? '&categorie=' . $categorieFiltre : ''; ?><?php echo $recherche ? '&recherche=' . urlencode($recherche) : ''; ?>&page=<?php echo $page; ?>" 
                                   class="btn-action btn-supprimer"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                    🗑️ Supprimer
                                </a>
                            </div>

                            <button type="button" 
                                    class="btn-action btn-voir"
                                    onclick="afficherArticle(
                                        '<?php echo htmlspecialchars($article['titre'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($article['auteur'] ?? 'Anonyme', ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($article['categorie'], ENT_QUOTES); ?>',
                                        '<?php echo date('d/m/Y', strtotime($article['date_creation'])); ?>',
                                        '<?php echo number_format($article['vues']); ?>',
                                        '<?php echo htmlspecialchars($article['image_couverture'] ?? '', ENT_QUOTES); ?>',
                                        `<?php echo htmlspecialchars($article['contenu'], ENT_QUOTES); ?>`
                                    )">
                                📖 Lire plus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $categorieFiltre ? '&categorie=' . $categorieFiltre : ''; ?><?php echo $recherche ? '&recherche=' . urlencode($recherche) : ''; ?>">
                            ← Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $categorieFiltre ? '&categorie=' . $categorieFiltre : ''; ?><?php echo $recherche ? '&recherche=' . urlencode($recherche) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $categorieFiltre ? '&categorie=' . $categorieFiltre : ''; ?><?php echo $recherche ? '&recherche=' . urlencode($recherche) : ''; ?>">
                            Suivant →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-articles">
                <h2>😔 Aucun article trouvé</h2>
                <p>Essayez de modifier vos critères de recherche ou consultez toutes les catégories.</p>
                <br>
                <a href="articles.php" class="search-btn" style="display: inline-block; text-decoration: none;">
                    Voir tous les articles
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal pour afficher l'article complet -->
    <div id="articleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="fermerModal()">&times;</span>
                <h2 id="modal-titre"></h2>
                <div class="modal-meta">
                    <span class="meta-item">
                        👤 <span id="modal-auteur"></span>
                    </span>
                    <span class="category-badge" id="modal-categorie"></span>
                    <span class="meta-item">
                        📅 <span id="modal-date"></span>
                    </span>
                    <span class="meta-item">
                        👁️ <span id="modal-vues"></span> vues
                    </span>
                </div>
            </div>
            <div class="modal-body">
                <img id="modal-image" class="modal-image" style="display: none;">
                <div id="modal-contenu" class="modal-contenu"></div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>

    <script>
        // Fonction pour afficher l'article dans le modal
        function afficherArticle(titre, auteur, categorie, date, vues, image, contenu) {
            document.getElementById('modal-titre').textContent = titre;
            document.getElementById('modal-auteur').textContent = auteur;
            document.getElementById('modal-categorie').textContent = categorie;
            document.getElementById('modal-date').textContent = date;
            document.getElementById('modal-vues').textContent = vues;
            document.getElementById('modal-contenu').textContent = contenu;
            
            const modalImage = document.getElementById('modal-image');
            if (image && image.trim() !== '') {
                modalImage.src = image;
                modalImage.style.display = 'block';
            } else {
                modalImage.style.display = 'none';
            }
            
            document.getElementById('articleModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Empêcher le scroll du body
        }

        // Fonction pour fermer le modal
        function fermerModal() {
            document.getElementById('articleModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Réactiver le scroll du body
        }

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('articleModal');
            if (event.target == modal) {
                fermerModal();
            }
        }

        // Fermer le modal avec la touche Échap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                fermerModal();
            }
        });
    </script>
</body>
</html>