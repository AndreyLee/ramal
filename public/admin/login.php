<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to admin dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error message after displaying
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sistema de Ramais</title>
    <link rel="stylesheet" href="../css/admin_style.css"> <!-- We'll create this later -->
</head>
<body>
    <div class="login-container">
        <h2>Login Administrativo</h2>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form action="actions/login_action.php" method="POST">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            <a href="../index.php">Voltar para a Lista Pública</a>
        </p>
    </div>
</body>
</html>
