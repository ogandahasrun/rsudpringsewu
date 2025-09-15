<!DOCTYPE html>
<html lang="en">
<head>
    <title>Laporan Kunjungan Pasien</title>
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
        .stts-belum { background-color: #ffcccc; }    /* Merah */
        .stts-batal { background-color: #fff3cd; }    /* Kuning */
        .stts-sudah { background-color: #d4edda; }    /* Hijau */
        .bayar-belum { background-color: #ffcccc; }   /* Merah */
        .bayar-sudah { background-color: #d4edda; }   /* Hijau */
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
        <h1>Laporan Kunjungan Pasien</h1>
    </header>
    <div class="back-button">
        <a href="laporandansurat.php">Kembali ke Menu Laporan</a>
    </div>

    <?php
    include 'koneksi.php';

    // Ambil data untuk dropdown filter
    function getOptions($koneksi, $field, $table, $where = "") {
        $options = [];
        $query = "SELECT DISTINCT $field FROM $table $where ORDER BY $field";
        $result = mysqli_query($koneksi, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $options[] = $row[$field];
        }
        return $options;
    }

    // Default value
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    $png_jawab = isset($_POST['png_jawab']) ? $_POST['png_jawab'] : '';
    $status_lanjut = isset($_POST['status_lanjut']) ? $_POST['status_lanjut'] : '';
    $stts = isset($_POST['stts']) ? $_POST['stts'] : '';
    $status_bayar = isset($_POST['status_bayar']) ? $_POST['status_bayar'] : '';

    // Dropdown options
    $png_jawab_options = getOptions($koneksi, 'png_jawab', 'penjab');
    $status_lanjut_options = getOptions($koneksi, 'status_lanjut', 'reg_periksa');
    $stts_options = getOptions($koneksi, 'stts', 'reg_periksa');
    $status_bayar_options = getOptions($koneksi, 'status_bayar', 'reg_periksa');
    ?>

    <form method="POST">
        Filter Tanggal Registrasi:
        <input type="date" name="tanggal_awal" required value="<?php echo $tanggal_awal; ?>">
        <input type="date" name="tanggal_akhir" required value="<?php echo $tanggal_akhir; ?>">

        <br><br>
        Filter Penjab:
        <select name="png_jawab">
            <option value="">Semua</option>
            <?php foreach ($png_jawab_options as $opt) { ?>
                <option value="<?php echo $opt; ?>" <?php if ($png_jawab == $opt) echo "selected"; ?>><?php echo $opt; ?></option>
            <?php } ?>
        </select>

        Filter Status Lanjut:
        <select name="status_lanjut">
            <option value="">Semua</option>
            <?php foreach ($status_lanjut_options as $opt) { ?>
                <option value="<?php echo $opt; ?>" <?php if ($status_lanjut == $opt) echo "selected"; ?>><?php echo $opt; ?></option>
            <?php } ?>
        </select>

        Filter Status Periksa:
        <select name="stts">
            <option value="">Semua</option>
            <?php foreach ($stts_options as $opt) { ?>
                <option value="<?php echo $opt; ?>" <?php if ($stts == $opt) echo "selected"; ?>><?php echo $opt; ?></option>
            <?php } ?>
        </select>

        Filter Status Bayar:
        <select name="status_bayar">
            <option value="">Semua</option>
            <?php foreach ($status_bayar_options as $opt) { ?>
                <option value="<?php echo $opt; ?>" <?php if ($status_bayar == $opt) echo "selected"; ?>><?php echo $opt; ?></option>
            <?php } ?>
        </select>

        <button type="submit" name="filter">Filter</button>
    </form>

    <?php
    if (isset($_POST['filter'])) {
        $where = "WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        if ($png_jawab != '') $where .= " AND penjab.png_jawab = '$png_jawab'";
        if ($status_lanjut != '') $where .= " AND reg_periksa.status_lanjut = '$status_lanjut'";
        if ($stts != '') $where .= " AND reg_periksa.stts = '$stts'";
        if ($status_bayar != '') $where .= " AND reg_periksa.status_bayar = '$status_bayar'";

        $query = "SELECT
                    reg_periksa.tgl_registrasi,
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    penjab.png_jawab,
                    reg_periksa.status_lanjut,
                    reg_periksa.status_bayar,
                    reg_periksa.stts
                FROM
                    reg_periksa
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                $where
                ORDER BY reg_periksa.tgl_registrasi, reg_periksa.no_rawat
                ";
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            echo '<button class="copy-button" onclick="copyTableData()">Copy Tabel</button>';
            echo "<table>
                <tr>
                    <th>No</th>
                    <th>TANGGAL REGISTRASI</th>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR REKAM MEDIS</th>
                    <th>NAMA PASIEN</th>
                    <th>PENJAB</th>
                    <th>STATUS LANJUT</th>
                    <th>STATUS PERIKSA</th>
                    <th>STATUS BAYAR</th> 
                </tr>";
            $no = 1; 
            while ($row = mysqli_fetch_assoc($result)) {
                // Warna kolom stts
                $stts_class = '';
                if (strtolower($row['stts']) == 'belum') $stts_class = 'stts-belum';
                elseif (strtolower($row['stts']) == 'batal') $stts_class = 'stts-batal';
                elseif (strtolower($row['stts']) == 'sudah') $stts_class = 'stts-sudah';

                // Warna kolom status_bayar
                $bayar_class = '';
                if (strtolower($row['status_bayar']) == 'belum bayar') $bayar_class = 'bayar-belum';
                elseif (strtolower($row['status_bayar']) == 'sudah bayar') $bayar_class = 'bayar-sudah';

                echo "<tr>
                        <td>{$no}</td>
                        <td>{$row['tgl_registrasi']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['png_jawab']}</td>
                        <td>{$row['status_lanjut']}</td>
                        <td class='$stts_class'>{$row['stts']}</td>                        
                        <td class='$bayar_class'>{$row['status_bayar']}</td>    
                    </tr>";
                $no++;    
            }
            echo "</table>";
        }
        mysqli_close($koneksi);
    }
    ?>
</body>
</html>