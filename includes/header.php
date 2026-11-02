<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$page_title  = $page_title  ?? 'Dashboard';
$active_menu = $active_menu ?? '';
$role        = $role        ?? ($_SESSION['role'] ?? 'guest');

if (!function_exists('base_url')) {
    function base_url($path = '') { return '/gensa-fit/' . ltrim($path, '/'); }
}

if (!isset($sidebar_menu)) {
    $sidebar_menu = [];
    if ($role === 'admin') {
        $sidebar_menu = [
            ['key'=>'dashboard',   'label'=>'Dashboard',  'icon'=>'📊', 'url'=>'dashboard.php'],
            ['key'=>'instructors', 'label'=>'Instruktur', 'icon'=>'🏋️', 'url'=>'instructors.php'],
            ['key'=>'members',     'label'=>'Members',    'icon'=>'👥', 'url'=>'members.php'],
            ['key'=>'packages',    'label'=>'Paket',      'icon'=>'📦', 'url'=>'packages.php'],
            ['key'=>'classes',     'label'=>'Kelas',      'icon'=>'🧘', 'url'=>'classes.php'],
            ['key'=>'schedules',   'label'=>'Jadwal',     'icon'=>'📅', 'url'=>'schedules.php'],
            ['key'=>'payments',    'label'=>'Pembayaran', 'icon'=>'💰', 'url'=>'payments.php'],
            ['key'=>'revenue',     'label'=>'Pendapatan',   'icon'=>'📈', 'url'=>'revenue.php']
        ];
    } elseif ($role === 'instructor') {
        $sidebar_menu = [
            ['key'=>'dashboard',   'label'=>'Dashboard',   'icon'=>'📊', 'url'=>'dashboard.php'],
            ['key'=>'my_classes',  'label'=>'Kelas Saya',  'icon'=>'🧘', 'url'=>'my_classes.php'],
            ['key'=>'my_schedule', 'label'=>'Jadwal Saya', 'icon'=>'📅', 'url'=>'my_schedule.php'],
        ];
    } elseif ($role === 'member') {
        $sidebar_menu = [
            ['key'=>'dashboard',   'label'=>'Dashboard',    'icon'=>'📊', 'url'=>'dashboard.php'],
            ['key'=>'my_package',  'label'=>'Paket Saya',   'icon'=>'📦', 'url'=>'my_package.php'],
            ['key'=>'my_schedule', 'label'=>'Booking Kelas','icon'=>'📅', 'url'=>'my_schedule.php'],
        ];
    }
}

$panel_label = ['admin'=>'Admin Panel','instructor'=>'Instructor Panel','member'=>'Member Area'][$role] ?? 'Panel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Gensa Fit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="bg-slate-100">
<div class="flex min-h-screen">

    <aside id="sidebar" class="w-64 bg-slate-900 text-white flex flex-col fixed lg:static inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="p-6 border-b border-slate-800">
            <div class="text-2xl font-black">Gensa<span class="text-orange-500">Fit</span></div>
            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($panel_label) ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <?php foreach ($sidebar_menu as $item): $active = $active_menu === $item['key']; ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $active ? 'bg-orange-500 font-semibold' : 'hover:bg-slate-800' ?>">
                <span><?= $item['icon'] ?></span>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="p-4 border-t border-slate-800">
            <div class="text-sm text-gray-400 mb-1">Hi,</div>
            <div class="text-sm font-semibold truncate mb-3"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
            <a href="<?= base_url('auth/logout.php') ?>"
               class="block text-center bg-red-600 hover:bg-red-700 py-2 rounded-lg font-semibold transition">
                Logout
            </a>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <main class="flex-1 min-w-0">
        <div class="lg:hidden bg-slate-900 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-20">
            <button onclick="toggleSidebar()" class="text-2xl">☰</button>
            <div class="text-lg font-black">Gensa<span class="text-orange-500">Fit</span></div>
            <a href="<?= base_url('auth/logout.php') ?>" class="text-sm bg-red-600 px-3 py-1.5 rounded-lg">Logout</a>
        </div>
        <div class="p-6 lg:p-8">