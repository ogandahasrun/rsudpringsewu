<?php
include 'koneksi.php';

// Set default values untuk filter tanggal (check POST first, then GET, then today)
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : (isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d'));
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : (isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d'));

$query = "SELECT
    pemesanan.no_faktur AS no_faktur,
    pemesanan.no_order AS no_order,
    datasuplier.nama_suplier AS nama_suplier,
    detailpesan.kode_brng AS kode_brng,
    databarang.nama_brng AS nama_brng,
    kodesatuan.satuan AS satuan,
    detailpesan.jumlah AS jumlah,
    detailpesan.h_pesan AS h_pesan,
    (detailpesan.jumlah * detailpesan.h_pesan) AS subtotal,
    pemesanan.tgl_faktur AS tgl_faktur,
    pemesanan.tgl_pesan AS tgl_pesan,
    pemesanan.total2 AS total2,
    pemesanan.ppn AS ppn,
    pemesanan.tagihan AS tagihan,
    datasuplier.direktur AS direktur,
    datasuplier.NPWP AS NPWP,
    datasuplier.jabatan AS jabatan
    FROM
    ((((pemesanan
    JOIN detailpesan ON (detailpesan.no_faktur = pemesanan.no_faktur))
    JOIN datasuplier ON (pemesanan.kode_suplier = datasuplier.kode_suplier))
    JOIN databarang ON (detailpesan.kode_brng = databarang.kode_brng))
    JOIN kodesatuan ON (detailpesan.kode_sat = kodesatuan.kode_sat AND databarang.kode_sat = kodesatuan.kode_sat AND databarang.kode_satbesar = kodesatuan.kode_sat))
    WHERE
    pemesanan.tgl_faktur BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    ORDER BY pemesanan.no_faktur ASC";

// Eksekusi query
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rencana Belanja Farmasi (Flat Detail)</title>
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
        .filter-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn:hover { transform: translateY(-2px); }
        .copy-notification { position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1000; transform: translateX(100%); transition: transform 0.3s ease; }
        .copy-notification.show { transform: translateX(0); }
        .copy-notification.error { background: #dc3545; }
        .info-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 25px; }
        .info-card h3 { margin: 0 0 10px 0; font-size: 1.5em; }
        .info-card p { margin: 0; font-size: 14px; opacity: 0.9; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .money { text-align: right; font-weight: bold; color: #28a745; }
        .center { text-align: center; }
        .summary-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #28a745; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h4 { margin: 0 0 5px 0; font-size: 1.2em; color: #28a745; }
        .stat-card p { margin: 0; font-size: 12px; color: #666; }
        
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
<div class="container" id="allTables">
    <div class="header">
        <h1>🛒 Rencana Belanja Farmasi (Detail Flat)</h1>
    </div>
    
    <div class="content">
        <div class="back-button">
            <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
        </div>

        <form method="post" class="filter-form">
            <div class="filter-title">
                📅 Filter Periode Tanggal
            </div>
            
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="tanggal_awal">Tanggal Awal</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
                </div>
                
                <div class="filter-group">
                    <label for="tanggal_akhir">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">📊 Tampilkan Data</button>
                <a href="rencanabelanja2.php" class="btn btn-secondary">🔄 Reset Filter</a>
                <button type="button" class="btn btn-info" id="copyTableBtn">📋 Copy ke Clipboard</button>
                <button type="button" class="btn btn-info" id="exportExcelBtn">📊 Export Excel</button>
                <a href="rencanabelanja.php?tanggal_awal=<?php echo urlencode($tanggal_awal); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="btn btn-warning">📋 Lihat Versi Ringkas</a>
            </div>
        </form>

        <?php
        // Hitung total dan statistik
        if ($result && mysqli_num_rows($result) > 0) {
            $total_items = 0;
            $total_value = 0;
            $suppliers = [];
            
            // Reset pointer untuk menghitung statistik
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)) {
                $total_items += $row['jumlah'];
                $total_value += $row['subtotal'];
                $suppliers[$row['nama_suplier']] = true;
            }
            
            // Reset pointer lagi untuk display data
            mysqli_data_seek($result, 0);
        ?>
        
        <div class="summary-stats">
            <div class="stat-card">
                <h4><?php echo number_format(mysqli_num_rows($result)); ?></h4>
                <p>Total Transaksi</p>
            </div>
            <div class="stat-card">
                <h4><?php echo number_format($total_items); ?></h4>
                <p>Total Item</p>
            </div>
            <div class="stat-card">
                <h4>Rp <?php echo number_format($total_value, 0, ',', '.'); ?></h4>
                <p>Total Nilai</p>
            </div>
            <div class="stat-card">
                <h4><?php echo number_format(count($suppliers)); ?></h4>
                <p>Total Supplier</p>
            </div>
        </div>
        
        <?php } ?>
        
        <div class="table-container">
            <table id="dataTable">
        <thead>
            <tr>
                <th>No</th>
                <th>No Faktur</th>
                <th>No Order</th>
                <th>Supplier</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th>Tanggal Faktur</th>
                <th>Tanggal Pesan</th>
                <th>Total</th>
                <th>PPN</th>
                <th>Tagihan</th>
                <th>Direktur</th>
                <th>NPWP</th>
                <th>Jabatan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rows = [];
            $total_jumlah = 0;
            $total_subtotal = 0;
            $total_total2 = 0;
            $total_ppn = 0;
            $total_tagihan = 0;
            $last_faktur_total = '';
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                    $total_jumlah += $row['jumlah'];
                    $total_subtotal += $row['subtotal'];
                    if ($row['no_faktur'] !== $last_faktur_total) {
                        $last_faktur_total = $row['no_faktur'];
                        $total_total2 += $row['total2'];
                        $total_ppn += $row['ppn'];
                        $total_tagihan += $row['tagihan'];
                    }
                }
            }
            if (!isset($rows) || !is_array($rows)) {
                $rows = [];
            }
            ?>
            <?php
            if (count($rows) > 0) {
                $no = 1;
                foreach ($rows as $row) {
                    ?>
                    <tr>
                        <td class="center"><?php echo $no++; ?></td>
                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_faktur']); ?></td>
                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_order']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_suplier']); ?></td>
                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_brng']); ?></td>
                        <td class="center"><?php echo htmlspecialchars($row['satuan']); ?></td>
                        <td class="center"><?php echo htmlspecialchars($row['jumlah']); ?></td>
                        <td class="money"><?php echo htmlspecialchars($row['h_pesan']); ?></td>
                        <td class="money"><?php echo htmlspecialchars($row['subtotal']); ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($row['tgl_faktur'])); ?></td>
                        <td class="center"><?php echo date('d/m/Y', strtotime($row['tgl_pesan'])); ?></td>
                        <td class="money"><?php echo htmlspecialchars($row['total2']); ?></td>
                        <td class="money"><?php echo htmlspecialchars($row['ppn']); ?></td>
                        <td class="money"><?php echo htmlspecialchars($row['tagihan']); ?></td>
                        <td><?php echo htmlspecialchars($row['direktur']); ?></td>
                        <td style="font-family: monospace;"><?php echo htmlspecialchars($row['NPWP']); ?></td>
                        <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="18" class="no-data">Tidak ada data untuk periode yang dipilih</td>
                </tr>
                <?php
            }
            ?>
        </tbody>
        <tfoot>
            <?php if (count($rows) > 0) { ?>
                <tr style="background:#d4edda;font-weight:bold;">
                    <td colspan="7" style="text-align:right;">TOTAL</td>
                    <td class="center"><?php echo $total_jumlah; ?></td>
                    <td></td>
                    <td class="money"><?php echo $total_subtotal; ?></td>
                    <td></td>
                    <td></td>
                    <td class="money"><?php echo $total_total2; ?></td>
                    <td class="money"><?php echo $total_ppn; ?></td>
                    <td class="money"><?php echo $total_tagihan; ?></td>
                    <td colspan="3"></td>
                </tr>
            <?php } ?>
        </tfoot>
            </table>
        </div>
        
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                <strong>📊 Total: <?php echo number_format(mysqli_num_rows($result)); ?> transaksi ditemukan</strong>
                <br>
                <small>Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir)); ?></small>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
