<?php
require_once __DIR__ . '/../../../src/includes/session_auth.php';
require_login();

require_once __DIR__ . '/../../../src/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extension_id = $_POST['extension_id'] ?? null;

    // Assign Extension
    if (isset($_POST['assign_extension'])) {
        $person_id = $_POST['person_id'] ?? null;

        if (empty($extension_id) || empty($person_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ramal e Pessoa são obrigatórios para atribuição.'];
        } else {
            try {
                $pdo->beginTransaction();

                // Check if the person is already assigned to another extension (optional, business rule)
                // For this example, we assume one person can have multiple extensions.
                // If a person can only have one, you'd add a check here.

                // Check if the extension is actually Vago
                $stmt_check = $pdo->prepare("SELECT status FROM extensions WHERE id = :extension_id");
                $stmt_check->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $current_status = $stmt_check->fetchColumn();

                if ($current_status !== 'Vago') {
                     $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Este ramal não está vago e não pode ser atribuído diretamente.'];
                     $pdo->rollBack();
                } else {
                    $stmt = $pdo->prepare("UPDATE extensions SET person_id = :person_id, status = 'Atribuído' WHERE id = :extension_id AND status = 'Vago'");
                    $stmt->bindParam(':person_id', $person_id, PDO::PARAM_INT);
                    $stmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
                    $affected_rows = $stmt->execute() ? $stmt->rowCount() : 0;

                    if ($affected_rows > 0) {
                        $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Ramal atribuído com sucesso!'];
                        $pdo->commit();
                    } else {
                        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Não foi possível atribuir o ramal. Pode já estar atribuído ou não existir.'];
                        $pdo->rollBack();
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error assigning extension: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao atribuir ramal. Tente novamente.'];
            }
        }
    }
    // Unassign Extension
    elseif (isset($_POST['unassign_extension'])) {
        if (empty($extension_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'ID do ramal inválido para desatribuição.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE extensions SET person_id = NULL, status = 'Vago' WHERE id = :extension_id");
                $stmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Ramal desatribuído com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error unassigning extension: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao desatribuir ramal. Tente novamente.'];
            }
        }
    }
    // Update Assignment (Change person for an extension)
    elseif (isset($_POST['update_assignment'])) {
        $new_person_id = $_POST['new_person_id'] ?? null;
        // $current_person_id = $_POST['current_person_id'] ?? null; // For logging or more complex logic

        if (empty($extension_id) || empty($new_person_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ramal e Nova Pessoa são obrigatórios para alterar a atribuição.'];
        } else {
             try {
                // Ensure the extension is currently assigned, and person_id is not null
                $stmt_check = $pdo->prepare("SELECT person_id FROM extensions WHERE id = :extension_id AND status = 'Atribuído'");
                $stmt_check->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $current_assigned_person = $stmt_check->fetchColumn();

                if ($current_assigned_person === false) { // Not assigned or doesn't exist
                     $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'O ramal não está atualmente atribuído ou não foi encontrado.'];
                } else if ($current_assigned_person == $new_person_id) {
                     $_SESSION['feedback_message'] = ['type' => 'info', 'text' => 'O ramal já está atribuído a esta pessoa. Nenhuma alteração feita.'];
                }
                else {
                    $stmt = $pdo->prepare("UPDATE extensions SET person_id = :new_person_id, status = 'Atribuído' WHERE id = :extension_id");
                    $stmt->bindParam(':new_person_id', $new_person_id, PDO::PARAM_INT);
                    $stmt->bindParam(':extension_id', $extension_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Atribuição do ramal alterada com sucesso!'];
                }
            } catch (PDOException $e) {
                error_log("Error updating assignment: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao alterar atribuição do ramal. Tente novamente.'];
            }
        }
    } else {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ação desconhecida.'];
    }
} else {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Requisição inválida.'];
}

header('Location: ../manage_assignments.php');
exit();
?>
