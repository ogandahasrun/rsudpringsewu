<?php
include 'koneksi.php';

// Set default values
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$nm_dokter = isset($_GET['nm_dokter']) ? $_GET['nm_dokter'] : '';
$nm_poli = isset($_GET['nm_poli']) ? $_GET['nm_poli'] : '';

// Get dokter list untuk dropdown
$dokter_query = "SELECT DISTINCT dokter.kd_dokter, dokter.nm_dokter 
                FROM dokter 
                INNER JOIN reg_periksa ON dokter.kd_dokter = reg_periksa.kd_dokter
                INNER JOIN pasien_detail_tambahan ON pasien_detail_tambahan.no_rkm_medis = reg_periksa.no_rkm_medis
                WHERE pasien_detail_tambahan.hepatitis_c = 'ya'
                ORDER BY dokter.nm_dokter";
$dokter_result = mysqli_query($koneksi, $dokter_query);
$dokter_options = [];
if ($dokter_result) {
    while ($row = mysqli_fetch_assoc($dokter_result)) {
        $dokter_options[] = $row;
    }
}

// Get poliklinik list untuk dropdown
$poli_query = "SELECT DISTINCT poliklinik.kd_poli, poliklinik.nm_poli 
               FROM poliklinik 
               INNER JOIN reg_periksa ON poliklinik.kd_poli = reg_periksa.kd_poli
               INNER JOIN pasien_detail_tambahan ON pasien_detail_tambahan.no_rkm_medis = reg_periksa.no_rkm_medis
               WHERE pasien_detail_tambahan.hepatitis_c = 'ya'
               ORDER BY poliklinik.nm_poli";
