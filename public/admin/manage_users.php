<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can manage users

require_once __DIR__ . '/../../src/includes/db.php';

// Fetch all users for listing
try {
    $stmt = $pdo->query("SELECT id, username, profile FROM users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $page_error = "Erro ao carregar usuários. Tente novamente.";
}

$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);

$edit_user_data = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, profile FROM users WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_id'], PDO::PARAM_INT);
        $stmt->execute();
        $edit_user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_user_data) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Usuário não encontrado para edição.'];
            header('Location: manage_users.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching user for edit: " . $e->getMessage());
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao carregar dados do usuário para edição.'];
        header('Location: manage_users.php');
        exit;
    }
}
$user_profiles = ['Admin', 'Super-Admin']; // Available profiles
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Super Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Gerenciar Usuários</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php">Atribuir Ramais</a></li>
                    <li><a href="manage_sectors.php">Gerenciar Setores</a></li>
                    <li><a href="manage_extensions.php">Gerenciar Ramais</a></li>
                    <li><a href="manage_users.php" class="active">Gerenciar Usuários</a></li>
                    <li><a href="logout.php">Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container admin-main">
        <?php if (isset($page_error)): ?>
            <p class="message error-message"><?php echo htmlspecialchars($page_error); ?></p>
        <?php endif; ?>

        <?php if ($feedback_message): ?>
            <p class="message <?php echo htmlspecialchars($feedback_message['type'] === 'success' ? 'success-message' : 'error-message'); ?>">
                <?php echo htmlspecialchars($feedback_message['text']); ?>
            </p>
        <?php endif; ?>

        <section class="form-section">
            <h2><?php echo $edit_user_data ? 'Editar Usuário' : 'Adicionar Novo Usuário'; ?></h2>
            <form action="actions/user_action.php" method="POST">
                <?php if ($edit_user_data): ?>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user_data['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">Nome de Usuário:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($edit_user_data['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="profile">Perfil:</label>
                    <select id="profile" name="profile" required>
                        <?php foreach ($user_profiles as $profile_option): ?>
                            <option value="<?php echo $profile_option; ?>" <?php echo (isset($edit_user_data['profile']) && $edit_user_data['profile'] === $profile_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($profile_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" <?php echo $edit_user_data ? '' : 'required'; ?>>
                    <?php if ($edit_user_data): ?>
                        <small>Deixe em branco para não alterar a senha atual.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" <?php echo $edit_user_data ? '' : 'required'; ?>>
                </div>

                <button type="submit" name="<?php echo $edit_user_data ? 'update_user' : 'add_user'; ?>" class="btn">
                    <?php echo $edit_user_data ? 'Atualizar Usuário' : 'Adicionar Usuário'; ?>
                </button>
                <?php if ($edit_user_data): ?>
                    <a href="manage_users.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="list-section">
            <h2>Usuários Cadastrados</h2>
            <?php if (empty($users) && !isset($page_error)): ?>
                <p>Nenhum usuário cadastrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome de Usuário</th>
                            <th>Perfil</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['profile']); ?></td>
                                <td class="actions">
                                    <a href="manage_users.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-edit">Editar</a>
                                    <?php // Prevent deleting the currently logged-in user or the last Super-Admin ?>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form action="actions/user_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-delete">Excluir</button>
                                    </form>
                                    <?php else: ?>
                                        <button class="btn btn-delete" disabled title="Você não pode excluir a si mesmo.">Excluir</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Sistema de Lista de Ramais - Admin</p>
        </div>
    </footer>
</body>
</html>
