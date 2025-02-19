<!DOCTYPE html>
<html lang="en">
<head>
    <title>Penerimaan Narkotik</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
            }
    </style>
    <body>
        <header>
            <h1>Data Penerimaan Obat Narkotik dan Psikotropik per Periode</h1>
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

        /* CSS untuk tombol */
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50; /* Warna latar belakang */
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: white; /* Warna teks */
            text-decoration: none; /* Menghapus garis bawah */
            border-radius: 5px; /* Membuat sudut tombol menjadi melengkung */
            border: none; /* Menghapus garis pinggir */
            cursor: pointer; /* Mengubah kursor saat dihover menjadi pointer */
            font-size: 16px; /* Ukuran huruf */
            font-weight: bold; /* Ketebalan huruf */
            transition: background-color 0.3s; /* Efek transisi saat tombol dihover */
        }

        /* CSS untuk mengubah warna tombol saat dihover */
        .back-button:hover {
            background-color: #45a049;
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
                pemesanan.tgl_pesan AS tgl_pesan,
                detailpesan.kode_brng AS kode_brng,
                databarang.nama_brng AS nama_brng,
                golongan_barang.kode AS kode,
                detailpesan.jumlah AS jumlah
            FROM
                (((pemesanan
                JOIN detailpesan ON (detailpesan.no_faktur = pemesanan.no_faktur))
                JOIN databarang ON (detailpesan.kode_brng = databarang.kode_brng))
                JOIN golongan_barang ON (databarang.kode_golongan = golongan_barang.kode))
            WHERE
                pemesanan.tgl_pesan BETWEEN '$tanggal_awal' AND '$tanggal_akhir' AND
                golongan_barang.kode IN ('NK', 'psi')";

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
            <th>Tanggal Datang</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Golongan</th>
            <th>Jumlah</th>
        </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['tgl_pesan'] . "</td>";
            echo "<td>" . $row['kode_brng'] . "</td>";
            echo "<td>" . $row['nama_brng'] . "</td>";
            echo "<td>" . $row['kode'] . "</td>";
            echo "<td>" . $row['jumlah'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    mysqli_close($koneksi);
    ?>
    







    