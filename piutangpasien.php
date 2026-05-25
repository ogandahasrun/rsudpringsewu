<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piutang Pasien - RSUD Pringsewu</title>
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
            background: linear-gradient(45deg, #dc3545, #c82333);
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
            display: inline-block;
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
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .copy-button {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .copy-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th {
            background: linear-gradient(45deg, #343a40, #495057);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            white-space: nowrap;
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
            background: #ffebee;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .summary-stats {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 25px;
        }
        .summary-stats .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .summary-stats .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .duplicate-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .filter-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
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
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .button-group {
                justify-content: center;
            }
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            let range = document.createRange();
            range.selectNode(table);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand("copy");
            window.getSelection().removeAllRanges();
            alert("✅ Tabel berhasil disalin ke clipboard!");
        }
        
        function resetFilter() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tgl_awal').value = today;
            document.getElementById('tgl_akhir').value = today;
            document.getElementById('png_jawab').value = '';
            window.location.href = window.location.pathname + '?tgl_awal=' + encodeURIComponent(today) + '&tgl_akhir=' + encodeURIComponent(today);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Piutang Pasien</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="keuangan.php">← Kembali ke Menu Keuangan</a>
            </div>

            <?php
            include 'koneksi.php';

            $today = date('Y-m-d');
            $tgl_awal = isset($_GET['tgl_awal']) && trim($_GET['tgl_awal']) !== '' ? trim($_GET['tgl_awal']) : $today;
            $tgl_akhir = isset($_GET['tgl_akhir']) && trim($_GET['tgl_akhir']) !== '' ? trim($_GET['tgl_akhir']) : $today;
            $png_jawab = isset($_GET['png_jawab']) ? trim($_GET['png_jawab']) : '';

            $penjab_options = array();
            $penjab_query = "SELECT png_jawab FROM penjab ORDER BY png_jawab";
            $penjab_result = mysqli_query($koneksi, $penjab_query);
            if ($penjab_result) {
                while ($penjab_row = mysqli_fetch_assoc($penjab_result)) {
                    $penjab_options[] = $penjab_row['png_jawab'];
                }
            }

            $where_conditions = array("1=1");
            if (!empty($tgl_awal) && !empty($tgl_akhir)) {
                $tgl_awal_escaped = mysqli_real_escape_string($koneksi, $tgl_awal);
                $tgl_akhir_escaped = mysqli_real_escape_string($koneksi, $tgl_akhir);
                $where_conditions[] = "bridging_sep.tglsep BETWEEN '$tgl_awal_escaped' AND '$tgl_akhir_escaped'";
            } elseif (!empty($tgl_awal)) {
                $tgl_awal_escaped = mysqli_real_escape_string($koneksi, $tgl_awal);
                $where_conditions[] = "bridging_sep.tglsep >= '$tgl_awal_escaped'";
            } elseif (!empty($tgl_akhir)) {
                $tgl_akhir_escaped = mysqli_real_escape_string($koneksi, $tgl_akhir);
                $where_conditions[] = "bridging_sep.tglsep <= '$tgl_akhir_escaped'";
            }

            if (!empty($png_jawab)) {
                $png_jawab_escaped = mysqli_real_escape_string($koneksi, $png_jawab);
                $where_conditions[] = "penjab.png_jawab = '$png_jawab_escaped'";
            }

            $where_sql = implode(' AND ', $where_conditions);

            $main_query = "SELECT
                    bridging_sep.tglsep,
                    bridging_sep.no_sep,
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    GROUP_CONCAT(DISTINCT nota_inap.no_nota ORDER BY nota_inap.no_nota SEPARATOR ', ') AS nota_inap,
                    GROUP_CONCAT(DISTINCT nota_jalan.no_nota ORDER BY nota_jalan.no_nota SEPARATOR ', ') AS nota_ralan,
                    COALESCE(MAX(piutang_pasien.totalpiutang), 0) AS totalpiutang,
                    penjab.png_jawab
                FROM bridging_sep
                INNER JOIN reg_periksa ON bridging_sep.no_rawat = reg_periksa.no_rawat
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                LEFT JOIN piutang_pasien ON piutang_pasien.no_rawat = reg_periksa.no_rawat
                LEFT JOIN nota_inap ON nota_inap.no_rawat = reg_periksa.no_rawat
                LEFT JOIN nota_jalan ON nota_jalan.no_rawat = reg_periksa.no_rawat
                INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                WHERE $where_sql
                GROUP BY
                    bridging_sep.tglsep,
                    bridging_sep.no_sep,
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    penjab.png_jawab
                ORDER BY bridging_sep.tglsep DESC, reg_periksa.no_rawat DESC";

            $summary_query = "SELECT COUNT(*) AS total_data, COALESCE(SUM(totalpiutang), 0) AS total_piutang FROM ($main_query) AS data_piutang";
            $summary_result = mysqli_query($koneksi, $summary_query);
            $total_data = 0;
            $total_piutang = 0;
            if ($summary_result) {
                $summary_row = mysqli_fetch_assoc($summary_result);
                $total_data = (int)$summary_row['total_data'];
                $total_piutang = (float)$summary_row['total_piutang'];
            }

            $result = mysqli_query($koneksi, $main_query);
            ?>

            <div class="filter-form">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tgl_awal">📅 Tanggal Awal SEP:</label>
                            <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tgl_akhir">📅 Tanggal Akhir SEP:</label>
                            <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>">
                        </div>

                        <div class="form-group">
                            <label for="png_jawab">💳 Penjab / Cara Bayar:</label>
                            <select id="png_jawab" name="png_jawab">
                                <option value="">-- Semua Penjab --</option>
                                <?php foreach ($penjab_options as $option) { ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $png_jawab === $option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">🔍 Filter Data</button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilter()">🔄 Reset Filter</button>
                    </div>
                </form>
            </div>

            <div class="alert-info">
                <strong>ℹ️ Informasi:</strong> Halaman ini menampilkan data SEP beserta nota dan total piutang pasien, dengan filter berdasarkan periode tanggal SEP dan penjab.
            </div>

            <?php
            if ($total_data > 0) {
                $periode_text = "Semua periode";
                if (!empty($tgl_awal) && !empty($tgl_akhir)) {
                    $periode_text = date('d/m/Y', strtotime($tgl_awal)) . " - " . date('d/m/Y', strtotime($tgl_akhir));
                } elseif (!empty($tgl_awal)) {
                    $periode_text = "Dari " . date('d/m/Y', strtotime($tgl_awal));
                } elseif (!empty($tgl_akhir)) {
                    $periode_text = "Sampai " . date('d/m/Y', strtotime($tgl_akhir));
                }

                echo '<div class="summary-stats">
                        <span class="stat-number">' . number_format($total_data) . '</span>
                        <span class="stat-label">Data ditemukan | Periode: ' . htmlspecialchars($periode_text) . ' | Total Piutang: Rp ' . number_format($total_piutang, 0, ',', '.') . '</span>
                      </div>';
            }

            if ($result && mysqli_num_rows($result) > 0) {
                echo '<button class="copy-button" onclick="copyTableData()">📋 Copy Tabel ke Clipboard</button>';
                echo '<div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal SEP</th>
                                    <th>Nomor SEP</th>
                                    <th>Nomor Rawat</th>
                                    <th>No. Rekam Medis</th>
                                    <th>Nama Pasien</th>
                                    <th>Nota Inap</th>
                                    <th>Nota Ralan</th>
                                    <th>Total Piutang</th>
                                    <th>Penjab</th>
                                </tr>
                            </thead>
                            <tbody>';
                
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                            <td style='text-align: center; font-weight: bold;'>{$no}</td>
                            <td style='text-align: center;'>" . date('d/m/Y', strtotime($row['tglsep'])) . "</td>
                            <td style='font-family: monospace;'>" . htmlspecialchars($row['no_sep']) . "</td>
                            <td style='font-family: monospace; font-weight: bold;'>" . htmlspecialchars($row['no_rawat']) . "</td>
                            <td style='font-family: monospace; text-align: center;'>" . htmlspecialchars($row['no_rkm_medis']) . "</td>
                            <td style='font-weight: bold;'>" . htmlspecialchars($row['nm_pasien']) . "</td>
                            <td style='font-family: monospace;'>" . htmlspecialchars($row['nota_inap'] ?: '-') . "</td>
                            <td style='font-family: monospace;'>" . htmlspecialchars($row['nota_ralan'] ?: '-') . "</td>
                            <td style='text-align: right; font-weight: bold;'>Rp " . number_format($row['totalpiutang'], 0, ',', '.') . "</td>
                            <td><span class='duplicate-badge'>" . htmlspecialchars($row['png_jawab']) . "</span></td>
                        </tr>";
                    $no++;
                }
                
                echo '</tbody></table></div>';
                echo '<div style="margin-top: 20px; text-align: center; padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24; border: 1px solid #f5c6cb;">
                        <strong>ℹ️ Ringkasan:</strong> Total piutang pasien pada hasil filter ini adalah <strong>Rp ' . number_format($total_piutang, 0, ',', '.') . '</strong>
                      </div>';
            } else {
                echo '<div class="no-data">
                        <h3>📭 Data Tidak Ditemukan</h3>
                        <p>Tidak ada data piutang pasien yang sesuai dengan filter periode tanggal SEP dan penjab yang dipilih.</p>
                        <p style="margin-top: 15px; font-size: 14px; color: #28a745;">
                            <strong>Saran:</strong> Ubah periode tanggal atau pilih penjab lain untuk menampilkan data.
                        </p>
                      </div>';
            }
            mysqli_close($koneksi);    
            ?>
        </div>
    </div>
</body>
</html>
