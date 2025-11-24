<?php
// filepath: c:\xampp\htdocs\rsudpringsewu\skpfarmasi.php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Fungsi untuk membuat array tanggal pada periode
function getDateRange($start, $end) {
    $dates = [];
    $current = strtotime($start);
    $end = strtotime($end);
    while ($current <= $end) {
        $dates[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    return $dates;
}
$periode = getDateRange($tanggal_awal, $tanggal_akhir);

// --- Query dan proses data untuk setiap tabel ---
// Tambahkan pengecekan error pada setiap query agar mudah debug

// 1. Permintaan Barang dari Depo Rawat Inap
$data1 = array_fill_keys($periode, 0);
$q1 = "SELECT tanggal, COUNT(*) as jumlah FROM permintaan_medis WHERE kd_bangsal = 'dri' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tanggal";
$r1 = mysqli_query($koneksi, $q1);
if (!$r1) die("Query error 1: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r1)) {
    $data1[$row['tanggal']] = $row['jumlah'];
}

// 2. Permintaan Barang ke Gudang Obat
$data2 = array_fill_keys($periode, 0);
$q2 = "SELECT tanggal, COUNT(*) as jumlah FROM permintaan_medis WHERE kd_bangsaltujuan = 'go' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tanggal";
$r2 = mysqli_query($koneksi, $q2);
if (!$r2) die("Query error 2: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r2)) {
    $data2[$row['tanggal']] = $row['jumlah'];
}

// 3. Rekap Penerimaan Barang per Tanggal
$data3 = array_fill_keys($periode, 0);
$q3 = "SELECT tgl_pesan, COUNT(*) as jumlah FROM pemesanan WHERE tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' GROUP BY tgl_pesan";
$r3 = mysqli_query($koneksi, $q3);
if (!$r3) die("Query error 3: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r3)) {
    $data3[$row['tgl_pesan']] = $row['jumlah'];
}

// 4. Resep depo Rawat Inap (08.00 - 14.00)
$data4 = array_fill_keys($periode, 0);
$q4 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM resep_obat WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND jam BETWEEN '08:00:01' AND '14:00:00' AND status = 'ranap' GROUP BY tgl_perawatan";
$r4 = mysqli_query($koneksi, $q4);
if (!$r4) die("Query error 4: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r4)) {
    $data4[$row['tgl_perawatan']] = $row['jumlah'];
}

// 5. Resep depo Rawat Jalan (08.00 - 14.00)
$data5 = array_fill_keys($periode, 0);
$q5 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM resep_obat WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND jam BETWEEN '08:00:01' AND '14:00:00' AND status = 'ralan' GROUP BY tgl_perawatan";
$r5 = mysqli_query($koneksi, $q5);
if (!$r5) die("Query error 5: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r5)) {
    $data5[$row['tgl_perawatan']] = $row['jumlah'];
}

// 6. PIO depo Rawat Inap
$data6 = array_fill_keys($periode, 0);
$q6 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM rawat_inap_pr WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND kd_jenis_prw = 'T. FARMASIKL.1' GROUP BY tgl_perawatan";
$r6 = mysqli_query($koneksi, $q6);
if (!$r6) die("Query error 6: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r6)) {
    $data6[$row['tgl_perawatan']] = $row['jumlah'];
}

// 7. PIO depo Rawat Jalan
$data7 = array_fill_keys($periode, 0);
$q7 = "SELECT tgl_perawatan, COUNT(*) as jumlah FROM rawat_jl_pr WHERE tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND kd_jenis_prw = 'FARMASI24' GROUP BY tgl_perawatan";
$r7 = mysqli_query($koneksi, $q7);
if (!$r7) die("Query error 7: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r7)) {
    $data7[$row['tgl_perawatan']] = $row['jumlah'];
}

// 8. Resep Racikan Depo Rawat Inap
$data8 = array_fill_keys($periode, 0);
$q8 = "SELECT resep_obat.tgl_perawatan, COUNT(*) as jumlah FROM resep_obat INNER JOIN resep_dokter_racikan ON resep_dokter_racikan.no_resep = resep_obat.no_resep WHERE resep_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND resep_obat.status = 'ranap' GROUP BY resep_obat.tgl_perawatan";
$r8 = mysqli_query($koneksi, $q8);
if (!$r8) die("Query error 8: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r8)) {
    $data8[$row['tgl_perawatan']] = $row['jumlah'];
}

// 9. Resep Racikan Depo Rawat Jalan
$data9 = array_fill_keys($periode, 0);
$q9 = "SELECT resep_obat.tgl_perawatan, COUNT(*) as jumlah FROM resep_obat INNER JOIN resep_dokter_racikan ON resep_dokter_racikan.no_resep = resep_obat.no_resep WHERE resep_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND resep_obat.status = 'ralan' GROUP BY resep_obat.tgl_perawatan";
$r9 = mysqli_query($koneksi, $q9);
if (!$r9) die("Query error 9: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r9)) {
    $data9[$row['tgl_perawatan']] = $row['jumlah'];
}

// 10. Mutasi Masuk dari Gudang Obat ke Depo Rawat Jalan
$data10 = [];
$data10 = array_fill_keys($periode, 0);
$q10 = "SELECT DATE(mutasibarang.tanggal) AS tgl, COUNT(*) as jumlah
FROM mutasibarang
WHERE mutasibarang.kd_bangsalke = 'AP' 
  AND mutasibarang.kd_bangsaldari = 'GO' 
  AND mutasibarang.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
GROUP BY tgl";
$r10 = mysqli_query($koneksi, $q10);
if (!$r10) die("Query error 10: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r10)) {
    $data10[$row['tgl']] = $row['jumlah'];
}

// 11. Penerimaan Barang ke Gudang Farmasi
$data11 = array_fill_keys($periode, 0);
$q11 = "SELECT pemesanan.tgl_pesan, COUNT(detailpesan.kode_brng) as jumlah
FROM pemesanan
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
WHERE pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY pemesanan.tgl_pesan
ORDER BY pemesanan.tgl_pesan ASC";
$r11 = mysqli_query($koneksi, $q11);
if (!$r11) die("Query error 11: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r11)) {
    $data11[$row['tgl_pesan']] = $row['jumlah'];
}

// 12. Stok Keluar Gudang Farmasi
$data12 = array_fill_keys($periode, 0);
$q12 = "SELECT pengeluaran_obat_bhp.tanggal, COUNT(detail_pengeluaran_obat_bhp.kode_brng) as jumlah
FROM pengeluaran_obat_bhp
INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
WHERE pengeluaran_obat_bhp.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY pengeluaran_obat_bhp.tanggal
ORDER BY pengeluaran_obat_bhp.tanggal ASC";
$r12 = mysqli_query($koneksi, $q12);
if (!$r12) die("Query error 12: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r12)) {
    $data12[$row['tanggal']] = $row['jumlah'];
}

// 13. Mutasi dari Gudang Farmasi (format tanggal+jam, tampilkan tanggal dan jumlah)
$data13 = array_fill_keys($periode, 0);
$q13 = "SELECT DATE(mutasibarang.tanggal) AS tgl, COUNT(mutasibarang.kode_brng) as jumlah
FROM mutasibarang
WHERE mutasibarang.kd_bangsaldari = 'go' 
  AND mutasibarang.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
GROUP BY tgl
ORDER BY tgl ASC";
$r13 = mysqli_query($koneksi, $q13);
if (!$r13) die("Query error 13: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r13)) {
    $data13[$row['tgl']] = $row['jumlah'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKP Farmasi - RSUD Pringsewu</title>
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            grid-template-columns: auto auto auto;
            gap: 15px;
            align-items: end;
            justify-content: start;
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
        .filter-group input {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 30px 0 15px 0;
            padding: 15px 20px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            border-left: 5px solid #007bff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 400px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #e3f2fd;
        }
        .number-cell {
            text-align: right;
            font-weight: bold;
            font-family: monospace;
        }
        .date-cell {
            text-align: center;
            font-family: monospace;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
        }
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
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
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 300px;
            }
            .section-title {
                font-size: 16px;
                padding: 12px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .summary-cards {
                grid-template-columns: 1fr;
            }
            .summary-card .value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 SKP Farmasi</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">
                    ← Kembali ke Menu Farmasi
                </a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Laporan SKP Farmasi
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" 
                               id="tanggal_awal"
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir"
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    
                    <div>
                        <button type="submit" name="filter" class="btn btn-primary">
                            📊 Tampilkan Laporan
                        </button>
                    </div>
                </div>
            </form>

            <?php
            // Hitung total untuk summary cards
            $total_data = [];
            for ($i = 1; $i <= 13; $i++) {
                $varName = "data$i";
                $total_data[$i] = array_sum($$varName);
            }
            ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3>📋 Total Permintaan Depo</h3>
                    <div class="value"><?php echo number_format($total_data[1]); ?></div>
                </div>
                <div class="summary-card">
                    <h3>🏥 Total Permintaan Gudang</h3>
                    <div class="value"><?php echo number_format($total_data[2]); ?></div>
                </div>
                <div class="summary-card">
                    <h3>📦 Total Penerimaan</h3>
                    <div class="value"><?php echo number_format($total_data[3]); ?></div>
                </div>
                <div class="summary-card">
                    <h3>💊 Total Resep</h3>
                    <div class="value"><?php echo number_format($total_data[4] + $total_data[5]); ?></div>
                </div>
            </div>

            <div class="actions-bar">
                <div style="color: #6c757d; font-size: 14px;">
                    📋 <strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>
                </div>
                <button id="copyTableBtn" class="btn btn-success">
                    📋 Copy Semua Data
                </button>
            </div>

            <div id="allTables">

            <div class="section-title">
                📋 1. Permintaan Barang dari Depo Rawat Inap
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Permintaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data1[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[1]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🏥 2. Permintaan Barang ke Gudang Obat
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Permintaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data2[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[2]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                📦 3. Rekap Penerimaan Barang per Tanggal
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Penerimaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data3[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[3]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                💊 4. Resep depo Rawat Inap (08.00 - 14.00)
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data4[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[4]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🏥 5. Resep depo Rawat Jalan (08.00 - 14.00)
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data5[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[5]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                📋 6. PIO depo Rawat Inap
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah PIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data6[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[6]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🏥 7. PIO depo Rawat Jalan
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah PIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data7[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[7]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🧪 8. Resep Racikan Depo Rawat Inap
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Racikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data8[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[8]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                💉 9. Resep Racikan Depo Rawat Jalan
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Racikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data9[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[9]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🔄 10. Mutasi Masuk dari Gudang Obat ke Depo Rawat Jalan
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data10[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[10]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                📥 11. Penerimaan Barang ke Gudang Farmasi
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Penerimaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data11[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[11]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                📤 12. Stok Keluar Gudang Farmasi
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data12[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[12]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section-title">
                🔄 13. Mutasi dari Gudang Farmasi
            </div>
            <div class="table-responsive">
                <table class="skp-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">Tanggal</th>
                            <th style="text-align: right;">Jumlah Mutasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                        <tr>
                            <td class="date-cell"><?php echo date('d/m/Y', strtotime($tgl)); ?></td>
                            <td class="number-cell"><?php echo number_format($data13[$tgl]); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #343a40; color: white; font-weight: bold;">
                            <td style="text-align: center; padding: 12px;">TOTAL</td>
                            <td class="number-cell" style="color: white;"><?php echo number_format($total_data[13]); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            </div> <!-- Tutup allTables -->
            
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->

    <script>
    document.getElementById('copyTableBtn').onclick = function() {
        var allTables = document.getElementById('allTables');
        var range = document.createRange();
        range.selectNode(allTables);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showNotification('✅ Semua tabel berhasil disalin ke clipboard!', 'success');
            } else {
                showNotification('❌ Gagal menyalin tabel.', 'error');
            }
        } catch (err) {
            showNotification('❌ Browser tidak mendukung copy tabel otomatis.', 'error');
        }
        window.getSelection().removeAllRanges();
    };
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            font-weight: bold;
            transform: translateX(400px);
            transition: all 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }
    </script>
</body>
</html>