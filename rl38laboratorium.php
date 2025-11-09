<?php
include 'koneksi.php';

// Ambil filter tanggal dari form, default hari ini
$tanggal_awal  = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Query ringkas: Group by nm_perawatan & Pemeriksaan
$query = "
    SELECT 
        jns_perawatan_lab.nm_perawatan,
        template_laboratorium.Pemeriksaan,
        COALESCE(SUM(CASE WHEN pasien.jk = 'L' THEN 1 ELSE 0 END), 0) AS L,
        COALESCE(SUM(CASE WHEN pasien.jk = 'P' THEN 1 ELSE 0 END), 0) AS P,
        COUNT(*) AS Jumlah
    FROM detail_periksa_lab
    INNER JOIN reg_periksa ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN template_laboratorium ON detail_periksa_lab.id_template = template_laboratorium.id_template
    INNER JOIN jns_perawatan_lab ON detail_periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
    WHERE detail_periksa_lab.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY jns_perawatan_lab.nm_perawatan, template_laboratorium.Pemeriksaan
    ORDER BY jns_perawatan_lab.nm_perawatan, template_laboratorium.Pemeriksaan
";

$result = mysqli_query($koneksi, $query);

$query_pa = "
    SELECT 
        jns_perawatan_lab.nm_perawatan,
        SUM(CASE WHEN pasien.jk = 'L' THEN 1 ELSE 0 END) AS L,
        SUM(CASE WHEN pasien.jk = 'P' THEN 1 ELSE 0 END) AS P,
        COUNT(*) AS Jumlah
    FROM detail_periksa_labpa
    INNER JOIN reg_periksa ON detail_periksa_labpa.no_rawat = reg_periksa.no_rawat
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN jns_perawatan_lab ON detail_periksa_labpa.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
    WHERE detail_periksa_labpa.tgl_periksa BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY jns_perawatan_lab.nm_perawatan
    ORDER BY jns_perawatan_lab.nm_perawatan
";
$result_pa = mysqli_query($koneksi, $query_pa);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.8 Kegiatan Laboratorium - RSUD Pringsewu</title>
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
        tfoot tr {
            background-color: #e8f5e8 !important;
            font-weight: bold;
        }
        .section-title {
            color: #28a745;
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            margin: 30px 0 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, #e8f5e9, #f8fff9);
            border-radius: 8px;
            border-left: 5px solid #28a745;
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
            th, td {
                padding: 8px 6px;
                font-size: 12px;
            }
            table {
                min-width: 720px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }
            .filter-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔬 RL 3.8 Kegiatan Laboratorium</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Tanggal Pemeriksaan
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Filter Data
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <div class="section-title">
                🔬 Rekapitulasi Patologi Klinik
            </div>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="filter-actions">
                    <button type="button" onclick="copyTableData()" class="btn btn-success">
                        📋 Copy Tabel
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table id="tabel-patologi-klinik">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pemeriksaan</th>
                                <th>Pemeriksaan</th>
                                <th>L</th>
                                <th>P</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_L = 0;
                            $total_P = 0;
                            $total_jumlah = 0;
                            $no = 1;
                            
                            while ($row = mysqli_fetch_assoc($result)):
                                $total_L += $row['L'];
                                $total_P += $row['P'];
                                $total_jumlah += $row['Jumlah'];
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_perawatan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Pemeriksaan']); ?></td>
                                    <td><?php echo number_format($row['L']); ?></td>
                                    <td><?php echo number_format($row['P']); ?></td>
                                    <td><?php echo number_format($row['Jumlah']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">TOTAL</td>
                                <td><?php echo number_format($total_L); ?></td>
                                <td><?php echo number_format($total_P); ?></td>
                                <td><?php echo number_format($total_jumlah); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data untuk periode ini
                    <br>
                    <small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>

            <div class="section-title" style="margin-top: 40px;">
                🧬 Rekapitulasi Patologi Anatomi
            </div>

            <?php if ($result_pa && mysqli_num_rows($result_pa) > 0): ?>
                <div class="table-responsive">
                    <table id="tabel-patologi-anatomi">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pemeriksaan</th>
                                <th>L</th>
                                <th>P</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_L_pa = 0;
                            $total_P_pa = 0;
                            $total_jumlah_pa = 0;
                            $no_pa = 1;
                            
                            while ($row_pa = mysqli_fetch_assoc($result_pa)):
                                $total_L_pa += $row_pa['L'];
                                $total_P_pa += $row_pa['P'];
                                $total_jumlah_pa += $row_pa['Jumlah'];
                            ?>
                                <tr>
                                    <td><?php echo $no_pa++; ?></td>
                                    <td><?php echo htmlspecialchars($row_pa['nm_perawatan']); ?></td>
                                    <td><?php echo number_format($row_pa['L']); ?></td>
                                    <td><?php echo number_format($row_pa['P']); ?></td>
                                    <td><?php echo number_format($row_pa['Jumlah']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">TOTAL</td>
                                <td><?php echo number_format($total_L_pa); ?></td>
                                <td><?php echo number_format($total_P_pa); ?></td>
                                <td><?php echo number_format($total_jumlah_pa); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data Patologi Anatomi untuk periode ini
                    <br>
                    <small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                <br>
                <strong>🔬 Total Patologi Klinik:</strong> <?php echo isset($total_jumlah) ? number_format($total_jumlah) : '0'; ?> pemeriksaan
                <br>
                <strong>🧬 Total Patologi Anatomi:</strong> <?php echo isset($total_jumlah_pa) ? number_format($total_jumlah_pa) : '0'; ?> pemeriksaan
            </div>
        </div>
    </div>

    <script>
        function copyTableData() {
            let tables = document.querySelectorAll(".table-responsive");
            if (tables.length > 0) {
                let content = "";
                tables.forEach(table => {
                    let range = document.createRange();
                    range.selectNode(table);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                    content += window.getSelection().toString() + "\n\n";
                });
                try {
                    navigator.clipboard.writeText(content);
                    alert("✅ Tabel berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel");
                }
                window.getSelection().removeAllRanges();
            }
        }

        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        }
    </script>
</body>
</html>

</body>
</html>
