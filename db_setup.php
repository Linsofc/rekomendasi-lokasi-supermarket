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
        echo "Tabel masih kosong, mulai mengimpor data dari CSV...<br>";
        $csvFile = __DIR__ . '/data_lokasi.csv';
        
        if (file_exists($csvFile)) {
            if (($handle = fopen($csvFile, "r")) !== FALSE) {
                // Baca header
                $headers = fgetcsv($handle, 1000, ",");
                
                $insertQuery = "
                INSERT INTO lokasi (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli)
                VALUES (:nama_daerah, :latitude, :longitude, :biaya_pembangunan, :kepadatan_penduduk, :daya_beli)
                ";
                $stmtInsert = $pdo->prepare($insertQuery);
                
                $rowCount = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Mapping data:
                    // id_lokasi, nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli
                    // data[0] = id_lokasi, data[1] = nama_daerah, data[2] = latitude, data[3] = longitude,
                    // data[4] = biaya_pembangunan, data[5] = kepadatan_penduduk, data[6] = daya_beli
                    
                    $stmtInsert->execute([
                        ':nama_daerah' => $data[1],
                        ':latitude' => (float)$data[2],
                        ':longitude' => (float)$data[3],
                        ':biaya_pembangunan' => (int)$data[4],
                        ':kepadatan_penduduk' => (int)$data[5],
                        ':daya_beli' => (int)$data[6]
                    ]);
                    $rowCount++;
                }
                fclose($handle);
                echo "Berhasil mengimpor $rowCount data dari CSV.<br>";
            } else {
                echo "Gagal membuka file CSV!<br>";
            }
        } else {
            echo "File CSV tidak ditemukan di $csvFile!<br>";
        }
    } else {
        echo "Tabel 'lokasi' sudah berisi data. Lewati proses impor.<br>";
    }
    
} catch (PDOException $e) {
    echo "Error saat konfigurasi database: " . $e->getMessage() . "<br>";
}
