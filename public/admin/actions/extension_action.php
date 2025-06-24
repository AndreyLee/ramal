<?php
require_once __DIR__ . '/../../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can perform these actions

require_once __DIR__ . '/../../../src/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extension_id = $_POST['extension_id'] ?? null;
    $number = trim($_POST['number'] ?? '');
    $type = $_POST['type'] ?? ''; // ENUM: 'Interno', 'Externo'
    $sector_id = $_POST['sector_id'] ?? null;
    $status = $_POST['status'] ?? ''; // ENUM: 'Vago', 'Atribuído'
    $person_id = ($status === 'Atribuído' && !empty($_POST['person_id'])) ? $_POST['person_id'] : null;

    $valid_types = ['Interno', 'Externo'];
    $valid_statuses = ['Vago', 'Atribuído'];

    // Basic Validation
    if (empty($number) || empty($type) || empty($sector_id) || empty($status)) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Número, tipo, setor e status são obrigatórios.'];
    } elseif (!in_array($type, $valid_types)) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Tipo de ramal inválido.'];
    } elseif (!in_array($status, $valid_statuses)) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Status de ramal inválido.'];
    } elseif ($status === 'Atribuído' && empty($person_id)) {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Se o status é "Atribuído", uma pessoa deve ser selecionada.'];
    } elseif ($status === 'Vago' && !empty($person_id)) {
        // Ensure person_id is NULL if status is 'Vago'
        $person_id = null;
    }
    else {
        // Add Extension
        if (isset($_POST['add_extension'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO extensions (number, type, sector_id, status, person_id) VALUES (:number, :type, :sector_id, :status, :person_id)");
                $stmt->bindParam(':number', $number);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':person_id', $person_id, $person_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Ramal adicionado com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error adding extension: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao adicionar ramal. Verifique se o número do ramal já existe.'];
                if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation for 'number'
                     $_SESSION['feedback_message']['text'] = 'Erro: O número do ramal "' . htmlspecialchars($number) . '" já existe.';
                }
            }
        }
        // Update Extension
        elseif (isset($_POST['update_extension']) && !empty($extension_id)) {
            try {
                $stmt = $pdo->prepare("UPDATE extensions SET number = :number, type = :type, sector_id = :sector_id, status = :status, person_id = :person_id WHERE id = :id");
                $stmt->bindParam(':number', $number);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':person_id', $person_id, $person_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(':id', $extension_id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Ramal atualizado com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error updating extension: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao atualizar ramal. Verifique se o número do ramal já existe para outro registro.'];
                 if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation for 'number'
                     $_SESSION['feedback_message']['text'] = 'Erro: O número do ramal "' . htmlspecialchars($number) . '" já existe para outro registro.';
                }
            }
        }
        // Delete Extension
        elseif (isset($_POST['delete_extension']) && !empty($extension_id)) {
            // No specific validation needed before deleting an extension, as FK constraints handle person linkage (person_id becomes NULL or is already NULL)
            // If there were other dependencies, they would be checked here.
            try {
                $stmt = $pdo->prepare("DELETE FROM extensions WHERE id = :id");
                $stmt->bindParam(':id', $extension_id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Ramal excluído com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error deleting extension: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao excluir ramal. Tente novamente.'];
            }
        } else {
            if (empty($_SESSION['feedback_message'])) { // Only set if not already set by basic validation
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ação desconhecida ou ID do ramal ausente para edição/exclusão.'];
            }
        }
    }
} else {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Requisição inválida.'];
}

header('Location: ../manage_extensions.php' . (isset($_POST['delete_extension']) ? '' : '?edit_id=' . $extension_id)); // Redirect back, keep edit_id if it was an update/add error for convenience
// A better redirect for delete would be just to the main page.
if(isset($_POST['delete_extension']) || (isset($_SESSION['feedback_message']['type']) && $_SESSION['feedback_message']['type'] == 'success' && (isset($_POST['add_extension']) || isset($_POST['update_extension'])) ) ){
    header('Location: ../manage_extensions.php');
} else {
    // If there was an error on add/update, redirect back to the form, potentially with ?edit_id= for update
    $redirect_url = '../manage_extensions.php';
    if (isset($_POST['update_extension']) && !empty($extension_id)) {
        $redirect_url .= '?edit_id=' . $extension_id;
    }
    // For add error, no edit_id needed.
    header('Location: ' . $redirect_url);
}
exit();
?>
