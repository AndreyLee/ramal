<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_login(); // Admins and Super-Admins can manage assignments

require_once __DIR__ . '/../../src/includes/db.php';

// Fetch persons for dropdown
try {
    $stmt_persons = $pdo->query("SELECT id, name FROM persons ORDER BY name");
    $persons = $stmt_persons->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching persons for assignments: " . $e->getMessage());
    $persons = [];
    $page_error = "Erro ao carregar pessoas.";
}

// Fetch extensions (assigned and unassigned) for listing and assigning
try {
    $sql_extensions = "SELECT e.id, e.number, e.type, e.status, s.name AS sector_name, p.name AS person_name, e.person_id
                       FROM extensions e
                       JOIN sectors s ON e.sector_id = s.id
                       LEFT JOIN persons p ON e.person_id = p.id
                       ORDER BY s.name, e.number";
    $stmt_extensions = $pdo->query($sql_extensions);
    $extensions = $stmt_extensions->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching extensions for assignments: " . $e->getMessage());
    $extensions = [];
    $page_error = (isset($page_error) ? $page_error . ' ' : '') . "Erro ao carregar ramais.";
}

$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atribuir Ramais - Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Atribuir Ramais</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php" class="active">Atribuir Ramais</a></li>
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

        <section class="list-section">
            <h2>Lista de Ramais e Atribuições</h2>
            <?php if (empty($extensions) && !isset($page_error)): ?>
                <p>Nenhum ramal cadastrado. Cadastre ramais em "Gerenciar Ramais" (Super-Admin).</p>
            <?php elseif (!empty($extensions)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th>Setor</th>
                            <th>Status</th>
                            <th>Atribuído a</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extensions as $ext): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ext['number']); ?></td>
                                <td><?php echo htmlspecialchars($ext['type']); ?></td>
                                <td><?php echo htmlspecialchars($ext['sector_name']); ?></td>
                                <td><span class="status <?php echo $ext['status'] === 'Vago' ? 'status-vago' : 'status-atribuido'; ?>"><?php echo htmlspecialchars($ext['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($ext['person_name'] ?: '---'); ?></td>
                                <td class="actions">
                                    <?php if ($ext['status'] === 'Vago'): ?>
                                        <form action="actions/assignment_action.php" method="POST" class="form-inline">
                                            <input type="hidden" name="extension_id" value="<?php echo $ext['id']; ?>">
                                            <select name="person_id" required>
                                                <option value="">Selecione uma pessoa</option>
                                                <?php foreach ($persons as $person): ?>
                                                    <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_extension" class="btn btn-assign">Atribuir</button>
                                        </form>
                                    <?php else: // Status 'Atribuído' ?>
                                        <form action="actions/assignment_action.php" method="POST" class="form-inline" onsubmit="return confirm('Tem certeza que deseja desatribuir este ramal?');">
                                            <input type="hidden" name="extension_id" value="<?php echo $ext['id']; ?>">
                                            <input type="hidden" name="current_person_id" value="<?php echo $ext['person_id']; ?>">
                                            <button type="submit" name="unassign_extension" class="btn btn-delete">Desatribuir</button>
                                        </form>
                                        <button onclick="showEditModal(<?php echo $ext['id']; ?>, <?php echo $ext['person_id']; ?>, '<?php echo htmlspecialchars(addslashes($ext['person_name'])); ?>', '<?php echo htmlspecialchars($ext['number']); ?>')" class="btn btn-edit">Alterar Atribuição</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <!-- Modal for Editing Assignment -->
    <div id="editAssignmentModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditModal()">&times;</span>
            <h3>Alterar Atribuição do Ramal <span id="modalExtensionNumber"></span></h3>
            <form action="actions/assignment_action.php" method="POST">
                <input type="hidden" name="extension_id" id="modalExtensionId">
                <input type="hidden" name="current_person_id" id="modalCurrentPersonId">
                <div class="form-group">
                    <label for="modalPersonId">Atribuir a:</label>
                    <select name="new_person_id" id="modalPersonId" required>
                        <option value="">Selecione uma nova pessoa</option>
                        <?php foreach ($persons as $person): ?>
                            <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p>Atualmente atribuído a: <strong id="modalCurrentPersonName"></strong></p>
                <button type="submit" name="update_assignment" class="btn">Salvar Alteração</button>
            </form>
        </div>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Sistema de Lista de Ramais - Admin</p>
        </div>
    </footer>

    <script>
        const modal = document.getElementById('editAssignmentModal');
        const modalExtensionNumber = document.getElementById('modalExtensionNumber');
        const modalExtensionId = document.getElementById('modalExtensionId');
        const modalCurrentPersonId = document.getElementById('modalCurrentPersonId');
        const modalCurrentPersonName = document.getElementById('modalCurrentPersonName');
        const modalPersonIdSelect = document.getElementById('modalPersonId');

        function showEditModal(extensionId, currentPersonId, currentPersonName, extensionNumber) {
            modalExtensionId.value = extensionId;
            modalCurrentPersonId.value = currentPersonId;
            modalExtensionNumber.textContent = extensionNumber;
            modalCurrentPersonName.textContent = currentPersonName;

            // Reset select to default and then select the current person if needed,
            // or rather, ensure the current person is not pre-selected as the "new" one.
            modalPersonIdSelect.value = '';

            modal.style.display = 'block';
        }

        function closeEditModal() {
            modal.style.display = 'none';
        }

        // Close modal if clicked outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
