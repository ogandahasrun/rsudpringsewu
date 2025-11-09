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
    <title>RL 5.1 Morbiditas Pasien Rawat Jalan - RSUD Pringsewu</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .filter-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1400px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            white-space: nowrap;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e9ecef;
            font-size: 12px;
            text-align: center;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #e3f2fd;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }
        .diagnosis-name {
            text-align: left !important;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 1.5em;
            }
            .content {
                padding: 15px;
            }
            .filter-form {
                padding: 20px 15px;
            }
            .filter-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .filter-actions {
                justify-content: stretch;
            }
            .btn {
                padding: 10px 15px;
                font-size: 13px;
            }
            th, td {
                padding: 6px 4px;
                font-size: 10px;
            }
            table {
                min-width: 1200px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .filter-title {
                font-size: 16px;
            }
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
    <div class="container">
        <div class="header">
            <h1>📊 RL 5.1 Morbiditas Pasien Rawat Jalan</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Tanggal Registrasi
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Filter Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <div class="filter-actions">
                <button type="button" onclick="copyTableData()" class="btn btn-success">
                    📋 Copy Tabel
                </button>
            </div>

            <div class="table-responsive">
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
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                <br>
                <strong>🏥 Data Morbiditas Rawat Jalan</strong> berdasarkan kelompok umur dan jenis kelamin
            </div>
        </div>
    </div>

    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        }
    </script>
</body>
</html>