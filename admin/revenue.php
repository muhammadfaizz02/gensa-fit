<?php
// admin/revenue.php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

// Handle export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $year   = (int)($_GET['year'] ?? date('Y'));
    $month  = $_GET['month'] ?? 'all';
    $status = $_GET['status'] ?? 'paid';

    $sql = "SELECT pay.payment_date, u.full_name, m.member_code, p.name AS package_name,
                   pay.amount, pay.method, pay.status
            FROM payments pay
            JOIN members m ON m.id = pay.member_id
            JOIN users u ON u.id = m.user_id
            LEFT JOIN packages p ON p.id = pay.package_id
            WHERE YEAR(pay.payment_date) = ?";
    $params = [$year];
    if ($month !== 'all') { $sql .= " AND MONTH(pay.payment_date) = ?"; $params[] = (int)$month; }
    if ($status !== 'all') { $sql .= " AND pay.status = ?"; $params[] = $status; }
    $sql .= " ORDER BY pay.payment_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="revenue_' . $year . '_' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM untuk Excel
    fputcsv($out, ['Tanggal','Member','Kode','Paket','Jumlah','Metode','Status']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['payment_date'], $r['full_name'], $r['member_code'],
            $r['package_name'], $r['amount'], $r['method'], $r['status']
        ]);
    }
    fclose($out);
    exit;
}

$page_title  = 'Pendapatan';
$active_menu = 'revenue';
$role        = 'admin';

// ============================================================
// FILTER
// ============================================================
$filter_year  = (int)($_GET['year']  ?? date('Y'));
$filter_month = $_GET['month'] ?? 'all'; // 'all' atau 1-12
$filter_status = $_GET['status'] ?? 'paid'; // paid/pending/failed/all

// ============================================================
// STATISTIK UTAMA
// ============================================================

// Total pendapatan (all time, hanya paid)
$totalAllTime = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid'")->fetchColumn();

