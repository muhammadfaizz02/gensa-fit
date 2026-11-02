<?php
// auth/login.php
require_once __DIR__ . '/../config/database.php';
if (is_logged_in()) {
    redirect(base_url($_SESSION['role'] . '/dashboard.php'));
}
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gensa Fit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-black flex items-center justify-center p-4">
    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, #f97316 0%, transparent 40%), radial-gradient(circle at 80% 70%, #dc2626 0%, transparent 40%);"></div>

    <div class="relative w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-5xl font-black text-white tracking-tight">
                Gensa<span class="text-orange-500">Fit</span>
            </h1>
            <p class="text-gray-400 mt-2">Gym Management System</p>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-2xl font-bold text-white mb-1">Selamat Datang</h2>
            <p class="text-gray-400 text-sm mb-6">Masuk ke akun Anda</p>

            <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="process_login.php" class="space-y-4">
                <div>
                    <label class="text-gray-300 text-sm font-medium">Username</label>
                    <input type="text" name="username" required
                        class="w-full mt-1 px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition"
                        placeholder="Masukkan username">
                </div>
                <div>
                    <label class="text-gray-300 text-sm font-medium">Password</label>
                    <input type="password" name="password" required
                        class="w-full mt-1 px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold py-3 rounded-lg transition transform hover:scale-[1.02] shadow-lg shadow-orange-500/30">
                    Masuk
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../landing/index.php" class="text-gray-400 hover:text-orange-400 text-sm transition">← Kembali ke Beranda</a>
            </div>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">© <?= date('Y') ?> Gensa Fit. All rights reserved.</p>
    </div>
</body>
</html>