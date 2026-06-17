<?php
// php-rlsm/algorithms.php

require_once __DIR__ . '/db.php';

function load_raw_data() {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->query("SELECT * FROM lokasi");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

// Langkah Pra-pemrosesan: Min-Max Scaling sesuai Rumus Jurnal
function preprocess_and_normalize($daftarLokasi) {
    if (empty($daftarLokasi)) return [];

    $kriteria = ['populasi', 'pendapatan', 'aksesibilitas', 'jarak_pesaing', 'sewa_tanah', 'lalu_lintas'];
    $mins = [];
    $maxs = [];

    // Cari nilai min dan max untuk setiap kriteria
    foreach ($kriteria as $k) {
        $kolom = array_column($daftarLokasi, $k);
        $mins[$k] = min($kolom);
        $maxs[$k] = max($kolom);
    }

    foreach ($daftarLokasi as &$loc) {
        $normalized = [];
        foreach ($kriteria as $k) {
            $denom = ($maxs[$k] - $mins[$k]) == 0 ? 1 : ($maxs[$k] - $mins[$k]);
            
            if ($k === 'sewa_tanah') {
                // Kriteria Cost: (Xmax - Xij) / (Xmax - Xmin)
                $normalized[$k] = ($maxs[$k] - $loc[$k]) / $denom;
            } else {
                // Kriteria Benefit: (Xij - Xmin) / (Xmax - Xmin)
                $normalized[$k] = ($loc[$k] - $mins[$k]) / $denom;
            }
        }

        $loc['normalized'] = $normalized;
    }
    unset($loc);
    
    return $daftarLokasi;
}

// Perhitungan Nilai Fitness berdasarkan Rumus (1) di Jurnal
function hitungFitness($loc, $weights) {
    $n = $loc['normalized'];
    return ($weights['w1'] * $n['populasi']) +
           ($weights['w2'] * $n['pendapatan']) +
           ($weights['w3'] * $n['aksesibilitas']) +
           ($weights['w4'] * $n['jarak_pesaing']) +
           ($weights['w5'] * $n['sewa_tanah']) +
           ($weights['w6'] * $n['lalu_lintas']);
}

// Implementasi Genetic Algorithm (GA) untuk Pemilihan Lokasi Terbaik
function jalankanGA($daftarLokasi, $maxGenerasi = 100, $ukuranPopulasi = 30) {
    $n_lokasi = count($daftarLokasi);
    if ($n_lokasi == 0) return null;

    // Definisikan bobot seimbang jika tidak diatur (Total = 1)
    $weights = ['w1'=>0.1667, 'w2'=>0.1667, 'w3'=>0.1667, 'w4'=>0.1667, 'w5'=>0.1667, 'w6'=>0.1667];

    // Hitung jumlah bit yang diperlukan untuk merepresentasikan indeks (Chromosomes length)
    // B = ceil(log2(N))
    $chromeLength = (int)ceil(log(max(2, $n_lokasi), 2));

    // Helper: Decode biner array menjadi integer desimal modulo N
    $decodeIndex = function($chromosome) use ($n_lokasi) {
        $decimal = 0;
        foreach ($chromosome as $bit) {
            $decimal = ($decimal << 1) | $bit;
        }
        return $decimal % $n_lokasi;
    };

    // Helper: Hitung fitness untuk individu biner
    $evalIndividu = function($chromosome) use ($decodeIndex, $daftarLokasi, $weights) {
        $index = $decodeIndex($chromosome);
        return [
            'chromosome' => $chromosome,
            'indeks_lokasi' => $index,
            'fitness' => hitungFitness($daftarLokasi[$index], $weights)
        ];
    };

    // 1. Inisialisasi Populasi secara acak
    $populasi = [];
    for ($i = 0; $i < $ukuranPopulasi; $i++) {
        $chrome = [];
        for ($g = 0; $g < $chromeLength; $g++) {
            $chrome[] = rand(0, 1);
        }
        $populasi[$i] = $evalIndividu($chrome);
    }

    $historyFitness = [];
    $bestGlobal = null;

    // Loop Generasi (Evolusi)
    for ($gen = 1; $gen <= $maxGenerasi; $gen++) {
        // Cari individu terbaik dalam generasi saat ini untuk Elitism
        $bestGen = $populasi[0];
        foreach ($populasi as $ind) {
            if ($ind['fitness'] > $bestGen['fitness']) {
                $bestGen = $ind;
            }
        }

        // Update Best Global
        if ($bestGlobal === null || $bestGen['fitness'] > $bestGlobal['fitness']) {
            $bestGlobal = $bestGen;
        }

        $historyFitness[$gen] = $bestGlobal['fitness'];

        // Siapkan populasi baru
        $populasiBaru = [];

        // Terapkan Elitism: Salin 2 individu terbaik langsung ke generasi berikutnya
        usort($populasi, function($a, $b) {
            return $b['fitness'] <=> $a['fitness'];
        });
        $populasiBaru[] = $populasi[0];
        if ($ukuranPopulasi > 1) {
            $populasiBaru[] = $populasi[1];
        }

        // Generate sisa populasi baru melalui Seleksi, Crossover, dan Mutasi
        while (count($populasiBaru) < $ukuranPopulasi) {
            // A. SELEKSI: Tournament Selection (ukuran = 3)
            $pilihParent = function() use ($populasi, $ukuranPopulasi) {
                $best = $populasi[rand(0, $ukuranPopulasi - 1)];
                for ($k = 0; $k < 2; $k++) {
                    $opponent = $populasi[rand(0, $ukuranPopulasi - 1)];
                    if ($opponent['fitness'] > $best['fitness']) {
                        $best = $opponent;
                    }
                }
                return $best['chromosome'];
            };

            $parent1 = $pilihParent();
            $parent2 = $pilihParent();

            // B. CROSSOVER: Single-Point Crossover (Probabilitas = 0.8)
            $child1 = $parent1;
            $child2 = $parent2;
            if (rand(0, 100) < 80 && $chromeLength > 1) {
                $crossPoint = rand(1, $chromeLength - 1);
                for ($g = $crossPoint; $g < $chromeLength; $g++) {
                    $child1[$g] = $parent2[$g];
                    $child2[$g] = $parent1[$g];
                }
            }

            // C. MUTASI: Bit-Flip Mutation (Probabilitas per bit = 0.1)
            $mutate = function($chrome) use ($chromeLength) {
                for ($g = 0; $g < $chromeLength; $g++) {
                    if (rand(0, 100) < 10) {
                        $chrome[$g] = $chrome[$g] === 1 ? 0 : 1;
                    }
                }
                return $chrome;
            };

            $child1 = $mutate($child1);
            $child2 = $mutate($child2);

            $populasiBaru[] = $evalIndividu($child1);
            if (count($populasiBaru) < $ukuranPopulasi) {
                $populasiBaru[] = $evalIndividu($child2);
            }
        }

        $populasi = $populasiBaru;
    }

    return [
        'lokasiTerbaik' => $daftarLokasi[$bestGlobal['indeks_lokasi']],
        'fitnessTerbaik' => $bestGlobal['fitness'],
        'history' => $historyFitness
    ];
}