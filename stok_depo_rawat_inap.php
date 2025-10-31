<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Depo Rawat Inap - RSUD Pringsewu</title>
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        .back-button img {
            width: 20px;
            height: 20px;
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
            min-width: 800px;
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
        .stock-low {
            background-color: #f8d7da !important;
            color: #721c24;
            font-weight: bold;
        }
        .stock-medium {
            background-color: #fff3cd !important;
            color: #856404;
            font-weight: bold;
        }
        .stock-good {
            background-color: #d4edda !important;
            color: #155724;
            font-weight: bold;
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
                min-width: 600px;
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
            document.getElementById('keyword').value = '';
            document.getElementById('lokasi').value = '';
            document.getElementById('stok_level').value = '';
            document.getElementById('keyword').focus();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Stok Depo Rawat Inap</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">
                    <img src="images/logo.png" alt="Logo">
                    ← Kembali ke Farmasi
                </a>
            </div>

            <?php
            // Include file koneksi
            include 'koneksi.php';
            
            // Cek koneksi database
            if ($koneksi->connect_error) {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>❌ Koneksi Database Gagal:</strong> ' . $koneksi->connect_error . '
                      </div>';
                exit;
            }

            // Inisialisasi variabel pencarian
            $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
            $lokasi_filter = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
            $stok_filter = isset($_GET['stok_level']) ? $_GET['stok_level'] : '';

            // Ambil data lokasi untuk dropdown
            $lokasi_query = "SELECT DISTINCT lokasi FROM lokasibarangmedisdri WHERE lokasi IS NOT NULL AND lokasi != '' ORDER BY lokasi";
            $lokasi_result = $koneksi->query($lokasi_query);
            $lokasi_options = [];
            
            if ($lokasi_result) {
                while ($row = $lokasi_result->fetch_assoc()) {
                    if (!empty(trim($row['lokasi']))) {
                        $lokasi_options[] = $row['lokasi'];
                    }
                }
            }
            ?>

            <form method="GET" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Stok Depo Rawat Inap
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="keyword">🔍 Cari Nama Barang/Lokasi</label>
                        <input type="text" 
                               id="keyword" 
                               name="keyword" 
                               placeholder="Masukkan nama barang atau lokasi..." 
                               value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="lokasi">📍 Filter Lokasi</label>
                        <select id="lokasi" name="lokasi">
                            <option value="">-- Semua Lokasi --</option>
                            <?php foreach ($lokasi_options as $lokasi): ?>
                                <option value="<?php echo htmlspecialchars($lokasi); ?>" 
                                        <?php echo ($lokasi_filter == $lokasi) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lokasi); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="stok_level">📊 Level Stok</label>
                        <select id="stok_level" name="stok_level">
                            <option value="">-- Semua Level --</option>
                            <option value="low" <?php echo ($stok_filter == 'low') ? 'selected' : ''; ?>>🔴 Stok Rendah (≤ 10)</option>
                            <option value="medium" <?php echo ($stok_filter == 'medium') ? 'selected' : ''; ?>>🟡 Stok Sedang (11-50)</option>
                            <option value="high" <?php echo ($stok_filter == 'high') ? 'selected' : ''; ?>>🟢 Stok Tinggi (> 50)</option>
                            <option value="empty" <?php echo ($stok_filter == 'empty') ? 'selected' : ''; ?>>⚫ Stok Kosong (0)</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        🔍 Cari Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

            <?php
            // Query dengan penambahan kondisi pencarian dan filter
            $conditions = ["gudangbarang.kd_bangsal = 'dri'"];
            
            if (!empty($keyword)) {
                $keyword_escaped = mysqli_real_escape_string($koneksi, $keyword);
                $conditions[] = "(databarang.nama_brng LIKE '%$keyword_escaped%' OR lokasibarangmedisdri.lokasi LIKE '%$keyword_escaped%')";
            }
            
            if (!empty($lokasi_filter)) {
                $lokasi_escaped = mysqli_real_escape_string($koneksi, $lokasi_filter);
                $conditions[] = "lokasibarangmedisdri.lokasi = '$lokasi_escaped'";
            }
            
            // Filter berdasarkan level stok
            if (!empty($stok_filter)) {
                switch ($stok_filter) {
                    case 'empty':
                        $conditions[] = "gudangbarang.stok = 0";
                        break;
                    case 'low':
                        $conditions[] = "gudangbarang.stok > 0 AND gudangbarang.stok <= 10";
                        break;
                    case 'medium':
                        $conditions[] = "gudangbarang.stok > 10 AND gudangbarang.stok <= 50";
                        break;
                    case 'high':
                        $conditions[] = "gudangbarang.stok > 50";
                        break;
                }
            }
            
            $where_clause = "WHERE " . implode(" AND ", $conditions);

            $query = "
                SELECT
                    gudangbarang.kode_brng,
                    databarang.nama_brng,
                    databarang.kode_sat,
                    gudangbarang.stok,
                    lokasibarangmedisdri.lokasi,
                    bangsal.nm_bangsal
                FROM
                    gudangbarang
                INNER JOIN databarang ON gudangbarang.kode_brng = databarang.kode_brng
                INNER JOIN lokasibarangmedisdri ON lokasibarangmedisdri.kode_brng = gudangbarang.kode_brng
                INNER JOIN bangsal ON gudangbarang.kd_bangsal = bangsal.kd_bangsal
                $where_clause
                ORDER BY
                    lokasibarangmedisdri.lokasi ASC,
                    databarang.nama_brng ASC
            ";

            // Eksekusi query
            $result = $koneksi->query($query);
            
            // Debug: tampilkan error jika ada
            if (!$result) {
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <strong>❌ Database Error:</strong> ' . $koneksi->error . '
                        <br><br>
                        <strong>Query:</strong> ' . htmlspecialchars($query) . '
                      </div>';
                $koneksi->close();
                exit;
            }
            
            // Tampilkan hasil
            if ($result && $result->num_rows > 0) {
                $total_rows = $result->num_rows;
                $total_items = 0;
                $low_stock_count = 0;
                $empty_stock_count = 0;
                
                // Hitung statistik
                $temp_result = $koneksi->query($query);
                while ($temp_row = $temp_result->fetch_assoc()) {
                    $total_items += $temp_row['stok'];
                    if ($temp_row['stok'] == 0) $empty_stock_count++;
                    elseif ($temp_row['stok'] <= 10) $low_stock_count++;
                }
                
                echo '<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
                echo '<div style="font-weight: bold; color: #495057;">';
                echo '📊 Ditemukan: <span style="color: #007bff;">' . number_format($total_rows) . '</span> jenis barang | ';
                echo '📦 Total Item: <span style="color: #28a745;">' . number_format($total_items) . '</span> | ';
                if ($low_stock_count > 0) {
                    echo '🔴 Stok Rendah: <span style="color: #dc3545;">' . $low_stock_count . '</span> | ';
                }
                if ($empty_stock_count > 0) {
                    echo '⚫ Stok Kosong: <span style="color: #6c757d;">' . $empty_stock_count . '</span>';
                }
                echo '</div>';
                echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
                echo '</div>';
                
                echo '<div class="table-responsive">';
                echo "<table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Lokasi</th>
                            <th>Bangsal</th>
                        </tr>
                    </thead>
                    <tbody>";

                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    // Tentukan class berdasarkan stok
                    $stock_class = '';
                    $status_text = '';
                    $stok = intval($row["stok"]);
                    
                    if ($stok == 0) {
                        $stock_class = 'stock-low';
                        $status_text = '⚫ Kosong';
                    } elseif ($stok <= 10) {
                        $stock_class = 'stock-low';
                        $status_text = '🔴 Rendah';
                    } elseif ($stok <= 50) {
                        $stock_class = 'stock-medium';
                        $status_text = '🟡 Sedang';
                    } else {
                        $stock_class = 'stock-good';
                        $status_text = '🟢 Baik';
                    }
                    
                    echo "<tr>
                            <td style='text-align: center; font-weight: bold;'>{$no}</td>
                            <td style='font-family: monospace;'>" . htmlspecialchars($row["kode_brng"]) . "</td>
                            <td style='font-weight: bold;'>" . htmlspecialchars($row["nama_brng"]) . "</td>
                            <td style='text-align: center;'>" . htmlspecialchars($row["kode_sat"]) . "</td>
                            <td style='text-align: right; font-weight: bold; font-size: 14px;'>" . number_format($row["stok"]) . "</td>
                            <td class='{$stock_class}' style='text-align: center;'>{$status_text}</td>
                            <td>" . htmlspecialchars($row["lokasi"]) . "</td>
                            <td>" . htmlspecialchars($row["nm_bangsal"]) . "</td>
                        </tr>";
                    $no++;
                }

                echo "</tbody></table></div>";
            } else {
                echo '<div class="no-data">
                        <h3>📦 Tidak Ada Data Stok</h3>
                        <p>Tidak ditemukan data stok barang sesuai dengan filter yang dipilih.</p>
                        <br>
                        <small><strong>Tips:</strong> 
                            <br>• Coba gunakan kata kunci yang berbeda
                            <br>• Periksa filter lokasi dan level stok
                            <br>• Pastikan data sudah ter-input di sistem
                        </small>
                    </div>';
            }

            // Tutup koneksi
            $koneksi->close();
            ?>
            
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
