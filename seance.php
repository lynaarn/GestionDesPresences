<?php
// Démarrer la session
session_start();

// Vérification de la session utilisateur
if (!isset($_SESSION['id_personne']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: login.php");
    exit();
}

// Validation de l'identifiant de l'utilisateur
$id_personne = filter_var($_SESSION['id_personne'], FILTER_VALIDATE_INT);
if (!$id_personne) {
    die("ID utilisateur invalide.");
}

// Informations de connexion à la base de données
$host = "postgresql-projetbdr.alwaysdata.net";
$port = "5432";
$dbname = "projetbdr_s";
$user = "projetbdr_all";
$password = "A123456*bd";

// Connexion à la base de données
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Récupérer les filtres
$nom_cours_filter = isset($_GET['nom_cours']) ? $_GET['nom_cours'] : '';
$type_cours_filter = isset($_GET['type_cours']) ? $_GET['type_cours'] : '';

// Requête SQL qui récupere les seances de l'enseignant connecté
$sql = "
    SELECT 
        s.id_seance, 
        s.jour, 
        s.heure, 
        s.heure_fin, 
        c.nom_cours, 
        c.type_cours, 
        sa.nom_salle 
    FROM 
        seance s
    INNER JOIN enseigner e ON s.cours_id = e.cours_id
    INNER JOIN cours c ON s.cours_id = c.cours_id
    INNER JOIN salle sa ON s.salle_id = sa.salle_id
    WHERE e.enseignant_id = :enseignant_id
";

// Ajouter des conditions pour les filtres
if ($nom_cours_filter) {
    $sql .= " AND c.nom_cours ILIKE :nom_cours";
}

if ($type_cours_filter) {
    $sql .= " AND c.type_cours = :type_cours";
}

$sql .= " ORDER BY s.jour, s.heure";

// Préparation de la requête avec les filtres
try {
    $stmt = $pdo->prepare($sql);

    // Exécution avec les paramètres
    $params = ['enseignant_id' => $id_personne];

    if ($nom_cours_filter) {
        $params['nom_cours'] = '%' . $nom_cours_filter . '%'; // Utilisation de ILIKE pour une recherche insensible à la casse
    }

    if ($type_cours_filter) {
        $params['type_cours'] = $type_cours_filter;
    }

    $stmt->execute($params);
    $seances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des séances : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Séances</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
    <link href="css/responsive.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="presences.php"><i class="fa-solid fa-check"></i>Présences</a></li>
                        <li class="nav-item"><a class="nav-link" href="cours.php">Cours</a></li>
                        <li class="nav-item active"><a class="nav-link" href="seance.php">Séances</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fa-solid fa-user"></i>Déconnexion</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="container mt-5">
        <h1 class="mb-4" style="color: #4b208c;">Mes Séances</h1>

        <!-- Formulaire de filtrage -->
        <form method="GET" class="mb-4">
            <div class="form-row">
                <div class="col">
                    <label for="nom_cours">Nom du Cours</label>
                    <input type="text" class="form-control" id="nom_cours" name="nom_cours" placeholder="Nom du cours" value="<?= isset($_GET['nom_cours']) ? htmlspecialchars($_GET['nom_cours']) : '' ?>">
                </div>
                <div class="col">
                    <label for="type_cours">Type de Cours</label>
                    <select class="form-control" id="type_cours" name="type_cours">
                        <option value="">Sélectionner un type</option>
                        <option value="td" <?= isset($_GET['type_cours']) && $_GET['type_cours'] == 'td' ? 'selected' : '' ?>>TD</option>
                        <option value="tp" <?= isset($_GET['type_cours']) && $_GET['type_cours'] == 'tp' ? 'selected' : '' ?>>TP</option>
                        <option value="cours" <?= isset($_GET['type_cours']) && $_GET['type_cours'] == 'cours' ? 'selected' : '' ?>>Cours</option>
                    </select>
                </div>
                <div class="col">
                    <button type="submit" class="btn  btn-success" style="margin-top: 32px;">Filtrer</button>
                </div>
            </div>
        </form>
 <!-- tableau des seances -->
        <?php if (count($seances) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Cours</th>
                            <th>Jour</th>
                            <th>Heure de début</th>
                            <th>Heure de fin</th>
                            <th>Salle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seances as $seance): ?>
                            <tr>
                                <td>
                                    <?= strtoupper(htmlspecialchars($seance['type_cours'])) ?> 
                                    <?= htmlspecialchars($seance['nom_cours']) ?>
                                </td>
                                <td><?= htmlspecialchars($seance['jour']) ?></td>
                                <td><?= htmlspecialchars($seance['heure']) ?></td>
                                <td><?= htmlspecialchars($seance['heure_fin']) ?></td>
                                <td><?= htmlspecialchars($seance['nom_salle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucune séance trouvée pour votre recherche.</p>
        <?php endif; ?>
    </div>



</body>
</html>

 <!-- Footer -->
<?php include "include/footerens.inc.php"?>