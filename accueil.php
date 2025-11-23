<?php
session_start();
include("connexion.php");

$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

$articlesQuery = $conn->query("SELECT article.id_article, article.titre, article.slug, article.contenu,
                    article.image_couverture, article.date_creation, article.date_modification, article.vues,
                    utilisateur.nom_complet AS auteur, categories.nom AS categorie
                    FROM article 
                    JOIN utilisateur ON article.id_utilisateur = utilisateur.id_utilisateur
                    JOIN categories ON article.id_categories = categories.id_categories
                    ORDER BY article.date_creation DESC");

$articles = $articlesQuery->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur est connecté (adapter selon votre système)
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['id_utilisateur']) || isset($_SESSION['utilisateur']);

// Pour tester, vous pouvez temporairement forcer l'affichage :
// $isLoggedIn = true;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
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

        .nav-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .nav-main {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .nav-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .admin-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            border: 2px solid white;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .admin-btn:hover {
            background: white;
            color: #667eea !important;
            opacity: 1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .site-title {
            text-align: center;
        }

        .site-title h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .site-tagline {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Section des boutons d'action */
        .action-buttons {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .action-card.purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-card.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .action-description {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        main {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: #2c3e50;
            text-align: center;
        }

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

        .category-badge {
            background: #667eea;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
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

        .no-articles {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 968px) {
            .site-title h1 {
                font-size: 2rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-main {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-right {
                flex-wrap: wrap;
                justify-content: center;
            }

            .articles-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <nav class="nav-links">
                <div class="nav-main">
                    <a href="accueil.php">Accueil</a>
                    <a href="propos.php">À propos</a>
                    <a href="contact.php">Contact</a>
                    <a href="newsletter.php">Newsletter</a>
                </div>
                <div class="nav-right">
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="admin-btn">
                            📊 Dashboard
                        </a>
                        <a href="logout.php" class="admin-btn">
                            🚪 Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="admin-btn">
                            🔐 Connexion
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
            
            <div class="site-title">
                <h1><?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></h1>
                <p class="site-tagline"><?php echo htmlspecialchars($settings['cle'] ?? 'Votre description ici'); ?></p>
            </div>
        </div>
    </header>

    <!-- Boutons d'action -->
    <div class="action-buttons">
        <a href="creer_articles.php" class="action-card purple">
            <div class="action-icon">📝</div>
            <h3 class="action-title">Créer un Article</h3>
            <p class="action-description">Rédigez et publiez un nouvel article sur votre blog</p>
        </a>
        
        
    </div>

    <main>
        <h2 class="section-title">Derniers Articles</h2>
        
        <div class="articles-grid">
            <?php if (count($articles) > 0): ?>
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
                                <?php echo htmlspecialchars($article['titre'] ?? 'Sans titre'); ?>
                            </h2>
                            
                            <div class="article-meta">
                                <span class="meta-item">
                                    👤 <?php echo htmlspecialchars($article['auteur'] ?? 'Anonyme'); ?>
                                </span>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($article['categorie'] ?? 'Non catégorisé'); ?>
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
            <?php else: ?>
                <div class="no-articles">
                    <h3>Aucun article pour le moment</h3>
                    <p>Revenez bientôt pour découvrir nos nouveaux contenus !</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>