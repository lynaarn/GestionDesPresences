<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['id_personne']) || $_SESSION['role'] !== 'enseignant') {
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

// Requête pour récupérer les cours enseignés par l'enseignant connecté avec le type et le volume horaire
$sql = "
   SELECT 
    c.cours_id, 
    c.nom_cours, 
    COALESCE(array_agg(c.type_cours), ARRAY[]::text[]) as types_cours, 
    c.volume_horraire
FROM 
    cours c
INNER JOIN enseigner e ON c.cours_id = e.cours_id
WHERE e.enseignant_id = :enseignant_id
GROUP BY c.cours_id
ORDER BY c.nom_cours

";


$stmt = $pdo->prepare($sql);
$stmt->execute(['enseignant_id' => $id_personne]);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Cours</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/responsive.css" rel="stylesheet" />
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
    <!-- Inclusion de font-awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
<!-- Inclusion de jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Inclusion de Bootstrap JavaScript -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
    
</head>
<body>

    <!-- Barre de navigation -->
    <header class="header_section2">
        <div class="container">
            <nav class="navbar navbar-expand-lg custom_nav-container">
            <a href="./presences.php"><img src="images/logo.png" alt="Logo" class="logo"></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="s-1"> </span>
                    <span class="s-2"> </span>
                    <span class="s-3"> </span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <div class="d-flex ml-auto flex-column flex-lg-row align-items-center">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="presences.php"><i class="fa-solid fa-check"></i>Présences</a>
                            </li>
                            <li class="nav-item active">
                                <a class="nav-link" href="cours.php">Cours</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="seance.php">Séances</a>
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
    <h1 class="">Mes Cours</h1>

    <?php if (count($cours) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nom du Cours</th>
                        <th>Volume Horaire</th>
                        <th>type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cours as $cours_item): ?>
                        <tr>
                            <td>
                                <!-- Nom du cours avec un bouton pour afficher les types de cours -->
                                <?= htmlspecialchars($cours_item['nom_cours']) ?>
                                <button class="btn btn-info" data-toggle="collapse" data-target="#types_cours_<?= $cours_item['cours_id'] ?>">
                                    Voir type cours
                                </button>
                            </td>
                            <td><?= htmlspecialchars($cours_item['volume_horraire']) ?> heures</td>
                            <td>
                                <!-- Liste des types de cours cachée par défaut -->
                                <div id="types_cours_<?= $cours_item['cours_id'] ?>" class="collapse">
                                    <ul>
                                        <?php 
                                        // Vérifier si 'types_cours' est une chaîne et la séparer en tableau si nécessaire
                                        if (!empty($cours_item['types_cours']) && is_string($cours_item['types_cours'])) {
                                            $types_cours = explode(',', $cours_item['types_cours']); // Séparer la chaîne par des virgules
                                            foreach ($types_cours as $type): ?>
                                                <li><?= strtoupper(htmlspecialchars($type)) ?></li>
                                            <?php endforeach; 
                                        } 
                                        ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Aucun cours trouvé.</p>
    <?php endif; ?>
</div>


<script>
        // Fonction JavaScript pour afficher/masquer les types de cours
        function toggleTypes(coursId) {
            var types = document.getElementById('types-' + coursId);
            if (types.style.display === "none") {
                types.style.display = "block";
            } else {
                types.style.display = "none";
            }
        }
    </script>
</body>
</html>
<?php include "include/footerens2.inc.php"?>