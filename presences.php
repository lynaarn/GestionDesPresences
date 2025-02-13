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

// Requête pour récupérer les séances de l'enseignant connecté
$sql_seances = "
    SELECT 
        s.id_seance, 
        s.jour, 
        s.heure, 
        s.heure_fin, 
        c.nom_cours,
        c,type_cours,
        sa.nom_salle
    FROM 
        seance s
    INNER JOIN enseigner e ON s.cours_id = e.cours_id
    INNER JOIN cours c ON s.cours_id = c.cours_id
    INNER JOIN salle sa ON s.salle_id = sa.salle_id
    WHERE e.enseignant_id = :enseignant_id
    ORDER BY s.jour, s.heure
";

$stmt_seances = $pdo->prepare($sql_seances);
$stmt_seances->execute(['enseignant_id' => $id_personne]);
$seances = $stmt_seances->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si une séance a été sélectionnée via GET
$id_seance = isset($_GET['id_seance']) ? (int)$_GET['id_seance'] : null;

if ($id_seance) {
    // Requête pour récupérer les présences des étudiants pour une séance donnée avec porte = true
    $sql_presences = "
        SELECT 
            etu.id_badge,
            p.date_presence, 
            p.heure_arrivee, 
            etu_nom.nom AS etudiant_nom, 
            etu_nom.prenom AS etudiant_prenom, 
            s.jour AS jour_seance,
            c.nom_cours AS cours_nom, 
            c.type_cours,   -- Récupérer le type de cours
            sa.nom_salle AS salle_nom
        FROM 
            presence p
        INNER JOIN etudiant etu ON p.id_badge = etu.id_badge
        INNER JOIN seance s ON p.id_seance = s.id_seance
        INNER JOIN cours c ON s.cours_id = c.cours_id
        INNER JOIN salle sa ON s.salle_id = sa.salle_id
        INNER JOIN personne etu_nom ON etu.id_badge = etu_nom.id_personne
        WHERE s.id_seance = :id_seance AND p.porte = TRUE
        ORDER BY p.date_presence DESC, p.heure_arrivee DESC
    ";

    $stmt_presences = $pdo->prepare($sql_presences);
    $stmt_presences->execute(['id_seance' => $id_seance]);
    $presences = $stmt_presences->fetchAll(PDO::FETCH_ASSOC);
  // Requête pour récupérer les étudiants absents dans la séance donnée
    $sql_absences = "
    SELECT 
        etu.id_badge,
        etu_nom.nom AS etudiant_nom, 
        etu_nom.prenom AS etudiant_prenom, 
        s.jour AS jour_seance,
        c.nom_cours AS cours_nom, 
        c.type_cours,  -- Récupérer le type de cours
        sa.nom_salle AS salle_nom
    FROM 
        concerner co
    INNER JOIN etudiant etu ON co.id_badge = etu.id_badge
    INNER JOIN personne etu_nom ON etu.id_badge = etu_nom.id_personne
    INNER JOIN seance s ON co.id_seance = s.id_seance
    INNER JOIN cours c ON s.cours_id = c.cours_id
    INNER JOIN salle sa ON s.salle_id = sa.salle_id
    LEFT JOIN presence p ON p.id_badge = etu.id_badge AND p.id_seance = s.id_seance
    WHERE 
        co.id_seance = :id_seance
        AND p.id_badge IS NULL  -- L'absence est déterminée par l'absence d'enregistrement dans la table presence
    ORDER BY etu_nom.nom, etu_nom.prenom
";


    $stmt_absences = $pdo->prepare($sql_absences);
$stmt_absences->execute(['id_seance' => $id_seance]);
$absences = $stmt_absences->fetchAll(PDO::FETCH_ASSOC);
} else {
    $presences = [];
    $absences = [];
}

