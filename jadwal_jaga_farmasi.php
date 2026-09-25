<?php
session_start();
include 'koneksi.php';

// Ambil info instansi
$query_instansi = "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$logo_src = "images/logo.png";
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        $logo_src = "data:image/png;base64," . base64_encode($row_instansi['logo']);
    }
}

// Param bulan dan tahun
$bulan_pilihan = isset($_GET['bulan']) ? sprintf('%02d', (int)$_GET['bulan']) : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Total hari dalam bulan
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, (int)$bulan_pilihan, $tahun_pilihan);

// Data Libur Nasional dari DB set_hari_libur
$hari_libur_db = [];
$q_libur = mysqli_query($koneksi, "SELECT tanggal, ktg FROM set_hari_libur WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$tahun_pilihan-$bulan_pilihan'");
if ($q_libur) {
    while ($r_lib = mysqli_fetch_assoc($q_libur)) {
        $hari_libur_db[$r_lib['tanggal']] = $r_lib['ktg'];
    }
}

// Daftar 19 Pegawai Farmasi beserta Rule Bawaannya
// Daftar 19 Pegawai Farmasi beserta Rule Bawaan & Kategori Tugas
$daftar_pegawai_config = [
    [
        'nik' => '198508162020122003',
        'tipe' => 'non_shift',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Hadir setiap hari kerja.'
    ],
    [
        'nik' => 'Farmasi1',
        'tipe' => 'non_shift',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Hadir setiap hari kerja.'
    ],
    [
        'nik' => '19950523',
        'tipe' => 'khusus_faisal',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Kamis & Jumat libur; Senin & Sabtu Pagi-Sore (PS); Selasa & Rabu Pagi (P)'
    ],
    [
        'nik' => '199602012022032019',
        'tipe' => 'khusus_febrina',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Kamis & Jumat Pagi-Sore (PS); Sabtu-Senin Libur; Selasa & Rabu Pagi (P)'
    ],
    [
        'nik' => '27071992',
        'tipe' => 'non_shift',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Hadir setiap hari kerja kecuali Minggu & Libur Nasional'
    ],
    [
        'nik' => 'Farmasi4',
        'tipe' => 'khusus_mario',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Senin, Jumat & Sabtu libur; Selasa, Rabu, Kamis Pagi-Sore (PS)'
    ],
    [
        'nik' => '27091982',
        'tipe' => 'non_shift',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Hadir setiap hari kerja.'
    ],
    [
        'nik' => '21091996',
        'tipe' => 'non_shift',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Hadir setiap hari kerja kecuali Minggu & Libur Nasional'
    ],
    [
        'nik' => '12091992',
        'tipe' => 'non_shift',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Hadir setiap hari kerja.'
    ],
    [
        'nik' => '199401122024212018',
        'tipe' => 'khusus_sasqia',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Jumat & Sabtu libur; Senin-Selasa Pagi-Sore (PS), Rabu-Kamis Pagi (P)'
    ],
    [
        'nik' => 'ronaldi',
        'tipe' => 'non_shift',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Hadir setiap hari kerja.'
    ],
    [
        'nik' => '26091986',
        'tipe' => 'non_shift',
        'kategori' => 'pelayanan',
        'rule_desc' => 'Pelayanan Pasien. Hadir setiap hari kerja kecuali Minggu & Libur Nasional'
    ],
    [
        'nik' => '198509172005011001',
        'tipe' => 'khusus_hasrun',
        'kategori' => 'non_pelayanan',
        'rule_desc' => 'Administrasi / Gudang (Non-Pelayanan Pasien). Senin-Kamis Pagi (P), Jumat Pagi-Sore (PS), Sabtu & Minggu Libur (L)'
    ],
    // 6 Pegawai Shift Malam Bergantian (Semua Bertugas Pelayanan Pasien)
    [
        'nik' => '05101992',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 0,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ],
    [
        'nik' => '11111997',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 1,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ],
    [
        'nik' => '13031999',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 2,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ],
    [
        'nik' => '16121993',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 3,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ],
    [
        'nik' => '28071999',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 4,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ],
    [
        'nik' => '16032003',
        'tipe' => 'shift_malam',
        'kategori' => 'pelayanan',
        'urutan_malam' => 5,
        'rule_desc' => 'Pelayanan Pasien. Shift Malam Bergantian & Jaga Pagi/Sore'
    ]
];

// Ambil detail data pegawai dari database
$data_pegawai_db = [];
foreach ($daftar_pegawai_config as $idx => $cfg) {
    $nik_clean = mysqli_real_escape_string($koneksi, $cfg['nik']);
    $nama = "Pegawai " . ($idx + 1);
    $jbtn = "Petugas Farmasi";
    $id_peg = 0;

    $q_peg = mysqli_query($koneksi, "SELECT id, nik, nama, jbtn FROM pegawai WHERE nik = '$nik_clean' OR nik LIKE '%$nik_clean%' OR nama LIKE '%$nik_clean%' LIMIT 1");
    if ($q_peg && $r = mysqli_fetch_assoc($q_peg)) {
        $nama = $r['nama'];
        $jbtn = !empty($r['jbtn']) && $r['jbtn'] != '-' ? $r['jbtn'] : "Petugas Farmasi";
        $id_peg = $r['id'];
    }

    $data_pegawai_db[] = array_merge($cfg, [
        'id' => $id_peg,
        'nama' => $nama,
        'jbtn' => $jbtn
    ]);
}

// Cek simpan data POST
$pesan_sukses = "";
$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_db') {
    $jadwal_post = isset($_POST['jadwal']) ? $_POST['jadwal'] : [];
    $berhasil = 0;

    foreach ($jadwal_post as $nik_p => $days) {
        $nik_clean = mysqli_real_escape_string($koneksi, $nik_p);
        $id_peg = 0;
        $q_id = mysqli_query($koneksi, "SELECT id FROM pegawai WHERE nik = '$nik_clean' LIMIT 1");
        if ($q_id && $r_id = mysqli_fetch_assoc($q_id)) {
            $id_peg = $r_id['id'];
        }

        if ($id_peg > 0) {
            $col_values = [];
            for ($d = 1; $d <= 31; $d++) {
                $val = isset($days[$d]) ? trim($days[$d]) : 'L';
                $db_val = '';
                if ($val === 'P') $db_val = 'Pagi';
                else if ($val === 'S') $db_val = 'Siang';
                else if ($val === 'M') $db_val = 'Malam';
                else if ($val === 'PS') $db_val = 'Pagi2';
                else $db_val = '';
                
                $col_values["h$d"] = $db_val;
            }

            $q_chk = mysqli_query($koneksi, "SELECT id FROM jadwal_pegawai WHERE id = '$id_peg' AND tahun = '$tahun_pilihan' AND bulan = '$bulan_pilihan'");
            if ($q_chk && mysqli_num_rows($q_chk) > 0) {
                $set_clause = [];
                foreach ($col_values as $col => $val_db) {
                    $set_clause[] = "$col = '$val_db'";
                }
                $sql_upd = "UPDATE jadwal_pegawai SET " . implode(", ", $set_clause) . " WHERE id = '$id_peg' AND tahun = '$tahun_pilihan' AND bulan = '$bulan_pilihan'";
                if (mysqli_query($koneksi, $sql_upd)) $berhasil++;
            } else {
                $cols = ["id", "tahun", "bulan"];
                $vals = ["'$id_peg'", "'$tahun_pilihan'", "'$bulan_pilihan'"];
                foreach ($col_values as $col => $val_db) {
                    $cols[] = $col;
                    $vals[] = "'$val_db'";
                }
                $sql_ins = "INSERT INTO jadwal_pegawai (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ")";
                if (mysqli_query($koneksi, $sql_ins)) $berhasil++;
            }
        }
    }
    $pesan_sukses = "Berhasil menyimpan data jadwal untuk $berhasil pegawai ke database!";
}

