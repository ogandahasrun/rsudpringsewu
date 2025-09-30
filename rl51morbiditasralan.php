<?php
// filepath: c:\xampp\htdocs\rsudpringsewu\rl51morbiditasralan.php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Daftar label kelompok umur
$kelompok_umur = [
    '1_hr'      => '1 hr',
    '2_7_hr'    => '2-7 hr',
    '8_31_hr'   => '8-31 hr',
    '1_3_bl'    => '1-3 bl',
    '4_6_bl'    => '4-6 bl',
    '7_11_bl'   => '7-11 bl',
    '1_4_th'    => '1-4 th',
    '5_9_th'    => '5-9 th',
    '10_14_th'  => '10-14 th',
    '15_19_th'  => '15-19 th',
    '20_24_th'  => '20-24 th',
    '25_29_th'  => '25-29 th',
    '30_34_th'  => '30-34 th',
    '35_39_th'  => '35-39 th',
    '40_44_th'  => '40-44 th',
    '45_49_th'  => '45-49 th',
    '50_54_th'  => '50-54 th',
    '55_59_th'  => '55-59 th',
    '60_64_th'  => '60-64 th',
    '65_69_th'  => '65-69 th',
    '70_74_th'  => '70-74 th',
    '75_79_th'  => '75-79 th',
    '80_84_th'  => '80-84 th',
    '85_th'     => '>84 th'
];

// Query utama: ambil data diagnosa utama, umur, jenis kelamin, status_lanjut
$query = "SELECT
    diagnosa_pasien.kd_penyakit,
    penyakit.nm_penyakit,
    pasien.jk,
    reg_periksa.umurdaftar,
    reg_periksa.sttsumur,
    reg_periksa.status_lanjut
    FROM
    diagnosa_pasien
    INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
    INNER JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    WHERE
    diagnosa_pasien.prioritas = '1' AND
    reg_periksa.status_lanjut = 'ralan' AND
    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    ORDER BY
    diagnosa_pasien.kd_penyakit ASC
";
$result = mysqli_query($koneksi, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $kd = $row['kd_penyakit'];
    $jk = $row['jk'];
    $umur = (int)$row['umurdaftar'];
    $sttsumur = strtolower($row['sttsumur']);
    $nm_penyakit = $row['nm_penyakit'];
    $status_lanjut = isset($row['status_lanjut']) ? strtolower($row['status_lanjut']) : '';

    // Inisialisasi jika belum ada
    if (!isset($data[$kd])) {
        $data[$kd] = [
            'nm_penyakit' => $nm_penyakit,
            'kelompok' => [],
            'jumlah_L' => 0,
            'jumlah_P' => 0,
            'jumlah_total' => 0,
            'meninggal_L' => 0,
            'meninggal_P' => 0,
            'meninggal_total' => 0
        ];
        // Inisialisasi kelompok umur
        foreach ($kelompok_umur as $key => $label) {
            $data[$kd]['kelompok'][$key . '_L'] = 0;
            $data[$kd]['kelompok'][$key . '_P'] = 0;
        }
    }

    // Tentukan kelompok umur
    $kelompok = '';
    if ($sttsumur == 'hr' && $umur == 0) $kelompok = '1_hr';
    elseif ($sttsumur == 'hr' && $umur >= 1 && $umur <= 7) $kelompok = '2_7_hr';
    elseif ($sttsumur == 'hr' && $umur >= 8 && $umur <= 31) $kelompok = '8_31_hr';
    elseif ($sttsumur == 'bl' && $umur >= 1 && $umur <= 3) $kelompok = '1_3_bl';
    elseif ($sttsumur == 'bl' && $umur >= 4 && $umur <= 6) $kelompok = '4_6_bl';
    elseif ($sttsumur == 'bl' && $umur >= 7 && $umur <= 11) $kelompok = '7_11_bl';
    elseif ($sttsumur == 'th' && $umur >= 1 && $umur <= 4) $kelompok = '1_4_th';
    elseif ($sttsumur == 'th' && $umur >= 5 && $umur <= 9) $kelompok = '5_9_th';
    elseif ($sttsumur == 'th' && $umur >= 10 && $umur <= 14) $kelompok = '10_14_th';
    elseif ($sttsumur == 'th' && $umur >= 15 && $umur <= 19) $kelompok = '15_19_th';
    elseif ($sttsumur == 'th' && $umur >= 20 && $umur <= 24) $kelompok = '20_24_th';
    elseif ($sttsumur == 'th' && $umur >= 25 && $umur <= 29) $kelompok = '25_29_th';
    elseif ($sttsumur == 'th' && $umur >= 30 && $umur <= 34) $kelompok = '30_34_th';
    elseif ($sttsumur == 'th' && $umur >= 35 && $umur <= 39) $kelompok = '35_39_th';
    elseif ($sttsumur == 'th' && $umur >= 40 && $umur <= 44) $kelompok = '40_44_th';
    elseif ($sttsumur == 'th' && $umur >= 45 && $umur <= 49) $kelompok = '45_49_th';
    elseif ($sttsumur == 'th' && $umur >= 50 && $umur <= 54) $kelompok = '50_54_th';
    elseif ($sttsumur == 'th' && $umur >= 55 && $umur <= 59) $kelompok = '55_59_th';
    elseif ($sttsumur == 'th' && $umur >= 60 && $umur <= 64) $kelompok = '60_64_th';
    elseif ($sttsumur == 'th' && $umur >= 65 && $umur <= 69) $kelompok = '65_69_th';
    elseif ($sttsumur == 'th' && $umur >= 70 && $umur <= 74) $kelompok = '70_74_th';
    elseif ($sttsumur == 'th' && $umur >= 75 && $umur <= 79) $kelompok = '75_79_th';
    elseif ($sttsumur == 'th' && $umur >= 80 && $umur <= 84) $kelompok = '80_84_th';
    elseif ($sttsumur == 'th' && $umur > 84) $kelompok = '85_th';

    // Tambahkan ke kelompok umur dan jenis kelamin
    if ($kelompok) {
        if ($jk == 'L') {
            $data[$kd]['kelompok'][$kelompok . '_L']++;
            $data[$kd]['jumlah_L']++;
        } else {
            $data[$kd]['kelompok'][$kelompok . '_P']++;
            $data[$kd]['jumlah_P']++;
        }
        $data[$kd]['jumlah_total']++;
    }

    // Hitung meninggal
    if ($status_lanjut == 'meninggal') {
        if ($jk == 'L') $data[$kd]['meninggal_L']++;
        else $data[$kd]['meninggal_P']++;
        $data[$kd]['meninggal_total']++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 5.1 Morbiditas Pasien Rawat Jalan</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-top: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
        }
        th:last-child, td:last-child {
            border-right: none;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 12px;
        }
        .back-button {
            margin-bottom: 15px;
        }
        .filter-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .filter-form input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background-color: #45a049;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }
        #copyTableBtn {
            margin-bottom:15px;
            padding:8px 15px;
            background:#2196F3;
            color:#fff;
            border:none;
            border-radius:4px;
            cursor:pointer;
        }
        #copyTableBtn:hover {
            background:#1976D2;
        }
    </style>
