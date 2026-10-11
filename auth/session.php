<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Cek role admin
 */
function requireAdmin() {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        die('Akses ditolak. Hanya admin.');
    }
}

/**
 * Cek login untuk API (return JSON, bukan redirect)
 */
function requireLoginAPI() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => 'Unauthorized. Silakan login.']);
        exit;
    }
}

function currentUser() {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'nama'  => $_SESSION['user_nama'] ?? null,
        'role'  => $_SESSION['user_role'] ?? null,
    ];
}
?>