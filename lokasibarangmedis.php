<?php
include 'koneksi.php';

// Fetch list of bangsal for dropdowns
$bangsal_list = [];
$bangsal_query = mysqli_query($koneksi, "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE nm_bangsal IS NOT NULL AND nm_bangsal != '' AND nm_bangsal != '-' ORDER BY nm_bangsal ASC");
if ($bangsal_query) {
    while ($row_b = mysqli_fetch_assoc($bangsal_query)) {
        $bangsal_list[] = $row_b;
    }
}

// Handle form submission untuk simpan/edit lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $kode_brng = mysqli_real_escape_string($koneksi, $_POST['kode_brng']);
    $kd_bangsal = mysqli_real_escape_string($koneksi, $_POST['kd_bangsal']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $stok_minimal_bangsal = isset($_POST['stok_minimal_bangsal']) ? mysqli_real_escape_string($koneksi, $_POST['stok_minimal_bangsal']) : 0;
    $original_kd_bangsal = isset($_POST['original_kd_bangsal']) ? mysqli_real_escape_string($koneksi, $_POST['original_kd_bangsal']) : '';
    
    if (!empty($original_kd_bangsal) && $original_kd_bangsal !== $kd_bangsal) {
        // Jika user mengubah bangsal, kita harus mengupdate baris composite key yang lama
        $update_query = "UPDATE lokasi_barang_medis 
                        SET kd_bangsal = '$kd_bangsal', lokasi = '$lokasi', stok_minimal_bangsal = '$stok_minimal_bangsal' 
                        WHERE kode_brng = '$kode_brng' AND kd_bangsal = '$original_kd_bangsal'";
        if (mysqli_query($koneksi, $update_query)) {
            $message = "✅ Data berhasil diperbarui!";
        } else {
            $message = "❌ Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    } else {
        // Simpan baru atau update (jika bangsal sama)
        $insert_query = "INSERT INTO lokasi_barang_medis (kode_brng, kd_bangsal, lokasi, stok_minimal_bangsal) 
                        VALUES ('$kode_brng', '$kd_bangsal', '$lokasi', '$stok_minimal_bangsal') 
                        ON DUPLICATE KEY UPDATE 
                        lokasi = VALUES(lokasi),
                        stok_minimal_bangsal = VALUES(stok_minimal_bangsal)";
        if (mysqli_query($koneksi, $insert_query)) {
            $message = "✅ Data berhasil disimpan!";
        } else {
            $message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$show_all = isset($_GET['show_all']) ? $_GET['show_all'] : '';
$filter_bangsal = isset($_GET['filter_bangsal']) ? $_GET['filter_bangsal'] : '';
$filter_lokasi = isset($_GET['filter_lokasi']) ? $_GET['filter_lokasi'] : '';

// Edit form parameters
$edit_mode = false;
$edit_kode_brng = '';
$edit_nama_brng = '';
$edit_kd_bangsal = '';
$edit_lokasi = '';
$edit_stok_minimal = 0;

if (isset($_GET['edit_kode_brng']) && isset($_GET['edit_kd_bangsal'])) {
    $edit_mode = true;
    $edit_kode_brng = mysqli_real_escape_string($koneksi, $_GET['edit_kode_brng']);
    $edit_kd_bangsal = mysqli_real_escape_string($koneksi, $_GET['edit_kd_bangsal']);
    
    // Fetch details
    $edit_query = "SELECT databarang.nama_brng, lokasi_barang_medis.lokasi, lokasi_barang_medis.stok_minimal_bangsal 
                   FROM databarang 
                   LEFT JOIN lokasi_barang_medis ON databarang.kode_brng = lokasi_barang_medis.kode_brng 
                        AND lokasi_barang_medis.kd_bangsal = '$edit_kd_bangsal'
                   WHERE databarang.kode_brng = '$edit_kode_brng'";
    $edit_result = mysqli_query($koneksi, $edit_query);
    if ($edit_result && $edit_row = mysqli_fetch_assoc($edit_result)) {
        $edit_nama_brng = $edit_row['nama_brng'];
        $edit_lokasi = $edit_row['lokasi'];
        $edit_stok_minimal = $edit_row['stok_minimal_bangsal'];
    }
}

// Preserve search parameters for redirect links
$url_params = http_build_query([
    'search' => $search,
    'show_all' => $show_all,
    'filter_bangsal' => $filter_bangsal,
    'filter_lokasi' => $filter_lokasi
]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Barang medis - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: linear-gradient(45deg, #007bff, #0056b3); color: white; }
        .btn:hover { transform: translateY(-2px); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .message.success { background: #d1edff; color: #0c5460; border: 1px solid #bee5eb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .bangsal-go { background: #20c997; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        
        /* Layout */
        .main-grid { margin-top: 20px; }
        
        /* Modal Overlay */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; animation: fadeIn 0.3s ease; padding: 15px; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        /* Form Card inside Modal */
        .form-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 100%; max-width: 450px; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .form-card-header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 15px; font-weight: bold; font-size: 15px; text-align: center; }
        .form-card-body { padding: 20px; }
        .form-group-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; text-align: left; }
        .form-group-field label { font-weight: bold; font-size: 12px; color: #495057; }
        .form-input-field, .form-select-field, .input-disabled { padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 13px; outline: none; transition: all 0.3s ease; width: 100%; box-sizing: border-box; }
        .form-input-field:focus, .form-select-field:focus { border-color: #28a745; }
        .input-disabled { background-color: #e9ecef; color: #6c757d; cursor: not-allowed; }
        .form-card-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: center; }
        
        /* Action buttons in table */
        .btn-action { display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-decoration: none; text-align: center; transition: all 0.3s ease; }
        .btn-edit-row { background: #ffc107; color: #212529; }
        .btn-edit-row:hover { background: #e0a800; transform: translateY(-1px); }
        .btn-setup-row { background: #28a745; color: white; }
        .btn-setup-row:hover { background: #218838; transform: translateY(-1px); }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px 6px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 Lokasi Barang medis</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>

            <?php if (isset($message)): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="main-grid">
                <!-- Modal Popup for Form Card -->
                <?php if ($edit_mode): ?>
                    <div class="modal-overlay">
                        <div class="form-card">
                            <div class="form-card-header">✏️ Edit Lokasi & Stok Min</div>
                            <div class="form-card-body">
                                <form method="POST" action="lokasibarangmedis.php?<?php echo $url_params; ?>">
                                    <input type="hidden" name="action" value="simpan">
                                    <input type="hidden" name="kode_brng" value="<?php echo htmlspecialchars($edit_kode_brng); ?>">
                                    <input type="hidden" name="original_kd_bangsal" value="<?php echo htmlspecialchars($edit_kd_bangsal); ?>">
                                    
                                    <div class="form-group-field">
                                        <label>📦 Kode Barang</label>
                                        <input type="text" class="input-disabled" value="<?php echo htmlspecialchars($edit_kode_brng); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group-field">
                                        <label>🏷️ Nama Barang</label>
                                        <textarea class="input-disabled" style="resize: none; height: 60px;" disabled><?php echo htmlspecialchars($edit_nama_brng); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group-field">
                                        <label>🏥 Nama Bangsal</label>
                                        <select name="kd_bangsal" class="form-select-field">
                                            <?php foreach ($bangsal_list as $b): ?>
                                                <option value="<?php echo htmlspecialchars($b['kd_bangsal']); ?>" <?php echo ($edit_kd_bangsal == $b['kd_bangsal']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($b['nm_bangsal'] . ' (' . $b['kd_bangsal'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group-field">
                                        <label>📍 Nama Lokasi</label>
                                        <input type="text" name="lokasi" class="form-input-field" value="<?php echo htmlspecialchars($edit_lokasi); ?>" placeholder="Contoh: Rak A-1..." required>
                                    </div>
                                    
                                    <div class="form-group-field">
                                        <label>📊 Stok Minimal</label>
                                        <input type="number" name="stok_minimal_bangsal" class="form-input-field" value="<?php echo (int)$edit_stok_minimal; ?>" min="0" required>
                                    </div>
                                    
                                    <div class="form-card-actions">
                                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">💾 Simpan</button>
                                        <a href="lokasibarangmedis.php?<?php echo $url_params; ?>" class="btn btn-secondary" style="padding: 10px 20px;">❌ Batal</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Main Content Area: Filter & Table -->
                <div class="right-content">
                    <form method="GET" class="filter-form">
                        <div class="filter-title">🔍 Filter Data Barang</div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search">🔍 Cari Barang</label>
                        <input type="text" id="search" name="search" placeholder="Kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="show_all">📋 Tampilkan</label>
                        <select id="show_all" name="show_all">
                            <option value="">Hanya dengan filter</option>
                            <option value="1" <?php echo ($show_all == '1') ? 'selected' : ''; ?>>Semua barang</option>
                            <option value="has_location" <?php echo ($show_all == 'has_location') ? 'selected' : ''; ?>>Yang sudah ada lokasi</option>
                            <option value="no_location" <?php echo ($show_all == 'no_location') ? 'selected' : ''; ?>>Yang belum ada lokasi</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_bangsal">🏥 Filter Bangsal</label>
                        <select id="filter_bangsal" name="filter_bangsal">
                            <option value="">-- Semua Bangsal --</option>
                            <?php foreach ($bangsal_list as $b): ?>
                                <option value="<?php echo htmlspecialchars($b['kd_bangsal']); ?>" <?php echo ($filter_bangsal == $b['kd_bangsal']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['nm_bangsal'] . ' (' . $b['kd_bangsal'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_lokasi">📍 Filter Lokasi</label>
                        <input type="text" id="filter_lokasi" name="filter_lokasi" placeholder="Cari lokasi spesifik..." value="<?php echo htmlspecialchars($filter_lokasi); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan Data</button>
                    <a href="lokasibarangmedis.php" class="btn btn-secondary">🔄 Reset Filter</a>
                </div>
            </form>

            <?php
            // ...existing code...
            
            if (!empty($search) || !empty($show_all) || !empty($filter_bangsal) || !empty($filter_lokasi)) {
                // Query data barang dengan lokasi medis dan stok
                $query = "SELECT 
                            databarang.kode_brng,
                            databarang.nama_brng,
                            databarang.kode_sat,
                            COALESCE(lokasi_barang_medis.kd_bangsal, 'GO') as kd_bangsal,
                            COALESCE(lokasi_barang_medis.lokasi, '') as lokasi,
                            COALESCE(lokasi_barang_medis.stok_minimal_bangsal, 0) as stok_minimal_bangsal,
                            COALESCE(gudangbarang.stok, 0) as stok
                        FROM databarang
                        LEFT JOIN lokasi_barang_medis ON databarang.kode_brng = lokasi_barang_medis.kode_brng
                        LEFT JOIN gudangbarang ON databarang.kode_brng = gudangbarang.kode_brng 
                            AND COALESCE(lokasi_barang_medis.kd_bangsal, 'GO') = gudangbarang.kd_bangsal";
                
                // Build WHERE conditions
                $where_conditions = [];
                
                if (!empty($search)) {
                    $search_escaped = mysqli_real_escape_string($koneksi, $search);
                    $where_conditions[] = "(databarang.kode_brng LIKE '%$search_escaped%' OR databarang.nama_brng LIKE '%$search_escaped%')";
                }
                
                // Filter berdasarkan status lokasi
                if ($show_all == 'has_location') {
                    $where_conditions[] = "lokasi_barang_medis.lokasi IS NOT NULL AND lokasi_barang_medis.lokasi != ''";
                } elseif ($show_all == 'no_location') {
                    $where_conditions[] = "(lokasi_barang_medis.lokasi IS NULL OR lokasi_barang_medis.lokasi = '')";
                }
                
                // Filter berdasarkan kd_bangsal
                if (!empty($filter_bangsal)) {
                    $bangsal_escaped = mysqli_real_escape_string($koneksi, $filter_bangsal);
                    $where_conditions[] = "COALESCE(lokasi_barang_medis.kd_bangsal, 'GO') = '$bangsal_escaped'";
                }
                
                // Filter berdasarkan lokasi
                if (!empty($filter_lokasi)) {
                    $lokasi_escaped = mysqli_real_escape_string($koneksi, $filter_lokasi);
                    $where_conditions[] = "lokasi_barang_medis.lokasi LIKE '%$lokasi_escaped%'";
                }
                
                $where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                
                $query .= " $where_sql
                        ORDER BY 
                            CASE WHEN lokasi_barang_medis.lokasi IS NOT NULL AND lokasi_barang_medis.lokasi != '' THEN 0 ELSE 1 END,
                            databarang.nama_brng ASC";
                
                $result = mysqli_query($koneksi, $query);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    echo '<div style="margin-bottom: 15px; text-align: right;">
                            <button id="copyTableBtn" class="btn btn-success">📋 Copy Tabel ke Clipboard</button>
                          </div>';
                    
                    echo '<div class="table-container" id="tableData">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>kode_brng</th>
                                        <th>nama_brng</th>
                                        <th>kode_sat</th>
                                        <th>kd_bangsal</th>
                                        <th>Stok</th>
                                        <th>Stok Min</th>
                                        <th>Lokasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $stok = $row['stok'];
                        $stok_min = $row['stok_minimal_bangsal'];
                        
                        // Stok di bawah stok minimal -> merah. Di atas/sama dengan -> hijau.
                        $stok_class = $stok < $stok_min ? 'color: #dc3545; font-weight: bold;' : 'color: #28a745; font-weight: bold;';
                        
                        $lokasi_display = !empty($row['lokasi']) ? htmlspecialchars($row['lokasi']) : '<span style="color: #999; font-style: italic;">- Belum diatur -</span>';
                        $aksi_text = !empty($row['lokasi']) ? '✏️ Edit' : '✏️ Atur Lokasi';
                        $aksi_class = !empty($row['lokasi']) ? 'btn-edit-row' : 'btn-setup-row';
                        
                        // Edit URL parameters preserving filters
                        $edit_url = "lokasibarangmedis.php?edit_kode_brng=" . urlencode($row['kode_brng']) . "&edit_kd_bangsal=" . urlencode($row['kd_bangsal']) . "&" . $url_params;
                        
                        echo "<tr>
                                <td style='text-align: center; font-weight: bold;'>{$no}</td>
                                <td>" . htmlspecialchars($row['kode_brng']) . "</td>
                                <td>" . htmlspecialchars($row['nama_brng']) . "</td>
                                <td style='text-align: center;'>" . htmlspecialchars($row['kode_sat'] ?: '-') . "</td>
                                <td style='text-align: center;'><span class='bangsal-go'>" . htmlspecialchars($row['kd_bangsal']) . "</span></td>
                                <td style='text-align: right; {$stok_class}'>" . number_format($stok) . "</td>
                                <td style='text-align: right; font-weight: bold; color: #495057;'>" . number_format($stok_min) . "</td>
                                <td>{$lokasi_display}</td>
                                <td style='text-align: center;'>
                                    <a href='{$edit_url}' class='btn-action {$aksi_class}'>{$aksi_text}</a>
                                </td>
                              </tr>";
                        $no++;
                    }
                    
                    echo '</tbody></table></div>';
                    echo '<div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                            <strong>📊 Total: ' . number_format(mysqli_num_rows($result)) . ' barang medis ditemukan</strong>
                          </div>';
                          
                } else {
                    echo '<div class="no-data">
                            <h3>📭 Tidak ada data ditemukan</h3>
                            <p>Tidak ada barang yang sesuai dengan filter yang dipilih.</p>
                          </div>';
                }
            } else {
                echo '<div class="no-data">
                        <h3>🔍 Gunakan Filter untuk Menampilkan Data</h3>
                        <p>Silakan pilih opsi tampilan atau masukkan kata kunci pencarian untuk menampilkan tabel barang medis.</p>
                        <br>
                        <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                            <h4>📋 Struktur Tabel Barang medis:</h4>
                            <ol style="text-align: left; line-height: 1.8;">
                                <li><strong>Kolom 1:</strong> Nomor urut (sequential)</li>
                                <li><strong>Kolom 2:</strong> kode_brng (dari databarang)</li>
                                <li><strong>Kolom 3:</strong> nama_brng (dari databarang)</li>
                                <li><strong>Kolom 4:</strong> kode_sat (dari databarang)</li>
                                <li><strong>Kolom 5:</strong> kd_bangsal (default: <span class="bangsal-go">GO</span>)</li>
                                <li><strong>Kolom 6:</strong> Stok (dari gudangbarang sesuai kd_bangsal)</li>
                                <li><strong>Kolom 7:</strong> Stok Min (stok minimal pada bangsal/lokasi)</li>
                                <li><strong>Kolom 8:</strong> Lokasi (input manual)</li>
                                <li><strong>Kolom 9:</strong> Aksi (Tombol atur/edit lokasi)</li>
                            </ol>
                            <br>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7;">
                                <strong>💡 Fitur:</strong>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    <li><strong>Tombol Simpan:</strong> Untuk data lokasi baru</li>
                                    <li><strong>Tombol Edit:</strong> Untuk mengubah data yang sudah ada</li>
                                    <li><strong>kd_bangsal:</strong> Default "GO", dapat diubah saat input</li>
                                    <li><strong>Lokasi:</strong> Input manual sesuai kebutuhan</li>
                                    <li><strong>Stok:</strong> Dibandingkan dengan Stok Minimal (merah = di bawah min, hijau = aman)</li>
                                </ul>
                            </div>
                        </div>
                      </div>';
            }
            ?>
                </div> <!-- closing right-content -->
            </div> <!-- closing main-grid -->
            <?php
            
            mysqli_close($koneksi);
            ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Copy table to clipboard
            const copyBtn = document.getElementById('copyTableBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const tableData = document.getElementById('tableData');
                    const range = document.createRange();
                    range.selectNode(tableData);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                    
                    try {
                        const successful = document.execCommand('copy');
                        if (successful) {
                            showNotification('✅ Tabel berhasil disalin ke clipboard!', 'success');
                        } else {
                            showNotification('❌ Gagal menyalin tabel.', 'error');
                        }
                    } catch (err) {
                        showNotification('❌ Browser tidak mendukung copy tabel otomatis.', 'error');
                    }
                    
                    window.getSelection().removeAllRanges();
                });
            }
            
            // Auto-focus pada input lokasi saat mengedit
            const lokasiInput = document.querySelector('input[name="lokasi"]');
            if (lokasiInput) {
                lokasiInput.focus();
                lokasiInput.select();
            }
        });
        
        // Show notification function
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
</body>
</html>
