<?php
session_start();
include("connexion.php");

// Récupérer les catégories
$categoriesQuery = $conn->query("SELECT * FROM categories ORDER BY nom ASC");
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'ID de l'utilisateur connecté (adapter selon votre système de session)
$userId = $_SESSION['user_id'] ?? $_SESSION['id_utilisateur'] ?? 1; // Par défaut ID 1 si non connecté

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $id_categories = $_POST['id_categories'] ?? '';
    $image_couverture = trim($_POST['image_couverture'] ?? '');
    
    // Validation
    if (empty($titre)) {
        $error = "Le titre est obligatoire";
    } elseif (empty($contenu)) {
        $error = "Le contenu est obligatoire";
    } elseif (empty($id_categories)) {
        $error = "Veuillez sélectionner une catégorie";
    } else {
        // Générer un slug à partir du titre
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre), '-'));
        
        try {
            $stmt = $conn->prepare("INSERT INTO article (titre, slug, contenu, image_couverture, id_utilisateur, id_categories, date_creation, vues) 
                                    VALUES (:titre, :slug, :contenu, :image_couverture, :id_utilisateur, :id_categories, NOW(), 0)");
            
            $stmt->execute([
                ':titre' => $titre,
                ':slug' => $slug,
                ':contenu' => $contenu,
                ':image_couverture' => $image_couverture,
                ':id_utilisateur' => $userId,
                ':id_categories' => $id_categories
            ]);
            
            $message = "Article créé avec succès !";
            
            // Redirection après 2 secondes
            header("refresh:2;url=accueil.php");
        } catch (PDOException $e) {
            $error = "Erreur lors de la création de l'article : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Article</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .form-container {
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
        input[type="url"],
        select,
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
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 250px;
            line-height: 1.6;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        button {
            flex: 1;
            padding: 1rem;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .preview-section {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
        }

        .preview-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .image-preview {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 0.5rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="accueil.php" class="back-link">← Retour à l'accueil</a>
            <h1>📝 Créer un Article</h1>
            <p>Rédigez et publiez votre nouvel article</p>
        </div>

        <div class="form-container">
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="titre">
                        Titre de l'article <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="titre" 
                        name="titre" 
                        placeholder="Entrez le titre de votre article"
                        value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="id_categories">
                        Catégorie <span class="required">*</span>
                    </label>
                    <select id="id_categories" name="id_categories" required>
                        <option value="">-- Sélectionnez une catégorie --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id_categories']; ?>"
                                <?php echo (isset($_POST['id_categories']) && $_POST['id_categories'] == $cat['id_categories']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image_couverture">
                        URL de l'image de couverture
                    </label>
                    <input 
                        type="url" 
                        id="image_couverture" 
                        name="image_couverture" 
                        placeholder="https://exemple.com/image.jpg"
                        value="<?php echo htmlspecialchars($_POST['image_couverture'] ?? ''); ?>"
                        onchange="previewImage(this.value)"
                    >
                    
                    <div id="imagePreview" class="preview-section" style="display: none;">
                        <div class="preview-title">Aperçu de l'image :</div>
                        <img id="previewImg" class="image-preview" src="" alt="Aperçu">
                    </div>
                </div>

                <div class="form-group">
                    <label for="contenu">
                        Contenu de l'article <span class="required">*</span>
                    </label>
                    <textarea 
                        id="contenu" 
                        name="contenu" 
                        placeholder="Rédigez le contenu de votre article..."
                        required
                    ><?php echo htmlspecialchars($_POST['contenu'] ?? ''); ?></textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">
                        ✓ Publier l'article
                    </button>
                    <button type="reset" class="btn-secondary">
                        ↺ Réinitialiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(url) {
            const previewDiv = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (url && url.trim() !== '') {
                previewImg.src = url;
                previewDiv.style.display = 'block';
                
                previewImg.onerror = function() {
                    previewDiv.style.display = 'none';
                };
            } else {
                previewDiv.style.display = 'none';
            }
        }

        // Prévisualiser l'image au chargement si elle existe
        window.onload = function() {
            const imageInput = document.getElementById('image_couverture');
            if (imageInput.value) {
                previewImage(imageInput.value);
            }
        };
    </script>
</body>
</html>