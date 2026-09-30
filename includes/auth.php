<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/audit.php';

function loginUser(string $email, string $password): array
{
    $pdo = getDBConnection();

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = :email LIMIT 1"
    );

    $stmt->execute([
        ':email' => trim($email)
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        return [
            'status' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    if ($user['status'] !== 'active') {

        if ($user['status'] === 'pending') {
            return [
                'status' => false,
                'message' => 'Your account is pending admin approval.'
            ];
        }

        if ($user['status'] === 'rejected') {
            return [
                'status' => false,
                'message' => 'Your account registration was rejected.'
            ];
        }

        if ($user['status'] === 'disabled') {
            return [
                'status' => false,
                'message' => 'Your account has been disabled.'
            ];
        }

        return [
            'status' => false,
            'message' => 'Your account is not active.'
        ];
    }

    if (!password_verify($password, $user['password_hash'])) {

        audit(
            'failed_login',
            'auth',
            $user['id'],
            "Failed login attempt for user: " . $user['email']
        );

        return [
            'status' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_photo'] = null;

    if ($user['role'] === 'admin') {

        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['full_name'] = 'Administrator';

    } else {

        $empStmt = $pdo->prepare(
            "SELECT full_name, photo FROM employees WHERE user_id = ? LIMIT 1"
        );

        $empStmt->execute([$user['id']]);
        $employee = $empStmt->fetch();

        if ($employee) {
            $_SESSION['full_name'] = $employee['full_name'];
            $_SESSION['profile_photo'] = $employee['photo'];
        } else {
            $_SESSION['full_name'] = $user['email'];
        }
    }

    $updateStmt = $pdo->prepare(
        "UPDATE users SET last_login = NOW() WHERE id = ?"
    );

    $updateStmt->execute([$user['id']]);

    audit(
        'login_success',
        'auth',
        $user['id'],
        "User logged in successfully"
    );

    return [
        'status' => true,
        'role' => $user['role'],
        'user' => $user
    ];
}


function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Please log in to access this page.'
        ];

        header('Location: /SecuraHR/login.php');
        exit;
    }
}


function requireRole(string|array $roles): void
{
    requireLogin();

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        http_response_code(403);
        die(
            '403 Forbidden: You do not have permission to access this resource.'
        );
    }
}


function logoutUser(): void
{
    if (isset($_SESSION['user_id'])) {

        audit(
            'logout',
            'auth',
            $_SESSION['user_id'],
            "User logged out"
        );
    }

    $_SESSION = array();

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}