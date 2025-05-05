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
$email = '';
$remember = false;
$error = '';

// Check for registration success message
$registration_success = '';
if (isset($_SESSION['registration_success'])) {
    $registration_success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate credentials
    if (empty($email) || empty($password)) {
        $error = 'Veuillez entrer votre email et votre mot de passe';
    } else {
        // Attempt to authenticate user
        $user = authenticateUser($email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = generateRandomString(32);
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $tokenData = [
                    'user_id' => $user['id'],
                    'token' => password_hash($token, PASSWORD_DEFAULT),
                    'expires_at' => date('Y-m-d H:i:s', $expiry)
                ];
                
                insert('remember_tokens', $tokenData);
                
                // Set cookie
                setcookie('remember_token', $user['id'] . ':' . $token, $expiry, '/', '', false, true);
            }
            
            // Redirect to home page
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    list($userId, $token) = explode(':', $_COOKIE['remember_token']);
    
    // Get token from database
    $storedToken = fetchRow(
        "SELECT * FROM remember_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1",
        [$userId],
        "i"
    );
    
    if ($storedToken && password_verify($token, $storedToken['token'])) {
        // Get user
        $user = getUserById($userId);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect to home page
            header('Location: index.php');
            exit;
        }
    }
    
    // Invalid token, clear cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SocialConnect</title>
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
                        <h2 class="mb-0">Connexion</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($registration_success): ?>
                            <div class="alert alert-success"><?php echo $registration_success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="login.php">
                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember" 
                                       <?php echo $remember ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Connexion</button>
                            </div>
                            
                            <!-- Forgot Password -->
                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="text-decoration-none">Mot de passe oublié?</a>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <p class="mb-0">Vous n'avez pas de compte? <a href="register.php">Inscrivez-vous</a></p>
                    </div>
                </div>
                
                <!-- Social Login -->
                <div class="card mt-4 shadow">
                    <div class="card-body p-4">
                        <h5 class="card-title text-center mb-4">Ou connectez-vous avec</h5>
                        <div class="d-grid gap-2">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fab fa-facebook-f me-2"></i> Facebook
                            </a>
                            <a href="#" class="btn btn-outline-danger">
                                <i class="fab fa-google me-2"></i> Google
                            </a>
                        </div>
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