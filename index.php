<?php
// php-rlsm/index.php
require_once __DIR__ . '/algorithms.php';

$daftarLokasi = load_raw_data();
$dbEmpty = count($daftarLokasi) === 0;
$hasilGA = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dbEmpty) {
    $normalizedData = preprocess_and_normalize($daftarLokasi);
    $hasilGA = jalankanGA($normalizedData, 100, 30);
}

$totalKandidat = count($daftarLokasi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Optimasi Lokasi Supermarket - Genetic Algorithm (GA)</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #070a13;
            --card-bg: rgba(15, 23, 42, 0.65);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-lg: 0 10px 30px -10px rgba(0, 0, 0, 0.7);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-color);
            background-image: radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* Glass Nav */
        .glass-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(7, 10, 19, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
        }

        .nav-brand {
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            background: linear-gradient(135deg, #60a5fa, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 16px;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active {
            color: #fff;
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        .container {
            max-width: 1000px;
            margin: 40px auto 0;
            padding: 0 24px;
        }

        header {
            margin-bottom: 35px;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-muted);
            margin-top: 4px;
            font-size: 0.95rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary), #8b5cf6);
            opacity: 0.7;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
        }

        /* Optimize Card */
        .card-form {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            margin-bottom: 35px;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .btn-optimize {
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35);
            margin-top: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-optimize:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(59, 130, 246, 0.5);
        }

        .alert-empty {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            text-align: center;
        }

        .alert-empty a {
            color: #93c5fd;
            font-weight: 600;
            text-decoration: underline;
        }

        /* Split Result Layout */
        .result-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
            margin-bottom: 35px;
        }

        @media (max-width: 768px) {
            .result-layout {
                grid-template-columns: 1fr;
            }
        }

        .card-result-glow {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: var(--shadow-lg);
            position: relative;
        }

        .glow-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.05);
            border: 4px solid var(--primary);
            box-shadow: 0 0 25px rgba(37, 99, 235, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
        }

        .card-result-details {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-lg);
        }

        .result-details-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 480px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        /* Chart Card */
        .chart-card {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            text-align: center;
        }

        .chart-container {
            height: 320px;
            position: relative;
        }
    </style>
</head>
<body>

<nav class="glass-nav">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">🧬 GA SPK</a>
        <div class="nav-links">
            <a href="index.php" class="nav-link active">Dashboard</a>
            <a href="kelola_lokasi.php" class="nav-link">Kelola Lokasi</a>
        </div>
    </div>
</nav>

<div class="container">
    <header>
        <h1>Optimasi Lokasi Supermarket</h1>
        <p class="subtitle">Sistem Penunjang Keputusan berbasis Algoritma Genetika (Genetic Algorithm - GA) sesuai metodologi jurnal.</p>
    </header>

    <?php if ($dbEmpty): ?>
        <div class="alert-empty">
            <h3 style="font-weight: 700; margin-bottom: 8px;">Database Kandidat Kosong!</h3>
            <p>Silakan lakukan import data awal terlebih dahulu dengan membuka <a href="db_setup.php">db_setup.php</a> atau tambahkan kandidat secara manual di halaman <a href="kelola_lokasi.php">Kelola Lokasi</a>.</p>
        </div>
    <?php else: ?>
        <!-- Stats Summary Card -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Kandidat Lokasi Terdaftar</div>
                <div class="stat-value"><?= $totalKandidat ?> Alternatif</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ukuran Populasi (GA)</div>
                <div class="stat-value">30 Kromosom</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Maksimal Generasi</div>
                <div class="stat-value">100 Generasi</div>
            </div>
        </div>

        <!-- Optimize Button Area -->
        <div class="card-form">
            <form method="POST">
                <h3 style="font-weight: 600; font-size: 1.1rem; color: #cbd5e1; margin-bottom: 8px;">Siap Melakukan Analisis Lokasi?</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 600px; margin: 0 auto 10px;">Algoritma Genetika akan mengevaluasi populasi biner secara evolusioner (seleksi, crossover, mutasi) untuk mencari rekomendasi optimal.</p>
                <button type="submit" class="btn-optimize">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation: pulse 2s infinite;"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    Jalankan Proses Optimasi GA
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($hasilGA !== null): ?>
        <!-- Split Results Layout -->
        <div class="result-layout">
            <!-- Glow Fitness Card -->
            <div class="card-result-glow">
                <div class="glow-circle">
                    <?= number_format($hasilGA['fitnessTerbaik'], 4) ?>
                </div>
                <h3 style="font-weight: 700; font-size: 1.2rem; color: #fff; margin-bottom: 4px;">Rekomendasi Terbaik</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">Skor Fitness Tertinggi (GA)</p>
            </div>

            <!-- Detailed Parameter Grid -->
            <div class="card-result-details">
                <div class="result-details-title">
                    <span style="color: var(--success); font-weight: 700; font-size: 1.4rem;"><?= htmlspecialchars($hasilGA['lokasiTerbaik']['nama_daerah']) ?></span>
                </div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Populasi Penduduk</div>
                        <div class="detail-value"><?= number_format($hasilGA['lokasiTerbaik']['populasi'], 0, ',', '.') ?> Ribu</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pendapatan Masyarakat</div>
                        <div class="detail-value">Rp <?= number_format($hasilGA['lokasiTerbaik']['pendapatan'], 0, ',', '.') ?> Jt/Thn</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Aksesibilitas Lokasi</div>
                        <div class="detail-value"><?= $hasilGA['lokasiTerbaik']['aksesibilitas'] ?> / 10</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Jarak ke Kompetitor</div>
                        <div class="detail-value"><?= number_format($hasilGA['lokasiTerbaik']['jarak_pesaing'], 2, ',', '.') ?> Km</div>
                    </div>
                    <div class="detail-item" style="border-color: rgba(239, 68, 68, 0.25);">
                        <div class="detail-label">Sewa Tanah (Cost)</div>
                        <div class="detail-value" style="color: #f87171;">Rp <?= number_format($hasilGA['lokasiTerbaik']['sewa_tanah'], 0, ',', '.') ?> Jt/Thn</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Volume Lalu Lintas</div>
                        <div class="detail-value"><?= number_format($hasilGA['lokasiTerbaik']['lalu_lintas'], 0, ',', '.') ?> Kend/Jam</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Convergence Line Chart -->
        <div class="chart-card">
            <h3 class="chart-title">Grafik Konvergensi GA (GA Fitness Convergence Curve)</h3>
            <div class="chart-container">
                <canvas id="convergenceChart"></canvas>
            </div>
        </div>

        <script>
            const ctx = document.getElementById('convergenceChart').getContext('2d');
            const historyData = <?= json_encode($hasilGA['history']) ?>;
            
            // Create a premium blue-indigo gradient for fill
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.25)');
            gradient.addColorStop(1, 'rgba(139, 92, 246, 0.01)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Object.keys(historyData),
                    datasets: [{
                        label: 'Fitness Terbaik',
                        data: Object.values(historyData),
                        borderColor: '#60a5fa',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: '#fff',
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: { 
                            title: { display: true, text: 'Iterasi Ke-', color: '#94a3b8' },
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8' }
                        },
                        y: { 
                            title: { display: true, text: 'Nilai Fitness', color: '#94a3b8' },
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>
</div>
</body>
</html>