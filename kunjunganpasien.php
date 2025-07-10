<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kunjungan Pasien</title>
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
        }
        .copy-button {
            margin: 10px 0;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .back-button {
            margin: 10px 0;
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("Tabel berhasil disalin!");
        }
    </script>
</head>
<body>
    <header>
        <h1>KUNJUNGAN PASIEN</h1>
    </header>

    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

    <?php
    // Inisialisasi nilai default agar tidak error saat pertama kali dibuka
    $tanggal_awal = date('Y-m-01');
    $tanggal_akhir = date('Y-m-d');

    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];
    }
    ?>

    <form method="POST">
        Filter Tanggal Registrasi:
        <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    include 'koneksi.php';

    if (isset($_POST['filter'])) {
        $query = "SELECT
                    reg_periksa.tgl_registrasi,
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    poliklinik.nm_poli,
                    dokter.nm_dokter,
                    pasien.no_tlp,
                    penjab.png_jawab,
                    pasien.no_ktp,
                    pasien.jk,
                    pasien.tmp_lahir,
                    pasien.tgl_lahir,
                    pasien.alamat,
                    pasien.agama
                  FROM
                    reg_periksa
                  INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                  INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                  INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                  INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                  WHERE
                    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                  ORDER BY
                    reg_periksa.tgl_registrasi ASC";

        $result = mysqli_query($koneksi, $query);
        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                    <tr>
                        <th>NOMOR URUT</th>
                        <th>TANGGAL REGISTRASI</th>
                        <th>NOMOR RAWAT</th>
                        <th>NOMOR REKAM MEDIK</th>
                        <th>NAMA PASIEN</th>
                        <th>NOMOR KTP</th>
                        <th>JENIS KELAMIN</th>
                        <th>TEMPAT LAHIR</th>
                        <th>TANGGAL LAHIR</th>
                        <th>ALAMAT</th>
                        <th>AGAMA</th>
                        <th>NAMA POLI</th>
                        <th>NAMA DOKTER</th>
                        <th>NOMOR TELP</th>
                        <th>PENANGGUNG JAWAB</th>
                    </tr>";

            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>$no</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['no_ktp']}</td>
                        <td>{$row['jk']}</td>
                        <td>{$row['tmp_lahir']}</td>
                        <td>{$row['tgl_lahir']}</td>
                        <td>{$row['alamat']}</td>
                        <td>{$row['agama']}</td>
                        <td>{$row['nm_poli']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['no_tlp']}</td>
                        <td>{$row['png_jawab']}</td>
                    </tr>";
                $no++;
            }
            echo "</table>";
        } else {
            echo "<p>Terjadi kesalahan dalam query: " . mysqli_error($koneksi) . "</p>";
        }
        mysqli_close($koneksi);
    }
    ?>
</body>
</html>
