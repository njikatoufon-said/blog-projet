<?php
session_start();
include("connexion.php");

// Vérification de la connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération de l'ID de la catégorie
if (!isset($_GET['id'])) {
    header('Location: admin.php?error=no_category_id');
    exit();
}

$category_id = intval($_GET['id']);

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($nom)) {
        $error = "Le nom de la catégorie est obligatoire.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE categories 
                                   SET nom = ?, description = ? 
                                   WHERE id_categories = ?");
            
            $stmt->execute([$nom, $description, $category_id]);
            
            header('Location: admin.php#categories&success=category_updated');
            exit();
            
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour de la catégorie.";
        }
    }
}

// Récupération de la catégorie
$categoryQuery = $conn->prepare("SELECT categories.*, COUNT(article.id_article) as nb_articles
                                 FROM categories 
                                 LEFT JOIN article ON categories.id_categories = article.categorie_id 
                                 WHERE categories.id_categories = ?
                                 GROUP BY categories.id_categories");
$categoryQuery->execute([$category_id]);
$category = $categoryQuery->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: admin.php?error=category_not_found');
    exit();
}

$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la catégorie - Admin</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            color: #2c3e50;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-menu {
            margin-top: 2rem;
        }

        .menu-item {
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: background 0.3s;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2.5rem;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .info-box {
            background: #e3f2fd;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #2196f3;
            color: #1565c0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .required {
            color: #e74c3c;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .char-count {
            text-align: right;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 1rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>🎛️ Admin Panel</h1>
                <p><?php echo htmlspecialchars($_SESSION['admin_nom'] ?? 'Administrateur'); ?></p>
            </div>
            
            <nav class="sidebar-menu">
                <a href="admin.php" class="menu-item">
                    <span>📊</span> Tableau de bord
                </a>
                <a href="admin.php#articles" class="menu-item">
                    <span>📝</span> Articles
                </a>
                <a href="admin.php#categories" class="menu-item">
                    <span>📂</span> Catégories
                </a>
                <a href="admin_messages.php" class="menu-item">
                    <span>📧</span> Messages
                </a>
                <a href="admin_newsletter.php" class="menu-item">
                    <span>📬</span> Newsletter
                </a>
                <a href="index.php" class="menu-item" target="_blank">
                    <span>🌐</span> Voir le site
                </a>
                <a href="logout.php" class="menu-item">
                    <span>🚪</span> Déconnexion
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>✏️ Modifier la catégorie</h2>
                <a href="categorie.php?id=<?php echo $category_id; ?>" target="_blank" class="btn btn-secondary">
                    👁️ Voir la catégorie
                </a>
            </div>

            <div class="card">
                <?php if (isset($error)): ?>
                    <div class="alert">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    ℹ️ Cette catégorie contient actuellement <strong><?php echo $category['nb_articles']; ?></strong> article<?php echo $category['nb_articles'] > 1 ? 's' : ''; ?>.
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Nom de la catégorie <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" 
                               value="<?php echo htmlspecialchars($category['nom']); ?>" 
                               required maxlength="100">
                        <div class="char-count" id="nom-count">0/100 caractères</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Décrivez le contenu de cette catégorie..."><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                        <div class="char-count" id="desc-count">0 caractères</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            💾 Enregistrer les modifications
                        </button>
                        <a href="admin.php#categories" class="btn btn-secondary">
                            ❌ Annuler
                        </a>
                        <?php if ($category['nb_articles'] == 0): ?>
                            <button type="button" onclick="confirmDelete()" class="btn btn-danger" style="margin-left: auto;">
                                🗑️ Supprimer la catégorie
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-danger" style="margin-left: auto;" disabled title="Impossible de supprimer une catégorie contenant des articles">
                                🗑️ Supprimer (<?php echo $category['nb_articles']; ?> articles)
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($category['nb_articles'] == 0): ?>
                    <form id="deleteForm" method="POST" action="admin.php" style="display: none;">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Compteur de caractères pour le nom
        const nomInput = document.getElementById('nom');
        const nomCount = document.getElementById('nom-count');
        
        function updateNomCount() {
            const count = nomInput.value.length;
            nomCount.textContent = count + '/100 caractères';
            nomCount.style.color = count > 90 ? '#e74c3c' : '#7f8c8d';
        }
        
        nomInput.addEventListener('input', updateNomCount);
        updateNomCount();

        // Compteur de caractères pour la description
        const descInput = document.getElementById('description');
        const descCount = document.getElementById('desc-count');
        
        function updateDescCount() {
            const count = descInput.value.length;
            descCount.textContent = count.toLocaleString() + ' caractères';
        }
        
        descInput.addEventListener('input', updateDescCount);
        updateDescCount();

        // Confirmation de suppression
        function confirmDelete() {
            if (confirm('⚠️ Êtes-vous vraiment sûr de vouloir supprimer cette catégorie ?\n\nCette action est irréversible !')) {
                document.getElementById('deleteForm').submit();
            }
        }

        // Confirmation avant de quitter avec modifications non sauvegardées
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>