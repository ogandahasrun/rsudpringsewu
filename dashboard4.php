<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Registrasi Pasien</title>
    <style>
        /* Mengatur gaya layout dua kolom */
        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            box-sizing: border-box;
        }
        .left, .right {
            width: 48%;
            padding: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .left {
            background-color: #f4f4f4;
        }
        .right {
            background-color: #e9ecef;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #999;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .left, .right {
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Bagian kiri untuk menampilkan data poliklinik 'int' -->
    <div class="left">
        <h2>Data Pasien Poliklinik Bedah</h2>
        <?php
        // Sertakan file koneksi
        include 'koneksi.php';

        // Query untuk poliklinik 'int'
        $query_int = "
            SELECT
                reg_periksa.no_reg,
                pasien.nm_pasien,
                dokter.nm_dokter,
                poliklinik.nm_poli
            FROM
                reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            WHERE
                reg_periksa.kd_poli = 'bed' AND 
                reg_periksa.tgl_registrasi = CURRENT_DATE AND
                reg_periksa.stts = 'belum'
            ORDER BY
                reg_periksa.no_reg ASC
        ";

        // Eksekusi query
        $result_int = mysqli_query($koneksi, $query_int);

        // Cek apakah ada hasil
        if (mysqli_num_rows($result_int) > 0) {
            echo "<table>
                    <tr>
                        <th>No. Registrasi</th>
                        <th>Nama Pasien</th>
                        <th>Nama Dokter</th>
                        <th>Nama Poliklinik</th>
                    </tr>";
            // Looping melalui hasil query
            while ($row_int = mysqli_fetch_assoc($result_int)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row_int['no_reg']) . "</td>
                        <td>" . htmlspecialchars($row_int['nm_pasien']) . "</td>
                        <td>" . htmlspecialchars($row_int['nm_dokter']) . "</td>
                        <td>" . htmlspecialchars($row_int['nm_poli']) . "</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Tidak ada data ditemukan untuk poliklinik bedah.</p>";
        }
        ?>
    </div>

    <!-- Bagian kanan untuk menampilkan data poliklinik 'tht' -->
    <div class="right">
        <h2>Data Pasien Poliklinik Bedah Mulut</h2>
        <?php
        // Query untuk poliklinik 'tht'
        $query_tht = "
            SELECT
                reg_periksa.no_reg,
                pasien.nm_pasien,
                dokter.nm_dokter,
                poliklinik.nm_poli
            FROM
                reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            WHERE
                reg_periksa.kd_poli = 'bdm' AND 
                reg_periksa.tgl_registrasi = CURRENT_DATE AND
                reg_periksa.stts = 'belum'
            ORDER BY
                reg_periksa.no_reg ASC
        ";

        // Eksekusi query
        $result_tht = mysqli_query($koneksi, $query_tht);

        // Cek apakah ada hasil
        if (mysqli_num_rows($result_tht) > 0) {
            echo "<table>
                    <tr>
                        <th>No. Registrasi</th>
                        <th>Nama Pasien</th>
                        <th>Nama Dokter</th>
                        <th>Nama Poliklinik</th>
                    </tr>";
            // Looping melalui hasil query
            while ($row_tht = mysqli_fetch_assoc($result_tht)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row_tht['no_reg']) . "</td>
                        <td>" . htmlspecialchars($row_tht['nm_pasien']) . "</td>
                        <td>" . htmlspecialchars($row_tht['nm_dokter']) . "</td>
                        <td>" . htmlspecialchars($row_tht['nm_poli']) . "</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Tidak ada data ditemukan untuk poliklinik bedah mulut.</p>";
        }

        // Tutup koneksi
        mysqli_close($koneksi);
        ?>
    </div>
</div>

<!-- Script JavaScript untuk Auto-Refresh setiap 10 detik -->
<script>
    // Me-refresh halaman setiap 10 detik (10000 milidetik)
    setTimeout(function(){
        window.location.reload(1);
    }, 10000);
</script>

</body>
</html>
