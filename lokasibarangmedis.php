<?php
include 'koneksi.php';

// Handle form submission untuk simpan/edit lokasi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $kode_brng = mysqli_real_escape_string($koneksi, $_POST['kode_brng']);
    $kd_bangsal = mysqli_real_escape_string($koneksi, $_POST['kd_bangsal']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    
    // Buat tabel lokasi_barang_medis jika belum ada
    $create_table = "CREATE TABLE IF NOT EXISTS lokasi_barang_medis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode_brng VARCHAR(15) UNIQUE NOT NULL,
        kd_bangsal VARCHAR(5) DEFAULT 'GO',
        lokasi TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($koneksi, $create_table);
    
    if ($_POST['action'] == 'simpan') {
        // Insert data baru
        $insert_query = "INSERT INTO lokasi_barang_medis (kode_brng, kd_bangsal, lokasi) 
                        VALUES ('$kode_brng', '$kd_bangsal', '$lokasi') 
                        ON DUPLICATE KEY UPDATE 
                        kd_bangsal = VALUES(kd_bangsal),
                        lokasi = VALUES(lokasi)";
        
        if (mysqli_query($koneksi, $insert_query)) {
            $message = "✅ Data berhasil disimpan!";
        } else {
            $message = "❌ Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    } elseif ($_POST['action'] == 'edit') {
        // Update data yang sudah ada
        $update_query = "UPDATE lokasi_barang_medis 
                        SET kd_bangsal = '$kd_bangsal', lokasi = '$lokasi' 
                        WHERE kode_brng = '$kode_brng'";
        
        if (mysqli_query($koneksi, $update_query)) {
            $message = "✅ Data berhasil diperbarui!";
        } else {
            $message = "❌ Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$show_all = isset($_GET['show_all']) ? $_GET['show_all'] : '';
$filter_bangsal = isset($_GET['filter_bangsal']) ? $_GET['filter_bangsal'] : '';
$filter_lokasi = isset($_GET['filter_lokasi']) ? $_GET['filter_lokasi'] : '';
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
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .input-small { width: 80px; padding: 6px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 12px; text-align: center; }
        .input-medium { width: 200px; padding: 6px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 12px; }
        .input-small:focus, .input-medium:focus { outline: none; border-color: #28a745; }
        .form-inline { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .save-btn { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: bold; white-space: nowrap; }
        .save-btn:hover { background: #218838; }
        .edit-btn { background: #ffc107; color: #212529; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: bold; white-space: nowrap; }
        .edit-btn:hover { background: #e0a800; }
        .data-display { background: #e8f5e8; padding: 6px 10px; border-radius: 6px; font-weight: bold; color: #155724; border: 1px solid #28a745; margin-right: 8px; }
        .bangsal-go { background: #20c997; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px 6px; font-size: 12px; }
            .form-inline { flex-direction: column; align-items: stretch; }
            .input-small, .input-medium { width: 100%; margin-bottom: 5px; }
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
                        <input type="text" id="filter_bangsal" name="filter_bangsal" placeholder="Contoh: GO, AP, DRI..." value="<?php echo htmlspecialchars($filter_bangsal); ?>">
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
            // Buat tabel lokasi_barang_medis jika belum ada
            $create_table = "CREATE TABLE IF NOT EXISTS lokasi_barang_medis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kode_brng VARCHAR(15) UNIQUE NOT NULL,
                kd_bangsal VARCHAR(5) DEFAULT 'GO',
                lokasi TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            mysqli_query($koneksi, $create_table);
            
            if (!empty($search) || !empty($show_all) || !empty($filter_bangsal) || !empty($filter_lokasi)) {
                // Query data barang dengan lokasi medis dan stok
                $query = "SELECT 
                            databarang.kode_brng,
                            databarang.nama_brng,
                            databarang.kode_sat,
                            COALESCE(lokasi_barang_medis.kd_bangsal, 'GO') as kd_bangsal,
                            COALESCE(lokasi_barang_medis.lokasi, '') as lokasi,
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
                    $where_conditions[] = "COALESCE(lokasi_barang_medis.kd_bangsal, 'GO') LIKE '%$bangsal_escaped%'";
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
                                        <th>Lokasi & Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $stok = $row['stok'];
                        $stok_class = $stok > 0 ? 'color: #28a745; font-weight: bold;' : 'color: #dc3545; font-weight: bold;';
                        
                        echo "<tr>
                                <td style='text-align: center; font-weight: bold;'>{$no}</td>
                                <td style='font-family: monospace;'>" . htmlspecialchars($row['kode_brng']) . "</td>
                                <td>" . htmlspecialchars($row['nama_brng']) . "</td>
                                <td style='text-align: center; font-family: monospace;'>" . htmlspecialchars($row['kode_sat'] ?: '-') . "</td>
                                <td style='text-align: center;'><span class='bangsal-go'>" . htmlspecialchars($row['kd_bangsal']) . "</span></td>
                                <td style='text-align: right; {$stok_class}'>" . number_format($stok) . "</td>
                                <td>";
                        
                        if (!empty($row['lokasi'])) {
                            // Mode edit - data sudah ada
                            echo '<form method="POST" class="form-inline">
                                    <input type="hidden" name="kode_brng" value="' . htmlspecialchars($row['kode_brng']) . '">
                                    <input type="hidden" name="action" value="edit">
                                    <span class="data-display">' . htmlspecialchars($row['lokasi']) . '</span>
                                    <input type="text" name="kd_bangsal" class="input-small" value="' . htmlspecialchars($row['kd_bangsal']) . '" placeholder="Bangsal">
                                    <input type="text" name="lokasi" class="input-medium" value="' . htmlspecialchars($row['lokasi']) . '" placeholder="Lokasi detail...">
                                    <button type="submit" class="edit-btn">✏️ Edit</button>
                                  </form>';
                        } else {
                            // Mode simpan - data belum ada
                            echo '<form method="POST" class="form-inline">
                                    <input type="hidden" name="kode_brng" value="' . htmlspecialchars($row['kode_brng']) . '">
                                    <input type="hidden" name="action" value="simpan">
                                    <input type="text" name="kd_bangsal" class="input-small" value="GO" placeholder="Bangsal">
                                    <input type="text" name="lokasi" class="input-medium" placeholder="Masukkan lokasi..." required>
                                    <button type="submit" class="save-btn">💾 Simpan</button>
                                  </form>';
                        }
                        
                        echo "</td></tr>";
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
                                <li><strong>Kolom 7:</strong> Lokasi (input manual) + Tombol Aksi</li>
                            </ol>
                            <br>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7;">
                                <strong>💡 Fitur:</strong>
                                <ul style="margin: 10px 0; padding-left: 20px;">
                                    <li><strong>Tombol Simpan:</strong> Untuk data lokasi baru</li>
                                    <li><strong>Tombol Edit:</strong> Untuk mengubah data yang sudah ada</li>
                                    <li><strong>kd_bangsal:</strong> Default "GO", dapat diubah saat input</li>
                                    <li><strong>Lokasi:</strong> Input manual sesuai kebutuhan</li>
                                    <li><strong>Stok:</strong> Otomatis dari tabel gudangbarang sesuai kd_bangsal (hijau = ada stok, merah = stok 0)</li>
                                </ul>
                            </div>
                        </div>
                      </div>';
            }
            
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
            
            // Auto-focus pada input lokasi yang kosong
            const emptyInputs = document.querySelectorAll('input[placeholder="Masukkan lokasi..."]');
            if (emptyInputs.length > 0) {
                emptyInputs[0].focus();
            }
            
            // Enter key handling untuk form
            const inputs = document.querySelectorAll('.input-medium');
            inputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const form = this.closest('form');
                        if (form && this.value.trim() !== '') {
                            form.submit();
                        }
                    }
                });
            });
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
