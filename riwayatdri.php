<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Barang Medis</title>
</head>
<body>

<a href="index.php">
    <img src="images/logo.png" alt="Logo" width="80" height="100">
</a>

</body>
</html>



<?php
include 'koneksi.php';

// Tangkap nilai keyword dari formulir pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Hanya eksekusi query jika ada keyword yang dimasukkan
if (!empty($keyword)) {
    // Modifikasi query SQL untuk mencari berdasarkan nama_barang atau kode_barang
    $sql = "SELECT
                riwayat_barang_medis.kode_brng,
                databarang.nama_brng,
                databarang.kode_sat,
                riwayat_barang_medis.stok_awal,
                riwayat_barang_medis.masuk,
                riwayat_barang_medis.keluar,
                riwayat_barang_medis.stok_akhir,
                riwayat_barang_medis.posisi,
                riwayat_barang_medis.tanggal,
                riwayat_barang_medis.jam,
                bangsal.nm_bangsal
            FROM
                riwayat_barang_medis
            INNER JOIN databarang ON riwayat_barang_medis.kode_brng = databarang.kode_brng
            INNER JOIN bangsal ON riwayat_barang_medis.kd_bangsal = bangsal.kd_bangsal
            WHERE
                riwayat_barang_medis.tanggal BETWEEN '2023-01-01' AND '3000-12-31' AND
                riwayat_barang_medis.kd_bangsal = 'DRI' AND
                (databarang.nama_brng LIKE '%$keyword%' OR riwayat_barang_medis.kode_brng LIKE '%$keyword%')
            ORDER BY
                riwayat_barang_medis.kode_brng ASC,
                riwayat_barang_medis.tanggal ASC,
                riwayat_barang_medis.jam ASC";

    $result = $koneksi->query($sql);
}

// Tampilkan formulir pencarian dengan gaya CSS sederhana
echo '<html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                }
                form {
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #dddddd;
                    text-align: left;
                    padding: 8px;
                }
                th {
                    background-color: #f2f2f2;
                }
                tr:hover {
                    background-color: #f5f5f5;
                }
            </style>
        </head>
        <body>';

// Tampilkan formulir pencarian
echo '<form action="" method="get">
        <label for="keyword">Cari Barang :</label>
        <input type="text" id="keyword" name="keyword" value="'.$keyword.'">
        <input type="submit" value="Cari">
      </form>';

// Tampilkan tabel jika ada hasil pencarian
if (isset($result) && $result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kode Satuan</th>
                <th>Stok Awal</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Stok Akhir</th>
                <th>Posisi</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Nama Bangsal</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['kode_brng']}</td>
                <td>{$row['nama_brng']}</td>
                <td>{$row['kode_sat']}</td>
                <td>{$row['stok_awal']}</td>
                <td>{$row['masuk']}</td>
                <td>{$row['keluar']}</td>
                <td>{$row['stok_akhir']}</td>
                <td>{$row['posisi']}</td>
                <td>{$row['tanggal']}</td>
                <td>{$row['jam']}</td>
                <td>{$row['nm_bangsal']}</td>
              </tr>";
    }

    echo "</table>";
} elseif (isset($result)) {
    echo "<p>0 results</p>";
}

// Tutup tag HTML
echo '</body>
      </html>';

$koneksi->close();
?>
