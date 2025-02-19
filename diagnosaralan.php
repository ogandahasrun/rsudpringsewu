<!DOCTYPE html>
<html lang="en">
<head>
    <title>Diagnosa Pasien Rawat Jalan</title>
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
        }
    </style>
</head>
<body>
    <header>
        <h1>Diagnosa Pasien Rawat Jalan</h1>
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
                    reg_periksa.tgl_registrasi,
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    poliklinik.nm_poli,
                    diagnosa_pasien.kd_penyakit,
                    reg_periksa.kd_pj
                    FROM
                    reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                    LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                    WHERE
                    reg_periksa.status_lanjut = 'ralan' AND
                    reg_periksa.kd_pj = 'UM' AND
                    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    ORDER BY
                    reg_periksa.tgl_registrasi ASC,
                    reg_periksa.no_rkm_medis ASC";
        
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
                <th>TANGGAL REGISTRASI</th>
                <th>NOMOR RAWAT</th>
                <th>NOMOR RM</th>
                <th>NAMA PASIEN</th>
                <th>POLIKLINIK</th>
                <th>KODE DIAGNOSA</th>
                <th>CARA BAYAR</th>
            </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['tgl_registrasi'] . "</td>";
            echo "<td>" . $row['no_rawat'] . "</td>";
            echo "<td>" . $row['no_rkm_medis'] . "</td>";
            echo "<td>" . $row['nm_pasien'] . "</td>";
            echo "<td>" . $row['nm_poli'] . "</td>";
            echo "<td>" . $row['kd_penyakit'] . "</td>";
            echo "<td>" . $row['kd_pj'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
    mysqli_close($koneksi);
    ?>
</body>
</html>
