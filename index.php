<?php
// php-rlsm/index.php
require_once __DIR__ . '/algorithms.php';

$hasilGreedy = null;
$hasilDp = null;
$waktuGreedy = 0;
$waktuDp = 0;
$budget = '';
$jarak = '';
$dataPoints = [];
$dbEmpty = false;
$dbError = null;

try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM lokasi");
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $dbEmpty = true;
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dbEmpty && !$dbError) {
    $budget = isset($_POST['budget']) ? (int)$_POST['budget'] : 0;
    $jarak = isset($_POST['jarak']) ? (float)str_replace(',', '.', $_POST['jarak']) : 0.0;
    
    $daftarLokasi = load_and_preprocess_data();
    
    if (count($daftarLokasi) > 0) {
        // Eksekusi Greedy
        $startGreedy = hrtime(true);
        $hasilGreedy = jalankanGreedy($daftarLokasi, $budget, $jarak);
        $waktuGreedy = (hrtime(true) - $startGreedy) / 1e9; // Konversi ke detik
        
        // Eksekusi DP
        $startDp = hrtime(true);
        $hasilDp = jalankanDp($daftarLokasi, $budget);
        $waktuDp = (hrtime(true) - $startDp) / 1e9; // Konversi ke detik
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penentuan Lokasi Supermarket</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #047857;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border-color: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s ease;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .btn-manage {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            box-shadow: var(--shadow-md);
        }

        .btn-manage:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Form Card */
        .card-form {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 30px;
            margin-bottom: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 600;
            font-size: 0.95rem;
            color: #334155;
        }

        input[type="number"] {
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background-color: #f8fafc;
            width: 100%;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn-calculate {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 16px;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            box-shadow: var(--shadow-md);
        }

        .btn-calculate:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Alert styling */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 30px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }

        .alert-info a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: underline;
        }

        .alert-success {
            background-color: var(--success-bg);
            border: 1px solid var(--success-border);
            color: var(--success-text);
        }

        /* Results section */
        .results-header {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 12px;
            color: #1e293b;
        }

        .grid-results {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 900px) {
            .grid-results {
                grid-template-columns: 1fr;
            }
        }

        .card-result {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-md);
        }

        .card-result h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .method-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .badge-greedy {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .badge-dp {
            background-color: #dcfce7;
            color: #15803d;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            background-color: #f8fafc;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .meta-info strong {
            color: var(--text-main);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th {
            background-color: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Chart section */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-md);
            height: 320px;
            position: relative;
        }

        .chart-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
            color: #475569;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>Optimasi Lokasi Supermarket</h1>
            <p style="color: var(--text-muted); margin-top: 4px;">Tentukan koordinat cabang baru dengan alokasi budget efisien.</p>
        </div>
        <a href="kelola_lokasi.php" class="btn-manage">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            Kelola Data Lokasi
        </a>
    </header>

    <?php if ($dbError): ?>
        <div class="alert alert-info" style="background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c;">
            <strong>Koneksi Database Gagal!</strong><br>
            Detail Error: <?= htmlspecialchars($dbError) ?><br><br>
            Silakan jalankan database setup dengan membuka <a href="db_setup.php" style="color: #b91c1c; text-decoration: underline;">db_setup.php</a>.
        </div>
    <?php elseif ($dbEmpty): ?>
        <div class="alert alert-info">
            <strong>Database Kosong!</strong><br>
            Data lokasi belum tersedia di database. Silakan lakukan inisialisasi awal database dengan mengklik tombol di bawah ini:
            <div style="margin-top: 12px;">
                <a href="db_setup.php" class="btn-manage" style="padding: 8px 16px; font-size: 0.85rem; background-color: var(--success);">Inisialisasi Database</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-form">
        <form method="POST" action="index.php">
            <div class="form-grid">
                <div class="form-group">
                    <label for="budget">Budget Maksimal (Juta Rp):</label>
                    <input type="number" id="budget" name="budget" required placeholder="Contoh: 1000" value="<?= htmlspecialchars($budget) ?>">
                </div>
                <div class="form-group">
                    <label for="jarak">Jarak Minimum Antar Lokasi (KM):</label>
                    <input type="number" step="0.1" id="jarak" name="jarak" required placeholder="Contoh: 2.5" value="<?= htmlspecialchars($jarak) ?>">
                </div>
            </div>
            <button type="submit" class="btn-calculate" <?= ($dbEmpty || $dbError) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>
                Hitung Optimasi Rekomendasi
            </button>
        </form>
    </div>

    <?php if ($hasilGreedy !== null && $hasilDp !== null): ?>
        <h2 class="results-header">Hasil Analisis Optimasi</h2>

        <!-- Kesimpulan Card -->
        <div class="alert alert-success">
            <h3 style="margin-top: 0; font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Kesimpulan Komparasi Rekomendasi
            </h3>
            <?php if ($hasilDp['totalValue'] > $hasilGreedy['totalValue']): ?>
                <p>Metode <strong>Dynamic Programming (DP)</strong> memberikan hasil yang lebih baik karena menghasilkan Total Value yang lebih besar (<strong><?= $hasilDp['totalValue'] ?></strong>) dibandingkan Greedy (<strong><?= $hasilGreedy['totalValue'] ?></strong>).</p>
            <?php elseif ($hasilGreedy['totalValue'] > $hasilDp['totalValue']): ?>
                <p>Metode <strong>Greedy</strong> memberikan hasil yang lebih baik dengan Total Value yang lebih besar (<strong><?= $hasilGreedy['totalValue'] ?></strong>) dibandingkan DP (<strong><?= $hasilDp['totalValue'] ?></strong>).</p>
            <?php else: ?>
                <p>Kedua metode menghasilkan Total Value yang sama (<strong><?= $hasilGreedy['totalValue'] ?></strong>). Namun, jika ditinjau dari waktu komputasi:</p>
                <p style="margin-top: 6px; font-weight: 500;">
                    <?php if ($waktuGreedy < $waktuDp): ?>
                        Metode <strong>Greedy</strong> lebih disarankan karena mengeksekusi algoritma lebih cepat (<strong><?= number_format($waktuGreedy, 8) ?> detik</strong>) dibandingkan DP (<strong><?= number_format($waktuDp, 8) ?> detik</strong>).
                    <?php elseif ($waktuDp < $waktuGreedy): ?>
                        Metode <strong>Dynamic Programming (DP)</strong> lebih disarankan karena mengeksekusi algoritma lebih cepat (<strong><?= number_format($waktuDp, 8) ?> detik</strong>) dibandingkan Greedy (<strong><?= number_format($waktuGreedy, 8) ?> detik</strong>).
                    <?php else: ?>
                        Keduanya memiliki waktu eksekusi yang identik.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="grid-results">
            <!-- Greedy Card -->
            <div class="card-result">
                <h3>
                    1. Pendekatan Greedy 
                    <span class="method-badge badge-greedy">Greedy</span>
                </h3>
                <div class="meta-info">
                    <div>Value: <strong><?= $hasilGreedy['totalValue'] ?></strong></div>
                    <div>Cost: <strong>Rp <?= number_format($hasilGreedy['totalCost'], 0, ',', '.') ?> Jt</strong></div>
                    <div>Waktu: <strong><?= number_format($waktuGreedy, 8) ?> s</strong></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Nama Daerah</th>
                            <th>Cost</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hasilGreedy['lokasiTerpilih']) > 0): ?>
                            <?php $no = 1; foreach ($hasilGreedy['lokasiTerpilih'] as $loc): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($loc['nama_daerah']) ?></strong></td>
                                    <td><?= $loc['cost'] ?></td>
                                    <td><?= $loc['value'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Tidak ada lokasi terpilih (budget tidak mencukupi atau konflik jarak).</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- DP Card -->
            <div class="card-result">
                <h3>
                    2. Pendekatan DP
                    <span class="method-badge badge-dp">Knapsack 0-1</span>
                </h3>
                <div class="meta-info">
                    <div>Value: <strong><?= $hasilDp['totalValue'] ?></strong></div>
                    <div>Cost: <strong>Rp <?= number_format($hasilDp['totalCost'], 0, ',', '.') ?> Jt</strong></div>
                    <div>Waktu: <strong><?= number_format($waktuDp, 8) ?> s</strong></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Nama Daerah</th>
                            <th>Cost</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hasilDp['lokasiTerpilih']) > 0): ?>
                            <?php $no = 1; foreach ($hasilDp['lokasiTerpilih'] as $loc): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($loc['nama_daerah']) ?></strong></td>
                                    <td><?= $loc['cost'] ?></td>
                                    <td><?= $loc['value'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Tidak ada lokasi terpilih (budget tidak mencukupi).</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Section -->
        <h2 class="results-header" style="margin-top: 40px;">Visualisasi Komparasi Performa</h2>
        <div class="chart-grid">
            <div class="chart-card">
                <h4>Perbandingan Total Value (Semakin Besar Semakin Baik)</h4>
                <canvas id="valueChart"></canvas>
            </div>
            <div class="chart-card">
                <h4>Perbandingan Waktu Eksekusi (Detik - Semakin Kecil Semakin Baik)</h4>
                <canvas id="timeChart"></canvas>
            </div>
        </div>

        <script>
            // Value comparison chart
            const ctxValue = document.getElementById('valueChart').getContext('2d');
            new Chart(ctxValue, {
                type: 'bar',
                data: {
                    labels: ['Greedy', 'Dynamic Programming'],
                    datasets: [{
                        label: 'Total Value',
                        data: [<?= $hasilGreedy['totalValue'] ?>, <?= $hasilDp['totalValue'] ?>],
                        backgroundColor: ['#3b82f6', '#10b981'],
                        borderColor: ['#2563eb', '#059669'],
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // Time execution comparison chart
            const ctxTime = document.getElementById('timeChart').getContext('2d');
            new Chart(ctxTime, {
                type: 'bar',
                data: {
                    labels: ['Greedy', 'Dynamic Programming'],
                    datasets: [{
                        label: 'Waktu Eksekusi (Detik)',
                        data: [<?= sprintf('%.10f', $waktuGreedy) ?>, <?= sprintf('%.10f', $waktuDp) ?>],
                        backgroundColor: ['#ef4444', '#f59e0b'],
                        borderColor: ['#dc2626', '#d97706'],
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>
</div>

</body>
</html>
