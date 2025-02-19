<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mutasi Narkotik</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
            }
    </style>
    <body>
        <header>
            <h1>Data Mutasi Obat Narkotik dan Psikotropik per Periode</h1>
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
                mutasibarang.tanggal AS tanggal,
                mutasibarang.kd_bangsaldari AS kd_bangsaldari,
                mutasibarang.kd_bangsalke AS kd_bangsalke,
                bangsal.nm_bangsal AS nm_bangsal,
                databarang.kode_brng AS kode_brng,
                databarang.nama_brng AS nama_brng,
                golongan_barang.nama AS nama,
                mutasibarang.jml AS jml
                FROM
                (((mutasibarang
                JOIN databarang ON (mutasibarang.kode_brng = databarang.kode_brng))
                JOIN golongan_barang ON (databarang.kode_golongan = golongan_barang.kode))
                JOIN bangsal ON (mutasibarang.kd_bangsalke = bangsal.kd_bangsal))
                WHERE
                mutasibarang.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND
                golongan_barang.kode IN ('nk', 'psi')
                ORDER BY
                kd_bangsalke ASC,
                kd_bangsaldari ASC";

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
            <th>TANGGAL</th>
            <th>DARI BANGSAL</th>
            <th>KE BANGSAL</th>
            <th>NAMA BANGSAL</th>
            <th>KODE BARANG</th>
            <th>NAMA BARANG</th>
            <th>GOLONGAN</th>
            <th>JUMLAH</th>
        </tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['tanggal'] . "</td>";
        echo "<td>" . $row['kd_bangsaldari'] . "</td>";
        echo "<td>" . $row['kd_bangsalke'] . "</td>";
        echo "<td>" . $row['nm_bangsal'] . "</td>";
        echo "<td>" . $row['kode_brng'] . "</td>";
        echo "<td>" . $row['nama_brng'] . "</td>";
        echo "<td>" . $row['nama'] . "</td>";
        echo "<td>" . $row['jml'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
mysqli_close($koneksi);
?>
