<?php
include 'koneksi.php';

// Set default filter values
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$kd_poli = isset($_GET['kd_poli']) ? $_GET['kd_poli'] : '';

// Build filter query
$where = [];
$where[] = "reg_periksa.tgl_registrasi BETWEEN '$tgl_awal' AND '$tgl_akhir'";
$where[] = "reg_periksa.status_lanjut = 'ralan'";
$poli_result = mysqli_query($koneksi, "SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
if ($kd_poli) $where[] = "reg_periksa.kd_poli = '".mysqli_real_escape_string($koneksi, $kd_poli)."'";
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Main query
$query = "SELECT reg_periksa.no_rawat, reg_periksa.kd_poli, pasien.no_rkm_medis, pasien.nm_pasien, SUM(detail_pemberian_obat.total) AS total, rawat_jl_pr.kd_jenis_prw, jns_perawatan.nm_perawatan, reg_periksa.status_bayar FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis INNER JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat LEFT JOIN rawat_jl_pr ON rawat_jl_pr.no_rawat = reg_periksa.no_rawat LEFT JOIN jns_perawatan ON rawat_jl_pr.kd_jenis_prw = jns_perawatan.kd_jenis_prw $where_sql GROUP BY reg_periksa.no_rawat";
$result = mysqli_query($koneksi, $query);

// Proses tambah ke rawat_jl_pr
if (isset($_POST['tambah_rawat_jl_pr']) && isset($_POST['no_rawat'])) {
    $no_rawat = mysqli_real_escape_string($koneksi, $_POST['no_rawat']);
    $nip = isset($_SESSION['nik']) ? $_SESSION['nik'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
    if (empty($nip)) { $nip = '26091986'; }
    $tgl_perawatan = date('Y-m-d');
    $jam_rawat = date('H:i:s');
    $kd_jenis_prw = 'FARMASI24';
    $material = 0;
    $bhp = 0;
    $tarif_tindakanpr = 2000;
    $kso = 0;
    $menejemen = 0;
    $biaya_rawat = 2000;
    $stts_bayar = 'Belum';
    $cek = mysqli_query($koneksi, "SELECT no_rawat FROM rawat_jl_pr WHERE no_rawat='$no_rawat' AND kd_jenis_prw='$kd_jenis_prw' LIMIT 1");
    if ($cek && mysqli_num_rows($cek) == 0) {
        $q = "INSERT INTO rawat_jl_pr (no_rawat, kd_jenis_prw, nip, tgl_perawatan, jam_rawat, material, bhp, tarif_tindakanpr, kso, menejemen, biaya_rawat, stts_bayar) VALUES ('$no_rawat', '$kd_jenis_prw', '$nip', '$tgl_perawatan', '$jam_rawat', $material, $bhp, $tarif_tindakanpr, $kso, $menejemen, $biaya_rawat, '$stts_bayar')";
        $ok = mysqli_query($koneksi, $q);
        if ($ok) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            echo '<div style="background:#f8d7da;color:#721c24;padding:10px 18px;border-radius:7px;margin-bottom:15px;border:1.5px solid #dc3545;">❌ Gagal menambah data: '.htmlspecialchars(mysqli_error($koneksi)).'</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIORALAN - RSUD Pringsewu</title>
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
        .filter-group input { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus { outline: none; border-color: #28a745; }
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
            <h1>📝 PIORALAN</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="index.php">← Kembali ke Menu Utama</a>
            </div>
            <form method="GET" class="filter-form">
                <div class="filter-title">🔍 Filter Data PIORALAN</div>
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
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Tampilkan Data</button>
                    <a href="pioralan.php" class="btn btn-secondary">🔄 Reset Filter</a>
                </div>
            </form>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No Urut</th>
                                <th>No. Rawat</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Kode Poli</th>
                                <th>Total</th>
                                <th>Nama Perawatan</th>
                                <th>Status Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php if (!empty($row['total'])): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold;"><?php echo $no; ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_pasien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kd_poli']); ?></td>
                                    <td><?php echo number_format($row['total'] ?? 0, 0, ',', '.'); ?></td>
                                    <td><?php echo ($row['kd_jenis_prw'] === 'FARMASI24') ? htmlspecialchars($row['nm_perawatan']) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($row['status_bayar']); ?></td>
                                    <td>
                                    <?php
                                    // Cek apakah sudah ada data FARMASI24 untuk no_rawat ini
                                    $cek_farmasi24 = mysqli_query($koneksi, "SELECT 1 FROM rawat_jl_pr WHERE no_rawat='".mysqli_real_escape_string($koneksi, $row['no_rawat'])."' AND kd_jenis_prw='FARMASI24' LIMIT 1");
                                    if ($cek_farmasi24 && mysqli_num_rows($cek_farmasi24) == 0 && $row['status_bayar'] === 'Belum Bayar'):
                                    ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="no_rawat" value="<?php echo htmlspecialchars($row['no_rawat']); ?>">
                                            <button type="submit" name="tambah_rawat_jl_pr" class="btn btn-primary" onclick="return confirm('Tambahkan data rawat_jl_pr untuk No. Rawat ini?')">Tambahkan</button>
                                        </form>
                                    <?php endif; ?>
                                    </td>
                                </tr>
                                <?php $no++; endif; ?>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 8px; color: #155724;">
                    <strong>📊 Total: <?php echo number_format($no-1); ?> data ditemukan</strong>
                    <br>
                    <small>Periode: <?php echo date('d/m/Y', strtotime($tgl_awal)) . ' s/d ' . date('d/m/Y', strtotime($tgl_akhir)); ?></small>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📭 Tidak ada data PIORALAN</h3>
                    <p>Tidak ada data pada periode yang dipilih.</p>
                </div>
            <?php endif; ?>
            <?php mysqli_close($koneksi); ?>
        </div>
    </div>
</body>
</html>
