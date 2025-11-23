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
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error = true;
        $errorMessage = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMessage = "L'adresse email n'est pas valide.";
    } elseif (strlen($message) < 10) {
        $error = true;
        $errorMessage = "Le message doit contenir au moins 10 caractères.";
    } else {
        // Insertion dans la base de données
        try {
            $stmt = $conn->prepare("INSERT INTO contacts (nom, email, sujet, message, date_envoi, statut) 
                                    VALUES (?, ?, ?, ?, NOW(), 'non_lu')");
            $stmt->execute([$nom, $email, $sujet, $message]);
            
            $success = true;
            
            // Optionnel : Envoi d'email de notification
            // $to = "admin@votresite.com";
            // $subject = "Nouveau message de contact : " . $sujet;
            // $body = "Nom: $nom\nEmail: $email\n\nMessage:\n$message";
            // mail($to, $subject, $body);
            
            // Réinitialisation des variables
            $nom = $email = $sujet = $message = '';
            
        } catch (PDOException $e) {
            $error = true;
            $errorMessage = "Une erreur s'est produite. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?></title>
    
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

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        main {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 20px;
        }

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .contact-form-section,
        .contact-info-section {
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
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
        input[type="email"],
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Contact Info Section */
        .contact-info-section {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .info-card {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: transform 0.3s;
        }

        .info-card:hover {
            transform: translateX(5px);
        }

        .info-icon {
            font-size: 2.5rem;
            min-width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }

        .info-content h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .info-content p {
            color: #555;
            line-height: 1.8;
        }

        .info-content a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50%;
            font-size: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .social-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .faq-section {
            margin-top: 3rem;
            background: white;
            border-radius: 10px;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .faq-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .faq-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .faq-question {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .faq-answer {
            color: #555;
            line-height: 1.8;
        }

        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        @media (max-width: 968px) {
            .contact-container {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2rem;
            }

            .social-links {
                justify-content: center;
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
            
            <h1 class="page-title">📧 Contactez-nous</h1>
            <p class="page-subtitle">Nous sommes là pour vous aider. N'hésitez pas à nous écrire !</p>
        </div>
    </header>

    <main>
        <div class="contact-container">
            <!-- Formulaire de contact -->
            <div class="contact-form-section">
                <h2>📝 Envoyez-nous un message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span>✅</span>
                        <div>
                            <strong>Message envoyé avec succès !</strong><br>
                            Nous vous répondrons dans les plus brefs délais.
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

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Votre nom <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" 
                               value="<?php echo htmlspecialchars($nom ?? ''); ?>" 
                               placeholder="Jean Dupont" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Votre email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="jean.dupont@exemple.com" required>
                    </div>

                    <div class="form-group">
                        <label for="sujet">Sujet <span class="required">*</span></label>
                        <input type="text" id="sujet" name="sujet" 
                               value="<?php echo htmlspecialchars($sujet ?? ''); ?>" 
                               placeholder="Objet de votre message" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Votre message <span class="required">*</span></label>
                        <textarea id="message" name="message" 
                                  placeholder="Écrivez votre message ici..." 
                                  required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        📨 Envoyer le message
                    </button>
                </form>
            </div>

            <!-- Informations de contact -->
            <div class="contact-info-section">
                <h2>📍 Nos coordonnées</h2>

                <div class="info-card">
                    <div class="info-icon">📧</div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p><a href="mailto:contact@votreblog.com">contact@votreblog.com</a></p>
                        <p>Nous répondons sous 24-48h</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">📞</div>
                    <div class="info-content">
                        <h3>Téléphone</h3>
                        <p><a href="tel:+237123456789">+237 123 456 789</a></p>
                        <p>Lun - Ven : 9h00 - 18h00</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h3>Adresse</h3>
                        <p>Douala, Cameroun</p>
                        <p>Quartier Bonanjo</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon">⏰</div>
                    <div class="info-content">
                        <h3>Horaires</h3>
                        <p>Lundi - Vendredi : 9h00 - 18h00</p>
                        <p>Week-end : Fermé</p>
                    </div>
                </div>

                <h3 style="margin-top: 2rem; color: #2c3e50;">🌐 Suivez-nous</h3>
                <div class="social-links">
                    <a href="https://facebook.com" target="_blank" class="social-link" title="Facebook">📘</a>
                    <a href="https://twitter.com" target="_blank" class="social-link" title="Twitter">🐦</a>
                    <a href="https://instagram.com" target="_blank" class="social-link" title="Instagram">📷</a>
                    <a href="https://linkedin.com" target="_blank" class="social-link" title="LinkedIn">💼</a>
                </div>
            </div>
        </div>

        <!-- Section FAQ -->
        <div class="faq-section">
            <h2>❓ Questions fréquentes</h2>
            
            <div class="faq-item">
                <div class="faq-question">Sous quel délai recevrai-je une réponse ?</div>
                <div class="faq-answer">Nous nous efforçons de répondre à tous les messages dans un délai de 24 à 48 heures ouvrables.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Puis-je proposer un article invité ?</div>
                <div class="faq-answer">Oui ! Nous acceptons les contributions. Contactez-nous avec votre idée d'article et nous vous reviendrons rapidement.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Comment puis-je signaler un problème technique ?</div>
                <div class="faq-answer">Utilisez le formulaire ci-dessus en précisant "Problème technique" dans le sujet, et décrivez le problème en détail.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question">Proposez-vous des partenariats ?</div>
                <div class="faq-answer">Oui, nous sommes ouverts aux partenariats. Envoyez-nous votre proposition via le formulaire de contact.</div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['nom_site'] ?? 'Mon Blog'); ?>. Tous droits réservés.</p>
    </footer>
</body>
</html>