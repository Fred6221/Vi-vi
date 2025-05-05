<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Initialize errors array
    $errors = [];
    
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
    
    // Validate terms
    if (!$terms) {
        $errors['terms'] = 'Vous devez accepter les conditions d\'utilisation';
    }
    
    // If there are errors, store them in session and redirect back to registration form
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['registration_data'] = [
            'name' => $name,
            'email' => $email,
            'birthdate' => $birthdate,
            'gender' => $gender
        ];
        
        header('Location: ../register.php');
        exit;
    }
    
    // Register user
    $userId = registerUser($name, $email, $password, $birthdate, $gender);
    
    if ($userId) {
        // Create default profile settings
        $profileData = [
            'user_id' => $userId,
            'privacy_profile' => 'public',
            'privacy_posts' => 'friends',
            'privacy_friends' => 'public',
            'email_notifications' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        insert('user_settings', $profileData);
        
        // Create registration activity log
        $activityData = [
            'user_id' => $userId,
            'activity_type' => 'registration',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        insert('user_activities', $activityData);
        
        // Set success message
        $_SESSION['registration_success'] = 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.';
        
        // Redirect to login page
        header('Location: ../login.php');
        exit;
    } else {
        // Registration failed
        $_SESSION['registration_errors'] = ['general' => 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.'];
        $_SESSION['registration_data'] = [
            'name' => $name,
            'email' => $email,
            'birthdate' => $birthdate,
            'gender' => $gender
        ];
        
        header('Location: ../register.php');
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header('Location: ../register.php');
    exit;
}