<?php
// instructor/my_classes.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('instructor');

$page_title  = 'Kelas Saya';
$active_menu = 'my_classes';
$role        = 'instructor';

// Ambil instructor_id dari user yang login
$stmt = $pdo->prepare("SELECT * FROM instructors WHERE user_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$instructor = $stmt->fetch();

if (!$instructor) {
    die('Data instruktur tidak ditemukan. Hubungi admin.');
}

// Daftar kelas yang di-assign ke instruktur ini
$stmt = $pdo->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM schedules WHERE class_id = c.id) AS schedule_count,
           (SELECT COUNT(*) FROM bookings b
                JOIN schedules s ON s.id = b.schedule_id
                WHERE s.class_id = c.id AND b.status='booked') AS booking_count
    FROM classes c
    WHERE c.instructor_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$instructor['id']]);
$classes = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-black mb-2">Kelas Saya</h1>
<p class="text-gray-500 mb-8">Daftar kelas yang Anda ampu sebagai instruktur</p>

<?php if (empty($classes)): ?>
    <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-slate-200">
        <div class="text-5xl mb-3">🧘</div>
        <h3 class="font-bold text-lg mb-1">Belum ada kelas</h3>
        <p class="text-gray-500 text-sm">Admin belum meng-assign kelas kepada Anda.</p>
    </div>
<?php else: ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($classes as $c): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 stat-card">
                <div class="flex justify-between items-start mb-3">
                    <div class="text-4xl">🧘</div>
                    <span class="bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1 rounded-full">
                        <?= (int)$c['schedule_count'] ?> jadwal
                    </span>
                </div>
                <h3 class="font-bold text-lg"><?= htmlspecialchars($c['name']) ?></h3>
                <p class="text-sm text-gray-500 mb-4 min-h-[40px]">
                    <?= htmlspecialchars($c['description'] ?: 'Tanpa deskripsi') ?>
                </p>

                <div class="text-xs text-gray-500 space-y-1 mb-4">
                    <div>👥 Kapasitas: <strong><?= (int)$c['capacity'] ?> orang</strong></div>
                    <div>⏱ Durasi: <strong><?= (int)$c['duration_minutes'] ?> menit</strong></div>
                    <div>📌 Total booking aktif: <strong class="text-orange-600"><?= (int)$c['booking_count'] ?></strong></div>
                </div>

                <a href="my_schedule.php?class_id=<?= (int)$c['id'] ?>"
                   class="block text-center w-full bg-gradient-to-r from-orange-500 to-red-600 text-white py-2.5 rounded-lg font-semibold text-sm hover:scale-[1.02] transition">
                    Lihat Jadwal →
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>