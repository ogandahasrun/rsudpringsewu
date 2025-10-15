<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol SEP Ganda - RSUD Pringsewu</title>
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
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Kontrol SEP Ganda</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="casemix.php">← Kembali ke Menu Casemix</a>
            </div>

            <div class="alert-info">
                <strong>ℹ️ Informasi:</strong> Halaman ini menampilkan data SEP (Surat Eligibilitas Peserta) yang memiliki duplikasi berdasarkan nomor rawat yang sama.
            </div>

            <?php
            include 'koneksi.php';
            
            // Query untuk menghitung total duplikasi
            $count_query = "SELECT COUNT(*) as total_duplicate FROM bridging_sep WHERE no_rawat IN (
                SELECT no_rawat FROM bridging_sep GROUP BY no_rawat HAVING COUNT(*) > 1
            )";
            $count_result = mysqli_query($koneksi, $count_query);
            $total_duplicate = 0;
            if ($count_result) {
                $count_row = mysqli_fetch_assoc($count_result);
                $total_duplicate = $count_row['total_duplicate'];
            }
            
            // Query untuk menghitung berapa nomor rawat yang duplikasi
            $rawat_count_query = "SELECT COUNT(DISTINCT no_rawat) as rawat_duplicate FROM bridging_sep WHERE no_rawat IN (
                SELECT no_rawat FROM bridging_sep GROUP BY no_rawat HAVING COUNT(*) > 1
            )";
            $rawat_count_result = mysqli_query($koneksi, $rawat_count_query);
            $rawat_duplicate = 0;
            if ($rawat_count_result) {
                $rawat_count_row = mysqli_fetch_assoc($rawat_count_result);
                $rawat_duplicate = $rawat_count_row['rawat_duplicate'];
            }
            
            if ($total_duplicate > 0) {
                echo '<div class="summary-stats">
                        <span class="stat-number">' . number_format($total_duplicate) . '</span>
                        <span class="stat-label">Total SEP Ganda dari ' . number_format($rawat_duplicate) . ' No. Rawat</span>
                      </div>';
            }
            
            $query = "SELECT bridging_sep.no_rawat, bridging_sep.no_sep, bridging_sep.nomr, bridging_sep.nama_pasien, bridging_sep.tglsep,
                      (SELECT COUNT(*) FROM bridging_sep bs WHERE bs.no_rawat = bridging_sep.no_rawat) as jumlah_duplikasi
              FROM bridging_sep
              WHERE bridging_sep.no_rawat IN (
                  SELECT no_rawat FROM bridging_sep GROUP BY no_rawat HAVING COUNT(*) > 1
              )
              ORDER BY bridging_sep.no_rawat, bridging_sep.no_sep";

            $result = mysqli_query($koneksi, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                echo '<button class="copy-button" onclick="copyTableData()">📋 Copy Tabel ke Clipboard</button>';
                echo '<div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Rawat</th>
                                    <th>Nomor SEP</th>
                                    <th>No. Rekam Medis</th>
                                    <th>Nama Pasien</th>
                                    <th>Tanggal SEP</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>';
                
                $no = 1;
                $current_rawat = '';
                while ($row = mysqli_fetch_assoc($result)) {
                    $is_new_group = ($current_rawat != $row['no_rawat']);
                    $current_rawat = $row['no_rawat'];
                    
                    echo "<tr" . ($is_new_group ? " style='border-top: 3px solid #dc3545;'" : "") . ">
                            <td style='text-align: center; font-weight: bold;'>{$no}</td>
                            <td style='font-family: monospace; font-weight: bold;'>" . htmlspecialchars($row['no_rawat']) . "</td>
                            <td style='font-family: monospace;'>" . htmlspecialchars($row['no_sep']) . "</td>
                            <td style='font-family: monospace; text-align: center;'>" . htmlspecialchars($row['nomr']) . "</td>
                            <td style='font-weight: bold;'>" . htmlspecialchars($row['nama_pasien']) . "</td>
                            <td style='text-align: center;'>" . date('d/m/Y', strtotime($row['tglsep'])) . "</td>
                            <td><span class='duplicate-badge'>Duplikasi ({$row['jumlah_duplikasi']}x)</span></td>
                        </tr>";
                    $no++;
                }
                
                echo '</tbody></table></div>';
                echo '<div style="margin-top: 20px; text-align: center; padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24; border: 1px solid #f5c6cb;">
                        <strong>⚠️ Perhatian:</strong> Ditemukan <strong>' . number_format($total_duplicate) . '</strong> SEP ganda yang perlu ditindaklanjuti
                      </div>';
            } else {
                echo '<div class="no-data">
                        <h3>✅ Tidak Ada SEP Ganda</h3>
                        <p>Selamat! Tidak ditemukan SEP (Surat Eligibilitas Peserta) yang memiliki duplikasi nomor rawat.</p>
                        <p style="margin-top: 15px; font-size: 14px; color: #28a745;">
                            <strong>Status sistem:</strong> Bersih dari duplikasi SEP
                        </p>
                      </div>';
            }
            mysqli_close($koneksi);    
            ?>
        </div>
    </div>
</body>
</html>