if ($id_seance) {
    // Requête pour récupérer les étudiants concernés par le cours mais qui sont absents dans celui ci , mais ils sont present
    // dans une autre seance de ce meme cours.
    $sql_etudiants_autres_seances_porte_false = "
    SELECT 
        etu.id_badge,
        etu_nom.nom AS etudiant_nom, 
        etu_nom.prenom AS etudiant_prenom, 
        s.jour AS jour_seance,
        s.heure AS heure_debut,
        c.nom_cours AS cours_nom, 
        c.type_cours,  -- Récupérer le type de cours
        sa.nom_salle AS salle_nom,
        p.heure_arrivee AS heure_arrivee
    FROM 
        presence p
    INNER JOIN etudiant etu ON p.id_badge = etu.id_badge
    INNER JOIN personne etu_nom ON etu.id_badge = etu_nom.id_personne
    INNER JOIN seance s ON p.id_seance = s.id_seance
    INNER JOIN cours c ON s.cours_id = c.cours_id
    INNER JOIN salle sa ON s.salle_id = sa.salle_id
    WHERE 
       
       p.id_badge IN (
            SELECT id_badge
            FROM concerner
            WHERE id_seance = :id_seance  -- L'étudiant doit être concerné par la séance donnée
        )
        AND p.porte = FALSE  -- L'étudiant doit être marqué comme présent mais avec porte = FALSE
        AND s.num_seance = (
            SELECT num_seance
            FROM seance
            WHERE id_seance = :id_seance  -- Le numéro de la séance doit être le même
        )
        AND c.cours_id = (
                SELECT cours_id
                FROM seance
                WHERE id_seance = :id_seance  -- Le cours doit être le même
            
        )
       
";




    $stmt_etudiants_autres_seances_porte_false = $pdo->prepare($sql_etudiants_autres_seances_porte_false);
    $stmt_etudiants_autres_seances_porte_false->execute(['id_seance' => $id_seance]);
    $etudiants_autres_seances_porte_false = $stmt_etudiants_autres_seances_porte_false->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si id_seance n'est pas défini ou null, initialiser $etudiants_autres_seances_porte_false comme un tableau vide
    $etudiants_autres_seances_porte_false = [];
}
    
    // Enregistrer une notification

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_notification'])) {
        $type_notification = $_POST['type_notification'];
        $contenu_notification = $_POST['contenu_notification'];
        $id_badge = $_POST['id_badge'];
        $id_seance = $_POST['id_seance'];
        $date_envoi = date('Y-m-d');
    
