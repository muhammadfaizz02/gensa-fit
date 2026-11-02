<?php
// admin/schedules.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Jadwal';
$active_menu = 'schedules';
$role        = 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $stmt = $pdo->prepare("INSERT INTO schedules (class_id,day,start_time,end_time,room) VALUES (?,?,?,?,?)");
    $stmt->execute([$_POST['class_id'],$_POST['day'],$_POST['start_time'],$_POST['end_time'],$_POST['room']]);
    flash('success', 'Jadwal ditambahkan');
    redirect('schedules.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM schedules WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Jadwal dihapus');
    redirect('schedules.php');
}

$classes = $pdo->query("SELECT id, name FROM classes")->fetchAll();
$filter_class = (int)($_GET['class_id'] ?? 0);

$sql = "SELECT s.*, c.name AS class_name, u.full_name AS instructor_name
    FROM schedules s
    JOIN classes c ON c.id=s.class_id
    LEFT JOIN instructors i ON i.id=c.instructor_id
    LEFT JOIN users u ON u.id=i.user_id";
$params = [];
if ($filter_class) { $sql .= " WHERE s.class_id=?"; $params[] = $filter_class; }
$sql .= " ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];

$success = flash('success');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black">Manajemen Jadwal</h1>
        <p class="text-gray-500">Kelola jadwal kelas mingguan</p>
    </div>
    <div class="flex gap-3">
        <form method="GET">
            <select name="class_id" onchange="this.form.submit()" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                <option value="">Semua Kelas</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filter_class===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <button onclick="showModal('modal')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">+ Tambah Jadwal</button>
    </div>
</div>

<?php if ($success): ?><div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div><?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Hari</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kelas</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Instruktur</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Ruangan</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($schedules)): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada jadwal</td></tr>
                <?php else: foreach ($schedules as $s): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-semibold"><?= $dayLabel[$s['day']] ?? $s['day'] ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($s['class_name']) ?></td>
                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($s['instructor_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-orange-500 font-semibold"><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?></td>
                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($s['room'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-right">
                        <a href="?delete=<?= $s['id'] ?>" data-confirm="Hapus jadwal ini?" class="text-red-500 hover:text-red-700 text-sm font-semibold">Hapus</a>
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
        <div class="p-6 border-b flex justify-between"><h2 class="text-xl font-black">Tambah Jadwal</h2><button onclick="hideModal('modal')" class="text-2xl text-gray-400">×</button></div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div><label class="text-sm font-semibold">Kelas *</label>
                <select name="class_id" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                    <option value="">Pilih Kelas</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Hari *</label>
                    <select name="day" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <?php foreach ($dayLabel as $en=>$id): ?>
                        <option value="<?= $en ?>"><?= $id ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-sm font-semibold">Ruangan</label><input type="text" name="room" placeholder="Studio A" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Jam Mulai *</label><input type="time" name="start_time" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Jam Selesai *</label><input type="time" name="end_time" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modal')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>