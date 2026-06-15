<?php
// php-rlsm/kelola_lokasi.php
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT * FROM lokasi");
    $data = $stmt->fetchAll();
} catch (Exception $e) {
    // If the database doesn't exist, we can offer to set it up
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Lokasi | Optimasi Supermarket</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #10b981;
            --success-hover: #059669;
            --warning: #f59e0b;
            --warning-hover: #d97706;
            --danger: #ef4444;
            --danger-hover: #dc2626;
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
            max-width: 1100px;
            margin: 0 auto;
        }

        .header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateX(-4px);
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--success);
            color: white;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        .btn-add:hover {
            background-color: var(--success-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 24px;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
            color: #334155;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #fef3c7;
            color: #d97706;
        }

        .btn-edit:hover {
            background-color: var(--warning);
            color: white;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background-color: var(--danger);
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: var(--card-bg);
            margin: 6% auto;
            padding: 30px;
            border: 1px solid var(--border-color);
            width: 90%;
            max-width: 550px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transform: scale(0.9);
            opacity: 0;
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes modalIn {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .close {
            color: var(--text-muted);
            float: right;
            font-size: 28px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: var(--text-main);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-main);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            color: #475569;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            background-color: #f8fafc;
        }

        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }

        .alert-error a {
            color: #b91c1c;
            font-weight: 600;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <a href="index.php" class="btn-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Beranda
        </a>
        <button class="btn-add" onclick="openAddModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Tambah Lokasi Baru
        </button>
    </div>

    <h1>Kelola Data Lokasi</h1>
    <p class="subtitle">Kelola daftar lokasi potensial beserta rincian parameter pendukungnya.</p>

    <?php if (isset($dbError)): ?>
        <div class="alert-error">
            <strong>Koneksi Database Gagal!</strong><br>
            Detail error: <?= htmlspecialchars($dbError) ?><br><br>
            Silakan jalankan setup database terlebih dahulu dengan membuka <a href="db_setup.php" target="_blank">db_setup.php</a>.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Daerah</th>
                        <th>Koordinat (Lat, Lng)</th>
                        <th>Biaya Pembangunan</th>
                        <th>Kepadatan Penduduk</th>
                        <th>Daya Beli</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($data) && count($data) > 0): ?>
                        <?php $no = 1; foreach ($data as $item): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($item['nama_daerah']) ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: #f1f5f9; color: #475569;">
                                        <?= (float)$item['latitude'] ?>, <?= (float)$item['longitude'] ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($item['biaya_pembangunan'], 0, ',', '.') ?> Juta</td>
                                <td><?= number_format($item['kepadatan_penduduk'], 0, ',', '.') ?> jiwa</td>
                                <td><?= number_format($item['daya_beli'], 0, ',', '.') ?></td>
                                <td>
                                    <div class="action-cell">
                                        <button class="btn-action btn-edit" title="Edit Data" onclick='openEditModal(<?= json_encode($item) ?>)'>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4Z"></path></svg>
                                        </button>
                                        <form action="process.php?action=delete&id=<?= $item['id_lokasi'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data lokasi <?= htmlspecialchars($item['nama_daerah']) ?>?');">
                                            <button type="submit" class="btn-action btn-delete" title="Hapus Data">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 24px;">
                                Belum ada data lokasi. Silakan klik tombol 'Tambah Lokasi Baru' untuk mengisi data.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div id="formModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle" class="modal-title">Tambah Lokasi Baru</h2>
        
        <form id="lokasiForm" method="POST" action="process.php?action=add">
            <div class="form-group">
                <label for="nama_daerah">Nama Daerah:</label>
                <input type="text" id="nama_daerah" name="nama_daerah" required placeholder="Contoh: Kawasan Kemang">
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="latitude">Latitude:</label>
                    <input type="number" step="any" id="latitude" name="latitude" required placeholder="Contoh: -6.214">
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude:</label>
                    <input type="number" step="any" id="longitude" name="longitude" required placeholder="Contoh: 106.845">
                </div>
            </div>
            
            <div class="form-group">
                <label for="biaya_pembangunan">Biaya Pembangunan (Juta Rp):</label>
                <input type="number" id="biaya_pembangunan" name="biaya_pembangunan" required placeholder="Contoh: 500">
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="kepadatan_penduduk">Kepadatan Penduduk (Jiwa):</label>
                    <input type="number" id="kepadatan_penduduk" name="kepadatan_penduduk" required placeholder="Contoh: 800">
                </div>
                <div class="form-group">
                    <label for="daya_beli">Daya Beli (Index/Skor):</label>
                    <input type="number" id="daya_beli" name="daya_beli" required placeholder="Contoh: 400">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Simpan Lokasi</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("formModal");
    const form = document.getElementById("lokasiForm");
    const title = document.getElementById("modalTitle");

    function openAddModal() {
        form.action = "process.php?action=add";
        title.innerText = "Tambah Lokasi Baru";
        form.reset();
        modal.style.display = "block";
    }

    function openEditModal(item) {
        form.action = "process.php?action=edit&id=" + item.id_lokasi;
        title.innerText = "Edit Lokasi: " + item.nama_daerah;
        
        document.getElementById("nama_daerah").value = item.nama_daerah;
        document.getElementById("latitude").value = item.latitude;
        document.getElementById("longitude").value = item.longitude;
        document.getElementById("biaya_pembangunan").value = item.biaya_pembangunan;
        document.getElementById("kepadatan_penduduk").value = item.kepadatan_penduduk;
        document.getElementById("daya_beli").value = item.daya_beli;
        
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>
