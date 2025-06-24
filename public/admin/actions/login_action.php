<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../src/includes/db.php'; // Adjusted path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = 'Usuário e senha são obrigatórios.';
        header('Location: ../login.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, profile FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_profile'] = $user['profile']; // 'Admin' or 'Super-Admin'

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Redirect to the admin dashboard or a protected page
            header('Location: ../index.php'); // Assuming admin dashboard is at admin/index.php
            exit();
        } else {
            // Invalid credentials
            $_SESSION['login_error'] = 'Usuário ou senha inválidos.';
            header('Location: ../login.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Login Action Error: " . $e->getMessage());
        $_SESSION['login_error'] = 'Erro no sistema. Tente novamente mais tarde.';
        header('Location: ../login.php');
        exit();
    }
} else {
    // Not a POST request, redirect to login page
    header('Location: ../login.php');
    exit();
}
?>
