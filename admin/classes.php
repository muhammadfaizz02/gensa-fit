<?php
// admin/classes.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Kelas';
$active_menu = 'classes';
$role        = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO classes (name,description,instructor_id,capacity,duration_minutes) VALUES (?,?,?,?,?)");
    $stmt->execute([
        $_POST['name'], $_POST['description'],
        $_POST['instructor_id'] ?: null,
        (int)$_POST['capacity'], (int)$_POST['duration_minutes']
    ]);
    flash('success', 'Kelas berhasil ditambahkan');
    redirect('classes.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM classes WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Kelas dihapus');
    redirect('classes.php');
}

$instructors = $pdo->query("SELECT i.id, u.full_name FROM instructors i JOIN users u ON u.id=i.user_id")->fetchAll();
$classes = $pdo->query("
    SELECT c.*, u.full_name AS instructor_name,
    (SELECT COUNT(*) FROM schedules WHERE class_id=c.id) AS schedule_count
    FROM classes c
    LEFT JOIN instructors i ON i.id=c.instructor_id
    LEFT JOIN users u ON u.id=i.user_id
    ORDER BY c.id DESC
")->fetchAll();
$success = flash('success');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div><h1 class="text-3xl font-black">Manajemen Kelas</h1><p class="text-gray-500">Kelola jenis kelas yang tersedia</p></div>
    <button onclick="showModal('modal')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">+ Tambah Kelas</button>
</div>

<?php if ($success): ?><div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div><?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <?php foreach ($classes as $c): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 stat-card">
        <div class="flex justify-between items-start mb-3">
            <div class="text-4xl">🧘</div>
            <span class="bg-slate-100 text-xs px-3 py-1 rounded-full"><?= $c['schedule_count'] ?> jadwal</span>
        </div>
        <h3 class="font-bold text-lg"><?= htmlspecialchars($c['name']) ?></h3>
        <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($c['description']) ?></p>
        <div class="text-xs text-gray-500 space-y-1 mb-4">
            <div>👨‍🏫 Instruktur: <strong><?= htmlspecialchars($c['instructor_name'] ?? '-') ?></strong></div>
            <div>👥 Kapasitas: <strong><?= $c['capacity'] ?> orang</strong></div>
            <div>⏱ Durasi: <strong><?= $c['duration_minutes'] ?> menit</strong></div>
        </div>
        <div class="flex gap-2">
            <a href="schedules.php?class_id=<?= $c['id'] ?>" class="flex-1 text-center text-xs bg-orange-100 text-orange-700 py-2 rounded-lg font-semibold hover:bg-orange-200">Kelola Jadwal</a>
            <a href="?delete=<?= $c['id'] ?>" data-confirm="Hapus kelas ini?" class="text-red-500 px-3 text-xs">Hapus</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- MODAL -->
<div id="modal" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-xl">
        <div class="p-6 border-b flex justify-between"><h2 class="text-xl font-black">Tambah Kelas</h2><button onclick="hideModal('modal')" class="text-2xl text-gray-400">×</button></div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div><label class="text-sm font-semibold">Nama Kelas *</label><input type="text" name="name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            <div><label class="text-sm font-semibold">Deskripsi</label><textarea name="description" rows="2" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Instruktur</label>
                    <select name="instructor_id" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="">- Belum ditentukan -</option>
                        <?php foreach ($instructors as $i): ?>
                        <option value="<?= $i['id'] ?>"><?= htmlspecialchars($i['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-sm font-semibold">Kapasitas</label><input type="number" name="capacity" value="20" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Durasi (menit)</label><input type="number" name="duration_minutes" value="60" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modal')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>