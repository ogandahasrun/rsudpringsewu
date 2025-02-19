<!DOCTYPE html>
<html lang="en">
<head>
    <title>Resep Narkotik</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
            }
    </style>
    <body>
        <header>
            <h1>Data Pemberian Obat Narkotik dan Psikotropik per Periode</h1>
        </header>
    <div class="back-button">
        <a href="laporannarkotik.php">...</a>
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
                detail_pemberian_obat.tgl_perawatan AS tgl_perawatan,
                reg_periksa.no_rawat AS no_rawat,
                pasien.no_rkm_medis AS no_rkm_medis,
                pasien.nm_pasien AS nm_pasien,
                pasien.umur AS umur,
                pasien.alamat AS alamat,
                databarang.nama_brng AS nama_brng,
                golongan_barang.kode AS kode,
                detail_pemberian_obat.jml AS jml,
                detail_pemberian_obat.kd_bangsal AS kd_bangsal,
                dokter.nm_dokter AS nm_dokter
              FROM
                (((((
                  detail_pemberian_obat
                  JOIN databarang ON (detail_pemberian_obat.kode_brng = databarang.kode_brng))
                  JOIN golongan_barang ON (databarang.kode_golongan = golongan_barang.kode))
                  JOIN reg_periksa ON (detail_pemberian_obat.no_rawat = reg_periksa.no_rawat))
                  JOIN pasien ON (reg_periksa.no_rkm_medis = pasien.no_rkm_medis))
                  JOIN dokter ON (reg_periksa.kd_dokter = dokter.kd_dokter))
              WHERE
                golongan_barang.kode IN ('NK', 'PSI') AND
                detail_pemberian_obat.tgl_perawatan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
              ORDER BY
                kd_bangsal ASC,
                databarang.kode_golongan ASC,
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
            <th>Tanggal Pemberian</th>
            <th>No Rawat</th>
            <th>No Rekam Medis</th>
            <th>Nama Pasien</th>
            <th>Umur</th>
            <th>Alamat</th>
            <th>Nama Barang</th>
            <th>Kode</th>
            <th>Jumlah</th>
            <th>Kode Bangsal</th>
            <th>Nama Dokter</th>
        </tr>";

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['tgl_perawatan'] . "</td>";
        echo "<td>" . $row['no_rawat'] . "</td>";
        echo "<td>" . $row['no_rkm_medis'] . "</td>";
        echo "<td>" . $row['nm_pasien'] . "</td>";
        echo "<td>" . $row['umur'] . "</td>";
        echo "<td>" . $row['alamat'] . "</td>";
        echo "<td>" . $row['nama_brng'] . "</td>";
        echo "<td>" . $row['kode'] . "</td>";
        echo "<td>" . $row['jml'] . "</td>";
        echo "<td>" . $row['kd_bangsal'] . "</td>";
        echo "<td>" . $row['nm_dokter'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

mysqli_close($koneksi);
?>
