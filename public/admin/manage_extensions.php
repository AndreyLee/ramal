<?php
require_once __DIR__ . '/../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can manage extensions comprehensively

require_once __DIR__ . '/../../src/includes/db.php';

// Fetch sectors for dropdowns
try {
    $stmt_sectors = $pdo->query("SELECT id, name FROM sectors ORDER BY name");
    $sectors = $stmt_sectors->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching sectors for extensions page: " . $e->getMessage());
    $sectors = [];
    $page_error = "Erro ao carregar setores.";
}

// Fetch persons for dropdowns (for assigning)
try {
    $stmt_persons = $pdo->query("SELECT id, name FROM persons ORDER BY name");
    $persons = $stmt_persons->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching persons for extensions page: " . $e->getMessage());
    $persons = []; // Keep it empty, but don't overwrite page_error from sectors if it exists
    $page_error = (isset($page_error) ? $page_error . ' ' : '') . "Erro ao carregar pessoas.";
}


// Filters and Search
$filter_type = $_GET['filter_type'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_sector = $_GET['filter_sector'] ?? '';
$search_term = $_GET['search_term'] ?? '';

$sql_extensions = "SELECT e.id, e.number, e.type, e.status, e.sector_id, s.name AS sector_name, e.person_id, p.name AS person_name
                   FROM extensions e
                   JOIN sectors s ON e.sector_id = s.id
                   LEFT JOIN persons p ON e.person_id = p.id
                   WHERE 1=1"; // Base condition

$params = [];
if (!empty($filter_type)) {
    $sql_extensions .= " AND e.type = :type";
    $params[':type'] = $filter_type;
}
if (!empty($filter_status)) {
    $sql_extensions .= " AND e.status = :status";
    $params[':status'] = $filter_status;
}
if (!empty($filter_sector)) {
    $sql_extensions .= " AND e.sector_id = :sector_id";
    $params[':sector_id'] = $filter_sector;
}
if (!empty($search_term)) {
    $sql_extensions .= " AND (e.number LIKE :search_term OR p.name LIKE :search_term)";
    $params[':search_term'] = '%' . $search_term . '%';
}
$sql_extensions .= " ORDER BY s.name, e.number";

try {
    $stmt_extensions = $pdo->prepare($sql_extensions);
    $stmt_extensions->execute($params);
    $extensions_list = $stmt_extensions->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching extensions list: " . $e->getMessage());
    $extensions_list = [];
    $page_error = (isset($page_error) ? $page_error . ' ' : '') . "Erro ao carregar lista de ramais.";
}


$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);

$edit_extension_data = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, number, type, status, sector_id, person_id FROM extensions WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit_id'], PDO::PARAM_INT);
        $stmt->execute();
        $edit_extension_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_extension_data) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ramal não encontrado para edição.'];
            header('Location: manage_extensions.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching extension for edit: " . $e->getMessage());
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao carregar dados do ramal para edição.'];
        header('Location: manage_extensions.php');
        exit;
    }
}

