<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kunjungan Pasien Berdasarkan Cara Bayar - RSUD Pringsewu 2026</title>
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
            <h1>📊 Laporan RL 3.19 Kunjungan Pasien Berdasarkan Cara Bayar versi 2026</h1>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>
<?php
include 'koneksi.php';
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-t');
$rekap = [];
$periode_label = date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir));

$query_penjab = "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab ASC";
$result_penjab = mysqli_query($koneksi, $query_penjab);
if ($result_penjab) {
    while ($row = mysqli_fetch_assoc($result_penjab)) {
        $rekap[$row['kd_pj']] = [
            'cara_bayar' => $row['png_jawab'],
            'ranap_jumlah_pasien' => 0,
            'ranap_lama_dirawat' => 0,
            'ralan_jumlah_pasien' => 0,
            'ralan_laboratorium' => 0,
            'ralan_radiologi' => 0
        ];
    }
}

$query_ranap_pasien = "SELECT reg_periksa.kd_pj, COUNT(reg_periksa.no_rawat) AS jumlah_pasien
    FROM reg_periksa
    INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
    WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
      AND kamar_inap.stts_pulang <> 'Pindah Kamar'
    GROUP BY reg_periksa.kd_pj";
$result_ranap_pasien = mysqli_query($koneksi, $query_ranap_pasien);
if ($result_ranap_pasien) {
    while ($row = mysqli_fetch_assoc($result_ranap_pasien)) {
        if (isset($rekap[$row['kd_pj']])) {
            $rekap[$row['kd_pj']]['ranap_jumlah_pasien'] = (int)$row['jumlah_pasien'];
        }
    }
}

$query_ranap_lama = "SELECT reg_periksa.kd_pj, SUM(kamar_inap.lama) AS lama_dirawat
    FROM reg_periksa
    INNER JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
    WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY reg_periksa.kd_pj";
$result_ranap_lama = mysqli_query($koneksi, $query_ranap_lama);
if ($result_ranap_lama) {
    while ($row = mysqli_fetch_assoc($result_ranap_lama)) {
        if (isset($rekap[$row['kd_pj']])) {
            $rekap[$row['kd_pj']]['ranap_lama_dirawat'] = (int)$row['lama_dirawat'];
        }
    }
}

$query_ralan_pasien = "SELECT reg_periksa.kd_pj, COUNT(reg_periksa.no_rawat) AS jumlah_pasien
    FROM reg_periksa
    WHERE reg_periksa.status_lanjut = 'ralan'
      AND reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY reg_periksa.kd_pj";
$result_ralan_pasien = mysqli_query($koneksi, $query_ralan_pasien);
if ($result_ralan_pasien) {
    while ($row = mysqli_fetch_assoc($result_ralan_pasien)) {
        if (isset($rekap[$row['kd_pj']])) {
            $rekap[$row['kd_pj']]['ralan_jumlah_pasien'] = (int)$row['jumlah_pasien'];
        }
    }
}

$query_ralan_lab = "SELECT reg_periksa.kd_pj, COUNT(DISTINCT periksa_lab.no_rawat) AS jumlah_pasien
    FROM reg_periksa
    INNER JOIN periksa_lab ON periksa_lab.no_rawat = reg_periksa.no_rawat
    WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    AND reg_periksa.status_lanjut = 'ralan'
    GROUP BY reg_periksa.kd_pj";
$result_ralan_lab = mysqli_query($koneksi, $query_ralan_lab);
if ($result_ralan_lab) {
    while ($row = mysqli_fetch_assoc($result_ralan_lab)) {
        if (isset($rekap[$row['kd_pj']])) {
            $rekap[$row['kd_pj']]['ralan_laboratorium'] = (int)$row['jumlah_pasien'];
        }
    }
}

$query_ralan_radiologi = "SELECT reg_periksa.kd_pj, COUNT(DISTINCT periksa_radiologi.no_rawat) AS jumlah_pasien
    FROM reg_periksa
    INNER JOIN periksa_radiologi ON periksa_radiologi.no_rawat = reg_periksa.no_rawat
    WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    AND reg_periksa.status_lanjut = 'ralan'
    GROUP BY reg_periksa.kd_pj";
