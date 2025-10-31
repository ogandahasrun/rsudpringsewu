<?php include "koneksi.php"; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Barang Farmasi - RSUD Pringsewu</title>
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
            grid-template-columns: 1fr auto auto;
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
        .filter-group input {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus {
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
        th.sortable {
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }
        th.sortable:hover {
            background: linear-gradient(45deg, #495057, #6c757d);
        }
        th.sortable::after {
            content: ' ⇅';
            color: #ccc;
            font-size: 12px;
            margin-left: 5px;
        }
        th.sortable.asc::after {
            content: ' ▲';
            color: #fff;
        }
        th.sortable.desc::after {
            content: ' ▼';
            color: #fff;
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
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .info-card {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
        }
        .info-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .help-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            .info-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 700px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .info-cards {
                grid-template-columns: 1fr;
            }
            .info-card .value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Stok Barang Farmasi</h1>
        </div>
        
        <div class="content">

            <div class="back-button">
                <a href="farmasi.php">
                    ← Kembali ke Menu Farmasi
                </a>
            </div>

            <form method="GET" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Stok Barang Farmasi
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="cari">🔍 Cari Barang (Kode atau Nama)</label>
                        <input type="text" 
                               id="cari"
                               name="cari" 
                               placeholder="Masukkan kode barang atau nama barang..." 
                               value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>">
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary">
                            🔍 Cari Data
                        </button>
                    </div>
                    
                    <div>
                        <button type="button" onclick="resetForm()" class="btn btn-success">
                            🔄 Reset Filter
                        </button>
                    </div>
                </div>
            </form>

            <?php
            $filter = "";
            $total_items = 0;
            $total_jenis = 0;
            $zero_stock = 0;
            $low_stock = 0;
            
            if (isset($_GET['cari']) && $_GET['cari'] != "") {
                $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
                $filter = "WHERE (db.kode_brng LIKE '%$cari%' OR db.nama_brng LIKE '%$cari%')";
            }

            // Query untuk statistik
            $stats_query = "
                SELECT
                    COUNT(*) as total_jenis,
                    SUM(COALESCE(gb_go.stok, 0) + COALESCE(gb_dri.stok, 0) + COALESCE(gb_ap.stok, 0) + COALESCE(gb_di.stok, 0) + COALESCE(gb_do.stok, 0)) as total_items,
                    SUM(CASE WHEN (COALESCE(gb_go.stok, 0) + COALESCE(gb_dri.stok, 0) + COALESCE(gb_ap.stok, 0) + COALESCE(gb_di.stok, 0) + COALESCE(gb_do.stok, 0)) = 0 THEN 1 ELSE 0 END) as zero_stock,
                    SUM(CASE WHEN (COALESCE(gb_go.stok, 0) + COALESCE(gb_dri.stok, 0) + COALESCE(gb_ap.stok, 0) + COALESCE(gb_di.stok, 0) + COALESCE(gb_do.stok, 0)) > 0 AND (COALESCE(gb_go.stok, 0) + COALESCE(gb_dri.stok, 0) + COALESCE(gb_ap.stok, 0) + COALESCE(gb_di.stok, 0) + COALESCE(gb_do.stok, 0)) <= 20 THEN 1 ELSE 0 END) as low_stock
                FROM databarang db
                LEFT JOIN gudangbarang gb_go ON gb_go.kode_brng = db.kode_brng AND gb_go.kd_bangsal = 'GO'
                LEFT JOIN gudangbarang gb_dri ON gb_dri.kode_brng = db.kode_brng AND gb_dri.kd_bangsal = 'DRI'
                LEFT JOIN gudangbarang gb_ap ON gb_ap.kode_brng = db.kode_brng AND gb_ap.kd_bangsal = 'AP'
                LEFT JOIN gudangbarang gb_di ON gb_di.kode_brng = db.kode_brng AND gb_di.kd_bangsal = 'DI'
                LEFT JOIN gudangbarang gb_do ON gb_do.kode_brng = db.kode_brng AND gb_do.kd_bangsal = 'DO'
                $filter
            ";
            $stats_result = mysqli_query($koneksi, $stats_query);
            $stats = mysqli_fetch_assoc($stats_result);
            ?>

            <div class="info-cards">
                <div class="info-card">
                    <h3>📦 Total Jenis Barang</h3>
                    <div class="value"><?php echo number_format($stats['total_jenis']); ?></div>
                </div>
                <div class="info-card">
                    <h3>📊 Total Item Stok</h3>
                    <div class="value"><?php echo number_format($stats['total_items']); ?></div>
                </div>
                <div class="info-card">
                    <h3>⚫ Stok Kosong</h3>
                    <div class="value" style="color: #dc3545;"><?php echo number_format($stats['zero_stock']); ?></div>
                </div>
                <div class="info-card">
                    <h3>🔴 Stok Rendah</h3>
                    <div class="value" style="color: #fd7e14;"><?php echo number_format($stats['low_stock']); ?></div>
                </div>
            </div>

            <div class="actions-bar">
                <div class="help-text">
                    💡 <strong>Tips:</strong> Klik pada header tabel untuk mengurutkan data
                </div>
                <button class="btn btn-success" onclick="copyTableToClipboard('dataTable')">
                    📋 Copy Tabel
                </button>
            </div>

            <div class="table-responsive">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th style="text-align: center;">No</th>
                            <th class="sortable" onclick="sortTable(1)">Kode Barang</th>
                            <th class="sortable" onclick="sortTable(2)">Nama Barang</th>
                            <th class="sortable" onclick="sortTable(3)" style="text-align: right;">Harga</th>
                            <th class="sortable" onclick="sortTable(4)" style="text-align: center;">Satuan</th>
                            <th class="sortable" onclick="sortTable(5)" style="text-align: right;">Stok GO</th>
                            <th class="sortable" onclick="sortTable(6)" style="text-align: right;">Stok DRI</th>
                            <th class="sortable" onclick="sortTable(7)" style="text-align: right;">Stok AP</th>
                            <th class="sortable" onclick="sortTable(8)" style="text-align: right;">Stok DI</th>
                            <th class="sortable" onclick="sortTable(9)" style="text-align: right;">Stok DO</th>
                            <th class="sortable" onclick="sortTable(10)" style="text-align: right;">Total Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
        $filter = "";
        if (isset($_GET['cari']) && $_GET['cari'] != "") {
            $cari = mysqli_real_escape_string($koneksi, $_GET['cari']);
            $filter = "WHERE (db.kode_brng LIKE '%$cari%' OR db.nama_brng LIKE '%$cari%')";
        }

        $query = "
            SELECT
                db.kode_brng,
                db.nama_brng,
                db.kode_sat,
                db.dasar,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'GO' THEN gb.stok ELSE 0 END), 0) AS stok_go,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DRI' THEN gb.stok ELSE 0 END), 0) AS stok_dri,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'AP' THEN gb.stok ELSE 0 END), 0) AS stok_ap,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DI' THEN gb.stok ELSE 0 END), 0) AS stok_di,
                COALESCE(SUM(CASE WHEN gb.kd_bangsal = 'DO' THEN gb.stok ELSE 0 END), 0) AS stok_do
            FROM
                databarang db
            LEFT JOIN
                gudangbarang gb ON gb.kode_brng = db.kode_brng
            $filter
            GROUP BY
                db.kode_brng, db.nama_brng, db.kode_sat, db.dasar
            ORDER BY
                db.kode_brng ASC
        ";

        $result = mysqli_query($koneksi, $query);
        $no = 1;

        while ($row = mysqli_fetch_assoc($result)) {
            $total_stok = $row['stok_go'] + $row['stok_dri'] + $row['stok_ap'] + $row['stok_di'] + $row['stok_do'];
            
            // Tentukan class untuk total stok
            $total_class = '';
            if ($total_stok == 0) $total_class = 'stock-zero';
            elseif ($total_stok <= 20) $total_class = 'stock-low';
            else $total_class = 'stock-good';
            
            echo "<tr>
                <td style='text-align: center; font-weight: bold;'>{$no}</td>
                <td style='font-family: monospace; font-weight: bold;'>" . htmlspecialchars($row['kode_brng']) . "</td>
                <td style='text-align: left; font-weight: bold;'>" . htmlspecialchars($row['nama_brng']) . "</td>
                <td style='text-align: right; font-family: monospace;'>Rp " . number_format($row['dasar'], 0, ',', '.') . "</td>
                <td style='text-align: center; font-weight: bold;'>" . htmlspecialchars($row['kode_sat']) . "</td>
                <td class='stock-cell " . ($row['stok_go'] == 0 ? 'stock-zero' : ($row['stok_go'] <= 10 ? 'stock-low' : 'stock-good')) . "'>" . number_format($row['stok_go']) . "</td>
                <td class='stock-cell " . ($row['stok_dri'] == 0 ? 'stock-zero' : ($row['stok_dri'] <= 10 ? 'stock-low' : 'stock-good')) . "'>" . number_format($row['stok_dri']) . "</td>
                <td class='stock-cell " . ($row['stok_ap'] == 0 ? 'stock-zero' : ($row['stok_ap'] <= 10 ? 'stock-low' : 'stock-good')) . "'>" . number_format($row['stok_ap']) . "</td>
                <td class='stock-cell " . ($row['stok_di'] == 0 ? 'stock-zero' : ($row['stok_di'] <= 10 ? 'stock-low' : 'stock-good')) . "'>" . number_format($row['stok_di']) . "</td>
                <td class='stock-cell " . ($row['stok_do'] == 0 ? 'stock-zero' : ($row['stok_do'] <= 10 ? 'stock-low' : 'stock-good')) . "'>" . number_format($row['stok_do']) . "</td>
                <td class='stock-cell $total_class' style='font-size: 14px; font-weight: bold;'>" . number_format($total_stok) . "</td>
            </tr>";
            $no++;
        }

        mysqli_close($koneksi);
        ?>
                    </tbody>
                </table>
            </div>
            
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->

    <script>
    function resetForm() {
        document.getElementById('cari').value = '';
        document.getElementById('cari').focus();
    }
    function copyTableToClipboard(tableID) {
        const table = document.getElementById(tableID);
        const textarea = document.createElement("textarea");
        let text = "";

        for (let row of table.rows) {
            let rowData = [];
            for (let cell of row.cells) {
                // Bersihkan format angka untuk copy
                let cellText = cell.innerText.replace(/Rp\s?/g, '').replace(/\./g, '');
                rowData.push(cellText);
            }
            text += rowData.join("\t") + "\n";
        }

        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        
        // Tampilkan notifikasi yang lebih menarik
        showNotification("✅ Data tabel berhasil disalin ke clipboard!", "success");
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

    let sortDirection = {}; // Track sort direction for each column

    function sortTable(columnIndex) {
        const table = document.getElementById("dataTable");
        const tbody = table.getElementsByTagName("tbody")[0];
        const rows = Array.from(tbody.rows);
        const header = table.getElementsByTagName("th")[columnIndex];
        
        // Determine sort direction
        const isAscending = !sortDirection[columnIndex] || sortDirection[columnIndex] === 'desc';
        sortDirection[columnIndex] = isAscending ? 'asc' : 'desc';
        
        // Clear all header classes
        document.querySelectorAll('th.sortable').forEach(th => {
            th.classList.remove('asc', 'desc');
        });
        
        // Add class to current header
        header.classList.add(sortDirection[columnIndex]);
        
        // Sort rows
        rows.sort((a, b) => {
            let aValue = a.cells[columnIndex].textContent.trim();
            let bValue = b.cells[columnIndex].textContent.trim();
            
            // Check if values are numeric
            const aNumeric = !isNaN(aValue.replace(/[,.]/g, ''));
            const bNumeric = !isNaN(bValue.replace(/[,.]/g, ''));
            
            if (aNumeric && bNumeric) {
                // Numeric comparison
                aValue = parseFloat(aValue.replace(/[,.]/g, ''));
                bValue = parseFloat(bValue.replace(/[,.]/g, ''));
                return isAscending ? aValue - bValue : bValue - aValue;
            } else {
                // String comparison
                return isAscending ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
        
        // Update row numbers
        rows.forEach((row, index) => {
            row.cells[0].textContent = index + 1;
        });
    }
    </script>

</body>
</html>
