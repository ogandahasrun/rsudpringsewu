<?php
include 'koneksi.php';

// Filter tanggal: default tgl_keluar hari ini jika tidak difilter
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
$filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'tgl_keluar';

// Query dengan GROUP BY untuk menampilkan hanya 1 diagnosa per pasien
$where_condition = '';
if ($filter_type == 'tgl_masuk') {
    $where_condition = "kamar_inap.tgl_masuk BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
} else {
    $where_condition = "kamar_inap.tgl_keluar BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
}

$query = "SELECT
            kamar_inap.tgl_masuk,
            kamar_inap.tgl_keluar,
            kamar_inap.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            pasien.jk,
            poliklinik.nm_poli,
            kamar_inap.lama,
            diagnosa_pasien.kd_penyakit,
            penyakit.nm_penyakit
        FROM
            kamar_inap
        INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = kamar_inap.no_rawat
        LEFT JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
        LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
        WHERE
            kamar_inap.stts_pulang = 'Meninggal' AND
            $where_condition
        GROUP BY kamar_inap.no_rawat
        ORDER BY
            kamar_inap.tgl_keluar DESC";

$result = mysqli_query($koneksi, $query);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasien Meninggal Ranap</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        h1 { color: #4CAF50; text-align: center; margin-bottom: 24px; }
        .back-button { margin-bottom: 16px; }
        .back-button a { color: #fff; background: #6c757d; padding: 8px 16px; border-radius: 4px; text-decoration: none; }
        .back-button a:hover { background: #495057; }
        .filter-form { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-form input, .filter-form select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background: #45a049; }
        .export-button { margin-bottom: 15px; }
        .copy-btn { background: #2196F3; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; }
        .copy-btn:hover { background: #1976D2; }
        .table-container { max-height: 500px; overflow-y: auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; font-size: 13px; }
        th { background: #4CAF50; color: white; position: sticky; top: 0; z-index: 2; }
        tr:nth-child(even) { background: #f2f2f2; }
        tr:hover { background: #e3f2fd; }
        .no-data { text-align: center; color: #666; font-style: italic; margin: 20px 0; }
        @media (max-width: 700px) {
            .container { padding: 10px; }
            .filter-form { flex-direction: column; gap: 8px; align-items: stretch; }
            th, td { font-size: 12px; padding: 6px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pasien Meninggal Ranap</h1>
        
        <div class="back-button">
            <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
        </div>

        <form method="POST" class="filter-form">
            <label>Filter Berdasarkan:</label>
            <select name="filter_type">
                <option value="tgl_keluar" <?php if($filter_type == 'tgl_keluar') echo 'selected'; ?>>Tanggal Keluar</option>
                <option value="tgl_masuk" <?php if($filter_type == 'tgl_masuk') echo 'selected'; ?>>Tanggal Masuk</option>
            </select>
            
            <label>Periode:</label>
            <input type="date" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
            <span>s/d</span>
            <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
            <button type="submit" name="filter">Filter</button>
        </form>

        <div class="export-button">
            <button class="copy-btn" onclick="copyTableToClipboard('dataTable')">Copy ke Clipboard</button>
        </div>

        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Masuk</th>
                        <th>Tanggal Keluar</th>
                        <th>No Rawat</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>JK</th>
                        <th>Poliklinik</th>
                        <th>Lama Rawat</th>
                        <th>Kode Penyakit</th>
                        <th>Nama Penyakit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['tgl_masuk']); ?></td>
                                <td><?php echo htmlspecialchars($row['tgl_keluar']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                <td><?php echo htmlspecialchars($row['jk']); ?></td>
                                <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                <td style="text-align:right;"><?php echo htmlspecialchars($row['lama']); ?> hari</td>
                                <td><?php echo htmlspecialchars($row['kd_penyakit']); ?></td>
                                <td><?php echo htmlspecialchars($row['nm_penyakit']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="no-data">Tidak ada data pasien meninggal pada periode tersebut.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function copyTableToClipboard(tableID) {
        const table = document.getElementById(tableID);
        const textarea = document.createElement("textarea");
        let text = "";

        for (let row of table.rows) {
            let rowData = [];
            for (let cell of row.cells) {
                rowData.push(cell.innerText);
            }
            text += rowData.join("\t") + "\n";
        }

        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        alert("Data tabel telah disalin ke clipboard!");
    }
    </script>
</body>
</html>