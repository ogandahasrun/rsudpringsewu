<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Ganti Jenis Bayar - RSUD Pringsewu</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body, table, th, td, input, select, button {
            font-family: Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            margin: 0;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #e65100, #ff8f00);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: bold;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 25px;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .back-button a {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .back-button a:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .filter-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .filter-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #e65100;
            box-shadow: 0 0 0 3px rgba(230, 81, 0, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #e65100, #ff8f00);
            color: white;
            box-shadow: 0 4px 15px rgba(230, 81, 0, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 81, 0, 0.4);
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
            position: relative;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            min-width: 800px;
        }
        th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 13px;
        }
        tr:nth-child(even) td {
            background: #f8f9fa;
        }
        tr:hover td {
            background: #fff3e0;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-jenis {
            background: #e3f2fd;
            color: #1565c0;
        }
        .badge-norawat {
            background: #f3e5f5;
            color: #7b1fa2;
            font-family: monospace;
            font-size: 11px;
        }
        .badge-ip {
            background: #e8f5e9;
            color: #2e7d32;
            font-family: monospace;
            font-size: 11px;
        }
        .badge-ralan {
            background: #e0f2fe;
            color: #0369a1;
        }
        .badge-ranap {
            background: #fef3c7;
            color: #b45309;
        }
        .badge-status {
            background: #f1f5f9;
            color: #475569;
        }
        .badge-asal {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 1.5em;
            }
            .content {
                padding: 15px;
            }
            .filter-form {
                padding: 20px 15px;
            }
            .filter-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .filter-actions {
                justify-content: stretch;
            }
            .btn {
                padding: 10px 15px;
                font-size: 13px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 900px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .filter-title {
                font-size: 16px;
            }
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function changePage(page) {
            let elem = document.getElementById('halaman');
            if (elem) {
                elem.value = page;
                document.getElementById('formFilter').submit();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('limit').value = '50';
            if (document.getElementById('halaman')) {
                document.getElementById('halaman').value = '1';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Kontrol Ganti Jenis Bayar</h1>
            <p>Monitoring perubahan jenis bayar pasien oleh petugas</p>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="keuangan.php">← Kembali ke Menu Keuangan</a>
            </div>

    <?php
    include 'koneksi.php';

    // Default value
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    $limit = isset($_POST['limit']) ? $_POST['limit'] : '50';
    $halaman = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
    if ($halaman < 1) $halaman = 1;
    ?>

            <form method="POST" class="filter-form" id="formFilter">
                <input type="hidden" name="filter" value="1">
                <input type="hidden" id="halaman" name="halaman" value="<?php echo htmlspecialchars($halaman); ?>">
                <div class="filter-title">
                    🔍 Filter Kontrol Ganti Jenis Bayar
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="limit">🔢 Tampilkan Data</label>
                        <select id="limit" name="limit" onchange="document.getElementById('halaman').value=1; this.form.submit();">
                            <option value="50" <?php echo ($limit == '50') ? 'selected' : ''; ?>>50 Data</option>
                            <option value="100" <?php echo ($limit == '100') ? 'selected' : ''; ?>>100 Data</option>
                            <option value="200" <?php echo ($limit == '200') ? 'selected' : ''; ?>>200 Data</option>
                            <option value="semua" <?php echo ($limit == 'semua') ? 'selected' : ''; ?>>Semua Data</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        📊 Tampilkan Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

    <?php
    if (isset($_POST['filter'])) {
        $tanggal_awal = mysqli_real_escape_string($koneksi, $_POST['tanggal_awal']);
        $tanggal_akhir = mysqli_real_escape_string($koneksi, $_POST['tanggal_akhir']);
        $limit = isset($_POST['limit']) ? $_POST['limit'] : '50';
        $halaman = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
        if ($halaman < 1) $halaman = 1;
        
        // 1. Query hitung total data secara efisien (tanpa JOIN dan tanpa ORDER BY)
        $query_count = "SELECT COUNT(*) AS total
                        FROM trackersql
                        WHERE trackersql.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
                          AND trackersql.sqle LIKE '%update reg_periksa set  kd_pj=%'";

        $result_count = mysqli_query($koneksi, $query_count);

        if ($result_count) {
            $row_count = mysqli_fetch_assoc($result_count);
            $total_rows = (int)($row_count['total'] ?? 0);
            
            // Hitung Pagination
            if ($limit === 'semua') {
                $total_pages = 1;
                $halaman = 1;
                $offset = 0;
                $limit_clause = "";
            } else {
                $limit_val = (int)$limit;
                if ($limit_val <= 0) $limit_val = 50;
                $total_pages = (int)ceil($total_rows / $limit_val);
                if ($total_pages < 1) $total_pages = 1;
                if ($halaman > $total_pages) $halaman = $total_pages;
                
                $offset = ($halaman - 1) * $limit_val;
                $limit_clause = " LIMIT $offset, $limit_val";
            }
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #e65100;">' . $total_rows . '</span> perubahan jenis bayar';
            if ($limit !== 'semua' && $total_pages > 1) {
                echo ' <span style="color: #6c757d; font-size: 13px;">(Halaman ' . $halaman . ' dari ' . $total_pages . ')</span>';
            }
            echo '</div>';
            
            echo '<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">';
            if ($limit !== 'semua' && $total_pages > 1) {
                echo '<div style="display: flex; align-items: center; gap: 6px;">';
                echo '<label for="halaman_select" style="font-weight: bold; font-size: 13px; color: #495057;">📄 Pilih Halaman:</label>';
                echo '<select id="halaman_select" onchange="changePage(this.value)" style="padding: 8px 12px; border-radius: 8px; border: 2px solid #e9ecef; font-size: 13px; outline: none; background: white; cursor: pointer;">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $selected_page = ($i == $halaman) ? 'selected' : '';
                    echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                }
                echo '</select>';
                echo '</div>';
            }
            echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
            echo '</div>';
            echo '</div>';
            
            if ($total_rows > 0) {
                // Ambil daftar penjab hanya saat ada data yang perlu ditampilkan
                $penjab_map = [];
                $query_penjab = "SELECT kd_pj, png_jawab FROM penjab";
                $result_penjab = mysqli_query($koneksi, $query_penjab);
                if ($result_penjab) {
                    while ($pj = mysqli_fetch_assoc($result_penjab)) {
                        $penjab_map[$pj['kd_pj']] = $pj['png_jawab'];
                    }
                }

                // 2. Query ambil data hanya sejumlah limit yang dibutuhkan
                $query_data = "SELECT
                            trackersql.tanggal,
                            trackersql.sqle,
                            COALESCE(pegawai.nama, trackersql.usere) AS nama
                        FROM
                            trackersql
                        LEFT JOIN pegawai ON trackersql.usere = pegawai.nik
                        WHERE
                            trackersql.tanggal BETWEEN '$tanggal_awal 00:00:00' AND '$tanggal_akhir 23:59:59'
                            AND trackersql.sqle LIKE '%update reg_periksa set  kd_pj=%'
                        ORDER BY trackersql.tanggal DESC" . $limit_clause;

                $result = mysqli_query($koneksi, $query_data);

                // Kumpulkan data dan parse sqle
                $rows_data = [];
                $all_no_rawat = [];
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $sqle = $row['sqle'];
                        
                        // Parse kode jenis bayar baru dari sqle
                        $kd_pj_baru = '-';
                        $nama_penjab_baru = '-';
                        if (preg_match('/\|([A-Za-z0-9_]+)\|/', $sqle, $matches_pj)) {
                            $kd_pj_baru = $matches_pj[1];
                            if (isset($penjab_map[$kd_pj_baru])) {
                                $nama_penjab_baru = $penjab_map[$kd_pj_baru];
                            } else {
                                $nama_penjab_baru = $kd_pj_baru;
                            }
                        }

                        // Parse no_rawat dari sqle
                        $no_rawat = '-';
                        if (preg_match('/\|[A-Za-z0-9_]+\|(\d{4}\/\d{2}\/\d{2}\/\d+)/', $sqle, $matches_nr)) {
                            $no_rawat = $matches_nr[1];
                            $all_no_rawat[] = $no_rawat;
                        }

                        // Parse IP address dari sqle
                        $ip_address = '-';
                        if (preg_match('/^([\d\.]+)\s/', $sqle, $matches_ip)) {
                            $ip_address = $matches_ip[1];
                        }

                        $rows_data[] = [
                            'tanggal' => $row['tanggal'],
                            'nama_petugas' => $row['nama'],
                            'kd_pj_baru' => $kd_pj_baru,
                            'nama_penjab_baru' => $nama_penjab_baru,
                            'no_rawat' => $no_rawat,
                            'ip_address' => $ip_address,
                        ];
                    }
                }

                // 1. Batch query: ambil data pasien berdasarkan no_rawat
                $pasien_map = [];
                $update_old_pj_map = [];
                $unique_no_rawat = array_values(array_unique(array_filter($all_no_rawat, function($v) {
                    return $v !== '-' && !empty($v);
                })));

                if (!empty($unique_no_rawat)) {
                    $no_rawat_escaped_arr = array_map(function($nr) use ($koneksi) {
                        return "'" . mysqli_real_escape_string($koneksi, $nr) . "'";
                    }, $unique_no_rawat);
                    $in_clause = implode(',', $no_rawat_escaped_arr);
                    $query_pasien = "SELECT rp.no_rawat, rp.no_rkm_medis, rp.status_lanjut, p.nm_pasien 
                                     FROM reg_periksa rp 
                                     INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis 
                                     WHERE rp.no_rawat IN ($in_clause)";
                    $result_pasien = mysqli_query($koneksi, $query_pasien);
                    if ($result_pasien) {
                        while ($rp = mysqli_fetch_assoc($result_pasien)) {
                            $pasien_map[$rp['no_rawat']] = [
                                'no_rkm_medis' => $rp['no_rkm_medis'],
                                'nm_pasien' => $rp['nm_pasien'],
                                'status_lanjut' => $rp['status_lanjut'] ?? '-',
                            ];
                        }
                    }

                    // 2. Batch query: rekonstruksi timeline riwayat jenis bayar awal dari trackersql
                    $min_date = $tanggal_awal;
                    foreach ($unique_no_rawat as $nr) {
                        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})/', $nr, $m_date)) {
                            $tgl_nr = $m_date[1] . '-' . $m_date[2] . '-' . $m_date[3];
                            if ($tgl_nr < $min_date) {
                                $min_date = $tgl_nr;
                            }
                        }
                    }

                    $history_start_date = $min_date . ' 00:00:00';
                    $history_end_date = $tanggal_akhir . ' 23:59:59';

                    $nr_like_arr = array_map(function($nr) use ($koneksi) {
                        $nr_esc = mysqli_real_escape_string($koneksi, $nr);
                        return "trackersql.sqle LIKE '%$nr_esc%'";
                    }, $unique_no_rawat);
                    $nr_sql = implode(' OR ', $nr_like_arr);

                    $query_history = "SELECT trackersql.tanggal, trackersql.sqle 
                                      FROM trackersql 
                                      WHERE trackersql.tanggal BETWEEN '$history_start_date' AND '$history_end_date'
                                        AND (
                                          trackersql.sqle LIKE '%update reg_periksa set  kd_pj=%' 
                                          OR trackersql.sqle LIKE '%insert into reg_periksa values%'
                                        )
                                        AND ($nr_sql)
                                      ORDER BY trackersql.tanggal ASC";

                    $res_history = mysqli_query($koneksi, $query_history);
                    $timeline = [];
                    if ($res_history) {
                        while ($h = mysqli_fetch_assoc($res_history)) {
                            $h_sqle = $h['sqle'];
                            $h_tgl = $h['tanggal'];

                            $found_nr = null;
                            if (preg_match('/(\d{4}\/\d{2}\/\d{2}\/\d+)/', $h_sqle, $m_nr)) {
                                $found_nr = $m_nr[1];
                            }

                            if ($found_nr && in_array($found_nr, $unique_no_rawat)) {
                                if (stripos($h_sqle, 'insert into reg_periksa') !== false) {
                                    $init_pj = null;
                                    if (preg_match('/\|(?:Ralan|Ranap)\|([A-Za-z0-9_-]+)\|/i', $h_sqle, $m_pj)) {
                                        $init_pj = $m_pj[1];
                                    } else {
                                        $parts = explode('|', $h_sqle);
                                        if (isset($parts[15]) && !empty($parts[15])) {
                                            $init_pj = trim($parts[15]);
                                        }
                                    }
                                    if ($init_pj) {
                                        $timeline[$found_nr][] = [
                                            'type' => 'INSERT',
                                            'tanggal' => $h_tgl,
                                            'kd_pj' => $init_pj,
                                        ];
                                    }
                                } elseif (stripos($h_sqle, 'update reg_periksa set  kd_pj=') !== false) {
                                    if (preg_match('/\|([A-Za-z0-9_]+)\|/', $h_sqle, $m_pj)) {
                                        $timeline[$found_nr][] = [
                                            'type' => 'UPDATE',
                                            'tanggal' => $h_tgl,
                                            'kd_pj' => $m_pj[1],
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    // Susun riwayat per no_rawat secara kronologis
                    foreach ($timeline as $nr => $events) {
                        $current_state = '-';
                        foreach ($events as $ev) {
                            if ($ev['type'] === 'INSERT') {
                                $current_state = $ev['kd_pj'];
                            } elseif ($ev['type'] === 'UPDATE') {
                                $key = $nr . '@' . $ev['tanggal'];
                                $update_old_pj_map[$key] = $current_state;
                                $current_state = $ev['kd_pj'];
                            }
                        }
                    }
                }

                echo "<div class='table-responsive'><table>
                    <tr>
                        <th>No</th>
                        <th>TANGGAL</th>
                        <th>JENIS BAYAR AWAL</th>
                        <th>GANTI JADI</th>
                        <th>NO RAWAT</th>
                        <th>STATUS</th>
                        <th>NO RKM MEDIS</th>
                        <th>NAMA PASIEN</th>
                        <th>IP ADDRESS</th>
                        <th>NAMA PETUGAS</th>
                    </tr>";

                $no = isset($offset) ? $offset + 1 : 1;
                foreach ($rows_data as $rd) {
                    $tanggal = htmlspecialchars($rd['tanggal']);
                    $nama_petugas = htmlspecialchars($rd['nama_petugas']);
                    $nama_penjab_baru_escaped = htmlspecialchars($rd['nama_penjab_baru']);
                    $no_rawat_escaped = htmlspecialchars($rd['no_rawat']);
                    $ip_escaped = htmlspecialchars($rd['ip_address']);

                    // Cari Jenis Bayar Awal dari map riwayat
                    $key = $rd['no_rawat'] . '@' . $rd['tanggal'];
                    $kd_pj_lama = $update_old_pj_map[$key] ?? '-';
                    $nama_penjab_lama = ($kd_pj_lama !== '-' && isset($penjab_map[$kd_pj_lama])) ? $penjab_map[$kd_pj_lama] : $kd_pj_lama;
                    $nama_penjab_lama_escaped = htmlspecialchars($nama_penjab_lama);
                    $badge_lama_html = ($nama_penjab_lama_escaped !== '-') 
                        ? "<span class='badge badge-asal'>{$nama_penjab_lama_escaped}</span>" 
                        : "<span style='color: #94a3b8;'>-</span>";

                    // Ambil data pasien dari map
                    $no_rkm_medis = '-';
                    $nm_pasien = '-';
                    $status_lanjut_html = '-';
                    if ($rd['no_rawat'] !== '-' && isset($pasien_map[$rd['no_rawat']])) {
                        $no_rkm_medis = htmlspecialchars($pasien_map[$rd['no_rawat']]['no_rkm_medis']);
                        $nm_pasien = htmlspecialchars($pasien_map[$rd['no_rawat']]['nm_pasien']);
                        $raw_status = $pasien_map[$rd['no_rawat']]['status_lanjut'];
                        
                        if (strcasecmp($raw_status, 'Ranap') === 0) {
                            $status_lanjut_html = "<span class='badge badge-ranap'>🏥 Ranap</span>";
                        } elseif (strcasecmp($raw_status, 'Ralan') === 0) {
                            $status_lanjut_html = "<span class='badge badge-ralan'>🚶 Ralan</span>";
                        } elseif (!empty($raw_status) && $raw_status !== '-') {
                            $status_lanjut_html = "<span class='badge badge-status'>" . htmlspecialchars($raw_status) . "</span>";
                        }
                    }

                    echo "<tr>
                            <td>{$no}</td>
                            <td>{$tanggal}</td>
                            <td>{$badge_lama_html}</td>
                            <td><span class='badge badge-jenis'>🔄 {$nama_penjab_baru_escaped}</span></td>
                            <td><span class='badge badge-norawat'>{$no_rawat_escaped}</span></td>
                            <td>{$status_lanjut_html}</td>
                            <td>{$no_rkm_medis}</td>
                            <td>{$nm_pasien}</td>
                            <td><span class='badge badge-ip'>{$ip_escaped}</span></td>
                            <td>{$nama_petugas}</td>
                        </tr>";
                    $no++;
                }
                echo "</table></div>";
                
                // Pagination bawah
                if ($limit !== 'semua' && $total_pages > 1) {
                    echo '<div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: #f8f9fa; padding: 12px 20px; border-radius: 8px; border: 1px solid #e9ecef;">';
                    echo '<div style="font-size: 13px; color: #495057; font-weight: 500;">Menampilkan halaman <strong>' . $halaman . '</strong> dari <strong>' . $total_pages . '</strong> (Total ' . $total_rows . ' data)</div>';
                    echo '<div style="display: flex; align-items: center; gap: 6px;">';
                    echo '<label for="halaman_select_bottom" style="font-weight: bold; font-size: 13px; color: #495057;">📄 Pilih Halaman:</label>';
                    echo '<select id="halaman_select_bottom" onchange="changePage(this.value)" style="padding: 6px 12px; border-radius: 8px; border: 2px solid #e9ecef; font-size: 13px; outline: none; background: white; cursor: pointer;">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $selected_page = ($i == $halaman) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected_page}>Halaman {$i}</option>";
                    }
                    echo '</select>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-data">📋 Tidak ada data perubahan jenis bayar pada rentang tanggal yang dipilih</div>';
            }
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
            echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
            echo '</div>';
        }
        mysqli_close($koneksi);
    }
    ?>
    
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
