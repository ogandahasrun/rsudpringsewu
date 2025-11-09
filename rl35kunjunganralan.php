<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.5 Kunjungan Rawat Jalan - RSUD Pringsewu</title>
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
        .patient-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 5px;
            border-left: 5px solid #4CAF50;
            font-family: Arial, sans-serif;
        }
        .patient-info p {
            margin: 5px 0;
            font-size: 16px;
        }

        .col-uraian {
            width: 20%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .col-hasil {
            width: 80%;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 RL 3.5 Kunjungan Rawat Jalan</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>

<?php
include 'koneksi.php';

// Tanggal filter - perbaikan: menggunakan $_POST karena form menggunakan method POST
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');

// Perbaikan query: menambahkan validasi tanggal dan memperbaiki typo (CASE menjadi CASE)
$query = "
SELECT 
    ROW_NUMBER() OVER (ORDER BY poliklinik.nm_poli) AS 'Nomor urut',
    poliklinik.nm_poli AS 'nm_poli',
    SUM(CASE WHEN pasien.jk = 'L' AND kabupaten.kd_kab = '1810' THEN 1 ELSE 0 END) AS 'Laki-laki (KD_KAB=1810)',
    SUM(CASE WHEN pasien.jk = 'P' AND kabupaten.kd_kab = '1810' THEN 1 ELSE 0 END) AS 'Perempuan (KD_KAB=1810)',
    SUM(CASE WHEN pasien.jk = 'L' AND kabupaten.kd_kab != '1810' THEN 1 ELSE 0 END) AS 'Laki-laki (KD_KAB!=1810)',
    SUM(CASE WHEN pasien.jk = 'P' AND kabupaten.kd_kab != '1810' THEN 1 ELSE 0 END) AS 'Perempuan (KD_KAB!=1810)',
    COUNT(*) AS 'Jumlah Total'
FROM 
    reg_periksa
    INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
WHERE 
    reg_periksa.status_lanjut = 'ralan' AND
    reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
GROUP BY 
    poliklinik.nm_poli
ORDER BY 
    poliklinik.nm_poli
";

// Debugging: tambahkan ini untuk memeriksa query yang dihasilkan
// echo "<pre>Query: " . htmlspecialchars($query) . "</pre>";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Error dalam query: " . mysqli_error($koneksi));
}
?>
    <header>
        <h1>RL 3.5 Kunjungan Rawat Jalan</h1>
    </header>

    <div class="back-button">
        <a href="surveilans.php">Kembali ke Menu Surveilans</a>
    </div>

            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Tanggal Registrasi
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

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="filter-actions">
                    <button type="button" onclick="copyTableData()" class="btn btn-success">
                        📋 Copy Tabel
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table id="tabel-kunjungan">
                        <thead>
                            <tr>
                                <th rowspan="2">No</th>
                                <th rowspan="2">Nama Poli</th>
                                <th colspan="2">Kab. Pringsewu</th>
                                <th colspan="2">Luar Kab. Pringsewu</th>
                                <th rowspan="2">Jumlah</th>
                            </tr>
                            <tr>
                                <th>L</th>
                                <th>P</th>
                                <th>L</th>
                                <th>P</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_all = 0;
                            $total_l_kab1810 = 0;
                            $total_p_kab1810 = 0;
                            $total_l_kab_lain = 0;
                            $total_p_kab_lain = 0;
                            
                            while ($row = mysqli_fetch_assoc($result)):
                                $total_all += $row['Jumlah Total'];
                                $total_l_kab1810 += $row['Laki-laki (KD_KAB=1810)'];
                                $total_p_kab1810 += $row['Perempuan (KD_KAB=1810)'];
                                $total_l_kab_lain += $row['Laki-laki (KD_KAB!=1810)'];
                                $total_p_kab_lain += $row['Perempuan (KD_KAB!=1810)'];
                            ?>
                                <tr>
                                    <td><?php echo $row['Nomor urut']; ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                    <td><?php echo number_format($row['Laki-laki (KD_KAB=1810)']); ?></td>
                                    <td><?php echo number_format($row['Perempuan (KD_KAB=1810)']); ?></td>
                                    <td><?php echo number_format($row['Laki-laki (KD_KAB!=1810)']); ?></td>
                                    <td><?php echo number_format($row['Perempuan (KD_KAB!=1810)']); ?></td>
                                    <td><?php echo number_format($row['Jumlah Total']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <!-- Baris Total -->
                            <tr style="background-color: #e8f5e8; font-weight: bold;">
                                <td colspan="2">Total</td>
                                <td><?php echo number_format($total_l_kab1810); ?></td>
                                <td><?php echo number_format($total_p_kab1810); ?></td>
                                <td><?php echo number_format($total_l_kab_lain); ?></td>
                                <td><?php echo number_format($total_p_kab_lain); ?></td>
                                <td><?php echo number_format($total_all); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                    <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                    <br>
                    <strong>📋 Total Kunjungan:</strong> <?php echo number_format($total_all); ?> kunjungan
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data ditemukan untuk periode yang dipilih
                    <br>
                    <small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyTableData() {
            let table = document.querySelector(".table-responsive");
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
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
        }
    </script>
</body>
</html>