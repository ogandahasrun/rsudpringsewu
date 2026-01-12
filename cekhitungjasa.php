<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Hitung Jasa - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #007bff, #00c6ff); color: white; padding: 25px; text-align: center; }
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
        .filter-group input, .filter-group select, .filter-group textarea { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus, .filter-group textarea:focus { outline: none; border-color: #007bff; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #00c6ff); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .export-btn { background: #17a2b8; color: white; }
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
            <h1>🧮 Cek Hitung Jasa</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="jasapelayanan.php">← Kembali ke Menu Jasa Pelayanan</a>
            </div>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Data Hitung Jasa
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search_by">Cari Berdasarkan</label>
                        <select id="search_by" name="search_by">
                            <option value="no_sep" selected>No. SEP</option>
                            <option value="no_rawat">No. Rawat</option>
                        </select>
                    </div>
                    <div class="filter-group" style="grid-column: 1 / -1;">
                        <label for="search_value">Masukkan No. SEP / No. Rawat (pisahkan dengan koma atau baris baru untuk multiple data)</label>
                        <textarea id="search_value" name="search_value" placeholder="Contoh: 1234567890123, 2024/12/24/006392\nAtau masukkan satu per baris:\n1234567890123\n2024/12/24/006392" rows="4" style="padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; resize: vertical; font-family: monospace;"><?php echo isset($_POST['search_value']) ? htmlspecialchars($_POST['search_value']) : ''; ?></textarea>
                        <small style="color: #6c757d; font-style: italic;">💡 Tips: Anda bisa memasukkan multiple data dengan memisahkan menggunakan koma atau baris baru</small>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">🔍 Cari Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset</button>
                </div>
            </form>

<?php
$result = null;
$total_rows = 0;
$debug_info = array();
$query_executed = '';
$original_array = array();
$final_data = array();

if (isset($_POST['filter']) && !empty($_POST['search_value'])) {
    $search_by = $_POST['search_by'] === 'no_rawat' ? 'no_rawat' : 'no_sep';
    $search_input = trim($_POST['search_value']);
    $debug_info[] = "Input diterima: " . $search_input;
    $array = preg_split('/[\,\n\r]+/', $search_input);
    $debug_info[] = "Array setelah split: " . print_r($array, true);
    $clean = array();
    foreach ($array as $val) {
        $val = trim($val);
        if (!empty($val)) {
            $original_array[] = $val;
            $clean[] = "'" . mysqli_real_escape_string($koneksi, $val) . "'";
        }
    }
    $debug_info[] = "Array setelah clean: " . print_r($clean, true);
    if (!empty($clean)) {
        $in_string = implode(',', $clean);
        $debug_info[] = "String IN clause: " . $in_string;
        $query = "SELECT rspsw_umbal.no_sep, rspsw_umbal.no_rawat, rspsw_umbal.bulanklaim, rspsw_umbal.diajukan, rspsw_umbal.disetujui FROM rspsw_umbal WHERE rspsw_umbal." . $search_by . " IN ($in_string) ORDER BY rspsw_umbal.bulanklaim DESC";
        $query_executed = $query;
        $debug_info[] = "Query lengkap: " . $query;
        $result = mysqli_query($koneksi, $query);
        $found_data = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $found_data[$row[$search_by]] = $row;
            }
            $debug_info[] = "Query berhasil, rows: " . count($found_data);
        } else {
            $debug_info[] = "Query error: " . mysqli_error($koneksi);
        }
        foreach ($original_array as $search_val) {
            if (isset($found_data[$search_val])) {
                $final_data[] = $found_data[$search_val];
            } else {
                $final_data[] = array(
                    'no_sep' => $search_by == 'no_sep' ? $search_val : '-',
                    'no_rawat' => $search_by == 'no_rawat' ? $search_val : '-',
                    'bulanklaim' => '-',
                    'diajukan' => '-',
                    'disetujui' => '-',
                    'not_found' => true
                );
            }
        }
        $total_rows = count($final_data);
    } else {
        $debug_info[] = "Tidak ada data yang valid setelah cleaning";
    }
}
?>

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

<?php if (isset($_POST['filter']) && $total_rows > 0): ?>
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="font-weight: bold; color: #495057;">
            📊 Menampilkan: <span style="color: #007bff;"><?php echo number_format($total_rows); ?></span> data yang dicari
        </div>
        <button onclick="copyTableToClipboard()" class="btn export-btn">
            📋 Copy ke Clipboard
        </button>
    </div>
    <div class="table-container">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. SEP</th>
                    <th>No. Rawat</th>
                    <th>Bulan Klaim</th>
                    <th>Diajukan</th>
                    <th>Disetujui</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($final_data as $row): 
                    $is_not_found = isset($row['not_found']) && $row['not_found'];
                ?>
                    <tr style="<?php echo $is_not_found ? 'background: #ffe6e6 !important;' : ''; ?>">
                        <td style="text-align: center; font-weight: bold;"><?php echo $no; ?></td>
                        <td style="font-family: monospace; font-weight: bold; color: <?php echo $is_not_found ? '#dc3545' : '#007bff'; ?>;">
                            <?php echo htmlspecialchars($row['no_sep']); ?>
                        </td>
                        <td style="font-family: monospace; font-weight: bold; color: <?php echo $is_not_found ? '#dc3545' : '#007bff'; ?>;">
                            <?php echo htmlspecialchars($row['no_rawat']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['bulanklaim']); ?></td>
                        <td><?php echo htmlspecialchars($row['diajukan']); ?></td>
                        <td><?php echo htmlspecialchars($row['disetujui']); ?></td>
                    </tr>
                <?php 
                $no++;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
<?php elseif (!isset($_POST['filter'])): ?>
    <div class="no-data">
        <h3>🧮 Cek Hitung Jasa</h3>
        <p>Silakan masukkan No. SEP atau No. Rawat pada form di atas untuk menampilkan data hitung jasa.</p>
        <br>
        <div style="text-align: left; max-width: 500px; margin: 0 auto;">
            <strong>📋 Cara penggunaan:</strong>
            <ul style="text-align: left; color: #6c757d;">
                <li>Pilih "Cari Berdasarkan" (No. SEP atau No. Rawat)</li>
                <li>Masukkan satu atau lebih data</li>
                <li>Pisahkan dengan koma (,) atau baris baru</li>
                <li>Klik tombol "Cari Data" untuk menampilkan hasil</li>
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
            let text = "Data Hitung Jasa - RSUD Pringsewu\n";
            text += "Tanggal: " + new Date().toLocaleDateString('id-ID') + "\n\n";
            const headers = table.querySelectorAll('thead th');
            const headerTexts = Array.from(headers).map(th => th.textContent.trim());
            text += headerTexts.join('\t') + '\n';
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const cellTexts = Array.from(cells).map(td => td.textContent.trim());
                text += cellTexts.join('\t') + '\n';
            });
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showNotification('✅ Data berhasil disalin ke clipboard!', 'success');
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
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
                background: ${type === 'success' ? '#007bff' : '#dc3545'};
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
            document.getElementById('search_value').value = '';
            document.getElementById('search_value').focus();
        }
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('search_value');
            if (textarea && !textarea.value) {
                textarea.focus();
            }
            textarea.addEventListener('input', function() {
                const value = this.value.trim();
                if (value) {
                    this.style.borderColor = '#007bff';
                } else {
                    this.style.borderColor = '#e9ecef';
                }
            });
        });
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