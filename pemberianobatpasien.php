<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pemberian Obat Pasien</title>
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
        <h1>Pemberian Obat Pasien</h1>
    </header>

    <div class="back-button">
        <a href="farmasi.php">Kembali ke Menu Farmasi</a>
    </div>

    <?php
    // Inisialisasi nilai default
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

    // Jalankan query meskipun belum menekan tombol filter, menggunakan tanggal default
    $query = "SELECT
                reg_periksa.tgl_registrasi,
                reg_periksa.no_rawat,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                reg_periksa.status_lanjut,
                reg_periksa.kd_pj,
                SUM(detail_pemberian_obat.total) AS total
            FROM
                reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            LEFT JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat
            WHERE
                reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
            GROUP BY
                reg_periksa.no_rawat";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
        echo "<table>
                <tr>
                    <th>No</th>
                    <th>Tanggal Registrasi</th>
                    <th>No. Rawat</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Status Lanjut</th>
                    <th>Penanggung Jawab</th>
                    <th>Biaya Obat</th>
                </tr>";

        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $biaya_obat = number_format($row['total'], 0, ',', '.');
            echo "<tr>
                    <td>$no</td>
                    <td>{$row['tgl_registrasi']}</td>
                    <td>{$row['no_rawat']}</td>
                    <td>{$row['no_rkm_medis']}</td>
                    <td>{$row['nm_pasien']}</td>
                    <td>{$row['status_lanjut']}</td>
                    <td>{$row['kd_pj']}</td>
                    <td>Rp {$biaya_obat}</td>
                </tr>";
            $no++;
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada data ditemukan untuk rentang tanggal tersebut.</p>";
    }

    mysqli_close($koneksi);
    ?>
</body>
</html>
