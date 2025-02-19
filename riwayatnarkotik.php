<!DOCTYPE html>
<html lang="en">
<head>
    <title>Riwayat Narkotik</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
            }
    </style>
    <body>
        <header>
            <h1>Laporan Riwayat Barang Narkotik dan Psikotropik per Periode</h1>
        </header>
    <div class="back-button">
        <a href="laporannarkotik.php">Kembali ke Laporan Narkotik</a>
    </div>    
    </body>
</html>

<style>
    table {
        border-collapse: collapse;
        width: 100%;
        font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
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
</style>

<?php
include 'koneksi.php';

// Inisialisasi variabel tanggal awal dan akhir
$tanggal_awal = $tanggal_akhir = "";

// Proses filter jika tombol "Filter" diklik
if(isset($_POST['filter'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];

    // Query dengan filter tanggal
    $query = "SELECT
                riwayat_barang_medis.kode_brng AS kode_brng,
                databarang.nama_brng AS nama_brng,
                kodesatuan.satuan AS satuan,
                golongan_barang.nama AS nama,
                riwayat_barang_medis.stok_awal AS stok_awal,
                riwayat_barang_medis.masuk AS masuk,
                riwayat_barang_medis.keluar AS keluar,
                riwayat_barang_medis.stok_akhir AS stok_akhir,
                riwayat_barang_medis.kd_bangsal AS kd_bangsal,
                riwayat_barang_medis.tanggal AS tanggal,
                riwayat_barang_medis.jam AS jam,
                riwayat_barang_medis.keterangan AS keterangan
            FROM
                (((riwayat_barang_medis
                JOIN databarang ON (riwayat_barang_medis.kode_brng = databarang.kode_brng))
                JOIN kodesatuan ON (databarang.kode_sat = kodesatuan.kode_sat))
                JOIN golongan_barang ON (databarang.kode_golongan = golongan_barang.kode))
            WHERE
                databarang.kode_golongan IN ('NK', 'PSI') AND
                riwayat_barang_medis.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                ORDER BY
                databarang.kode_brng ASC";

    $result = mysqli_query($koneksi, $query);

    if (!$result) {
        die("Query error: " . mysqli_error($koneksi));
    }
}
?>

<form method="POST">
    Filter Tanggal: 
    <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
    <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
    <button type="submit" name="filter">Filter</button>
</form>

<?php
// Tampilkan tabel hanya jika hasil query ada
if(isset($result)) {
    echo "<table>
        <tr>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Nama</th>
            <th>Stok Awal</th>
            <th>Masuk</th>
            <th>Keluar</th>
            <th>Stok Akhir</th>
            <th>Bangsal</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Keterangan</th>
        </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['kode_brng'] . "</td>";
            echo "<td>" . $row['nama_brng'] . "</td>";
            echo "<td>" . $row['satuan'] . "</td>";
            echo "<td>" . $row['nama'] . "</td>";
            echo "<td>" . $row['stok_awal'] . "</td>";
            echo "<td>" . $row['masuk'] . "</td>";
            echo "<td>" . $row['keluar'] . "</td>";
            echo "<td>" . $row['stok_akhir'] . "</td>";
            echo "<td>" . $row['kd_bangsal'] . "</td>";
            echo "<td>" . $row['tanggal'] . "</td>";
            echo "<td>" . $row['jam'] . "</td>";
            echo "<td>" . $row['keterangan'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

mysqli_close($koneksi);
?>