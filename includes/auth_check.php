<?php
// includes/auth_check.php
// File ini HANYA memastikan config sudah di-load (untuk jaga-jaga).
// Semua fungsi (require_role, current_user, dll) sudah didefinisikan
// di config/database.php dengan proteksi if(!function_exists).
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}