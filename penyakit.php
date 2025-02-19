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
// Tambahkan gaya CSS di sini
echo "
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }
    th, td {
        padding: 12px;
        text-align: left;
    }
    th {
        background-color: #3498db;
        color: #ffffff;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #e5e5e5;
    }
</style>";


// Include file koneksi.php
include 'koneksi.php';

// Lakukan pencarian jika ada kata kunci yang dikirim melalui form
if (isset($_GET['cari'])) {
    $keyword = $_GET['cari'];
    $query = "SELECT
        reg_periksa.no_rawat AS no_rawat,
        pasien.no_rkm_medis AS no_rkm_medis,
        pasien.nm_pasien AS nm_pasien,
        pasien.no_ktp AS no_ktp,
        diagnosa_pasien.kd_penyakit,
        penyakit.nm_penyakit AS nm_penyakit,
        pasien.alamat AS alamat,
        kelurahan.nm_kel AS nm_kel,
        kecamatan.nm_kec AS nm_kec,
        kabupaten.nm_kab AS nm_kab,
        propinsi.nm_prop AS nm_prop,
        kamar_inap.tgl_masuk AS tgl_masuk,
        kamar_inap.tgl_keluar AS tgl_keluar,
        kamar_inap.lama AS lama
        FROM
        reg_periksa
        JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
        JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
        JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
        JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
        JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
        JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
        JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
        WHERE
        reg_periksa.no_rawat LIKE '%$keyword%' OR
        pasien.no_rkm_medis LIKE '%$keyword%' OR
        pasien.nm_pasien LIKE '%$keyword%' OR
        pasien.no_ktp LIKE '%$keyword%' OR
        diagnosa_pasien.kd_penyakit LIKE '%$keyword%' OR
        penyakit.nm_penyakit LIKE '%$keyword%' OR
        pasien.alamat LIKE '%$keyword%' OR
        kelurahan.nm_kel LIKE '%$keyword%' OR
        kecamatan.nm_kec LIKE '%$keyword%' OR
        kabupaten.nm_kab LIKE '%$keyword%' OR
        propinsi.nm_prop LIKE '%$keyword%' OR
        kamar_inap.tgl_masuk LIKE '%$keyword%' OR
        kamar_inap.tgl_keluar LIKE '%$keyword%' OR
        kamar_inap.lama LIKE '%$keyword%'";

    $result = mysqli_query($koneksi, $query);

    // Tampilkan data dalam tabel HTML
echo "<table border='1'>
<tr>
<th>No Rawat</th>
<th>No Rekam Medis</th>
<th>Nama Pasien</th>
<th>No KTP</th>
<th>Kode Penyakit</th>
<th>Nama Penyakit</th>
<th>Alamat</th>
<th>Nama Kelurahan</th>
<th>Nama Kecamatan</th>
<th>Nama Kabupaten</th>
<th>Nama Propinsi</th>
<th>Tanggal Masuk</th>
<th>Tanggal Keluar</th>
<th>Lama</th>
</tr>";

while ($row = mysqli_fetch_assoc($result)) {
echo "<tr>";
echo "<td>" . $row['no_rawat'] . "</td>";
echo "<td>" . $row['no_rkm_medis'] . "</td>";
echo "<td>" . $row['nm_pasien'] . "</td>";
echo "<td>" . $row['no_ktp'] . "</td>";
echo "<td>" . $row['kd_penyakit'] . "</td>";
echo "<td>" . $row['nm_penyakit'] . "</td>";
echo "<td>" . $row['alamat'] . "</td>";
echo "<td>" . $row['nm_kel'] . "</td>";
echo "<td>" . $row['nm_kec'] . "</td>";
echo "<td>" . $row['nm_kab'] . "</td>";
echo "<td>" . $row['nm_prop'] . "</td>";
echo "<td>" . $row['tgl_masuk'] . "</td>";
echo "<td>" . $row['tgl_keluar'] . "</td>";
echo "<td>" . $row['lama'] . "</td>";
echo "</tr>";
}
echo "</table>";

    // Bebaskan memory
    mysqli_free_result($result);
} else {
    // Tampilkan form pencarian jika tidak ada kata kunci yang dikirim
    echo "<form action='penyakit.php' method='GET'>
        <input type='text' name='cari' placeholder='Masukkan kode diagnosa'>
        <button type='submit'>Cari</button>
        </form>";
}

// Tutup koneksi
mysqli_close($koneksi);
?>
