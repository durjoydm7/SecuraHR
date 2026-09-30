<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Database Configuration & Connection Handler
 * DDM Industries Ltd.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'securahr_db');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // সরাসরি আসল এরর মেসেজ দেখার জন্য:
            die("<div style='padding: 20px; background: #fee2e2; color: #991b1b; font-family: sans-serif; border-radius: 8px; margin: 20px;'>
                    <h3>Database Connection Error!</h3>
                    <p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <hr style='border-color: #fca5a5;'>
                    <p><small>কানেকশন ঠিক করার জন্য phpMyAdmin-এ ডাটাবেসের নাম চেক করুন এবং DB_NAME পরিবর্তন করুন।</small></p>
                 </div>");
        }
    }

    return $pdo;
}