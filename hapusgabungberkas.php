<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hapus Gabung Berkas Klaim</title>
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
        .delete-button {
            padding: 5px 10px;
            background-color: red;
            color: white;
            border: none;
            cursor: pointer;
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

        function confirmDelete(no_rawat) {
            if (confirm("Apakah Anda yakin ingin menghapus data ini?")) {
                document.getElementById('delete-form-' + no_rawat).submit();
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Hapus Gabung Berkas Klaim</h1>
    </header>
    <div class="back-button">
        <a href="casemix.php">Kembali ke Menu Casemix</a>
    </div>

    <?php
    include 'koneksi.php';

    // Hapus data jika tombol hapus diklik
    if (isset($_POST['hapus'])) {
        $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);
        $delete_query = "DELETE FROM bw_file_casemix_hasil WHERE no_rawat = '$no_rawat'";
        if (mysqli_query($koneksi, $delete_query)) {
            echo "<p style='color:green;'>Data dengan No. Rawat <b>$no_rawat</b> berhasil dihapus.</p>";
        } else {
            echo "<p style='color:red;'>Gagal menghapus data: " . mysqli_error($koneksi) . "</p>";
        }
    }

    $tanggal_awal = '';
    $tanggal_akhir = '';

    if (isset($_POST['filter'])) {
        $tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
        $tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);

        $query = "SELECT
                    reg_periksa.tgl_registrasi,
                    bw_file_casemix_hasil.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    reg_periksa.status_lanjut
                FROM
                    bw_file_casemix_hasil
                INNER JOIN reg_periksa ON bw_file_casemix_hasil.no_rawat = reg_periksa.no_rawat
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                WHERE
                    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";

        $result = mysqli_query($koneksi, $query);
    }
    ?>

    <form method="POST">
        Filter Tanggal Registrasi:
        <input type="date" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    if (isset($result) && mysqli_num_rows($result) > 0) {
        echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
        echo "<table>
                <tr>
                    <th>TANGGAL REGISTRASI</th>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR RM</th>
                    <th>NAMA PASIEN</th>
                    <th>STATUS LANJUT</th>
                    <th>AKSI</th>
                </tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            $no_rawat_escaped = htmlspecialchars($row['no_rawat']);
            echo "<tr>
                    <td>{$row['tgl_registrasi']}</td>
                    <td>{$row['no_rawat']}</td>
                    <td>{$row['no_rkm_medis']}</td>
                    <td>{$row['nm_pasien']}</td>
                    <td>{$row['status_lanjut']}</td>
                    <td>
                        <form method='POST' id='delete-form-$no_rawat_escaped' style='display:inline;'>
                            <input type='hidden' name='no_rawat' value='$no_rawat_escaped'>
                            <input type='hidden' name='tanggal_awal' value='$tanggal_awal'>
                            <input type='hidden' name='tanggal_akhir' value='$tanggal_akhir'>
                            <button type='button' class='delete-button' name='hapus' onclick='confirmDelete(\"$no_rawat_escaped\")'>Hapus</button>
                            <input type='hidden' name='hapus' value='1'>
                        </form>
                    </td>
                </tr>";
        }
        echo "</table>";
        mysqli_free_result($result);
    }

    mysqli_close($koneksi);
    ?>
</body>
</html>
