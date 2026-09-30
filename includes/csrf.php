<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * CSRF Protection Engine
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function renderCSRFField(): void {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCSRFToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function validateCSRFOrDie(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('Security Violation: Invalid or missing CSRF token.');
        }
    }
}