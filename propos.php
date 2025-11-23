<?php
include("connexion.php");

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Statistiques du blog
$statsQuery = $conn->query("SELECT 
    (SELECT COUNT(*) FROM article) as total_articles,
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM utilisateur) as total_authors,
    (SELECT SUM(vues) FROM article) as total_views");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

// Récupération des auteurs/contributeurs
$authorsQuery = $conn->query("SELECT utilisateur.*, COUNT(article.id_article) as nb_articles
                              FROM utilisateur 
                              LEFT JOIN article ON utilisateur.id_utilisateur = article.id_utilisateur
                              GROUP BY utilisateur.id_utilisateur
                              ORDER BY nb_articles DESC
                              LIMIT 6");
$authors = $authorsQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
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

        .page-header {
            text-align: center;
        }

        .page-title {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.3rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }

        main {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 20px;
        }

        .about-section {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }

        .about-section h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .about-section p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 3rem 0;
        }

        .mission-card,
        .vision-card {
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .mission-card {
            border-left: 5px solid #667eea;
        }

        .vision-card {
            border-left: 5px solid #764ba2;
        }

        .mission-card h3,
        .vision-card h3 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .values-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }

        .value-item {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .value-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .value-item h4 {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
        }

        .value-item p {
            color: #555;
            font-size: 1rem;
        }

        .team-section {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-member {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .member-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .member-name {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .member-role {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .member-stats {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .timeline-section {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 3rem;
        }

        .timeline {
            position: relative;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
        }

        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            width: 45%;
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .timeline-date {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .timeline-title {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .timeline-description {
            color: #555;
        }

        .timeline-marker {
            width: 20px;
            height: 20px;
            background: #667eea;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 0 0 3px #667eea;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            margin-top: 3rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 968px) {
            .page-title {
                font-size: 2rem;
            }

            .mission-vision {
                grid-template-columns: 1fr;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item {
                flex-direction: row !important;
            }

            .timeline-content {
                width: calc(100% - 60px);
                margin-left: 60px;
            }

            .timeline-marker {
                left: 20px;
            }

            .cta-buttons {
                flex-direction: column;
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
            
            <div class="page-header">
                <h1 class="page-title">✨ À propos de nous</h1>
                <p class="page-subtitle">
                    Découvrez notre histoire, notre mission et l'équipe passionnée qui se cache derrière <?php echo htmlspecialchars($settings['nom_site'] ?? 'ce blog'); ?>
                </p>
            </div>
        </div>
    </header>

    <main>
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?php echo number_format($stats['total_articles']); ?></div>
                <div class="stat-label">Articles publiés</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo number_format($stats['total_authors']); ?></div>
                <div class="stat-label">Contributeurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📂</div>
                <div class="stat-number"><?php echo number_format($stats['total_categories']); ?></div>
                <div class="stat-label">Catégories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-number"><?php echo number_format($stats['total_views']); ?></div>
                <div class="stat-label">Vues totales</div>
            </div>
        </div>

        <!-- Notre histoire -->
        <div class="about-section">
            <h2>📖 Notre histoire</h2>
            <p>
                Fondé en 2024, <strong><?php echo htmlspecialchars($settings['nom_site'] ?? 'notre blog'); ?></strong> est né de la passion de partager des connaissances et d'inspirer une communauté de lecteurs curieux. Ce qui a commencé comme un simple projet personnel s'est transformé en une plateforme dynamique où les idées prennent vie.
            </p>
            <p>
                Basé à Douala, au Cameroun, nous nous efforçons de créer un espace où la qualité du contenu prime sur la quantité, où chaque article est soigneusement rédigé pour apporter de la valeur à nos lecteurs.
            </p>
            <p>
                Au fil des années, nous avons construit une communauté engagée de lecteurs qui partagent notre passion pour l'apprentissage et la découverte. Notre engagement reste le même : produire du contenu authentique, informatif et inspirant.
            </p>
        </div>

        <!-- Mission et Vision -->
        <div class="mission-vision">
            <div class="mission-card">
                <h3>🎯 Notre mission</h3>
                <p>
                    Créer un espace d'apprentissage et de partage où chacun peut trouver des informations de qualité, des perspectives nouvelles et des idées inspirantes. Nous croyons au pouvoir de l'éducation et à la force d'une communauté unie par la curiosité.
                </p>
            </div>
            
            <div class="vision-card">
                <h3>🔭 Notre vision</h3>
                <p>
                    Devenir la référence incontournable dans notre domaine, en continuant à innover et à fournir du contenu qui fait la différence. Nous aspirons à construire un pont entre l'expertise et l'accessibilité.
                </p>
            </div>
        </div>

        <!-- Nos valeurs -->
        <div class="about-section">
            <h2>💎 Nos valeurs</h2>
            <div class="values-list">
                <div class="value-item">
                    <div class="value-icon">✨</div>
                    <h4>Qualité</h4>
                    <p>Chaque article est soigneusement recherché et rédigé avec passion</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">🤝</div>
                    <h4>Authenticité</h4>
                    <p>Nous restons fidèles à nos convictions et transparents avec notre communauté</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">🌱</div>
                    <h4>Innovation</h4>
                    <p>Toujours à la recherche de nouvelles façons d'apporter de la valeur</p>
                </div>
                <div class="value-item">
                    <div class="value-icon">🌍</div>
                    <h4>Accessibilité</h4>
                    <p>Le savoir doit être accessible à tous, partout</p>
                </div>
            </div>
        </div>

        <!-- Notre équipe -->
        <?php if (count($authors) > 0): ?>
        <div class="team-section">
            <h2>👥 Notre équipe</h2>
            <p style="text-align: center; color: #7f8c8d; margin-bottom: 2rem;">
                Des experts passionnés qui donnent vie à notre vision
            </p>
            
            <div class="team-grid">
                <?php foreach($authors as $author): ?>
                    <div class="team-member">
                        <div class="member-avatar">👤</div>
                        <div class="member-name"><?php echo htmlspecialchars($author['nom_complet']); ?></div>
                        <div class="member-role">Contributeur</div>
                        <div class="member-stats">
                            <?php echo $author['nb_articles']; ?> article<?php echo $author['nb_articles'] > 1 ? 's' : ''; ?> publié<?php echo $author['nb_articles'] > 1 ? 's' : ''; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="timeline-section">
            <h2 style="text-align: center; color: #2c3e50; margin-bottom: 3rem;">📅 Notre parcours</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-date">2024</div>
                        <div class="timeline-title">Lancement du blog</div>
                        <div class="timeline-description">
                            Début de l'aventure avec nos premiers articles et une vision claire
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-date">Q2 2024</div>
                        <div class="timeline-title">Croissance de la communauté</div>
                        <div class="timeline-description">
                            Atteinte de <?php echo number_format($stats['total_views']); ?> vues et création d'une newsletter
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div class="timeline-date">Aujourd'hui</div>
                        <div class="timeline-title">En pleine expansion</div>
                        <div class="timeline-description">
                            <?php echo $stats['total_articles']; ?> articles publiés et une communauté engagée
                        </div>
                    </div>
                    <div class="timeline-marker"></div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="cta-section">
            <h2>🚀 Rejoignez l'aventure</h2>
            <p>Faites partie de notre communauté et ne manquez aucun de nos contenus</p>
            <div class="cta-buttons">
                <a href="newsletter.php" class="btn btn-primary">📬 S'abonner à la newsletter</a>
                <a href="contact.php" class="btn btn-secondary">📧 Nous contacter</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>