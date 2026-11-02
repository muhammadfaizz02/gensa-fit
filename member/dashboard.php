<?php
// member/dashboard.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('member');

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
$role        = 'member';

$stmt = $pdo->prepare("
    SELECT m.*, p.name AS package_name, p.price, p.features
    FROM members m
    LEFT JOIN packages p ON p.id=m.package_id
    WHERE m.user_id=?
");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

$daysLeft = 0;
if ($member && $member['expire_date']) {
    $daysLeft = max(0, floor((strtotime($member['expire_date']) - time()) / 86400));
}

$bookings = [];
if ($member) {
    $stmt = $pdo->prepare("
        SELECT b.*, s.day, s.start_time, s.end_time, s.room, c.name AS class_name, u.full_name AS instructor_name
        FROM bookings b
        JOIN schedules s ON s.id=b.schedule_id
        JOIN classes c ON c.id=s.class_id
        LEFT JOIN instructors i ON i.id=c.instructor_id
        LEFT JOIN users u ON u.id=i.user_id
        WHERE b.member_id=? AND b.status='booked'
        ORDER BY b.id DESC LIMIT 5
    ");
    $stmt->execute([$member['id']]);
    $bookings = $stmt->fetchAll();
}

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];

require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-black mb-2">Halo, <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</h1>
<p class="text-gray-500 mb-8">Selamat datang di member area Gensa Fit</p>

<?php if (!$member): ?>
<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 p-6 rounded-2xl">
    <h3 class="font-bold mb-2">Anda belum terdaftar sebagai member</h3>
    <p>Silakan hubungi admin untuk mendaftarkan paket membership Anda.</p>
</div>
<?php else: ?>

<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-6 shadow-lg stat-card">
        <div class="text-sm opacity-90 mb-1">Kode Member</div>
        <div class="text-2xl font-black mb-4"><?= htmlspecialchars($member['member_code']) ?></div>
        <div class="text-xs opacity-80">Bergabung: <?= date('d M Y', strtotime($member['join_date'])) ?></div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-sm text-gray-500 mb-1">Paket Aktif</div>
        <div class="text-2xl font-black mb-2"><?= htmlspecialchars($member['package_name'] ?? '-') ?></div>
        <div class="text-xs text-gray-500">Expired: <?= $member['expire_date'] ? date('d M Y', strtotime($member['expire_date'])) : '-' ?></div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-sm text-gray-500 mb-1">Sisa Hari</div>
        <div class="text-4xl font-black <?= $daysLeft > 7 ? 'text-green-600' : 'text-red-600' ?>"><?= $daysLeft ?></div>
        <div class="text-xs text-gray-500">hari lagi</div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Fasilitas Paket Anda</h3>
        <ul class="space-y-2">
            <?php foreach (explode(',', $member['features'] ?? '') as $f): if(trim($f)): ?>
            <li class="flex items-center gap-2 text-sm"><span class="text-green-500">✓</span> <?= htmlspecialchars(trim($f)) ?></li>
            <?php endif; endforeach; ?>
        </ul>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h3 class="font-bold text-lg mb-4">Booking Kelas Aktif</h3>
        <?php if (empty($bookings)): ?>
        <p class="text-gray-500 text-sm">Belum ada booking aktif. <a href="my_schedule.php" class="text-orange-500 font-semibold">Booking sekarang →</a></p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($bookings as $b): ?>
            <div class="border-l-4 border-orange-500 bg-orange-50 p-3 rounded">
                <div class="font-semibold text-sm"><?= htmlspecialchars($b['class_name']) ?></div>
                <div class="text-xs text-gray-500"><?= $dayLabel[$b['day']] ?? $b['day'] ?>, <?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?> • <?= htmlspecialchars($b['room'] ?? '-') ?></div>
                <div class="text-xs text-orange-600 mt-1">Instruktur: <?= htmlspecialchars($b['instructor_name'] ?? '-') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>