// Vérifier si l'étudiant a déjà été notifié pour cette séance afin de ne pas permettre d'envoyer 2 notifications au même étudiant concernant la même séance.
$sql_check_notifie = "
            SELECT notifie 
            FROM presence 
            WHERE id_badge = :id_badge AND id_seance = :id_seance
        ";
        $stmt_check_notifie = $pdo->prepare($sql_check_notifie);
        $stmt_check_notifie->execute([
            'id_badge' => $id_badge,
            'id_seance' => $id_seance
        ]);
        $presence = $stmt_check_notifie->fetch(PDO::FETCH_ASSOC);
    
        if ($presence && $presence['notifie'] === false) {
            // Si `notifie` est à FALSE, procéder à l'envoi de la notification
            try {
                // Insérer la notification dans la table notification
                $sql_insert_notification = "
                    INSERT INTO notification (type_notification, contenu_notification, date_envoi, enseignant_id, id_badge, id_seance)
                    VALUES (:type_notification, :contenu_notification, :date_envoi, :enseignant_id, :id_badge, :id_seance)
                ";
                $stmt_insert = $pdo->prepare($sql_insert_notification);
                $stmt_insert->execute([
                    'type_notification' => $type_notification,
                    'contenu_notification' => $contenu_notification,
                    'date_envoi' => $date_envoi,
                    'enseignant_id' => $id_personne,
                    'id_badge' => $id_badge,
                    'id_seance' => $id_seance,
                ]);
    
                // Mettre à jour la colonne `notifie` dans la table presence
                $sql_update_notifie = "
                    UPDATE presence 
                    SET notifie = TRUE 
                    WHERE id_badge = :id_badge AND id_seance = :id_seance
                ";
                $stmt_update = $pdo->prepare($sql_update_notifie);
                $stmt_update->execute([
                    'id_badge' => $id_badge,
                    'id_seance' => $id_seance
                ]);
    
                // Rediriger après succès
                header("Location: presences.php?id_seance=$id_seance");
                exit;
            } catch (Exception $e) {
                // Gérer les erreurs d'exécution
                echo "<script>alert('Une erreur s’est produite lors de l’envoi de la notification : " . $e->getMessage() . "');</script>";
            }
        } else {
            // Si `notifie` est à TRUE, afficher un message d'erreur
            echo "<script>alert('Vous ne pouvez pas envoyer de notification. Cet étudiant a déjà été notifié pour cette séance.');</script>";
        }
    }
    

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_notification'])) {
        $type_notification = $_POST['type_notification'];
        $contenu_notification = $_POST['contenu_notification'];
        $id_badge = $_POST['id_badge'];
        $id_seance = $_POST['id_seance'];
        $date_envoi = date('Y-m-d');
    
        try {
           // Pour les étudiants absents, étant donné qu'ils ne sont pas insérés dans la table présence pour cette séance, 
          // on vérifie si une notification existe déjà pour cet étudiant et cette séance.

            $sql_check_notification = "
                SELECT COUNT(*) AS notification_count 
                FROM notification 
                WHERE id_badge = :id_badge AND id_seance = :id_seance
            ";
            $stmt_check_notification = $pdo->prepare($sql_check_notification);
            $stmt_check_notification->execute([
                'id_badge' => $id_badge,
                'id_seance' => $id_seance
            ]);
            $notification = $stmt_check_notification->fetch(PDO::FETCH_ASSOC);
    
            if ($notification && $notification['notification_count'] == 0) {
                // Si aucune notification n'existe, insérer une nouvelle notification
                $sql_insert_notification = "
                    INSERT INTO notification (type_notification, contenu_notification, date_envoi, enseignant_id, id_badge, id_seance)
                    VALUES (:type_notification, :contenu_notification, :date_envoi, :enseignant_id, :id_badge, :id_seance)
                ";
                $stmt_insert = $pdo->prepare($sql_insert_notification);
                $stmt_insert->execute([
                    'type_notification' => $type_notification,
                    'contenu_notification' => $contenu_notification,
                    'date_envoi' => $date_envoi,
                    'enseignant_id' => $id_personne,
                    'id_badge' => $id_badge,
                    'id_seance' => $id_seance,
                ]);
    
                // Rediriger après succès
                header("Location: presences.php?id_seance=$id_seance");
                exit;
            } else {
                // Si une notification existe déjà, afficher un message d'erreur
                echo "<script>alert('Vous ne pouvez pas envoyer de notification. Cet étudiant a déjà été notifié pour cette séance.');</script>";
            }
        } catch (Exception $e) {
            // Gérer les erreurs d'exécution
            echo "<script>alert('Une erreur s’est produite lors de l’envoi de la notification : " . $e->getMessage() . "');</script>";
        }
    }
    
    


?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Présences</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/style.css"  />
    
   
    
    <link href="css/responsive.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>

    <!-- Barre de navigation -->
    <header class="header_section2 ">
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
                            <li class="nav-item active">
                                <a class="nav-link" href="presences.php"><i class="fa-solid fa-check"></i>Présences</a>
                            </li>
                            <li class="nav-item">
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
        <h1 class="mb-4" style="color: #4b208c;">Liste des Présences</h1>

        <!-- Liste des séances -->
     
        <ul class="list-group mb-4">
            <?php foreach ($seances as $seance): ?>
                <li class="list-group-item">
                    <a href="?id_seance=<?= $seance['id_seance'] ?>">
                    <?= htmlspecialchars($seance['type_cours']) ?> -   <?= htmlspecialchars($seance['nom_cours']) ?> - <?= htmlspecialchars($seance['jour']) ?> (<?= htmlspecialchars($seance['heure']) ?> - <?= htmlspecialchars($seance['heure_fin']) ?>)
                        - Salle : <?= htmlspecialchars($seance['nom_salle']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Tableau des présences -->
        <?php if ($id_seance && count($presences) > 0): ?>
            <h3>Présences pour la séance du <?= htmlspecialchars($presences[0]['jour_seance']) ?> :</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Heure d'Arrivée</th>
                            <th>Nom Étudiant</th>
                            <th>Prénom Étudiant</th>
                            <th>Notifier</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presences as $presence): ?>
                            <tr>
                                <td><?= htmlspecialchars($presence['heure_arrivee']) ?></td>
                              
                                <td><?= htmlspecialchars($presence['etudiant_nom']) ?></td>
                                <td><?= htmlspecialchars($presence['etudiant_prenom']) ?></td>
                                <td>
                                <button class="boutonNotifier" data-bs-toggle="modal" data-bs-target="#notifyModal" 
        data-id-badge="<?= $presence['id_badge'] ?>">Notifier</button>


                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($id_seance): ?>
            <p>Aucune présence enregistrée pour cette séance.</p>
        <?php endif; ?>

        <!-- Tableau des absences -->
