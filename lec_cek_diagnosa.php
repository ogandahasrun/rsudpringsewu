<!DOCTYPE html>
<html lang="en">
<head>
    <title>LEC Cek Diagnosa dan Tindakan</title>
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
            text-align: center;
        }
        
        .container {
            width: 95%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .filter-form {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            margin-top: 20px;
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
        
        input[type="date"], select, button {
            padding: 5px;
            margin-right: 10px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 15px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .filter-row {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>LEC Cek Diagnosa dan Tindakan</h1>
        </header>

        <div class="back-button">
            <a href="index.php">Kembali ke Menu Awal</a>
        </div>
        
        <div class="filter-form">
            <form method="POST">
                <h3>Filter Data</h3>
                <div class="filter-row">
                    <label>Tanggal Registrasi:</label>
                    <input type="date" name="tanggal_awal" required value="<?php echo isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : ''; ?>">
                    s/d
                    <input type="date" name="tanggal_akhir" required value="<?php echo isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : ''; ?>">
                </div>
                <div class="filter-row">
                    <label>Nama Dokter:</label>
                    <select name="kd_dokter">
                        <option value="">-- Semua Dokter --</option>
                        <?php
                        include 'koneksi.php';
                        $query_dokter = "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter";
                        $result_dokter = mysqli_query($koneksi, $query_dokter);
                        
                        while ($dokter = mysqli_fetch_assoc($result_dokter)) {
                            $selected = (isset($_POST['kd_dokter']) && $_POST['kd_dokter'] == $dokter['kd_dokter']) ? 'selected' : '';
                            echo '<option value="'.$dokter['kd_dokter'].'" '.$selected.'>'.$dokter['nm_dokter'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div style="margin-top: 10px;">
                    <button type="submit" name="filter">Filter</button>
                </div>
            </form>
        </div>

        <?php
        // Proses filter jika tombol "Filter" diklik
        if(isset($_POST['filter'])) {
            $tanggal_awal = $_POST['tanggal_awal'];
            $tanggal_akhir = $_POST['tanggal_akhir'];
            $kd_dokter = isset($_POST['kd_dokter']) ? $_POST['kd_dokter'] : '';
            
            // Query dengan filter tanggal dan dokter
            $query = "SELECT
                        reg_periksa.tgl_registrasi,
                        reg_periksa.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien,
                        reg_periksa.status_lanjut,
                        GROUP_CONCAT(DISTINCT diagnosa_pasien.kd_penyakit SEPARATOR ', ') AS kd_penyakit,
                        GROUP_CONCAT(DISTINCT penyakit.nm_penyakit SEPARATOR ', ') AS nm_penyakit,
                        GROUP_CONCAT(DISTINCT paket_operasi.nm_perawatan SEPARATOR ', ') AS nm_perawatan,
                        dokter.nm_dokter
                      FROM
                        reg_periksa
                        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                        LEFT JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
                        LEFT JOIN operasi ON operasi.no_rawat = reg_periksa.no_rawat
                        LEFT JOIN paket_operasi ON operasi.kode_paket = paket_operasi.kode_paket
                        LEFT JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                      WHERE
                        reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            
            if (!empty($kd_dokter)) {
                $query .= " AND dokter.kd_dokter = '$kd_dokter'";
            }
            
            $query .= " GROUP BY reg_periksa.no_rawat";

            $result = mysqli_query($koneksi, $query);

            if (!$result) {
                die("Query error: " . mysqli_error($koneksi));
            }
        }
        ?>

        <?php
        // Tampilkan tabel hanya jika hasil query ada
        if(isset($result)) {
            echo "<table>
                <tr>
                    <th>TGL REGISTRASI</th>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR REKAM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>STATUS LANJUT</th>
                    <th>KODE PENYAKIT</th>
                    <th>NAMA PENYAKIT</th>
                    <th>TINDAKAN</th>
                    <th>DOKTER</th>
                </tr>";
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['tgl_registrasi'] . "</td>";
                    echo "<td>" . $row['no_rawat'] . "</td>";
                    echo "<td>" . $row['no_rkm_medis'] . "</td>";
                    echo "<td>" . $row['nm_pasien'] . "</td>";
                    echo "<td>" . $row['status_lanjut'] . "</td>";
                    echo "<td>" . $row['kd_penyakit'] . "</td>";
                    echo "<td>" . $row['nm_penyakit'] . "</td>";
                    echo "<td>" . $row['nm_perawatan'] . "</td>";
                    echo "<td>" . $row['nm_dokter'] . "</td>";        
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9' style='text-align:center;'>Tidak ada data ditemukan</td></tr>";
            }
            echo "</table>";
        }    
        
        // Tutup koneksi hanya jika sudah dibuka sebelumnya
        if(isset($koneksi)) {
            mysqli_close($koneksi);
        }
        ?>
    </div>
</body>
</html>