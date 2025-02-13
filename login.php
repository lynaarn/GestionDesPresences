<?php
// Démarrer la session
session_start();

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
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification du formulaire de connexion
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérification des informations d'identification
    if (!empty($username) && !empty($password)) {
        // Requête pour récupérer l'ID, le rôle et le mot de passe haché
        $stmt = $pdo->prepare("SELECT id_personne, role, mot_de_passe FROM personne WHERE login = :username");
        $stmt->execute(['username' => $username]);

        if ($stmt->rowCount() == 1) {
            // Récupérer les données utilisateur
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification du mot de passe haché
            if (password_verify($password, $user['mot_de_passe'])) {
                // Stocker les informations dans la session
                $_SESSION['id_personne'] = $user['id_personne'];
                $_SESSION['role'] = $user['role'];

                // Redirection selon le rôle
                if ($user['role'] === 'enseignant') {
                    header("Location: presences.php");
                    exit; // Terminer le script après la redirection
                } else {
                    // Redirigez vers une page générale ou appropriée pour les autres rôles
                    header("Location: seanceEtu.php");
                    exit;
                }
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        $error = "Veuillez entrer le nom d'utilisateur et le mot de passe.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/favicon.png" type="image/x-icon" />
    <title>Connexion</title>
</head>
<body class="login-page">
    <div class="container">
        <!-- Formulaire de connexion-->
        <div class="form-section">
      
            <h2>Formulaire de Connexion</h2>
            <?php if ($error): ?>
                <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" name="username" id="username" required>

                <label for="password">Mot de passe :</label>
                <input type="password" name="password" id="password" required>

                <button type="submit">Se connecter</button>
            </form>
        </div>

      
        <div class="image-section">
            <a href=index.php>
            <img src="images/logo2.png" alt="Login Image">
            </a>
        </div>
    </div>
</body>
</html>
