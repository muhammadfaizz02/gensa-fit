<?php
// member/my_schedule.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('member');

$page_title  = 'Booking Kelas';
$active_menu = 'my_schedule';
$role        = 'member';

$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $sid = (int)$_POST['schedule_id'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE member_id=? AND schedule_id=? AND status='booked'");
    $check->execute([$member_id, $sid]);
    if ($check->fetchColumn() > 0) {
        flash('error', 'Anda sudah booking kelas ini');
    } else {
        $pdo->prepare("INSERT INTO bookings (member_id,schedule_id,booking_date) VALUES (?,?,CURDATE())")
            ->execute([$member_id, $sid]);
        flash('success', 'Booking berhasil!');
    }
    redirect('my_schedule.php');
}
if (isset($_GET['cancel'])) {
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND member_id=?")
        ->execute([(int)$_GET['cancel'], $member_id]);
    flash('success', 'Booking dibatalkan');
    redirect('my_schedule.php');
}

$schedules = $pdo->query("
    SELECT s.*, c.name AS class_name, c.capacity, u.full_name AS instructor_name,
    (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id=s.id AND b.status='booked') AS booked
    FROM schedules s
    JOIN classes c ON c.id=s.class_id
    LEFT JOIN instructors i ON i.id=c.instructor_id
    LEFT JOIN users u ON u.id=i.user_id
    ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time
")->fetchAll();

$stmt = $pdo->prepare("
    SELECT b.*, s.day, s.start_time, s.end_time, s.room, c.name AS class_name, u.full_name AS instructor_name
    FROM bookings b
    JOIN schedules s ON s.id=b.schedule_id
    JOIN classes c ON c.id=s.class_id
    LEFT JOIN instructors i ON i.id=c.instructor_id
    LEFT JOIN users u ON u.id=i.user_id
    WHERE b.member_id=? AND b.status='booked'
    ORDER BY b.id DESC
");
$stmt->execute([$member_id]);
$myBookings = $stmt->fetchAll();

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];

$success = flash('success');
$error   = flash('error');

require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-black mb-2">Booking Kelas</h1>
<p class="text-gray-500 mb-8">Pilih kelas favoritmu dan mulai berlatih</p>

<?php if ($success): ?><div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div data-flash class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?= $error ?></div><?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <h2 class="font-bold text-lg mb-4">Jadwal Tersedia</h2>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Hari</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Waktu</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($schedules as $s):
                            $isFull = (int)$s['booked'] >= (int)$s['capacity'];
                        ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-sm"><?= $dayLabel[$s['day']] ?? $s['day'] ?></td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-sm"><?= htmlspecialchars($s['class_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($s['instructor_name'] ?? '-') ?> · <?= (int)$s['booked'] ?>/<?= (int)$s['capacity'] ?> peserta</div>
                            </td>
                            <td class="px-4 py-3 text-orange-500 font-semibold text-sm"><?= substr($s['start_time'],0,5) ?>-<?= substr($s['end_time'],0,5) ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($isFull): ?>
                                <span class="text-xs text-gray-400 font-semibold">Penuh</span>
                                <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="book">
                                    <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                    <button class="bg-orange-500 hover:bg-orange-600 text-white text-xs px-4 py-2 rounded-lg font-semibold transition">Booking</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <h2 class="font-bold text-lg mb-4">Booking Saya</h2>
        <div class="space-y-3">
            <?php if (empty($myBookings)): ?>
            <div class="bg-white rounded-2xl p-6 text-center text-gray-500 text-sm">Belum ada booking</div>
            <?php else: foreach ($myBookings as $b): ?>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
                <div class="font-semibold"><?= htmlspecialchars($b['class_name']) ?></div>
                <div class="text-xs text-gray-500 mt-1"><?= $dayLabel[$b['day']] ?? $b['day'] ?>, <?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?></div>
                <a href="?cancel=<?= $b['id'] ?>" data-confirm="Batalkan booking?" class="text-red-500 text-xs font-semibold mt-2 inline-block">Batalkan</a>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>