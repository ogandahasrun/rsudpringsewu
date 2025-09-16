<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Concern</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include 'header.php'; ?>

        <!-- Form Pencarian -->
        <div class="search-form">
            <form method="POST">
                Cari Berdasarkan Nomor Rawat : 
                <input type="text" name="no_rawat" required value="<?php echo isset($_POST['no_rawat']) ? htmlspecialchars($_POST['no_rawat']) : ''; ?>">
                <button type="submit" name="filter">Cari</button>
            </form>
        </div>

        <!-- Tombol untuk preview cetak -->
        <div class="print-button">
            <button onclick="window.print()">Preview Cetak</button>
        </div>

        <!-- Konten Surat -->
        <div class="content">
            <h3 class="center-text">GENERAL CONCERN</h3>
            <h4 class="center-text">Nomor : 812 / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / LL.04 / 2025</h4>

            <table class="no-border-table">
                <tr>
                    <td>Yang bertanda tangan di bawah ini dokter Rumah Sakit Umum Daerah Pringsewu 
                        Kabupaten Pringsewu dengan ini menerangkan bahwa :</td>
                </tr>
            </table>

            <?php
            include 'koneksi.php';

            // Inisialisasi variabel
            $nm_pasien = $no_rkm_medis = $tgl_lahir = $pekerjaan = $stts_nikah = $alamat = "";

            // Proses filter jika tombol "Filter" diklik
            if (isset($_POST['filter'])) {
                $no_rawat = $_POST['no_rawat'];

                // Validasi nomor rawat
                if (empty($no_rawat)) {
                    echo "<p style='color: red;'>Masukkan nomor rawat</p>";
                } else {
                    // Query dengan filter nomor rawat 
                    $query = "SELECT
                                reg_periksa.tgl_registrasi,
                                reg_periksa.no_rawat,
                                pasien.no_rkm_medis,
                                pasien.nm_pasien,
                                pasien.tgl_lahir,
                                pasien.jk,
                                pasien.namakeluarga,
                                pasien.keluarga,
                                reg_periksa.p_jawab,
                                pasien.pekerjaan,
                                pasien.stts_nikah,
                                pasien.alamat
                            FROM
                                reg_periksa
                            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                            WHERE
                                reg_periksa.no_rawat = ? ";

                    $stmt = mysqli_prepare($koneksi, $query);
                    if (!$stmt) {
                        die("Query prepare error: " . mysqli_error($koneksi));
                    }
                    mysqli_stmt_bind_param($stmt, "s", $no_rawat);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (!$result) {
                        die("Query error: " . mysqli_error($koneksi));
                    }

                    // Ambil data dari hasil query
                    if (mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        $nm_pasien    = $row['nm_pasien'];
                        $no_rkm_medis = $row['no_rkm_medis'];
                        $tgl_lahir    = $row['tgl_lahir'];
                        $pekerjaan    = $row['pekerjaan'];
                        $stts_nikah   = $row['stts_nikah'];
                        $alamat       = $row['alamat'];
                    } else {
                        echo "<p style='color: red;'>Data tidak ditemukan</p>";
                    }
                }
            }
            ?>

            <table class="no-border-table">
                <tr><td>Nama Pasien</td><td>:</td><td><?php echo htmlspecialchars($nm_pasien); ?></td></tr>
                <tr><td>Nomor Rekam Medis</td><td>:</td><td><?php echo htmlspecialchars($no_rkm_medis); ?></td></tr>
                <tr><td>Tanggal Lahir / Umur</td><td>:</td><td><?php echo htmlspecialchars($tgl_lahir); ?></td></tr>
                <tr><td>Pekerjaan</td><td>:</td><td><?php echo htmlspecialchars($pekerjaan); ?></td></tr>
                <tr><td>Status</td><td>:</td><td><?php echo htmlspecialchars($stts_nikah); ?></td></tr>
                <tr><td>Alamat</td><td>:</td><td><?php echo htmlspecialchars($alamat); ?></td></tr>
                <tr><td>Tempat Pemeriksaan</td><td>:</td><td>RSUD Pringsewu</td></tr>
                <tr><td>Hasil Pemeriksaan</td><td>:</td><td>CKD Stage 5</td></tr>
                <tr><td>Keterangan Lain</td><td>:</td><td>Hemodialisa 2 kali seminggu</td></tr>
            </table>

            <table class="no-border-table">
                <tr>
                    <td>Demikian Surat Keterangan ini dibuat dengan sebenarnya, agar dapat dipergunakan sebagaimana mestinya</td>
                </tr>
            </table>

        <?php
        $signature_file = '';
        if (!empty($no_rawat)) {
            $files = glob("image/" . preg_replace('/[^a-zA-Z0-9]/', '', $no_rawat) . "_*.png");
            if ($files && count($files) > 0) {
                // Ambil file terbaru
                usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                $signature_file = $files[0];
            }
        }
        ?>

            <div class="signature">
                <p>Pringsewu, ................................... </p>
                <p>Dokter RSUD Pringsewu</p>
                <br><br>

        <?php if ($signature_file): ?>
            <div style="margin-bottom:10px;">
                <strong>Tanda Tangan Sebelumnya:</strong><br>
                <img src="<?php echo $signature_file; ?>" alt="Tanda Tangan" style="border:1px solid #888; background:#fff; width:350px; height:120px;">
            </div>
        <?php endif; ?>

                <canvas id="signature-pad" width="350" height="120" style="border:1px solid #888; background:#fff;"></canvas>
                <br>
                <button type="button" onclick="signaturePad.clear()">Hapus Tanda Tangan</button>
                <button type="button" onclick="saveSignature()">Simpan Tanda Tangan</button> <!-- Tambahkan di sini -->
                <br><br>
                <p><strong><u id="signed-name">.............................................</u></strong></p>
                <p>...............................................</p>
            </div>
        </div>       
    </div>    

    <script>
        const canvas = document.getElementById('signature-pad');

        function saveSignature() {
            if (signaturePad.isEmpty()) {
                alert("Silakan tanda tangan dulu.");
                return;
            }
            var dataURL = signaturePad.toDataURL();
            var noRawat = document.querySelector('input[name="no_rawat"]').value;
            var now = new Date();
            var waktu = now.getFullYear().toString() +
                        ("0"+(now.getMonth()+1)).slice(-2) +
                        ("0"+now.getDate()).slice(-2) +
                        ("0"+now.getHours()).slice(-2) +
                        ("0"+now.getMinutes()).slice(-2) +
                        ("0"+now.getSeconds()).slice(-2);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "simpan_ttd.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    alert(xhr.responseText);
                }
            };
            xhr.send("img=" + encodeURIComponent(dataURL) + "&no_rawat=" + encodeURIComponent(noRawat) + "&waktu=" + waktu);
        }
    </script>

    <script>
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas);
        // Jika ingin menyimpan tanda tangan sebagai gambar:
        // const dataURL = signaturePad.toDataURL(); // base64 PNG
    </script>

</body>
</html>