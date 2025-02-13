<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion (index.php)
header("Location: index.php");
exit; // Assurez-vous de terminer le script
?>
