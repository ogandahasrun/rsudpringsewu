<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasien Resep Ganda - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 25px; text-align: center; }
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
        .filter-group input:focus { outline: none; border-color: #dc3545; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #dc3545, #c82333); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .info-card { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .info-card h3 { margin: 0 0 5px 0; font-size: 2em; }
        .info-card p { margin: 0; font-size: 13px; opacity: 0.9; }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 12px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 12px 8px; text-align: center; font-weight: bold; font-size: 11px; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
        td { padding: 10px 8px; border-bottom: 1px solid #e9ecef; text-align: center; }
        td:nth-child(4), td:nth-child(6) { text-align: left; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #ffe6e6; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .badge-danger { background: #dc3545; }
        .badge-warning { background: #ffc107; color: #333; }
        .resep-list { margin: 5px 0; padding: 8px; background: #fff3cd; border-radius: 4px; border-left: 3px solid #ffc107; }
        .resep-item { padding: 3px 0; font-size: 11px; }
        .resep-time { font-weight: bold; color: #dc3545; }
        
        @media print {
            body { background: white; padding: 0; }
            .header { background: #dc3545 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .back-button, .filter-form, .btn { display: none; }
            .container { box-shadow: none; }
            table { font-size: 10px; }
            th, td { padding: 6px 4px; }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px 15px; }
            .header h1 { font-size: 1.5em; }
            .content { padding: 15px; }
            .filter-form { padding: 20px 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px 4px; font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Monitoring Pasien Resep Ganda</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="farmasi.php">← Kembali ke Menu Farmasi</a>
            </div>

            <form method="POST" class="filter-form" id="filterForm">
                <div class="filter-title">
                    📅 Filter Tanggal
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tgl_perawatan">📆 Tanggal Perawatan</label>
                        <input type="date" id="tgl_perawatan" name="tgl_perawatan" 
                               value="<?php echo isset($_POST['tgl_perawatan']) ? $_POST['tgl_perawatan'] : date('Y-m-d'); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        🔍 Tampilkan Data
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-success">
                        🖨️ Cetak
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['filter'])) {
                $tgl_perawatan = mysqli_real_escape_string($koneksi, $_POST['tgl_perawatan']);
                
                // Query untuk mendapatkan pasien dengan resep lebih dari 1 kali pada jam 7:30 - 20:00
                $query = "SELECT
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien,
                    COUNT(DISTINCT resep_obat.no_resep) as jumlah_resep,
                    GROUP_CONCAT(DISTINCT CONCAT(resep_obat.jam, '|', resep_obat.no_resep) ORDER BY resep_obat.jam SEPARATOR '###') as resep_detail
                FROM
                    resep_obat
                INNER JOIN reg_periksa ON resep_obat.no_rawat = reg_periksa.no_rawat
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
                WHERE
                    resep_obat.tgl_perawatan = '$tgl_perawatan'
                    AND resep_obat.jam >= '07:30:00'
                    AND resep_obat.jam <= '20:00:00'
                    AND (kamar_inap.tgl_keluar = '0000-00-00' OR kamar_inap.tgl_keluar IS NULL OR kamar_inap.tgl_keluar >= '$tgl_perawatan')
                GROUP BY
                    reg_periksa.no_rawat,
                    pasien.no_rkm_medis,
                    pasien.nm_pasien
                HAVING
                    jumlah_resep > 1
                ORDER BY
                    jumlah_resep DESC,
                    pasien.nm_pasien ASC";
                
                $result = mysqli_query($koneksi, $query);
                
                // Error handling
                if (!$result) {
                    echo '<div class="no-data" style="background: #f8d7da; color: #721c24;">';
                    echo '<h3>❌ Error Query</h3>';
                    echo '<p><strong>Error:</strong> ' . mysqli_error($koneksi) . '</p>';
                    echo '<pre style="text-align: left; background: white; padding: 15px; border-radius: 8px; overflow-x: auto;">' . htmlspecialchars($query) . '</pre>';
                    echo '</div>';
                    $total_rows = 0;
                } else {
                    $total_rows = mysqli_num_rows($result);
                }
                
                if ($result && $total_rows > 0):
            ?>
            
            <div class="info-cards">
                <div class="info-card">
                    <h3><?php echo $total_rows; ?></h3>
                    <p>Total Pasien Resep Ganda</p>
                </div>
                <div class="info-card">
                    <h3><?php echo date('d/m/Y', strtotime($tgl_perawatan)); ?></h3>
                    <p>Tanggal Monitoring</p>
                </div>
                <div class="info-card">
                    <h3>07:30 - 20:00</h3>
                    <p>Rentang Waktu</p>
                </div>
            </div>
            
            <div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;">
                <strong>⚠️ Perhatian:</strong> Halaman ini menampilkan pasien rawat inap yang mendapatkan resep lebih dari 1 kali pada rentang waktu 07:30 - 20:00 di tanggal yang sama.
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Rawat</th>
                            <th>No. RM</th>
                            <th>Nama Pasien</th>
                            <th>Kamar</th>
                            <th>Bangsal</th>
                            <th>Jumlah<br>Resep</th>
                            <th>Detail Resep</th>
                            <th>Tgl Masuk</th>
                            <th>Tgl Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                            // Parse detail resep
                            $resep_details = explode('###', $row['resep_detail']);
                            
                            // Ambil data kamar terbaru untuk pasien ini
                            $no_rawat = mysqli_real_escape_string($koneksi, $row['no_rawat']);
                            $query_kamar = "SELECT 
                                ki.kd_kamar,
                                k.kd_bangsal,
                                b.nm_bangsal,
                                ki.tgl_masuk,
                                ki.tgl_keluar
                            FROM kamar_inap ki
                            LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
                            LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
                            WHERE ki.no_rawat = '$no_rawat'
                            ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
                            LIMIT 1";
                            $result_kamar = mysqli_query($koneksi, $query_kamar);
                            $kamar = null;
                            if ($result_kamar && mysqli_num_rows($result_kamar) > 0) {
                                $kamar = mysqli_fetch_assoc($result_kamar);
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td style="font-family: monospace; font-weight: bold; color: #007bff;">
                                <?php echo htmlspecialchars($row['no_rawat']); ?>
                            </td>
                            <td style="font-family: monospace;">
                                <?php echo htmlspecialchars($row['no_rkm_medis']); ?>
                            </td>
                            <td style="font-weight: 600;">
                                <?php echo htmlspecialchars($row['nm_pasien']); ?>
                            </td>
                            <td style="font-family: monospace;">
                                <?php echo $kamar ? htmlspecialchars($kamar['kd_kamar']) : '-'; ?>
                            </td>
                            <td>
                                <?php echo $kamar ? htmlspecialchars($kamar['nm_bangsal']) : '-'; ?>
                            </td>
                            <td>
                                <span class="badge badge-danger">
                                    <?php echo $row['jumlah_resep']; ?>x
                                </span>
                            </td>
                            <td style="text-align: left;">
                                <div class="resep-list">
                                    <?php foreach ($resep_details as $detail): 
                                        list($jam, $no_resep) = explode('|', $detail);
                                    ?>
                                    <div class="resep-item">
                                        <span class="resep-time">⏰ <?php echo substr($jam, 0, 5); ?></span> - 
                                        Resep: <strong><?php echo htmlspecialchars($no_resep); ?></strong>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo $kamar ? date('d/m/Y', strtotime($kamar['tgl_masuk'])) : '-'; ?>
                            </td>
                            <td>
                                <?php 
                                if ($kamar) {
                                    if ($kamar['tgl_keluar'] == '0000-00-00' || empty($kamar['tgl_keluar'])) {
                                        echo '<span class="badge badge-warning">Masih Dirawat</span>';
                                    } else {
                                        echo date('d/m/Y', strtotime($kamar['tgl_keluar']));
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
                else:
            ?>
                <div class="no-data">
                    <h3>✅ Tidak Ada Data</h3>
                    <p>Tidak ada pasien yang mendapatkan resep lebih dari 1 kali pada tanggal <strong><?php echo date('d/m/Y', strtotime($tgl_perawatan)); ?></strong> di rentang waktu 07:30 - 20:00.</p>
                    <br>
                    <small style="color: #28a745;">
                        <strong>✓ Good!</strong> Semua pasien hanya mendapatkan 1 resep atau tidak ada resep di rentang waktu tersebut.
                    </small>
                </div>
            <?php 
                endif;
            } else {
            ?>
                <div class="no-data">
                    <h3>📋 Monitoring Pasien Resep Ganda</h3>
                    <p>Silakan pilih <strong>Tanggal Perawatan</strong> untuk menampilkan data pasien yang mendapatkan resep lebih dari 1 kali.</p>
                    <br>
                    <div style="text-align: left; max-width: 600px; margin: 0 auto;">
                        <strong>📊 Informasi Monitoring:</strong>
                        <ul style="text-align: left; color: #6c757d;">
                            <li><strong>Tujuan:</strong> Mendeteksi pasien yang mendapatkan resep ganda dalam satu hari</li>
                            <li><strong>Kriteria:</strong> Resep lebih dari 1 kali pada jam 07:30 - 20:00</li>
                            <li><strong>Status:</strong> Hanya pasien rawat inap yang masih dirawat atau belum keluar</li>
                            <li><strong>Detail:</strong> Menampilkan waktu dan nomor resep untuk setiap pemberian</li>
                            <li><strong>Manfaat:</strong> Mencegah duplikasi pemberian obat dan monitoring kepatuhan</li>
                        </ul>
                    </div>
                </div>
            <?php } ?>
            
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>

    <script>
        function resetForm() {
            document.getElementById('tgl_perawatan').value = '<?php echo date('Y-m-d'); ?>';
        }
        
        // Auto-submit jika dari halaman dashboard dengan parameter tanggal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('auto') && urlParams.get('auto') === '1') {
                document.getElementById('filterForm').submit();
            }
        });
    </script>
</body>
</html>
