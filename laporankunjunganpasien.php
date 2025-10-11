<!DOCTYPE html>
<html lang="en">
<head>
    <title>Laporan Kunjungan Pasien</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        h1 {
            font-family: Arial, sans-serif;
            color: green;
        }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            min-width: 900px; /* aktifkan scroll horizontal di layar kecil */
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

        @media (max-width: 600px) {
            th, td { padding: 6px; font-size: 12px; }
            .copy-button { width: 100%; box-sizing: border-box; }
            table { min-width: 720px; } /* sedikit lebih kecil agar nyaman di HP */
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("Tabel berhasil disalin!");
        }

        // Kirim ke halaman tujuan dengan POST agar langsung terfilter oleh no_rawat
        function gotoPage(selectEl) {
            const page = selectEl.value;
            if (!page) return;
            const noRawat = selectEl.dataset.no_rawat || '';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = page;
            // hidden inputs
            const i1 = document.createElement('input'); i1.type = 'hidden'; i1.name = 'no_rawat'; i1.value = noRawat;
            const i2 = document.createElement('input'); i2.type = 'hidden'; i2.name = 'filter'; i2.value = '1';
            form.appendChild(i1); form.appendChild(i2);
            document.body.appendChild(form);
            form.submit();
            // reset select option
            selectEl.value = '';
        }
    </script>
</head>
<body>
    <header>
        <h1>Laporan Kunjungan Pasien</h1>
    </header>
    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
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
            echo "<div class='table-responsive'><table>
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
                    <th>Aksi</th>
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
                        <td>
                            <select data-no_rawat='" . htmlspecialchars($row['no_rawat'], ENT_QUOTES) . "' onchange='gotoPage(this)'>
                                <option value=''>Pilih halaman…</option>
                                <option value='generalconsent.php'>General Consent</option>
                                <option value='informedconsent.php'>Informed Consent</option>
                                <!-- Tambah opsi lain di sini sesuai kebutuhan -->
                            </select>
                        </td>
                    </tr>";
                $no++;    
            }
            echo "</table></div>";
        }
        mysqli_close($koneksi);
    }
    ?>
</body>
</html>