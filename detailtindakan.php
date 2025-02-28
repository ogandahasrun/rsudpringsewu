<!DOCTYPE html>
<html lang="en">
<head>
    <title>DETAIL TINDAKAN</title>
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
        <h1>DETAIL TINDAKAN</h1>
    </header>

    <div class="back-button">
        <a href="index.php">Kembali ke Menu Keuangan</a>
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
            reg_periksa.tgl_registrasi AS tglregis,
            reg_periksa.no_rawat AS norawat,
            pasien.no_rkm_medis AS norm,
            pasien.nm_pasien AS nama,
            COALESCE(SUM(DISTINCT `rawat_jl_dr`.`biaya_rawat`), 0) AS ralandokter,
            COALESCE(SUM(DISTINCT `rawat_inap_dr`.`biaya_rawat`), 0) AS ranapdokter,
            COALESCE(SUM(DISTINCT `rawat_jl_pr`.`biaya_rawat`), 0) AS ralanpetugas,
            COALESCE(SUM(DISTINCT `rawat_inap_pr`.`biaya_rawat`), 0) AS ranappetugas,
            COALESCE(SUM(DISTINCT `rawat_jl_drpr`.`biaya_rawat`), 0) AS ralanbersama,
            COALESCE(SUM(DISTINCT `rawat_inap_drpr`.`biaya_rawat`), 0) AS ranapbersama,
            -- Menambahkan total dari semua biaya
            (
                COALESCE(SUM(DISTINCT `rawat_jl_dr`.`biaya_rawat`), 0) +
                COALESCE(SUM(DISTINCT `rawat_inap_dr`.`biaya_rawat`), 0) +
                COALESCE(SUM(DISTINCT `rawat_jl_pr`.`biaya_rawat`), 0) +
                COALESCE(SUM(DISTINCT `rawat_inap_pr`.`biaya_rawat`), 0) +
                COALESCE(SUM(DISTINCT `rawat_jl_drpr`.`biaya_rawat`), 0) +
                COALESCE(SUM(DISTINCT `rawat_inap_drpr`.`biaya_rawat`), 0)
            ) AS total_biaya
        FROM
            reg_periksa
            LEFT JOIN rawat_jl_dr ON (rawat_jl_dr.no_rawat = reg_periksa.no_rawat)
            LEFT JOIN rawat_inap_dr ON (rawat_inap_dr.no_rawat = reg_periksa.no_rawat)
            LEFT JOIN rawat_jl_pr ON rawat_jl_pr.no_rawat = reg_periksa.no_rawat
            LEFT JOIN rawat_inap_pr ON rawat_inap_pr.no_rawat = reg_periksa.no_rawat
            LEFT JOIN rawat_jl_drpr ON rawat_jl_drpr.no_rawat = reg_periksa.no_rawat
            LEFT JOIN rawat_inap_drpr ON rawat_inap_drpr.no_rawat = reg_periksa.no_rawat
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        WHERE
            reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        GROUP BY
            reg_periksa.no_rawat
        ORDER BY
            tglregis ASC;";
        
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
                <th>BIAYA RAWAT JALAN DOKTER</th>
                <th>BIAYA RAWAT INAP DOKTER</th>
                <th>BIAYA RAWAT JALAN PETUGAS</th>
                <th>BIAYA RAWAT INAP PETUGAS</th>
                <th>BIAYA RAWAT JALAN BERSAMA</th>
                <th>BIAYA RAWAT INAP BERSAMA</th>
                <th>TOTAL BIAYA</th>
            </tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['tglregis'] . "</td>";
            echo "<td>" . $row['norawat'] . "</td>";
            echo "<td>" . $row['norm'] . "</td>";
            echo "<td>" . $row['nama'] . "</td>";
            echo "<td>" . $row['ralandokter'] . "</td>";
            echo "<td>" . $row['ranapdokter'] . "</td>";
            echo "<td>" . $row['ralanpetugas'] . "</td>";
            echo "<td>" . $row['ranappetugas'] . "</td>";
            echo "<td>" . $row['ralanbersama'] . "</td>";
            echo "<td>" . $row['ranapbersama'] . "</td>";
            echo "<td>" . $row['total_biaya'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }    
    
    mysqli_close($koneksi);
    ?>
</body>
</html>