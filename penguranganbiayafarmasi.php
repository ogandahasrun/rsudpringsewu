<?php
include 'koneksi.php';

// Set default filter values
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$kd_poli = isset($_GET['kd_poli']) ? $_GET['kd_poli'] : '';
$kd_pj = isset($_GET['kd_pj']) ? $_GET['kd_pj'] : '';
$status_lanjut = isset($_GET['status_lanjut']) ? $_GET['status_lanjut'] : '';

// Dropdown options
$poli_result = mysqli_query($koneksi, "SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
$pj_result = mysqli_query($koneksi, "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
$status_options = ['Ralan', 'Ranap'];

// Handle input pengurangan biaya
if (isset($_POST['input_pengurangan']) && isset($_POST['no_rawat'])) {
    $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);
    $nama_pengurangan = mysqli_real_escape_string($koneksi, $_POST['nama_pengurangan']);
    $besar_pengurangan = mysqli_real_escape_string($koneksi, $_POST['besar_pengurangan']);
    // cek apakah data sudah ada
    $cek = mysqli_query($koneksi, "SELECT no_rawat FROM pengurangan_biaya WHERE no_rawat='$no_rawat' LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        // update
        $q = "UPDATE pengurangan_biaya SET nama_pengurangan='$nama_pengurangan', besar_pengurangan='$besar_pengurangan' WHERE no_rawat='$no_rawat'";
    } else {
        // insert
        $q = "INSERT INTO pengurangan_biaya (no_rawat, nama_pengurangan, besar_pengurangan) VALUES ('$no_rawat', '$nama_pengurangan', '$besar_pengurangan')";
    }
    $ok = mysqli_query($koneksi, $q);
    if ($ok) {
        echo '<div style="background:#d4edda;color:#155724;padding:10px 18px;border-radius:7px;margin-bottom:15px;border:1.5px solid #28a745;">✅ Data pengurangan biaya berhasil disimpan untuk No. Rawat <b>'.htmlspecialchars($no_rawat).'</b></div>';
    } else {
        echo '<div style="background:#f8d7da;color:#721c24;padding:10px 18px;border-radius:7px;margin-bottom:15px;border:1.5px solid #dc3545;">❌ Gagal menyimpan data: '.htmlspecialchars(mysqli_error($koneksi)).'</div>';
    }
}

// Build filter query
$where = [];
$where[] = "reg_periksa.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'";
if ($kd_poli) $where[] = "reg_periksa.kd_poli = '".mysqli_real_escape_string($koneksi, $kd_poli)."'";
if ($kd_pj) $where[] = "reg_periksa.kd_pj = '".mysqli_real_escape_string($koneksi, $kd_pj)."'";
if ($status_lanjut) $where[] = "reg_periksa.status_lanjut = '".mysqli_real_escape_string($koneksi, $status_lanjut)."'";
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Main query
$query = "SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rawat, pasien.no_rkm_medis, pasien.nm_pasien, reg_periksa.kd_poli, reg_periksa.kd_pj, pengurangan_biaya.nama_pengurangan, pengurangan_biaya.besar_pengurangan, reg_periksa.status_lanjut, SUM(detail_pemberian_obat.total) AS biaya_obat FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis LEFT JOIN pengurangan_biaya ON pengurangan_biaya.no_rawat = reg_periksa.no_rawat LEFT JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat $where_sql GROUP BY reg_periksa.no_rawat ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.no_rawat DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurangan Biaya Farmasi - RSUD Pringsewu</title>
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
            <h1>💊 Pengurangan Biaya Farmasi</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
            </div>
            <form method="GET" class="filter-form">
                <div class="filter-title">🔍 Filter Data Pengurangan Biaya Farmasi</div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tgl_awal">📅 Tanggal Awal</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="tgl_akhir">📅 Tanggal Akhir</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="kd_poli">🏥 Poliklinik</label>
                        <select id="kd_poli" name="kd_poli">
                            <option value="">-- Semua Poliklinik --</option>
                            <?php while ($poli = mysqli_fetch_assoc($poli_result)): ?>
                                <option value="<?php echo htmlspecialchars($poli['kd_poli']); ?>" <?php echo ($kd_poli == $poli['kd_poli']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($poli['nm_poli']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kd_pj">💳 Penjamin</label>
                        <select id="kd_pj" name="kd_pj">
                            <option value="">-- Semua Penjamin --</option>
                            <?php while ($pj = mysqli_fetch_assoc($pj_result)): ?>
                                <option value="<?php echo htmlspecialchars($pj['kd_pj']); ?>" <?php echo ($kd_pj == $pj['kd_pj']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pj['png_jawab']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status_lanjut">🔄 Status Lanjut</label>
                        <select id="status_lanjut" name="status_lanjut">
                            <option value="">-- Semua Status --</option>
                            <?php foreach ($status_options as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($status_lanjut == $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan Data</button>
                    <a href="penguranganbiayafarmasi.php" class="btn btn-secondary">🔄 Reset Filter</a>
                </div>
            </form>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No Urut</th>
                                <th>Tgl Registrasi</th>
                                <th>No. Rawat</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Kode Poli</th>
                                <th>Kode Penjamin</th>
                                <th>Status Lanjut</th>
                                <th>Biaya Obat</th>
                                <th>Nama Pengurangan</th>
                                <th>Besar Pengurangan</th>
                                <th>Input Pengurangan</th>
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
                                    <td><?php echo htmlspecialchars($row['kd_poli']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kd_pj']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status_lanjut']); ?></td>
                                    <td><?php echo number_format($row['biaya_obat'] ?? 0, 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_pengurangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['besar_pengurangan']); ?></td>
                                    <td>
                                        <?php if (empty($row['nama_pengurangan']) || empty($row['besar_pengurangan'])): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="no_rawat" value="<?php echo htmlspecialchars($row['no_rawat']); ?>">
                                                <input type="text" name="nama_pengurangan" placeholder="Nama Pengurangan" required style="width:120px;">
                                                <input type="number" name="besar_pengurangan" placeholder="Besar" required style="width:80px;">
                                                <button type="submit" name="input_pengurangan" class="btn btn-primary" style="padding:6px 12px;font-size:12px;">Simpan</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#28a745;font-weight:bold;">✔</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php $no++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                    <strong>📊 Total: <?php echo number_format(mysqli_num_rows($result)); ?> data ditemukan</strong>
                    <br>
                    <small>Periode: <?php echo date('d/m/Y', strtotime($tgl_awal)) . ' s/d ' . date('d/m/Y', strtotime($tgl_akhir)); ?></small>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📭 Tidak ada data pengurangan biaya farmasi</h3>
                    <p>Tidak ada data pada periode yang dipilih.</p>
                </div>
            <?php endif; ?>
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>
</body>
</html>
