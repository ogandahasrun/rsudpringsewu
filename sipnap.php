<?php
// filepath: c:\xampp\htdocs\rsudpringsewu\sipnap.php
include 'koneksi.php';

// Ambil tanggal filter, default hari ini jika belum dipilih
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// 1. Query utama: ambil data barang golongan NK dan PSI
$query_barang = "SELECT kode_brng, nama_brng, kode_sat, kode_golongan FROM databarang WHERE kode_golongan IN ('NK','PSI') ORDER BY kode_golongan ASC";
$result_barang = mysqli_query($koneksi, $query_barang);
$barang_list = [];
while ($row = mysqli_fetch_assoc($result_barang)) {
    $barang_list[$row['kode_brng']] = [
        'nama_brng' => $row['nama_brng'],
        'kode_sat' => $row['kode_sat'],
        'kode_golongan' => $row['kode_golongan']
    ];
}

// 2. Stok awal per bangsal (ambil stok pada tanggal dan jam paling awal di periode untuk setiap barang)
$stok_awal = [];
$bangsals = ['GO','DRI','AP','DI','DO','DPED'];
foreach ($bangsals as $bangsal) {
    $stok_awal[$bangsal] = [];
    foreach ($barang_list as $kode_brng => $barang) {
        // Ambil tanggal dan jam paling awal untuk barang dan bangsal ini dalam periode
        $q_min = "SELECT tanggal FROM riwayat_barang_medis 
                  WHERE kd_bangsal = '$bangsal' 
                    AND kode_brng = '$kode_brng'
                    AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                  ORDER BY tanggal ASC LIMIT 1";
        $r_min = mysqli_query($koneksi, $q_min);
        $min_row = mysqli_fetch_assoc($r_min);
        
        if ($min_row) {
            $min_tanggal = $min_row['tanggal'];
            // Ambil stok_awal pada tanggal dan jam paling awal dalam periode
            $q_stok = "SELECT stok_awal FROM riwayat_barang_medis 
                       WHERE kd_bangsal = '$bangsal' 
                         AND kode_brng = '$kode_brng'
                         AND tanggal = '$min_tanggal'
                       LIMIT 1";
            $r_stok = mysqli_query($koneksi, $q_stok);
            $stok_row = mysqli_fetch_assoc($r_stok);
            $stok_awal[$bangsal][$kode_brng] = $stok_row ? $stok_row['stok_awal'] : 0;
        } else {
            // Jika tidak ada data dalam periode, ambil stok_akhir dari transaksi terakhir sebelum periode
            $q_prev = "SELECT stok_akhir FROM riwayat_barang_medis 
                       WHERE kd_bangsal = '$bangsal' 
                         AND kode_brng = '$kode_brng'
                         AND tanggal < '$tanggal_awal'
                       ORDER BY tanggal DESC LIMIT 1";
            $r_prev = mysqli_query($koneksi, $q_prev);
            $prev_row = mysqli_fetch_assoc($r_prev);
            $stok_awal[$bangsal][$kode_brng] = $prev_row ? $prev_row['stok_akhir'] : 0;
        }
    }
}

// 3. Barang masuk
// a. Penerimaan
$penerimaan = [];
$q = "SELECT detailpesan.kode_brng, SUM(detailpesan.jumlah) as jumlah FROM pemesanan
INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY detailpesan.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error penerimaan: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $penerimaan[$row['kode_brng']] = $row['jumlah'];
}

// b. Hibah
$hibah = [];
$q = "SELECT detailhibah_obat_bhp.kode_brng, SUM(detailhibah_obat_bhp.jumlah) as jumlah FROM hibah_obat_bhp
INNER JOIN detailhibah_obat_bhp ON detailhibah_obat_bhp.no_hibah = hibah_obat_bhp.no_hibah
INNER JOIN databarang ON detailhibah_obat_bhp.kode_brng = databarang.kode_brng
WHERE hibah_obat_bhp.tgl_hibah BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND databarang.kode_golongan IN ('NK','PSI')
GROUP BY detailhibah_obat_bhp.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error hibah: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $hibah[$row['kode_brng']] = $row['jumlah'];
}

