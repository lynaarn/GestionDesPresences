# Nom du Projet

Système de gestion des présences avec badge NFC pour une université (Application Web)

## Description

Dans le cadre de la transition numérique des établissements scolaires, cette application web propose un système automatisé de gestion des présences, remplaçant les méthodes manuelles obsolètes et permettant un suivi en temps réel, tout en réduisant les erreurs et les abus.

## Détails des Fichiers PHP

**index.php**
Page d'accueil de l'application. Elle affiche les informations principales de notre site et le lien vers le formulaire de connexion.

**login.php**
Ce fichier contient le formulaire de connexion, ainsi que la vérification des données entrées, afin que l'enseignant ou l'étudiant puisse se connecter.

**presences.php**
Ce fichier permet à l'enseignant connecté de gérer les absences et présences des étudiants :

- Afficher la liste des étudiants présents avec les différentes informations
- Afficher la liste des étudiants absents avec les différentes informations
- Afficher la liste des étudiants absents à la séance donnée et qui ont assisté à une autre séance, afin de récupérer la séance manquée
- Donner la possibilité à l'enseignant d'envoyer des notifications aux étudiants présents, absents, ou ayant assisté à une autre séance

**seance.php**
Ce fichier est utilisé pour afficher toutes les séances auxquelles un enseignant connecté est concerné,avec la possibilité de filtrer selon le nom ou le type de cours.

**cours.php**
Ce fichier est utilisé pour afficher les cours qu'un enseignant connecté enseigne.

**seanceEtu.php**
Ce fichier permet à l'étudiant connecté de visualiser les séances qu'il a, avec la possibilité de filtrer selon le nom ou le type de cours.

**coursEtu.php**
Ce fichier permet à l'étudiant connecté de visualiser tous les cours qui le concernent.

**notificationEtu.php**
Ce fichier permet à l'étudiant connecté de visualiser toutes les notifications qu'il a reçues d'un enseignant pour une séance particulière.

**logout.php**
Ce fichier permet de terminer la session de l'enseignant ou de l'étudiant connecté. Lorsqu'il est appelé, il met fin à la session en cours, déconnecte l'utilisateur et redirige vers la page d'accueil (`index.php`).

## Fichier sql

**BaseDeDonnee.sql**
Dans le fichier SQL, on retrouve toutes les tables insérées dans la base de données, ainsi que les triggers utilisés pour assurer la cohérence, ainsi que les insertions effectuées dans la base de données avec les explications données.

## Auteur
  Aourane Lyna Ines
- **Université :** CY Cergy Paris Université
- **Date :** 03-12-2024
