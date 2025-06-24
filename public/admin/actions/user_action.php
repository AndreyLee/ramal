<?php
require_once __DIR__ . '/../../../src/includes/session_auth.php';
require_super_admin(); // Only Super-Admins can perform these actions

require_once __DIR__ . '/../../../src/includes/db.php';

$current_user_id = $_SESSION['user_id'];
$user_profiles = ['Admin', 'Super-Admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id_to_action = $_POST['user_id'] ?? null; // ID of the user being actioned upon
    $username = trim($_POST['username'] ?? '');
    $profile = $_POST['profile'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Add User
    if (isset($_POST['add_user'])) {
        if (empty($username) || empty($profile) || empty($password) || empty($confirm_password)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Nome de usuário, perfil, senha e confirmação de senha são obrigatórios.'];
        } elseif ($password !== $confirm_password) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'As senhas não coincidem.'];
        } elseif (!in_array($profile, $user_profiles)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Perfil de usuário inválido.'];
        } else {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, profile) VALUES (:username, :password_hash, :profile)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password_hash', $password_hash);
                $stmt->bindParam(':profile', $profile);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Usuário adicionado com sucesso!'];
            } catch (PDOException $e) {
                error_log("Error adding user: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao adicionar usuário. Verifique se o nome de usuário já existe.'];
                if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation
                     $_SESSION['feedback_message']['text'] = 'Erro: O nome de usuário "' . htmlspecialchars($username) . '" já existe.';
                }
            }
        }
    }
    // Update User
    elseif (isset($_POST['update_user']) && !empty($user_id_to_action)) {
        if (empty($username) || empty($profile)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Nome de usuário e perfil são obrigatórios.'];
        } elseif (!empty($password) && $password !== $confirm_password) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'As senhas não coincidem.'];
        } elseif (!in_array($profile, $user_profiles)) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Perfil de usuário inválido.'];
        } else {
            try {
                // Check if trying to demote the last Super-Admin
                if ($profile !== 'Super-Admin') {
                    $stmt_check_last_super = $pdo->prepare("SELECT COUNT(*) FROM users WHERE profile = 'Super-Admin' AND id != :user_id_to_action");
                    $stmt_check_last_super->bindParam(':user_id_to_action', $user_id_to_action, PDO::PARAM_INT);
                    $stmt_check_last_super->execute();
                    $other_super_admins_count = $stmt_check_last_super->fetchColumn();

                    $stmt_is_super = $pdo->prepare("SELECT profile FROM users WHERE id = :user_id_to_action");
                    $stmt_is_super->bindParam(':user_id_to_action', $user_id_to_action, PDO::PARAM_INT);
                    $stmt_is_super->execute();
                    $current_profile_of_user_being_edited = $stmt_is_super->fetchColumn();


                    if ($current_profile_of_user_being_edited === 'Super-Admin' && $other_super_admins_count == 0) {
                        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Não é possível alterar o perfil do último Super-Admin para algo diferente de Super-Admin.'];
                        header('Location: ../manage_users.php?edit_id=' . $user_id_to_action);
                        exit();
                    }
                }


                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, profile = :profile, password_hash = :password_hash WHERE id = :id");
                    $stmt->bindParam(':password_hash', $password_hash);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, profile = :profile WHERE id = :id");
                }
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':profile', $profile);
                $stmt->bindParam(':id', $user_id_to_action, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Usuário atualizado com sucesso!'];

                // If current user updated their own profile, update session
                if ($user_id_to_action == $current_user_id) {
                    $_SESSION['username'] = $username; // Update username in session if changed
                    $_SESSION['user_profile'] = $profile; // Update profile in session if changed
                }

            } catch (PDOException $e) {
                error_log("Error updating user: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao atualizar usuário. Verifique se o nome de usuário já existe para outro registro.'];
                 if ($e->errorInfo[1] == 1062) { // MySQL unique constraint violation
                     $_SESSION['feedback_message']['text'] = 'Erro: O nome de usuário "' . htmlspecialchars($username) . '" já existe para outro registro.';
                }
            }
        }
    }
    // Delete User
    elseif (isset($_POST['delete_user']) && !empty($user_id_to_action)) {
        if ($user_id_to_action == $current_user_id) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Você não pode excluir sua própria conta.'];
        } else {
            try {
                // Check if this is the last Super-Admin
                $stmt_profile = $pdo->prepare("SELECT profile FROM users WHERE id = :id");
                $stmt_profile->bindParam(':id', $user_id_to_action, PDO::PARAM_INT);
                $stmt_profile->execute();
                $user_to_delete_profile = $stmt_profile->fetchColumn();

                if ($user_to_delete_profile === 'Super-Admin') {
                    $stmt_count_super = $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'Super-Admin'");
                    $super_admin_count = $stmt_count_super->fetchColumn();
                    if ($super_admin_count <= 1) {
                        $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Não é possível excluir o último usuário Super-Admin.'];
                        header('Location: ../manage_users.php');
                        exit();
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id_to_action, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['feedback_message'] = ['type' => 'success', 'text' => 'Usuário excluído com sucesso!'];

            } catch (PDOException $e) {
                error_log("Error deleting user: " . $e->getMessage());
                $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Erro ao excluir usuário. Tente novamente.'];
            }
        }
    } else {
         if (empty($_SESSION['feedback_message'])) {
            $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Ação desconhecida ou ID de usuário ausente.'];
         }
    }
} else {
    $_SESSION['feedback_message'] = ['type' => 'error', 'text' => 'Requisição inválida.'];
}

// Redirect logic
$redirect_url = '../manage_users.php';
if (isset($_POST['update_user']) && !empty($user_id_to_action) && isset($_SESSION['feedback_message']['type']) && $_SESSION['feedback_message']['type'] === 'error') {
    // If update failed, redirect back to edit form
    $redirect_url .= '?edit_id=' . $user_id_to_action;
}
// For add error, no edit_id needed, default redirect is fine.
// For success or delete, default redirect is fine.

header('Location: ' . $redirect_url);
exit();
?>
