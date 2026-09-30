<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Central Audit Logger Engine
 */

require_once __DIR__ . '/../config/database.php';

function audit(string $action, string $module, ?int $targetId = null, string $description = ''): bool {
    $pdo = getDBConnection();
    
    $userId = $_SESSION['user_id'] ?? null;
    $adminId = $_SESSION['admin_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }

    $sql = "INSERT INTO audit_logs (user_id, admin_id, action, module, target_id, description, ip_address) 
            VALUES (:user_id, :admin_id, :action, :module, :target_id, :description, :ip_address)";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':admin_id' => $adminId,
            ':action' => $action,
            ':module' => $module,
            ':target_id' => $targetId,
            ':description' => $description,
            ':ip_address' => $ipAddress
        ]);
    } catch (PDOException $e) {
        error_log("Audit Logging Failed: " . $e->getMessage());
        return false;
    }
}