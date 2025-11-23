<?php
session_start();
include("connexion.php");

// Vérification de la connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Fonction d'envoi d'email améliorée
function envoyerEmailReponse($destinataire, $sujet, $message, $nom_site) {
    // Formatage du message en HTML
    $message_html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .message { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin: 20px 0; }
            .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📧 ' . htmlspecialchars($nom_site) . '</h1>
            </div>
            <div class="content">
                <p>Bonjour,</p>
                <p>Nous avons bien reçu votre message et nous vous répondons :</p>
                <div class="message">' . nl2br(htmlspecialchars($message)) . '</div>
                <p>Cordialement,<br>L\'équipe de ' . htmlspecialchars($nom_site) . '</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($nom_site) . '. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>';
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $nom_site . " <noreply@votresite.com>\r\n";
    $headers .= "Reply-To: noreply@votresite.com\r\n";
    
    return mail($destinataire, $sujet, $message_html, $headers);
}

// Récupérer les paramètres du site
$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Gestion de l'envoi de réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reply') {
    $id_contact = $_POST['id_contact'];
    $email_destinataire = $_POST['email_destinataire'];
    $sujet = $_POST['sujet_reponse'];
    $message = $_POST['message_reponse'];
    
    // Envoi de l'email avec la fonction améliorée
    if (envoyerEmailReponse($email_destinataire, $sujet, $message, $settings['nom_site'] ?? 'Mon Blog')) {
        // Marquer le message comme traité
        $stmt = $conn->prepare("UPDATE contacts SET statut = 'traite' WHERE id_contact = ?");
        $stmt->execute([$id_contact]);
        
        header('Location: admin_messages.php?success=reply_sent');
        exit();
    } else {
        header('Location: admin_messages.php?error=reply_failed');
        exit();
    }
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE contacts SET statut = 'lu' WHERE id_contact = ?");
                $stmt->execute([$id]);
                header('Location: admin_messages.php?success=marked_read');
                exit();
                break;
                
            case 'mark_processed':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE contacts SET statut = 'traite' WHERE id_contact = ?");
                $stmt->execute([$id]);
                header('Location: admin_messages.php?success=marked_processed');
                exit();
                break;
                
            case 'delete_message':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM contacts WHERE id_contact = ?");
                $stmt->execute([$id]);
                header('Location: admin_messages.php?success=deleted');
                exit();
                break;
        }
    }
}

// Filtrage par statut
$filter = $_GET['filter'] ?? 'all';
$whereClause = "";
if ($filter === 'non_lu') {
    $whereClause = "WHERE statut = 'non_lu'";
} elseif ($filter === 'lu') {
    $whereClause = "WHERE statut = 'lu'";
} elseif ($filter === 'traite') {
    $whereClause = "WHERE statut = 'traite'";
}

