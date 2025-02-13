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

// Récupérer les filtres du formulaire
$nom_cours_filter = isset($_POST['nom_cours']) ? $_POST['nom_cours'] : '';
$type_cours_filter = isset($_POST['type_cours']) ? $_POST['type_cours'] : '';

// Requête pour récupérer les séances avec filtrage
$sql_seances_etudiant = "
    SELECT 
        s.id_seance, 
        s.jour, 
        s.heure, 
        s.heure_fin, 
        c.nom_cours,
        c.type_cours,
        sa.nom_salle
    FROM 
        concerner co
    INNER JOIN seance s ON co.id_seance = s.id_seance
    INNER JOIN cours c ON s.cours_id = c.cours_id
    INNER JOIN salle sa ON s.salle_id = sa.salle_id
    WHERE co.id_badge = :id_badge
    AND (c.nom_cours ILIKE :nom_cours)  -- Utilisation de ILIKE pour une recherche insensible à la casse
    AND (c.type_cours LIKE :type_cours)
    ORDER BY s.jour, s.heure
";

// Préparer et exécuter la requête avec les filtres
$stmt_seances_etudiant = $pdo->prepare($sql_seances_etudiant);
$stmt_seances_etudiant->execute([
    'id_badge' => $id_personne,
    'nom_cours' => "%" . $nom_cours_filter . "%",  // Recherche par sous-chaîne
    'type_cours' => "%" . $type_cours_filter . "%"
]);
$seances = $stmt_seances_etudiant->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Séances</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/responsive.css" rel="stylesheet" />
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
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
                            <li class="nav-item active">
                                <a class="nav-link" href="seanceEtu.php">Mes Séances</a>
                            </li>
                            <li class="nav-item ">
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

    <div class="container mt-5">
        <h1 class=" title mb-4">Mes Séances</h1>

        <!-- Formulaire de filtrage -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="nom_cours" class="form-control" placeholder="Filtrer par nom du cours" value="<?= htmlspecialchars($nom_cours_filter) ?>">
                </div>
                <div class="col-md-4">
                    <select name="type_cours" class="form-control">
                        <option value="">Filtrer par type de cours</option>
                        <option value="td" <?= $type_cours_filter == 'td' ? 'selected' : '' ?>>TD</option>
                        <option value="tp" <?= $type_cours_filter == 'tp' ? 'selected' : '' ?>>TP</option>
                        <option value="cours" <?= $type_cours_filter == 'cours' ? 'selected' : '' ?>>Cours</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success">Filtrer</button>
                </div>
            </div>
        </form>

        <!-- Liste des séances -->
        <?php if (count($seances) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Heure Début</th>
                            <th>Heure Fin</th>
                            <th>Nom du Cours</th>
                            <th>Type de Cours</th>
                            <th>Salle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seances as $seance): ?>
                            <tr>
                                <td><?= htmlspecialchars($seance['jour']) ?></td>
                                <td><?= htmlspecialchars($seance['heure']) ?></td>
                                <td><?= htmlspecialchars($seance['heure_fin']) ?></td>
                                <td><?= htmlspecialchars($seance['nom_cours']) ?></td>
                                <td><?= htmlspecialchars($seance['type_cours']) ?></td>
                                <td><?= htmlspecialchars($seance['nom_salle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucune séance disponible.</p>
        <?php endif; ?>
    </div>


 <?php include "include/footeretu.inc.php"?>
