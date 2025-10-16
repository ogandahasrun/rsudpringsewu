<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kunjungan Pasien - RSUD Pringsewu</title>
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
            min-width: 900px;
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
        }
        
        /* Status Color Coding */
        .stts-belum { background-color: #f8d7da !important; color: #721c24; }
        .stts-batal { background-color: #fff3cd !important; color: #856404; }
        .stts-sudah { background-color: #d1ecf1 !important; color: #0c5460; }
        .bayar-belum { background-color: #f8d7da !important; color: #721c24; }
        .bayar-sudah { background-color: #d4edda !important; color: #155724; }
        
        /* Action Select Styling */
        .action-select {
            padding: 6px 12px;
            border: 1px solid #007bff;
            border-radius: 4px;
            background: white;
            color: #007bff;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .action-select:hover {
            background: #007bff;
            color: white;
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
                min-width: 720px;
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
            document.getElementById('poliklinik').value = '';
            document.getElementById('dokter').value = '';
            document.getElementById('cara_bayar').value = '';
            document.getElementById('status_lanjut').value = '';
            document.getElementById('status_bayar').value = '';
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
    <div class="container">
        <div class="header">
            <h1>📊 Laporan Kunjungan Pasien</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
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

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Laporan Kunjungan
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
                        <label for="png_jawab">💳 Cara Bayar/Penjab</label>
                        <select id="png_jawab" name="png_jawab">
                            <option value="">-- Semua Penjab --</option>
                            <?php foreach ($png_jawab_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($png_jawab == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status_lanjut">🏥 Status Lanjut</label>
                        <select id="status_lanjut" name="status_lanjut">
                            <option value="">-- Semua Status --</option>
                            <?php foreach ($status_lanjut_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($status_lanjut == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="stts">🩺 Status Periksa</label>
                        <select id="stts" name="stts">
                            <option value="">-- Semua Status --</option>
                            <?php foreach ($stts_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($stts == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status_bayar">💰 Status Bayar</label>
                        <select id="status_bayar" name="status_bayar">
                            <option value="">-- Semua Status --</option>
                            <?php foreach ($status_bayar_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($status_bayar == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        📊 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
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
            $total_rows = mysqli_num_rows($result);
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> kunjungan</div>';
            echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
            echo '</div>';
            
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
                            <select class='action-select' data-no_rawat='" . htmlspecialchars($row['no_rawat'], ENT_QUOTES) . "' onchange='gotoPage(this)'>
                                <option value=''>⚙️ Aksi</option>
                                <option value='persetujuanumum.php'>📋 Persetujuan Umum</option>
                                <option value='persetujuanpenolakantindakan.php'>📋 Persetujuan Penolakan Tindakan</option>
                                <!-- Tambah opsi lain di sini sesuai kebutuhan -->
                            </select>
                        </td>
                    </tr>";
                $no++;    
            }
            echo "</table></div>";
            
            if ($total_rows == 0) {
                echo '<div class="no-data">📊 Tidak ada data kunjungan pada filter yang dipilih</div>';
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