// c. Retur pasien
$retur = [];
$q = "SELECT detreturjual.kode_brng, SUM(detreturjual.jml_retur) as jumlah FROM returjual
INNER JOIN detreturjual ON detreturjual.no_retur_jual = returjual.no_retur_jual
INNER JOIN databarang ON detreturjual.kode_brng = databarang.kode_brng
WHERE returjual.tgl_retur BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND databarang.kode_golongan IN ('NK','PSI')
GROUP BY detreturjual.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error retur: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $retur[$row['kode_brng']] = $row['jumlah'];
}

// 4. Barang keluar
// a. Pemberian obat
$pemberian = [];
$q = "SELECT detail_pemberian_obat.kode_brng, SUM(detail_pemberian_obat.jml) as jumlah FROM detail_pemberian_obat
INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY detail_pemberian_obat.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error pemberian: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $pemberian[$row['kode_brng']] = $row['jumlah'];
}

// b. Resep pulang
$resep_pulang = [];
$q = "SELECT resep_pulang.kode_brng, SUM(resep_pulang.jml_barang) as jumlah FROM resep_pulang
INNER JOIN databarang ON resep_pulang.kode_brng = databarang.kode_brng
WHERE databarang.kode_golongan IN ('NK','PSI') AND resep_pulang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY resep_pulang.kode_brng";
$r = mysqli_query($koneksi, $q);
if (!$r) die("Query error resep_pulang: " . mysqli_error($koneksi));
while ($row = mysqli_fetch_assoc($r)) {
    $resep_pulang[$row['kode_brng']] = $row['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPNAP - RSUD Pringsewu</title>
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
        .info-summary {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
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
            font-size: 12px;
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
        .number-cell {
            text-align: right;
            font-weight: bold;
            font-family: monospace;
        }
        .code-cell {
            font-family: monospace;
            font-weight: bold;
            text-align: left;
        }
        .name-cell {
            text-align: left;
            font-weight: bold;
        }
        
        /* Warna khusus untuk kolom total */
        .total-stok-awal {
            background: linear-gradient(45deg, #e3f2fd, #bbdefb) !important;
            color: #1565c0;
            font-weight: bold;
        }
        .total-masuk {
            background: linear-gradient(45deg, #e8f5e8, #c8e6c8) !important;
            color: #2e7d32;
            font-weight: bold;
        }
        .total-keluar {
            background: linear-gradient(45deg, #ffebee, #ffcdd2) !important;
            color: #c62828;
            font-weight: bold;
        }
        .stok-akhir {
            background: linear-gradient(45deg, #fff3e0, #ffe0b2) !important;
            color: #ef6c00;
            font-weight: bold;
        }
        
        /* Header untuk kolom total */
        th.total-stok-awal-header {
            background: linear-gradient(45deg, #1976d2, #42a5f5) !important;
        }
        th.total-masuk-header {
            background: linear-gradient(45deg, #388e3c, #66bb6a) !important;
        }
        th.total-keluar-header {
            background: linear-gradient(45deg, #d32f2f, #f44336) !important;
        }
        th.stok-akhir-header {
            background: linear-gradient(45deg, #f57c00, #ff9800) !important;
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
            .info-summary {
                flex-direction: column;
                text-align: center;
            }
            th, td {
                padding: 6px 4px;
                font-size: 11px;
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
    </style>
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
                    showNotification("✅ Tabel berhasil disalin ke clipboard!", "success");
                } catch(err) {
                    showNotification("❌ Gagal menyalin tabel", "error");
                }
                window.getSelection().removeAllRanges();
            }
        }
        
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
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 SIPNAP (Sistem Informasi Pemakaian Narkotika & Psikotropika)</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">
                    ← Kembali ke Menu Farmasi
                </a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Laporan SIPNAP
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
            // Hitung statistik untuk summary
            $total_barang = count($barang_list);
            $total_stok_awal_semua = 0;
            $total_masuk_semua = 0;
            $total_keluar_semua = 0;
            $total_stok_akhir_semua = 0;
            
            foreach ($barang_list as $kode_brng => $barang) {
                // Stok awal per bangsal
                $go = isset($stok_awal['GO'][$kode_brng]) ? $stok_awal['GO'][$kode_brng] : 0;
                $dri = isset($stok_awal['DRI'][$kode_brng]) ? $stok_awal['DRI'][$kode_brng] : 0;
                $ap = isset($stok_awal['AP'][$kode_brng]) ? $stok_awal['AP'][$kode_brng] : 0;
                $di = isset($stok_awal['DI'][$kode_brng]) ? $stok_awal['DI'][$kode_brng] : 0;
                $do = isset($stok_awal['DO'][$kode_brng]) ? $stok_awal['DO'][$kode_brng] : 0;
                $de = isset($stok_awal['DPED'][$kode_brng]) ? $stok_awal['DPED'][$kode_brng] : 0;
                $total_stok_awal = $go + $dri + $ap + $di + $do + $de;

                // Barang masuk
                $masuk_penerimaan = isset($penerimaan[$kode_brng]) ? $penerimaan[$kode_brng] : 0;
                $masuk_hibah = isset($hibah[$kode_brng]) ? $hibah[$kode_brng] : 0;
                $masuk_retur = isset($retur[$kode_brng]) ? $retur[$kode_brng] : 0;
                $total_masuk = $masuk_penerimaan + $masuk_hibah + $masuk_retur;

                // Barang keluar
                $keluar_pemberian = isset($pemberian[$kode_brng]) ? $pemberian[$kode_brng] : 0;
                $keluar_resep = isset($resep_pulang[$kode_brng]) ? $resep_pulang[$kode_brng] : 0;
                $total_keluar = $keluar_pemberian + $keluar_resep;

                // Stok akhir
                $stok_akhir = ($total_stok_awal + $total_masuk) - $total_keluar;
                
                $total_stok_awal_semua += $total_stok_awal;
                $total_masuk_semua += $total_masuk;
                $total_keluar_semua += $total_keluar;
                $total_stok_akhir_semua += $stok_akhir;
            }
            ?>

            <div class="info-summary">
                <div class="summary-item">
                    <div class="label">📦 Total Barang</div>
                    <div class="value"><?php echo number_format($total_barang); ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">📊 Total Stok Awal</div>
                    <div class="value" style="color: #1565c0;"><?php echo number_format($total_stok_awal_semua); ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">📥 Total Masuk</div>
                    <div class="value" style="color: #2e7d32;"><?php echo number_format($total_masuk_semua); ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">📤 Total Keluar</div>
                    <div class="value" style="color: #c62828;"><?php echo number_format($total_keluar_semua); ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">📋 Stok Akhir</div>
                    <div class="value" style="color: #ef6c00;"><?php echo number_format($total_stok_akhir_semua); ?></div>
                </div>
            </div>

            <div class="actions-bar">
                <div style="color: #6c757d; font-size: 14px;">
                    📋 <strong>Periode:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>
                </div>
                <button onclick="copyTableData()" class="btn btn-success">
                    📋 Copy Tabel
                </button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Golongan</th>
                            <th>Stok Awal GO</th>
                            <th>Stok Awal DRI</th>
                            <th>Stok Awal AP</th>
                            <th>Stok Awal DI</th>
                            <th>Stok Awal DO</th>
                            <th>Stok Awal DPED</th>
                            <th class="total-stok-awal-header">Total Stok Awal</th>
                            <th>Penerimaan</th>
                            <th>Hibah</th>
                            <th>Retur Pasien</th>
                            <th class="total-masuk-header">Total Barang Masuk</th>
                            <th>Pemberian Obat</th>
                            <th>Resep Pulang</th>
                            <th class="total-keluar-header">Total Barang Keluar</th>
                            <th class="stok-akhir-header">Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
            $no = 1;
foreach ($barang_list as $kode_brng => $barang) {
    // Stok awal per bangsal
    $go = isset($stok_awal['GO'][$kode_brng]) ? $stok_awal['GO'][$kode_brng] : 0;
    $dri = isset($stok_awal['DRI'][$kode_brng]) ? $stok_awal['DRI'][$kode_brng] : 0;
    $ap = isset($stok_awal['AP'][$kode_brng]) ? $stok_awal['AP'][$kode_brng] : 0;
    $di = isset($stok_awal['DI'][$kode_brng]) ? $stok_awal['DI'][$kode_brng] : 0;
    $do = isset($stok_awal['DO'][$kode_brng]) ? $stok_awal['DO'][$kode_brng] : 0;
    $de = isset($stok_awal['DPED'][$kode_brng]) ? $stok_awal['DPED'][$kode_brng] : 0;
    $total_stok_awal = $go + $dri + $ap + $di + $do + $de;

    // Barang masuk
    $masuk_penerimaan = isset($penerimaan[$kode_brng]) ? $penerimaan[$kode_brng] : 0;
    $masuk_hibah = isset($hibah[$kode_brng]) ? $hibah[$kode_brng] : 0;
    $masuk_retur = isset($retur[$kode_brng]) ? $retur[$kode_brng] : 0;
    $total_masuk = $masuk_penerimaan + $masuk_hibah + $masuk_retur;

    // Barang keluar
    $keluar_pemberian = isset($pemberian[$kode_brng]) ? $pemberian[$kode_brng] : 0;
    $keluar_resep = isset($resep_pulang[$kode_brng]) ? $resep_pulang[$kode_brng] : 0;
    $total_keluar = $keluar_pemberian + $keluar_resep;

    // Stok akhir (perbaikan rumus)
    $stok_akhir = ($total_stok_awal + $total_masuk) - $total_keluar;

    echo "<tr>
        <td style='font-weight: bold;'>$no</td>
        <td class='code-cell'>$kode_brng</td>
        <td class='name-cell'>{$barang['nama_brng']}</td>
        <td style='text-align: center; font-weight: bold;'>{$barang['kode_sat']}</td>
        <td style='text-align: center; font-weight: bold; color: #007bff;'>{$barang['kode_golongan']}</td>
        <td class='number-cell'>" . number_format($go) . "</td>
        <td class='number-cell'>" . number_format($dri) . "</td>
        <td class='number-cell'>" . number_format($ap) . "</td>
        <td class='number-cell'>" . number_format($di) . "</td>
        <td class='number-cell'>" . number_format($do) . "</td>
        <td class='number-cell'>" . number_format($de) . "</td>
        <td class='number-cell total-stok-awal'>" . number_format($total_stok_awal) . "</td>
        <td class='number-cell'>" . number_format($masuk_penerimaan) . "</td>
        <td class='number-cell'>" . number_format($masuk_hibah) . "</td>
        <td class='number-cell'>" . number_format($masuk_retur) . "</td>
        <td class='number-cell total-masuk'>" . number_format($total_masuk) . "</td>
        <td class='number-cell'>" . number_format($keluar_pemberian) . "</td>
        <td class='number-cell'>" . number_format($keluar_resep) . "</td>
        <td class='number-cell total-keluar'>" . number_format($total_keluar) . "</td>
        <td class='number-cell stok-akhir'>" . number_format($stok_akhir) . "</td>
    </tr>";
    $no++;
}
            ?>
                    </tbody>
                </table>
            </div>
            
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>