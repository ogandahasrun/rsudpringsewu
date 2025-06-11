<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kelengkapan Berkas Rawat Jalan</title>
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
        <h1>Kelengkapan Berkas Rawat Jalan</h1>
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
    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];
        $query = "SELECT 
                    reg_periksa.no_rawat, 
                    pasien.no_rkm_medis, 
                    pasien.nm_pasien, 
                    bridging_sep.no_sep, 
                    MAX(diagnosa_pasien.kd_penyakit) AS kd_penyakit,
                    rspsw_umbal.disetujui 
                FROM 
                    reg_periksa 
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
                LEFT JOIN bridging_sep ON bridging_sep.no_rawat = reg_periksa.no_rawat 
                LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                LEFT JOIN rspsw_umbal ON rspsw_umbal.no_rawat = reg_periksa.no_rawat
                WHERE 
                    reg_periksa.kd_pj = 'BPJ' 
                    AND reg_periksa.status_lanjut = 'ralan' 
                    AND reg_periksa.stts != 'batal' 
                    AND reg_periksa.tgl_registrasi 
                BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
                GROUP BY 
                    reg_periksa.no_rawat
                ";
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR REKAM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>DIAGNOSA</th>
                    <th>NOMOR SEP</th>
                    <th>DISETUJUI</th>
                </tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['kd_penyakit']}</td>
                        <td>{$row['no_sep']}</td>
                        <td>{$row['disetujui']}</td>
                    </tr>";
            }
            echo "</table>";
        }
        mysqli_close($koneksi);
    }
    ?>
</body>
</html>
