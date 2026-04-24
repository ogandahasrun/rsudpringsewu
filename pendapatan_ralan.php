
<?php
include 'koneksi.php';
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
$png_jawab = isset($_GET['png_jawab']) ? $_GET['png_jawab'] : '';
$penjab_list = [];
$penjab_query = mysqli_query($koneksi, "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
while ($row = mysqli_fetch_assoc($penjab_query)) {
    $penjab_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan Rawat Jalan</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 98vw;
            width: 98vw;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: auto;
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
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
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
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus, .filter-group select:focus {
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
            min-width: 1600px;
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
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; gap: 15px; }
            .filter-actions { justify-content: stretch; }
            .btn { padding: 10px 15px; font-size: 13px; }
            th, td { padding: 8px 6px; font-size: 12px; }
            table { min-width: 720px; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.3em; }
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
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Pendapatan Rawat Jalan</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="keuangan.php">← Kembali ke Menu Keuangan</a>
            </div>
            <form method="get" class="filter-form">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Periode:</label>
                        <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal) ?>">
                        <span style="text-align:center;">s/d</span>
                        <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Penanggung Jawab:</label>
                        <select name="png_jawab">
                            <option value="">-- Semua --</option>
                            <?php foreach ($penjab_list as $pj): ?>
                                <option value="<?= htmlspecialchars($pj['png_jawab']) ?>" <?= $png_jawab == $pj['png_jawab'] ? 'selected' : '' ?>><?= htmlspecialchars($pj['png_jawab']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>

<?php
if ($tgl_awal && $tgl_akhir) {
    $filter_png_jawab = $png_jawab ? "AND penjab.png_jawab = '" . mysqli_real_escape_string($koneksi, $png_jawab) . "'" : '';
    $sql = "SELECT reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, reg_periksa.tgl_registrasi, reg_periksa.jam_reg, nota_jalan.no_nota, nota_jalan.tanggal, detail_nota_jalan.besar_bayar, penjab.png_jawab, piutang_pasien.sisapiutang FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis INNER JOIN nota_jalan ON nota_jalan.no_rawat = reg_periksa.no_rawat LEFT JOIN detail_nota_jalan ON detail_nota_jalan.no_rawat = reg_periksa.no_rawat INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj LEFT JOIN piutang_pasien ON piutang_pasien.no_rawat = reg_periksa.no_rawat WHERE ((reg_periksa.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir') OR (nota_jalan.tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir')) $filter_png_jawab ORDER BY reg_periksa.tgl_registrasi, reg_periksa.jam_reg";
    $result = mysqli_query($koneksi, $sql);
    echo '<div style="margin-bottom: 15px; display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 10px;">';
    echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
    echo '</div>';
    echo '<div class="table-responsive">';
    echo '<table>';
    echo '<tr><th>No</th><th>No Rawat</th><th>No RM</th><th>Nama Pasien</th><th>Tgl Registrasi</th><th>Jam</th><th>No Nota</th><th>Tanggal Nota</th><th>Besar Bayar</th><th>Sisa Piutang</th><th>Penanggung Jawab</th><th>Obat</th><th>PPN Obat</th><th>Total Tanpa Obat</th><th>Total</th><th>Status</th></tr>';
    $no = 1;
    $total_besar_bayar = 0;
    $total_sisapiutang = 0;
    $total_obat = 0;
    $total_ppn = 0;
    $total_tanpa_obat = 0;
    $total_semua = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $no_rawat = $row['no_rawat'];
        // Obat
        $q_obat = mysqli_query($koneksi, "SELECT SUM(totalbiaya) AS total FROM billing WHERE no_rawat = '$no_rawat' AND status = 'obat' AND nm_perawatan <> 'PPN Obat'");
        $obat = mysqli_fetch_assoc($q_obat)['total'] ?: 0;
        // PPN Obat
        $q_ppn = mysqli_query($koneksi, "SELECT SUM(totalbiaya) AS total FROM billing WHERE no_rawat = '$no_rawat' AND status = 'obat' AND nm_perawatan = 'PPN Obat'");
        $ppn = mysqli_fetch_assoc($q_ppn)['total'] ?: 0;
        // Total Tanpa Obat
        $q_tanpa_obat = mysqli_query($koneksi, "SELECT SUM(totalbiaya) AS total FROM billing WHERE no_rawat = '$no_rawat' AND status <> 'obat'");
        $tanpa_obat = mysqli_fetch_assoc($q_tanpa_obat)['total'] ?: 0;
        // Total
        $q_total = mysqli_query($koneksi, "SELECT SUM(totalbiaya) AS total FROM billing WHERE no_rawat = '$no_rawat'");
        $total = mysqli_fetch_assoc($q_total)['total'] ?: 0;
        // Penjumlahan total
        $total_besar_bayar += $row['besar_bayar'];
        $total_sisapiutang += $row['sisapiutang'];
        $total_obat += $obat;
        $total_ppn += $ppn;
        $total_tanpa_obat += $tanpa_obat;
        $total_semua += $total;
        // Status (contoh: Lunas/Belum Lunas)
        $status = ($row['besar_bayar'] >= $total) ? 'Lunas' : 'Belum Lunas';
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($row['no_rawat']) . '</td>';
        echo '<td>' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
        echo '<td>' . htmlspecialchars($row['tgl_registrasi']) . '</td>';
        echo '<td>' . htmlspecialchars($row['jam_reg']) . '</td>';
        echo '<td>' . htmlspecialchars($row['no_nota']) . '</td>';
        echo '<td>' . htmlspecialchars($row['tanggal']) . '</td>';
        echo '<td>' . number_format($row['besar_bayar'], 0, ',', '.') . '</td>';
        echo '<td>' . number_format($row['sisapiutang'], 0, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($row['png_jawab']) . '</td>';
        echo '<td>' . number_format($obat, 0, ',', '.') . '</td>';
        echo '<td>' . number_format($ppn, 0, ',', '.') . '</td>';
        echo '<td>' . number_format($tanpa_obat, 0, ',', '.') . '</td>';
        echo '<td>' . number_format($total, 0, ',', '.') . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    // Baris total
    echo '<tr style="background:#e9ecef; font-weight:bold;">';
    echo '<td colspan="8" style="text-align:right;">TOTAL</td>';
    echo '<td>' . number_format($total_besar_bayar, 0, ',', '.') . '</td>';
    echo '<td>' . number_format($total_sisapiutang, 0, ',', '.') . '</td>';
    echo '<td></td>';
    echo '<td>' . number_format($total_obat, 0, ',', '.') . '</td>';
    echo '<td>' . number_format($total_ppn, 0, ',', '.') . '</td>';
    echo '<td>' . number_format($total_tanpa_obat, 0, ',', '.') . '</td>';
    echo '<td>' . number_format($total_semua, 0, ',', '.') . '</td>';
    echo '<td></td>';
    echo '</tr>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="no-data">Silakan pilih filter periode terlebih dahulu.</div>';
}
?>
        </div>
    </div>
</body>
</html>
