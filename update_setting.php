<?php
session_start();
include("connexion.php");

// Vérification de la connexion admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Vérification que c'est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit();
}

// Récupération et validation des données
$nom_site = trim($_POST['nom_site'] ?? '');
$cle = trim($_POST['cle'] ?? '');
$email_contact = trim($_POST['email_contact'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validation des champs obligatoires
if (empty($nom_site)) {
    header('Location: admin.php?error=missing_site_name');
    exit();
}

// Validation de l'email si fourni
if (!empty($email_contact) && !filter_var($email_contact, FILTER_VALIDATE_EMAIL)) {
    header('Location: admin.php?error=invalid_email');
    exit();
}

try {
    // Vérifier si des paramètres existent déjà
    $checkSettings = $conn->query("SELECT COUNT(*) as count FROM setting");
    $settingsExist = $checkSettings->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    if ($settingsExist) {
        // Mise à jour des paramètres existants
        $stmt = $conn->prepare("UPDATE setting 
                               SET nom_site = ?, cle = ?, email_contact = ?, description = ? 
                               WHERE id_setting = (SELECT id FROM (SELECT id_setting as id FROM setting LIMIT 1) as temp)");
        
        $stmt->execute([$nom_site, $cle, $email_contact, $description]);
    } else {
        // Insertion de nouveaux paramètres
        $stmt = $conn->prepare("INSERT INTO setting (nom_site, cle, email_contact, description) 
                               VALUES (?, ?, ?, ?)");
        
        $stmt->execute([$nom_site, $cle, $email_contact, $description]);
    }
    
    // Redirection avec message de succès
    header('Location: admin.php?success=settings_updated');
    exit();
    
} catch (PDOException $e) {
    // En cas d'erreur
    error_log("Erreur mise à jour paramètres: " . $e->getMessage());
    header('Location: admin.php?error=settings_update_failed');
    exit();
}
?>