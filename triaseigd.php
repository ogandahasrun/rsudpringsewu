<!DOCTYPE html>
<html lang="en">
<head>
    <title>Triase IGD</title>
    <style>
        h1 {
            font-family: Arial, sans-serif; /* Mengubah jenis huruf/font */
            color: green; /* Mengubah warna teks */
        }
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
            position: sticky;
            top: 0;
            z-index: 1; /* Menjaga posisi tetap di atas konten */
        }
    </style>
</head>
<body>
    <header>
        <h1>Triase IGD</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Surveilans</a>
    </div>

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
data_triase_igd.no_rawat,
pasien.no_rkm_medis,
pasien.nm_pasien,
reg_periksa.status_lanjut,
master_triase_skala1.pengkajian_skala1,
master_triase_skala2.pengkajian_skala2,
master_triase_skala3.pengkajian_skala3,
master_triase_skala4.pengkajian_skala4,
master_triase_skala5.pengkajian_skala5
FROM
data_triase_igd
INNER JOIN reg_periksa ON data_triase_igd.no_rawat = reg_periksa.no_rawat
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN data_triase_igddetail_skala1 ON data_triase_igddetail_skala1.no_rawat = reg_periksa.no_rawat
LEFT JOIN master_triase_skala1 ON data_triase_igddetail_skala1.kode_skala1 = master_triase_skala1.kode_skala1
LEFT JOIN data_triase_igddetail_skala2 ON data_triase_igddetail_skala2.no_rawat = reg_periksa.no_rawat
LEFT JOIN master_triase_skala2 ON data_triase_igddetail_skala2.kode_skala2 = master_triase_skala2.kode_skala2
LEFT JOIN data_triase_igddetail_skala3 ON data_triase_igddetail_skala3.no_rawat = reg_periksa.no_rawat
LEFT JOIN master_triase_skala3 ON data_triase_igddetail_skala3.kode_skala3 = master_triase_skala3.kode_skala3
LEFT JOIN data_triase_igddetail_skala4 ON data_triase_igddetail_skala4.no_rawat = reg_periksa.no_rawat
LEFT JOIN master_triase_skala4 ON data_triase_igddetail_skala4.kode_skala4 = master_triase_skala4.kode_skala4
LEFT JOIN data_triase_igddetail_skala5 ON data_triase_igddetail_skala5.no_rawat = reg_periksa.no_rawat
LEFT JOIN master_triase_skala5 ON data_triase_igddetail_skala5.kode_skala5 = master_triase_skala5.kode_skala5
WHERE
data_triase_igd.tgl_kunjungan BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
ORDER BY
data_triase_igd.tgl_kunjungan ASC";
        
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
                <th>NOMOR RAWAT</th>               
                <th>NOMOR RM</th>
                <th>NAMA PASIEN</th>
                <th>STATUS LANJUT</th>
                <th>SKALA 1</th>
                <th>SKALA 2</th>
                <th>SKALA 3</th>
                <th>SKALA 4</th>
                <th>SKALA 5</th>
            </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['no_rawat'] . "</td>";
            echo "<td>" . $row['no_rkm_medis'] . "</td>";
            echo "<td>" . $row['nm_pasien'] . "</td>";
            echo "<td>" . $row['status_lanjut'] . "</td>";
            echo "<td>" . $row['pengkajian_skala1'] . "</td>";
            echo "<td>" . $row['pengkajian_skala2'] . "</td>";
            echo "<td>" . $row['pengkajian_skala3'] . "</td>";
            echo "<td>" . $row['pengkajian_skala4'] . "</td>";
            echo "<td>" . $row['pengkajian_skala5'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
    mysqli_close($koneksi);
    ?>
</body>
</html>
