<?php
// member/my_package.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('member');

$page_title  = 'Paket Saya';
$active_menu = 'my_package';
$role        = 'member';

$stmt = $pdo->prepare("
    SELECT m.*, p.name AS package_name, p.price, p.features, p.duration_days
    FROM members m
    LEFT JOIN packages p ON p.id=m.package_id
    WHERE m.user_id=?
");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

$payments = [];
if ($member) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE member_id=? ORDER BY id DESC");
    $stmt->execute([$member['id']]);
    $payments = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-black mb-2">Paket Saya</h1>
<p class="text-gray-500 mb-8">Detail paket dan riwayat pembayaran</p>

<?php if (!$member || !$member['package_name']): ?>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 p-6 rounded-2xl">Belum ada paket aktif. Hubungi admin.</div>
<?php else: ?>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-8 shadow-lg">
        <div class="text-sm opacity-90 mb-2">Paket Aktif</div>
        <div class="text-4xl font-black mb-4"><?= htmlspecialchars($member['package_name']) ?></div>
        <div class="text-2xl font-bold mb-6">Rp <?= number_format($member['price'],0,',','.') ?></div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><div class="opacity-80">Kode Member</div><div class="font-bold"><?= $member['member_code'] ?></div></div>
            <div><div class="opacity-80">Status</div><div class="font-bold"><?= ucfirst($member['status']) ?></div></div>
            <div><div class="opacity-80">Join</div><div class="font-bold"><?= date('d M Y', strtotime($member['join_date'])) ?></div></div>
            <div><div class="opacity-80">Expired</div><div class="font-bold"><?= date('d M Y', strtotime($member['expire_date'])) ?></div></div>
        </div>
    </div>
    <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Fasilitas Anda</h3>
        <ul class="space-y-3">
            <?php foreach (explode(',', $member['features'] ?? '') as $f): if(trim($f)): ?>
            <li class="flex items-center gap-3">
                <span class="bg-green-100 text-green-600 w-6 h-6 rounded-full flex items-center justify-center text-xs">✓</span>
                <?= htmlspecialchars(trim($f)) ?>
            </li>
            <?php endif; endforeach; ?>
        </ul>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b"><h3 class="font-bold text-lg">Riwayat Pembayaran</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Metode</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payments)): ?>
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat pembayaran</td></tr>
                <?php else: foreach ($payments as $p): ?>
                <tr>
                    <td class="px-6 py-4"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                    <td class="px-6 py-4 font-semibold">Rp <?= number_format($p['amount'],0,',','.') ?></td>
                    <td class="px-6 py-4"><span class="bg-slate-100 px-3 py-1 rounded-full text-xs"><?= ucfirst($p['method']) ?></span></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?= $p['status']==='paid'?'bg-green-100 text-green-700':($p['status']==='pending'?'bg-yellow-100 text-yellow-700':'bg-red-100 text-red-700') ?>">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>