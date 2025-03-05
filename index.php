<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();

// Fonction pour rechercher les camions
function rechercheCamions($type, $ville) {
    global $conn;
    $sql = "SELECT 
                c.immatriculation, 
                c.type, 
                a.ville_depart, 
                a.ville_arrivee,
                GROUP_CONCAT(m.nom SEPARATOR ', ') as marchandises
            FROM Camion c
            JOIN Affectation a ON c.immatriculation = a.immatriculation
            JOIN Cargaison car ON a.id = car.id_affectation
            JOIN Marchandise m ON car.numero_marchandise = m.numero
            WHERE c.type = ? AND a.ville_arrivee = ?
            GROUP BY c.immatriculation, a.date";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$type, $ville]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction de localisation des camions
function localiserCamions($type, $date) {
    global $conn;
    $sql = "SELECT 
                c.immatriculation, 
                c.type, 
                a.localisation_matin, 
                a.localisation_soir
            FROM Camion c
            LEFT JOIN Affectation a ON c.immatriculation = a.immatriculation AND a.date = ?
            WHERE c.type = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$date, $type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement des formulaires
function handleFormSubmissions($conn) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            switch($_POST['action']) {
                // Insertion d'un camion
                case 'ajouter_camion':
                    $stmt = $conn->prepare("INSERT INTO Camion (immatriculation, type, poids_max) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $_POST['immatriculation'],
                        $_POST['type'],
                        $_POST['poids_max']
                    ]);
                    $_SESSION['message'] = "Camion ajouté avec succès !";
                    break;

                // Modification d'un chauffeur
                case 'modifier_chauffeur':
                    $stmt = $conn->prepare("UPDATE Chauffeur SET nom = ?, prenom = ? WHERE num_permis = ?");
                    $stmt->execute([
                        $_POST['nom'],
                        $_POST['prenom'],
                        $_POST['num_permis']
                    ]);
                    $_SESSION['message'] = "Chauffeur modifié avec succès !";
                    break;

                // Suppression d'un chauffeur
                case 'supprimer_chauffeur':
                    $stmt = $conn->prepare("DELETE FROM Chauffeur WHERE num_permis = ?");
                    $stmt->execute([$_POST['num_permis']]);
                    $_SESSION['message'] = "Chauffeur supprimé avec succès !";
                    break;
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }

        // Redirection pour éviter les soumissions multiples
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Exécution des soumissions de formulaire
handleFormSubmissions($conn);

// Récupération des données pour les listes déroulantes
$types = $conn->query("SELECT DISTINCT type FROM Camion")->fetchAll(PDO::FETCH_COLUMN);
$villes = $conn->query("SELECT DISTINCT ville_arrivee FROM Affectation")->fetchAll(PDO::FETCH_COLUMN);
$chauffeurs = $conn->query("SELECT * FROM Chauffeur")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Logistique - Tableau de Bord</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icônes Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f4f6f9; }
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border: none;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body class="container-fluid p-4">
<div class="row">
    <div class="col-12">
        <h1 class="mb-4 text-center">
            <i class="bi bi-truck"></i> Tableau de Bord Logistique
        </h1>

        <!-- Messages de notification -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Insertion de Camion -->
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="bi bi-plus-circle"></i> Ajouter un Camion</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="ajouter_camion">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="immatriculation" class="form-control" placeholder="Immatriculation" required>
                        </div>
                        <div class="col-md-4">
                            <select name="type" class="form-select" required>
                                <option value="">Type de Camion</option>
                                <option value="frigo">Frigo</option>
                                <option value="citerne">Citerne</option>
                                <option value="palette">Palette</option>
                                <option value="plateau">Plateau</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="poids_max" class="form-control" placeholder="Poids max (kg)" required>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Gestion des Chauffeurs -->
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="bi bi-people"></i> Gestion des Chauffeurs</span>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>N° Permis</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($chauffeurs as $chauffeur): ?>
                        <tr>
                            <td><?= htmlspecialchars($chauffeur['num_permis']) ?></td>
                            <td><?= htmlspecialchars($chauffeur['nom']) ?></td>
                            <td><?= htmlspecialchars($chauffeur['prenom']) ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modifierChauffeur<?= $chauffeur['num_permis'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer ce chauffeur ?');">
                                        <input type="hidden" name="action" value="supprimer_chauffeur">
                                        <input type="hidden" name="num_permis" value="<?= $chauffeur['num_permis'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Modal de modification -->
                                <div class="modal fade" id="modifierChauffeur<?= $chauffeur['num_permis'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier Chauffeur</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="modifier_chauffeur">
                                                    <input type="hidden" name="num_permis" value="<?= $chauffeur['num_permis'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nom</label>
                                                        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($chauffeur['nom']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Prénom</label>
                                                        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($chauffeur['prenom']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Localisation des Camions -->
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-geo-alt"></i> Localisation des Camions</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <select name="type_loc" class="form-select" required>
                            <option value="">Type de Camion</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="date" name="date_loc" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Localiser
                        </button>
                    </div>
                </form>

                <?php if (isset($_GET['type_loc']) && isset($_GET['date_loc'])): ?>
                    <?php $localisations = localiserCamions($_GET['type_loc'], $_GET['date_loc']); ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Immatriculation</th>
                                <th>Position Matin</th>
                                <th>Position Soir</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($localisations as $l): ?>
                                <tr>
                                    <td><?= htmlspecialchars($l['immatriculation']) ?></td>
                                    <td><?= htmlspecialchars($l['localisation_matin'] ?? 'Non renseigné') ?></td>
                                    <td><?= htmlspecialchars($l['localisation_soir'] ?? 'Non renseigné') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>