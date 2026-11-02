<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$role        = 'admin';

$stats = [
    'members'     => $pdo->query("SELECT COUNT(*) FROM members WHERE status='active'")->fetchColumn(),
    'instructors' => $pdo->query("SELECT COUNT(*) FROM instructors")->fetchColumn(),
    'classes'     => $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'revenue'     => $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn(),
];

$recentMembers = $pdo->query("
    SELECT u.full_name, u.email, m.member_code, m.status, p.name AS package_name
    FROM members m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN packages p ON p.id = m.package_id
    ORDER BY m.id DESC LIMIT 5
")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-black mb-2">Dashboard</h1>
<p class="text-gray-500 mb-8">Selamat datang kembali, <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</p>

<!-- STATS -->
<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-3xl">👥</span>
            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded">Active</span>
        </div>
        <div class="text-3xl font-black"><?= number_format($stats['members']) ?></div>
        <div class="text-gray-500 text-sm">Total Members</div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-3xl mb-3">🏋️</div>
        <div class="text-3xl font-black"><?= number_format($stats['instructors']) ?></div>
        <div class="text-gray-500 text-sm">Instruktur</div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-3xl mb-3">🧘</div>
        <div class="text-3xl font-black"><?= number_format($stats['classes']) ?></div>
        <div class="text-gray-500 text-sm">Kelas Aktif</div>
    </div>
    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-6 shadow-lg stat-card">
        <div class="text-3xl mb-3">💰</div>
        <div class="text-3xl font-black">Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></div>
        <div class="text-orange-100 text-sm">Total Revenue</div>
    </div>
</div>

<!-- RECENT MEMBERS -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200">
    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
        <h2 class="text-lg font-bold">Member Terbaru</h2>
        <a href="members.php" class="text-orange-500 text-sm font-semibold hover:underline">Lihat Semua →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentMembers as $m): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div class="font-semibold"><?= htmlspecialchars($m['full_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($m['email']) ?></div>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($m['member_code']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($m['package_name'] ?? '-') ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?= $m['status']==='active' ? 'bg-green-100 text-green-700' : ($m['status']==='expired' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                            <?= ucfirst($m['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>