<?php
// instructor/my_schedule.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('instructor');

$page_title  = 'Jadwal Saya';
$active_menu = 'my_schedule';
$role        = 'instructor';

// Ambil instructor_id
$stmt = $pdo->prepare("SELECT id FROM instructors WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$instructor_id = $stmt->fetchColumn();

if (!$instructor_id) die('Data instruktur tidak ditemukan.');

// Filter opsional per kelas
$filter_class = (int)($_GET['class_id'] ?? 0);

// Daftar kelas milik instruktur (untuk dropdown filter)
$stmt = $pdo->prepare("SELECT id, name FROM classes WHERE instructor_id = ? ORDER BY name");
$stmt->execute([$instructor_id]);
$myClasses = $stmt->fetchAll();

// Jadwal
$sql = "
    SELECT s.*, c.name AS class_name, c.capacity,
        (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.status='booked') AS booked
    FROM schedules s
    JOIN classes c ON c.id = s.class_id
    WHERE c.instructor_id = ?
";
$params = [$instructor_id];
if ($filter_class) {
    $sql .= " AND c.id = ?";
    $params[] = $filter_class;
}
$sql .= " ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];

require __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black">Jadwal Saya</h1>
        <p class="text-gray-500">Jadwal kelas yang Anda ampu minggu ini</p>
    </div>

    <form method="GET" class="flex gap-2">
        <select name="class_id" onchange="this.form.submit()"
                class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
            <option value="">Semua Kelas</option>
            <?php foreach ($myClasses as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $filter_class===(int)$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Hari</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kelas</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Ruangan</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Peserta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            Belum ada jadwal untuk kelas Anda.
                        </td>
                    </tr>
                <?php else: foreach ($schedules as $s):
                    $booked = (int)$s['booked'];
                    $cap = (int)$s['capacity'];
                    $pct = $cap > 0 ? min(100, round($booked / $cap * 100)) : 0;
                ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-semibold">
                            <?= htmlspecialchars($dayLabel[$s['day']] ?? $s['day']) ?>
                        </td>
                        <td class="px-6 py-4"><?= htmlspecialchars($s['class_name']) ?></td>
                        <td class="px-6 py-4 text-orange-500 font-semibold">
                            <?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($s['room'] ?? '-') ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold"><?= $booked ?>/<?= $cap ?></span>
                                <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-orange-500 to-red-600"
                                         style="width: <?= $pct ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>