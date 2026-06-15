<?php
// php-rlsm/algorithms.php

require_once __DIR__ . '/db.php';

function load_and_preprocess_data() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT * FROM lokasi");
        $data = $stmt->fetchAll();
        
        foreach ($data as &$row) {
            $row['cost'] = (int)$row['biaya_pembangunan'];
            $row['value'] = (int)(($row['kepadatan_penduduk'] * 0.6) + ($row['daya_beli'] * 0.4));
            $row['latitude'] = (float)$row['latitude'];
            $row['longitude'] = (float)$row['longitude'];
        }
        unset($row); // break references
        return $data;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

function cekJarakAman($lokasiBaru, $daftarTerpilih, $batasJarak) {
    foreach ($daftarTerpilih as $loc) {
        $jarak = sqrt(
            pow($lokasiBaru['latitude'] - $loc['latitude'], 2) + 
            pow($lokasiBaru['longitude'] - $loc['longitude'], 2)
        );
        $jarakKm = $jarak * 111;
        if ($jarakKm < $batasJarak) {
            return false;
        }
    }
    return true;
}

function jalankanGreedy($daftarLokasi, $maxBudget, $batasJarak) {
    foreach ($daftarLokasi as &$loc) {
        $loc['rasio'] = $loc['cost'] > 0 ? $loc['value'] / $loc['cost'] : 0;
    }
    unset($loc);
    
    // Duplikasi agar pengurutan tidak mengacaukan array asli di scope pemanggil jika dibutuhkan
    $lokasiSorted = $daftarLokasi;
    
    // Menggunakan usort yang stabil di PHP 8+
    usort($lokasiSorted, function($a, $b) {
        if ($a['rasio'] == $b['rasio']) {
            return 0;
        }
        return ($a['rasio'] > $b['rasio']) ? -1 : 1;
    });
    
    $lokasiTerpilih = [];
    $totalCost = 0;
    $totalValue = 0;
    
    foreach ($lokasiSorted as $loc) {
        if ($totalCost + $loc['cost'] <= $maxBudget) {
            if (cekJarakAman($loc, $lokasiTerpilih, $batasJarak)) {
                $lokasiTerpilih[] = $loc;
                $totalCost += $loc['cost'];
                $totalValue += $loc['value'];
            }
        }
    }
    
    return [
        'metode' => 'Greedy',
        'lokasiTerpilih' => $lokasiTerpilih,
        'totalCost' => $totalCost,
        'totalValue' => $totalValue
    ];
}

function jalankanDp($daftarLokasi, $maxBudget) {
    $n = count($daftarLokasi);
    
    // Inisialisasi DP table dengan 0
    $dpTable = [];
    for ($i = 0; $i <= $n; $i++) {
        $dpTable[$i] = array_fill(0, $maxBudget + 1, 0);
    }
    
    for ($i = 1; $i <= $n; $i++) {
        $costSekarang = $daftarLokasi[$i - 1]['cost'];
        $valueSekarang = $daftarLokasi[$i - 1]['value'];
        for ($w = 0; $w <= $maxBudget; $w++) {
            if ($costSekarang <= $w) {
                $dpTable[$i][$w] = max(
                    $dpTable[$i - 1][$w],
                    $dpTable[$i - 1][$w - $costSekarang] + $valueSekarang
                );
            } else {
                $dpTable[$i][$w] = $dpTable[$i - 1][$w];
            }
        }
    }
    
    $hasilValue = $dpTable[$n][$maxBudget];
    $w = $maxBudget;
    $lokasiTerpilih = [];
    $totalCost = 0;
    
    for ($i = $n; $i > 0; $i--) {
        if ($hasilValue <= 0) {
            break;
        }
        if ($hasilValue == $dpTable[$i - 1][$w]) {
            continue;
        } else {
            $itemTerpilih = $daftarLokasi[$i - 1];
            $lokasiTerpilih[] = $itemTerpilih;
            $hasilValue -= $itemTerpilih['value'];
            $w -= $itemTerpilih['cost'];
            $totalCost += $itemTerpilih['cost'];
        }
    }
    
    return [
        'metode' => 'Dynamic Programming',
        'lokasiTerpilih' => $lokasiTerpilih,
        'totalCost' => $totalCost,
        'totalValue' => $dpTable[$n][$maxBudget]
    ];
}
