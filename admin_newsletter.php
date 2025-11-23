<?php
session_start();
include("connexion.php");

// Vérification de la connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'activate':
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE newsletter_s SET statut = 'actif' WHERE id_newsletter_s = ?");
                $stmt->execute([$id]);
                header('Location: admin_newsletter_s.php?success=activated');
                exit();
                break;
                
            case 'deactivate':
                $id = $_POST['id_news'];
                $stmt = $conn->prepare("UPDATE newsletter_s SET statut = 'inactif' WHERE id_news = ?");
                $stmt->execute([$id]);
                header('Location: admin_newsletter.php?success=deactivated');
                exit();
                break;
                
            case 'delete':
                $id = $_POST['id_news'];
                $stmt = $conn->prepare("DELETE FROM newsletter_s WHERE id_news = ?");
                $stmt->execute([$id]);
                header('Location: admin_newsletter.php?success=deleted');
                exit();
                break;
                
            case 'export':
                // Export des emails en CSV
                $subscribersQuery = $conn->query("SELECT email, nom, prenom, date_inscription, statut FROM newsletter_s ORDER BY date_inscription DESC");
                $subscribers = $subscribersQuery->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=newsletter_s_subscribers_' . date('Y-m-d') . '.csv');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Email', 'Nom', 'Prénom', 'Date inscription', 'Statut']);
                
                foreach ($subscribers as $subscriber) {
                    fputcsv($output, [
                        $subscriber['email'],
                        $subscriber['nom'],
                        $subscriber['prenom'],
                        $subscriber['date_inscription'],
                        $subscriber['statut']
                    ]);
                }
                
                fclose($output);
                exit();
                break;
        }
    }
}

// Filtrage par statut
$filter = $_GET['filter'] ?? 'all';
$whereClause = "";
if ($filter === 'actif') {
    $whereClause = "WHERE statut = 'actif'";
} elseif ($filter === 'inactif') {
    $whereClause = "WHERE statut = 'inactif'";
}

// Récupération des abonnés
$subscribersQuery = $conn->query("SELECT * FROM newsletter_s $whereClause ORDER BY date_inscription DESC");
$subscribers = $subscribersQuery->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$statsQuery = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
    SUM(CASE WHEN statut = 'inactif' THEN 1 ELSE 0 END) as inactifs,
    (SELECT COUNT(*) FROM newsletter_s WHERE DATE(date_inscription) = CURDATE()) as nouveaux_aujourdhui
    FROM newsletter_s");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$settingsQuery = $conn->query("SELECT * FROM setting LIMIT 1");
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion newsletter_s - Admin</title>
    
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
            flex-wrap: wrap;
            gap: 1rem;
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

        .stat-card.active h3 { color: #27ae60; }
        .stat-card.inactive h3 { color: #e74c3c; }
        .stat-card.total h3 { color: #667eea; }
        .stat-card.today h3 { color: #3498db; }

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

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .no-subscribers {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
        }

        .search-box {
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            width: 300px;
            font-size: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 1rem;
            }

            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.7rem;
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
                <a href="admin_newsletter_s.php" class="menu-item active">
                    <span>📬</span> newsletter_s
                </a>
                <a href="accueil.php" class="menu-item" target="_blank">
                    <span>🌐</span> Voir le site
                </a>
                <a href="deconnexion.php" class="menu-item">
                    <span>🚪</span> Déconnexion
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert">
                    ✅ Opération effectuée avec succès !
                </div>
            <?php endif; ?>

            <div class="top-bar">
                <h2>📬 Gestion de la newsletter_s</h2>
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="searchBox" class="search-box" placeholder="🔍 Rechercher un email...">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="export">
                        <button type="submit" class="btn btn-success">📥 Exporter CSV</button>
                    </form>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card active">
                    <h3><?php echo number_format($stats['actifs']); ?></h3>
                    <p>Abonnés actifs</p>
                </div>
                <div class="stat-card inactive">
                    <h3><?php echo number_format($stats['inactifs']); ?></h3>
                    <p>Désabonnés</p>
                </div>
                <div class="stat-card total">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Total</p>
                </div>
                <div class="stat-card today">
                    <h3><?php echo number_format($stats['nouveaux_aujourdhui']); ?></h3>
                    <p>Aujourd'hui</p>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="admin_newsletter_s.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    Tous (<?php echo $stats['total']; ?>)
                </a>
                <a href="admin_newsletter_s.php?filter=actif" class="filter-tab <?php echo $filter === 'actif' ? 'active' : ''; ?>">
                    Actifs (<?php echo $stats['actifs']; ?>)
                </a>
                <a href="admin_newsletter_s.php?filter=inactif" class="filter-tab <?php echo $filter === 'inactif' ? 'active' : ''; ?>">
                    Inactifs (<?php echo $stats['inactifs']; ?>)
                </a>
            </div>

            <div class="card">
                <h3>📋 Liste des abonnés</h3>
                <br>
                <?php if (count($subscribers) > 0): ?>
                    <table id="subscribersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Date d'inscription</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subscribers as $subscriber): ?>
                                <tr>
                                    <td>#<?php echo $subscriber['id_news']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($subscriber['email']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($subscriber['nom'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($subscriber['prenom'] ?: '-'); ?></td>
                                    <td><?php echo date('d/m/Y à H:i', strtotime($subscriber['date_inscription'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $subscriber['statut']; ?>">
                                            <?php echo $subscriber['statut'] === 'actif' ? '✅ Actif' : '❌ Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($subscriber['statut'] === 'actif'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="id" value="<?php echo $subscriber['id_news']; ?>">
                                                    <button type="submit" class="btn btn-warning" title="Désactiver">🚫</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="id" value="<?php echo $subscriber['id_news']; ?>">
                                                    <button type="submit" class="btn btn-success" title="Activer">✅</button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet abonné ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $subscriber['id_news']; ?>">
                                                <button type="submit" class="btn btn-danger" title="Supprimer">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-subscribers">
                        <h3>📭 Aucun abonné</h3>
                        <p>Il n'y a aucun abonné dans cette catégorie.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Recherche en temps réel
        document.getElementById('searchBox').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('subscribersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const email = rows[i].cells[1].textContent.toLowerCase();
                const nom = rows[i].cells[2].textContent.toLowerCase();
                const prenom = rows[i].cells[3].textContent.toLowerCase();
                
                if (email.includes(searchTerm) || nom.includes(searchTerm) || prenom.includes(searchTerm)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>