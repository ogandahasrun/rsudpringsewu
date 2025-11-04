<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Umpan Balik BPJS - RSUD Pringsewu</title>
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
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .info-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .info-card h3 { margin: 0 0 10px 0; font-size: 2em; }
        .info-card p { margin: 0; font-size: 14px; opacity: 0.9; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .export-btn { background: #17a2b8; color: white; }
        .prb-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; text-align: center; }
        .prb-ya { background: #28a745; }
        .prb-tidak { background: #dc3545; }
        
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
            <h1>🏥 Umpan Balik BPJS</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="jasapelayanan.php">← Kembali ke Menu Jasa Pelayanan</a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Data Umpan Balik BPJS
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group" style="grid-column: 1 / -1;">
                        <label for="no_sep_list">📋 Nomor SEP (pisahkan dengan koma untuk multiple data)</label>
                        <textarea id="no_sep_list" 
                                name="no_sep_list" 
                                placeholder="Contoh: 0807R0060625V000585, 0807R0061224V006392&#10;Atau masukkan satu per baris:&#10;0807R0060625V000585&#10;0807R0061224V006392"
                                rows="4"
                                style="padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; resize: vertical; font-family: monospace;"><?php echo isset($_POST['no_sep_list']) ? htmlspecialchars($_POST['no_sep_list']) : ''; ?></textarea>
                        <small style="color: #6c757d; font-style: italic;">💡 Tips: Anda bisa memasukkan multiple nomor SEP dengan memisahkan menggunakan koma atau baris baru</small>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Cari Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <?php
            // Inisialisasi variabel
            $result = null;
            $total_rows = 0;
            $debug_info = array();
            $query_executed = '';
            
            // Proses form jika ada data yang dikirim
            if (isset($_POST['filter']) && !empty($_POST['no_sep_list'])) {
                $no_sep_input = trim($_POST['no_sep_list']);
                $debug_info[] = "Input diterima: " . $no_sep_input;
                
                // Pisahkan input berdasarkan koma atau baris baru
                $no_sep_array = preg_split('/[,\n\r]+/', $no_sep_input);
                $debug_info[] = "Array setelah split: " . print_r($no_sep_array, true);
                
                // Bersihkan dan filter array
                $no_sep_clean = array();
                foreach ($no_sep_array as $sep) {
                    $sep = trim($sep);
                    if (!empty($sep)) {
                        // Escape string untuk keamanan
                        $no_sep_clean[] = "'" . mysqli_real_escape_string($koneksi, $sep) . "'";
                    }
                }
                $debug_info[] = "Array setelah clean: " . print_r($no_sep_clean, true);
                
                // Jika ada nomor SEP yang valid
                if (!empty($no_sep_clean)) {
                    $no_sep_string = implode(',', $no_sep_clean);
                    $debug_info[] = "String IN clause: " . $no_sep_string;
                    
                    // Cek dulu apakah data SEP ada di tabel bridging_sep
                    $check_query = "SELECT no_sep, no_rawat FROM bridging_sep WHERE no_sep IN ($no_sep_string)";
                    $check_result = mysqli_query($koneksi, $check_query);
                    $check_count = mysqli_num_rows($check_result);
                    $debug_info[] = "Query check: " . $check_query;
                    $debug_info[] = "Data ditemukan di bridging_sep: " . $check_count . " records";
                    
                    if ($check_count > 0) {
                        while ($check_row = mysqli_fetch_assoc($check_result)) {
                            $debug_info[] = "SEP found: " . $check_row['no_sep'] . " -> " . $check_row['no_rawat'];
                        }
                    }
                    
                    $query = "SELECT DISTINCT
                                bridging_sep.no_sep,
                                bridging_sep.no_rawat,
                                COALESCE(detail_nota_inap.besar_bayar, detail_nota_jalan.besar_bayar, 0) as besar_bayar,
                                COALESCE(piutang_pasien.totalpiutang, 0) as totalpiutang
                            FROM
                                bridging_sep
                            LEFT JOIN detail_nota_inap ON bridging_sep.no_rawat = detail_nota_inap.no_rawat
                            LEFT JOIN detail_nota_jalan ON bridging_sep.no_rawat = detail_nota_jalan.no_rawat
                            LEFT JOIN piutang_pasien ON bridging_sep.no_rawat = piutang_pasien.no_rawat
                            WHERE
                                bridging_sep.no_sep IN ($no_sep_string)
                            ORDER BY bridging_sep.tglsep DESC";
                    
                    $query_executed = $query;
                    $debug_info[] = "Query lengkap: " . $query;
                    
                    $result = mysqli_query($koneksi, $query);
                    
                    if ($result) {
                        $total_rows = mysqli_num_rows($result);
                        $debug_info[] = "Query berhasil, rows: " . $total_rows;
                    } else {
                        $debug_info[] = "Query error: " . mysqli_error($koneksi);
                    }
                } else {
                    $debug_info[] = "Tidak ada nomor SEP yang valid setelah cleaning";
                }
            }
            ?>

            <!-- DEBUG INFORMATION -->
            <?php if (isset($_POST['filter']) && !empty($debug_info)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">🔧 Debug Information</h4>
                    <div style="font-family: monospace; font-size: 12px; color: #856404; max-height: 200px; overflow-y: auto;">
                        <?php foreach ($debug_info as $info): ?>
                            <div style="margin-bottom: 5px; border-bottom: 1px dotted #ddd; padding-bottom: 3px;">
                                <?php echo htmlspecialchars($info); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button onclick="this.parentElement.style.display='none'" style="margin-top: 10px; padding: 5px 10px; background: #ffc107; border: none; border-radius: 4px; cursor: pointer;">
                        ❌ Tutup Debug
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($_POST['filter'])): ?>
                <?php if ($result && $total_rows > 0): ?>
                    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div style="font-weight: bold; color: #495057;">
                            📊 Ditemukan: <span style="color: #28a745;"><?php echo number_format($total_rows); ?></span> data umpan balik BPJS
                        </div>
                        <button onclick="copyTableToClipboard()" class="btn export-btn">
                            � Copy ke Clipboard
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table id="dataTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor SEP</th>
                                    <th>Nomor Rawat</th>
                                    <th>Besar Bayar</th>
                                    <th>Total Piutang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total_bayar = 0;
                                $total_piutang = 0;
                                
                                while ($row = mysqli_fetch_assoc($result)): 
                                    $total_bayar += floatval($row['besar_bayar']);
                                    $total_piutang += floatval($row['totalpiutang']);
                                ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: bold;"><?php echo $no; ?></td>
                                        <td style="font-family: monospace; font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($row['no_sep']); ?></td>
                                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                        <td style="text-align: right; font-weight: bold; color: #28a745;">
                                            Rp <?php echo number_format($row['besar_bayar'], 0, ',', '.'); ?>
                                        </td>
                                        <td style="text-align: right; font-weight: bold; color: #dc3545;">
                                            Rp <?php echo number_format($row['totalpiutang'], 0, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php 
                                $no++;
                                endwhile; 
                                ?>
                                
                                <!-- Total Row -->
                                <tr style="background: #e9ecef !important; font-weight: bold;">
                                    <td colspan="11" style="text-align: right; font-weight: bold; color: #495057;">
                                        💰 Total Keseluruhan:
                                    </td>
                                    <td style="text-align: right; font-weight: bold; color: #28a745;">
                                        Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: bold; color: #dc3545;">
                                        Rp <?php echo number_format($total_piutang, 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                <?php else: ?>
                    <div class="no-data">
                        <h3>❌ Data Tidak Ditemukan</h3>
                        <p>Nomor SEP yang Anda masukkan tidak ditemukan dalam database.</p>
                        
                        <?php if (!empty($query_executed)): ?>
                            <div style="margin: 20px 0; padding: 15px; background: #f8d7da; border-radius: 8px; text-align: left;">
                                <h4 style="margin: 0 0 10px 0; color: #721c24;">🔍 Query yang Dijalankan:</h4>
                                <code style="display: block; background: white; padding: 10px; border-radius: 4px; font-size: 11px; word-break: break-all;">
                                    <?php echo htmlspecialchars($query_executed); ?>
                                </code>
                            </div>
                        <?php endif; ?>
                        
                        <div style="text-align: left; background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <h4 style="margin: 0 0 10px 0; color: #0c5460;">🔧 Langkah Troubleshooting:</h4>
                            <ol style="color: #0c5460; margin: 0; text-align: left;">
                                <li><strong>Cek format nomor SEP:</strong> Pastikan format benar (contoh: 0807R0060625V000585)</li>
                                <li><strong>Cek di database:</strong> Jalankan query manual di database untuk memastikan data ada</li>
                                <li><strong>Cek tabel bridging_sep:</strong> Pastikan nomor SEP ada di tabel bridging_sep</li>
                                <li><strong>Cek nama tabel:</strong> Pastikan nama tabel dan field sesuai dengan struktur database</li>
                                <li><strong>Cek koneksi database:</strong> Pastikan koneksi ke database berfungsi</li>
                            </ol>
                        </div>
                        
                        <small><strong>💡 Tips Pencarian:</strong> 
                            <br>• Pastikan nomor SEP benar dan lengkap (tanpa spasi di awal/akhir)
                            <br>• Periksa apakah pasien sudah terdaftar di sistem
                            <br>• Coba search satu nomor SEP dulu untuk testing
                            <br>• Lihat debug information di atas untuk detail lebih lanjut
                        </small>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-data">
                    <h3>� Cari Data Umpan Balik BPJS</h3>
                    <p>Silakan masukkan nomor SEP pada form di atas untuk menampilkan data umpan balik BPJS.</p>
                    <br>
                    <div style="text-align: left; max-width: 500px; margin: 0 auto;">
                        <strong>📋 Cara penggunaan:</strong>
                        <ul style="text-align: left; color: #6c757d;">
                            <li>Masukkan satu atau lebih nomor SEP</li>
                            <li>Pisahkan dengan koma (,) atau baris baru</li>
                            <li>Klik tombol "Cari Data" untuk menampilkan hasil</li>
                            <li>Data akan menampilkan informasi lengkap pasien dan keuangan</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>

    <script>
        function copyTableToClipboard() {
            const table = document.getElementById('dataTable');
            if (!table) {
                showNotification('❌ Tidak ada data untuk disalin', 'error');
                return;
            }
            
            // Create text content from table
            let text = "Data Umpan Balik BPJS - RSUD Pringsewu\n";
            text += "Tanggal: " + new Date().toLocaleDateString('id-ID') + "\n\n";
            
            // Get table headers
            const headers = table.querySelectorAll('thead th');
            const headerTexts = Array.from(headers).map(th => th.textContent.trim());
            text += headerTexts.join('\t') + '\n';
            
            // Get table rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const cellTexts = Array.from(cells).map(td => {
                    // Clean up currency formatting and other formatting
                    let cellText = td.textContent.trim();
                    cellText = cellText.replace(/Rp\s?/g, '').replace(/\./g, '');
                    return cellText;
                });
                text += cellTexts.join('\t') + '\n';
            });
            
            // Copy to clipboard
            if (navigator.clipboard && window.isSecureContext) {
                // Use modern clipboard API
                navigator.clipboard.writeText(text).then(() => {
                    showNotification('✅ Data berhasil disalin ke clipboard!', 'success');
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                // Fallback for older browsers
                fallbackCopyTextToClipboard(text);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showNotification('✅ Data berhasil disalin ke clipboard!', 'success');
                } else {
                    showNotification('❌ Gagal menyalin data. Silakan copy manual.', 'error');
                }
            } catch (err) {
                showNotification('❌ Browser tidak mendukung copy otomatis.', 'error');
            }
            
            document.body.removeChild(textArea);
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
                max-width: 300px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        function resetForm() {
            document.getElementById('no_sep_list').value = '';
            document.getElementById('no_sep_list').focus();
        }
        
        // Auto-focus pada textarea saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('no_sep_list');
            if (textarea && !textarea.value) {
                textarea.focus();
            }
            
            // Add input validation
            textarea.addEventListener('input', function() {
                const value = this.value.trim();
                if (value) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#e9ecef';
                }
            });
        });
        
        // Add keyboard shortcut (Ctrl+Enter) to submit form
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                const form = document.querySelector('form');
                if (form) {
                    form.submit();
                }
            }
        });
    </script>
</body>
</html>