// Bulan ini
$totalThisMonth = $pdo->query("
    SELECT COALESCE(SUM(amount),0) FROM payments 
    WHERE status='paid' 
      AND YEAR(payment_date) = YEAR(CURDATE()) 
      AND MONTH(payment_date) = MONTH(CURDATE())
")->fetchColumn();

// Bulan lalu (untuk perbandingan)
$totalLastMonth = $pdo->query("
    SELECT COALESCE(SUM(amount),0) FROM payments 
    WHERE status='paid' 
      AND YEAR(payment_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
      AND MONTH(payment_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
")->fetchColumn();

// Growth %
$growth = $totalLastMonth > 0 
    ? round((($totalThisMonth - $totalLastMonth) / $totalLastMonth) * 100, 1) 
    : ($totalThisMonth > 0 ? 100 : 0);

// Transaksi bulan ini
$trxThisMonth = $pdo->query("
    SELECT COUNT(*) FROM payments 
    WHERE status='paid' 
      AND YEAR(payment_date) = YEAR(CURDATE()) 
      AND MONTH(payment_date) = MONTH(CURDATE())
")->fetchColumn();

// Rata-rata per transaksi bulan ini
$avgThisMonth = $trxThisMonth > 0 ? $totalThisMonth / $trxThisMonth : 0;

// ============================================================
// GRAFIK REVENUE 12 BULAN
// ============================================================
$chartData = $pdo->prepare("
    SELECT 
        MONTH(payment_date) AS bulan,
        COALESCE(SUM(amount),0) AS total
    FROM payments
    WHERE status='paid' AND YEAR(payment_date) = ?
    GROUP BY MONTH(payment_date)
    ORDER BY bulan
");
$chartData->execute([$filter_year]);
$rawChart = $chartData->fetchAll();

// Isi bulan yang kosong dengan 0
$monthlyRevenue = array_fill(1, 12, 0);
foreach ($rawChart as $r) {
    $monthlyRevenue[(int)$r['bulan']] = (float)$r['total'];
}
$chartValues = array_values($monthlyRevenue);

// ============================================================
// BREAKDOWN PER PAKET
// ============================================================
$perPackage = $pdo->prepare("
    SELECT p.name AS package_name, 
           COUNT(pay.id) AS trx_count, 
           COALESCE(SUM(pay.amount),0) AS total
    FROM payments pay
    LEFT JOIN packages p ON p.id = pay.package_id
    WHERE pay.status='paid' AND YEAR(pay.payment_date) = ?
    GROUP BY pay.package_id
    ORDER BY total DESC
");
$perPackage->execute([$filter_year]);
$perPackage = $perPackage->fetchAll();

// ============================================================
// DETAIL TRANSAKSI (dengan filter bulan)
// ============================================================
$sql = "
    SELECT pay.*, u.full_name, m.member_code, p.name AS package_name
    FROM payments pay
    JOIN members m ON m.id = pay.member_id
    JOIN users u ON u.id = m.user_id
    LEFT JOIN packages p ON p.id = pay.package_id
    WHERE YEAR(pay.payment_date) = ?
";
$params = [$filter_year];

if ($filter_month !== 'all') {
    $sql .= " AND MONTH(pay.payment_date) = ?";
    $params[] = (int)$filter_month;
}
if ($filter_status !== 'all') {
    $sql .= " AND pay.status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY pay.payment_date DESC, pay.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$monthNames = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

require __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-black">Pendapatan</h1>
        <p class="text-gray-500">Analisis pendapatan Gensa Fit</p>
    </div>
    <a href="?year=<?= $filter_year ?>&month=<?= $filter_month ?>&status=<?= $filter_status ?>&export=csv"
       class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-lg font-bold transition">
        ⬇ Export CSV
    </a>
</div>

<!-- ============================================================
     KARTU STATISTIK
     ============================================================ -->
<div class="grid md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-2xl p-6 shadow-lg stat-card">
        <div class="text-sm opacity-90 mb-1">Total Pendapatan</div>
        <div class="text-2xl font-black mb-2">Rp <?= number_format($totalAllTime, 0, ',', '.') ?></div>
        <div class="text-xs opacity-80">Semua waktu</div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-sm text-gray-500 mb-1">Bulan Ini</div>
        <div class="text-2xl font-black mb-2">Rp <?= number_format($totalThisMonth, 0, ',', '.') ?></div>
        <div class="text-xs <?= $growth >= 0 ? 'text-green-600' : 'text-red-600' ?> font-bold">
            <?= $growth >= 0 ? '▲' : '▼' ?> <?= abs($growth) ?>% dari bulan lalu
        </div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-sm text-gray-500 mb-1">Transaksi Bulan Ini</div>
        <div class="text-2xl font-black mb-2"><?= number_format($trxThisMonth) ?></div>
        <div class="text-xs text-gray-500">transaksi berhasil</div>
    </div>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 stat-card">
        <div class="text-sm text-gray-500 mb-1">Rata-rata / Transaksi</div>
        <div class="text-2xl font-black mb-2">Rp <?= number_format($avgThisMonth, 0, ',', '.') ?></div>
        <div class="text-xs text-gray-500">bulan ini</div>
    </div>
</div>

<!-- ============================================================
     GRAFIK + BREAKDOWN PAKET
     ============================================================ -->
<div class="grid md:grid-cols-3 gap-6 mb-8">
    
    <!-- GRAFIK BULANAN -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg">Pendapatan Bulanan <?= $filter_year ?></h3>
            <select onchange="location.href='?year='+this.value+'&month=<?= $filter_month ?>&status=<?= $filter_status ?>'"
                    class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-orange-500">
                <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $filter_year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <!-- BREAKDOWN PER PAKET -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-bold text-lg mb-4">Per Paket (<?= $filter_year ?>)</h3>
        <?php if (empty($perPackage)): ?>
            <p class="text-gray-500 text-sm">Belum ada data</p>
        <?php else:
            $grandTotal = array_sum(array_column($perPackage, 'total'));
            foreach ($perPackage as $pp):
                $pct = $grandTotal > 0 ? round(($pp['total'] / $grandTotal) * 100, 1) : 0;
        ?>
        <div class="mb-4">
            <div class="flex justify-between items-center mb-1">
                <span class="font-semibold text-sm"><?= htmlspecialchars($pp['package_name'] ?? 'Tanpa Paket') ?></span>
                <span class="text-xs text-gray-500"><?= $pct ?>%</span>
            </div>
            <div class="text-sm font-bold text-orange-500 mb-1">Rp <?= number_format($pp['total'], 0, ',', '.') ?></div>
            <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-orange-500 to-red-600" style="width: <?= $pct ?>%"></div>
            </div>
            <div class="text-xs text-gray-500 mt-1"><?= $pp['trx_count'] ?> transaksi</div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- ============================================================
     FILTER TRANSAKSI
     ============================================================ -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b flex flex-wrap justify-between items-center gap-3">
        <h3 class="font-bold text-lg">Detail Transaksi</h3>
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="hidden" name="year" value="<?= $filter_year ?>">
            <select name="month" onchange="this.form.submit()" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="all" <?= $filter_month==='all'?'selected':'' ?>>Semua Bulan</option>
                <?php foreach ($monthNames as $num => $name): ?>
                <option value="<?= $num ?>" <?= $filter_month==$num?'selected':'' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" onchange="this.form.submit()" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="paid" <?= $filter_status==='paid'?'selected':'' ?>>Paid</option>
                <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                <option value="failed" <?= $filter_status==='failed'?'selected':'' ?>>Failed</option>
                <option value="all" <?= $filter_status==='all'?'selected':'' ?>>Semua Status</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Metode</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($transactions)): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Tidak ada transaksi</td></tr>
                <?php else: 
                    $subtotal = 0;
                    foreach ($transactions as $t): 
                        if ($t['status'] === 'paid') $subtotal += $t['amount'];
                ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 text-sm"><?= date('d M Y', strtotime($t['payment_date'])) ?></td>
                    <td class="px-6 py-4">
                        <div class="font-semibold text-sm"><?= htmlspecialchars($t['full_name']) ?></div>
                        <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($t['member_code']) ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm"><?= htmlspecialchars($t['package_name'] ?? '-') ?></td>
                    <td class="px-6 py-4 font-bold">Rp <?= number_format($t['amount'], 0, ',', '.') ?></td>
                    <td class="px-6 py-4"><span class="bg-slate-100 px-3 py-1 rounded-full text-xs"><?= ucfirst($t['method']) ?></span></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold
                            <?= $t['status']==='paid'?'bg-green-100 text-green-700':($t['status']==='pending'?'bg-yellow-100 text-yellow-700':'bg-red-100 text-red-700') ?>">
                            <?= ucfirst($t['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="bg-slate-50 font-bold">
                    <td colspan="3" class="px-6 py-4 text-right">SUBTOTAL (Paid):</td>
                    <td class="px-6 py-4 text-orange-600">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                    <td colspan="2"></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     CHART.JS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: <?= json_encode($chartValues) ?>,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249,115,22,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000) + 'rb';
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>