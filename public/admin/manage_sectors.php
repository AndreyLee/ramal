<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can manage sectors

require_once __DIR__ . '/../../src/includes/db.php';

// Fetch all sectors for listing
try {
    $stmt = $pdo->query("SELECT s.id, s.name, COUNT(e.id) AS extension_count
                         FROM sectors s
                         LEFT JOIN extensions e ON s.id = e.sector_id
                         GROUP BY s.id, s.name
                         ORDER BY s.name");
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching sectors: " . $e->getMessage());
    $sectors = [];
    $page_error = "Erro ao carregar setores. Tente novamente.";
}

$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);

$edit_sector_data = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM sectors WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_id'], PDO::PARAM_INT);
        $stmt->execute();
        $edit_sector_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_sector_data) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Setor não encontrado para edição.'];
            header('Location: manage_sectors.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching sector for edit: " . $e->getMessage());
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao carregar dados do setor para edição.'];
        header('Location: manage_sectors.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Setores - Super Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Gerenciar Setores</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php">Atribuir Ramais</a></li>
                    <li><a href="manage_sectors.php" class="active">Gerenciar Setores</a></li>
                    <li><a href="manage_extensions.php">Gerenciar Ramais</a></li>
                    <li><a href="manage_users.php">Gerenciar Usuários</a></li>
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
            <h2><?php echo $edit_sector_data ? 'Editar Setor' : 'Adicionar Novo Setor'; ?></h2>
            <form action="actions/sector_action.php" method="POST">
                <?php if ($edit_sector_data): ?>
                    <input type="hidden" name="sector_id" value="<?php echo htmlspecialchars($edit_sector_data['id']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Nome do Setor:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_sector_data['name'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="<?php echo $edit_sector_data ? 'update_sector' : 'add_sector'; ?>" class="btn">
                    <?php echo $edit_sector_data ? 'Atualizar Setor' : 'Adicionar Setor'; ?>
                </button>
                <?php if ($edit_sector_data): ?>
                    <a href="manage_sectors.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="list-section">
            <h2>Setores Cadastrados</h2>
            <?php if (empty($sectors) && !isset($page_error)): ?>
                <p>Nenhum setor cadastrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome do Setor</th>
                            <th>Nº de Ramais</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sectors as $sector): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sector['name']); ?></td>
                                <td><?php echo htmlspecialchars($sector['extension_count']); ?></td>
                                <td class="actions">
                                    <a href="manage_sectors.php?edit_id=<?php echo $sector['id']; ?>" class="btn btn-edit">Editar</a>
                                    <form action="actions/sector_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este setor? Se houver ramais associados, a exclusão será impedida.');">
                                        <input type="hidden" name="sector_id" value="<?php echo $sector['id']; ?>">
                                        <button type="submit" name="delete_sector" class="btn btn-delete">Excluir</button>
                                    </form>
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
