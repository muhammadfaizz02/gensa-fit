<?php
// admin/members.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$page_title  = 'Members';
$active_menu = 'members';
$role        = 'admin';

// ✅ AUTO-EXPIRE: update status member yang sudah lewat tanggal
$pdo->exec("UPDATE members SET status='expired' WHERE expire_date < CURDATE() AND status='active'");

// ============================================================
// HANDLE TAMBAH MEMBER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username   = trim($_POST['username']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $package_id = (int)$_POST['package_id'];
    $join_date  = $_POST['join_date'];

    $pkg = $pdo->prepare("SELECT duration_days FROM packages WHERE id=?");
    $pkg->execute([$package_id]);
    $days = $pkg->fetchColumn() ?: 30;
    $expire = date('Y-m-d', strtotime($join_date . " +$days days"));
    $member_code = 'GF' . date('ymd') . rand(100, 999);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO users (username,password,full_name,email,phone,role) VALUES (?,?,?,?,?,'member')");
        $stmt->execute([$username,$password,$full_name,$email,$phone]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO members (user_id,member_code,package_id,join_date,expire_date,status) VALUES (?,?,?,?,?,'active')");
        $stmt->execute([$user_id,$member_code,$package_id,$join_date,$expire]);

        $pdo->commit();
        flash('success', "Member $full_name berhasil ditambahkan dengan kode $member_code");
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect('members.php' . return_query());
}

// ============================================================
// HANDLE EDIT MEMBER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $member_id  = (int)$_POST['member_id'];
    $user_id    = (int)$_POST['user_id'];
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $package_id = (int)$_POST['package_id'];
    $join_date  = $_POST['join_date'];
    $status     = $_POST['status'];
    $custom_expire = $_POST['expire_date']; // admin bisa override

    try {
        $pdo->beginTransaction();

        // Update data user
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->execute([$full_name, $email, $phone, $user_id]);

        // Update password kalau diisi
        if (!empty($_POST['new_password'])) {
            $newHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newHash, $user_id]);
        }

        // Hitung expired date:
        // Kalau admin isi custom expire → pakai itu.
        // Kalau tidak → auto dari join_date + durasi paket.
        if (!empty($custom_expire)) {
            $expire = $custom_expire;
        } else {
            $pkg = $pdo->prepare("SELECT duration_days FROM packages WHERE id=?");
            $pkg->execute([$package_id]);
            $days = $pkg->fetchColumn() ?: 30;
            $expire = date('Y-m-d', strtotime($join_date . " +$days days"));
        }

        // Update data member
        $stmt = $pdo->prepare("UPDATE members SET package_id=?, join_date=?, expire_date=?, status=? WHERE id=?");
        $stmt->execute([$package_id, $join_date, $expire, $status, $member_id]);

        $pdo->commit();
        flash('success', "Data member $full_name berhasil diupdate");
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal: ' . $e->getMessage());
    }
    redirect('members.php' . return_query());
}

// ============================================================
// HANDLE PERPANJANG (EXTEND) — tambah 30 hari dari expire_date
// ============================================================
if (isset($_GET['extend'])) {
    $member_id = (int)$_GET['extend'];
    $days      = (int)($_GET['days'] ?? 30);

    $stmt = $pdo->prepare("
        UPDATE members 
        SET expire_date = DATE_ADD(GREATEST(expire_date, CURDATE()), INTERVAL ? DAY),
            status = 'active'
        WHERE id = ?
    ");
    $stmt->execute([$days, $member_id]);

    flash('success', "Member berhasil diperpanjang $days hari");
    redirect('members.php' . return_query());
}

// ============================================================
// HANDLE DELETE
// ============================================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT user_id FROM members WHERE id=?");
    $stmt->execute([$id]);
    $uid = $stmt->fetchColumn();
    if ($uid) $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
    flash('success', 'Member berhasil dihapus');
    redirect('members.php' . return_query());
}

