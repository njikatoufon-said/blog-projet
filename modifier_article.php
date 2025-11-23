<?php
include("connexion.php");

// Vérification de l'ID de l'article
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: articles.php");
    exit();
}

$id = $_GET['id'];

// Traitement du formulaire de modification
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $contenu = trim($_POST['contenu']);
    $id_categorie = $_POST['id_categorie'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
    
    // Gestion de l'image
    $image_couverture = $_POST['ancienne_image'];
    
    if(isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
        $dossier_upload = "uploads/";
        
        // Créer le dossier s'il n'existe pas
        if(!is_dir($dossier_upload)) {
            mkdir($dossier_upload, 0777, true);
        }
        
        $extension = pathinfo($_FILES['image_couverture']['name'], PATHINFO_EXTENSION);
        $nom_fichier = uniqid() . '.' . $extension;
        $chemin_fichier = $dossier_upload . $nom_fichier;
        
        if(move_uploaded_file($_FILES['image_couverture']['tmp_name'], $chemin_fichier)) {
            // Supprimer l'ancienne image si elle existe
            if(!empty($image_couverture) && file_exists($image_couverture)) {
                unlink($image_couverture);
            }
            $image_couverture = $chemin_fichier;
        }
    }
    
    // Mise à jour de l'article
    $updateQuery = $conn->prepare("UPDATE article 
                                    SET titre = ?, 
                                        slug = ?, 
                                        contenu = ?, 
                                        id_categories = ?, 
                                        image_couverture = ?,
                                        date_modification = NOW()
                                    WHERE id_article = ?");
    
    if($updateQuery->execute([$titre, $slug, $contenu, $id_categorie, $image_couverture, $id])) {
        $message = "Article modifié avec succès !";
        $messageType = "success";
    } else {
        $message = "Erreur lors de la modification de l'article.";
        $messageType = "error";
    }
}

// Récupération de l'article à modifier
$articleQuery = $conn->prepare("SELECT * FROM article WHERE id_article = ?");
$articleQuery->execute([$id]);
$article = $articleQuery->fetch(PDO::FETCH_ASSOC);

if(!$article) {
    header("Location: articles.php");
    exit();
}

// Récupération de toutes les catégories
$categoriesQuery = $conn->query("SELECT * FROM categories ORDER BY nom ASC");
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'article - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
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
            max-width: 900px;
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

        header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        main {
            max-width: 900px;
            margin: 2rem auto 3rem;
            padding: 0 20px;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
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

        .form-container {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            min-height: 300px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            padding: 0.5rem;
        }

        .image-preview {
            margin-top: 1rem;
            max-width: 300px;
        }

        .image-preview img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .image-preview p {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .required {
            color: #dc3545;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 2rem 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="articles.php" class="back-link">
                ← Retour aux articles
            </a>
            <h1>✏️ Modifier l'article</h1>
        </div>
    </header>

    <main>
        <?php if(isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titre">
                        Titre de l'article <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="titre" 
                           name="titre" 
                           value="<?php echo htmlspecialchars($article['titre']); ?>"
                           required 
                           placeholder="Entrez le titre de l'article">
                </div>

                <div class="form-group">
                    <label for="id_categorie">
                        Catégorie <span class="required">*</span>
                    </label>
                    <select id="id_categorie" name="id_categorie" required>
                        <option value="">-- Sélectionnez une catégorie --</option>
                        <?php foreach($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id_categories']; ?>"
                                    <?php echo $article['id_categories'] == $categorie['id_categories'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contenu">
                        Contenu de l'article <span class="required">*</span>
                    </label>
                    <textarea id="contenu" 
                              name="contenu" 
                              required 
                              placeholder="Rédigez le contenu de votre article..."><?php echo htmlspecialchars($article['contenu']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image_couverture">
                        Image de couverture
                    </label>
                    <input type="file" 
                           id="image_couverture" 
                           name="image_couverture" 
                           accept="image/*">
                    
                    <?php if(!empty($article['image_couverture'])): ?>
                        <div class="image-preview">
                            <p><strong>Image actuelle :</strong></p>
                            <img src="<?php echo htmlspecialchars($article['image_couverture']); ?>" 
                                 alt="Image actuelle">
                        </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="ancienne_image" value="<?php echo htmlspecialchars($article['image_couverture']); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        💾 Enregistrer les modifications
                    </button>
                    <a href="articles.php" class="btn btn-secondary">
                        ❌ Annuler
                    </a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>

    <script>
        // Prévisualisation de la nouvelle image
        document.getElementById('image_couverture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        document.getElementById('image_couverture').parentNode.appendChild(preview);
                    }
                    preview.innerHTML = `
                        <p><strong>Nouvelle image :</strong></p>
                        <img src="${e.target.result}" alt="Nouvelle image">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>