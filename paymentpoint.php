<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Point</title>
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
            document.getElementById('penjab').value = '';
            document.getElementById('nm_poli').value = '';
            document.getElementById('jenis_rawat').value = '';
        }

    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Payment Point</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="keuangan.php">← Kembali ke Menu Keuangan</a>
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
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    $penjab = isset($_POST['penjab']) ? $_POST['penjab'] : '';
    $nm_poli = isset($_POST['nm_poli']) ? $_POST['nm_poli'] : '';
    $jenis_rawat = isset($_POST['jenis_rawat']) ? $_POST['jenis_rawat'] : '';

    // Dropdown options
    $penjab_options = getOptions($koneksi, 'png_jawab', 'penjab');
    $poli_options = getOptions($koneksi, 'nm_poli', 'poliklinik');
    ?>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Payment Point
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Bayar Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Bayar Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="penjab">💳 Penjab</label>
                        <select id="penjab" name="penjab">
                            <option value="">-- Semua Penjab --</option>
                            <?php foreach ($penjab_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($penjab == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="nm_poli">🏥 Poliklinik</label>
                        <select id="nm_poli" name="nm_poli">
                            <option value="">-- Semua Poliklinik --</option>
                            <?php foreach ($poli_options as $opt) { ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" 
                                        <?php if ($nm_poli == $opt) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($opt); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="jenis_rawat">🩺 Jenis Rawat</label>
                        <select id="jenis_rawat" name="jenis_rawat">
                            <option value="">-- Semua Jenis --</option>
                            <option value="RAWAT INAP" <?php if ($jenis_rawat == 'RAWAT INAP') echo "selected"; ?>>🏥 Rawat Inap</option>
                            <option value="RAWAT JALAN" <?php if ($jenis_rawat == 'RAWAT JALAN') echo "selected"; ?>>🚶 Rawat Jalan</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        💰 Tampilkan Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

    <?php
    if (isset($_POST['filter'])) {
        $queries = [];
        
        // Query untuk data rawat inap (hanya jika jenis_rawat kosong atau 'RAWAT INAP')
        if ($jenis_rawat == '' || $jenis_rawat == 'RAWAT INAP') {
            $where_inap = "WHERE nota_inap.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            if ($penjab != '') $where_inap .= " AND penjab.png_jawab = '$penjab'";
            if ($nm_poli != '') $where_inap .= " AND poliklinik.nm_poli = '$nm_poli'";

            $query_inap = "SELECT
                        'RAWAT INAP' as jenis_rawat,
                        nota_inap.tanggal as tanggal_bayar,
                        nota_inap.jam as jam_bayar,
                        reg_periksa.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien,
                        penjab.png_jawab,
                        poliklinik.nm_poli,
                        SUM(detail_nota_inap.besar_bayar) as total_bayar
                    FROM
                        nota_inap
                        INNER JOIN reg_periksa ON nota_inap.no_rawat = reg_periksa.no_rawat
                        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                        INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                        INNER JOIN detail_nota_inap ON nota_inap.no_rawat = detail_nota_inap.no_rawat
                    $where_inap
                    GROUP BY nota_inap.no_rawat, nota_inap.tanggal, nota_inap.jam";
            
            $queries[] = $query_inap;
        }

        // Query untuk data rawat jalan (hanya jika jenis_rawat kosong atau 'RAWAT JALAN')
        if ($jenis_rawat == '' || $jenis_rawat == 'RAWAT JALAN') {
            $where_jalan = "WHERE nota_jalan.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            if ($penjab != '') $where_jalan .= " AND penjab.png_jawab = '$penjab'";
            if ($nm_poli != '') $where_jalan .= " AND poliklinik.nm_poli = '$nm_poli'";

            $query_jalan = "SELECT
                        'RAWAT JALAN' as jenis_rawat,
                        nota_jalan.tanggal as tanggal_bayar,
                        nota_jalan.jam as jam_bayar,
                        reg_periksa.no_rawat,
                        pasien.no_rkm_medis,
                        pasien.nm_pasien,
                        penjab.png_jawab,
                        poliklinik.nm_poli,
                        SUM(detail_nota_jalan.besar_bayar) as total_bayar
                    FROM
                        nota_jalan
                        INNER JOIN reg_periksa ON nota_jalan.no_rawat = reg_periksa.no_rawat
                        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                        INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                        INNER JOIN detail_nota_jalan ON nota_jalan.no_rawat = detail_nota_jalan.no_rawat
                    $where_jalan
                    GROUP BY nota_jalan.no_rawat, nota_jalan.tanggal, nota_jalan.jam";
            
            $queries[] = $query_jalan;
        }

        // Gabungkan query dengan UNION jika ada lebih dari 1 query
        if (count($queries) > 1) {
            $query = "(" . implode(") UNION (", $queries) . ") ORDER BY tanggal_bayar DESC, jam_bayar DESC";
        } elseif (count($queries) == 1) {
            $query = $queries[0] . " ORDER BY tanggal_bayar DESC, jam_bayar DESC";
        } else {
            $query = "SELECT 'RAWAT INAP' as jenis_rawat, '' as tanggal_bayar, '' as jam_bayar, '' as no_rawat, '' as no_rkm_medis, '' as nm_pasien, '' as png_jawab, '' as nm_poli, 0 as total_bayar WHERE 1=0";
        }
        
        $result = mysqli_query($koneksi, $query);
        if ($result) {
            $total_rows = mysqli_num_rows($result);
            $total_pembayaran = 0;
            
            // Hitung total pembayaran
            $temp_result = mysqli_query($koneksi, $query);
            while ($temp_row = mysqli_fetch_assoc($temp_result)) {
                $total_pembayaran += $temp_row['total_bayar'];
            }
            
            echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
            echo '<div style="font-weight: bold; color: #495057;">� Total Data: <span style="color: #007bff;">' . $total_rows . '</span> pembayaran | Total: <span style="color: #28a745;">Rp ' . number_format($total_pembayaran, 0, ',', '.') . '</span></div>';
            echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
            echo '</div>';
            
            echo "<div class='table-responsive'><table>
                <tr>
                    <th>No</th>
                    <th>JENIS RAWAT</th>
                    <th>TANGGAL BAYAR</th>
                    <th>JAM BAYAR</th>
                    <th>NOMOR RAWAT</th>
                    <th>NOMOR RM</th>
                    <th>NAMA PASIEN</th>
                    <th>PENJAB</th>
                    <th>POLIKLINIK</th>
                    <th>TOTAL BAYAR</th>
                </tr>";
            $no = 1; 
            while ($row = mysqli_fetch_assoc($result)) {
                // Color coding untuk jenis rawat
                $jenis_class = '';
                if ($row['jenis_rawat'] == 'RAWAT INAP') $jenis_class = 'stts-sudah';
                elseif ($row['jenis_rawat'] == 'RAWAT JALAN') $jenis_class = 'stts-belum';

                echo "<tr>
                        <td>{$no}</td>
                        <td class='$jenis_class'>{$row['jenis_rawat']}</td>
                        <td>{$row['tanggal_bayar']}</td>
                        <td>{$row['jam_bayar']}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['png_jawab']}</td>
                        <td>{$row['nm_poli']}</td>
                        <td style='text-align: right; font-weight: bold;'>Rp " . number_format($row['total_bayar'], 0, ',', '.') . "</td>
                    </tr>";
                $no++;    
            }
            echo "</table></div>";
            
            if ($total_rows == 0) {
                echo '<div class="no-data">� Tidak ada data pembayaran pada filter yang dipilih</div>';
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