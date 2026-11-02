<?php
// admin/instructors.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Instruktur';
$active_menu = 'instructors';
$role        = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO users (username,password,full_name,email,phone,role) VALUES (?,?,?,?,?,'instructor')");
        $stmt->execute([
            trim($_POST['username']),
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            trim($_POST['full_name']),
            trim($_POST['email']),
            trim($_POST['phone']),
        ]);
        $uid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO instructors (user_id,specialization,bio,experience_years) VALUES (?,?,?,?)")
            ->execute([$uid, $_POST['specialization'], $_POST['bio'], (int)$_POST['experience_years']]);
        $pdo->commit();
        flash('success', 'Instruktur berhasil ditambahkan');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect('instructors.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT user_id FROM instructors WHERE id=?");
    $stmt->execute([$id]);
    $uid = $stmt->fetchColumn();
    if ($uid) $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
    flash('success', 'Instruktur dihapus');
    redirect('instructors.php');
}

$instructors = $pdo->query("
    SELECT i.*, u.full_name, u.email, u.phone, u.username
    FROM instructors i
    JOIN users u ON u.id = i.user_id
    ORDER BY i.id DESC
")->fetchAll();

$success = flash('success');
$error   = flash('error');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-black">Manajemen Instruktur</h1>
        <p class="text-gray-500">Kelola instruktur Gensa Fit</p>
    </div>
    <button onclick="showModal('modal')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">
        + Tambah Instruktur
    </button>
</div>

<?php if ($success): ?>
<div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?= $error ?></div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <?php if (empty($instructors)): ?>
        <div class="md:col-span-3 bg-white rounded-2xl p-12 text-center text-gray-500">Belum ada instruktur</div>
    <?php else: foreach ($instructors as $i): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 stat-card">
        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center text-white text-2xl font-black mb-4">
            <?= strtoupper(substr($i['full_name'], 0, 1)) ?>
        </div>
        <h3 class="font-bold text-lg"><?= htmlspecialchars($i['full_name']) ?></h3>
        <p class="text-sm text-orange-500 font-semibold mb-2"><?= htmlspecialchars($i['specialization'] ?? 'General') ?></p>
        <p class="text-xs text-gray-500 mb-1"><?= htmlspecialchars($i['email']) ?></p>
        <p class="text-xs text-gray-500 mb-3"><?= htmlspecialchars($i['phone']) ?></p>
        <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($i['bio'] ?? '') ?></p>
        <div class="flex items-center justify-between">
            <span class="text-xs bg-slate-100 px-3 py-1 rounded-full"><?= $i['experience_years'] ?> tahun pengalaman</span>
            <a href="?delete=<?= $i['id'] ?>" data-confirm="Hapus instruktur ini?" class="text-red-500 hover:text-red-700 text-sm font-semibold">Hapus</a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-black">Tambah Instruktur</h2>
            <button onclick="hideModal('modal')" class="text-2xl text-gray-400 hover:text-gray-700">×</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Username *</label><input type="text" name="username" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Password *</label><input type="text" name="password" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Nama Lengkap *</label><input type="text" name="full_name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Spesialisasi</label><input type="text" name="specialization" placeholder="Yoga, Boxing, dll" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Email</label><input type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">No. HP</label><input type="text" name="phone" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Pengalaman (tahun)</label><input type="number" name="experience_years" value="0" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div class="md:col-span-2"><label class="text-sm font-semibold">Bio</label><textarea name="bio" rows="3" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></textarea></div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modal')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold hover:bg-slate-50">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>