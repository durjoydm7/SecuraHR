<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Global Helper & Utility Functions
 */

require_once __DIR__ . '/../config/database.php';

function getCompanySetting(string $key, mixed $default = null): mixed {
    static $settings = null;
    if ($settings === null) {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM company_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $settings[$key] ?? $default;
}

function formatCurrency(float|int $amount): string {
    return 'BDT ' . number_format($amount, 2);
}

function formatDate(?string $date): string {
    if (!$date) return 'N/A';
    return date('d M Y', strtotime($date));
}

function formatDateTime(?string $datetime): string {
    if (!$datetime) return 'N/A';
    return date('d M Y, h:i A', strtotime($datetime));
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}