<?php if ($id_seance && count($absences) > 0): ?>
    <h3>Absences pour la séance du <?= htmlspecialchars($absences[0]['jour_seance']) ?> :</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Nom Étudiant</th>
                    <th>Prénom Étudiant</th>
                    <th>Notifier</th>
                  
                </tr>
            </thead>
            <tbody>
                <?php foreach ($absences as $absence): ?>
                    <tr>
                        <td><?= htmlspecialchars($absence['etudiant_nom']) ?></td>
                        <td><?= htmlspecialchars($absence['etudiant_prenom']) ?></td>
                        <td>
   
                        <button class="boutonNotifier" data-bs-toggle="modal" data-bs-target="#notifyModal" 
    data-id-badge="<?= $absence['id_badge'] ?>">Notifier</button>


                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($id_seance): ?>
    <p>Aucune absence enregistrée pour cette séance.</p>
<?php endif; ?>

<!-- Tableau des étudiants qui étaient absents à cette séance mais qui ont assisté à une autre séance -->
<?php if ($id_seance && count($etudiants_autres_seances_porte_false) > 0): ?>
    <h3>Étudiants assistant à une autre séance :</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Nom Étudiant</th>
                    <th>Prénom Étudiant</th>
                    <th>Date de la Séance</th>
                    <th>Heure de Début</th>
                    <th>Nom du Cours</th>
                    <th>Nom de la Salle</th>
                    <th>Heure d'Arrivée</th>
                    <th>Notifier</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etudiants_autres_seances_porte_false as $etudiant): ?>
                    <tr>
                        <td><?= htmlspecialchars($etudiant['etudiant_nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['etudiant_prenom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['jour_seance']) ?></td>
                        <td><?= htmlspecialchars($etudiant['heure_debut']) ?></td>
                        <td><?= htmlspecialchars($etudiant['cours_nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['salle_nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['heure_arrivee']) ?></td>
                        <td>
                        <button class=" boutonNotifier" data-bs-toggle="modal" data-bs-target="#notifyModal" 
    data-id-badge="<?= $etudiant['id_badge'] ?>">Notifier</button>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($id_seance): ?>
    <p>Aucun étudiant assistant à une autre séance.</p>
<?php endif; ?>

  <!-- Modal pour envoyer une notification -->

<div class="modal fade" id="notifyModal" tabindex="-1" aria-labelledby="notifyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notifyModalLabel">Envoyer une Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_badge" id="id_badge">
                        <input type="hidden" name="id_seance" id="id_seance" value="<?= $id_seance ?>">

                        <div class="mb-3">
                            <label for="type_notification" class="form-label">Type de Notification</label>
                            <select class="form-select" name="type_notification" id="type_notification" required>
                                <option value="retard">Retard</option>
                                <option value="absence">Absence</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="contenu_notification" class="form-label">Contenu</label>
                            <textarea class="form-control" name="contenu_notification" id="contenu_notification" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="submit_notification" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Ce script récupère l'identifiant de l'étudiant à partir du bouton notifier cliqué et la séance 
 en cours pour les insérer dans les champs cachés du formulaire -->
 
<script>
   document.querySelectorAll('.boutonNotifier').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const idBadge = this.getAttribute('data-id-badge');
            if (idBadge) {
                document.getElementById('id_badge').value = idBadge;
            }
            const idSeanceField = document.getElementById('id_seance');
            if (idSeanceField) {
                idSeanceField.value = "<?= $id_seance ?>";
            }
        });
      });



</script>
