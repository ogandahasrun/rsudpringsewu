<!DOCTYPE html>
<html lang="en">
<head>
    <title>Triase IGD</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
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
            z-index: 1;
        }
        .back-button {
            margin-bottom: 20px;
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
    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];

        // Validasi tanggal
        if ($tanggal_awal > $tanggal_akhir) {
            echo "<p style='color: red;'>Tanggal awal tidak boleh lebih besar dari tanggal akhir.</p>";
        } else {
            // Query dengan filter tanggal dan GROUP BY
            $query = "SELECT
                reg_periksa.no_rawat,
                MAX(reg_periksa.tgl_registrasi) AS tgl_registrasi,
                MAX(pasien.no_rkm_medis) AS no_rkm_medis,
                MAX(pasien.nm_pasien) AS nm_pasien,
                CASE
                    WHEN MAX(data_triase_igddetail_skala1.no_rawat) IS NOT NULL THEN 'SKALA 1'
                    WHEN MAX(data_triase_igddetail_skala2.no_rawat) IS NOT NULL THEN 'SKALA 2'
                    WHEN MAX(data_triase_igddetail_skala3.no_rawat) IS NOT NULL THEN 'SKALA 3'
                    WHEN MAX(data_triase_igddetail_skala4.no_rawat) IS NOT NULL THEN 'SKALA 4'
                    WHEN MAX(data_triase_igddetail_skala5.no_rawat) IS NOT NULL THEN 'SKALA 5'
                    ELSE 'TIDAK ADA SKALA'
                END AS skala_triase
            FROM
                reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            LEFT JOIN data_triase_igddetail_skala1 ON data_triase_igddetail_skala1.no_rawat = reg_periksa.no_rawat
            LEFT JOIN data_triase_igddetail_skala2 ON data_triase_igddetail_skala2.no_rawat = reg_periksa.no_rawat
            LEFT JOIN data_triase_igddetail_skala3 ON data_triase_igddetail_skala3.no_rawat = reg_periksa.no_rawat
            LEFT JOIN data_triase_igddetail_skala4 ON data_triase_igddetail_skala4.no_rawat = reg_periksa.no_rawat
            LEFT JOIN data_triase_igddetail_skala5 ON data_triase_igddetail_skala5.no_rawat = reg_periksa.no_rawat
            WHERE
                reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                AND reg_periksa.kd_poli = 'IGDK'
            GROUP BY
                reg_periksa.no_rawat;";
            
            $result = mysqli_query($koneksi, $query);

            if (!$result) {
                die("Query error: " . mysqli_error($koneksi));
            }
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
    if (isset($result)) {
        echo "<table>
            <tr>
                <th>TANGGAL REGISTRASI</th>
                <th>NOMOR RAWAT</th>
                <th>NOMOR RM</th>
                <th>NAMA PASIEN</th>
                <th>SKALA TRIASE</th>
            </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Tentukan warna berdasarkan nilai skala_triase
            $warna = "";
            switch ($row['skala_triase']) {
                case 'SKALA 1':
                    $warna = "background-color: #8B0000; color: white;";
                    break;
                case 'SKALA 2':
                    $warna = "background-color: #FF0000; color: white;";
                    break;
                case 'SKALA 3':
                    $warna = "background-color: #FFFF00;";
                    break;
                case 'SKALA 4':
                    $warna = "background-color: #90EE90;";
                    break;
                case 'SKALA 5':
                    $warna = "background-color: #008000; color: white;";
                    break;
                default:
                    $warna = "";
                    break;
            }

            echo "<tr>";
            echo "<td>" . $row['tgl_registrasi'] . "</td>";
            echo "<td>" . $row['no_rawat'] . "</td>";
            echo "<td>" . $row['no_rkm_medis'] . "</td>";
            echo "<td>" . $row['nm_pasien'] . "</td>";
            echo "<td style='$warna'>" . $row['skala_triase'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
    mysqli_close($koneksi);
    ?>
</body>
</html>