<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosa Penyebab Kematian</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>

<br/>
DIAGNOSA PENYEBAB KEMATIAN
<br/>

<?php
// include file koneksi.php
include 'koneksi.php';

// Query SQL
$query = "
    SELECT
        reg_periksa.no_rawat,
        pasien.no_rkm_medis,
        pasien.nm_pasien,
        pasien.jk,
        diagnosa_pasien.kd_penyakit,
        kamar_inap.stts_pulang,
        kamar_inap.tgl_keluar,
        penyakit.nm_penyakit,
        diagnosa_pasien.`status`,
        diagnosa_pasien.prioritas
    FROM
        kamar_inap
        INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
        INNER JOIN kelompokkamar ON kelompokkamar.kd_kamar = kamar.kd_kamar
        INNER JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
        INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
    WHERE
        kamar_inap.stts_pulang = 'meninggal'
    ORDER BY
        kamar_inap.tgl_keluar ASC,
        penyakit.kd_penyakit ASC
";

// Eksekusi query
$result = mysqli_query($koneksi, $query);

// Check apakah query berhasil dieksekusi
if (!$result) {
    die("Query error: " . mysqli_error($koneksi));
}

// Buat tabel HTML untuk menampilkan data dengan CSS
echo "<style>
    table {
        font-family: Arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    th {
        background-color: #f2f2f2;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
</style>";

echo "<table>
<tr>
<th>No. Rawat</th>
<th>No. Rekam Medis</th>
<th>Nama Pasien</th>
<th>Jenis Kelamin</th>
<th>Kode Penyakit</th>
<th>Status Pulang</th>
<th>Tanggal Keluar</th>
<th>Nama Penyakit</th>
<th>Status</th>
<th>Prioritas</th>
</tr>";

// Loop untuk menampilkan data per baris
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['no_rawat'] . "</td>";
    echo "<td>" . $row['no_rkm_medis'] . "</td>";
    echo "<td>" . $row['nm_pasien'] . "</td>";
    echo "<td>" . $row['jk'] . "</td>";
    echo "<td>" . $row['kd_penyakit'] . "</td>";
    echo "<td>" . $row['stts_pulang'] . "</td>";
    echo "<td>" . $row['tgl_keluar'] . "</td>";
    echo "<td>" . $row['nm_penyakit'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>" . $row['prioritas'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Bebaskan hasil query
mysqli_free_result($result);

// Tutup koneksi database
mysqli_close($koneksi);
?>
