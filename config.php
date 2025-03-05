<?php
// Connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'TP');
define('DB_PASS', 'password');
define('DB_NAME', 'transport_logistique');

function getConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}


