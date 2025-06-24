<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_login(); // Require login for this page

$username = $_SESSION['username'] ?? 'Usuário';
$profile = $_SESSION['user_profile'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistema de Ramais</title>
    <link rel="stylesheet" href="../css/admin_style.css"> <!-- We'll create this later -->
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Painel Administrativo</h1>
            <nav>
                <ul>
                    <li>Bem-vindo(a), <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($profile); ?>)</li>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php">Atribuir Ramais</a></li>
                    <?php if ($profile === 'Super-Admin'): ?>
                        <li><a href="manage_sectors.php">Gerenciar Setores</a></li>
                        <li><a href="manage_extensions.php">Gerenciar Ramais</a></li>
                        <li><a href="manage_users.php">Gerenciar Usuários</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container admin-main">
        <h2>Visão Geral</h2>
        <p>Bem-vindo(a) ao painel administrativo do Sistema de Lista de Ramais.</p>
        <p>Utilize o menu acima para navegar pelas funcionalidades disponíveis.</p>

        <!-- Placeholder for potential dashboard widgets or summaries -->
        <div class="dashboard-widgets">
            <div class="widget">
                <h3>Pessoas</h3>
                <p><a href="manage_persons.php">Ir para Gerenciamento de Pessoas</a></p>
            </div>
            <div class="widget">
                <h3>Atribuições de Ramais</h3>
                <p><a href="manage_assignments.php">Ir para Atribuições</a></p>
            </div>
            <?php if ($profile === 'Super-Admin'): ?>
            <div class="widget">
                <h3>Setores</h3>
                <p><a href="manage_sectors.php">Ir para Gerenciamento de Setores</a></p>
            </div>
            <div class="widget">
                <h3>Ramais</h3>
                <p><a href="manage_extensions.php">Ir para Gerenciamento de Ramais</a></p>
            </div>
            <div class="widget">
                <h3>Usuários</h3>
                <p><a href="manage_users.php">Ir para Gerenciamento de Usuários</a></p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Sistema de Lista de Ramais - Admin</p>
        </div>
    </footer>
</body>
</html>
