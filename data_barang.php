<?php
// Sertakan file koneksi.php
include "koneksi.php";

// Filter pencarian
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$where = $keyword ? "WHERE databarang.nama_brng LIKE '%$keyword%'" : '';

// Query untuk mengambil data dari tabel databarang
$query = "SELECT
            databarang.kode_brng,
            databarang.nama_brng,
            databarang.dasar,
            databarang.kode_sat
          FROM
            databarang
          $where
          ORDER BY
            databarang.nama_brng ASC";

$result = $koneksi->query($query);

// Hitung statistik
$total_barang = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; position: relative; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .logo-link { position: absolute; left: 25px; top: 50%; transform: translateY(-50%); }
        .logo-link img { width: 50px; height: auto; border-radius: 8px; transition: transform 0.3s ease; }
        .logo-link:hover img { transform: scale(1.1); }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .search-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .search-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .search-grid { display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end; }
        .search-group { display: flex; flex-direction: column; gap: 8px; }
        .search-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .search-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .search-group input:focus { outline: none; border-color: #28a745; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn:hover { transform: translateY(-2px); }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 25px; }
        .stats-card h3 { margin: 0 0 10px 0; font-size: 2em; }
        .stats-card p { margin: 0; font-size: 14px; opacity: 0.9; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .money { text-align: right; font-weight: bold; color: #28a745; }
        .center { text-align: center; }
        .copy-notification { position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1000; transform: translateX(100%); transition: transform 0.3s ease; }
        .copy-notification.show { transform: translateX(0); }
        .copy-notification.error { background: #dc3545; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .search-form { padding: 20px 15px; }
            .search-grid { grid-template-columns: 1fr; gap: 15px; }
            .logo-link { position: relative; left: auto; top: auto; transform: none; margin-bottom: 15px; }
            th, td { padding: 8px 6px; font-size: 12px; }
            .action-buttons { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="logo-link">
                <img src="images/logo.png" alt="Logo RSUD Pringsewu">
            </a>
            <h1>📦 Data Barang</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
            </div>

            <!-- Form pencarian -->
            <form action="" method="get" class="search-form">
                <div class="search-title">
                    🔍 Pencarian Data Barang
                </div>
                
                <div class="search-grid">
                    <div class="search-group">
                        <label for="keyword">Nama Barang</label>
                        <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Masukkan nama barang...">
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Cari</button>
                </div>
                
                <?php if ($keyword): ?>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="data_barang.php" class="btn btn-secondary">🔄 Reset Pencarian</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Statistik -->
            <div class="stats-card">
                <h3><?php echo number_format($total_barang); ?></h3>
                <p><?php echo $keyword ? "Barang ditemukan untuk '$keyword'" : "Total Data Barang"; ?></p>
            </div>

            <!-- Action buttons -->
            <?php if ($total_barang > 0): ?>
                <div class="action-buttons">
                    <button type="button" class="btn btn-info" id="copyTableBtn">📋 Copy ke Clipboard</button>
                    <button type="button" class="btn btn-info" id="exportExcelBtn">📊 Export Excel</button>
                    <button type="button" class="btn btn-warning" id="printTableBtn">🖨️ Print</button>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kode Satuan</th>
                            <th>Harga Dasar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($total_barang > 0) {
                            $no = 1;
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='center'>" . $no++ . "</td>";
                                echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['kode_brng']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['nama_brng']) . "</td>";
                                echo "<td class='center'>" . htmlspecialchars($row['kode_sat']) . "</td>";
                                echo "<td class='money'>Rp " . number_format($row['dasar'], 0, ',', '.') . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='no-data'>";
                            if ($keyword) {
                                echo "Tidak ada barang yang ditemukan dengan nama '<strong>" . htmlspecialchars($keyword) . "</strong>'";
                            } else {
                                echo "Tidak ada data barang tersedia";
                            }
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_barang > 0): ?>
                <div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                    <strong>📊 Total: <?php echo number_format($total_barang); ?> data barang ditemukan</strong>
                    <?php if ($keyword): ?>
                        <br><small>Hasil pencarian untuk: "<strong><?php echo htmlspecialchars($keyword); ?></strong>"</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fungsi copy table ke clipboard
        function copyTableToClipboard() {
            const table = document.getElementById('dataTable');
            if (!table) {
                showNotification('❌ Tidak ada data untuk disalin', true);
                return;
            }

            // Buat string text dari table
            let textToCopy = "DATA BARANG - RSUD PRINGSEWU\n";
            textToCopy += "Tanggal Export: " + new Date().toLocaleDateString('id-ID') + "\n";
            <?php if ($keyword): ?>
            textToCopy += "Pencarian: <?php echo htmlspecialchars($keyword); ?>\n";
            <?php endif; ?>
            textToCopy += "Total Data: <?php echo $total_barang; ?> barang\n\n";
            
            // Header table
            const headers = table.querySelectorAll('thead th');
            let headerRow = "";
            headers.forEach((header, index) => {
                headerRow += header.textContent.trim();
                if (index < headers.length - 1) headerRow += "\t";
            });
            textToCopy += headerRow + "\n";
            
            // Data rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) { // Skip "no data" row
                    let rowText = "";
                    cells.forEach((cell, index) => {
                        rowText += cell.textContent.trim();
                        if (index < cells.length - 1) rowText += "\t";
                    });
                    textToCopy += rowText + "\n";
                }
            });

            // Copy menggunakan modern clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showCopySuccess();
                }).catch(err => {
                    console.error('Gagal copy dengan clipboard API:', err);
                    fallbackCopyTextToClipboard(textToCopy);
                });
            } else {
                fallbackCopyTextToClipboard(textToCopy);
            }
        }

        // Fallback method untuk browser lama
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess();
                } else {
                    showCopyError();
                }
            } catch (err) {
                console.error('Fallback copy failed:', err);
                showCopyError();
            }
            
            document.body.removeChild(textArea);
        }

        // Fungsi export ke Excel
        function exportToExcel() {
            const table = document.getElementById('dataTable');
            if (!table) {
                showNotification('❌ Tidak ada data untuk di-export', true);
                return;
            }
            
            // Buat workbook
            let htmlTable = table.outerHTML;
            
            // Buat data URI untuk download
            const uri = 'data:application/vnd.ms-excel;base64,';
            const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Barang</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta charset="UTF-8"></head><body><h2>Data Barang - RSUD Pringsewu</h2><p>Tanggal Export: ' + new Date().toLocaleDateString('id-ID') + '</p><?php if ($keyword): ?><p>Pencarian: <?php echo htmlspecialchars($keyword); ?></p><?php endif; ?><p>Total: <?php echo $total_barang; ?> data</p>' + htmlTable + '</body></html>';
            
            // Buat link download
            const link = document.createElement('a');
            link.href = uri + btoa(unescape(encodeURIComponent(template)));
            link.download = 'Data_Barang_<?php echo $keyword ? "Search_" . str_replace(" ", "_", $keyword) . "_" : ""; ?>' + new Date().toISOString().slice(0,10) + '.xls';
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Update tombol
            const btn = document.getElementById('exportExcelBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '✅ File Diunduh!';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#17a2b8';
            }, 2000);
        }

        // Fungsi print table
        function printTable() {
            const table = document.getElementById('dataTable');
            if (!table) {
                showNotification('❌ Tidak ada data untuk dicetak', true);
                return;
            }
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Data Barang</title>
                    <meta charset="utf-8">
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h2 { text-align: center; color: #333; }
                        .info { text-align: center; margin-bottom: 20px; color: #666; }
                        table { width: 100%; border-collapse: collapse; font-size: 12px; }
                        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
                        th { background: #f0f0f0; font-weight: bold; }
                        tr:nth-child(even) { background: #f9f9f9; }
                        .money { text-align: right; }
                        .center { text-align: center; }
                        @media print {
                            body { margin: 0; }
                            table { font-size: 10px; }
                            th, td { padding: 4px; }
                        }
                    </style>
                </head>
                <body>
                    <h2>Data Barang - RSUD Pringsewu</h2>
                    <div class="info">
                        <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                        <?php if ($keyword): ?>
                        <p>Pencarian: <?php echo htmlspecialchars($keyword); ?></p>
                        <?php endif; ?>
                        <p>Total: <?php echo $total_barang; ?> data barang</p>
                    </div>
                    ${table.outerHTML}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            // Wait for content to load then print
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Fungsi show notification
        function showNotification(message, isError = false) {
            // Hapus notification lama jika ada
            const oldNotification = document.querySelector('.copy-notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            // Buat notification baru
            const notification = document.createElement('div');
            notification.className = 'copy-notification' + (isError ? ' error' : '');
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Hide notification
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Update fungsi showCopySuccess dan showCopyError
        function showCopySuccess() {
            const btn = document.getElementById('copyTableBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '✅ Berhasil!';
            btn.style.background = '#28a745';
            
            showNotification('✅ Data barang berhasil disalin ke clipboard!');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#17a2b8';
            }, 2000);
        }

        function showCopyError() {
            const btn = document.getElementById('copyTableBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '❌ Gagal';
            btn.style.background = '#dc3545';
            
            showNotification('❌ Gagal menyalin data ke clipboard!', true);
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '#17a2b8';
            }, 2000);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const copyBtn = document.getElementById('copyTableBtn');
            const exportBtn = document.getElementById('exportExcelBtn');
            const printBtn = document.getElementById('printTableBtn');
            
            if (copyBtn) copyBtn.onclick = copyTableToClipboard;
            if (exportBtn) exportBtn.onclick = exportToExcel;
            if (printBtn) printBtn.onclick = printTable;
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi
$koneksi->close();
?>
