<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['id_personne']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
}

// Récupérer l'id de la personne connectée
$id_personne = $_SESSION['id_personne'];

// Connexion à la base de données
$host = "postgresql-projetbdr.alwaysdata.net";
$port = "5432";
$dbname = "projetbdr_s";
$user = "projetbdr_all";
$password = "A123456*bd";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Requête pour récupérer les notifications de l'étudiant
$sql_notifications_etudiant = "
  SELECT 
    c.nom_cours, 
    c.type_cours, 
    s.jour AS date_seance, 
    s.heure AS heure_debut, 
    s.heure_fin, 
    n.type_notification, 
    n.contenu_notification, 
    n.date_envoi, 
    p.nom AS nom_enseignant, 
    p.prenom AS prenom_enseignant
FROM 
    notification n
JOIN 
    enseignant e ON e.enseignant_id = n.enseignant_id
JOIN 
    personne p ON p.id_personne = e.enseignant_id
JOIN 
    seance s ON s.id_seance = n.id_seance
JOIN 
    cours c ON c.cours_id = s.cours_id
WHERE 
    n.id_badge = :id_badge
ORDER BY 
    n.date_envoi DESC;
";

$stmt_notifications_etudiant = $pdo->prepare($sql_notifications_etudiant);
$stmt_notifications_etudiant->bindParam(':id_badge', $id_personne, PDO::PARAM_INT);
$stmt_notifications_etudiant->execute();
$notifications = $stmt_notifications_etudiant->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Notifications</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="css/style.css" rel="stylesheet" />
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
    <link href="css/responsive.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

   
</head>
<body>
    <!-- Barre de navigation -->
    <header class="header_section2 ">
        <div class="container2">
            <nav class="navbar navbar-expand-lg custom_nav-container">
            <a href="seanceEtu.php"> <img src="images/logo.png" alt="Logo" class="logo"></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="s-1"> </span>
                    <span class="s-2"> </span>
                    <span class="s-3"> </span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <div class="d-flex ml-auto flex-column flex-lg-row align-items-center">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="seanceEtu.php">Mes Séances</a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="notificationEtu.php">
                                    <i class="fa-regular fa-bell"></i> Mes Notifications
                                </a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link" href="coursEtu.php"><i class="fa-solid fa-graduation-cap"></i>Mes cours</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php"><i class="fa-solid fa-user"></i>Déconnexion</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="container mt-5">
    <h1 class="title mb-4">Mes Notifications</h1>


        <!-- Liste des notifications -->
        <?php if (count($notifications) > 0): ?>
            <div class="notification-container">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card">
                    
                        <h5> <i class="fa-regular fa-bell" style="margin-right: 10px;"></i><?= htmlspecialchars($notification['nom_cours']) ?> (<?= htmlspecialchars($notification['type_cours']) ?>)</h5>
                        <p class="info">Motif : <span class="highlight2"><?= htmlspecialchars($notification['type_notification']) ?></span></p>
                        <p class="info">Message : <span class="highlight"><?= htmlspecialchars($notification['contenu_notification']) ?></p>
                        <p class="info">Enseignant : <span class="highlight2"><?= htmlspecialchars($notification['nom_enseignant']) . ' ' . htmlspecialchars($notification['prenom_enseignant']) ?></span></p>
                        <p class="info">Date de la séance : <span class="highlight"><?= htmlspecialchars($notification['date_seance']) ?> de <?= htmlspecialchars($notification['heure_debut']) ?> à <?= htmlspecialchars($notification['heure_fin']) ?></span></p>
                        <p class="date">Envoyé le : <?= htmlspecialchars($notification['date_envoi']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucune notification disponible.</p>
        <?php endif; ?>
    </div>

<?php include "include/footeretu.inc.php"?>
