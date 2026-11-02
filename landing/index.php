<?php
// landing/index.php
require_once __DIR__ . '/../config/database.php';
$packages = $pdo->query("SELECT * FROM packages WHERE is_active=1")->fetchAll();
$schedules = $pdo->query("
    SELECT s.*, c.name AS class_name, c.duration_minutes, u.full_name AS instructor
    FROM schedules s
    JOIN classes c ON c.id = s.class_id
    LEFT JOIN instructors i ON i.id = c.instructor_id
    LEFT JOIN users u ON u.id = i.user_id
    ORDER BY FIELD(s.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time
")->fetchAll();

$dayLabel = [
    'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
    'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gensa Fit - Elevate Your Strength</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        /* ====================================================
           HERO BACKGROUND — Gradient + Grid Pattern (tanpa gambar)
           ==================================================== */
        .hero-bg {
            position: relative;
            background:
                radial-gradient(circle at 20% 30%, rgba(249,115,22,0.25), transparent 45%),
                radial-gradient(circle at 80% 70%, rgba(220,38,38,0.25), transparent 45%),
                linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            overflow: hidden;
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(249,115,22,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(249,115,22,0.07) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }
        .hero-bg::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 200px;
            background: linear-gradient(to top, #020617, transparent);
            pointer-events: none;
            z-index: 0;
        }
        .hero-bg > * {
            position: relative;
            z-index: 1;
        }

        html { scroll-behavior: smooth; }

        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        .fade-up.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #f97316; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #ea580c; }
    </style>
</head>
<body class="bg-slate-950 text-white">

<!-- ====================================================
     NAVBAR
     ==================================================== -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="#home" class="text-3xl font-black">Gensa<span class="text-orange-500">Fit</span></a>
        <div class="hidden md:flex items-center gap-8">
            <a href="#home" class="text-gray-300 hover:text-orange-500 transition font-medium">Home</a>
            <a href="#about" class="text-gray-300 hover:text-orange-500 transition font-medium">Tentang Kami</a>
            <a href="#services" class="text-gray-300 hover:text-orange-500 transition font-medium">Layanan</a>
            <a href="#packages" class="text-gray-300 hover:text-orange-500 transition font-medium">Paket</a>
            <a href="#schedule" class="text-gray-300 hover:text-orange-500 transition font-medium">Jadwal</a>
            <a href="#contact" class="text-gray-300 hover:text-orange-500 transition font-medium">Kontak</a>
        </div>
        <a href="../auth/login.php" class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 px-6 py-2.5 rounded-full font-bold transition transform hover:scale-105 shadow-lg shadow-orange-500/30">
            Login
        </a>
    </div>
</nav>

<!-- ====================================================
     HERO
     ==================================================== -->
<section id="home" class="hero-bg min-h-screen flex items-center justify-center pt-20">
    <div class="max-w-6xl mx-auto px-6 text-center">
        <span class="inline-block bg-orange-500/20 border border-orange-500/50 text-orange-400 px-4 py-1.5 rounded-full text-sm font-semibold mb-6 fade-up">
            🔥 #1 Gym Management di Indonesia
        </span>
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-black mb-6 leading-tight fade-up">
            ELEVATE YOUR<br>
            <span class="bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-transparent">STRENGTH</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto mb-10 fade-up">
            Bergabung dengan Gensa Fit dan ubah tubuh Anda menjadi versi terbaik. Fasilitas premium, instruktur profesional, dan komunitas yang mendukung.
        </p>
        <div class="flex flex-wrap gap-4 justify-center fade-up">
            <a href="#packages" class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 px-8 py-4 rounded-full font-bold text-lg transition transform hover:scale-105 shadow-xl shadow-orange-500/30">
                Mulai Sekarang →
            </a>
            <a href="#about" class="border-2 border-white/30 hover:border-orange-500 px-8 py-4 rounded-full font-bold text-lg transition">
                Pelajari Lebih
            </a>
        </div>

        <div class="grid grid-cols-3 gap-8 max-w-2xl mx-auto mt-20 fade-up">
            <div><div class="text-4xl font-black text-orange-500">5000+</div><div class="text-gray-400 text-sm">Members</div></div>
            <div><div class="text-4xl font-black text-orange-500">50+</div><div class="text-gray-400 text-sm">Kelas/Minggu</div></div>
            <div><div class="text-4xl font-black text-orange-500">25+</div><div class="text-gray-400 text-sm">Instruktur</div></div>
        </div>
    </div>
</section>

<!-- ====================================================
     ABOUT — Kartu Dekoratif (Solusi 1)
     ==================================================== -->
<section id="about" class="py-24 bg-slate-900">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-16 items-center">

        <!-- KIRI: Kartu dekoratif gradient + emoji -->
        <div class="fade-up">
            <div class="relative bg-gradient-to-br from-orange-500 to-red-700 rounded-3xl p-10 shadow-2xl shadow-orange-500/30 overflow-hidden">
                <!-- Ornamen background besar -->
                <div class="absolute -right-12 -top-12 text-[220px] opacity-10 select-none pointer-events-none">💪</div>
                <div class="absolute -left-8 -bottom-8 text-[140px] opacity-10 select-none pointer-events-none">🏋️</div>

                <div class="relative z-10">
                    <div class="text-7xl mb-6">🏋️‍♂️</div>
                    <h3 class="text-3xl md:text-4xl font-black mb-4 leading-tight">
                        Bentuk Tubuh<br>Impianmu
                    </h3>
                    <p class="text-orange-100 leading-relaxed mb-8">
                        Dengan program yang tepat dan dukungan komunitas yang solid, tidak ada tujuan yang tidak mungkin.
                    </p>

                    <div class="grid grid-cols-2 gap-6 pt-6 border-t border-white/20">
                        <div>
                            <div class="text-4xl font-black">10+</div>
                            <div class="text-sm text-orange-100">Tahun Pengalaman</div>
                        </div>
                        <div>
                            <div class="text-4xl font-black">50+</div>
                            <div class="text-sm text-orange-100">Jenis Latihan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KANAN: Teks -->
        <div class="fade-up">
            <span class="text-orange-500 font-bold tracking-widest text-sm">TENTANG KAMI</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3 mb-6">
                Lebih dari Sekedar <span class="text-orange-500">Gym</span>
            </h2>
            <p class="text-gray-400 leading-relaxed mb-8">
                Gensa Fit adalah pusat kebugaran modern yang berkomitmen membantu Anda mencapai tujuan fitness. Dengan peralatan berteknologi tinggi, kelas beragam, dan instruktur bersertifikat, kami hadir untuk setiap level — dari pemula hingga atlet profesional.
            </p>
            <div class="space-y-5">
                <div class="flex items-start gap-4">
                    <div class="bg-orange-500/20 p-3 rounded-lg shrink-0"><span class="text-2xl">🏆</span></div>
                    <div>
                        <h4 class="font-bold">Peralatan Premium</h4>
                        <p class="text-gray-400 text-sm">Mesin fitness terbaru dari brand internasional</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="bg-orange-500/20 p-3 rounded-lg shrink-0"><span class="text-2xl">👥</span></div>
                    <div>
                        <h4 class="font-bold">Instruktur Bersertifikat</h4>
                        <p class="text-gray-400 text-sm">Tim profesional dengan pengalaman bertahun-tahun</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="bg-orange-500/20 p-3 rounded-lg shrink-0"><span class="text-2xl">⏰</span></div>
                    <div>
                        <h4 class="font-bold">Akses 24/7</h4>
                        <p class="text-gray-400 text-sm">Latihan kapanpun sesuai jadwal Anda</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====================================================
     SERVICES
     ==================================================== -->
<section id="services" class="py-24 bg-slate-950">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 fade-up">
            <span class="text-orange-500 font-bold tracking-widest text-sm">LAYANAN KAMI</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3 mb-4">Apa yang Kami Tawarkan</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">Berbagai layanan fitness untuk mendukung perjalanan kebugaran Anda.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <?php
            $services = [
                ['💪', 'Weight Training', 'Area angkat beban lengkap dengan dumbell, barbell, dan mesin modern.'],
                ['🧘', 'Yoga & Pilates', 'Kelas yoga dan pilates untuk fleksibilitas dan ketenangan pikiran.'],
                ['🚴', 'Cardio Zone', 'Treadmill, sepeda statis, dan elliptical untuk latihan kardio optimal.'],
                ['🥊', 'Boxing & MMA', 'Latihan bela diri dengan instruktur profesional dan ring standar.'],
                ['🏋️', 'Personal Trainer', 'Program latihan personal yang disesuaikan dengan target Anda.'],
                ['🥗', 'Nutrition Consulting', 'Konsultasi gizi dengan ahli untuk hasil maksimal.'],
            ];
            foreach ($services as $s): ?>
            <div class="fade-up bg-gradient-to-br from-slate-800 to-slate-900 border border-slate-700 hover:border-orange-500 p-8 rounded-2xl transition-all duration-300 hover:transform hover:-translate-y-2 hover:shadow-2xl hover:shadow-orange-500/20">
                <div class="text-5xl mb-4"><?= $s[0] ?></div>
                <h3 class="text-xl font-bold mb-3"><?= $s[1] ?></h3>
                <p class="text-gray-400 text-sm leading-relaxed"><?= $s[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ====================================================
     PACKAGES
     ==================================================== -->
<section id="packages" class="py-24 bg-slate-900">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 fade-up">
            <span class="text-orange-500 font-bold tracking-widest text-sm">PAKET MEMBERSHIP</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3 mb-4">Pilih Paket Anda</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">Harga transparan, tanpa biaya tersembunyi.</p>
        </div>

        <?php if (empty($packages)): ?>
            <div class="text-center text-gray-500 py-12">Belum ada paket tersedia.</div>
        <?php else: ?>
        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($packages as $i => $p):
                $popular = $i === 1;
            ?>
            <div class="fade-up relative <?= $popular ? 'bg-gradient-to-br from-orange-600 to-red-700 md:scale-105 shadow-2xl shadow-orange-500/30' : 'bg-slate-800 border border-slate-700' ?> rounded-2xl p-8 transition-all duration-300 hover:transform hover:-translate-y-2">
                <?php if ($popular): ?>
                <span class="absolute -top-4 left-1/2 -translate-x-1/2 bg-white text-orange-600 px-4 py-1 rounded-full text-xs font-black">POPULAR</span>
                <?php endif; ?>
                <h3 class="text-2xl font-black mb-2"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="text-sm <?= $popular ? 'text-orange-100' : 'text-gray-400' ?> mb-6"><?= htmlspecialchars($p['description']) ?></p>
                <div class="mb-6">
                    <span class="text-4xl font-black">Rp <?= number_format($p['price'], 0, ',', '.') ?></span>
                    <span class="<?= $popular ? 'text-orange-100' : 'text-gray-400' ?> text-sm">/<?= $p['duration_days'] ?> hari</span>
                </div>
                <ul class="space-y-3 mb-8">
                    <?php foreach (explode(',', $p['features']) as $f): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 <?= $popular ? 'text-white' : 'text-orange-500' ?>" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <?= htmlspecialchars(trim($f)) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="#contact" class="block text-center w-full <?= $popular ? 'bg-white text-orange-600 hover:bg-orange-50' : 'bg-orange-500 hover:bg-orange-600 text-white' ?> font-bold py-3 rounded-lg transition">
                    Pilih Paket
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ====================================================
     SCHEDULE
     ==================================================== -->
<section id="schedule" class="py-24 bg-slate-950">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 fade-up">
            <span class="text-orange-500 font-bold tracking-widest text-sm">JADWAL KELAS</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3 mb-4">Jadwal Mingguan</h2>
            <p class="text-gray-400">Ikuti kelas favorit Anda dengan instruktur profesional.</p>
        </div>
        <div class="fade-up overflow-x-auto bg-slate-900 rounded-2xl border border-slate-800">
            <table class="w-full">
                <thead class="bg-slate-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-orange-500">Hari</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-orange-500">Kelas</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-orange-500">Instruktur</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-orange-500">Waktu</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-orange-500">Ruangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php if (empty($schedules)): ?>
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada jadwal tersedia</td></tr>
                    <?php else: foreach ($schedules as $s): ?>
                    <tr class="hover:bg-slate-800/50 transition">
                        <td class="px-6 py-4 font-semibold"><?= $dayLabel[$s['day']] ?? $s['day'] ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($s['class_name']) ?></td>
                        <td class="px-6 py-4 text-gray-400"><?= htmlspecialchars($s['instructor'] ?? '-') ?></td>
                        <td class="px-6 py-4 text-orange-400 font-medium"><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?></td>
                        <td class="px-6 py-4 text-gray-400"><?= htmlspecialchars($s['room'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ====================================================
     CONTACT
     ==================================================== -->
<section id="contact" class="py-24 bg-slate-900">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16 fade-up">
            <span class="text-orange-500 font-bold tracking-widest text-sm">HUBUNGI KAMI</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3 mb-4">Mari Terhubung</h2>
            <p class="text-gray-400">Punya pertanyaan? Tim kami siap membantu Anda.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-12">
            <div class="fade-up space-y-6">
                <div class="flex items-start gap-4 bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <div class="bg-orange-500/20 p-3 rounded-lg text-2xl">📍</div>
                    <div><h4 class="font-bold mb-1">Alamat</h4><p class="text-gray-400 text-sm">Jl. R.A.A Wiranata Kusumah No.8, Baleendah, Kec. Baleendah, Kabupaten Bandung, Jawa Barat 40375</p></div>
                </div>
                <div class="flex items-start gap-4 bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <div class="bg-orange-500/20 p-3 rounded-lg text-2xl">📞</div>
                    <div><h4 class="font-bold mb-1">Telepon</h4><p class="text-gray-400 text-sm">+62 851-9924-1559</p></div>
                </div>
                <div class="flex items-start gap-4 bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <div class="bg-orange-500/20 p-3 rounded-lg text-2xl">✉️</div>
                    <div><h4 class="font-bold mb-1">Email</h4><p class="text-gray-400 text-sm">info@gensafit.com</p></div>
                </div>
                <div class="flex items-start gap-4 bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <div class="bg-orange-500/20 p-3 rounded-lg text-2xl">🕒</div>
                    <div><h4 class="font-bold mb-1">Jam Operasional</h4><p class="text-gray-400 text-sm">Senin - Jumat: 05.00 - 23.00<br>Sabtu - Minggu: 06.00 - 22.00</p></div>
                </div>
            </div>
            <form class="fade-up bg-slate-800 p-8 rounded-2xl border border-slate-700 space-y-5" onsubmit="event.preventDefault(); alert('Pesan terkirim! Kami akan segera menghubungi Anda.'); this.reset();">
                <input type="text" placeholder="Nama Lengkap" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition">
                <input type="email" placeholder="Email" required class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition">
                <input type="text" placeholder="Subjek" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition">
                <textarea rows="5" placeholder="Pesan Anda..." class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-orange-500 transition resize-none"></textarea>
                <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 font-bold py-3 rounded-lg transition transform hover:scale-[1.02]">
                    Kirim Pesan
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ====================================================
     FOOTER
     ==================================================== -->
<footer class="bg-black border-t border-slate-800 py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <div>
                <div class="text-3xl font-black mb-4">Gensa<span class="text-orange-500">Fit</span></div>
                <p class="text-gray-400 text-sm">Elevate your strength dengan fasilitas premium dan komunitas yang mendukung.</p>
            </div>
            <div>
                <h4 class="font-bold mb-4">Menu</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="#home" class="hover:text-orange-500">Home</a></li>
                    <li><a href="#about" class="hover:text-orange-500">Tentang</a></li>
                    <li><a href="#services" class="hover:text-orange-500">Layanan</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Layanan</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li>Weight Training</li>
                    <li>Yoga & Pilates</li>
                    <li>Personal Trainer</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold mb-4">Ikuti Kami</h4>
                <div class="flex gap-3">
                    <a href="#" class="bg-slate-800 hover:bg-orange-500 w-10 h-10 rounded-full flex items-center justify-center transition">📘</a>
                    <a href="#" class="bg-slate-800 hover:bg-orange-500 w-10 h-10 rounded-full flex items-center justify-center transition">📷</a>
                    <a href="#" class="bg-slate-800 hover:bg-orange-500 w-10 h-10 rounded-full flex items-center justify-center transition">🐦</a>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-800 pt-6 text-center text-gray-500 text-sm">
            © <?= date('Y') ?> Gensa Fit. All rights reserved. Made with 💪 in Indonesia.
        </div>
    </div>
</footer>

<!-- ====================================================
     SCRIPTS
     ==================================================== -->
<script>
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('bg-slate-950/95', 'backdrop-blur-lg', 'shadow-lg');
        } else {
            navbar.classList.remove('bg-slate-950/95', 'backdrop-blur-lg', 'shadow-lg');
        }
    });

    // Fade-up on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('show');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
</script>
</body>
</html>