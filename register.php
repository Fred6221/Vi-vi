<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$name = '';
$email = '';
$birthdate = '';
$gender = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Le nom est requis';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'L\'email est requis';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email n\'est pas valide';
    } elseif (getUserByEmail($email)) {
        $errors['email'] = 'Cet email est déjà utilisé';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Le mot de passe est requis';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
    }
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Les mots de passe ne correspondent pas';
    }
    
    // Validate birthdate
    if (empty($birthdate)) {
        $errors['birthdate'] = 'La date de naissance est requise';
    }
    
    // Validate gender
    if (empty($gender)) {
        $errors['gender'] = 'Le genre est requis';
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $userId = registerUser($name, $email, $password, $birthdate, $gender);
        
        if ($userId) {
            // Set success message
            $_SESSION['registration_success'] = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
            
            // Redirect to login page
            header('Location: login.php');
            exit;
        } else {
            $errors['general'] = 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - SocialConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h2 class="mb-0">Créer un compte</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>
                        
                        <form id="registration-form" method="post" action="register.php" novalidate>
                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom complet</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Le mot de passe doit contenir au moins 6 caractères.</div>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" name="confirm_password" required>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Birthdate -->
                            <div class="mb-3">
                                <label for="birthdate" class="form-label">Date de naissance</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" class="form-control <?php echo isset($errors['birthdate']) ? 'is-invalid' : ''; ?>" 
                                           id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" required>
                                    <?php if (isset($errors['birthdate'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['birthdate']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Gender -->
                            <div class="mb-4">
                                <label class="form-label">Genre</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                               type="radio" name="gender" id="gender-male" value="male" 
                                               <?php echo $gender === 'male' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="gender-male">Homme</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                               type="radio" name="gender" id="gender-female" value="female" 
                                               <?php echo $gender === 'female' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="gender-female">Femme</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                               type="radio" name="gender" id="gender-other" value="other" 
                                               <?php echo $gender === 'other' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="gender-other">Autre</label>
                                    </div>
                                    <?php if (isset($errors['gender'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo $errors['gender']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    J'accepte les <a href="terms.php" target="_blank">conditions d'utilisation</a> et la 
                                    <a href="privacy.php" target="_blank">politique de confidentialité</a>
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">S'inscrire</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <p class="mb-0">Vous avez déjà un compte? <a href="login.php">Connectez-vous</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>