<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Pasien Hemodialisa</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <!-- Panggil file header.php -->
        <?php include 'header.php'; ?>

        <!-- Form Pencarian -->
        <div class="search-form">
            <form method="POST">
                Cari Berdasarkan Nomor Rekam Medik : 
                <input type="text" name="no_rkm_medis" required value="<?php echo isset($no_rkm_medis) ? $no_rkm_medis : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>

        <!-- Konten Surat -->
        <div class="content">
            <h3 class="center-text">SURAT KETERANGAN DIRAWAT</h3>
            <h4 class="center-text">Nomor : 812 / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / LL.04 / 2025</h4>

            <table class="no-border-table">
                <tr><td>Yang bertanda tangan di bawah ini dokter Rumah Sakit Umum Daerah Pringsewu 
                    Kabupaten Pringsewu dengan ini menerangkan bahwa :</td></tr>
            </table>

            <?php
            include 'koneksi.php';

            // Inisialisasi variabel nomor rekam medis
            $no_rkm_medis = "";

            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter'])) {
                $no_rkm_medis = $_POST['no_rkm_medis'];
                
                // Validasi nomor rekam medis
                if (empty($no_rkm_medis)) {
                    echo "<p style='color: red;'>Masukkan nomor rekam medis</p>";
                } else {
                    // Query dengan filter nomor rekam medis 
                    $query = "SELECT
                                pasien.no_rkm_medis,
                                pasien.nm_pasien,
                                pasien.tgl_lahir,
                                pasien.pekerjaan,
                                pasien.stts_nikah,
                                pasien.alamat
                                FROM
                                pasien
                                WHERE
                                pasien.no_rkm_medis= ?";
                    
                    $stmt = mysqli_prepare($koneksi, $query);
                    mysqli_stmt_bind_param($stmt, "s", $no_rkm_medis);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (!$result) {
                        die("Query error: " . mysqli_error($koneksi));
                    }

                    // Ambil data dari hasil query
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        $nm_pasien = $row['nm_pasien'];
                        $tgl_lahir = $row['tgl_lahir'];
                        $pekerjaan = $row['pekerjaan'];
                        $stts_nikah = $row['stts_nikah'];
                        $alamat = $row['alamat'];
                    } else {
                        echo "<p style='color: red;'>Data tidak ditemukan</p>";
                        $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";
                    }
                }
            } else {
                $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";
            }
            ?>

            <table class="no-border-table">
                <tr><td>Nama Pasien</td><td>:</td><td><?php echo $nm_pasien; ?></td></tr>
                <tr><td>Nomor Rekam Medis</td><td>:</td><td><?php echo $no_rkm_medis; ?></td></tr>
                <tr><td>Tanggal Lahir / Umur</td><td>:</td><td><?php echo $tgl_lahir; ?></td></tr>
                <tr><td>Pekerjaan</td><td>:</td><td><?php echo $pekerjaan; ?></td></tr>
                <tr><td>Status</td><td>:</td><td><?php echo $stts_nikah; ?></td></tr>
                <tr><td>Alamat</td><td>:</td><td><?php echo $alamat; ?></td></tr>
                <tr><td>Tempat Pemeriksaan</td><td>:</td><td>RSUD Pringsewu</td></tr>
                <tr><td>Hasil Pemeriksaan</td><td>:</td><td>CKD Stage 5</td></tr>
                <tr><td>Keterangan Lain</td><td>:</td><td>Hemodialisa 2 kali seminggu</td></tr>
            </table>

            <table class="no-border-table">
                <tr><td>Demikian Surat Keterangan ini dibuat dengan sebenarnya, agar dapat dipergunakan sebagaimana mestinya</td></tr>
            </table>

            <div class="signature">
                <p>Pringsewu, ................................... </p>
                <p>Dokter RSUD Pringsewu</p>
                <br>
                <br>
                <br>
                <p><strong><u>.............................................</u></strong></p>
                <p>...............................................</p>
            </div>
        </div>       
    </div>    
</body>
</html>
