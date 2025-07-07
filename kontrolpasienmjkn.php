<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kontrol Pasien mJKN</title>
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
        <h1>Kontrol Pasien mJKN</h1>
    </header>
    <div class="back-button">
        <a href="casemix.php">Kembali ke Menu Casemix</a>
    </div>

    <form method="POST">
        Filter Tanggal Registrasi:
        <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    include 'koneksi.php';

    $tanggal_hari_ini = date('Y-m-d');

    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];
    } else {
        $tanggal_awal = $tanggal_hari_ini;
        $tanggal_akhir = $tanggal_hari_ini;
    }
        $query = "SELECT
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    referensi_mobilejkn_bpjs.validasi,
                    bridging_sep.no_sep
                    FROM
                    referensi_mobilejkn_bpjs
                    LEFT JOIN reg_periksa ON referensi_mobilejkn_bpjs.no_rawat = reg_periksa.no_rawat
                    LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    LEFT JOIN bridging_sep ON bridging_sep.no_rawat = reg_periksa.no_rawat
                    WHERE
                    referensi_mobilejkn_bpjs.tanggalperiksa 
                    BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                ";
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR REKAM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>VALIDASI</th>
                    <th>NOMOR SEP</th>
                </tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['validasi']}</td>
                        <td>{$row['no_sep']}</td>
                    </tr>";
            }
            echo "</table>";
        }
        mysqli_close($koneksi);
    ?>
</body>
</html>
