<?php
// admin/payments.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Pembayaran';
$active_menu = 'payments';
$role        = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO payments (member_id,package_id,amount,payment_date,method,status) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['member_id'], $_POST['package_id'] ?: null, $_POST['amount'],
        $_POST['payment_date'], $_POST['method'], $_POST['status']
    ]);
    flash('success','Pembayaran dicatat');
    redirect('payments.php');
}

$members  = $pdo->query("SELECT m.id, u.full_name FROM members m JOIN users u ON u.id=m.user_id")->fetchAll();
$packages = $pdo->query("SELECT * FROM packages")->fetchAll();

$payments = $pdo->query("
    SELECT pay.*, u.full_name, p.name AS package_name
    FROM payments pay
    JOIN members m ON m.id=pay.member_id
    JOIN users u ON u.id=m.user_id
    LEFT JOIN packages p ON p.id=pay.package_id
    ORDER BY pay.id DESC
")->fetchAll();

$success = flash('success');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div><h1 class="text-3xl font-black">Manajemen Pembayaran</h1><p class="text-gray-500">Catat dan kelola pembayaran member</p></div>
    <button onclick="showModal('modal')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">+ Catat Pembayaran</button>
</div>

<?php if ($success): ?><div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Paket</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Metode</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada pembayaran</td></tr>
                <?php else: foreach ($payments as $p): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($p['full_name']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($p['package_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 font-bold">Rp <?= number_format($p['amount'],0,',','.') ?></td>
                    <td class="px-6 py-4"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
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

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-xl">
        <div class="p-6 border-b flex justify-between"><h2 class="text-xl font-black">Catat Pembayaran</h2><button onclick="hideModal('modal')" class="text-2xl text-gray-400">×</button></div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div><label class="text-sm font-semibold">Member *</label>
                <select name="member_id" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                    <option value="">Pilih Member</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Paket</label>
                    <select name="package_id" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="">- Pilih -</option>
                        <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-sm font-semibold">Jumlah (Rp) *</label><input type="number" name="amount" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Tanggal *</label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Metode</label>
                    <select name="method" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="cash">Cash</option>
                        <option value="transfer">Transfer</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="col-span-2"><label class="text-sm font-semibold">Status</label>
                    <select name="status" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modal')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>