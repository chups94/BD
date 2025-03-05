<?php
require_once 'config.php';

// Fonction de débogage pour vérifier les informations de connexion//
function debugLogin($login, $password) {
    $conn = getConnection();

    // Requête pour afficher toutes les informations de l'utilisateur//
    $stmt = $conn->prepare("SELECT * FROM Logins WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Informations de l'utilisateur trouvées :\n";
    print_r($user);

    echo "\nParamètres de test :\n";
    echo "Login saisi : $login\n";
    echo "Mot de passe saisi : $password\n";

    if ($user) {
        echo "\nComparaison des mots de passe :\n";
        echo "Mot de passe stocké : " . $user['password'] . "\n";
        echo "Mot de passe saisi : $password\n";

        if ($password === $user['password']) {
            echo "Mot de passe correct\n";
        } else {
            echo " Mot de passe incorrect\n";
        }
    } else {
        echo " Aucun utilisateur trouvé avec ce login\n";
    }
}

// Appelez cette fonction avec vos identifiants de test
debugLogin('jean.dupont', 'chauffeur123');

