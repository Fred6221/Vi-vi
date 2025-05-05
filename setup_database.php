<?php
session_start();
require_once 'config/database.php';

// Function to execute SQL statements
function executeSQLFile($conn, $sql) {
    // Split SQL statements by semicolon
    $statements = explode(';', $sql);
    
    $success = true;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                if ($conn->query($statement) === false) {
                    $success = false;
                    $errors[] = "Erreur lors de l'exécution de la requête: " . $conn->error . " - Requête: " . $statement;
                }
            } catch (Exception $e) {
                $success = false;
                $errors[] = "Exception lors de l'exécution de la requête: " . $e->getMessage() . " - Requête: " . $statement;
            }
        }
    }
    
    return ['success' => $success, 'errors' => $errors];
}

// Get database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if tables already exist
$tablesExist = false;
try {
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    $tablesExist = $result && $result->num_rows > 0;
} catch (Exception $e) {
    // Table doesn't exist
}

// Read SQL file content
$sqlFile = file_get_contents('database.sql');

if ($sqlFile === false) {
    die("Impossible de lire le fichier SQL.");
}

// Execute SQL statements
$result = executeSQLFile($conn, $sqlFile);

// Display result
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration de la base de données - Vi-vi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Configuration de la base de données Vi-vi</h3>
            </div>
            <div class="card-body">
                <?php if ($tablesExist): ?>
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle me-2"></i> Information</h4>
                        <p>Les tables nécessaires existent déjà dans la base de données.</p>
                    </div>
                <?php elseif ($result['success']): ?>
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle me-2"></i> Succès!</h4>
                        <p>Toutes les tables ont été créées avec succès.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-exclamation-triangle me-2"></i> Erreurs!</h4>
                        <p>Des erreurs sont survenues lors de la création des tables:</p>
                        <ul>
                            <?php foreach ($result['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>