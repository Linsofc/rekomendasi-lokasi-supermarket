<?php
// php-rlsm/process.php
require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = get_db_connection();
        
        if ($action === 'add') {
            $nama_daerah = $_POST['nama_daerah'];
            $latitude = (float)str_replace(',', '.', $_POST['latitude']);
            $longitude = (float)str_replace(',', '.', $_POST['longitude']);
            $biaya_pembangunan = (int)$_POST['biaya_pembangunan'];
            $kepadatan_penduduk = (int)$_POST['kepadatan_penduduk'];
            $daya_beli = (int)$_POST['daya_beli'];
            
            $sql = "INSERT INTO lokasi (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli) 
                    VALUES (:nama_daerah, :latitude, :longitude, :biaya_pembangunan, :kepadatan_penduduk, :daya_beli)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama_daerah' => $nama_daerah,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':biaya_pembangunan' => $biaya_pembangunan,
                ':kepadatan_penduduk' => $kepadatan_penduduk,
                ':daya_beli' => $daya_beli
            ]);
        }
        
        else if ($action === 'edit') {
            $id = (int)$_GET['id'];
            $nama_daerah = $_POST['nama_daerah'];
            $latitude = (float)str_replace(',', '.', $_POST['latitude']);
            $longitude = (float)str_replace(',', '.', $_POST['longitude']);
            $biaya_pembangunan = (int)$_POST['biaya_pembangunan'];
            $kepadatan_penduduk = (int)$_POST['kepadatan_penduduk'];
            $daya_beli = (int)$_POST['daya_beli'];
            
            $sql = "UPDATE lokasi SET 
                        nama_daerah = :nama_daerah, 
                        latitude = :latitude, 
                        longitude = :longitude, 
                        biaya_pembangunan = :biaya_pembangunan, 
                        kepadatan_penduduk = :kepadatan_penduduk, 
                        daya_beli = :daya_beli 
                    WHERE id_lokasi = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama_daerah' => $nama_daerah,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':biaya_pembangunan' => $biaya_pembangunan,
                ':kepadatan_penduduk' => $kepadatan_penduduk,
                ':daya_beli' => $daya_beli,
                ':id' => $id
            ]);
        }
        
        else if ($action === 'delete') {
            $id = (int)$_GET['id'];
            $sql = "DELETE FROM lokasi WHERE id_lokasi = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
        }
    } catch (Exception $e) {
        // Log error and optionally redirect with error state
        error_log("Error in process.php (action=$action): " . $e->getMessage());
    }
}

header("Location: kelola_lokasi.php");
exit;