// Cek data tersimpan di DB
$jadwal_terimpan_db = [];
$q_saved = mysqli_query($koneksi, "SELECT * FROM jadwal_pegawai WHERE tahun = '$tahun_pilihan' AND bulan = '$bulan_pilihan'");
if ($q_saved) {
    while ($r_saved = mysqli_fetch_assoc($q_saved)) {
        $id_peg = $r_saved['id'];
        $jadwal_terimpan_db[$id_peg] = $r_saved;
    }
}

// Algoritma Generasi Jadwal Presisi
function generate_jadwal_sebulan_presisi($data_pegawai_db, $tahun, $bulan, $jumlah_hari, $hari_libur_db) {
    $matrix = [];
    $night_shift_niks = ['05101992', '11111997', '13031999', '16121993', '28071999', '16032003'];

    // Helper untuk hitung akumulasi jam kerja seorang pegawai secara total dalam sebulan
    $calc_total_hours = function(&$m_ref, $nik_search, $tot_days) {
        $tot = 0;
        for ($cd = 1; $cd <= $tot_days; $cd++) {
            $val = isset($m_ref[$nik_search][$cd]) ? $m_ref[$nik_search][$cd] : 'L';
            if ($val === 'P' || $val === 'S') $tot += 6;
            else if ($val === 'M' || $val === 'PS') $tot += 12;
        }
        return $tot;
    };

    // Step 1: Assign Rule Dasar Individu
    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
        $timestamp = strtotime($tgl_str);
        $hari_idx = date('w', $timestamp); // 0 = Minggu
        $is_minggu = ($hari_idx == 0);
        $is_libur = isset($hari_libur_db[$tgl_str]);
        $is_hari_libur_atau_minggu = ($is_minggu || $is_libur);

        foreach ($data_pegawai_db as $peg) {
            $nik = $peg['nik'];
            $tipe = $peg['tipe'];

            if ($tipe === 'non_shift') {
                $matrix[$nik][$d] = $is_hari_libur_atau_minggu ? 'L' : 'P';
            } else if ($tipe === 'khusus_faisal') {
                if ($hari_idx == 1 || $hari_idx == 6) $matrix[$nik][$d] = 'PS';
                else if ($hari_idx == 2 || $hari_idx == 3) $matrix[$nik][$d] = 'P';
                else $matrix[$nik][$d] = 'L';
            } else if ($tipe === 'khusus_febrina') {
                if ($hari_idx == 4 || $hari_idx == 5) $matrix[$nik][$d] = 'PS';
                else if ($hari_idx == 2 || $hari_idx == 3) $matrix[$nik][$d] = 'P';
                else $matrix[$nik][$d] = 'L';
            } else if ($tipe === 'khusus_mario') {
                if ($hari_idx == 2 || $hari_idx == 3 || $hari_idx == 4) $matrix[$nik][$d] = 'PS';
                else $matrix[$nik][$d] = 'L';
            } else if ($tipe === 'khusus_sasqia') {
                if ($hari_idx == 1 || $hari_idx == 2) $matrix[$nik][$d] = 'PS';
                else if ($hari_idx == 3 || $hari_idx == 4) $matrix[$nik][$d] = 'P';
                else $matrix[$nik][$d] = 'L';
            } else if ($tipe === 'khusus_hasrun') {
                if ($is_hari_libur_atau_minggu || $hari_idx == 6) $matrix[$nik][$d] = 'L';
                else if ($hari_idx == 5) $matrix[$nik][$d] = 'PS';
                else $matrix[$nik][$d] = 'P';
            } else if ($tipe === 'shift_malam') {
                $matrix[$nik][$d] = 'L'; // Default Libur awal
            }
        }

        // Rule Spesifik Pegawai 05101992:
        // Senin (1), Selasa (2), Rabu (3): WAJIB Pagi 'P' (kecuali libur nasional/minggu)
        // Sabtu (6): WAJIB Libur 'L'
        if ($hari_idx >= 1 && $hari_idx <= 3) {
            $matrix['05101992'][$d] = $is_hari_libur_atau_minggu ? 'L' : 'P';
        } else if ($hari_idx == 6) {
            $matrix['05101992'][$d] = 'L';
        }
    }

    // Step 2: Rotasi Jaga Malam (M) - 2 hari berturut-turut per pegawai
    // Pengalihan khusus untuk 05101992 pada Senin-Rabu & Sabtu
    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
        $hari_idx = date('w', strtotime($tgl_str));

        $cycle_idx = floor(($d - 1) / 2) % 6;
        $malam_nik = $night_shift_niks[$cycle_idx];

        if ($malam_nik === '05101992' && (($hari_idx >= 1 && $hari_idx <= 3) || $hari_idx == 6)) {
            $malam_nik = '11111997'; // Alihkan ke anggota tim shift malam berikutnya
        }

        $matrix[$malam_nik][$d] = 'M';
    }

    // Step 3: Rotasi Sore (S) - Diisi oleh 2 pegawai shift malam secara bergantian
    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
        $hari_idx = date('w', strtotime($tgl_str));
        $prev_d = ($d > 1) ? $d - 1 : 1;
        
        $sore_count = 0;
        foreach ($data_pegawai_db as $peg) {
            $v = $matrix[$peg['nik']][$d];
            if ($v === 'S' || $v === 'PS') $sore_count++;
        }
        $sore_needed = 2 - $sore_count;

        if ($sore_needed > 0) {
            $avail = [];
            foreach ($night_shift_niks as $n_nik) {
                if ($matrix[$n_nik][$d] !== 'M' && $matrix[$n_nik][$prev_d] !== 'M') {
                    // Batasan 05101992: Tidak bisa Sore pada Senin-Rabu & Sabtu
                    if ($n_nik === '05101992' && (($hari_idx >= 1 && $hari_idx <= 3) || $hari_idx == 6)) {
                        continue;
                    }
                    $avail[] = $n_nik;
                }
            }
            for ($k = 0; $k < $sore_needed && $k < count($avail); $k++) {
                $pick_idx = ($d + $k) % count($avail);
                $chosen = $avail[$pick_idx];
                $matrix[$chosen][$d] = 'S';
            }
        }
    }

    // Step 4: Rotasi Pagi (P) Hari Minggu / Libur (2 Orang dari Tim Shift Malam)
    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
        $hari_idx = date('w', strtotime($tgl_str));
        $is_minggu = ($hari_idx == 0);
        $is_libur = isset($hari_libur_db[$tgl_str]);
        $prev_d = ($d > 1) ? $d - 1 : 1;

        if ($is_minggu || $is_libur) {
            $avail = [];
            foreach ($night_shift_niks as $n_nik) {
                if ($matrix[$n_nik][$d] === 'L' && $matrix[$n_nik][$prev_d] !== 'M') {
                    $avail[] = $n_nik;
                }
            }
            for ($k = 0; $k < 2 && $k < count($avail); $k++) {
                $pick_idx = ($d + $k) % count($avail);
                $chosen = $avail[$pick_idx];
                $matrix[$chosen][$d] = 'P';
            }
        }
    }

    // Step 5: Penjaminan Minimal 7 Petugas Pelayanan Pasien di Shift Pagi Hari Kerja (Senin - Sabtu)
    for ($d = 1; $d <= $jumlah_hari; $d++) {
        $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
        $hari_idx = date('w', strtotime($tgl_str));
        $is_minggu = ($hari_idx == 0);
        $is_libur = isset($hari_libur_db[$tgl_str]);
        $prev_d = ($d > 1) ? $d - 1 : 1;

        if (!$is_minggu && !$is_libur) {
            $pelayanan_pagi_cnt = 0;
            foreach ($data_pegawai_db as $peg) {
                $kategori = isset($peg['kategori']) ? $peg['kategori'] : 'pelayanan';
                if ($kategori === 'pelayanan') {
                    $v = $matrix[$peg['nik']][$d];
                    if ($v === 'P' || $v === 'PS') $pelayanan_pagi_cnt++;
                }
            }

            if ($pelayanan_pagi_cnt < 7) {
                $butuh = 7 - $pelayanan_pagi_cnt;
                $avail = [];
                foreach ($night_shift_niks as $n_nik) {
                    if ($matrix[$n_nik][$d] === 'L' && $matrix[$n_nik][$prev_d] !== 'M') {
                        // Batasan 05101992: Sabtu wajib Libur 'L'
                        if ($n_nik === '05101992' && $hari_idx == 6) {
                            continue;
                        }
                        $avail[] = $n_nik;
                    }
                }
                usort($avail, function($a, $b) use (&$matrix, $jumlah_hari, $calc_total_hours) {
                    return $calc_total_hours($matrix, $a, $jumlah_hari) - $calc_total_hours($matrix, $b, $jumlah_hari);
                });

                for ($k = 0; $k < $butuh && $k < count($avail); $k++) {
                    $matrix[$avail[$k]][$d] = 'P';
                }
            }
        }
    }

    // Step 6: Top up penyesuaian jam kerja seimbang (156 jam ideal) untuk pegawai shift malam
    foreach ($night_shift_niks as $nik) {
        for ($d = 1; $d <= $jumlah_hari; $d++) {
            $h = $calc_total_hours($matrix, $nik, $jumlah_hari);
            if ($h >= 156) break;

            $tgl_str = sprintf('%s-%s-%02d', $tahun, $bulan, $d);
            $hari_idx = date('w', strtotime($tgl_str));
            $is_minggu = ($hari_idx == 0);
            $prev_d = ($d > 1) ? $d - 1 : 1;

            if (!$is_minggu && $matrix[$nik][$d] === 'L' && $matrix[$nik][$prev_d] !== 'M') {
                if ($nik === '05101992' && $hari_idx == 6) {
                    continue; // 05101992 Wajib Libur pada hari Sabtu
                }
                $matrix[$nik][$d] = 'P';
            }
        }
    }

    return $matrix;
}

