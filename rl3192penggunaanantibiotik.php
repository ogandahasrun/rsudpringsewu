<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RL 3.19.2 Penggunaan Antibiotik - RSUD Pringsewu</title>
    <style>
        /* Copy style from rl318farmasiresep.php */
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .content { padding: 25px; }
        .back-button { margin-bottom: 20px; }
        .back-button a { display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .back-button a:hover { background: #5a6268; transform: translateY(-2px); }
        .filter-form { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .filter-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: bold; color: #495057; font-size: 14px; }
        .filter-group input, .filter-group select { padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .filter-actions { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(45deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4); }
        .btn-success { background: linear-gradient(45deg, #28a745, #20c997); color: white; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }
        .btn-secondary { background: #6c757d; color: white; box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3); }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .table-responsive { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; min-width: 900px; }
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: left; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; }
        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; gap: 15px; } .filter-actions { justify-content: stretch; } .btn { padding: 10px 15px; font-size: 13px; } th, td { padding: 8px 6px; font-size: 12px; } table { min-width: 720px; } }
        @media (max-width: 480px) { .header h1 { font-size: 1.3em; } .filter-title { font-size: 16px; } }
        .filter-form button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background-color: #45a049; }
        .no-data { text-align: center; color: #666; font-style: italic; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 RL 3.19.2 Penggunaan Antibiotik</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>
<?php
include 'koneksi.php';
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-t');

// Query jumlah sampel unik per status_lanjut dan nm_poli
$where_sampel = "reg_periksa.tgl_registrasi BETWEEN '".$tanggal_awal."' AND '".$tanggal_akhir."' AND reg_periksa.status_lanjut = 'ralan'";
$query_sampel = "SELECT reg_periksa.status_lanjut, poliklinik.nm_poli, COUNT(DISTINCT reg_periksa.no_rawat) AS jumlah_sampel FROM reg_periksa INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli WHERE $where_sampel GROUP BY reg_periksa.status_lanjut, poliklinik.nm_poli";
$result_sampel = mysqli_query($koneksi, $query_sampel);
$rekap = [];
if ($result_sampel && mysqli_num_rows($result_sampel) > 0) {
    while ($row = mysqli_fetch_assoc($result_sampel)) {
        $key = $row['status_lanjut'].'|'.$row['nm_poli'];
        $rekap[$key] = [
            'periode' => date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)),
            'status_lanjut' => $row['status_lanjut'],
            'nm_poli' => $row['nm_poli'],
            'jumlah_sampel' => (int)$row['jumlah_sampel'],
            'jumlah_antibiotik' => 0
        ];
    }
}

// Query jumlah pasien yang mendapat antibiotik (unik no_rawat per status_lanjut dan nm_poli)
$where_ab = "reg_periksa.tgl_registrasi BETWEEN '".$tanggal_awal."' AND '".$tanggal_akhir."' AND reg_periksa.status_lanjut = 'ralan' AND databarang.kode_golongan = 'AB'";
$query_ab = "SELECT reg_periksa.status_lanjut, poliklinik.nm_poli, COUNT(DISTINCT reg_periksa.no_rawat) AS jumlah_antibiotik FROM reg_periksa INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli INNER JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng WHERE $where_ab GROUP BY reg_periksa.status_lanjut, poliklinik.nm_poli";
$result_ab = mysqli_query($koneksi, $query_ab);
if ($result_ab && mysqli_num_rows($result_ab) > 0) {
    while ($row = mysqli_fetch_assoc($result_ab)) {
        $key = $row['status_lanjut'].'|'.$row['nm_poli'];
        if (!isset($rekap[$key])) {
            $rekap[$key] = [
                'periode' => date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)),
                'status_lanjut' => $row['status_lanjut'],
                'nm_poli' => $row['nm_poli'],
                'jumlah_sampel' => 0,
                'jumlah_antibiotik' => 0
            ];
        }
        $rekap[$key]['jumlah_antibiotik'] = (int)$row['jumlah_antibiotik'];
    }
}

// --- RL 3.19.2 RAWAT INAP (RANAP) ---
$rekap_ranap = [];
// Query jumlah sampel unik per status_lanjut dan kelompok_kamar (ambil tgl_keluar paling akhir per no_rawat)
$where_sampel_ranap = "kamar_inap.tgl_keluar BETWEEN '".$tanggal_awal."' AND '".$tanggal_akhir."' AND reg_periksa.status_lanjut = 'ranap'";
$query_sampel_ranap = "SELECT reg_periksa.status_lanjut, kelompokkamar.kelompok_kamar, COUNT(DISTINCT kamar_inap.no_rawat) AS jumlah_sampel FROM kamar_inap INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat INNER JOIN kelompokkamar ON kamar_inap.kd_kamar = kelompokkamar.kd_kamar WHERE $where_sampel_ranap AND kamar_inap.tgl_keluar = (SELECT MAX(ki2.tgl_keluar) FROM kamar_inap ki2 WHERE ki2.no_rawat = kamar_inap.no_rawat) GROUP BY reg_periksa.status_lanjut, kelompokkamar.kelompok_kamar";
$result_sampel_ranap = mysqli_query($koneksi, $query_sampel_ranap);
if ($result_sampel_ranap && mysqli_num_rows($result_sampel_ranap) > 0) {
    while ($row = mysqli_fetch_assoc($result_sampel_ranap)) {
        $key = $row['status_lanjut'].'|'.$row['kelompok_kamar'];
        $rekap_ranap[$key] = [
            'periode' => date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)),
            'status_lanjut' => $row['status_lanjut'],
            'kelompok_kamar' => $row['kelompok_kamar'],
            'jumlah_sampel' => (int)$row['jumlah_sampel'],
            'jumlah_antibiotik' => 0
        ];
    }
}

