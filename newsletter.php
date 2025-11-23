<?php
include("connexion.php");

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Variables pour les messages
$success = false;
$error = false;
$errorMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    
    // Validation
    if (empty($email)) {
        $error = true;
        $errorMessage = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMessage = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'email existe déjà
        $checkQuery = $conn->prepare("SELECT id_news FROM newsletter_s WHERE email = ?");
        $checkQuery->execute([$email]);
        
        if ($checkQuery->rowCount() > 0) {
            $error = true;
            $errorMessage = "Cette adresse email est déjà inscrite à notre newsletter.";
        } else {
            // Insertion dans la base de données
            try {
                $stmt = $conn->prepare("INSERT INTO newsletter_s (email, nom, prenom, date_inscription, statut) 
                                        VALUES (?, ?, ?, NOW(), 'actif')");
                $stmt->execute([$email, $nom, $prenom]);
                
                $success = true;
                
                // Réinitialisation des variables
                $email = $nom = $prenom = '';
                
            } catch (PDOException $e) {
                $error = true;
                $errorMessage = "Une erreur s'est produite. Veuillez réessayer plus tard.";
            }
        }
    }
}

// Récupération du nombre d'abonnés
$countQuery = $conn->query("SELECT COUNT(*) as total FROM newsletter_s WHERE statut = 'actif'");
$subscriberCount = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            padding: 2rem 0;
            color: white;
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

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 20px;
        }

        .newsletter-container {
            max-width: 600px;
            width: 100%;
        }

        .newsletter-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .newsletter-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .subtitle {
            color: #7f8c8d;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .benefits {
            text-align: left;
            margin: 2rem 0;
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .benefit-item:last-child {
            margin-bottom: 0;
        }

        .benefit-icon {
            font-size: 1.5rem;
            min-width: 30px;
        }

        .benefit-text {
            color: #555;
            line-height: 1.6;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
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
        input[type="email"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .name-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-subscribe {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.2rem 3rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-subscribe:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-subscribe:active {
            transform: translateY(0);
        }

        .subscriber-count {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .subscriber-count strong {
            color: #667eea;
            font-size: 1.5rem;
        }

        .privacy-note {
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #95a5a6;
            line-height: 1.6;
        }

        .unsubscribe-link {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ecf0f1;
        }

        .unsubscribe-link a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .unsubscribe-link a:hover {
            text-decoration: underline;
        }

        footer {
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .newsletter-card {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .name-group {
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
        </div>
    </header>

    <main>
        <div class="newsletter-container">
            <div class="newsletter-card">
                <div class="newsletter-icon">📬</div>
                
                <h1>Abonnez-vous à notre Newsletter</h1>
                <p class="subtitle">
                    Recevez nos derniers articles et actualités directement dans votre boîte mail
                </p>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span>✅</span>
                        <div>
                            <strong>Merci de votre inscription !</strong><br>
                            Vous recevrez bientôt nos prochaines publications.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>❌</span>
                        <div>
                            <strong>Erreur !</strong><br>
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="benefits">
                    <div class="benefit-item">
                        <span class="benefit-icon">✨</span>
                        <div class="benefit-text">
                            <strong>Contenus exclusifs</strong> - Accédez en avant-première à nos meilleurs articles
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">📅</span>
                        <div class="benefit-text">
                            <strong>Résumé hebdomadaire</strong> - Un email par semaine, pas de spam
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">🎁</span>
                        <div class="benefit-text">
                            <strong>Bonus gratuits</strong> - Guides, ressources et conseils pratiques
                        </div>
                    </div>
                    <div class="benefit-item">
                        <span class="benefit-icon">🔒</span>
                        <div class="benefit-text">
                            <strong>Confidentialité garantie</strong> - Vos données sont protégées
                        </div>
                    </div>
                </div>

                <form method="POST" action="">
                    <div class="name-group">
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" 
                                   value="<?php echo htmlspecialchars($prenom ?? ''); ?>" 
                                   placeholder="Jean">
                        </div>

                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" 
                                   value="<?php echo htmlspecialchars($nom ?? ''); ?>" 
                                   placeholder="Dupont">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="votre@email.com" required>
                    </div>

                    <button type="submit" class="btn-subscribe">
                        📨 S'inscrire gratuitement
                    </button>
                </form>

                <p class="privacy-note">
                    🔒 En vous inscrivant, vous acceptez de recevoir nos emails. 
                    Vous pouvez vous désabonner à tout moment. 
                    Nous respectons votre vie privée et ne partagerons jamais vos données.
                </p>

                <div class="subscriber-count">
                    Rejoignez <strong><?php echo number_format($subscriberCount); ?></strong> 
                    <?php echo $subscriberCount > 1 ? 'abonnés' : 'abonné'; ?> qui nous font déjà confiance 🎉
                </div>

                <div class="unsubscribe-link">
                    <a href="desabonement.php">Se désabonner de la newsletter</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>