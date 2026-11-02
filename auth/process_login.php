<?php
// auth/process_login.php
require_once __DIR__ . '/../config/database.php';

// Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('auth/login.php'));
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    flash('error', 'Username dan password wajib diisi');
    redirect(base_url('auth/login.php'));
}

// Cari user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Username atau password salah');
    redirect(base_url('auth/login.php'));
}

if ((int)$user['is_active'] !== 1) {
    flash('error', 'Akun Anda tidak aktif. Hubungi admin.');
    redirect(base_url('auth/login.php'));
}

if (!password_verify($password, $user['password'])) {
    flash('error', 'Username atau password salah');
    redirect(base_url('auth/login.php'));
}

// (Opsional) cek member expired saat login
if ($user['role'] === 'member') {
    $stmt = $pdo->prepare("SELECT status FROM members WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $mstatus = $stmt->fetchColumn();
    // tetap boleh login walau expired, hanya info
}

// Set session
session_regenerate_id(true);
$_SESSION['user_id']   = (int)$user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role']      = $user['role'];
$_SESSION['login_at']  = time();

// Redirect sesuai role
switch ($user['role']) {
    case 'admin':
        redirect(base_url('admin/dashboard.php'));
    case 'instructor':
        redirect(base_url('instructor/dashboard.php'));
    case 'member':
        redirect(base_url('member/dashboard.php'));
    default:
        session_destroy();
        flash('error', 'Role tidak dikenali');
        redirect(base_url('auth/login.php'));
}