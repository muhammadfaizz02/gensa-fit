<?php
// instructor/dashboard.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('instructor');

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$role        = 'instructor';

// instructor_id
$stmt = $pdo->prepare("SELECT i.*, u.full_name, u.email, u.phone
                       FROM instructors i
                       JOIN users u ON u.id = i.user_id
                       WHERE i.user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$instructor = $stmt->fetch();

if (!$instructor) die('Data instruktur tidak ditemukan.');

$instructor_id = $instructor['id'];

// Statistik
$totalClasses = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE instructor_id = ?");
$totalClasses->execute([$instructor_id]);
$totalClasses = (int)$totalClasses->fetchColumn();

$totalSchedules = $pdo->prepare("
    SELECT COUNT(*) FROM schedules s
    JOIN classes c ON c.id = s.class_id
    WHERE c.instructor_id = ?
");
$totalSchedules->execute([$instructor_id]);
$totalSchedules = (int)$totalSchedules->fetchColumn();

$totalBookings = $pdo->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN schedules s ON s.id = b.schedule_id
    JOIN classes c ON c.id = s.class_id
    WHERE c.instructor_id = ? AND b.status = 'booked'
");
$totalBookings->execute([$instructor_id]);
$totalBookings = (int)$totalBookings->fetchColumn();

// 5 jadwal terdekat (upcoming di minggu ini)
$upcoming = $pdo->prepare("
    SELECT s.*, c.name AS class_name, c.capacity,
        (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id=s.id AND b.status='booked') AS booked
    FROM schedules s
    JOIN classes c ON c.id = s.class_id
    WHERE c.instructor_id = ?
    ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time
    LIMIT 5
");
$upcoming->execute([$instructor_id]);
$upcoming = $upcoming->fetchAll();

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];

require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-black mb-1">Halo, <?= htmlspecialchars($instructor['full_name']) ?> 👋</h1>
    <p class="text-gray-500">
        <?= htmlspecialchars($instructor['specialization'] ?: 'Instruktur') ?>
        · <?= (int)$instructor['experience_years'] ?> tahun pengalaman
    </p>
</div>

<!-- STATS -->
<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-3xl mb-3">🧘</div>
        <div class="text-3xl font-black"><?= $totalClasses ?></div>
        <div class="text-gray-500 text-sm">Kelas Saya</div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-3xl mb-3">📅</div>
        <div class="text-3xl font-black"><?= $totalSchedules ?></div>
        <div class="text-gray-500 text-sm">Jadwal Mingguan</div>
    </div>
    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-6 shadow-lg stat-card">
        <div class="text-3xl mb-3">👥</div>
        <div class="text-3xl font-black"><?= $totalBookings ?></div>
        <div class="text-orange-100 text-sm">Total Booking Aktif</div>
    </div>
</div>

<!-- UPCOMING -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold">Jadwal Terdekat</h2>
        <a href="my_schedule.php" class="text-orange-500 text-sm font-semibold hover:underline">Lihat Semua →</a>
    </div>

    <?php if (empty($upcoming)): ?>
        <div class="p-12 text-center text-gray-500 text-sm">Belum ada jadwal</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Hari</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ruangan</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Peserta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($upcoming as $s): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold"><?= $dayLabel[$s['day']] ?? $s['day'] ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($s['class_name']) ?></td>
                            <td class="px-6 py-4 text-orange-500 font-semibold">
                                <?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($s['room'] ?? '-') ?></td>
                            <td class="px-6 py-4 text-sm">
                                <strong><?= (int)$s['booked'] ?></strong>/<?= (int)$s['capacity'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>