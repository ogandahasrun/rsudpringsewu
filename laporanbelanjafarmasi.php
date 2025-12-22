<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Belanja Farmasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .back-button a {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .back-button a:hover {
            background: #5a6268;
        }
        
        .filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        
        .btn-filter {
            padding: 10px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-filter:hover {
            background: #218838;
        }
        
        .btn-reset {
            padding: 10px 25px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-reset:hover {
            background: #c82333;
        }
        
        .btn-copy {
            padding: 10px 25px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            margin-bottom: 10px;
        }
        
        .btn-copy:hover {
            background: #138496;
        }
        
        .copy-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: #3498db;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #2980b9;
        }
        
        td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tr:hover {
            background: #e3f2fd;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .font-weight-bold {
            font-weight: bold;
        }
        
        .subtotal-group {
            border-top: 2px solid #3498db !important;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-style: italic;
        }
        
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box strong {
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .container {
                padding: 15px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 6px 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Laporan Belanja Farmasi</h1>
        
        <div class="back-button">
            <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
        </div>
        
        <?php
        include 'koneksi.php';
        
        // Set default dates to today
        $tanggal_awal = isset($_POST['tanggal_awal']) && !empty($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
        $tanggal_akhir = isset($_POST['tanggal_akhir']) && !empty($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
        $kode_suplier = isset($_POST['kode_suplier']) ? $_POST['kode_suplier'] : '';
        $kode_kategori = isset($_POST['kode_kategori']) ? $_POST['kode_kategori'] : '';
        $klasifikasi = isset($_POST['klasifikasi']) ? $_POST['klasifikasi'] : '';
        
        // Get suppliers for dropdown
        $query_suplier = "SELECT DISTINCT kode_suplier, nama_suplier FROM datasuplier ORDER BY nama_suplier ASC";
        $result_suplier = mysqli_query($koneksi, $query_suplier);
        
        // Get categories for dropdown
        $query_kategori = "SELECT DISTINCT kode, nama FROM kategori_barang ORDER BY nama ASC";
        $result_kategori = mysqli_query($koneksi, $query_kategori);
        
        // Get classifications for dropdown
        $query_klasifikasi = "SELECT DISTINCT klasifikasi FROM databarang_tambahan WHERE klasifikasi IS NOT NULL AND klasifikasi != '' ORDER BY klasifikasi ASC";
        $result_klasifikasi = mysqli_query($koneksi, $query_klasifikasi);
        ?>
        
        <form method="POST" action="">
            <div class="filter-container">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="tanggal_awal">Tanggal Awal:</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">Tanggal Akhir:</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
                    </div>
                    
                    <div class="filter-group">
                        <label for="kode_suplier">Supplier:</label>
                        <select id="kode_suplier" name="kode_suplier">
                            <option value="">-- Semua Supplier --</option>
                            <?php
                            if ($result_suplier && mysqli_num_rows($result_suplier) > 0) {
                                while ($row_suplier = mysqli_fetch_assoc($result_suplier)) {
                                    $selected = ($kode_suplier == $row_suplier['kode_suplier']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row_suplier['kode_suplier']) . "' $selected>" . 
                                         htmlspecialchars($row_suplier['kode_suplier']) . " - " . htmlspecialchars($row_suplier['nama_suplier']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="kode_kategori">Kategori:</label>
                        <select id="kode_kategori" name="kode_kategori">
                            <option value="">-- Semua Kategori --</option>
                            <?php
                            if ($result_kategori && mysqli_num_rows($result_kategori) > 0) {
                                while ($row_kategori = mysqli_fetch_assoc($result_kategori)) {
                                    $selected = ($kode_kategori == $row_kategori['kode']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row_kategori['kode']) . "' $selected>" . 
                                         htmlspecialchars($row_kategori['kode']) . " - " . htmlspecialchars($row_kategori['nama']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="klasifikasi">Klasifikasi:</label>
                        <select id="klasifikasi" name="klasifikasi">
                            <option value="">-- Semua Klasifikasi --</option>
                            <?php
                            if ($result_klasifikasi && mysqli_num_rows($result_klasifikasi) > 0) {
                                while ($row_klasifikasi = mysqli_fetch_assoc($result_klasifikasi)) {
                                    $selected = ($klasifikasi == $row_klasifikasi['klasifikasi']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row_klasifikasi['klasifikasi']) . "' $selected>" . 
                                         htmlspecialchars($row_klasifikasi['klasifikasi']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" name="filter" class="btn-filter">🔍 Filter Data</button>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" name="reset" class="btn-reset">🔄 Reset Filter</button>
                    </div>
                </div>
            </div>
        </form>
        
        <?php
        // Handle reset button
        if (isset($_POST['reset'])) {
            $tanggal_awal = date('Y-m-d');
            $tanggal_akhir = date('Y-m-d');
            $kode_suplier = '';
            $kode_kategori = '';
            $klasifikasi = '';
        }
        
        // Always show data, with or without additional filters
        $where_conditions = array();
        $where_conditions[] = "pemesanan.tgl_faktur BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        
        if (!empty($kode_suplier)) {
            $where_conditions[] = "pemesanan.kode_suplier = '" . mysqli_real_escape_string($koneksi, $kode_suplier) . "'";
        }
        
        if (!empty($kode_kategori)) {
            $where_conditions[] = "databarang.kode_kategori = '" . mysqli_real_escape_string($koneksi, $kode_kategori) . "'";
        }
        
        if (!empty($klasifikasi)) {
            $where_conditions[] = "databarang_tambahan.klasifikasi = '" . mysqli_real_escape_string($koneksi, $klasifikasi) . "'";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT
            pemesanan.tgl_faktur,
            pemesanan.no_faktur,
            datasuplier.nama_suplier,
            detailpesan.kode_brng,
            databarang.nama_brng,
            kategori_barang.nama,
            detailpesan.jumlah,
            kodesatuan.satuan,
            detailpesan.h_pesan,
            detailpesan.subtotal,
            databarang_tambahan.klasifikasi
        FROM
            pemesanan
        INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
        INNER JOIN datasuplier ON pemesanan.kode_suplier = datasuplier.kode_suplier
        INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
        INNER JOIN kategori_barang ON databarang.kode_kategori = kategori_barang.kode
        LEFT JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
        INNER JOIN kodesatuan ON detailpesan.kode_sat = kodesatuan.kode_sat
        WHERE $where_clause
        ORDER BY
            pemesanan.tgl_faktur ASC,
            pemesanan.no_faktur ASC";
        
        $result = mysqli_query($koneksi, $query);
        
        if (!$result) {
            echo "<div class='no-data'>Error dalam menjalankan query: " . mysqli_error($koneksi) . "</div>";
        } else {
            $num_rows = mysqli_num_rows($result);
            
            if ($num_rows > 0) {
                echo "<div class='info-box'>";
                echo "<strong>📈 Informasi:</strong> Ditemukan <strong>$num_rows</strong> record data pemesanan ";
                echo "dari tanggal <strong>" . date('d/m/Y', strtotime($tanggal_awal)) . "</strong> ";
                echo "sampai <strong>" . date('d/m/Y', strtotime($tanggal_akhir)) . "</strong>";
                if (!empty($kode_suplier) || !empty($kode_kategori) || !empty($klasifikasi)) {
                    echo " dengan filter tambahan yang dipilih";
                }
                echo "</div>";
                
                echo "<button onclick='copyTableToClipboard()' class='btn-copy'>📋 Copy Data ke Clipboard</button>";
                echo "<div id='copy-success' class='copy-success'>Data berhasil dicopy ke clipboard!</div>";
                
                echo "<div class='table-container'>"; 
                echo "<table>";
                echo "<thead>";
                echo "<tr>";
                echo "<th style='width: 80px;'>Tgl Faktur</th>";
                echo "<th style='width: 100px;'>No Faktur</th>";
                echo "<th style='width: 150px;'>Nama Supplier</th>";
                echo "<th style='width: 80px;'>Kode Barang</th>";
                echo "<th style='width: 200px;'>Nama Barang</th>";
                echo "<th style='width: 120px;'>Kategori</th>";
                echo "<th style='width: 60px;'>Jumlah</th>";
                echo "<th style='width: 60px;'>Satuan</th>";
                echo "<th style='width: 80px;'>Harga</th>";
                echo "<th style='width: 100px;'>Subtotal</th>";
                echo "<th style='width: 100px;'>Klasifikasi</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                $current_faktur = '';
                $total_keseluruhan = 0;
                $no = 1;
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $total_keseluruhan += $row['subtotal'];
                    
                    // Check if this is a new invoice
                    if ($current_faktur != $row['no_faktur']) {
                        $current_faktur = $row['no_faktur'];
                        $show_header = true;
                    } else {
                        $show_header = false;
                    }
                    
                    echo "<tr>";
                    echo "<td>" . ($show_header ? date('d/m/Y', strtotime($row['tgl_faktur'])) : '') . "</td>";
                    echo "<td>" . ($show_header ? htmlspecialchars($row['no_faktur']) : '') . "</td>";
                    echo "<td>" . ($show_header ? htmlspecialchars($row['nama_suplier']) : '') . "</td>";
                    echo "<td>" . htmlspecialchars($row['kode_brng']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_brng']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                    echo "<td class='text-center'>" . number_format($row['jumlah'], 0, ',', '.') . "</td>";
                    echo "<td class='text-center'>" . htmlspecialchars($row['satuan']) . "</td>";
                    echo "<td class='text-right'>Rp " . number_format($row['h_pesan'], 0, ',', '.') . "</td>";
                    echo "<td class='text-right'>Rp " . number_format($row['subtotal'], 0, ',', '.') . "</td>";
                    echo "<td>" . htmlspecialchars($row['klasifikasi'] ?? '') . "</td>";
                    echo "</tr>";
                    
                    $no++;
                }
                
                // Show grand total
                echo "<tr style='background: #2c3e50; color: white; font-weight: bold;'>";
                echo "<td colspan='9' class='text-right'>TOTAL KESELURUHAN:</td>";
                echo "<td class='text-right'>Rp " . number_format($total_keseluruhan, 0, ',', '.') . "</td>";
                echo "<td></td>";
                echo "</tr>";
                
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<div class='no-data'>";
                echo "📭 Tidak ada data pemesanan ditemukan untuk periode tanggal yang dipilih";
                if (!empty($kode_suplier) || !empty($kode_kategori) || !empty($klasifikasi)) {
                    echo " dengan filter yang diterapkan";
                }
                echo ".<br><br>Silakan ubah filter atau periode tanggal.";
                echo "</div>";
            }
        }
        ?>
        
    </div>

    <script>
        // Auto submit form when filters change (optional)
        function autoSubmit() {
            // Uncomment the line below if you want auto-submit on filter change
            // document.forms[0].submit();
        }
        
        // Copy table to clipboard function
        function copyTableToClipboard() {
            const table = document.querySelector('table');
            if (!table) {
                alert('Tidak ada data untuk dicopy!');
                return;
            }
            
            let csvContent = '';
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(function(row) {
                // Skip subtotal and total rows
                if (row.style.background === 'rgb(44, 62, 80)' || row.classList.contains('subtotal-group')) {
                    return;
                }
                
                let csvRow = [];
                const cells = row.querySelectorAll('th, td');
                
                cells.forEach(function(cell) {
                    let cellText = cell.textContent.trim();
                    // Clean up the text
                    cellText = cellText.replace(/\s+/g, ' ');
                    csvRow.push('"' + cellText + '"');
                });
                
                if (csvRow.length > 0) {
                    csvContent += csvRow.join(',') + '\n';
                }
            });
            
            // Copy to clipboard
            navigator.clipboard.writeText(csvContent).then(function() {
                // Show success message
                const successMsg = document.getElementById('copy-success');
                successMsg.style.display = 'block';
                setTimeout(function() {
                    successMsg.style.display = 'none';
                }, 3000);
            }).catch(function(err) {
                // Fallback method for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = csvContent;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Data berhasil dicopy ke clipboard!');
                } catch (err) {
                    alert('Gagal copy data ke clipboard');
                }
                document.body.removeChild(textArea);
            });
        }
        
        // Add event listeners for auto-submit (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Uncomment these lines if you want auto-submit functionality
            /*
            document.getElementById('kode_suplier').addEventListener('change', autoSubmit);
            document.getElementById('kode_kategori').addEventListener('change', autoSubmit);
            document.getElementById('klasifikasi').addEventListener('change', autoSubmit);
            */
        });
    </script>
</body>
</html>