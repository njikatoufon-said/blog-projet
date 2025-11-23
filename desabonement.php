<?php
include("connexion.php");

// Récupération des paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Variables pour les messages
$success = false;
$error = false;
$errorMessage = '';
$email = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($email)) {
        $error = true;
        $errorMessage = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMessage = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'email existe dans la base
        $checkQuery = $conn->prepare("SELECT id_news, statut FROM newsletter_s WHERE email = ?");
        $checkQuery->execute([$email]);
        $subscriber = $checkQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$subscriber) {
            $error = true;
            $errorMessage = "Cette adresse email n'est pas inscrite à notre newsletter.";
        } elseif ($subscriber['statut'] === 'inactif') {
            $error = true;
            $errorMessage = "Vous êtes déjà désinscrit de notre newsletter.";
        } else {
            // Désabonnement (changement de statut au lieu de suppression)
            try {
                $stmt = $conn->prepare("UPDATE newsletter_s SET statut = 'inactif', date_desinscription = NOW() WHERE email = ?");
                $stmt->execute([$email]);
                
                $success = true;
                $email = ''; // Réinitialisation
                
            } catch (PDOException $e) {
                $error = true;
                $errorMessage = "Une erreur s'est produite. Veuillez réessayer plus tard.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se désabonner - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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

        .unsubscribe-container {
            max-width: 600px;
            width: 100%;
        }

        .unsubscribe-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .icon-sad {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: sway 2s infinite;
        }

        @keyframes sway {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }

        .icon-success {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
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

        .reasons {
            text-align: left;
            margin: 2rem 0;
            background: #fff3cd;
            padding: 2rem;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }

        .reasons h3 {
            color: #856404;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .reason-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #856404;
        }

        .reason-item:last-child {
            margin-bottom: 0;
        }

        .reason-icon {
            font-size: 1.2rem;
            min-width: 25px;
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

        .success-message {
            background: #d4edda;
            padding: 2rem;
            border-radius: 10px;
            margin: 2rem 0;
            border-left: 4px solid #28a745;
        }

        .success-message h3 {
            color: #155724;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: #155724;
            line-height: 1.8;
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

        input[type="email"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .btn-unsubscribe {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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

        .btn-unsubscribe:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(231, 76, 60, 0.4);
        }

        .btn-unsubscribe:active {
            transform: translateY(0);
        }

        .alternatives {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #ecf0f1;
        }

        .alternatives h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .alt-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #3498db;
            color: white;
        }

        .feedback-section {
            margin-top: 2rem;
            text-align: left;
        }

        .feedback-section h4 {
            color: #2c3e50;
            margin-bottom: 0.8rem;
        }

        .feedback-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .feedback-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feedback-option input {
            width: auto;
        }

        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            margin-top: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        footer {
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .unsubscribe-card {
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .alt-buttons {
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
        </div>
    </header>

    <main>
        <div class="unsubscribe-container">
            <div class="unsubscribe-card">
                <?php if ($success): ?>
                    <!-- Message de succès -->
                    <div class="icon-success">✅</div>
                    <h1>Désinscription réussie</h1>
                    <p class="subtitle">Vous avez été désinscrit de notre newsletter avec succès.</p>
                    
                    <div class="success-message">
                        <h3>📭 C'est fait !</h3>
                        <p>
                            Vous ne recevrez plus nos emails. Nous espérons vous revoir bientôt !
                            Vous pouvez toujours consulter nos articles sur le site.
                        </p>
                    </div>

                    <div class="alternatives">
                        <h3>Continuez à nous suivre</h3>
                        <div class="alt-buttons">
                            <a href="accueil.php" class="btn btn-primary">📰 Lire nos articles</a>
                            <a href="contact.php" class="btn btn-secondary">📧 Nous contacter</a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Formulaire de désinscription -->
                    <div class="icon-sad">😢</div>
                    <h1>Vous nous quittez ?</h1>
                    <p class="subtitle">Nous sommes tristes de vous voir partir...</p>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <span>❌</span>
                            <div>
                                <strong>Erreur !</strong><br>
                                <?php echo htmlspecialchars($errorMessage); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="reasons">
                        <h3>💡 Avant de partir...</h3>
                        <div class="reason-item">
                            <span class="reason-icon">📅</span>
                            <span>Nous n'envoyons qu'un email par semaine maximum</span>
                        </div>
                        <div class="reason-item">
                            <span class="reason-icon">✨</span>
                            <span>Du contenu exclusif et des avant-premières</span>
                        </div>
                        <div class="reason-item">
                            <span class="reason-icon">🎁</span>
                            <span>Des ressources gratuites et des bonus</span>
                        </div>
                        <div class="reason-item">
                            <span class="reason-icon">🔒</span>
                            <span>Aucun spam, vos données sont protégées</span>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Votre adresse email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="votre@email.com" required>
                        </div>

                        <div class="feedback-section">
                            <h4>Pourquoi vous désabonnez-vous ? (Facultatif)</h4>
                            <div class="feedback-options">
                                <label class="feedback-option">
                                    <input type="checkbox" name="reason[]" value="trop_emails">
                                    Trop d'emails
                                </label>
                                <label class="feedback-option">
                                    <input type="checkbox" name="reason[]" value="contenu_non_pertinent">
                                    Contenu non pertinent
                                </label>
                                <label class="feedback-option">
                                    <input type="checkbox" name="reason[]" value="jamais_inscrit">
                                    Je ne me suis jamais inscrit
                                </label>
                                <label class="feedback-option">
                                    <input type="checkbox" name="reason[]" value="autre">
                                    Autre raison
                                </label>
                            </div>
                            <textarea name="feedback" placeholder="Dites-nous en plus (facultatif)..."></textarea>
                        </div>

                        <button type="submit" class="btn-unsubscribe">
                            Se désabonner
                        </button>
                    </form>

                    <div class="alternatives">
                        <h3>Vous préférez rester ?</h3>
                        <div class="alt-buttons">
                            <a href="newsletter.php" class="btn btn-primary">📬 Rester abonné</a>
                            <a href="contact.php" class="btn btn-secondary">💬 Nous faire un retour</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>