$poli_result = mysqli_query($koneksi, $poli_query);
$poli_options = [];
if ($poli_result) {
    while ($row = mysqli_fetch_assoc($poli_result)) {
        $poli_options[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Hepatitis C - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
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
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #28a745; }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #28a745, #20c997); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; cursor: pointer; user-select: none; position: relative; transition: background 0.3s ease; }
        th:hover { background: linear-gradient(45deg, #495057, #5a6268); }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e8f5e8; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; } th, td { padding: 8px 6px; font-size: 12px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦠 Data Hepatitis C</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
            </div>

            <!-- Form tambah data hepatitis_c -->
            <form method="POST" class="filter-form" style="margin-bottom:18px; background:#e8f5e8; border:1.5px solid #28a745;">
                <div class="filter-title">➕ Tambah Data Hepatitis C</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="input_no_rkm_medis">No. RM (Rekam Medis)</label>
                        <input type="text" id="input_no_rkm_medis" name="input_no_rkm_medis" maxlength="20" required placeholder="Masukkan No. RM">
                    </div>
                    <div class="filter-group">
                        <label for="input_hepatitis_c">Status Hepatitis C</label>
                        <select id="input_hepatitis_c" name="input_hepatitis_c" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="ya">Ya</option>
                            <option value="not detected">Not Detected</option>
                            <option value="tidak">Tidak</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="tambah_hepatitisc" class="btn btn-primary">💾 Simpan/Daftarkan</button>
                </div>
            </form>

            <?php
            // Proses tambah data hepatitis_c
            if (isset($_POST['tambah_hepatitisc'])) {
                $input_no_rkm_medis = trim($_POST['input_no_rkm_medis']);
                $input_hepatitis_c = isset($_POST['input_hepatitis_c']) ? trim($_POST['input_hepatitis_c']) : '';
                if ($input_no_rkm_medis !== '' && $input_hepatitis_c !== '') {
                    // Cek apakah sudah ada data
                    $cek = mysqli_query($koneksi, "SELECT no_rkm_medis FROM pasien_detail_tambahan WHERE no_rkm_medis='".mysqli_real_escape_string($koneksi, $input_no_rkm_medis)."'");
                    if ($cek && mysqli_num_rows($cek) > 0) {
                        // Update hepatitis_c sesuai pilihan
                        $q = "UPDATE pasien_detail_tambahan SET hepatitis_c='".mysqli_real_escape_string($koneksi, $input_hepatitis_c)."' WHERE no_rkm_medis='".mysqli_real_escape_string($koneksi, $input_no_rkm_medis)."'";
                    } else {
                        // Insert baru
                        $q = "INSERT INTO pasien_detail_tambahan (no_rkm_medis, hepatitis_c) VALUES ('".mysqli_real_escape_string($koneksi, $input_no_rkm_medis)."', '".mysqli_real_escape_string($koneksi, $input_hepatitis_c)."')";
                    }
                    $ok = mysqli_query($koneksi, $q);
                    if ($ok) {
                        echo '<div style="background:#d4edda;color:#155724;padding:10px 18px;border-radius:7px;margin-bottom:15px;border:1.5px solid #28a745;">✅ Data berhasil disimpan/diupdate untuk No. RM <b>'.htmlspecialchars($input_no_rkm_medis).'</b> (Status: <b>'.htmlspecialchars($input_hepatitis_c).'</b>)</div>';
                    } else {
                        echo '<div style="background:#f8d7da;color:#721c24;padding:10px 18px;border-radius:7px;margin-bottom:15px;border:1.5px solid #dc3545;">❌ Gagal menyimpan data: '.htmlspecialchars(mysqli_error($koneksi)).'</div>';
                    }
                }
            }
            ?>

            <form method="GET" class="filter-form">
                <div class="filter-title">🔍 Filter Data Hepatitis C</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="nm_dokter">👨‍⚕️ Nama Dokter</label>
                        <select id="nm_dokter" name="nm_dokter">
                            <option value="">-- Semua Dokter --</option>
                            <?php foreach ($dokter_options as $dokter): ?>
                                <option value="<?php echo htmlspecialchars($dokter['kd_dokter']); ?>" <?php echo ($nm_dokter == $dokter['kd_dokter']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dokter['nm_dokter']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="nm_poli">🏥 Poliklinik</label>
                        <select id="nm_poli" name="nm_poli">
                            <option value="">-- Semua Poliklinik --</option>
                            <?php foreach ($poli_options as $poli): ?>
                                <option value="<?php echo htmlspecialchars($poli['kd_poli']); ?>" <?php echo ($nm_poli == $poli['kd_poli']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($poli['nm_poli']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan Data</button>
                    <a href="hepatitisc.php" class="btn btn-secondary">🔄 Reset Filter</a>
                </div>
            </form>
            <?php
            // Build query dengan kondisi filter
            $where_conditions = [];
            $where_conditions[] = "reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            $where_conditions[] = "pasien_detail_tambahan.hepatitis_c = 'ya'";
            if (!empty($nm_dokter)) {
                $where_conditions[] = "dokter.kd_dokter = '" . mysqli_real_escape_string($koneksi, $nm_dokter) . "'";
            }
            if (!empty($nm_poli)) {
                $where_conditions[] = "poliklinik.kd_poli = '" . mysqli_real_escape_string($koneksi, $nm_poli) . "'";
            }
            $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
            // Query untuk data Hepatitis C
            $query = "SELECT
                reg_periksa.tgl_registrasi,
                reg_periksa.no_rawat,
                pasien.no_rkm_medis,
                pasien.nm_pasien,
                pasien_detail_tambahan.hepatitis_c,
                dokter.nm_dokter,
                poliklinik.nm_poli
            FROM
                reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN pasien_detail_tambahan ON pasien_detail_tambahan.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            $where_sql
            ORDER BY reg_periksa.tgl_registrasi DESC, dokter.nm_dokter ASC, pasien.nm_pasien ASC";
            $result = mysqli_query($koneksi, $query);
            ?>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tgl Registrasi</th>
                                <th>No. Rawat</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Hepatitis C</th>
                                <th>Nama Dokter</th>
                                <th>Poliklinik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold;"><?php echo $no; ?></td>
                                    <td><?php echo htmlspecialchars($row['tgl_registrasi']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td style="text-align: center;"><span class="prb-badge prb-ya">Ya</span></td>
                                    <td><?php echo htmlspecialchars($row['nm_dokter']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                </tr>
                            <?php $no++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                    <strong>📊 Total: <?php echo number_format(mysqli_num_rows($result)); ?> data Hepatitis C ditemukan</strong>
                    <br>
                    <small>Periode: <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir)); ?></small>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📭 Tidak ada data Hepatitis C</h3>
                    <p>Tidak ada data pasien dengan Hepatitis C pada periode yang dipilih.</p>
                </div>
            <?php endif; ?>
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>
</body>
</html>
