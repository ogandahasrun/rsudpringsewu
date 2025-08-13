<!DOCTYPE html>
<html lang="en">
<head>
    <title>Kontrol SEP Ganda</title>
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
        <h1>KONTROL SEP GANDA</h1>
    </header>
    <div class="back-button">
        <a href="casemix.php">Kembali ke Menu Casemix</a>
    </div>

    <?php
    include 'koneksi.php';
        $query = "SELECT bridging_sep.no_rawat, bridging_sep.no_sep, bridging_sep.nomr, bridging_sep.nama_pasien, bridging_sep.tglsep
          FROM bridging_sep
          WHERE bridging_sep.no_rawat IN (
              SELECT no_rawat FROM bridging_sep GROUP BY no_rawat HAVING COUNT(*) > 1
          )
          ORDER BY bridging_sep.no_rawat, bridging_sep.no_sep";

        $result = mysqli_query($koneksi, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR SEP</th>
                    <th>NOMOR REKAM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>TANGGAL SEP</th>
                </tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_sep']}</td>
                        <td>{$row['nomr']}</td>
                        <td>{$row['nama_pasien']}</td>
                        <td>{$row['tglsep']}</td>
                    </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Tidak ada data ganda.</p>";
        }
        mysqli_close($koneksi);    
        ?>
</body>
</html>