// Generate jadwal otomatis presisi untuk bulan ini
$jadwal_otomatis_matrix = generate_jadwal_sebulan_presisi($data_pegawai_db, $tahun_pilihan, $bulan_pilihan, $jumlah_hari, $hari_libur_db);

$nama_bulan_id = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_hari_id = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Jaga Petugas Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --dark: #0f172a;
            --light-bg: #f8fafc;
            --border-color: #cbd5e1;
            --active-green: #92d050; /* Warna hijau khas Excel */
            --active-green-dark: #70ad47;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #1e293b;
            padding-bottom: 60px;
        }

        .header-app {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%);
            color: white;
            padding: 22px 32px;
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.3);
            margin-bottom: 24px;
        }

        .header-content {
            max-width: 1850px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            background: white;
            border-radius: 12px;
            padding: 5px;
            object-fit: contain;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .brand-text h1 {
            font-size: 1.35rem;
            font-weight: 800;
        }

        .brand-text p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .btn-nav {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: translateY(-2px);
        }

        .container-fluid {
            max-width: 1850px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card-control {
            background: white;
            border-radius: 16px;
            padding: 18px 24px;
            box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form-group-custom {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group-custom label {
            font-size: 0.88rem;
            font-weight: 700;
            color: #475569;
        }

        .select-custom {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            font-size: 0.88rem;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            background: #fff;
        }

        .toolbar-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-primary-custom { background: #0284c7; color: white; }
        .btn-primary-custom:hover { background: #0369a1; }

        .btn-success-custom { background: #059669; color: white; }
        .btn-success-custom:hover { background: #047857; }

        .btn-warning-custom { background: #d97706; color: white; }
        .btn-warning-custom:hover { background: #b45309; }

        .btn-secondary-custom { background: #64748b; color: white; }
        .btn-secondary-custom:hover { background: #475569; }

        .btn-dark-custom { background: #1e293b; color: white; }
        .btn-dark-custom:hover { background: #0f172a; }

        .alert-box {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        .info-kuota-bar {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #166534;
            font-size: 0.88rem;
            font-weight: 700;
        }

        /* TABEL EXCEL STYLE */
        .table-wrapper {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 2px solid #334155;
            overflow-x: auto;
            position: relative;
            max-height: 74vh;
        }

        .table-excel {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            user-select: none;
        }

        .table-excel th, .table-excel td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
            padding: 4px 2px;
            box-sizing: border-box;
        }

        /* Baris 1 Header (Nomor Tanggal & Rekap Header) */
        .table-excel thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 20;
            height: 32px;
            background-color: #f8fafc;
        }

        /* Baris 2 Header (Sub-kolom P, S, M & Rekap Sub-kolom) */
        .table-excel thead tr:nth-child(2) th {
            position: sticky;
            top: 32px;
            z-index: 19;
            height: 26px;
            background-color: #ffffff;
        }

        .th-date-group {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0284c7;
            border-bottom: 2px solid #000 !important;
            border-right: 2px solid #000 !important;
            background: #f8fafc;
        }

        .th-date-group.libur {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
        }

        .th-sub-col {
            font-size: 0.85rem;
            font-weight: 800;
            color: #000;
            min-width: 24px;
            width: 24px;
            background: #ffffff;
            border-bottom: 2px solid #000 !important;
        }

        .th-sub-col.border-right-thick {
            border-right: 2px solid #000 !important;
        }

        /* Sticky Left Columns */
        .sticky-col-1 {
            position: sticky;
            left: 0;
            background: white;
            z-index: 11;
            min-width: 38px;
            width: 38px;
            border-right: 1px solid #000 !important;
        }

        .sticky-col-2 {
            position: sticky;
            left: 38px;
            background: white;
            z-index: 11;
            min-width: 120px;
            text-align: left !important;
            padding-left: 6px !important;
            font-family: monospace;
            font-weight: 600;
            border-right: 1px solid #000 !important;
        }

        .sticky-col-3 {
            position: sticky;
            left: 158px;
            background: white;
            z-index: 11;
            min-width: 210px;
            text-align: left !important;
            padding-left: 6px !important;
            font-weight: 700;
            color: #0f172a;
            border-right: 1px solid #000 !important;
        }

        .sticky-col-4 {
            position: sticky;
            left: 368px;
            background: white;
            z-index: 11;
            min-width: 130px;
            text-align: left !important;
            padding-left: 6px !important;
            color: #475569;
            font-size: 0.78rem;
            border-right: 2px solid #000 !important;
        }

        /* Sticky Header Left Columns (No, NIK, Nama, Jabatan) - Span 2 Rows */
        .table-excel th.sticky-col-1,
        .table-excel th.sticky-col-2,
        .table-excel th.sticky-col-3,
        .table-excel th.sticky-col-4 {
            background: #f1f5f9;
            top: 0 !important;
            z-index: 30 !important;
        }

        /* Sub-Cell Shift P, S, M */
        .cell-shift {
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            width: 26px;
            height: 30px;
            transition: background-color 0.1s ease;
        }

        .cell-shift.border-right-thick {
            border-right: 2px solid #000 !important;
        }

        /* Warna Hijau Aktif Sesuai Gambar Referensi */
        .cell-shift.active {
            background-color: #92d050 !important; /* Hijau khas Excel */
            color: #000 !important;
        }

        .cell-shift:hover {
            opacity: 0.85;
            outline: 2px solid #0284c7;
        }

        .col-summary {
            font-weight: 800;
            background-color: #f8fafc;
            min-width: 38px;
            border-right: 1px solid #000 !important;
        }

        .row-daily-counter td {
            font-weight: 800;
            background: #f8fafc;
            font-size: 0.8rem;
            border-top: 2px solid #000 !important;
        }

        .count-status-ok { background: #dcfce7 !important; color: #15803d; }
        .count-status-warn { background: #fef2f2 !important; color: #b91c1c; }

        .badge-status {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-block;
        }
        .status-ideal { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-kurang { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-lebih { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        .badge-kategori {
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-block;
            margin-top: 3px;
        }
        .badge-pelayanan { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-admin { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .btn-filter-kat {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1.5px solid #cbd5e1;
            background: #fff;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-filter-kat:hover { background: #f1f5f9; }
        .btn-filter-kat.active {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .rule-info-icon {
            color: #0284c7;
            margin-left: 4px;
            cursor: help;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            padding: 24px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            color: #64748b;
        }

        /* Print CSS */
        @media print {
            body { background: white; padding: 0; }
            .header-app, .card-control, .nav-actions, .info-kuota-bar, .rule-info-icon { display: none !important; }
            .container-fluid { max-width: 100%; padding: 0; }
            .table-wrapper { max-height: none; box-shadow: none; border: 2px solid #000; }
            .table-excel th, .table-excel td { padding: 2px 1px; font-size: 7.5pt; border: 1px solid #000 !important; }
            .sticky-col-1, .sticky-col-2, .sticky-col-3, .sticky-col-4 { position: static; }
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header-app">
        <div class="header-content">
            <div class="brand-box">
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo RS" class="brand-logo">
                <div class="brand-text">
                    <h1>Jadwal Jaga Petugas Farmasi</h1>
                    <p><?php echo htmlspecialchars($nama_instansi); ?> • Periode: <?php echo $nama_bulan_id[$bulan_pilihan] . " " . $tahun_pilihan; ?></p>
                </div>
            </div>
            <div class="nav-actions">
                <a href="farmasi.php" class="btn-nav"><i class="fas fa-th-large"></i> Menu Farmasi</a>
                <a href="index.php" class="btn-nav"><i class="fas fa-home"></i> Beranda</a>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><?php echo htmlspecialchars($pesan_sukses); ?></span>
            </div>
        <?php endif; ?>

        <!-- Info Kuota Harian & Kalkulasi Jam Kerja -->
        <div class="info-kuota-bar" id="status-kuota-banner">
            <i class="fas fa-clock fa-lg" style="color:#059669;"></i>
            <span>Bobot Jam Kerja: <strong>Pagi (P) = 6 Jam</strong>, <strong>Sore (S) = 6 Jam</strong>, <strong>Malam (M) = 12 Jam (2x Shift Pagi/Sore)</strong>. Target Jam Kerja Standar Seimbang: <strong>150 – 168 Jam / Bulan</strong>.</span>
        </div>

        <!-- Banner Pemisahan Kategori Tugas (Pelayanan Pasien vs Admin/Gudang) -->
        <div class="info-kuota-bar kat-summary-bar" style="background:#e0f2fe; border-color:#38bdf8; color:#0369a1; margin-bottom:16px;">
            <i class="fas fa-user-shield fa-lg" style="color:#0284c7;"></i>
            <span>Pemisahan Tugas: 
                <strong style="color:#0284c7;"><i class="fas fa-user-nurse"></i> 13 Pegawai Pelayanan Pasien</strong> (Depo/Resep Langsung) &nbsp;|&nbsp; 
                <strong style="color:#b45309;"><i class="fas fa-boxes"></i> 6 Pegawai Non-Pelayanan</strong> (Administrasi & Gudang: NIK 19850816..., Farmasi1, 27091982, 12091992, ronaldi, 19850917...).
            </span>
        </div>

        <!-- Form Filter & Control Toolbar -->
        <div class="card-control">
            <form method="GET" class="filter-form" id="form-filter">
                <div class="form-group-custom">
                    <label><i class="far fa-calendar-alt"></i> Bulan:</label>
                    <select name="bulan" class="select-custom" onchange="document.getElementById('form-filter').submit();">
                        <?php foreach ($nama_bulan_id as $m_num => $m_name): ?>
                            <option value="<?php echo $m_num; ?>" <?php echo $m_num == $bulan_pilihan ? 'selected' : ''; ?>>
                                <?php echo $m_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label>Tahun:</label>
                    <select name="tahun" class="select-custom" onchange="document.getElementById('form-filter').submit();">
                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $tahun_pilihan ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group-custom" style="margin-left: 10px;">
                    <label><i class="fas fa-filter"></i> Tampilkan:</label>
                    <div style="display:flex; gap:4px;">
                        <button type="button" class="btn-filter-kat active" id="filter-all" onclick="filterKategori('all')">Semua (19)</button>
                        <button type="button" class="btn-filter-kat" id="filter-pelayanan" onclick="filterKategori('pelayanan')">🩺 Pelayanan (13)</button>
                        <button type="button" class="btn-filter-kat" id="filter-non_pelayanan" onclick="filterKategori('non_pelayanan')">🏢 Admin & Gudang (6)</button>
                    </div>
                </div>
            </form>

            <div class="toolbar-buttons">
                <button type="button" class="btn-action btn-warning-custom" onclick="resetJadwalOtomatis()">
                    <i class="fas fa-sync-alt"></i> Jana Otomatis Presisi
                </button>

                <button type="button" class="btn-action btn-success-custom" onclick="simpanKeDatabase()">
                    <i class="fas fa-save"></i> Simpan ke DB
                </button>

                <button type="button" class="btn-action btn-primary-custom" onclick="bukaModalImport()">
                    <i class="fas fa-file-upload"></i> Impor CSV
                </button>

                <button type="button" class="btn-action btn-secondary-custom" onclick="eksporKeCSV()">
                    <i class="fas fa-file-csv"></i> Ekspor CSV
                </button>

                <button type="button" class="btn-action btn-dark-custom" onclick="salinKeClipboard()">
                    <i class="fas fa-copy"></i> Salin Tabel
                </button>

                <button type="button" class="btn-action btn-secondary-custom" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </div>

        <!-- Form Utama Jadwal Format Excel Grid P/S/M -->
        <form method="POST" id="form-jadwal-main">
            <input type="hidden" name="action" value="simpan_db">

            <div class="table-wrapper">
                <table class="table-excel" id="tabel-jadwal">
                    <thead>
                        <!-- Baris 1 Header: Nomor Tanggal (Span 3 Sub-kolom) -->
                        <tr>
                            <th class="sticky-col-1" rowspan="2">No</th>
                            <th class="sticky-col-2" rowspan="2">NIK</th>
                            <th class="sticky-col-3" rowspan="2">Nama Pegawai</th>
                            <th class="sticky-col-4" rowspan="2">Jabatan</th>

                            <?php 
                            for ($d = 1; $d <= $jumlah_hari; $d++): 
                                $tgl_full = sprintf('%s-%s-%02d', $tahun_pilihan, $bulan_pilihan, $d);
                                $timestamp = strtotime($tgl_full);
                                $day_idx = date('w', $timestamp);
                                $day_name = $nama_hari_id[$day_idx];
                                $is_libur = ($day_idx == 0 || isset($hari_libur_db[$tgl_full]));
                                $title_libur = isset($hari_libur_db[$tgl_full]) ? $hari_libur_db[$tgl_full] : ($day_idx == 0 ? 'Hari Minggu' : '');
                            ?>
                                <th colspan="3" class="th-date-group <?php echo $is_libur ? 'libur' : ''; ?>" title="<?php echo htmlspecialchars($title_libur); ?>">
                                    <?php echo $d; ?> <span style="font-size:0.7rem; font-weight:600; font-family:sans-serif; color:#475569;">(<?php echo $day_name; ?>)</span>
                                </th>
                            <?php endfor; ?>

                            <th colspan="6" style="border-bottom:1px solid #000;">Rekap Shift & Jam Kerja</th>
                        </tr>

                        <!-- Baris 2 Header: Sub-kolom P, S, M under each date -->
                        <tr>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <th class="th-sub-col">P</th>
                                <th class="th-sub-col">S</th>
                                <th class="th-sub-col border-right-thick">M</th>
                            <?php endfor; ?>

                            <th class="col-summary" title="Total Shift Pagi (termasuk Pagi-Sore)">P</th>
                            <th class="col-summary" title="Total Shift Sore (termasuk Pagi-Sore)">S</th>
                            <th class="col-summary" title="Total Shift Malam (12 Jam)">M</th>
                            <th class="col-summary" title="Total Libur">L</th>
                            <th class="col-summary" style="min-width:65px;" title="Akumulasi Jam Kerja: (Pagi x 6j) + (Sore x 6j) + (Malam x 12j)">Total Jam</th>
                            <th class="col-summary" style="min-width:75px;" title="Status Keseimbangan Jam Kerja Bulanan">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($data_pegawai_db as $no_idx => $peg): 
                            $nik = $peg['nik'];
                            $id_peg = $peg['id'];
                            $kategori = isset($peg['kategori']) ? $peg['kategori'] : 'pelayanan';
                            $has_saved = isset($jadwal_terimpan_db[$id_peg]);
                            $row_saved = $has_saved ? $jadwal_terimpan_db[$id_peg] : [];
                        ?>
                            <tr data-nik="<?php echo htmlspecialchars($nik); ?>" data-kategori="<?php echo htmlspecialchars($kategori); ?>">
                                <td class="sticky-col-1"><?php echo ($no_idx + 1); ?></td>
                                <td class="sticky-col-2"><?php echo htmlspecialchars($nik); ?></td>
                                <td class="sticky-col-3">
                                    <div>
                                        <?php echo htmlspecialchars($peg['nama']); ?>
                                        <i class="fas fa-info-circle rule-info-icon" title="Rule: <?php echo htmlspecialchars($peg['rule_desc']); ?>"></i>
                                    </div>
                                    <?php if ($kategori === 'pelayanan'): ?>
                                        <span class="badge-kategori badge-pelayanan" title="Tugas: Pelayanan Pasien Langsung (Depo/Resep)"><i class="fas fa-user-nurse"></i> Pelayanan Pasien</span>
                                    <?php else: ?>
                                        <span class="badge-kategori badge-admin" title="Tugas: Administrasi / Gudang (Non-Pelayanan Pasien)"><i class="fas fa-boxes"></i> Admin / Gudang</span>
                                    <?php endif; ?>
                                </td>
                                <td class="sticky-col-4"><?php echo htmlspecialchars($peg['jbtn']); ?></td>

                                <?php 
                                for ($d = 1; $d <= $jumlah_hari; $d++): 
                                    $tgl_full = sprintf('%s-%s-%02d', $tahun_pilihan, $bulan_pilihan, $d);
                                    
                                    // Tentukan nilai awal shift
                                    if ($has_saved && !empty($row_saved["h$d"])) {
                                        $val_db = $row_saved["h$d"];
                                        if ($val_db === 'Pagi') $shift_val = 'P';
                                        else if ($val_db === 'Siang') $shift_val = 'S';
                                        else if ($val_db === 'Malam') $shift_val = 'M';
                                        else if ($val_db === 'Pagi2') $shift_val = 'PS';
                                        else $shift_val = 'L';
                                    } else {
                                        $shift_val = isset($jadwal_otomatis_matrix[$nik][$d]) ? $jadwal_otomatis_matrix[$nik][$d] : 'P';
                                    }

                                    $is_p = ($shift_val === 'P' || $shift_val === 'PS');
                                    $is_s = ($shift_val === 'S' || $shift_val === 'PS');
                                    $is_m = ($shift_val === 'M');
                                ?>
                                    <!-- Input hidden untuk penampung nilai form submit POST -->
                                    <input type="hidden" name="jadwal[<?php echo htmlspecialchars($nik); ?>][<?php echo $d; ?>]" 
                                           id="input-<?php echo htmlspecialchars($nik); ?>-<?php echo $d; ?>" 
                                           value="<?php echo $shift_val; ?>"
                                           data-default="<?php echo isset($jadwal_otomatis_matrix[$nik][$d]) ? $jadwal_otomatis_matrix[$nik][$d] : 'P'; ?>"
                                           data-kategori="<?php echo htmlspecialchars($kategori); ?>"
                                           data-day="<?php echo $d; ?>">

                                    <!-- Sub-cell Pagi (P) -->
                                    <td class="cell-shift cell-p <?php echo $is_p ? 'active' : ''; ?>" 
                                        onclick="toggleSubCell(this, '<?php echo htmlspecialchars($nik); ?>', <?php echo $d; ?>, 'P')" title="Tanggal <?php echo $d; ?>: Pagi (6 Jam)">
                                        P
                                    </td>

                                    <!-- Sub-cell Sore (S) -->
                                    <td class="cell-shift cell-s <?php echo $is_s ? 'active' : ''; ?>" 
                                        onclick="toggleSubCell(this, '<?php echo htmlspecialchars($nik); ?>', <?php echo $d; ?>, 'S')" title="Tanggal <?php echo $d; ?>: Sore (6 Jam)">
                                        S
                                    </td>

                                    <!-- Sub-cell Malam (M) -->
                                    <td class="cell-shift cell-m border-right-thick <?php echo $is_m ? 'active' : ''; ?>" 
                                        onclick="toggleSubCell(this, '<?php echo htmlspecialchars($nik); ?>', <?php echo $d; ?>, 'M')" title="Tanggal <?php echo $d; ?>: Malam (12 Jam)">
                                        M
                                    </td>
                                <?php endfor; ?>

                                <!-- Kolom Rekapitulasi (P, S, M, L, Total Jam, Status) -->
                                <td class="col-summary count-p">0</td>
                                <td class="col-summary count-s">0</td>
                                <td class="col-summary count-m">0</td>
                                <td class="col-summary count-l">0</td>
                                <td class="col-summary count-jam" style="color:#0284c7; font-weight:800;">0 Jam</td>
                                <td class="col-summary count-status">--</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                    <!-- Baris Footer: Rekap Jumlah Petugas Jaga Pagi (Peak Hour Pelayanan vs Admin), Sore, & Malam -->
                    <tfoot>
                        <!-- Baris 1: Kecukupan Pagi Pelayanan Pasien (Peak Hour Direct Care) -->
                        <tr class="row-daily-counter" style="background:#e0f2fe;">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important; color:#0369a1;">
                                <strong>🩺 Pagi (Pelayanan Pasien):</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-p-pel" id="daily-p-pel-<?php echo $d; ?>" style="color:#0369a1; font-weight:800;">0</td>
                            <?php endfor; ?>
                            <td colspan="6" rowspan="6" style="background:#f1f5f9; font-size:0.75rem; text-align:left; padding:10px; color:#475569; vertical-align:top;">
                                <strong><i class="fas fa-info-circle"></i> Monitoring Shift:</strong><br>
                                • 🩺 <strong>Pagi Pelayanan Pasien</strong> (Peak Hour target ≥ 4 orang)<br>
                                • 🏢 <strong>Pagi Admin & Gudang</strong> (Total 6 orang)<br>
                                • 🌅 <strong>Sore</strong> (Wajib 2 orang)<br>
                                • 🌙 <strong>Malam</strong> (Wajib 1 orang)
                            </td>
                        </tr>

                        <!-- Baris 2: Pagi Non-Pelayanan (Admin & Gudang) -->
                        <tr class="row-daily-counter" style="background:#fef3c7;">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important; color:#92400e;">
                                <strong>🏢 Pagi (Admin & Gudang):</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-p-adm" id="daily-p-adm-<?php echo $d; ?>" style="color:#92400e;">0</td>
                            <?php endfor; ?>
                        </tr>

                        <!-- Baris 3: Total Pagi Overall -->
                        <tr class="row-daily-counter">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important;">
                                <strong>📊 Total Shift Pagi (Semua):</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-p-tot" id="daily-p-tot-<?php echo $d; ?>">0</td>
                            <?php endfor; ?>
                        </tr>

                        <!-- Baris 4: Sore -->
                        <tr class="row-daily-counter">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important;">
                                <strong>🌅 Jml Sore (S/PS):</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-s" id="daily-s-<?php echo $d; ?>">0</td>
                            <?php endfor; ?>
                        </tr>

                        <!-- Baris 5: Malam -->
                        <tr class="row-daily-counter">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important;">
                                <strong>🌙 Jml Malam (M):</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-m" id="daily-m-<?php echo $d; ?>">0</td>
                            <?php endfor; ?>
                        </tr>

                        <!-- Baris 6: Total Jam -->
                        <tr class="row-daily-counter">
                            <td colspan="4" class="sticky-col-1" style="text-align:right !important; padding-right:10px !important; color:#0369a1;">
                                <strong>⏱️ Total Jam Harian:</strong>
                            </td>
                            <?php for ($d = 1; $d <= $jumlah_hari; $d++): ?>
                                <td colspan="3" class="border-right-thick daily-count-jam" id="daily-jam-<?php echo $d; ?>" style="color:#0369a1;">0j</td>
                            <?php endfor; ?>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </form>
    </div>

    <!-- Modal Impor CSV -->
    <div class="modal-overlay" id="modal-import">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-file-upload"></i> Impor Data CSV Jadwal</h3>
                <button type="button" class="close-modal" onclick="tutupModalImport()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.85rem; color:#64748b; margin-bottom:14px;">
                    Unggah file CSV dengan format baris: <code>NIK;Shift1;Shift2;Shift3;...</code> 
                    (Isi shift berupa kode: P, S, M, PS, atau L).
                </p>
                <textarea id="csv-input-text" rows="8" placeholder="Contoh format:&#10;198508162020122003;P;P;P;P;P;L;L;...&#10;Farmasi1;P;P;P;P;P;L;L;..." style="width:100%; border-radius:10px; border:1px solid #cbd5e1; padding:12px; font-family:monospace; font-size:0.85rem; outline:none; margin-bottom:16px;"></textarea>

                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <input type="file" id="file-csv-input" accept=".csv, .txt" onchange="bacaFileCSV(this)" style="font-size:0.85rem;">
                    <button type="button" class="btn-action btn-success-custom" onclick="terapkanCSV()">
                        <i class="fas fa-check"></i> Terapkan ke Tabel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            hitungSemuaSummary();
        });

        // Toggle klik pada sub-kolom P / S / M
        function toggleSubCell(cellElem, nik, dayNum, shiftType) {
            const tr = cellElem.closest('tr');
            const hiddenInput = document.getElementById(`input-${nik}-${dayNum}`);
            const cellP = cellElem.parentNode.children[cellElem.cellIndex - (shiftType === 'S' ? 1 : (shiftType === 'M' ? 2 : 0))];
            const cellS = cellElem.parentNode.children[cellElem.cellIndex - (shiftType === 'S' ? 0 : (shiftType === 'M' ? 1 : -1))];
            const cellM = cellElem.parentNode.children[cellElem.cellIndex - (shiftType === 'S' ? -1 : (shiftType === 'M' ? 0 : -2))];

            let currVal = hiddenInput.value;

            if (shiftType === 'P') {
                if (currVal === 'P') currVal = 'L';
                else if (currVal === 'S') currVal = 'PS';
                else if (currVal === 'PS') currVal = 'S';
                else currVal = 'P';
            } else if (shiftType === 'S') {
                if (currVal === 'S') currVal = 'L';
                else if (currVal === 'P') currVal = 'PS';
                else if (currVal === 'PS') currVal = 'P';
                else currVal = 'S';
            } else if (shiftType === 'M') {
                if (currVal === 'M') currVal = 'L';
                else currVal = 'M';
            }

            hiddenInput.value = currVal;
            renderSubCells(cellP, cellS, cellM, currVal);
            hitungRowSummary(tr);
            hitungDailySummary(dayNum);
        }

        function renderSubCells(cellP, cellS, cellM, val) {
            cellP.classList.toggle('active', val === 'P' || val === 'PS');
            cellS.classList.toggle('active', val === 'S' || val === 'PS');
            cellM.classList.toggle('active', val === 'M');
        }

        // Hitung rekapitulasi summary per pegawai (P=6j, S=6j, M=12j)
        function hitungRowSummary(tr) {
            const inputs = tr.querySelectorAll('input[type="hidden"]');
            let p = 0, s = 0, m = 0, l = 0;

            inputs.forEach(inp => {
                const v = inp.value;
                if (v === 'P') {
                    p++;
                } else if (v === 'S') {
                    s++;
                } else if (v === 'M') {
                    m++;
                } else if (v === 'PS') {
                    p++; // Pagi & Sore dihitung 1 Pagi
                    s++; // dan 1 Sore
                } else if (v === 'L') {
                    l++;
                }
            });

            // Bobot Jam Kerja: Pagi = 6 Jam, Sore = 6 Jam, Malam = 12 Jam
            const totalJam = (p * 6) + (s * 6) + (m * 12);

            tr.querySelector('.count-p').innerText = p;
            tr.querySelector('.count-s').innerText = s;
            tr.querySelector('.count-m').innerText = m;
            tr.querySelector('.count-l').innerText = l;
            
            const jamElem = tr.querySelector('.count-jam');
            if (jamElem) {
                jamElem.innerText = totalJam + " Jam";
            }

            const badgeElem = tr.querySelector('.count-status');
            if (badgeElem) {
                if (totalJam >= 150 && totalJam <= 174) {
                    badgeElem.innerHTML = '<span class="badge-status status-ideal" title="Jam kerja seimbang (Standar 40 jam/minggu)">Ideal</span>';
                } else if (totalJam < 150) {
                    badgeElem.innerHTML = '<span class="badge-status status-kurang" title="Kurang dari target standar 150 jam">Kurang</span>';
                } else {
                    badgeElem.innerHTML = '<span class="badge-status status-lebih" title="Melebihi target 174 jam">Lembur</span>';
                }
            }
        }

        // Filter tampilan tabel berdasarkan kategori tugas (Semua, Pelayanan, Non-Pelayanan)
        function filterKategori(kat) {
            document.querySelectorAll('.btn-filter-kat').forEach(b => b.classList.remove('active'));
            const activeBtn = document.getElementById('filter-' + kat);
            if (activeBtn) activeBtn.classList.add('active');

            document.querySelectorAll('#tabel-jadwal tbody tr').forEach(tr => {
                const trKat = tr.getAttribute('data-kategori');
                if (kat === 'all' || trKat === kat) {
                    tr.style.display = '';
                } else {
                    tr.style.display = 'none';
                }
            });
        }

        // Hitung rekapitulasi jumlah Pagi (Pelayanan vs Admin), Sore (S/PS), Malam (M), dan Total Jam per tanggal
        function hitungDailySummary(dayNum) {
            const inputs = document.querySelectorAll(`input[data-day="${dayNum}"]`);
            let countP_Pelayanan = 0;
            let countP_Admin = 0;
            let countP_Total = 0;
            let countS = 0;
            let countM = 0;

            inputs.forEach(inp => {
                const v = inp.value;
                const kat = inp.getAttribute('data-kategori');

                if (v === 'P' || v === 'PS') {
                    countP_Total++;
                    if (kat === 'pelayanan') countP_Pelayanan++;
                    else countP_Admin++;
                }
                if (v === 'S' || v === 'PS') countS++;
                if (v === 'M') countM++;
            });

            const dailyTotalJam = (countP_Total * 6) + (countS * 6) + (countM * 12);

            const tdPPel = document.getElementById(`daily-p-pel-${dayNum}`);
            const tdPAdm = document.getElementById(`daily-p-adm-${dayNum}`);
            const tdPTot = document.getElementById(`daily-p-tot-${dayNum}`);
            const tdS = document.getElementById(`daily-s-${dayNum}`);
            const tdM = document.getElementById(`daily-m-${dayNum}`);
            const tdJam = document.getElementById(`daily-jam-${dayNum}`);

            if (tdPPel) {
                tdPPel.innerText = countP_Pelayanan;
                tdPPel.className = "border-right-thick daily-count-p-pel " + (countP_Pelayanan >= 4 ? "count-status-ok" : "count-status-warn");
                tdPPel.title = `Petugas Pelayanan Pasien Shift Pagi Tanggal ${dayNum}: ${countP_Pelayanan} orang` + (countP_Pelayanan < 4 ? " (Peringatan: Pagi Pelayanan < 4 Orang)" : " (Kuota Cukup)");
            }

            if (tdPAdm) {
                tdPAdm.innerText = countP_Admin;
            }

            if (tdPTot) {
                tdPTot.innerText = countP_Total;
            }

            if (tdS) {
                tdS.innerText = countS;
                tdS.className = "border-right-thick daily-count-s " + (countS >= 2 ? "count-status-ok" : "count-status-warn");
            }

            if (tdM) {
                tdM.innerText = countM;
                tdM.className = "border-right-thick daily-count-m " + (countM === 1 ? "count-status-ok" : "count-status-warn");
            }

            if (tdJam) {
                tdJam.innerText = dailyTotalJam + "j";
            }
        }

        function hitungSemuaSummary() {
            document.querySelectorAll('#tabel-jadwal tbody tr').forEach(tr => {
                hitungRowSummary(tr);
            });

            for (let d = 1; d <= <?php echo $jumlah_hari; ?>; d++) {
                hitungDailySummary(d);
            }
        }

        // Reset Jadwal ke Aturan Otomatis Bawaan
        function resetJadwalOtomatis() {
            if (confirm("Apakah Anda yakin ingin me-reset seluruh shift ke aturan presisi seimbang jam kerja? Data yang diubah manual akan ditimpa.")) {
                document.querySelectorAll('#tabel-jadwal tbody tr').forEach(tr => {
                    const inputs = tr.querySelectorAll('input[type="hidden"]');
                    inputs.forEach(inp => {
                        const def = inp.getAttribute('data-default');
                        if (def) {
                            inp.value = def;
                            const cellP = inp.nextElementSibling;
                            const cellS = cellP.nextElementSibling;
                            const cellM = cellS.nextElementSibling;
                            renderSubCells(cellP, cellS, cellM, def);
                        }
                    });
                    hitungRowSummary(tr);
                });
                for (let d = 1; d <= <?php echo $jumlah_hari; ?>; d++) {
                    hitungDailySummary(d);
                }
            }
        }

        function simpanKeDatabase() {
            if (confirm("Simpan data jadwal bulan ini ke database RSUD Pringsewu?")) {
                document.getElementById('form-jadwal-main').submit();
            }
        }

        // Salin data tabel ke Clipboard
        function salinKeClipboard() {
            const trs = document.querySelectorAll('#tabel-jadwal tbody tr');
            let text = "No\tNIK\tNama Pegawai\tJabatan\t" + Array.from({length: <?php echo $jumlah_hari; ?>}, (_, i) => `Tgl ${i+1}`).join("\t") + "\n";
            
            trs.forEach(tr => {
                let rowData = [
                    tr.querySelector('.sticky-col-1').innerText.trim(),
                    tr.querySelector('.sticky-col-2').innerText.trim(),
                    tr.querySelector('.sticky-col-3').innerText.replace(/\n/g, ' ').trim(),
                    tr.querySelector('.sticky-col-4').innerText.trim()
                ];
                const inputs = tr.querySelectorAll('input[type="hidden"]');
                inputs.forEach(inp => rowData.push(inp.value));
                text += rowData.join("\t") + "\n";
            });

            navigator.clipboard.writeText(text).then(() => {
                alert("Data tabel jadwal berhasil disalin ke clipboard!");
            }).catch(err => alert("Gagal menyalin: " + err));
        }

        // Ekspor Tabel ke CSV
        function eksporKeCSV() {
            const trs = document.querySelectorAll('#tabel-jadwal tbody tr');
            let csv = [];
            
            let header = ['"No"', '"NIK"', '"Nama Pegawai"', '"Jabatan"'];
            for (let d = 1; d <= <?php echo $jumlah_hari; ?>; d++) {
                header.push(`"Tgl ${d}"`);
            }
            csv.push(header.join(";"));

            trs.forEach(tr => {
                let rowData = [
                    '"' + tr.querySelector('.sticky-col-1').innerText.trim() + '"',
                    '"' + tr.querySelector('.sticky-col-2').innerText.trim() + '"',
                    '"' + tr.querySelector('.sticky-col-3').innerText.replace(/\n/g, ' ').replace(/"/g, '""').trim() + '"',
                    '"' + tr.querySelector('.sticky-col-4').innerText.trim() + '"'
                ];
                const inputs = tr.querySelectorAll('input[type="hidden"]');
                inputs.forEach(inp => rowData.push('"' + inp.value + '"'));
                csv.push(rowData.join(";"));
            });

            const csvString = csv.join("\n");
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `jadwal_jaga_farmasi_<?php echo $tahun_pilihan . '_' . $bulan_pilihan; ?>.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function bukaModalImport() { document.getElementById('modal-import').style.display = 'flex'; }
        function tutupModalImport() { document.getElementById('modal-import').style.display = 'none'; }

        function bacaFileCSV(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('csv-input-text').value = e.target.result;
                };
                reader.readAsText(file);
            }
        }

        function terapkanCSV() {
            const rawText = document.getElementById('csv-input-text').value.trim();
            if (!rawText) {
                alert("Silakan masukkan teks atau upload file CSV!");
                return;
            }

            const lines = rawText.split('\n');
            let updated = 0;

            lines.forEach(line => {
                const parts = line.split(/[;,]/).map(s => s.trim().replace(/^"|"$/g, ''));
                if (parts.length >= 2) {
                    const nikInput = parts[0];
                    const tr = document.querySelector(`tr[data-nik="${nikInput}"]`);
                    if (tr) {
                        const inputs = tr.querySelectorAll('input[type="hidden"]');
                        for (let i = 0; i < inputs.length; i++) {
                            if (parts[i + 1]) {
                                const v = parts[i + 1].toUpperCase();
                                if (['P', 'S', 'M', 'PS', 'L'].includes(v)) {
                                    inputs[i].value = v;
                                    const cellP = inputs[i].nextElementSibling;
                                    const cellS = cellP.nextElementSibling;
                                    const cellM = cellS.nextElementSibling;
                                    renderSubCells(cellP, cellS, cellM, v);
                                }
                            }
                        }
                        hitungRowSummary(tr);
                        updated++;
                    }
                }
            });

            for (let d = 1; d <= <?php echo $jumlah_hari; ?>; d++) {
                hitungDailySummary(d);
            }

            alert(`Berhasil memperbarui data jadwal untuk ${updated} pegawai!`);
            tutupModalImport();
        }
    </script>
</body>
</html>
