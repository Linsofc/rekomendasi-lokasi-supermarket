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

/**
 * Langkah Pra-pemrosesan: RANK-BASED NORMALIZATION
 *
 * Metode ini menggantikan Min-Max Scaling yang sensitif terhadap outlier.
 * Cara kerja:
 *   1. Setiap kriteria diurutkan dari terbaik ke terburuk → diberi peringkat (rank).
 *   2. Peringkat dikonversi ke skor 0–1: skor = (N - rank) / (N - 1)
 *      - Rank 1 (terbaik) → skor 1.0
 *      - Rank N (terburuk) → skor 0.0
 *   3. Nilai seri (tie) mendapat rata-rata peringkat (average rank).
 *
 * Keunggulan vs Min-Max:
 *   - Tidak terpengaruh outlier/nilai ekstrem.
 *   - Adil secara dinamis: berapapun nilai inputan, penilaian tetap proporsional.
 *   - Menambah/mengurangi lokasi hanya mengubah posisi relatif, bukan merusak skor semua.
 */
function preprocess_and_normalize($daftarLokasi) {
    if (empty($daftarLokasi)) return [];

    $n        = count($daftarLokasi);
    $kriteria = ['populasi', 'pendapatan', 'aksesibilitas', 'jarak_pesaing', 'sewa_tanah', 'lalu_lintas'];
    // Kriteria cost: nilai lebih rendah = lebih baik (rank 1)
    $kriteriaKost = ['sewa_tanah'];

    foreach ($kriteria as $kriterion) {
        // Kumpulkan nilai tiap lokasi beserta indeksnya
        $entries = [];
        foreach ($daftarLokasi as $idx => $loc) {
            $entries[] = ['idx' => $idx, 'val' => (float)$loc[$kriterion]];
        }

        // Urutkan: benefit → descending (besar = rank 1)
        //          cost   → ascending  (kecil = rank 1)
        if (in_array($kriterion, $kriteriaKost)) {
            usort($entries, fn($a, $b) => $a['val'] <=> $b['val']); // kecil duluan
        } else {
            usort($entries, fn($a, $b) => $b['val'] <=> $a['val']); // besar duluan
        }

        // Hitung peringkat dengan penanganan nilai seri (average rank)
        $rankMap = [];
        $i = 0;
        while ($i < $n) {
            $j = $i;
            // Temukan semua entri yang nilainya sama (tie)
            while ($j < $n - 1 && $entries[$j]['val'] == $entries[$j + 1]['val']) {
                $j++;
            }
            // Rata-rata peringkat untuk nilai seri (1-indexed)
            $avgRank = (($i + 1) + ($j + 1)) / 2.0;
            for ($t = $i; $t <= $j; $t++) {
                $rankMap[$entries[$t]['idx']] = $avgRank;
            }
            $i = $j + 1;
        }

        // Konversi rank → normalized score: (N - rank) / (N - 1)
        // Jika hanya 1 lokasi → skor = 1.0
        foreach ($daftarLokasi as $idx => &$loc) {
            $rank = $rankMap[$idx];
            $loc['normalized'][$kriterion] = ($n === 1) ? 1.0 : ($n - $rank) / ($n - 1);
        }
        unset($loc);
    }

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

    // Bobot seimbang — Total = 1
    $weights = ['w1'=>0.1667, 'w2'=>0.1667, 'w3'=>0.1667, 'w4'=>0.1667, 'w5'=>0.1667, 'w6'=>0.1667];

    // Representasi kromosom: integer langsung (indeks lokasi 0..N-1)
    // Menghindari bias distribusi dari encoding biner dengan modulo.
    $evalIndividu = function($indeks) use ($daftarLokasi, $weights) {
        return [
            'indeks_lokasi' => $indeks,
            'fitness'       => hitungFitness($daftarLokasi[$indeks], $weights)
        ];
    };

    // 1. Inisialisasi Populasi secara acak
    $populasi = [];
    for ($i = 0; $i < $ukuranPopulasi; $i++) {
        $populasi[$i] = $evalIndividu(rand(0, $n_lokasi - 1));
    }

    $historyFitness    = [];
    $historyAvgFitness = [];
    $bestGlobal        = null;

    // Loop Generasi (Evolusi)
    for ($gen = 1; $gen <= $maxGenerasi; $gen++) {

        // Cari individu terbaik dalam generasi ini
        $bestGen     = $populasi[0];
        $totalFitness = 0;
        foreach ($populasi as $ind) {
            if ($ind['fitness'] > $bestGen['fitness']) $bestGen = $ind;
            $totalFitness += $ind['fitness'];
        }
        $avgFitness = $totalFitness / $ukuranPopulasi;

        // Update Best Global
        if ($bestGlobal === null || $bestGen['fitness'] > $bestGlobal['fitness']) {
            $bestGlobal = $bestGen;
        }

        $historyFitness[$gen]    = round($bestGlobal['fitness'], 6);
        $historyAvgFitness[$gen] = round($avgFitness, 6);

        // Siapkan populasi baru
        $populasiBaru = [];

        // Elitism: 2 individu terbaik langsung lolos ke generasi berikutnya
        usort($populasi, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
        $populasiBaru[] = $populasi[0];
        if ($ukuranPopulasi > 1) $populasiBaru[] = $populasi[1];

        // Generate sisa populasi via Seleksi → Crossover → Mutasi
        while (count($populasiBaru) < $ukuranPopulasi) {

            // A. SELEKSI: Tournament Selection (k=3)
            $pilihParent = function() use ($populasi, $ukuranPopulasi) {
                $best = $populasi[rand(0, $ukuranPopulasi - 1)];
                for ($k = 0; $k < 2; $k++) {
                    $opp = $populasi[rand(0, $ukuranPopulasi - 1)];
                    if ($opp['fitness'] > $best['fitness']) $best = $opp;
                }
                return $best['indeks_lokasi'];
            };

            $p1 = $pilihParent();
            $p2 = $pilihParent();

            // B. CROSSOVER: Uniform crossover (Pc = 0.8)
            if (rand(0, 100) < 80) {
                $c1 = (rand(0, 1) === 0) ? $p1 : $p2;
                $c2 = (rand(0, 1) === 0) ? $p1 : $p2;
            } else {
                $c1 = $p1;
                $c2 = $p2;
            }

            // C. MUTASI: Random reset (Pm = 0.1)
            if (rand(0, 100) < 10) $c1 = rand(0, $n_lokasi - 1);
            if (rand(0, 100) < 10) $c2 = rand(0, $n_lokasi - 1);

            $populasiBaru[] = $evalIndividu($c1);
            if (count($populasiBaru) < $ukuranPopulasi) {
                $populasiBaru[] = $evalIndividu($c2);
            }
        }

        $populasi = $populasiBaru;
    }

    // Hitung skor & ranking SEMUA alternatif secara deterministik
    $semuaAlternatif = [];
    foreach ($daftarLokasi as $idx => $loc) {
        // Susun detail skor normalisasi per kriteria
        $n = $loc['normalized'];
        $semuaAlternatif[] = [
            'lokasi'        => $loc,
            'fitness'       => round(hitungFitness($loc, $weights), 6),
            'skor_kriteria' => [
                'populasi'       => round($n['populasi'],       4),
                'pendapatan'     => round($n['pendapatan'],     4),
                'aksesibilitas'  => round($n['aksesibilitas'],  4),
                'jarak_pesaing'  => round($n['jarak_pesaing'],  4),
                'sewa_tanah'     => round($n['sewa_tanah'],     4),
                'lalu_lintas'    => round($n['lalu_lintas'],    4),
            ],
            'ranking' => 0,
        ];
    }

    // Urutkan dari fitness tertinggi ke terendah & beri nomor ranking
    usort($semuaAlternatif, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
    foreach ($semuaAlternatif as $i => &$alt) {
        $alt['ranking'] = $i + 1;
    }
    unset($alt);

    return [
        'lokasiTerbaik'   => $daftarLokasi[$bestGlobal['indeks_lokasi']],
        'fitnessTerbaik'  => round($bestGlobal['fitness'], 6),
        'history'         => $historyFitness,
        'historyAvg'      => $historyAvgFitness,
        'semuaAlternatif' => $semuaAlternatif,
        'weights'         => $weights,
        'totalLokasi'     => $n_lokasi,
    ];
}