// ============================================================
// HELPER: pertahankan parameter search & filter saat redirect
// ============================================================
function return_query(): string {
    $q      = trim($_POST['return_q'] ?? $_GET['return_q'] ?? '');
    $status = trim($_POST['return_status'] ?? $_GET['return_status'] ?? '');
    $parts  = [];
    if ($q !== '')      $parts[] = 'q=' . urlencode($q);
    if ($status !== '') $parts[] = 'status_filter=' . urlencode($status);
    return $parts ? ('?' . implode('&', $parts)) : '';
}

// ============================================================
// AMBIL DATA + SEARCH & FILTER
// ============================================================
$search       = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status_filter'] ?? '';
if (!in_array($statusFilter, ['active','expired','pending'], true)) $statusFilter = '';

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.username LIKE ? OR m.member_code LIKE ?)";
    $kw = "%$search%";
    array_push($params, $kw, $kw, $kw, $kw, $kw);
}

if ($statusFilter !== '') {
    $where[]  = "m.status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT m.*, u.full_name, u.email, u.phone, u.username, u.id AS user_id, p.name AS package_name
    FROM members m
    JOIN users u ON u.id = m.user_id
    LEFT JOIN packages p ON p.id = m.package_id
";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();

$packages = $pdo->query("SELECT * FROM packages WHERE is_active=1")->fetchAll();

$success = flash('success');
$error   = flash('error');

require __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black">Manajemen Members</h1>
        <p class="text-gray-500">Kelola semua member Gensa Fit</p>
    </div>
    <button onclick="showModal('modalAdd')" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-3 rounded-lg font-bold hover:scale-105 transition shadow-lg">
        + Tambah Member
    </button>
</div>

<?php if ($success): ?>
<div data-flash class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?= $error ?></div>
<?php endif; ?>

<!-- ============================================================
     SEARCH & FILTER
     ============================================================ -->
<form method="GET" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-[220px] relative">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Cari nama, email, no. HP, username, atau kode member..."
                   class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-100">
        </div>
        <select name="status_filter" class="px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-orange-500 bg-white">
            <option value="">Semua Status</option>
            <option value="active"  <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
            <option value="expired" <?= $statusFilter==='expired'?'selected':'' ?>>Expired</option>
            <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
        </select>
        <button type="submit" class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:scale-105 transition">
            Cari
        </button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="members.php" class="text-gray-500 hover:text-gray-800 font-semibold px-3 py-2.5">Reset</a>
        <?php endif; ?>
    </div>
    <div class="text-sm text-gray-500 mt-2">
        Menampilkan <b><?= count($members) ?></b> dari <b><?= $totalMembers ?></b> member
        <?php if ($search !== ''): ?>
            — hasil pencarian "<b><?= htmlspecialchars($search) ?></b>"
        <?php endif; ?>
    </div>
</form>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Paket</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Expired</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($members)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            Tidak ada member yang cocok dengan pencarian.
                            <a href="members.php" class="text-orange-600 font-semibold hover:underline ml-1">Reset filter</a>
                        <?php else: ?>
                            Belum ada member.
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: foreach ($members as $m):
                    // Hitung sisa hari
                    $sisa = $m['expire_date'] ? floor((strtotime($m['expire_date']) - time()) / 86400) : 0;
                ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div class="font-semibold"><?= htmlspecialchars($m['full_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($m['email']) ?> • <?= htmlspecialchars($m['phone']) ?></div>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($m['member_code']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($m['package_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 text-sm">
                        <div><?= $m['expire_date'] ? date('d M Y', strtotime($m['expire_date'])) : '-' ?></div>
                        <?php if ($sisa > 0 && $sisa <= 7): ?>
                        <div class="text-xs text-orange-500 font-semibold">⚠️ <?= $sisa ?> hari lagi</div>
                        <?php elseif ($sisa <= 0): ?>
                        <div class="text-xs text-red-500 font-semibold">Kadaluarsa</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?= $m['status']==='active' ? 'bg-green-100 text-green-700' : ($m['status']==='expired' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                            <?= ucfirst($m['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <button onclick='openEdit(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' 
                                class="text-blue-600 hover:text-blue-800 font-semibold text-sm mr-3">Edit</button>
                        <a href="?extend=<?= $m['id'] ?>&days=30<?= $search !== '' ? '&return_q=' . urlencode($search) : '' ?><?= $statusFilter !== '' ? '&return_status=' . urlencode($statusFilter) : '' ?>" 
                           data-confirm="Perpanjang paket 30 hari?"
                           class="text-green-600 hover:text-green-800 font-semibold text-sm mr-3">Perpanjang</a>
                        <a href="?delete=<?= $m['id'] ?><?= $search !== '' ? '&return_q=' . urlencode($search) : '' ?><?= $statusFilter !== '' ? '&return_status=' . urlencode($statusFilter) : '' ?>" 
                           data-confirm="Yakin hapus member ini?" 
                           class="text-red-500 hover:text-red-700 font-semibold text-sm">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL TAMBAH
     ============================================================ -->
<div id="modalAdd" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-black">Tambah Member Baru</h2>
            <button onclick="hideModal('modalAdd')" class="text-2xl text-gray-400 hover:text-gray-700">×</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="return_q" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter) ?>">
            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Username *</label><input type="text" name="username" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Password *</label><input type="text" name="password" required minlength="4" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Nama Lengkap *</label><input type="text" name="full_name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Email</label><input type="email" name="email" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">No. HP</label><input type="text" name="phone" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Tanggal Join *</label><input type="date" name="join_date" value="<?= date('Y-m-d') ?>" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold">Paket *</label>
                    <select name="package_id" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="">Pilih Paket</option>
                        <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> - Rp <?= number_format($p['price'],0,',','.') ?> (<?= $p['duration_days'] ?> hari)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modalAdd')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold hover:bg-slate-50">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL EDIT
     ============================================================ -->
<div id="modalEdit" class="hidden fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-xl font-black">Edit Member</h2>
            <button onclick="hideModal('modalEdit')" class="text-2xl text-gray-400 hover:text-gray-700">×</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="member_id" id="edit_member_id">
            <input type="hidden" name="user_id" id="edit_user_id">
            <input type="hidden" name="return_q" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter) ?>">

            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="text-sm font-semibold">Username</label>
                    <input type="text" id="edit_username" disabled class="w-full mt-1 border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-gray-500"></div>
                <div><label class="text-sm font-semibold">Password Baru</label>
                    <input type="text" name="new_password" placeholder="Kosongkan jika tidak diganti" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="edit_full_name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">No. HP</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Kode Member</label>
                    <input type="text" id="edit_member_code" disabled class="w-full mt-1 border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 font-mono text-gray-500"></div>
                <div><label class="text-sm font-semibold">Paket</label>
                    <select name="package_id" id="edit_package_id" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <?php foreach ($packages as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> - Rp <?= number_format($p['price'],0,',','.') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="text-sm font-semibold">Status</label>
                    <select name="status" id="edit_status" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div><label class="text-sm font-semibold">Tanggal Join</label>
                    <input type="date" name="join_date" id="edit_join_date" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"></div>
                <div><label class="text-sm font-semibold">Expired Date</label>
                    <input type="date" name="expire_date" id="edit_expire_date" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500">
                    <p class="text-xs text-gray-500 mt-1">Ubah manual, atau biarkan sesuai paket.</p>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideModal('modalEdit')" class="flex-1 border border-slate-300 py-3 rounded-lg font-semibold hover:bg-slate-50">Batal</button>
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white py-3 rounded-lg font-bold">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(m) {
    document.getElementById('edit_member_id').value   = m.id;
    document.getElementById('edit_user_id').value     = m.user_id;
    document.getElementById('edit_username').value    = m.username;
    document.getElementById('edit_member_code').value = m.member_code;
    document.getElementById('edit_full_name').value   = m.full_name || '';
    document.getElementById('edit_email').value       = m.email || '';
    document.getElementById('edit_phone').value       = m.phone || '';
    document.getElementById('edit_package_id').value  = m.package_id || '';
    document.getElementById('edit_status').value      = m.status || 'active';
    document.getElementById('edit_join_date').value   = m.join_date || '';
    document.getElementById('edit_expire_date').value = m.expire_date || '';

    showModal('modalEdit');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>