<?php
// admin/packages.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Paket';
$active_menu = 'packages';
$role        = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO packages (name,description,price,duration_days,features) VALUES (?,?,?,?,?)");
    $stmt->execute([$_POST['name'],$_POST['description'],$_POST['price'],$_POST['duration_days'],$_POST['features']]);
    flash('success','Paket ditambahkan'); redirect('packages.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM packages WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success','Paket dihapus'); redirect('packages.php');
}

$packages = $pdo->query("SELECT * FROM packages ORDER BY price ASC")->fetchAll();
$success = flash('success');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div><h1 class="text-3xl font-black">Paket Membership</h1><p class="text-gray-500">Kelola paket yang ditawarkan</p></div>
    <button onclick="showModal('modal')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">+ Tambah Paket</button>
</div>

<?php if ($success): ?><div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div><?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <?php foreach ($packages as $p): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 stat-card">
        <h3 class="text-2xl font-black"><?= htmlspecialchars($p['name']) ?></h3>
        <p class="text-sm text-gray-500 mt-1 mb-4"><?= htmlspecialchars($p['description']) ?></p>
        <div class="text-3xl font-black text-orange-500 mb-4">Rp <?= number_format($p['price'],0,',','.') ?><span class="text-sm text-gray-400 font-normal">/<?= $p['duration_days'] ?>hr</span></div>
        <ul class="text-sm text-gray-600 space-y-1 mb-4">
            <?php foreach (explode(',', $p['features']) as $f): ?>
            <li>✓ <?= htmlspecialchars(trim($f)) ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="?delete=<?= $p['id'] ?>" data-confirm="Hapus paket ini?" class="text-red-500 text-sm font-semibold">Hapus</a>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-xl">
        <div class="p-6 border-b flex justify-between"><h2 class="text-xl font-black">Tambah Paket</h2><button onclick="hideModal('modal')" class="text-2xl text-gray-400">×</button></div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div><label class="text-sm font-semibold">Nama Paket *</label><input type="text" name="name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            <div><label class="text-sm font-semibold">Deskripsi</label><input type="text" name="description" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Harga (Rp) *</label><input type="number" name="price" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Durasi (hari) *</label><input type="number" name="duration_days" value="30" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            </div>
            <div><label class="text-sm font-semibold">Fitur (pisahkan dengan koma) *</label><textarea name="features" rows="2" placeholder="Akses gym, Loker, Air mineral" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></textarea></div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modal')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>