<?php
include 'koneksi.php';

// Ambil tanggal filter dari POST atau GET
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : (isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d'));
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : (isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d'));

// Validasi format tanggal YYYY-MM-DD
$tanggal_awal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_awal) ? $tanggal_awal : date('Y-m-d');
$tanggal_akhir = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir) ? $tanggal_akhir : date('Y-m-d');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// 1. EXPORT REKAP EXCEL (.XLS)
if ($action === 'export_rekap_excel') {
    $filename = "Rekap_RL318_" . date('Ymd', strtotime($tanggal_awal)) . "_sd_" . date('Ymd', strtotime($tanggal_akhir)) . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $query = "SELECT
                industrifarmasi.nama_industri,
                COUNT(detail_pemberian_obat.kode_brng) AS jumlah_resep
            FROM
                detail_pemberian_obat
            INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
            INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
            WHERE
                databarang.kode_kategori = 'OBT' AND
                detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
            GROUP BY
                industrifarmasi.nama_industri
            ORDER BY
                industrifarmasi.nama_industri ASC
    ";
    $result = mysqli_query($koneksi, $query);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; font-family: Calibri, Tahoma, Arial, sans-serif; font-size: 11pt; }
            th { background-color: #28a745; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #333333; text-align: left; }
            td { padding: 8px 10px; border: 1px solid #cccccc; vertical-align: middle; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>RSUD PRINGSEWU</h2>
        <h3>LAPORAN RL 3.18 FARMASI RESEP</h3>
        <p>
            Periode: <b><?php echo date('d/m/Y', strtotime($tanggal_awal)); ?></b> s/d <b><?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?></b><br>
            Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama Industri Farmasi</th>
                    <th style="width: 160px;" class="text-right">Jumlah Resep</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_resep = 0;
                while ($row = mysqli_fetch_assoc($result)) {
                    $total_resep += $row['jumlah_resep'];
                    echo '<tr>';
                    echo '<td class="text-center">' . $no++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_industri']) . '</td>';
                    echo '<td class="text-right" style="mso-number-format:\'#,##0\';">' . $row['jumlah_resep'] . '</td>';
                    echo '</tr>';
                }
                ?>
                <tr class="bold" style="background-color: #e9ecef;">
                    <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
                    <td class="text-right" style="mso-number-format:'#,##0';"><?php echo $total_resep; ?></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// 2. EXPORT DETAIL EXCEL (.XLS)
if ($action === 'export_detail_excel') {
    $filename = "Detail_Pendukung_RL318_" . date('Ymd', strtotime($tanggal_awal)) . "_sd_" . date('Ymd', strtotime($tanggal_akhir)) . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $query_detail = "SELECT
                detail_pemberian_obat.tgl_perawatan,
                detail_pemberian_obat.jam,
                detail_pemberian_obat.no_rawat,
                reg_periksa.no_rkm_medis,
                pasien.nm_pasien,
                detail_pemberian_obat.status AS status_pelayanan,
                detail_pemberian_obat.kode_brng,
                databarang.nama_brng,
                detail_pemberian_obat.jml,
                databarang.kode_sat,
                industrifarmasi.nama_industri,
                detail_pemberian_obat.no_batch,
                detail_pemberian_obat.no_faktur
            FROM
                detail_pemberian_obat
            INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
            INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
            LEFT JOIN reg_periksa ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat
            LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            WHERE
                databarang.kode_kategori = 'OBT' AND
                detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
            ORDER BY
                detail_pemberian_obat.tgl_perawatan ASC,
                detail_pemberian_obat.jam ASC
    ";
    $res_detail = mysqli_query($koneksi, $query_detail);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            table { border-collapse: collapse; font-family: Calibri, Tahoma, Arial, sans-serif; font-size: 10pt; }
            th { background-color: #0f766e; color: #ffffff; font-weight: bold; padding: 8px; border: 1px solid #333333; text-align: center; }
            td { padding: 6px 8px; border: 1px solid #cccccc; vertical-align: middle; }
            .text { mso-number-format: "\@"; }
            .num { mso-number-format: "#,##0"; text-align: right; }
            .center { text-align: center; }
        </style>
    </head>
    <body>
        <h2>RSUD PRINGSEWU</h2>
        <h3>DATA DETAIL PENDUKUNG RL 3.18 FARMASI RESEP</h3>
        <p>
            Periode Perawatan: <b><?php echo date('d/m/Y', strtotime($tanggal_awal)); ?></b> s/d <b><?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?></b><br>
            Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
        </p>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Perawatan</th>
                    <th>Jam</th>
                    <th>No. Rawat</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Status Pelayanan</th>
                    <th>Kode Obat</th>
                    <th>Nama Obat</th>
                    <th>Jumlah</th>
                    <th>Satuan</th>
                    <th>Nama Industri Farmasi</th>
                    <th>No. Batch</th>
                    <th>No. Faktur</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($res_detail)) {
                    echo '<tr>';
                    echo '<td class="center">' . $no++ . '</td>';
                    echo '<td class="center">' . $row['tgl_perawatan'] . '</td>';
                    echo '<td class="center">' . $row['jam'] . '</td>';
                    echo '<td class="text">' . $row['no_rawat'] . '</td>';
                    echo '<td class="text">' . $row['no_rkm_medis'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
                    echo '<td class="center">' . $row['status_pelayanan'] . '</td>';
                    echo '<td class="text">' . $row['kode_brng'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_brng']) . '</td>';
                    echo '<td class="num">' . $row['jml'] . '</td>';
                    echo '<td class="center">' . htmlspecialchars($row['kode_sat']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nama_industri']) . '</td>';
                    echo '<td class="text">' . htmlspecialchars($row['no_batch']) . '</td>';
                    echo '<td class="text">' . htmlspecialchars($row['no_faktur']) . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// 3. EXPORT DETAIL CSV (.CSV)
if ($action === 'export_detail_csv') {
    $filename = "Detail_Pendukung_RL318_" . date('Ymd', strtotime($tanggal_awal)) . "_sd_" . date('Ymd', strtotime($tanggal_akhir)) . ".csv";
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $output = fopen('php://output', 'w');
    // UTF-8 BOM untuk kompatibilitas Microsoft Excel
    fwrite($output, "\xEF\xBB\xBF");

    // Header CSV (Delimiter titik koma ';' sesuai format Excel Windows)
    fputcsv($output, [
        'No',
        'Tanggal Perawatan',
        'Jam',
        'No Rawat',
        'No RM',
        'Nama Pasien',
        'Status Pelayanan',
        'Kode Obat',
        'Nama Obat',
        'Jumlah',
        'Satuan',
        'Nama Industri Farmasi',
        'No Batch',
        'No Faktur'
    ], ';');

    $query_detail = "SELECT
                detail_pemberian_obat.tgl_perawatan,
                detail_pemberian_obat.jam,
                detail_pemberian_obat.no_rawat,
                reg_periksa.no_rkm_medis,
                pasien.nm_pasien,
                detail_pemberian_obat.status AS status_pelayanan,
                detail_pemberian_obat.kode_brng,
                databarang.nama_brng,
                detail_pemberian_obat.jml,
                databarang.kode_sat,
                industrifarmasi.nama_industri,
                detail_pemberian_obat.no_batch,
                detail_pemberian_obat.no_faktur
            FROM
                detail_pemberian_obat
            INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
            INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
            LEFT JOIN reg_periksa ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat
            LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            WHERE
                databarang.kode_kategori = 'OBT' AND
                detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
            ORDER BY
                detail_pemberian_obat.tgl_perawatan ASC,
                detail_pemberian_obat.jam ASC
    ";
    $res_detail = mysqli_query($koneksi, $query_detail);
    $no = 1;
    while ($row = mysqli_fetch_assoc($res_detail)) {
        fputcsv($output, [
            $no++,
            $row['tgl_perawatan'],
            $row['jam'],
            $row['no_rawat'],
            $row['no_rkm_medis'],
            $row['nm_pasien'],
            $row['status_pelayanan'],
            $row['kode_brng'],
            $row['nama_brng'],
            $row['jml'],
            $row['kode_sat'],
            $row['nama_industri'],
            $row['no_batch'],
            $row['no_faktur']
        ], ';');
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.18 Farmasi Resep - RSUD Pringsewu</title>
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
        .btn-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
        .btn-info:hover {
            background: #117a8b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
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
            min-width: 900px;
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
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
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
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 720px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 RL 3.18 Farmasi Resep</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>

<?php
$query = "SELECT
            industrifarmasi.nama_industri,
            COUNT(detail_pemberian_obat.kode_brng) AS jumlah_resep
        FROM
            detail_pemberian_obat
        INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
        INNER JOIN industrifarmasi ON databarang.kode_industri = industrifarmasi.kode_industri
        WHERE
            databarang.kode_kategori = 'OBT' AND
            detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        GROUP BY
            industrifarmasi.nama_industri
        ORDER BY
            industrifarmasi.nama_industri ASC
";

$result = mysqli_query($koneksi, $query);
$total_industri = $result ? mysqli_num_rows($result) : 0;
?>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Tanggal Resep
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

            <?php if ($result && $total_industri > 0): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <strong style="font-size: 15px; color: #1e293b; display: block;">📂 Opsi Export & Lampiran Data Pendukung</strong>
                            <span style="font-size: 13px; color: #64748b;">Unduh rekapitulasi atau seluruh rincian transaksi pendukung (audit trail) untuk periode ini.</span>
                        </div>
                        <div class="filter-actions" style="margin: 0; justify-content: flex-end;">
                            <button type="button" onclick="copyTableData()" class="btn btn-secondary" title="Salin tabel rekap ke clipboard">
                                📋 Copy Tabel
                            </button>
                            <a href="?action=export_rekap_excel&tanggal_awal=<?php echo urlencode($tanggal_awal); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="btn btn-success" title="Download ringkasan per industri dalam format Excel (.xls)">
                                📊 Export Rekap (.xls)
                            </a>
                            <a href="?action=export_detail_excel&tanggal_awal=<?php echo urlencode($tanggal_awal); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="btn btn-primary" title="Download rincian lengkap transaksi pasien & obat format Excel (.xls)">
                                📥 Export Detail (.xls)
                            </a>
                            <a href="?action=export_detail_csv&tanggal_awal=<?php echo urlencode($tanggal_awal); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="btn btn-info" title="Download rincian transaksi format CSV (Sangat Ringan & Cepat)">
                                📄 Export Detail (.csv)
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="tabel-farmasi">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Industri</th>
                                <th style="text-align: right; width: 180px;">Jumlah Resep</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $total_resep = 0;
                            while ($row = mysqli_fetch_assoc($result)): 
                                $total_resep += $row['jumlah_resep'];
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_industri']); ?></td>
                                    <td style="text-align: right; font-weight: 500;"><?php echo number_format($row['jumlah_resep']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #e9ecef; font-weight: bold; border-top: 2px solid #adb5bd;">
                                <td colspan="2" style="text-align: center;">TOTAL KESELURUHAN</td>
                                <td style="text-align: right;"><?php echo number_format($total_resep); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 18px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3; font-size: 13px; line-height: 1.6;">
                    <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                    <br>
                    <strong>📋 Total Industri Farmasi:</strong> <?php echo number_format($total_industri); ?> industri
                    <br>
                    <strong>💊 Total Rincian Resep (R/):</strong> <?php echo number_format($total_resep); ?> transaksi
                    <hr style="border: none; border-top: 1px dashed #90caf9; margin: 12px 0;">
                    <div style="color: #0d47a1;">
                        💡 <strong>Data Pendukung Audit:</strong> Gunakan tombol <strong>Export Detail (.xls)</strong> atau <strong>Export Detail (.csv)</strong> di atas untuk mengarsipkan rincian seluruh transaksi obat per pasien sebagai bukti fisik laporan. Jika di kemudian hari terdapat perubahan angka rekapitulasi pada periode ini, file detail tersebut dapat langsung dijadikan rujukan pembanding (*audit trail*).
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data ditemukan untuk periode yang dipilih
                    <br>
                    <small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>

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