<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hapus Gabung Berkas Klaim</title>
    <style>
        body {
            background: #f8f9fa;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 24px 18px 32px 18px;
        }
        .header {
            background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
            color: #fff;
            border-radius: 8px;
            padding: 18px 24px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
        }
        .header h1 {
            font-size: 1.7em;
            font-weight: 600;
            margin: 0;
            letter-spacing: 1px;
            flex: 1;
        }
        .back-button {
            margin-bottom: 18px;
        }
        .back-button a {
            color: #185a9d;
            text-decoration: none;
            font-weight: 500;
            font-size: 1em;
            transition: color 0.2s;
        }
        .back-button a:hover {
            color: #43cea2;
        }
        .filter-form {
            background: #f1f3f6;
            border-radius: 8px;
            padding: 18px 16px;
            margin-bottom: 22px;
        }
        .filter-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 12px;
            color: #185a9d;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 0.98em;
            margin-bottom: 6px;
            color: #185a9d;
        }
        .filter-group input[type="date"] {
            padding: 7px 10px;
            border-radius: 6px;
            border: 1px solid #b0bec5;
            font-size: 1em;
        }
        .filter-actions {
            margin-top: 18px;
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 8px 18px;
            border-radius: 6px;
            border: none;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary {
            background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
            color: #fff;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #185a9d 0%, #43cea2 100%);
        }
        .btn-secondary {
            background: #b0bec5;
            color: #185a9d;
        }
        .btn-secondary:hover {
            background: #78909c;
            color: #fff;
        }
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 18px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 720px;
            font-size: 1em;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        }
        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #185a9d;
            color: #fff;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f6fafd;
        }
        .delete-button {
            padding: 6px 14px;
            background: linear-gradient(90deg, #ff5858 0%, #f09819 100%);
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 0.98em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .delete-button:hover {
            background: linear-gradient(90deg, #f09819 0%, #ff5858 100%);
        }
        .copy-button {
            margin: 10px 0;
            padding: 8px 16px;
            background: #43cea2;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .copy-button:hover {
            background: #185a9d;
        }
        .stts-belum {
            background: #ffe082;
            color: #795548;
            font-weight: 600;
        }
        .stts-batal {
            background: #ff8a80;
            color: #c62828;
            font-weight: 600;
        }
        .stts-sudah {
            background: #b2ff59;
            color: #33691e;
            font-weight: 600;
        }
        @media (max-width: 700px) {
            .container {
                padding: 8px 2px 18px 2px;
            }
            .header {
                padding: 12px 8px;
                font-size: 1.1em;
            }
            .filter-form {
                padding: 10px 4px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 0.98em;
            }
            table {
                min-width: 520px;
            }
        }
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.1em;
            }
            .filter-title {
                font-size: 15px;
            }
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
    <div class="container">
        <div class="header">
            <h1>🗑️ Hapus Gabung Berkas Klaim</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="casemix.php">← Kembali ke Menu Casemix</a>
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

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Gabung Berkas Klaim
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Registrasi Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Registrasi Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">🗑️ Tampilkan Data</button>
                </div>
            </form>

    <?php
    if (isset($result)) {
        $total_rows = mysqli_num_rows($result);
        echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
        echo '<div style="font-weight: bold; color: #495057;">🗑️ Total Data: <span style="color: #43cea2;">' . $total_rows . '</span> berkas</div>';
        if ($total_rows > 0) {
            echo '<button class="copy-button" onclick="copyTableData()">📋 Copy Tabel</button>';
        }
        echo '</div>';
        if ($total_rows > 0) {
            echo "<div class='table-responsive'><table>
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
                // Color-coded status_lanjut
                $stts_class = '';
                if (strtolower($row['status_lanjut']) == 'ralan') $stts_class = 'stts-belum';
                elseif (strtolower($row['status_lanjut']) == 'ranap') $stts_class = 'stts-sudah';
                elseif (strtolower($row['status_lanjut']) == 'batal') $stts_class = 'stts-batal';
                echo "<tr>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td class='$stts_class'>{$row['status_lanjut']}</td>
                        <td>
                            <form method='POST' id='delete-form-$no_rawat_escaped' style='display:inline;'>
                                <input type='hidden' name='no_rawat' value='$no_rawat_escaped'>
                                <input type='hidden' name='tanggal_awal' value='$tanggal_awal'>
                                <input type='hidden' name='tanggal_akhir' value='$tanggal_akhir'>
                                <input type='hidden' name='filter' value='1'>
                                <button type='button' class='delete-button' name='hapus' onclick='confirmDelete(\"$no_rawat_escaped\")'>Hapus</button>
                                <input type='hidden' name='hapus' value='1'>
                            </form>
                        </td>
                    </tr>";
            }
            echo "</table></div>";
            mysqli_free_result($result);
        } else {
            echo '<div class="no-data">🗑️ Tidak ada data berkas pada filter yang dipilih</div>';
        }
    }

    mysqli_close($koneksi);
    ?>
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
