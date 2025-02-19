<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosa Pasien</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>

<?php
// Include file koneksi.php
include 'koneksi.php';

// Query untuk mendapatkan data
$query = "SELECT
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            reg_periksa.kd_poli,
            reg_periksa.status_lanjut,
            kamar_inap.tgl_masuk,
            kamar_inap.tgl_keluar
          FROM
            reg_periksa
          INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
          WHERE
            reg_periksa.status_lanjut = 'ranap' AND
            reg_periksa.tgl_registrasi BETWEEN '2022-12-01' AND '2050-12-31' AND
            reg_periksa.kd_poli NOT IN ('igd','igdk','pn')";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

// Membuat tabel data
echo "<table border='1'>
        <tr>
            <th>No Rawat</th>
            <th>No Rekam Medis</th>
            <th>Nama Pasien</th>
            <th>Kode Poli</th>
            <th>Status Lanjut</th>
            <th>Tanggal Masuk</th>
            <th>Tanggal Keluar</th>
        </tr>";

while ($row = mysqli_fetch_array($result)) {
    echo "<tr>";
    echo "<td>" . $row['no_rawat'] . "</td>";
    echo "<td>" . $row['no_rkm_medis'] . "</td>";
    echo "<td>" . $row['nm_pasien'] . "</td>";
    echo "<td>" . $row['kd_poli'] . "</td>";
    echo "<td>" . $row['status_lanjut'] . "</td>";
    echo "<td>" . $row['tgl_masuk'] . "</td>";
    echo "<td>" . $row['tgl_keluar'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Membebaskan hasil query
mysqli_free_result($result);

// Menutup koneksi database
mysqli_close($koneksi);
?>
