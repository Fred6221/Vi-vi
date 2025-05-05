<?php
// Display error message if login failed
if (isset($_SESSION['login_error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['login_error'] . '</div>';
    unset($_SESSION['login_error']);
}
?>

<form action="actions/login.php" method="post">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
    </div>
    
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Se souvenir de moi</label>
    </div>
    
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">Connexion</button>
    </div>
    
    <div class="text-center mt-3">
        <a href="forgot_password.php" class="text-decoration-none">Mot de passe oublié?</a>
    </div>
</form>