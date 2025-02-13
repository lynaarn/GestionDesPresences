<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['id_personne']) || $_SESSION['role'] !== 'etudiant') {
    header("Location: login.php");
}

$id_personne = $_SESSION['id_personne']; // Utiliser l'id de la personne connectée

// Connexion à la base de données
try {
    $dsn = "pgsql:host=postgresql-projetbdr.alwaysdata.net;port=5432;dbname=projetbdr_s;";
    $pdo = new PDO($dsn, 'projetbdr_all', 'A123456*bd');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Requête SQL pour récupérer les cours de l'étudiant via la table concerner
$sql = "
    SELECT DISTINCT 
        c.nom_cours, 
        c.type_cours
    FROM 
        concerner cn
    JOIN 
        seance s ON s.id_seance = cn.id_seance
    JOIN 
        cours c ON c.cours_id = s.cours_id
    WHERE 
        cn.id_badge = :id_personne;  -- Lier à l'id_personne de l'étudiant connecté
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id_personne', $id_personne, PDO::PARAM_INT); // Utilisation de id_personne
$stmt->execute();
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="css/style.css" rel="stylesheet" />
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
    <link href="css/responsive.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
   
</head>
<body>

<!-- Barre de navigation -->
<header class="header_section2">
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
                            <li class="nav-item ">
                                <a class="nav-link" href="notificationEtu.php">
                                    <i class="fa-regular fa-bell"></i> Mes Notifications
                                </a>
                            </li>
                            <li class="nav-item active ">
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
<h1 class="title mb-4">Mes cours</h1>
    <div class="course-container">
        <?php if (count($cours) > 0): ?>
            <?php foreach ($cours as $course): ?>
                <div class="course-card">
               
                    <h2> <i class="fa-solid fa-graduation-cap"></i><?= htmlspecialchars($course['type_cours']) ?>-<?= htmlspecialchars($course['nom_cours']) ?></h2>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun cours trouvé.</p>
        <?php endif; ?>
    </div>
</div>


<?php include "include/footeretu.inc.php"?>
