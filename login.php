<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, login, password, role FROM Logins WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && ($password === $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit();
    } else {
        $error = 'Login ou mot de passe incorrect';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion - Gestion Logistique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-4">Connexion</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Login</label>
                <input type="text" name="login" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
    </div>
</div>
</body>
</html>