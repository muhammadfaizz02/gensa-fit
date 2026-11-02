<?php
// config/database.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'gensafit_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// ====== Helper global (semua dibungkus if(!function_exists)) ======

if (!function_exists('base_url')) {
    function base_url($path = '') {
        return "/gensa-fit/" . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash']['error'] = 'Silakan login terlebih dahulu';
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($role) {
        require_login();
        if ($_SESSION['role'] !== $role) {
            $_SESSION['flash']['error'] = 'Akses ditolak';
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'username'  => $_SESSION['username']  ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role']      ?? null,
        ];
    }
}

if (!function_exists('flash')) {
    function flash($key, $msg = null) {
        if ($msg === null) {
            $v = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $v;
        }
        $_SESSION['flash'][$key] = $msg;
    }
}