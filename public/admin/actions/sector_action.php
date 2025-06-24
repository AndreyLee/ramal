<?php
require_once __DIR__ . '/../../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can perform these actions

require_once __DIR__ . '/../../../src/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Sector
    if (isset($_POST['add_sector'])) {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'O nome do setor é obrigatório.'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO sectors (name) VALUES (:name)");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Setor adicionado com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error adding sector: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao adicionar setor. Verifique se o nome já existe ou tente novamente.'];
                if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation
                     $_SESSION['feedback_message']['text'] = 'Erro: Já existe um setor com este nome.';
                }
            }
        }
    }
    // Update Sector
    elseif (isset($_POST['update_sector'])) {
        $sector_id = $_POST['sector_id'] ?? null;
        $name = trim($_POST['name'] ?? '');

        if (empty($name) || empty($sector_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Dados inválidos para atualização do setor.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE sectors SET name = :name WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':id', $sector_id, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Setor atualizado com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error updating sector: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao atualizar setor. Verifique se o nome já existe ou tente novamente.'];
                 if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation
                     $_SESSION['feedback_message']['text'] = 'Erro: Já existe um setor com este nome.';
                }
            }
        }
    }
    // Delete Sector
    elseif (isset($_POST['delete_sector'])) {
        $sector_id = $_POST['sector_id'] ?? null;
        if (empty($sector_id)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'ID do setor inválido para exclusão.'];
        } else {
            try {
                // Check if the sector has any extensions associated with it
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM extensions WHERE sector_id = :sector_id");
                $stmt_check->bindParam(':sector_id', $sector_id, PDO::PARAM_INT);
                $stmt_check->execute();
                $extension_count = $stmt_check->fetchColumn();

                if ($extension_count > 0) {
                    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Não é possível excluir este setor pois existem ' . $extension_count . ' ramal(is) associado(s) a ele. Remova ou realoque os ramais primeiro.'];
                } else {
                    // No extensions, proceed with deletion
                    $stmt = $pdo->prepare("DELETE FROM sectors WHERE id = :id");
                    $stmt->bindParam(':id', $sector_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Setor excluído com sucesso!'];
                }
            } catch (PDOException $e) {
                error_log("Error deleting sector: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao excluir setor. Tente novamente.'];
            }
        }
    } else {
        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ação desconhecida.'];
    }
} else {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Requisição inválida.'];
}

header('Location: ../manage_sectors.php');
exit();
?>