// Query jumlah pasien ranap yang mendapat antibiotik (unik no_rawat per status_lanjut dan kelompok_kamar)
$where_ab_ranap = "kamar_inap.tgl_keluar BETWEEN '".$tanggal_awal."' AND '".$tanggal_akhir."' AND reg_periksa.status_lanjut = 'ranap' AND databarang.kode_golongan = 'AB'";
$query_ab_ranap = "SELECT reg_periksa.status_lanjut, kelompokkamar.kelompok_kamar, COUNT(DISTINCT kamar_inap.no_rawat) AS jumlah_antibiotik FROM kamar_inap INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat INNER JOIN kelompokkamar ON kamar_inap.kd_kamar = kelompokkamar.kd_kamar INNER JOIN detail_pemberian_obat ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng WHERE $where_ab_ranap AND kamar_inap.tgl_keluar = (SELECT MAX(ki2.tgl_keluar) FROM kamar_inap ki2 WHERE ki2.no_rawat = kamar_inap.no_rawat) GROUP BY reg_periksa.status_lanjut, kelompokkamar.kelompok_kamar";
$result_ab_ranap = mysqli_query($koneksi, $query_ab_ranap);
if ($result_ab_ranap && mysqli_num_rows($result_ab_ranap) > 0) {
    while ($row = mysqli_fetch_assoc($result_ab_ranap)) {
        $key = $row['status_lanjut'].'|'.$row['kelompok_kamar'];
        if (!isset($rekap_ranap[$key])) {
            $rekap_ranap[$key] = [
                'periode' => date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)),
                'status_lanjut' => $row['status_lanjut'],
                'kelompok_kamar' => $row['kelompok_kamar'],
                'jumlah_sampel' => 0,
                'jumlah_antibiotik' => 0
            ];
        }
        $rekap_ranap[$key]['jumlah_antibiotik'] = (int)$row['jumlah_antibiotik'];
    }
}
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Pengambilan Data
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
                    <button type="submit" name="filter" class="btn btn-primary">🔍 Filter Data</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset</button>
                </div>
            </form>
            <?php if (count($rekap) > 0): ?>
                <div class="filter-actions">
                    <button type="button" onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>
                </div>
                <div class="table-responsive">
                    <table id="tabel-antibiotik">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode Pengambilan Data</th>
                                <th>Jenis Perawatan</th>
                                <th>AREA</th>
                                <th>Jumlah Sampel Periode</th>
                                <th colspan="2">Jumlah Pasien yang Mendapat Antibiotik</th>
                            </tr>
                            <tr>
                                <th colspan="5"></th>
                                <th>Jumlah</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rekap as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['periode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status_lanjut']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nm_poli']); ?></td>
                                    <td><?php echo $row['jumlah_sampel']; ?></td>
                                    <td><?php echo $row['jumlah_antibiotik']; ?></td>
                                    <td><?php echo $row['jumlah_sampel'] > 0 ? number_format(($row['jumlah_antibiotik']/$row['jumlah_sampel'])*100, 2) : '0.00'; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                    <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                    <br>
                    <strong>📋 Total Data:</strong> <?php echo count($rekap); ?> AREA
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data ditemukan untuk periode yang dipilih<br><small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>

            <!-- TABEL RL 3.19.2 RAWAT INAP (RANAP) -->
            <?php if (count($rekap_ranap) > 0): ?>
                <div class="filter-actions" style="margin-top:40px;">
                    <button type="button" onclick="copyTableDataRanap()" class="btn btn-success">📋 Copy Tabel Ranap</button>
                </div>
                <div class="table-responsive">
                    <table id="tabel-antibiotik-ranap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode Pengambilan Data</th>
                                <th>Jenis Perawatan</th>
                                <th>AREA</th>
                                <th>Jumlah Sampel Periode Survei (Pasien)</th>
                                <th colspan="2">Jumlah Pasien yang Mendapat Antibiotik</th>
                            </tr>
                            <tr>
                                <th colspan="5"></th>
                                <th>Jumlah (N)</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rekap_ranap as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['periode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status_lanjut']); ?></td>
                                    <td><?php echo htmlspecialchars($row['kelompok_kamar']); ?></td>
                                    <td><?php echo $row['jumlah_sampel']; ?></td>
                                    <td><?php echo $row['jumlah_antibiotik']; ?></td>
                                    <td><?php echo $row['jumlah_sampel'] > 0 ? number_format(($row['jumlah_antibiotik']/$row['jumlah_sampel'])*100, 2) : '0.00'; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                    <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                    <br>
                    <strong>📋 Total Data:</strong> <?php echo count($rekap_ranap); ?> AREA
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
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-t'); ?>';
        }
        function copyTableDataRanap() {
            let table = document.getElementById("tabel-antibiotik-ranap");
            if (table) {
                let range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand("copy");
                    alert("✅ Tabel RL 3.19.2 Ranap berhasil disalin ke clipboard!");
                } catch(err) {
                    alert("❌ Gagal menyalin tabel RL 3.19.2 Ranap");
                }
                window.getSelection().removeAllRanges();
            }
        }
    </script>
</body>
</html>
