<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep Pasien Rawat Jalan - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
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
            .filter-title { font-size: 16px; }
        }
    </style>
    <script>
        function copyNoSEP(no_sep) {
            if (!no_sep) return;
            navigator.clipboard.writeText(no_sep).then(function() {
                alert('No. SEP berhasil disalin: ' + no_sep);
            }, function(err) {
                alert('Gagal menyalin No. SEP');
            });
        }
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
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 Resep Pasien Rawat Jalan</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>

<?php
include 'koneksi.php';

$tanggal_hari_ini = date('Y-m-d');
$tanggal_awal = $tanggal_hari_ini;
$tanggal_akhir = $tanggal_hari_ini;
if (isset($_POST['filter'])) {
    $tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
    $tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);
}
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Resep Pasien Rawat Jalan
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Registrasi Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Registrasi Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">📊 Tampilkan Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset Filter</button>
                </div>
            </form>

<?php
$query = "SELECT
            resep_obat.tgl_perawatan,
            resep_obat.no_resep,
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            resep_dokter.kode_brng,
            databarang.nama_brng,
            resep_dokter.jml,
            databarang.kode_sat,
            resep_dokter.aturan_pakai,
            dokter.nm_dokter,
            bridging_sep.no_sep
        FROM
            resep_obat
            INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
            INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
            INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
            LEFT JOIN bridging_sep ON bridging_sep.no_rawat = reg_periksa.no_rawat
        WHERE
            resep_obat.`status` = 'ralan' AND
            reg_periksa.kd_poli NOT IN ('IGDK','HDL') AND
            resep_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        ORDER BY resep_obat.tgl_perawatan, resep_obat.no_resep";

$result = mysqli_query($koneksi, $query);
$data_grouped = [];
while ($row = mysqli_fetch_assoc($result)) {
    $no_resep = $row['no_resep'];
    if (!isset($data_grouped[$no_resep])) {
        $data_grouped[$no_resep] = [
            'tgl_perawatan' => $row['tgl_perawatan'],
            'no_rawat'      => $row['no_rawat'],
            'no_rkm_medis'  => $row['no_rkm_medis'],
            'nm_pasien'     => $row['nm_pasien'],
            'nm_dokter'     => $row['nm_dokter'],
            'no_sep'        => $row['no_sep'],
            'obat'          => []
        ];
    }
    $data_grouped[$no_resep]['obat'][] = [
        'kode_brng'    => $row['kode_brng'],
        'nama_brng'    => $row['nama_brng'],
        'jml'          => $row['jml'],
        'kode_sat'     => $row['kode_sat'],
        'aturan_pakai' => $row['aturan_pakai']
    ];
}
if (count($data_grouped) > 0) {
    echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
    echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . count($data_grouped) . '</span> resep</div>';
    echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
    echo '</div>';
    echo "<div class='table-responsive'><table>\n
            <tr>
                <th>NO</th>
                <th>TANGGAL</th>
                <th>NO RESEP</th>
                <th>NO RAWAT</th>
                <th>NO RM</th>
                <th>NAMA PASIEN</th>
                <th>DOKTER</th>
                <th>NO SEP</th>
                <th>KODE BARANG</th>
                <th>NAMA BARANG</th>
                <th>JUMLAH</th>
                <th>SATUAN</th>
                <th>ATURAN PAKAI</th>
            </tr>";
    $no = 1;
    foreach ($data_grouped as $no_resep => $data) {
        $first_row = true;
        foreach ($data['obat'] as $obat) {
            echo "<tr>";
            if ($first_row) {
                $rowspan = count($data['obat']);
                echo "<td rowspan='$rowspan' style='vertical-align: top;'>$no</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['tgl_perawatan']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>$no_resep</td>
                    <td rowspan='$rowspan' style='vertical-align: top; cursor:pointer; color:#007bff; text-decoration:underline' onclick=\"copyNoSEP('{$data['no_sep']}')\" title='Klik untuk copy No. SEP'>{$data['no_rawat']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['no_rkm_medis']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['nm_pasien']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['nm_dokter']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top; cursor:pointer; color:#007bff; text-decoration:underline' onclick=\"copyNoSEP('{$data['no_sep']}')\" title='Klik untuk copy No. SEP'>{$data['no_sep']}</td>";
                $first_row = false;
                $no++;
            }
            echo "<td>{$obat['kode_brng']}</td>
                  <td>{$obat['nama_brng']}</td>
                  <td>{$obat['jml']}</td>
                  <td>{$obat['kode_sat']}</td>
                  <td>{$obat['aturan_pakai']}</td>";
            echo "</tr>";
        }
    }
    echo "</table></div>";
} else {
    echo '<div class="no-data">📋 Tidak ada data resep pada filter yang dipilih</div>';
}
mysqli_close($koneksi);
?>
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
