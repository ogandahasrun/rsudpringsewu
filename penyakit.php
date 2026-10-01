<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Diagnosa Pasien - RSUD Pringsewu</title>
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
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .back-button {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            background-color: white;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
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
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1500px;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
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
            background: #e3f2fd;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-ralan {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .badge-ranap {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        .badge-utama {
            background-color: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }
        .badge-sekunder {
            background-color: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .kd-penyakit {
            font-family: monospace;
            font-weight: bold;
            color: #d63384;
            background: #f8f9fa;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
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
                width: 100%;
                justify-content: center;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 1200px;
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

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('status_lanjut').value = '';
            document.getElementById('keyword').value = '';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🩺 Data Diagnosa Pasien</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
                <a href="surveilans.php" style="background: #17a2b8;">📊 Menu Surveilans</a>
            </div>

    <?php
    include 'koneksi.php';

    // Nilai default filter
    $tanggal_awal = isset($_REQUEST['tanggal_awal']) ? $_REQUEST['tanggal_awal'] : date('Y-m-01');
    $tanggal_akhir = isset($_REQUEST['tanggal_akhir']) ? $_REQUEST['tanggal_akhir'] : date('Y-m-d');
    $status_lanjut = isset($_REQUEST['status_lanjut']) ? $_REQUEST['status_lanjut'] : '';
    $keyword = isset($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : (isset($_REQUEST['cari']) ? trim($_REQUEST['cari']) : '');
    ?>

            <form method="POST" action="penyakit.php" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Diagnosa Pasien
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Registrasi Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Registrasi Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status_lanjut">🔄 Status Lanjut</label>
                        <select id="status_lanjut" name="status_lanjut">
                            <option value="">-- Semua Status Lanjut --</option>
                            <option value="Ralan" <?php if ($status_lanjut == 'Ralan') echo 'selected'; ?>>Rawat Jalan (Ralan)</option>
                            <option value="Ranap" <?php if ($status_lanjut == 'Ranap') echo 'selected'; ?>>Rawat Inap (Ranap)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="keyword">🔎 Kata Kunci / Diagnosa</label>
                        <input type="text" 
                               id="keyword" 
                               name="keyword" 
                               placeholder="Kode ICD, Penyakit, No. Rawat, RM, Nama..."
                               value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" value="1" class="btn btn-primary">
                        📊 Tampilkan Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

    <?php
    if (isset($_REQUEST['filter']) || isset($_REQUEST['cari'])) {
        $where_conditions = ["reg_periksa.tgl_registrasi BETWEEN '" . mysqli_real_escape_string($koneksi, $tanggal_awal) . "' AND '" . mysqli_real_escape_string($koneksi, $tanggal_akhir) . "'"];
        
        if ($status_lanjut != '') {
            $where_conditions[] = "reg_periksa.status_lanjut = '" . mysqli_real_escape_string($koneksi, $status_lanjut) . "'";
        }
        
        if ($keyword != '') {
            $escaped_kw = mysqli_real_escape_string($koneksi, $keyword);
            $where_conditions[] = "(
                reg_periksa.no_rawat LIKE '%$escaped_kw%' OR
                pasien.no_rkm_medis LIKE '%$escaped_kw%' OR
                pasien.nm_pasien LIKE '%$escaped_kw%' OR
                pasien.no_ktp LIKE '%$escaped_kw%' OR
                diagnosa_pasien.kd_penyakit LIKE '%$escaped_kw%' OR
                penyakit.nm_penyakit LIKE '%$escaped_kw%' OR
                pasien.alamat LIKE '%$escaped_kw%' OR
                kelurahan.nm_kel LIKE '%$escaped_kw%' OR
                kecamatan.nm_kec LIKE '%$escaped_kw%' OR
                kabupaten.nm_kab LIKE '%$escaped_kw%' OR
                propinsi.nm_prop LIKE '%$escaped_kw%'
            )";
        }
        
        $where = "WHERE " . implode(" AND ", $where_conditions);

        $query = "SELECT
                    reg_periksa.tgl_registrasi,
                    reg_periksa.status_lanjut,
                    reg_periksa.no_rawat AS no_rawat,
                    pasien.no_rkm_medis AS no_rkm_medis,
                    pasien.nm_pasien AS nm_pasien,
                    pasien.no_ktp AS no_ktp,
                    diagnosa_pasien.kd_penyakit,
                    penyakit.nm_penyakit AS nm_penyakit,
                    diagnosa_pasien.prioritas,
                    pasien.alamat AS alamat,
                    kelurahan.nm_kel AS nm_kel,
                    kecamatan.nm_kec AS nm_kec,
                    kabupaten.nm_kab AS nm_kab,
                    propinsi.nm_prop AS nm_prop,
                    kamar_inap.tgl_masuk AS tgl_masuk,
                    kamar_inap.tgl_keluar AS tgl_keluar,
                    kamar_inap.lama AS lama
                FROM
                    reg_periksa
                    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                    INNER JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                    INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
                    LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
                    LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
                    LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
                    LEFT JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
                    LEFT JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
                $where
                ORDER BY 
                    reg_periksa.tgl_registrasi DESC, 
                    reg_periksa.no_rawat DESC, 
                    diagnosa_pasien.prioritas ASC";
                
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $total_rows = mysqli_num_rows($result);
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . number_format($total_rows, 0, ',', '.') . '</span> diagnosa</div>';
            if ($total_rows > 0) {
                echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
            }
            echo '</div>';
            
            if ($total_rows > 0) {
                echo "<div class='table-responsive'><table>
                    <tr>
                        <th>No</th>
                        <th>TANGGAL REGISTRASI</th>
                        <th>NO RAWAT</th>
                        <th>NO RM</th>
                        <th>NAMA PASIEN</th>
                        <th>STATUS LANJUT</th>
                        <th>NO KTP</th>
                        <th>KODE ICD</th>
                        <th>NAMA PENYAKIT</th>
                        <th>PRIORITAS</th>
                        <th>ALAMAT</th>
                        <th>KELURAHAN</th>
                        <th>KECAMATAN</th>
                        <th>KABUPATEN</th>
                        <th>PROPINSI</th>
                        <th>TGL MASUK</th>
                        <th>TGL KELUAR</th>
                        <th>LAMA</th>
                    </tr>";
                $no = 1; 
                while ($row = mysqli_fetch_assoc($result)) {
                    // Badge Status Lanjut
                    $status_raw = strtolower($row['status_lanjut'] ?? '');
                    if ($status_raw == 'ralan') {
                        $badge_status = "<span class='badge badge-ralan'>Ralan</span>";
                    } elseif ($status_raw == 'ranap') {
                        $badge_status = "<span class='badge badge-ranap'>Ranap</span>";
                    } else {
                        $badge_status = "<span class='badge'>" . htmlspecialchars($row['status_lanjut'] ?? '-') . "</span>";
                    }

                    // Badge Prioritas Diagnosa
                    $prioritas_val = $row['prioritas'] ?? '';
                    if ($prioritas_val == '1') {
                        $badge_prioritas = "<span class='badge badge-utama'>1 - Utama</span>";
                    } elseif (!empty($prioritas_val)) {
                        $badge_prioritas = "<span class='badge badge-sekunder'>" . htmlspecialchars($prioritas_val) . " - Sekunder</span>";
                    } else {
                        $badge_prioritas = "-";
                    }

                    // Format data rawat inap
                    $tgl_masuk = !empty($row['tgl_masuk']) ? htmlspecialchars($row['tgl_masuk']) : '-';
                    $tgl_keluar = (!empty($row['tgl_keluar']) && $row['tgl_keluar'] != '0000-00-00') ? htmlspecialchars($row['tgl_keluar']) : '-';
                    $lama = (!empty($row['lama']) && $row['lama'] > 0) ? htmlspecialchars($row['lama']) . ' hr' : '-';

                    echo "<tr>
                            <td>{$no}</td>
                            <td>" . htmlspecialchars($row['tgl_registrasi']) . "</td>
                            <td>" . htmlspecialchars($row['no_rawat']) . "</td>
                            <td>" . htmlspecialchars($row['no_rkm_medis']) . "</td>
                            <td>" . htmlspecialchars($row['nm_pasien']) . "</td>
                            <td>{$badge_status}</td>
                            <td>" . (!empty($row['no_ktp']) ? htmlspecialchars($row['no_ktp']) : '-') . "</td>
                            <td><span class='kd-penyakit'>" . htmlspecialchars($row['kd_penyakit']) . "</span></td>
                            <td>" . htmlspecialchars($row['nm_penyakit']) . "</td>
                            <td>{$badge_prioritas}</td>
                            <td>" . htmlspecialchars($row['alamat'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($row['nm_kel'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($row['nm_kec'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($row['nm_kab'] ?? '-') . "</td>
                            <td>" . htmlspecialchars($row['nm_prop'] ?? '-') . "</td>
                            <td>{$tgl_masuk}</td>
                            <td>{$tgl_keluar}</td>
                            <td>{$lama}</td>
                        </tr>";
                    $no++;    
                }
                echo "</table></div>";
            } else {
                echo '<div class="no-data">📊 Tidak ada data diagnosa pada filter yang dipilih. Silakan ubah periode atau kata kunci filter.</div>';
            }
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
            echo "❌ Terjadi kesalahan dalam query: " . mysqli_error($koneksi);
            echo '</div>';
        }
        mysqli_close($koneksi);
    } else {
        echo '<div style="text-align: center; color: #6c757d; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 1px dashed #ced4da;">💡 Silakan tentukan periode tanggal registrasi dan status lanjut, kemudian klik tombol <strong>"📊 Tampilkan Data"</strong> untuk menampilkan data diagnosa pasien.</div>';
    }
    ?>
    
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