$extension_types = ['Interno', 'Externo']; // Define available types
$extension_statuses = ['Vago', 'Atribuído']; // Define available statuses

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Ramais - Super Admin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <h1>Gerenciar Ramais (Completo)</h1>
            <nav>
                 <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="manage_persons.php">Gerenciar Pessoas</a></li>
                    <li><a href="manage_assignments.php">Atribuir Ramais</a></li>
                    <li><a href="manage_sectors.php">Gerenciar Setores</a></li>
                    <li><a href="manage_extensions.php" class="active">Gerenciar Ramais</a></li>
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
            <h2><?php echo $edit_extension_data ? 'Editar Ramal' : 'Adicionar Novo Ramal'; ?></h2>
            <form action="actions/extension_action.php" method="POST">
                <?php if ($edit_extension_data): ?>
                    <input type="hidden" name="extension_id" value="<?php echo htmlspecialchars($edit_extension_data['id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="number">Número do Ramal:</label>
                    <input type="text" id="number" name="number" value="<?php echo htmlspecialchars($edit_extension_data['number'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="type">Tipo:</label>
                    <select id="type" name="type" required>
                        <?php foreach ($extension_types as $type_option): ?>
                            <option value="<?php echo $type_option; ?>" <?php echo (isset($edit_extension_data['type']) && $edit_extension_data['type'] === $type_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sector_id">Setor:</label>
                    <select id="sector_id" name="sector_id" required>
                        <option value="">Selecione um setor</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?php echo $sector['id']; ?>" <?php echo (isset($edit_extension_data['sector_id']) && $edit_extension_data['sector_id'] == $sector['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sector['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required onchange="togglePersonAssignment(this.value)">
                        <?php foreach ($extension_statuses as $status_option): ?>
                            <option value="<?php echo $status_option; ?>" <?php echo (isset($edit_extension_data['status']) && $edit_extension_data['status'] === $status_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="person_assignment_group" style="<?php echo (isset($edit_extension_data['status']) && $edit_extension_data['status'] === 'Atribuído') || (empty($edit_extension_data) && ($extension_statuses[0] === 'Atribuído')) ? '' : 'display:none;'; ?>">
                    <label for="person_id">Atribuir a (Pessoa):</label>
                    <select id="person_id" name="person_id">
                        <option value="">Nenhuma (Ramal Vago)</option>
                        <?php foreach ($persons as $person): ?>
                            <option value="<?php echo $person['id']; ?>" <?php echo (isset($edit_extension_data['person_id']) && $edit_extension_data['person_id'] == $person['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($person['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="<?php echo $edit_extension_data ? 'update_extension' : 'add_extension'; ?>" class="btn">
                    <?php echo $edit_extension_data ? 'Atualizar Ramal' : 'Adicionar Ramal'; ?>
                </button>
                <?php if ($edit_extension_data): ?>
                    <a href="manage_extensions.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="filter-section">
            <h3>Filtrar e Buscar Ramais</h3>
            <form method="GET" action="manage_extensions.php">
                <div class="form-group-inline">
                    <label for="search_term">Buscar (Número/Nome):</label>
                    <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="form-group-inline">
                    <label for="filter_type">Tipo:</label>
                    <select name="filter_type" id="filter_type">
                        <option value="">Todos</option>
                        <?php foreach ($extension_types as $type_opt): ?>
                        <option value="<?php echo $type_opt; ?>" <?php if ($filter_type == $type_opt) echo 'selected'; ?>><?php echo $type_opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-inline">
                    <label for="filter_status">Status:</label>
                    <select name="filter_status" id="filter_status">
                        <option value="">Todos</option>
                         <?php foreach ($extension_statuses as $status_opt): ?>
                        <option value="<?php echo $status_opt; ?>" <?php if ($filter_status == $status_opt) echo 'selected'; ?>><?php echo $status_opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-inline">
                    <label for="filter_sector">Setor:</label>
                    <select name="filter_sector" id="filter_sector">
                        <option value="">Todos</option>
                        <?php foreach ($sectors as $sector): ?>
                        <option value="<?php echo $sector['id']; ?>" <?php if ($filter_sector == $sector['id']) echo 'selected'; ?>><?php echo htmlspecialchars($sector['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Filtrar</button>
                <a href="manage_extensions.php" class="btn btn-secondary">Limpar Filtros</a>
            </form>
        </section>

        <section class="list-section">
            <h2>Ramais Cadastrados</h2>
            <?php if (empty($extensions_list) && !isset($page_error)): ?>
                <p>Nenhum ramal encontrado com os filtros atuais ou nenhum ramal cadastrado.</p>
            <?php elseif(!empty($extensions_list)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th>Setor</th>
                            <th>Status</th>
                            <th>Atribuído a</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extensions_list as $ext): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ext['number']); ?></td>
                                <td><?php echo htmlspecialchars($ext['type']); ?></td>
                                <td><?php echo htmlspecialchars($ext['sector_name']); ?></td>
                                <td><span class="status <?php echo $ext['status'] === 'Vago' ? 'status-vago' : 'status-atribuido'; ?>"><?php echo htmlspecialchars($ext['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($ext['person_name'] ?: '---'); ?></td>
                                <td class="actions">
                                    <a href="manage_extensions.php?edit_id=<?php echo $ext['id']; ?>" class="btn btn-edit">Editar</a>
                                    <form action="actions/extension_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este ramal? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="extension_id" value="<?php echo $ext['id']; ?>">
                                        <button type="submit" name="delete_extension" class="btn btn-delete">Excluir</button>
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
<script>
    function togglePersonAssignment(statusValue) {
        const personGroup = document.getElementById('person_assignment_group');
        const personSelect = document.getElementById('person_id');
        if (statusValue === 'Atribuído') {
            personGroup.style.display = '';
            // personSelect.required = true; // Make person selection required if status is 'Atribuído'
        } else {
            personGroup.style.display = 'none';
            personSelect.value = ''; // Clear person selection if status is 'Vago'
            // personSelect.required = false;
        }
    }
    // Initialize on page load based on current status (if editing)
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('status');
        if (statusSelect) { // Check if element exists (it might not if there's a $page_error)
            togglePersonAssignment(statusSelect.value);
        }
    });
</script>
</body>
</html>
