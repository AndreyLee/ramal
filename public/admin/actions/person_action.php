<?php
require_once __DIR__ . '/../../../src/includes/session_auth.php';
require_login(); // Ensure user is logged in

require_once __DIR__ . '/../../../src/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Person
    if (isset($_POST['add_person'])) {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'O nome da pessoa é obrigatório.'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO persons (name) VALUES (:name)");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Pessoa adicionada com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error adding person: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao adicionar pessoa. Verifique se o nome já existe ou tente novamente.'];
                 // Check for unique constraint violation (error code 1062 for MySQL)
                if ($e->errorInfo[1] == 1062) {
                     $_SESSION['feedback_message']['text'] = 'Erro: Já existe uma pessoa com este nome.';
                }
            }
        }
    }
    // Update Person
    elseif (isset($_POST['update_person'])) {
        $person_id = $_POST['person_id'] ?? null;
        $name = trim($_POST['name'] ?? '');

        if (empty($name) || empty($person_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Dados inválidos para atualização.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE persons SET name = :name WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $person_id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Pessoa atualizada com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error updating person: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao atualizar pessoa. Verifique se o nome já existe ou tente novamente.'];
                if ($e->errorInfo[1] == 1062) {
                     $_SESSION['feedback_message']['text'] = 'Erro: Já existe uma pessoa com este nome.';
                }
            }
        }
    }
    // Delete Person
    elseif (isset($_POST['delete_person'])) {
        $person_id = $_POST['person_id'] ?? null;
        if (empty($person_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'ID da pessoa inválido para exclusão.'];
        } else {
            try {
                // Check if the person is associated with any extensions
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM extensions WHERE person_id = :person_id");
                $stmt_check->bindParam(':person_id', $person_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $count = $stmt_check->fetchColumn();

                if ($count > 0) {
                    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Não é possível excluir esta pessoa pois ela está atribuída a um ou mais ramais. Desatribua os ramais primeiro.'];
                } else {
                    $stmt = $pdo->prepare("DELETE FROM persons WHERE id = :id");
                    $stmt->bindParam(':id', $person_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Pessoa excluída com sucesso!'];
                }
            } catch (PDOException $e) {
                error_log("Error deleting person: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao excluir pessoa. Tente novamente.'];
            }
        }
    } else {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ação desconhecida.'];
    }
} else {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Requisição inválida.'];
}

header('Location: ../manage_persons.php');
exit();
?>
