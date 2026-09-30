<?php
/**
 * SECURAHR MANAGEMENT SYSTEM
 * Input Validation & Sanitization Engine
 */

function sanitizeInput(mixed $data): string {
    if (is_null($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function validateEmail(string $email): string|false {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

function validatePhone(string $phone): bool {
    // Validates standard Bangladesh / International phone formats
    return (bool)preg_match('/^(?:\+8801|01)[3-9]\d{8}$/', trim($phone));
}

function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateFileUpload(array $file, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'], int $maxSizeBytes = 5242880): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['status' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'error' => 'File upload failed with error code: ' . $file['error']];
    }

    if ($file['size'] > $maxSizeBytes) {
        return ['status' => false, 'error' => 'File size exceeds maximum allowed limit (5MB).'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['status' => false, 'error' => 'Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions)];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['status' => false, 'error' => 'Invalid MIME type. Uploaded file is not a valid image.'];
    }

    return ['status' => true, 'ext' => $ext, 'mime' => $mimeType];
}