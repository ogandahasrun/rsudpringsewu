<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kelengkapan Berkas Rawat Inap</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
            }
    </style>
    <body>
        <header>
            <h1>Kelengkapan Berkas Rawat Inap</h1>
        </header>

    <div class="back-button">
        <a href="casemix.php">Kembali ke Menu Casemix</a>
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
                reg_periksa.no_rawat AS no_rawat,
                pasien.no_rkm_medis AS no_rkm_medis,
                pasien.nm_pasien AS nm_pasien,
                bridging_sep.no_sep AS no_sep
                FROM
                reg_periksa
                INNER JOIN pasien ON (reg_periksa.no_rkm_medis = pasien.no_rkm_medis)
                LEFT JOIN bridging_sep ON (bridging_sep.no_rawat = reg_periksa.no_rawat)
                WHERE
                reg_periksa.kd_pj = 'BPJ' AND
                reg_periksa.status_lanjut = 'ranap' AND
                reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                ";

                $result = mysqli_query($koneksi, $query);

    if (!$result) {
        die("Query error: " . mysqli_error($koneksi));
        }
    }
?>

<form method="POST">
    Filter Tanggal Registrasi : 
    <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
    <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
    <button type="submit" name="filter">Filter</button>
</form>

<?php
// Tampilkan tabel hanya jika hasil query ada
if(isset($result)) {
    echo "<table>
        <tr>
            <th>NOMOR RAWAT</th>
            <th>NOMOR REKAM MEDIS</th>
            <th>NAMA PASIEN</th>
            <th>NOMOR SEP</th>
        </tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['no_rawat'] . "</td>";
        echo "<td>" . $row['no_rkm_medis'] . "</td>";
        echo "<td>" . $row['nm_pasien'] . "</td>";
        echo "<td>" . $row['no_sep'] . "</td>";
        echo "</tr>";
        }
        echo "</table>";
    }    
    
mysqli_close($koneksi);
?>