$result_ralan_radiologi = mysqli_query($koneksi, $query_ralan_radiologi);
if ($result_ralan_radiologi) {
    while ($row = mysqli_fetch_assoc($result_ralan_radiologi)) {
        if (isset($rekap[$row['kd_pj']])) {
            $rekap[$row['kd_pj']]['ralan_radiologi'] = (int)$row['jumlah_pasien'];
        }
    }
}
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Laporan
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
                <?php
                $total_ranap_jumlah_pasien = 0;
                $total_ranap_lama_dirawat = 0;
                $total_ralan_jumlah_pasien = 0;
                $total_ralan_laboratorium = 0;
                $total_ralan_radiologi = 0;

                foreach ($rekap as $item) {
                    $total_ranap_jumlah_pasien += $item['ranap_jumlah_pasien'];
                    $total_ranap_lama_dirawat += $item['ranap_lama_dirawat'];
                    $total_ralan_jumlah_pasien += $item['ralan_jumlah_pasien'];
                    $total_ralan_laboratorium += $item['ralan_laboratorium'];
                    $total_ralan_radiologi += $item['ralan_radiologi'];
                }
                ?>
                <div class="filter-actions">
                    <button type="button" onclick="copyTableData()" class="btn btn-success">📋 Copy to Clipboard</button>
                </div>
                <div class="table-responsive">
                    <table id="tabel-carabayar">
                        <thead>
                            <tr>
                                <th>Nomor Urut</th>
                                <th>Cara Bayar</th>
                                <th>Pasien Rawat Inap Jumlah Pasien Dirawat</th>
                                <th>Pasien Rawat Inap Jumlah Lama Dirawat</th>
                                <th>Jumlah Pasien Rawat Jalan</th>
                                <th>Jumlah Pasien Rawat Jalan Laboratorium</th>
                                <th>Jumlah Pasien Rawat Jalan Radiologi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($rekap as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['cara_bayar']); ?></td>
                                    <td><?php echo number_format($row['ranap_jumlah_pasien']); ?></td>
                                    <td><?php echo number_format($row['ranap_lama_dirawat']); ?></td>
                                    <td><?php echo number_format($row['ralan_jumlah_pasien']); ?></td>
                                    <td><?php echo number_format($row['ralan_laboratorium']); ?></td>
                                    <td><?php echo number_format($row['ralan_radiologi']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="font-weight: bold; background: #d1ecf1;">
                                <td colspan="2">TOTAL</td>
                                <td><?php echo number_format($total_ranap_jumlah_pasien); ?></td>
                                <td><?php echo number_format($total_ranap_lama_dirawat); ?></td>
                                <td><?php echo number_format($total_ralan_jumlah_pasien); ?></td>
                                <td><?php echo number_format($total_ralan_laboratorium); ?></td>
                                <td><?php echo number_format($total_ralan_radiologi); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                    <strong>📊 Periode Laporan:</strong> <?php echo $periode_label; ?>
                    <br>
                    <strong>📋 Total Data:</strong> <?php echo count($rekap); ?> Cara Bayar
                </div>
            <?php else: ?>
                <div class="no-data">
                    📭 Tidak ada data ditemukan untuk periode yang dipilih<br><small>Silakan pilih periode tanggal lain</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        async function copyTableData() {
            let table = document.getElementById("tabel-carabayar");
            if (!table) {
                alert("❌ Tabel tidak ditemukan");
                return;
            }

            let tableText = Array.from(table.rows).map(function(row) {
                return Array.from(row.cells).map(function(cell) {
                    return cell.innerText.trim();
                }).join("\t");
            }).join("\n");

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(tableText);
                } else {
                    let textArea = document.createElement("textarea");
                    textArea.value = tableText;
                    textArea.style.position = "fixed";
                    textArea.style.left = "-9999px";
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand("copy");
                    document.body.removeChild(textArea);
                }
                alert("✅ Tabel berhasil disalin ke clipboard!");
            } catch (err) {
                alert("❌ Gagal menyalin tabel ke clipboard");
            }
        }
        function resetForm() {
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-01'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-t'); ?>';
        }
    </script>
</body>
</html>
