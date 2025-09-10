<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kontrol DPJP</title>
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
        <h1>Kontrol DPJP</h1>
    </header>
    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

<?php
include 'koneksi.php';

// Ambil daftar dokter untuk filter
$dokterList = [];
$dokterRes = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter ASC");
while ($dok = mysqli_fetch_assoc($dokterRes)) {
    $dokterList[$dok['kd_dokter']] = $dok['nm_dokter'];
}

// Ambil filter dokter dari form
$filter_dokter = isset($_POST['filter_dokter']) ? $_POST['filter_dokter'] : '';

// Form filter dokter
echo '<form method="POST" class="filter-form">';
echo 'Pilih Dokter: <select name="filter_dokter">';
echo '<option value="">-- Semua Dokter --</option>';
foreach ($dokterList as $kd => $nama) {
    $selected = ($filter_dokter == $kd) ? 'selected' : '';
    echo "<option value=\"" . htmlspecialchars($kd) . "\" $selected>" . htmlspecialchars($nama) . "</option>";
}
echo '</select> ';
echo '<button type="submit" name="filter">Tampilkan</button>';
echo '</form>';

// Query data (tampilkan semua jika filter kosong)
$whereDokter = "";
if (!empty($filter_dokter)) {
    $whereDokter = "AND dpjp_ranap.kd_dokter = '" . mysqli_real_escape_string($koneksi, $filter_dokter) . "'";
}
$query = "SELECT
            kamar_inap.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            dokter.nm_dokter,
            bangsal.nm_bangsal,
            kamar.kd_kamar
        FROM
            kamar_inap
        INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
        LEFT JOIN dpjp_ranap ON kamar_inap.no_rawat = dpjp_ranap.no_rawat
        LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN dokter ON dpjp_ranap.kd_dokter = dokter.kd_dokter
        INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
        INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
        WHERE kamar_inap.stts_pulang = '-' $whereDokter
        ORDER BY dokter.nm_dokter ASC, bangsal.nm_bangsal ASC, kamar.kd_kamar ASC";

$result = mysqli_query($koneksi, $query);
if ($result) {
    echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
    echo "<table>
        <tr>
            <th>No</th>
            <th>NOMOR RAWAT</th>
            <th>NOMOR REKAM MEDIS</th>
            <th>NAMA PASIEN</th>
            <th>NAMA DOKTER</th>
            <th>NAMA BANGSAL</th>
            <th>NAMA KAMAR</th>
        </tr>";
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>{$no}</td>
                <td>{$row['no_rawat']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>{$row['nm_dokter']}</td>
                <td>{$row['nm_bangsal']}</td>
                <td>{$row['kd_kamar']}</td>
            </tr>";
        $no++;
    }
    echo "</table>";
} else {
    echo "<div style='color:red;'>Query error: " . mysqli_error($koneksi) . "</div>";
}
mysqli_close($koneksi);
?>
</body>
</html>

