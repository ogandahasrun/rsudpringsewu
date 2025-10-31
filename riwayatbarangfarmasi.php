<?php
include 'koneksi.php';

// Ambil daftar lokasi/bangsal untuk dropdown
$bangsal_options = [];
$bangsal_query = "SELECT kd_bangsal, nm_bangsal FROM bangsal ORDER BY nm_bangsal";
$bangsal_result = mysqli_query($koneksi, $bangsal_query);
while ($row = mysqli_fetch_assoc($bangsal_result)) {
    $bangsal_options[$row['kd_bangsal']] = $row['nm_bangsal'];
}

// Tangkap nilai filter dari formulir pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$kd_bangsal = isset($_GET['kd_bangsal']) ? $_GET['kd_bangsal'] : '';

// Hanya eksekusi query jika ada keyword atau filter
if (!empty($keyword) || !empty($kd_bangsal) || isset($_GET['tanggal_awal']) || isset($_GET['tanggal_akhir'])) {
    $where = [];
    $where[] = "riwayat_barang_medis.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    if (!empty($kd_bangsal)) {
        $where[] = "riwayat_barang_medis.kd_bangsal = '$kd_bangsal'";
    }
    if (!empty($keyword)) {
        $where[] = "(databarang.nama_brng LIKE '%$keyword%' OR riwayat_barang_medis.kode_brng LIKE '%$keyword%')";
    }
    $where_sql = implode(' AND ', $where);

    $sql = "SELECT
                riwayat_barang_medis.kode_brng,
                databarang.nama_brng,
                databarang.kode_sat,
                riwayat_barang_medis.stok_awal,
                riwayat_barang_medis.masuk,
                riwayat_barang_medis.keluar,
                riwayat_barang_medis.stok_akhir,
                riwayat_barang_medis.posisi,
                riwayat_barang_medis.tanggal,
                riwayat_barang_medis.jam,
                riwayat_barang_medis.keterangan,
                bangsal.nm_bangsal
            FROM
                riwayat_barang_medis
            INNER JOIN databarang ON riwayat_barang_medis.kode_brng = databarang.kode_brng
            INNER JOIN bangsal ON riwayat_barang_medis.kd_bangsal = bangsal.kd_bangsal
            WHERE $where_sql
            ORDER BY
                riwayat_barang_medis.kode_brng ASC,
                riwayat_barang_medis.tanggal ASC,
                riwayat_barang_medis.jam ASC";

    $result = $koneksi->query($sql);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Barang Farmasi - RSUD Pringsewu</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .info-summary {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
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
            min-width: 1000px;
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
        .number-cell {
            text-align: right;
            font-weight: bold;
            font-family: monospace;
        }
        .date-cell {
            text-align: center;
            font-family: monospace;
        }
        .code-cell {
            font-family: monospace;
            font-weight: bold;
        }
        .stock-change {
            font-weight: bold;
        }
        .stock-in {
            color: #28a745;
        }
        .stock-out {
            color: #dc3545;
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
            .info-summary {
                flex-direction: column;
                text-align: center;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 800px;
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
                    showNotification("✅ Tabel berhasil disalin ke clipboard!", "success");
                } catch(err) {
                    showNotification("❌ Gagal menyalin tabel", "error");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '';
            document.getElementById('tanggal_akhir').value = '';
            document.getElementById('kd_bangsal').value = '';
            document.getElementById('keyword').value = '';
            document.getElementById('keyword').focus();
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 1000;
                font-weight: bold;
                transform: translateX(400px);
                transition: all 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Riwayat Barang Farmasi</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">
                    <img src="images/logo.png" alt="Logo">
                    ← Kembali ke Farmasi
                </a>
            </div>

            <form method="GET" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Riwayat Barang Farmasi
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="kd_bangsal">🏥 Lokasi/Bangsal</label>
                        <select id="kd_bangsal" name="kd_bangsal">
                            <option value="">-- Semua Lokasi --</option>
                            <?php foreach ($bangsal_options as $kode => $nama): ?>
                                <option value="<?php echo htmlspecialchars($kode); ?>" 
                                        <?php echo ($kd_bangsal == $kode) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nama); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="keyword">🔍 Cari Barang</label>
                        <input type="text" 
                               id="keyword" 
                               name="keyword" 
                               placeholder="Kode barang atau nama barang..." 
                               value="<?php echo htmlspecialchars($keyword); ?>">
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
            // Tampilkan hasil pencarian
            if (isset($result) && $result->num_rows > 0) {
                $total_rows = $result->num_rows;
                
                // Hitung statistik
                $total_masuk = 0;
                $total_keluar = 0;
                $temp_result = $koneksi->query($sql);
                while ($temp_row = $temp_result->fetch_assoc()) {
                    $total_masuk += $temp_row['masuk'];
                    $total_keluar += $temp_row['keluar'];
                }
                
                echo '<div class="info-summary">
                        <div class="summary-item">
                            <div class="label">📊 Total Data</div>
                            <div class="value">' . number_format($total_rows) . '</div>
                        </div>
                        <div class="summary-item">
                            <div class="label">📥 Total Masuk</div>
                            <div class="value stock-in">' . number_format($total_masuk) . '</div>
                        </div>
                        <div class="summary-item">
                            <div class="label">📤 Total Keluar</div>
                            <div class="value stock-out">' . number_format($total_keluar) . '</div>
                        </div>
                        <div class="summary-item">
                            <button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>
                        </div>
                      </div>';
                
                echo '<div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th style="text-align: right;">Stok Awal</th>
                                    <th style="text-align: right;">Masuk</th>
                                    <th style="text-align: right;">Keluar</th>
                                    <th style="text-align: right;">Stok Akhir</th>
                                    <th>Posisi</th>
                                    <th style="text-align: center;">Tanggal</th>
                                    <th style="text-align: center;">Jam</th>
                                    <th>Bangsal</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>';

                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td style='text-align: center; font-weight: bold;'>{$no}</td>
                            <td class='code-cell'>" . htmlspecialchars($row['kode_brng']) . "</td>
                            <td style='font-weight: bold;'>" . htmlspecialchars($row['nama_brng']) . "</td>
                            <td style='text-align: center;'>" . htmlspecialchars($row['kode_sat']) . "</td>
                            <td class='number-cell'>" . number_format($row['stok_awal']) . "</td>
                            <td class='number-cell stock-change " . ($row['masuk'] > 0 ? 'stock-in' : '') . "'>" . number_format($row['masuk']) . "</td>
                            <td class='number-cell stock-change " . ($row['keluar'] > 0 ? 'stock-out' : '') . "'>" . number_format($row['keluar']) . "</td>
                            <td class='number-cell' style='font-weight: bold; font-size: 14px;'>" . number_format($row['stok_akhir']) . "</td>
                            <td>" . htmlspecialchars($row['posisi']) . "</td>
                            <td class='date-cell'>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>
                            <td class='date-cell'>" . htmlspecialchars($row['jam']) . "</td>
                            <td>" . htmlspecialchars($row['nm_bangsal']) . "</td>
                            <td>" . htmlspecialchars($row['keterangan']) . "</td>
                          </tr>";
                    $no++;
                }

                echo '</tbody></table></div>';
            } elseif (isset($result)) {
                echo '<div class="no-data">
                        <h3>📋 Tidak Ada Data Riwayat</h3>
                        <p>Tidak ditemukan data riwayat barang sesuai dengan filter yang dipilih.</p>
                        <br>
                        <small><strong>Tips:</strong> 
                            <br>• Coba ubah periode tanggal
                            <br>• Pilih lokasi/bangsal yang berbeda
                            <br>• Gunakan kata kunci pencarian yang lebih spesifik
                        </small>
                    </div>';
            } else {
                echo '<div class="no-data">
                        <h3>🔍 Silakan Gunakan Filter</h3>
                        <p>Gunakan form filter di atas untuk mencari data riwayat barang farmasi.</p>
                        <br>
                        <small><strong>Panduan:</strong> 
                            <br>• Pilih periode tanggal untuk mencari data
                            <br>• Filter berdasarkan lokasi/bangsal tertentu
                            <br>• Cari barang berdasarkan kode atau nama
                        </small>
                    </div>';
            }
            ?>
            
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>

<?php
$koneksi->close();
?>