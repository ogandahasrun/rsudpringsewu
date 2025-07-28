<!DOCTYPE html>
<html lang="en">
<head>
    <title>Resep Pasien Ralan</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .copy-button {
            margin: 10px 0;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .back-button {
            margin: 10px 0;
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("Tabel berhasil disalin!");
        }
    </script>
</head>
<body>
    <header>
        <h1>Resep Pasien Ralan</h1>
    </header>

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

<form method="POST">
    <label>Filter Tanggal Registrasi:</label>
    <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
    <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
    <button type="submit" name="filter">Filter</button>
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
            dokter.nm_dokter
        FROM
            resep_obat
            INNER JOIN resep_dokter ON resep_dokter.no_resep = resep_obat.no_resep
            INNER JOIN databarang ON resep_dokter.kode_brng = databarang.kode_brng
            INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN dokter ON resep_obat.kd_dokter = dokter.kd_dokter
        WHERE
            resep_obat.`status` = 'ralan' AND
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
    echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
    echo "<table>
            <tr>
                <th>NO</th>
                <th>TANGGAL</th>
                <th>NO RESEP</th>
                <th>NO RAWAT</th>
                <th>NO RM</th>
                <th>NAMA PASIEN</th>
                <th>DOKTER</th>
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
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['no_rawat']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['no_rkm_medis']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['nm_pasien']}</td>
                    <td rowspan='$rowspan' style='vertical-align: top;'>{$data['nm_dokter']}</td>";
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

    echo "</table>";
} else {
    echo "<p>Tidak ada data ditemukan.</p>";
}

mysqli_close($koneksi);
?>
</body>
</html>
