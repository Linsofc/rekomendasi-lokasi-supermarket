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
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
            max-width: 1060px;
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-optimize:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(59, 130, 246, 0.5);
        }

        .btn-optimize:active {
            transform: translateY(0);
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
            border: 1px solid rgba(37, 99, 235, 0.25);
            border-radius: var(--radius-lg);
            padding: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: var(--shadow-lg), 0 0 40px rgba(37, 99, 235, 0.1);
            position: relative;
        }

        .rank-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .glow-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.05);
            border: 4px solid var(--primary);
            box-shadow: 0 0 25px rgba(37, 99, 235, 0.4), inset 0 0 30px rgba(37, 99, 235, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            animation: pulseGlow 3s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 25px rgba(37, 99, 235, 0.4), inset 0 0 30px rgba(37, 99, 235, 0.05); }
            50% { box-shadow: 0 0 40px rgba(37, 99, 235, 0.7), inset 0 0 40px rgba(37, 99, 235, 0.1); }
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
            transition: border-color 0.2s ease;
        }

        .detail-item:hover {
            border-color: rgba(37, 99, 235, 0.3);
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
            margin-bottom: 6px;
            text-align: center;
        }

        .chart-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 20px;
        }

        .chart-container {
            height: 340px;
            position: relative;
        }

        /* Chart Legend */
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 14px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        /* Ranking Table */
        .ranking-card {
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 35px;
            overflow-x: auto;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .ranking-table th {
            text-align: left;
            padding: 10px 14px;
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .ranking-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
            transition: background 0.15s;
        }

        .ranking-table tr:hover td {
            background: rgba(255,255,255,0.025);
        }

        .ranking-table tr:last-child td {
            border-bottom: none;
        }

        /* Row highlight for rank 1 */
        .ranking-table tr.rank-1 td {
            background: rgba(37, 99, 235, 0.07);
        }

        .ranking-table tr.rank-1:hover td {
            background: rgba(37, 99, 235, 0.12);
        }

        .rank-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .rank-1-badge { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
        .rank-2-badge { background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff; }
        .rank-3-badge { background: linear-gradient(135deg, #b45309, #92400e); color: #fff; }
        .rank-other  { background: rgba(255,255,255,0.07); color: var(--text-muted); }

        .fitness-bar-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fitness-bar {
            height: 6px;
            border-radius: 4px;
            background: linear-gradient(90deg, #2563eb, #8b5cf6);
            transition: width 0.5s ease;
            min-width: 4px;
        }

        .fitness-val {
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            font-size: 0.92rem;
        }

        .best-tag {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 6px;
        }

        .nama-cell {
            font-weight: 600;
            color: #fff;
        }

        /* Score bar container total width */
        .bar-container {
            width: 100px;
        }

        /* Skor normalisasi per kriteria */
        .score-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 5px;
        }

        .score-pill {
            font-size: 0.68rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pill-high   { background: rgba(16,185,129,0.18); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
        .pill-mid    { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);  }
        .pill-low    { background: rgba(239,68,68,0.13);  color: #f87171; border: 1px solid rgba(239,68,68,0.2);  }

        /* Normalization method badge */
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            color: #a5b4fc;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
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
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 600px; margin: 0 auto 10px;">Algoritma Genetika akan mengevaluasi populasi secara evolusioner (seleksi, crossover, mutasi) untuk mencari rekomendasi optimal.</p>
                <button type="submit" id="btn-run" class="btn-optimize" onclick="this.disabled=true; this.innerHTML='⏳ Sedang Memproses...'; this.form.submit();">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
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
                <span class="rank-badge">🏆 Peringkat #1 GA</span>
                <div class="glow-circle">
                    <?= number_format($hasilGA['fitnessTerbaik'], 4) ?>
                </div>
                <h3 style="font-weight: 700; font-size: 1.2rem; color: #fff; margin-bottom: 4px;">Rekomendasi Terbaik</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Skor Fitness Tertinggi (GA)</p>
                <div style="background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.25); border-radius: var(--radius-sm); padding: 8px 16px; font-size: 0.82rem; color: #93c5fd;">
                    Bobot tiap kriteria: <strong><?= number_format(array_values($hasilGA['weights'])[0] * 100, 2) ?>%</strong> (seimbang)
                </div>
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
                        <div class="detail-label">Sewa Tanah (Cost ↓)</div>
                        <div class="detail-value" style="color: #f87171;">Rp <?= number_format($hasilGA['lokasiTerbaik']['sewa_tanah'], 0, ',', '.') ?> Jt/Thn</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Volume Lalu Lintas</div>
                        <div class="detail-value"><?= number_format($hasilGA['lokasiTerbaik']['lalu_lintas'], 0, ',', '.') ?> Kend/Jam</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking Semua Alternatif -->
        <div class="ranking-card">
            <div class="section-title">📊 Skor &amp; Peringkat Semua Alternatif Lokasi</div>

            <div class="method-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Normalisasi: <strong>Rank-Based (tahan outlier)</strong> — penilaian adil untuk semua nilai input
            </div>

            <?php
                $maxFitness = $hasilGA['semuaAlternatif'][0]['fitness'];
                $minFitness = end($hasilGA['semuaAlternatif'])['fitness'];
                $range      = ($maxFitness - $minFitness) > 0 ? ($maxFitness - $minFitness) : 1;

                // Helper: tentukan kelas pill berdasarkan skor normalisasi
                $pillClass = fn(float $s) => $s >= 0.65 ? 'pill-high' : ($s >= 0.30 ? 'pill-mid' : 'pill-low');

                $labelKriteria = [
                    'populasi'      => 'Pop',
                    'pendapatan'    => 'Pend',
                    'aksesibilitas' => 'Aks',
                    'jarak_pesaing' => 'Jrk',
                    'sewa_tanah'    => 'Sewa',
                    'lalu_lintas'   => 'Lalu',
                ];
            ?>
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Lokasi &amp; Nilai Asli</th>
                        <th style="min-width:230px;">Skor Normalisasi per Kriteria <span style="font-weight:400;color:#475569;">(0.00–1.00)</span></th>
                        <th>Skor Fitness</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($hasilGA['semuaAlternatif'] as $alt): ?>
                    <?php
                        $rank = $alt['ranking'];
                        $loc  = $alt['lokasi'];
                        $fit  = $alt['fitness'];
                        $sk   = $alt['skor_kriteria'];
                        $barPct = 8 + (($fit - $minFitness) / $range) * 92;
                        $badgeClass = match($rank) {
                            1 => 'rank-1-badge',
                            2 => 'rank-2-badge',
                            3 => 'rank-3-badge',
                            default => 'rank-other'
                        };
                        $rowClass = $rank === 1 ? 'rank-1' : '';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td><span class="rank-num <?= $badgeClass ?>"><?= $rank ?></span></td>
                        <td>
                            <span class="nama-cell"><?= htmlspecialchars($loc['nama_daerah']) ?></span>
                            <?php if ($rank === 1): ?><span class="best-tag">Terbaik</span><?php endif; ?>
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px;line-height:1.5;">
                                Pop: <?= number_format($loc['populasi'],0,',','.') ?> &middot;
                                Pend: <?= number_format($loc['pendapatan'],0,',','.') ?>jt &middot;
                                Aks: <?= $loc['aksesibilitas'] ?>/10<br>
                                Jrk: <?= number_format($loc['jarak_pesaing'],2,',','.') ?>km &middot;
                                Sewa: <?= number_format($loc['sewa_tanah'],0,',','.') ?>jt &middot;
                                Lalu: <?= number_format($loc['lalu_lintas'],0,',','.') ?>
                            </div>
                        </td>
                        <td>
                            <div class="score-pills">
                                <?php foreach ($labelKriteria as $key => $label): ?>
                                    <span class="score-pill <?= $pillClass($sk[$key]) ?>" title="<?= $key ?>">
                                        <?= $label ?>: <?= number_format($sk[$key], 2) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div class="fitness-bar-wrap">
                                <div class="bar-container">
                                    <div class="fitness-bar" style="width: <?= round($barPct) ?>%;"></div>
                                </div>
                                <span class="fitness-val"><?= number_format($fit, 4) ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top: 14px; font-size: 0.78rem; color: var(--text-muted); line-height: 1.6;">
                ✅ <strong style="color:#a5b4fc;">Rank-Based Normalization</strong> memastikan penilaian adil meski ada nilai ekstrem.
                Skor tiap kriteria dihitung dari <em>posisi relatif</em> lokasi tersebut (bukan nilai absolut),
                sehingga outlier tidak merusak skor lokasi lain.
                Warna pill: <span style="color:#34d399;">■ tinggi (≥0.65)</span> · <span style="color:#fbbf24;">■ sedang (0.30–0.64)</span> · <span style="color:#f87171;">■ rendah (&lt;0.30)</span>.
            </p>
        </div>

        <!-- Convergence Line Chart -->
        <div class="chart-card">
            <h3 class="chart-title">Grafik Konvergensi GA (Fitness Convergence Curve)</h3>
            <p class="chart-subtitle">
                Menampilkan evolusi fitness terbaik (global best) dan rata-rata populasi per generasi.
                Kurva yang semakin menyatu menandakan konvergensi algoritma.
            </p>
            <div class="chart-container">
                <canvas id="convergenceChart"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background: #60a5fa;"></div>
                    <span>Fitness Terbaik (Global Best)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: rgba(251,191,36,0.7); border: 1px dashed #fbbf24;"></div>
                    <span>Rata-rata Populasi</span>
                </div>
            </div>
        </div>

        <script>
        (function() {
            const ctx = document.getElementById('convergenceChart').getContext('2d');
            const historyData    = <?= json_encode(array_values($hasilGA['history'])) ?>;
            const historyAvgData = <?= json_encode(array_values($hasilGA['historyAvg'])) ?>;
            const labels         = <?= json_encode(array_keys($hasilGA['history'])) ?>;

            // Premium blue gradient for best fitness fill
            const gradientBest = ctx.createLinearGradient(0, 0, 0, 320);
            gradientBest.addColorStop(0, 'rgba(59, 130, 246, 0.22)');
            gradientBest.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            // Subtle yellow for avg fill
            const gradientAvg = ctx.createLinearGradient(0, 0, 0, 320);
            gradientAvg.addColorStop(0, 'rgba(251, 191, 36, 0.10)');
            gradientAvg.addColorStop(1, 'rgba(251, 191, 36, 0.0)');

            // Compute Y-axis bounds so chart is NOT flat
            const allVals = historyData.concat(historyAvgData);
            const dataMin = Math.min(...allVals);
            const dataMax = Math.max(...allVals);
            const padding = (dataMax - dataMin) * 0.15 || 0.05;
            const yMin = Math.max(0, parseFloat((dataMin - padding).toFixed(4)));
            const yMax = parseFloat((dataMax + padding).toFixed(4));

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Fitness Terbaik',
                            data: historyData,
                            borderColor: '#60a5fa',
                            backgroundColor: gradientBest,
                            borderWidth: 2.5,
                            fill: true,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            tension: 0.35,
                            order: 1
                        },
                        {
                            label: 'Rata-rata Populasi',
                            data: historyAvgData,
                            borderColor: 'rgba(251, 191, 36, 0.75)',
                            backgroundColor: gradientAvg,
                            borderWidth: 1.5,
                            borderDash: [5, 4],
                            fill: true,
                            pointBackgroundColor: '#fbbf24',
                            pointBorderColor: '#fff',
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.35,
                            order: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeInOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(7,10,19,0.92)',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            titleColor: '#94a3b8',
                            bodyColor: '#f8fafc',
                            padding: 12,
                            callbacks: {
                                title: function(items) {
                                    return 'Generasi ke-' + items[0].label;
                                },
                                label: function(item) {
                                    return ' ' + item.dataset.label + ': ' + parseFloat(item.raw).toFixed(4);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Generasi ke-', color: '#94a3b8', font: { size: 11 } },
                            grid: { color: 'rgba(255, 255, 255, 0.04)' },
                            ticks: {
                                color: '#64748b',
                                maxTicksLimit: 20,
                                font: { size: 10 }
                            }
                        },
                        y: {
                            min: yMin,
                            max: yMax,
                            title: { display: true, text: 'Nilai Fitness', color: '#94a3b8', font: { size: 11 } },
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: {
                                color: '#64748b',
                                font: { size: 10 },
                                callback: function(val) { return val.toFixed(3); }
                            }
                        }
                    }
                }
            });
        })();
        </script>

    <?php endif; ?>
</div>
</body>
</html>