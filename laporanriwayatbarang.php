<?php
set_time_limit(10000); // Batas waktu eksekusi 10000 detik (sekitar 2 jam 46 menit)
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Barang Medis - RSUD Pringsewu</title>
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
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group select:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #17a2b8; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; max-height: 70vh; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 11px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 10px 6px; text-align: center; font-weight: bold; font-size: 10px; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
        td { padding: 8px 6px; border-bottom: 1px solid #e9ecef; text-align: center; }
        td:nth-child(3) { text-align: left; font-weight: 500; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .total-row { background: #e9ecef !important; font-weight: bold; }
        .total-row td { background: #e9ecef !important; border-top: 2px solid #495057; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .month-group { border-left: 2px solid #495057; }
        .loading { text-align: center; padding: 40px; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #28a745; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media print {
            body { background: white; padding: 0; }
            .header { background: #28a745 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .back-button, .filter-form, .btn { display: none; }
            .container { box-shadow: none; }
            table { font-size: 9px; }
            th, td { padding: 4px 3px; }
            .table-container { max-height: none; }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            table { font-size: 9px; }
            th, td { padding: 6px 3px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Laporan Riwayat Barang Medis</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>

            <form method="POST" class="filter-form" id="filterForm">
                <div class="filter-title">
                    🔍 Filter Laporan
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tahun">📅 Tahun</label>
                        <select id="tahun" name="tahun" required>
                            <?php
                            $current_year = date('Y');
                            $selected_year = isset($_POST['tahun']) ? $_POST['tahun'] : $current_year;
                            for ($year = $current_year; $year >= 2020; $year--) {
                                $selected = ($year == $selected_year) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="bangsal">🏥 Lokasi</label>
                        <select id="bangsal" name="bangsal" required>
                            <?php
                            $selected_bangsal = isset($_POST['bangsal']) ? $_POST['bangsal'] : '';
                            
                            // Query untuk mengambil daftar bangsal yang ada di riwayat_barang_medis
                            $query_bangsal = "SELECT DISTINCT kd_bangsal FROM riwayat_barang_medis WHERE posisi <> 'opname' ORDER BY kd_bangsal ASC";
                            $result_bangsal = mysqli_query($koneksi, $query_bangsal);
                            
                            echo "<option value=''>-- Pilih Lokasi --</option>";
                            while ($row_bangsal = mysqli_fetch_assoc($result_bangsal)) {
                                $kd = $row_bangsal['kd_bangsal'];
                                $selected = ($kd == $selected_bangsal) ? 'selected' : '';
                                echo "<option value='$kd' $selected>$kd</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="copyTableToClipboard()" class="btn btn-success">
                        📋 Copy ke Clipboard
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-success">
                        🖨️ Cetak
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['filter']) && !empty($_POST['tahun']) && !empty($_POST['bangsal'])) {
                $tahun = mysqli_real_escape_string($koneksi, $_POST['tahun']);
                $bangsal = mysqli_real_escape_string($koneksi, $_POST['bangsal']);
                $tahun_sebelum = $tahun - 1;
                
                echo "<div style='margin-bottom: 20px; text-align: center;'>";
                echo "<h3 style='margin: 0; color: #495057;'>";
                echo "📊 Laporan Riwayat Barang Medis - Tahun <span style='color: #28a745;'>$tahun</span> - Lokasi <span style='color: #28a745;'>$bangsal</span>";
                echo "</h3></div>";
                
                // Query utama untuk mendapatkan daftar barang
                $query_barang = "SELECT
                    databarang.kode_brng,
                    databarang.nama_brng,
                    databarang.kode_sat,
                    databarang.dasar
                FROM
                    databarang
                ORDER BY
                    databarang.nama_brng ASC";
                
                $result_barang = mysqli_query($koneksi, $query_barang);
                
                if ($result_barang && mysqli_num_rows($result_barang) > 0):
            ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Kode<br>Barang</th>
                            <th rowspan="2">Nama Barang</th>
                            <th rowspan="2">Satuan</th>
                            <th rowspan="2">Awal<br>Tahun</th>
                            <th colspan="3" class="month-group">Januari</th>
                            <th colspan="3" class="month-group">Februari</th>
                            <th colspan="3" class="month-group">Maret</th>
                            <th colspan="3" class="month-group">April</th>
                            <th colspan="3" class="month-group">Mei</th>
                            <th colspan="3" class="month-group">Juni</th>
                            <th colspan="3" class="month-group">Juli</th>
                            <th colspan="3" class="month-group">Agustus</th>
                            <th colspan="3" class="month-group">September</th>
                            <th colspan="3" class="month-group">Oktober</th>
                            <th colspan="3" class="month-group">November</th>
                            <th colspan="3" class="month-group">Desember</th>
                            <th rowspan="2">Harga<br>Satuan</th>
                            <th rowspan="2">Nilai</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <th class="month-group">Masuk</th>
                                <th>Keluar</th>
                                <th>Sisa</th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $grand_total_nilai = 0;
                        
                        while ($row = mysqli_fetch_assoc($result_barang)) {
                            $kode_brng = $row['kode_brng'];
                            $nama_brng = $row['nama_brng'];
                            $kode_sat = $row['kode_sat'];
                            $harga_dasar = $row['dasar'];
                            
                            // 1. STOK AWAL TAHUN (dari tahun sebelumnya tanggal 31 Desember)
                            $query_awal = "SELECT stok_akhir, tanggal, jam
                                FROM riwayat_barang_medis
                                WHERE kode_brng = '$kode_brng'
                                AND kd_bangsal = '$bangsal'
                                AND posisi <> 'opname'
                                AND YEAR(tanggal) <= $tahun_sebelum
                                ORDER BY tanggal DESC, jam DESC
                                LIMIT 1";
                            $result_awal = mysqli_query($koneksi, $query_awal);
                            $stok_awal_tahun = 0;
                            if ($result_awal && mysqli_num_rows($result_awal) > 0) {
                                $row_awal = mysqli_fetch_assoc($result_awal);
                                $stok_awal_tahun = $row_awal['stok_akhir'];
                            }
                            
                            // Array untuk menyimpan data per bulan
                            $data_bulan = array();
                            $sisa_bulan_sebelum = $stok_awal_tahun;
                            
                            // 2. LOOP UNTUK SETIAP BULAN (Januari - Desember)
                            for ($bulan = 1; $bulan <= 12; $bulan++) {
                                $bulan_str = str_pad($bulan, 2, '0', STR_PAD_LEFT);
                                
                                // Query untuk mendapatkan total masuk dan keluar per bulan
                                $query_bulan = "SELECT
                                    COALESCE(SUM(masuk), 0) as total_masuk,
                                    COALESCE(SUM(keluar), 0) as total_keluar
                                FROM riwayat_barang_medis
                                WHERE kode_brng = '$kode_brng'
                                AND kd_bangsal = '$bangsal'
                                AND posisi <> 'opname'
                                AND DATE_FORMAT(tanggal, '%Y-%m') = '$tahun-$bulan_str'";
                                
                                $result_bulan = mysqli_query($koneksi, $query_bulan);
                                $row_bulan = mysqli_fetch_assoc($result_bulan);
                                
                                $masuk = $row_bulan['total_masuk'];
                                $keluar = $row_bulan['total_keluar'];
                                $sisa = $sisa_bulan_sebelum + $masuk - $keluar;
                                
                                $data_bulan[$bulan] = array(
                                    'masuk' => $masuk,
                                    'keluar' => $keluar,
                                    'sisa' => $sisa
                                );
                                
                                $sisa_bulan_sebelum = $sisa;
                            }
                            
                            // Sisa bulan Desember (bulan ke-12)
                            $sisa_desember = $data_bulan[12]['sisa'];
                            
                            // Nilai = Sisa Desember x Harga Satuan
                            $nilai = $sisa_desember * $harga_dasar;
                            $grand_total_nilai += $nilai;
                            
                            // Tampilkan hanya jika ada aktivitas (stok awal > 0 atau ada transaksi)
                            $ada_aktivitas = ($stok_awal_tahun > 0);
                            if (!$ada_aktivitas) {
                                foreach ($data_bulan as $data) {
                                    if ($data['masuk'] > 0 || $data['keluar'] > 0) {
                                        $ada_aktivitas = true;
                                        break;
                                    }
                                }
                            }
                            
                            if ($ada_aktivitas):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kode_brng); ?></td>
                            <td><?php echo htmlspecialchars($nama_brng); ?></td>
                            <td><?php echo htmlspecialchars($kode_sat); ?></td>
                            <td><?php echo number_format($stok_awal_tahun, 0, ',', '.'); ?></td>
                            
                            <?php for ($bulan = 1; $bulan <= 12; $bulan++): ?>
                                <td class="month-group"><?php echo number_format($data_bulan[$bulan]['masuk'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format($data_bulan[$bulan]['keluar'], 0, ',', '.'); ?></td>
                                <td style="font-weight: bold; <?php echo $data_bulan[$bulan]['sisa'] < 0 ? 'color: red;' : ''; ?>">
                                    <?php echo number_format($data_bulan[$bulan]['sisa'], 0, ',', '.'); ?>
                                </td>
                            <?php endfor; ?>
                            
                            <td style="text-align: right;">Rp <?php echo number_format($harga_dasar, 0, ',', '.'); ?></td>
                            <td style="text-align: right; font-weight: bold; color: #28a745;">
                                Rp <?php echo number_format($nilai, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        }
                        ?>
                        
                        <!-- Grand Total -->
                        <tr class="total-row">
                            <td colspan="41" style="text-align: right; padding-right: 10px;">
                                <strong>GRAND TOTAL NILAI:</strong>
                            </td>
                            <td style="text-align: right; font-weight: bold; color: #28a745;">
                                Rp <?php echo number_format($grand_total_nilai, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php 
                else:
            ?>
                <div class="no-data">
                    <h3>❌ Data Tidak Ditemukan</h3>
                    <p>Tidak ada data barang dalam sistem.</p>
                </div>
            <?php 
                endif;
            } else {
            ?>
                <div class="no-data">
                    <h3>📋 Laporan Riwayat Barang Medis</h3>
                    <p>Silakan pilih <strong>Tahun</strong> dan <strong>Lokasi</strong> pada form di atas untuk menampilkan laporan.</p>
                    <br>
                    <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                        <strong>📊 Informasi Laporan:</strong>
                        <ul style="text-align: left; color: #6c757d;">
                            <li>Laporan menampilkan riwayat barang medis per tahun dan lokasi</li>
                            <li>Data mencakup stok awal tahun, transaksi masuk/keluar per bulan</li>
                            <li>Sisa dihitung otomatis: Sisa Bulan Sebelum + Masuk - Keluar</li>
                            <li>Nilai dihitung dari: Sisa Desember × Harga Satuan</li>
                            <li>Hanya menampilkan barang yang memiliki aktivitas transaksi</li>
                            <li>Data opname tidak diikutsertakan dalam perhitungan</li>
                        </ul>
                    </div>
                </div>
            <?php } ?>
            
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>

    <script>
        function resetForm() {
            document.getElementById('tahun').value = '<?php echo date('Y'); ?>';
            document.getElementById('bangsal').value = '';
        }
        
        function copyTableToClipboard() {
            const table = document.querySelector('.table-container table');
            if (!table) {
                showNotification('❌ Tidak ada data untuk disalin', 'error');
                return;
            }
            
            // Create text content from table
            let text = "Laporan Riwayat Barang Medis - RSUD Pringsewu\n";
            text += "Tanggal Export: " + new Date().toLocaleDateString('id-ID') + "\n";
            
            <?php if (isset($_POST['filter'])): ?>
            text += "Tahun: <?php echo isset($tahun) ? $tahun : ''; ?>\n";
            text += "Lokasi: <?php echo isset($bangsal) ? $bangsal : ''; ?>\n";
            <?php endif; ?>
            text += "\n";
            
            // Get table headers
            const headerRows = table.querySelectorAll('thead tr');
            headerRows.forEach(row => {
                const headers = row.querySelectorAll('th');
                const headerTexts = Array.from(headers).map(th => {
                    const colspan = th.getAttribute('colspan') || 1;
                    const rowspan = th.getAttribute('rowspan') || 1;
                    let cellText = th.textContent.trim().replace(/\s+/g, ' ');
                    return cellText;
                });
                text += headerTexts.join('\t') + '\n';
            });
            
            // Get table body rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const cellTexts = Array.from(cells).map(td => {
                    // Clean up formatting
                    let cellText = td.textContent.trim();
                    cellText = cellText.replace(/Rp\s?/g, '').replace(/\./g, '').replace(/,/g, '.');
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
        
        // Validasi form sebelum submit
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const tahun = document.getElementById('tahun').value;
            const bangsal = document.getElementById('bangsal').value;
            
            if (!tahun || !bangsal) {
                e.preventDefault();
                alert('⚠️ Silakan pilih Tahun dan Lokasi terlebih dahulu!');
                return false;
            }
            
            // Show loading indicator
            const content = document.querySelector('.content');
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'loading';
            loadingDiv.innerHTML = '<div class="loading-spinner"></div><p>Memuat data, mohon tunggu...</p>';
            content.appendChild(loadingDiv);
        });
    </script>
</body>
</html>
