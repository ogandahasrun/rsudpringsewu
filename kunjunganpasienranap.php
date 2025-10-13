<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunjungan Pasien Rawat Inap - RSUD Pringsewu</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
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
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
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
            background: #e3f2fd;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
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
            .filter-actions {
                justify-content: stretch;
            }
            .btn {
                padding: 10px 15px;
                font-size: 13px;
            }
            .summary-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            .stat-card {
                padding: 15px;
            }
            .stat-number {
                font-size: 24px;
            }
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .summary-stats {
                grid-template-columns: 1fr;
            }
            .header h1 {
                font-size: 1.3em;
            }
            .filter-title {
                font-size: 16px;
            }
        }
    </style>
    <script>
        function copyTableData() {
            let table = document.querySelector("table");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('cara_bayar').value = '';
        }
    </script>
</head>
<body>
    <?php
    include 'koneksi.php';
    
    // Inisialisasi nilai default
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    $cara_bayar = isset($_POST['cara_bayar']) ? $_POST['cara_bayar'] : '';

    // Ambil daftar cara bayar untuk dropdown
    $cara_bayar_options = [];
    $penjab_query = "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab";
    $penjab_result = mysqli_query($koneksi, $penjab_query);
    while ($row = mysqli_fetch_assoc($penjab_result)) {
        $cara_bayar_options[] = $row;
    }
    ?>

    <div class="container">
        <div class="header">
            <h1>🏥 Kunjungan Pasien Rawat Inap</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali ke Menu Surveilans</a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📊 Filter Data Kunjungan
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Registrasi Awal</label>
                        <input type="date" 
                               id="tanggal_awal" 
                               name="tanggal_awal" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Registrasi Akhir</label>
                        <input type="date" 
                               id="tanggal_akhir" 
                               name="tanggal_akhir" 
                               required 
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="cara_bayar">💳 Cara Bayar</label>
                        <select id="cara_bayar" name="cara_bayar">
                            <option value="">-- Semua Cara Bayar --</option>
                            <?php foreach ($cara_bayar_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['kd_pj']); ?>" 
                                        <?php echo ($cara_bayar == $option['kd_pj']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option['png_jawab']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Tampilkan Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['filter'])) {
                // Bangun WHERE clause
                $where_conditions = ["reg_periksa.status_lanjut = 'ranap'"];
                $where_conditions[] = "reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
                
                if (!empty($cara_bayar)) {
                    $where_conditions[] = "reg_periksa.kd_pj = '" . mysqli_real_escape_string($koneksi, $cara_bayar) . "'";
                }
                
                $where_sql = implode(' AND ', $where_conditions);

                $query = "SELECT
                            reg_periksa.tgl_registrasi,
                            reg_periksa.no_rawat,
                            pasien.no_rkm_medis,
                            pasien.nm_pasien,
                            pasien.jk,
                            pasien.tgl_lahir,
                            penjab.png_jawab,
                            COALESCE(detail_nota_inap.besar_bayar, 0) as besar_bayar,
                            COALESCE(detail_piutang_pasien.sisapiutang, 0) as sisapiutang
                        FROM reg_periksa
                        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
                        LEFT JOIN detail_nota_inap ON detail_nota_inap.no_rawat = reg_periksa.no_rawat
                        LEFT JOIN detail_piutang_pasien ON detail_piutang_pasien.no_rawat = reg_periksa.no_rawat
                        WHERE $where_sql
                        ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.no_rawat DESC";

                $result = mysqli_query($koneksi, $query);
                
                if ($result) {
                    $total_records = mysqli_num_rows($result);
                    $total_tunai = 0;
                    $total_piutang = 0;
                    $data = [];
                    
                    // Ambil data dan hitung total
                    while ($row = mysqli_fetch_assoc($result)) {
                        $total_tunai += $row['besar_bayar'];
                        $total_piutang += $row['sisapiutang'];
                        $data[] = $row;
                    }
                    
                    // Tampilkan statistik
                    if ($total_records > 0) {
                        echo '<div class="summary-stats">
                                <div class="stat-card">
                                    <div class="stat-number">' . number_format($total_records) . '</div>
                                    <div class="stat-label">Total Kunjungan</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">Rp ' . number_format($total_tunai) . '</div>
                                    <div class="stat-label">Total Tunai</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">Rp ' . number_format($total_piutang) . '</div>
                                    <div class="stat-label">Total Piutang</div>
                                </div>
                              </div>';
                        
                        echo '<div style="display: flex; gap: 15px; margin-bottom: 20px; justify-content: center; flex-wrap: wrap;">
                                <button class="btn btn-success" onclick="copyTableData()">
                                    📋 Salin Tabel ke Clipboard
                                </button>
                              </div>';
                        
                        echo '<div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tgl Registrasi</th>
                                            <th>No Rawat</th>
                                            <th>No RM</th>
                                            <th>Nama Pasien</th>
                                            <th>JK</th>
                                            <th>Tgl Lahir</th>
                                            <th>Cara Bayar</th>
                                            <th>Tunai (Rp)</th>
                                            <th>Piutang (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        
                        $no = 1;
                        foreach ($data as $row) {
                            // Format tanggal lahir dan hitung umur
                            $tgl_lahir = $row['tgl_lahir'];
                            $umur = '';
                            if ($tgl_lahir && $tgl_lahir != '0000-00-00') {
                                $lahir = new DateTime($tgl_lahir);
                                $today = new DateTime();
                                $umur = $today->diff($lahir)->y . ' th';
                            }
                            
                            echo "<tr>
                                    <td style='text-align: center;'>{$no}</td>
                                    <td>" . date('d/m/Y', strtotime($row['tgl_registrasi'])) . "</td>
                                    <td>" . htmlspecialchars($row['no_rawat']) . "</td>
                                    <td>" . htmlspecialchars($row['no_rkm_medis']) . "</td>
                                    <td>" . htmlspecialchars($row['nm_pasien']) . "</td>
                                    <td style='text-align: center;'>" . htmlspecialchars($row['jk']) . "</td>
                                    <td>" . ($tgl_lahir != '0000-00-00' ? date('d/m/Y', strtotime($tgl_lahir)) . " ({$umur})" : '-') . "</td>
                                    <td>" . htmlspecialchars($row['png_jawab']) . "</td>
                                    <td style='text-align: right;'>" . number_format($row['besar_bayar']) . "</td>
                                    <td style='text-align: right;'>" . number_format($row['sisapiutang']) . "</td>
                                </tr>";
                            $no++;
                        }
                        
                        echo '</tbody>
                              </table>
                              </div>';
                    } else {
                        echo '<div class="no-data">
                                <h3>📭 Tidak ada data ditemukan</h3>
                                <p>Tidak ada kunjungan pasien rawat inap pada periode dan kriteria yang dipilih.</p>
                              </div>';
                    }
                } else {
                    echo '<div class="no-data">
                            <h3>❌ Terjadi Kesalahan</h3>
                            <p>Error: ' . htmlspecialchars(mysqli_error($koneksi)) . '</p>
                          </div>';
                }
                
                mysqli_close($koneksi);
            } else {
                echo '<div class="no-data">
                        <h3>🔍 Silakan Gunakan Filter</h3>
                        <p>Pilih tanggal dan cara bayar, kemudian klik "Tampilkan Data" untuk melihat hasil kunjungan pasien rawat inap.</p>
                      </div>';
            }
            ?>
        </div>
    </div>
</body>
</html>