<?php
// php-rlsm/db_setup.php

$host = 'localhost';
$user = 'root';
$password = '';

try {
    // 1. Hubungkan ke MySQL server tanpa database terlebih dahulu
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 2. Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS db_lokasi");
    echo "Database 'db_lokasi' berhasil dibuat atau sudah ada.<br>";
    
    // 3. Hubungkan ke database db_lokasi
    $pdo->exec("USE db_lokasi");
    
    // 4. Buat tabel lokasi
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS lokasi (
        id_lokasi INT AUTO_INCREMENT PRIMARY KEY,
        nama_daerah VARCHAR(255) NOT NULL,
        latitude DECIMAL(10, 6) NOT NULL,
        longitude DECIMAL(10, 6) NOT NULL,
        biaya_pembangunan INT NOT NULL,
        kepadatan_penduduk INT NOT NULL,
        daya_beli INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createTableSql);
    echo "Tabel 'lokasi' berhasil dibuat atau sudah ada.<br>";
    
    // 5. Cek apakah tabel kosong
    $stmt = $pdo->query("SELECT COUNT(*) FROM lokasi");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Tabel masih kosong, mulai mengimpor data awal langsung ke database...<br>";
        
        $insertQuery = "
        INSERT INTO lokasi (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli)
        VALUES 
        ('Kawasan Pusat Kota', -7.250445, 112.768845, 500, 800, 400),
        ('Kawasan Pinggiran', -7.275614, 112.791567, 300, 400, 250),
        ('Kawasan Industri', -7.319562, 112.738812, 400, 600, 300),
        ('Kawasan Residensial', -7.289166, 112.675545, 200, 500, 350),
        ('Kawasan Bisnis', -7.262536, 112.742512, 600, 900, 500)
        ";
        
        $pdo->exec($insertQuery);
        echo "Data awal berhasil diimpor langsung ke database.<br>";
    } else {
        echo "Tabel 'lokasi' sudah berisi data. Lewati proses impor.<br>";
    }
    
} catch (PDOException $e) {
    echo "Error saat konfigurasi database: " . $e->getMessage() . "<br>";
}
