<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_login(); // Admins and Super-Admins can manage persons

require_once __DIR__ . '/../../src/includes/db.php';

// Fetch all persons for listing
try {
    $stmt = $pdo->query("SELECT p.id, p.name, COUNT(e.id) AS extension_count
                         FROM persons p
                         LEFT JOIN extensions e ON p.id = e.person_id
                         GROUP BY p.id, p.name
                         ORDER BY p.name");
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching persons: " . $e->getMessage());
    $persons = [];
    $page_error = "Erro ao carregar pessoas. Tente novamente.";
}

$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);

$edit_person_data = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM persons WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_id'], PDO::PARAM_INT);
        $stmt->execute();
        $edit_person_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_person_data) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Pessoa não encontrada para edição.'];
            header('Location: manage_persons.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching person for edit: " . $e->getMessage());
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao carregar dados para edição.'];
        header('Location: manage_persons.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pessoas - Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Gerenciar Pessoas</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php" class="active">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php">Atribuir Ramais</a></li>
                    <?php if ($_SESSION['user_profile'] === 'Super-Admin'): ?>
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
        <?php if (isset($page_error)): ?>
            <p class="message error-message"><?php echo htmlspecialchars($page_error); ?></p>
        <?php endif; ?>

        <?php if ($feedback_message): ?>
            <p class="message <?php echo htmlspecialchars($feedback_message['type'] === 'success' ? 'success-message' : 'error-message'); ?>">
                <?php echo htmlspecialchars($feedback_message['text']); ?>
            </p>
        <?php endif; ?>

        <section class="form-section">
            <h2><?php echo $edit_person_data ? 'Editar Pessoa' : 'Adicionar Nova Pessoa'; ?></h2>
            <form action="actions/person_action.php" method="POST">
                <?php if ($edit_person_data): ?>
                    <input type="hidden" name="person_id" value="<?php echo htmlspecialchars($edit_person_data['id']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="name">Nome:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_person_data['name'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="<?php echo $edit_person_data ? 'update_person' : 'add_person'; ?>" class="btn">
                    <?php echo $edit_person_data ? 'Atualizar Pessoa' : 'Adicionar Pessoa'; ?>
                </button>
                <?php if ($edit_person_data): ?>
                    <a href="manage_persons.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="list-section">
            <h2>Pessoas Cadastradas</h2>
            <?php if (empty($persons) && !isset($page_error)): ?>
                <p>Nenhuma pessoa cadastrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Ramais Atribuídos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($persons as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['name']); ?></td>
                                <td><?php echo htmlspecialchars($person['extension_count']); ?></td>
                                <td class="actions">
                                    <a href="manage_persons.php?edit_id=<?php echo $person['id']; ?>" class="btn btn-edit">Editar</a>
                                    <form action="actions/person_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta pessoa? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                                        <button type="submit" name="delete_person" class="btn btn-delete">Excluir</button>
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