// Récupération des messages
$messagesQuery = $conn->query("SELECT * FROM contacts $whereClause ORDER BY date_envoi DESC");
$messages = $messagesQuery->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$statsQuery = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'non_lu' THEN 1 ELSE 0 END) as non_lus,
    SUM(CASE WHEN statut = 'lu' THEN 1 ELSE 0 END) as lus,
    SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites
    FROM contacts");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages de contact - Admin</title>
    
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

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.2);
        }

        .action-buttons {
            padding: 1rem 1.5rem;
            margin-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .action-buttons-title {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-action {
            width: 100%;
            padding: 0.8rem 1rem;
            margin-bottom: 0.7rem;
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .btn-action:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: translateX(5px);
        }

        .btn-action.create {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: transparent;
        }

        .btn-action.create:hover {
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-action.edit {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-color: transparent;
        }

        .btn-action.edit:hover {
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-card.unread h3 { color: #e74c3c; }
        .stat-card.read h3 { color: #3498db; }
        .stat-card.processed h3 { color: #27ae60; }
        .stat-card.total h3 { color: #667eea; }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.7rem 1.5rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .messages-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .message-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .message-card.unread {
            border-left: 5px solid #e74c3c;
        }

        .message-card.read {
            border-left: 5px solid #3498db;
        }

        .message-card.processed {
            border-left: 5px solid #27ae60;
            opacity: 0.7;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .message-info h3 {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .message-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .message-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.unread {
            background: #fee;
            color: #e74c3c;
        }

        .status-badge.read {
            background: #e3f2fd;
            color: #3498db;
        }

        .status-badge.processed {
            background: #e8f5e9;
            color: #27ae60;
        }

        .message-subject {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .message-body {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 1.5rem;
        }

        .message-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .btn-read {
            background: #3498db;
            color: white;
        }

        .btn-processed {
            background: #27ae60;
            color: white;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-reply {
            background: #667eea;
            color: white;
        }

        /* Formulaire de réponse */
        .reply-form {
            display: none;
            background: #f0f4ff;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            border: 2px solid #667eea;
        }

        .reply-form.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #2c3e50;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
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

        .no-messages {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 1rem;
            }

            .message-actions {
                flex-direction: column;
            }

            .form-buttons {
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
                <a href="articles.php" class="menu-item">
                    <span>📝</span> Articles
                </a>
                <a href="categorie.php" class="menu-item">
                    <span>📂</span> Catégories
                </a>
                <a href="admin_messages.php" class="menu-item active">
                    <span>📧</span> Messages
                </a>
                <a href="admin_newsletter.php" class="menu-item">
                    <span>📬</span> Newsletter
                </a>
                <a href="accueil.php" class="menu-item" target="_blank">
                    <span>🌐</span> Voir le site
                </a>
            </nav>

            <div class="action-buttons">
                <div class="action-buttons-title">Actions rapides</div>
                
                <!-- <a href="creer-article.php" class="btn-action">
                    <span>📝</span>
                    <span>Créer un article</span>
                </a> -->
                
                <a href="creer_categorie.php" class="btn-action create">
                    <span>➕</span>
                    <span>Créer une catégorie</span>
                </a>
                
                <a href="modifier_categorie.php" class="btn-action edit">
                    <span>✏️</span>
                    <span>Modifier catégories</span>
                </a>
            </div>

            <nav class="sidebar-menu" style="border-top: 1px solid rgba(255,255,255,0.2); margin-top: 1rem; padding-top: 1rem;">
                <a href="deconnexion.php" class="menu-item">
                    <span>🚪</span> Déconnexion
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        if ($_GET['success'] === 'reply_sent') {
                            echo '✅ Réponse envoyée avec succès !';
                        } else {
                            echo '✅ Opération effectuée avec succès !';
                        }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        if ($_GET['error'] === 'reply_failed') {
                            echo '❌ Erreur lors de l\'envoi de la réponse. Vérifiez la configuration email.';
                        }
                    ?>
                </div>
            <?php endif; ?>

            <div class="top-bar">
                <h2>📧 Messages de contact</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card unread">
                    <h3><?php echo $stats['non_lus']; ?></h3>
                    <p>Non lus</p>
                </div>
                <div class="stat-card read">
                    <h3><?php echo $stats['lus']; ?></h3>
                    <p>Lus</p>
                </div>
                <div class="stat-card processed">
                    <h3><?php echo $stats['traites']; ?></h3>
                    <p>Traités</p>
                </div>
                <div class="stat-card total">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total</p>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="admin_messages.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    Tous (<?php echo $stats['total']; ?>)
                </a>
                <a href="admin_messages.php?filter=non_lu" class="filter-tab <?php echo $filter === 'non_lu' ? 'active' : ''; ?>">
                    Non lus (<?php echo $stats['non_lus']; ?>)
                </a>
                <a href="admin_messages.php?filter=lu" class="filter-tab <?php echo $filter === 'lu' ? 'active' : ''; ?>">
                    Lus (<?php echo $stats['lus']; ?>)
                </a>
                <a href="admin_messages.php?filter=traite" class="filter-tab <?php echo $filter === 'traite' ? 'active' : ''; ?>">
                    Traités (<?php echo $stats['traites']; ?>)
                </a>
            </div>

            <div class="messages-container">
                <?php if (count($messages) > 0): ?>
                    <?php foreach($messages as $message): ?>
                        <div class="message-card <?php echo $message['statut']; ?>">
                            <div class="message-header">
                                <div class="message-info">
                                    <h3><?php echo htmlspecialchars($message['nom']); ?></h3>
                                    <div class="message-meta">
                                        <span>📧 <?php echo htmlspecialchars($message['email']); ?></span>
                                        <span>📅 <?php echo date('d/m/Y à H:i', strtotime($message['date_envoi'])); ?></span>
                                    </div>
                                </div>
                                <span class="status-badge <?php echo $message['statut']; ?>">
                                    <?php 
                                        echo $message['statut'] === 'non_lu' ? '🔴 Non lu' : 
                                             ($message['statut'] === 'lu' ? '🔵 Lu' : '✅ Traité');
                                    ?>
                                </span>
                            </div>

                            <div class="message-subject">
                                📌 <?php echo htmlspecialchars($message['sujet']); ?>
                            </div>

                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>

                            <div class="message-actions">
                                <button onclick="toggleReplyForm(<?php echo $message['id_contact']; ?>)" class="btn btn-reply">
                                    📨 Répondre
                                </button>

                                <?php if ($message['statut'] === 'non_lu'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="id" value="<?php echo $message['id_contact']; ?>">
                                        <button type="submit" class="btn btn-read">👁️ Marquer lu</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($message['statut'] !== 'traite'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="mark_processed">
                                        <input type="hidden" name="id" value="<?php echo $message['id_contact']; ?>">
                                        <button type="submit" class="btn btn-processed">✅ Marquer traité</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="id" value="<?php echo $message['id_contact']; ?>">
                                    <button type="submit" class="btn btn-delete">🗑️ Supprimer</button>
                                </form>
                            </div>

                            <!-- Formulaire de réponse -->
                            <div id="reply-form-<?php echo $message['id_contact']; ?>" class="reply-form">
                                <h4 style="margin-bottom: 1.5rem; color: #667eea;">✍️ Répondre à <?php echo htmlspecialchars($message['nom']); ?></h4>
                                
                                <form method="POST">
                                    <input type="hidden" name="action" value="send_reply">
                                    <input type="hidden" name="id_contact" value="<?php echo $message['id_contact']; ?>">
                                    <input type="hidden" name="email_destinataire" value="<?php echo htmlspecialchars($message['email']); ?>">
                                    
                                    <div class="form-group">
                                        <label for="sujet-<?php echo $message['id_contact']; ?>">Sujet</label>
                                        <input 
                                            type="text" 
                                            id="sujet-<?php echo $message['id_contact']; ?>" 
                                            name="sujet_reponse" 
                                            value="Re: <?php echo htmlspecialchars($message['sujet']); ?>"
                                            required
                                        >
                                    </div>

                                    <div class="form-group">
                                        <label for="message-<?php echo $message['id_contact']; ?>">Votre réponse</label>
                                        <textarea 
                                            id="message-<?php echo $message['id_contact']; ?>" 
                                            name="message_reponse" 
                                            placeholder="Écrivez votre réponse ici..."
                                            required
                                        ></textarea>
                                    </div>

                                    <div class="form-buttons">
                                        <button type="submit" class="btn-send">📤 Envoyer la réponse</button>
                                        <button type="button" onclick="toggleReplyForm(<?php echo $message['id_contact']; ?>)" class="btn-cancel">❌ Annuler</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <h3>📭 Aucun message</h3>
                        <p>Il n'y a aucun message dans cette catégorie.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleReplyForm(messageId) {
            const form = document.getElementById('reply-form-' + messageId);
            form.classList.toggle('active');
            
            // Scroll vers le formulaire si on l'ouvre
            if (form.classList.contains('active')) {
                setTimeout(() => {
                    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
    </script>
</body>
</html>