</head>
<body>
    <header>
        <h1>RL 5.1 Morbiditas Pasien Rawat Jalan</h1>
    </header>

    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

    <button id="copyTableBtn">Copy Tabel ke Clipboard</button>

    <!-- Form filter - method POST -->
    <form method="POST" class="filter-form">
        Filter Tanggal Registrasi :
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <table id="morbiditasTable">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Penyakit</th>
                <?php foreach ($kelompok_umur as $label): ?>
                    <th><?php echo $label; ?> L</th>
                    <th><?php echo $label; ?> P</th>
                <?php endforeach; ?>
                <th>Jumlah L</th>
                <th>Jumlah P</th>
                <th>Jumlah Total</th>
                <th>Meninggal L</th>
                <th>Meninggal P</th>
                <th>Meninggal Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="60" class="no-data">Tidak ada data untuk tanggal ini.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $kd => $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kd); ?></td>
                        <td><?php echo htmlspecialchars($row['nm_penyakit']); ?></td>
                        <?php foreach ($kelompok_umur as $key => $label): ?>
                            <td><?php echo $row['kelompok'][$key . '_L']; ?></td>
                            <td><?php echo $row['kelompok'][$key . '_P']; ?></td>
                        <?php endforeach; ?>
                        <td><?php echo $row['jumlah_L']; ?></td>
                        <td><?php echo $row['jumlah_P']; ?></td>
                        <td><?php echo $row['jumlah_total']; ?></td>
                        <td><?php echo $row['meninggal_L']; ?></td>
                        <td><?php echo $row['meninggal_P']; ?></td>
                        <td><?php echo $row['meninggal_total']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    document.getElementById('copyTableBtn').onclick = function() {
        var table = document.getElementById('morbiditasTable');
        var range = document.createRange();
        range.selectNode(table);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                alert('Tabel berhasil disalin ke clipboard!');
            } else {
                alert('Gagal menyalin tabel.');
            }
        } catch (err) {
            alert('Browser tidak mendukung copy tabel otomatis.');
        }
        window.getSelection().removeAllRanges();
    };
    </script>
</body>
</html>