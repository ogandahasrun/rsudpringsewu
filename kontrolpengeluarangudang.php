<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pengeluaran Gudang (GO) - RSUD Pringsewu</title>
    <style>
        body { font-family: Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 30px auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); padding: 30px; }
        h1 { text-align: center; color: #007bff; margin-bottom: 30px; }
        h2 { color: #343a40; margin-top: 40px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 10px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        th { background: linear-gradient(45deg, #007bff, #0056b3); color: white; font-weight: bold; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data { text-align: center; color: #888; font-style: italic; padding: 30px; }
        @media (max-width: 900px) {
            .container { padding: 10px; }
            th, td { font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Kontrol Pengeluaran Barang Gudang (GO)</h1>

    <!-- Tabel 1: Pemberian Obat -->
    <h2>1. Pemberian Obat</h2>
    <table>
        <thead>
            <tr>
                <th>Tgl Perawatan</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $q1 = "SELECT detail_pemberian_obat.tgl_perawatan, reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, databarang.kode_brng, databarang.nama_brng, detail_pemberian_obat.jml, databarang.kode_sat FROM detail_pemberian_obat INNER JOIN reg_periksa ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng WHERE detail_pemberian_obat.kd_bangsal = 'GO' AND detail_pemberian_obat.tgl_perawatan > '2024-12-31' ORDER BY detail_pemberian_obat.tgl_perawatan DESC LIMIT 500";
        $r1 = mysqli_query($koneksi, $q1);
        if ($r1 && mysqli_num_rows($r1) > 0) {
            while ($row = mysqli_fetch_assoc($r1)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tgl_perawatan']) . '</td>';
                echo '<td>' . htmlspecialchars($row['no_rawat']) . '</td>';
                echo '<td>' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_brng']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_brng']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($row['jml'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_sat']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" class="no-data">Tidak ada data pemberian obat.</td></tr>';
        }
        ?>
        </tbody>
    </table>

    <!-- Tabel 2: Resep Pulang -->
    <h2>2. Resep Pulang</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No Rawat</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $q2 = "SELECT resep_pulang.tanggal, reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, databarang.kode_brng, databarang.nama_brng, resep_pulang.jml_barang, databarang.kode_sat FROM resep_pulang INNER JOIN reg_periksa ON resep_pulang.no_rawat = reg_periksa.no_rawat INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis INNER JOIN databarang ON resep_pulang.kode_brng = databarang.kode_brng WHERE resep_pulang.kd_bangsal = 'GO' ORDER BY resep_pulang.tanggal DESC LIMIT 500";
        $r2 = mysqli_query($koneksi, $q2);
        if ($r2 && mysqli_num_rows($r2) > 0) {
            while ($row = mysqli_fetch_assoc($r2)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tanggal']) . '</td>';
                echo '<td>' . htmlspecialchars($row['no_rawat']) . '</td>';
                echo '<td>' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_brng']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_brng']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($row['jml_barang'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_sat']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" class="no-data">Tidak ada data resep pulang.</td></tr>';
        }
        ?>
        </tbody>
    </table>

    <!-- Tabel 3: Penjualan Bebas -->
    <h2>3. Penjualan Bebas</h2>
    <table>
        <thead>
            <tr>
                <th>Tgl Jual</th>
                <th>No RM</th>
                <th>Nama Pasien</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $q3 = "SELECT penjualan.tgl_jual, pasien.no_rkm_medis, pasien.nm_pasien, databarang.kode_brng, databarang.nama_brng, detailjual.jumlah, databarang.kode_sat FROM penjualan INNER JOIN detailjual ON detailjual.nota_jual = penjualan.nota_jual INNER JOIN pasien ON penjualan.no_rkm_medis = pasien.no_rkm_medis INNER JOIN databarang ON detailjual.kode_brng = databarang.kode_brng WHERE penjualan.kd_bangsal = 'GO' AND penjualan.tgl_jual > '2024-12-31' ORDER BY penjualan.tgl_jual DESC LIMIT 500";
        $r3 = mysqli_query($koneksi, $q3);
        if ($r3 && mysqli_num_rows($r3) > 0) {
            while ($row = mysqli_fetch_assoc($r3)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['tgl_jual']) . '</td>';
                echo '<td>' . htmlspecialchars($row['no_rkm_medis']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nm_pasien']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_brng']) . '</td>';
                echo '<td>' . htmlspecialchars($row['nama_brng']) . '</td>';
                echo '<td style="text-align:right;">' . number_format($row['jumlah'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($row['kode_sat']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" class="no-data">Tidak ada data penjualan bebas.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
