<?php
include "koneksi.php";
// Default filter
$tgl_peresepan = isset($_GET['tgl_peresepan']) ? $_GET['tgl_peresepan'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk no_rawat yang punya kode_brng > 1 pada tgl_peresepan yang dipilih
$subquery = "SELECT no_rawat FROM resep_obat 
    INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
    WHERE resep_obat.tgl_peresepan = '$tgl_peresepan' 
    GROUP BY no_rawat, kode_brng HAVING COUNT(kode_brng) > 1";

$filter_status = $status ? "AND resep_obat.status = '" . mysqli_real_escape_string($koneksi, $status) . "'" : "";


// Cari pasangan no_rawat dan kode_brng yang muncul lebih dari 1 kali pada tgl_peresepan yang dipilih
$rawat_barang_ganda = [];
$q_ganda = mysqli_query($koneksi, "SELECT no_rawat, kode_brng FROM resep_obat INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep WHERE resep_obat.tgl_peresepan = '$tgl_peresepan' GROUP BY no_rawat, kode_brng HAVING COUNT(*) > 1");
while ($row = mysqli_fetch_assoc($q_ganda)) {
    $rawat_barang_ganda[] = $row['no_rawat'] . '|' . $row['kode_brng'];
}

$query = "SELECT
    resep_obat.tgl_peresepan,
    reg_periksa.no_rawat,
    pasien.no_rkm_medis,
    pasien.nm_pasien,
    resep_obat.no_resep,
    resep_dokter.kode_brng,
    databarang.nama_brng,
    databarang.kode_sat,
    resep_dokter.jml,
    resep_dokter.aturan_pakai,
    resep_obat.status
FROM
    resep_obat
    INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
    INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
    INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
WHERE
    resep_obat.tgl_peresepan = '$tgl_peresepan'
    $filter_status
ORDER BY resep_obat.tgl_peresepan DESC, resep_obat.no_rawat, resep_obat.no_resep";

$result = mysqli_query($koneksi, $query);

// Ambil status unik untuk filter
$status_list = [];
$status_query = mysqli_query($koneksi, "SELECT DISTINCT status FROM resep_obat");
while ($row = mysqli_fetch_assoc($status_query)) {
    $status_list[] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontrol Resep</title>
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
            background: linear-gradient(45deg, #007bff, #0056b3);
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
        .filter-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }
        .filter-box label {
            font-weight: 500;
            color: #333;
        }
        .filter-box select, .filter-box input[type="date"] {
            padding: 7px 12px;
            border: 1px solid #b6d4fe;
            border-radius: 6px;
            background: #f5faff;
            font-size: 15px;
            color: #0056b3;
            outline: none;
            transition: border 0.2s;
        }
        .filter-box select:focus, .filter-box input[type="date"]:focus {
            border-color: #007bff;
        }
        .btn {
            padding: 7px 22px;
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0,123,255,0.08);
            transition: background 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #0056b3 0%, #007bff 100%);
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
        .info {
            margin-top: 24px;
            color: #555;
            font-size: 15px;
            background: #e3f0ff;
            padding: 12px 18px;
            border-radius: 8px;
        }
        @media (max-width: 700px) {
            .container { padding: 12px 4px; }
            table th, table td { padding: 7px 6px; font-size: 13px; }
            .filter-box { gap: 8px; }
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
            <h1>🩺 Kontrol Resep Pasien</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>
            <form method="get" class="filter-box">
                <label for="tgl_peresepan">Tanggal Peresepan:</label>
                <input type="date" id="tgl_peresepan" name="tgl_peresepan" value="<?= htmlspecialchars($tgl_peresepan) ?>">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    <?php foreach ($status_list as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $status == $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Tampilkan</button>
            </form>
            <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="font-weight: bold; color: #495057;">📊 Data Resep Ganda</div>
                <button onclick="copyTableData()" class="btn" style="background: linear-gradient(45deg, #28a745, #20c997);">📋 Copy Tabel</button>
            </div>
            <div class="table-responsive">
            <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>No Resep</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kode Satuan</th>
                <th>Jumlah</th>
                <th>Aturan Pakai</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // ...existing code...
        $no = 1;
        if ($result && mysqli_num_rows($result) > 0):
            while ($row = mysqli_fetch_assoc($result)):
                $key = $row['no_rawat'] . '|' . $row['kode_brng'];
                if (in_array($key, $rawat_barang_ganda)):
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['no_rawat']) ?></td>
                <td><?= htmlspecialchars($row['no_rkm_medis']) ?></td>
                <td><?= htmlspecialchars($row['nm_pasien']) ?></td>
                <td><?= htmlspecialchars($row['no_resep']) ?></td>
                <td><?= htmlspecialchars($row['kode_brng']) ?></td>
                <td><?= htmlspecialchars($row['nama_brng']) ?></td>
                <td><?= htmlspecialchars($row['kode_sat']) ?></td>
                <td><?= htmlspecialchars($row['jml']) ?></td>
                <td><?= htmlspecialchars($row['aturan_pakai']) ?></td>
            </tr>
        <?php
                endif;
            endwhile;
            if ($no == 1): // tidak ada data
        ?>
            <tr><td colspan="10" class="no-data">Tidak ada data resep ganda pada tanggal ini</td></tr>
        <?php
            endif;
        else:
        ?>
            <tr><td colspan="10" class="no-data">Tidak ada data resep ganda pada tanggal ini</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
            </div>
            <div class="info">
                Halaman ini berfungsi untuk mengontrol agar pasien tidak mendapatkan barang yang sama dalam no_resep yang berbeda pada tanggal yang sama.
            </div>
        </div>
    </div>
</body>
</html>