// Fungsi show notification
function showNotification(message, isError = false) {
    const oldNotification = document.querySelector('.copy-notification');
    if (oldNotification) {
        oldNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'copy-notification' + (isError ? ' error' : '');
    notification.innerHTML = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Fungsi copy table ke clipboard
function copyTableToClipboard() {
    const table = document.getElementById('dataTable');
    if (!table) {
        showNotification('❌ Tidak ada data untuk disalin', true);
        return;
    }

    let textToCopy = "RENCANA BELANJA FARMASI (DETAIL FLAT)\n";
    textToCopy += "Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>\n";
    textToCopy += "Tanggal Export: " + new Date().toLocaleDateString('id-ID') + "\n\n";
    
    const headers = table.querySelectorAll('thead th');
    let headerRow = "";
    headers.forEach((header, index) => {
        headerRow += header.textContent.trim();
        if (index < headers.length - 1) headerRow += "\t";
    });
    textToCopy += headerRow + "\n";
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let rowText = "";
        cells.forEach((cell, index) => {
            rowText += cell.textContent.trim();
            if (index < cells.length - 1) rowText += "\t";
        });
        textToCopy += rowText + "\n";
    });

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

function showCopySuccess() {
    const btn = document.getElementById('copyTableBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '✅ Berhasil!';
    btn.style.background = '#28a745';
    
    showNotification('✅ Data berhasil disalin ke clipboard!');
    
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

// Fungsi export ke Excel
function exportToExcel() {
    const table = document.getElementById('dataTable');
    if (!table) {
        showNotification('❌ Tidak ada data untuk di-export', true);
        return;
    }
    
    const htmlTable = table.outerHTML;
    const uri = 'data:application/vnd.ms-excel;base64,';
    const template = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rencana Belanja Detail</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta charset="UTF-8"></head><body><h2>Rencana Belanja Farmasi (Detail Flat) - RSUD Pringsewu</h2><p>Tanggal Export: \${new Date().toLocaleDateString('id-ID')}</p><p>Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir)); ?></p>\${htmlTable}</body></html>`;
    
    const link = document.createElement('a');
    link.href = uri + btoa(unescape(encodeURIComponent(template)));
    link.download = 'Rencana_Belanja_Detail_' + new Date().toISOString().slice(0,10) + '.xls';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    const btn = document.getElementById('exportExcelBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '✅ File Diunduh!';
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '#17a2b8';
    }, 2000);
}

document.getElementById('copyTableBtn').onclick = copyTableToClipboard;
document.getElementById('exportExcelBtn').onclick = exportToExcel;

// Set default date to today if not set
document.addEventListener('DOMContentLoaded', function() {
    const tanggalAwal = document.getElementById('tanggal_awal');
    const tanggalAkhir = document.getElementById('tanggal_akhir');
    
    if (!tanggalAwal.value) {
        tanggalAwal.value = new Date().toISOString().split('T')[0];
    }
    if (!tanggalAkhir.value) {
        tanggalAkhir.value = new Date().toISOString().split('T')[0];
    }
});
</script>
</body>
</html>

<?php
if (isset($result)) {
    mysqli_free_result($result);
}
mysqli_close($koneksi);
?>
