<?php
include "koneksi.php";

$selected_bangsal = '';
$bangsal_list = [];
$bangsal_query = mysqli_query($koneksi, "SELECT kd_bangsal, nm_bangsal FROM bangsal ORDER BY nm_bangsal ASC");
while ($row = mysqli_fetch_assoc($bangsal_query)) {
    $bangsal_list[] = $row;
}

// Proses filter
$selected_bangsal = isset($_GET['nm_bangsal']) ? $_GET['nm_bangsal'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Barang Per Lokasi - RSUD Pringsewu</title>
    <style>
        /* ... Copy style dari stokfarmasi.php ... */
        * { box-sizing: border-box; }
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
            grid-template-columns: 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
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
        .filter-group input, .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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
            position: relative;
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
        .stock-cell {
            text-align: right;
            font-weight: bold;
            font-family: monospace;
        }
        .stock-zero {
            color: #dc3545;
            background-color: #f8d7da !important;
        }
        .stock-low {
            color: #856404;
            background-color: #fff3cd !important;
        }
        .stock-good {
            color: #155724;
            background-color: #d4edda !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Stok Barang Per Lokasi</h1>
        </div>
        <div class="content">
            <form method="GET" class="filter-form">
                <div class="filter-title">Filter Stok Barang Per Lokasi</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="nm_bangsal">Pilih Depo/Lokasi</label>
                        <select name="nm_bangsal" id="nm_bangsal" required>
                            <option value="">-- Pilih Depo/Lokasi --</option>
                            <?php foreach($bangsal_list as $bangsal): ?>
                                <option value="<?php echo htmlspecialchars($bangsal['nm_bangsal']); ?>" <?php if($selected_bangsal == $bangsal['nm_bangsal']) echo 'selected'; ?>><?php echo htmlspecialchars($bangsal['nm_bangsal']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="keyword">Cari Barang (Kode/Nama)</label>
                        <input type="text" name="keyword" id="keyword" placeholder="Masukkan kode/nama barang..." value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                    <div>
                        <a href="stokperlokasi.php" class="btn btn-success">Reset</a>
                    </div>
                </div>
            </form>

            <?php if($selected_bangsal): ?>
            <div style="display:flex;justify-content:flex-end;margin-bottom:10px;">
                <button class="btn btn-success" onclick="copyTableToClipboard('tabelStok')" type="button">📋 Copy Tabel</button>
            </div>
            <div class="table-responsive">
                <table id="tabelStok">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Depo</th>
                            <th>Lokasi Barang</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $where = "WHERE bangsal.nm_bangsal = '".mysqli_real_escape_string($koneksi, $selected_bangsal)."'";
                    if($keyword) {
                        $where .= " AND (databarang.kode_brng LIKE '%".mysqli_real_escape_string($koneksi, $keyword)."%' OR databarang.nama_brng LIKE '%".mysqli_real_escape_string($koneksi, $keyword)."%')";
                    }
                    $query = "
                        SELECT databarang.kode_brng, databarang.nama_brng, databarang.kode_sat, gudangbarang.kd_bangsal, gudangbarang.stok, bangsal.nm_bangsal
                        FROM databarang
                        INNER JOIN gudangbarang ON gudangbarang.kode_brng = databarang.kode_brng
                        INNER JOIN bangsal ON gudangbarang.kd_bangsal = bangsal.kd_bangsal
                        $where
                        ORDER BY databarang.nama_brng ASC
                    ";
                    $result = mysqli_query($koneksi, $query);
                    $no = 1;
                    while($row = mysqli_fetch_assoc($result)) {
                        // Query lokasi barang
                        $lokasi = '-';
                        $q_lokasi = mysqli_query($koneksi, "
                            SELECT lokasi_barang_medis.lokasi
                            FROM lokasi_barang_medis
                            WHERE lokasi_barang_medis.kode_brng = '".mysqli_real_escape_string($koneksi, $row['kode_brng'])."' AND lokasi_barang_medis.kd_bangsal = (
                                SELECT kd_bangsal FROM bangsal WHERE nm_bangsal = '".mysqli_real_escape_string($koneksi, $row['nm_bangsal'])."' LIMIT 1
                            )
                            LIMIT 1
                        ");
                        if($r_lokasi = mysqli_fetch_assoc($q_lokasi)) {
                            $lokasi = $r_lokasi['lokasi'];
                        }
                        echo "<tr>
                            <td style='text-align:center;'>$no</td>
                            <td style='font-family:monospace;font-weight:bold;'>".htmlspecialchars($row['kode_brng'])."</td>
                            <td>".htmlspecialchars($row['nama_brng'])."</td>
                            <td style='text-align:center;'>".htmlspecialchars($row['kode_sat'])."</td>
                            <td class='stock-cell'>".number_format($row['stok'])."</td>
                            <td>".htmlspecialchars($row['nm_bangsal'])."</td>
                            <td>".htmlspecialchars($lokasi)."</td>
                        </tr>";
                        $no++;
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div style="margin-top:40px;text-align:center;color:#888;font-size:18px;">Silakan pilih depo/lokasi terlebih dahulu.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function copyTableToClipboard(tableID) {
        const table = document.getElementById(tableID);
        if (!table) return;
        let text = '';
        for (let row of table.rows) {
            let rowData = [];
            for (let cell of row.cells) {
                rowData.push(cell.innerText);
            }
            text += rowData.join('\t') + '\n';
        }
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('✅ Data tabel berhasil disalin ke clipboard!', 'success');
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
</body>
</html>
