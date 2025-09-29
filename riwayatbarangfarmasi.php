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

// Ambil daftar lokasi/bangsal untuk dropdown
$bangsal_options = [];
$bangsal_query = "SELECT kd_bangsal, nm_bangsal FROM bangsal ORDER BY nm_bangsal";
$bangsal_result = mysqli_query($koneksi, $bangsal_query);
while ($row = mysqli_fetch_assoc($bangsal_result)) {
    $bangsal_options[$row['kd_bangsal']] = $row['nm_bangsal'];
}

// Tangkap nilai filter dari formulir pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kd_bangsal = isset($_GET['kd_bangsal']) ? $_GET['kd_bangsal'] : '';

// Hanya eksekusi query jika ada keyword atau filter
if (!empty($keyword) || !empty($kd_bangsal) || isset($_GET['tanggal_awal']) || isset($_GET['tanggal_akhir'])) {
    $where = [];
    $where[] = "riwayat_barang_medis.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    if (!empty($kd_bangsal)) {
        $where[] = "riwayat_barang_medis.kd_bangsal = '$kd_bangsal'";
    }
    if (!empty($keyword)) {
        $where[] = "(databarang.nama_brng LIKE '%$keyword%' OR riwayat_barang_medis.kode_brng LIKE '%$keyword%')";
    }
    $where_sql = implode(' AND ', $where);

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
            WHERE $where_sql
            ORDER BY
                riwayat_barang_medis.kode_brng ASC,
                riwayat_barang_medis.tanggal ASC,
                riwayat_barang_medis.jam ASC";

    $result = $koneksi->query($sql);
}

// Tampilkan formulir pencarian dengan filter periode dan lokasi
echo '<html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                form { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }
                th { background-color: #f2f2f2; }
                tr:hover { background-color: #f5f5f5; }
            </style>
        </head>
        <body>';

echo '<form action="" method="get">
        <label for="tanggal_awal">Periode Awal:</label>
        <input type="date" id="tanggal_awal" name="tanggal_awal" value="'.$tanggal_awal.'">
        <label for="tanggal_akhir">Periode Akhir:</label>
        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="'.$tanggal_akhir.'">
        <label for="kd_bangsal">Lokasi:</label>
        <select id="kd_bangsal" name="kd_bangsal">
            <option value="">Semua Lokasi</option>';
foreach ($bangsal_options as $kode => $nama) {
    $selected = ($kd_bangsal == $kode) ? 'selected' : '';
    echo "<option value=\"$kode\" $selected>$nama</option>";
}
echo '</select>
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

echo '</body>
      </html>';

$koneksi->close();
?>