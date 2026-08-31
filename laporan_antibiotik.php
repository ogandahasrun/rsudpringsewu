<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kepatuhan Antibiotik - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(45deg, #e74c3c, #c0392b); color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; font-weight: bold; }
        .header p { margin: 8px 0 0; font-size: 14px; opacity: 0.9; }
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
        th { background: linear-gradient(45deg, #343a40, #495057); color: white; padding: 15px 12px; text-align: center; font-weight: bold; font-size: 13px; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; text-align: center; }
        tr:nth-child(even) td { background: #f8f9fa; }
        tr:hover td { background: #e3f2fd; }
        .no-data { text-align: center; color: #666; font-style: italic; padding: 40px; background: #f8f9fa; border-radius: 8px; margin-top: 20px; }
        .summary-box { margin-top: 20px; padding: 15px; border-radius: 8px; border-left: 4px solid; }
        .summary-info { background: #e3f2fd; border-color: #2196f3; }
        .badge-standar { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #d4edda; color: #155724; }
        .badge-tidak-standar { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #f8d7da; color: #721c24; }
        .persen-tinggi { color: #155724; font-weight: bold; }
        .persen-sedang { color: #856404; font-weight: bold; }
        .persen-rendah { color: #721c24; font-weight: bold; }
        td.text-left { text-align: left; }
        .row-total td { background: linear-gradient(45deg, #343a40, #495057) !important; color: white; font-weight: bold; }
        .row-kelompok td { background: linear-gradient(45deg, #2c3e50, #34495e) !important; color: #f1c40f; font-weight: bold; font-size: 14px; letter-spacing: 0.5px; }
        .row-subtotal td { background: linear-gradient(45deg, #2980b9, #3498db) !important; color: white; font-weight: bold; }
        @media (max-width: 768px) { body { padding: 10px; } .header { padding: 20px 15px; } .header h1 { font-size: 1.5em; } .content { padding: 15px; } .filter-form { padding: 20px 15px; } .filter-grid { grid-template-columns: 1fr; gap: 15px; } .filter-actions { justify-content: stretch; } .btn { padding: 10px 15px; font-size: 13px; } th, td { padding: 8px 6px; font-size: 12px; } table { min-width: 720px; } }
        @media (max-width: 480px) { .header h1 { font-size: 1.3em; } .filter-title { font-size: 16px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 Laporan Kepatuhan Penggunaan Antibiotik</h1>
            <p>Evaluasi Kesesuaian Antibiotik Rawat Inap Berdasarkan Formularium Standar (Per Kelompok Diagnosa)</p>
        </div>
        <div class="content">
            <div class="back-button">
                <a href="surveilans.php">← Kembali</a>
            </div>
<?php
include 'koneksi.php';

$tanggal_awal  = isset($_POST['tanggal_awal'])  ? $_POST['tanggal_awal']  : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-t');
?>
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    📅 Filter Periode Laporan
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">Tanggal Keluar Awal</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">Tanggal Keluar Akhir</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">🔍 Tampilkan Laporan</button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset</button>
                </div>
            </form>

<?php
// ============================================================
// LOGIKA UTAMA (DENGAN KELOMPOK DIAGNOSA)
// ============================================================

// 1. Ambil semua kd_penyakit + kelompok_diagnosa yang ada di mapping
$query_mapping_penyakit = "SELECT DISTINCT kd_penyakit, kelompok_diagnosa FROM mapping_antibiotik_standar ORDER BY kelompok_diagnosa, kd_penyakit";
$result_mp = mysqli_query($koneksi, $query_mapping_penyakit);
$daftar_diagnosa = [];
$kelompok_per_diagnosa = []; // kd_penyakit => kelompok_diagnosa
if ($result_mp && mysqli_num_rows($result_mp) > 0) {
    while ($row = mysqli_fetch_assoc($result_mp)) {
        $daftar_diagnosa[] = $row['kd_penyakit'];
        $kelompok_per_diagnosa[$row['kd_penyakit']] = $row['kelompok_diagnosa'] ?: 'Lainnya';
    }
}

if (count($daftar_diagnosa) === 0) {
    echo '<div class="no-data">⚠️ Belum ada data mapping antibiotik standar.<br><small>Silakan isi tabel <strong>mapping_antibiotik_standar</strong> terlebih dahulu.</small></div>';
} else {

    // 2. Ambil mapping lengkap: diagnosa → list kd_brng standar
    $mapping = [];
    $query_mapping = "SELECT kd_penyakit, kd_brng FROM mapping_antibiotik_standar";
    $result_m = mysqli_query($koneksi, $query_mapping);
    while ($row = mysqli_fetch_assoc($result_m)) {
        $mapping[$row['kd_penyakit']][] = $row['kd_brng'];
    }

    // 3. Buat placeholder untuk IN clause
    $placeholders = "'" . implode("','", array_map(function($v) use ($koneksi) {
        return mysqli_real_escape_string($koneksi, $v);
    }, $daftar_diagnosa)) . "'";

    // 4. Query: ambil semua pasien rawat inap yang pulang (bukan pindah kamar)
    //    dalam periode, yang memiliki salah satu diagnosa target
    $query_pasien = "
        SELECT DISTINCT
            rp.no_rawat,
            dp.kd_penyakit,
            p.nm_penyakit
        FROM reg_periksa rp
        INNER JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
        INNER JOIN diagnosa_pasien dp ON dp.no_rawat = rp.no_rawat
        INNER JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit
        WHERE ki.tgl_keluar BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
          AND ki.tgl_keluar <> '0000-00-00'
          AND ki.stts_pulang <> 'Pindah Kamar'
          AND dp.kd_penyakit IN ($placeholders)
        ORDER BY dp.kd_penyakit, rp.no_rawat
    ";
    $result_pasien = mysqli_query($koneksi, $query_pasien);

    if (!$result_pasien) {
        die("Query error: " . mysqli_error($koneksi));
    }

    // 5. Kumpulkan data per diagnosa
    //    Untuk setiap pasien-diagnosa, cek obat AB yang diberikan
    $rekap = []; // kd_penyakit => [nm_penyakit, total, standar, tidak_standar, kelompok_diagnosa]

    while ($row = mysqli_fetch_assoc($result_pasien)) {
        $no_rawat    = $row['no_rawat'];
        $kd_penyakit = $row['kd_penyakit'];
        $nm_penyakit = $row['nm_penyakit'];

        // Inisialisasi rekap untuk diagnosa ini
        if (!isset($rekap[$kd_penyakit])) {
            $rekap[$kd_penyakit] = [
                'nm_penyakit'        => $nm_penyakit,
                'total_pasien'       => 0,
                'ab_standar'         => 0,
                'ab_tidak_standar'   => 0,
                'kelompok_diagnosa'  => isset($kelompok_per_diagnosa[$kd_penyakit]) ? $kelompok_per_diagnosa[$kd_penyakit] : 'Lainnya'
            ];
        }

        $rekap[$kd_penyakit]['total_pasien']++;

        // Cek obat AB yang diberikan ke pasien ini
        $query_obat_ab = "
            SELECT DISTINCT dpo.kode_brng
            FROM detail_pemberian_obat dpo
            INNER JOIN databarang db ON dpo.kode_brng = db.kode_brng
            WHERE dpo.no_rawat = '" . mysqli_real_escape_string($koneksi, $no_rawat) . "'
              AND db.kode_golongan = 'AB'
        ";
        $result_obat = mysqli_query($koneksi, $query_obat_ab);

        $ada_ab = false;
        $semua_standar = true;

        if ($result_obat && mysqli_num_rows($result_obat) > 0) {
            $ada_ab = true;
            $list_standar = isset($mapping[$kd_penyakit]) ? $mapping[$kd_penyakit] : [];
            while ($obat = mysqli_fetch_assoc($result_obat)) {
                if (!in_array($obat['kode_brng'], $list_standar)) {
                    $semua_standar = false;
                    break;
                }
            }
        }

        // Evaluasi: hanya pasien yang mendapat AB yang dievaluasi
        if ($ada_ab) {
            if ($semua_standar) {
                $rekap[$kd_penyakit]['ab_standar']++;
            } else {
                $rekap[$kd_penyakit]['ab_tidak_standar']++;
            }
        }
    }

    // 6. Kelompokkan rekap berdasarkan kelompok_diagnosa
    $rekap_per_kelompok = []; // kelompok_diagnosa => [kd_penyakit => data]
    foreach ($rekap as $kd => $data) {
        $kelompok = $data['kelompok_diagnosa'];
        $rekap_per_kelompok[$kelompok][$kd] = $data;
    }
    // Urutkan kelompok secara alfabet
    ksort($rekap_per_kelompok);

    // 7. Tampilkan tabel rekap per kelompok diagnosa
    if (count($rekap) > 0) {
        $grand_total   = 0;
        $grand_standar = 0;
        $grand_tidak   = 0;
?>
            <div class="filter-actions">
                <button type="button" onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>
            </div>
            <div class="table-responsive">
                <table id="tabel-kepatuhan">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Kode Diagnosa</th>
                            <th rowspan="2">Nama Penyakit</th>
                            <th rowspan="2">Total Pasien</th>
                            <th colspan="2">Mendapat AB</th>
                            <th rowspan="2">% Kepatuhan</th>
                        </tr>
                        <tr>
                            <th>Standar</th>
                            <th>Tidak Standar</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
        $no = 1;
        foreach ($rekap_per_kelompok as $kelompok => $diagnosa_list) {
            // Hitung jumlah diagnosa dalam kelompok ini
            $jumlah_diagnosa_kelompok = count($diagnosa_list);

            // Header baris kelompok
?>
                        <tr class="row-kelompok">
                            <td colspan="7">📁 Kelompok: <?php echo htmlspecialchars($kelompok); ?> (<?php echo $jumlah_diagnosa_kelompok; ?> diagnosa)</td>
                        </tr>
<?php
            $sub_total   = 0;
            $sub_standar = 0;
            $sub_tidak   = 0;

            foreach ($diagnosa_list as $kd => $data) {
                $total_ab = $data['ab_standar'] + $data['ab_tidak_standar'];
                $persen   = $total_ab > 0 ? ($data['ab_standar'] / $total_ab) * 100 : 0;

                // Warna persentase
                if ($persen >= 80) {
                    $persen_class = 'persen-tinggi';
                } elseif ($persen >= 50) {
                    $persen_class = 'persen-sedang';
                } else {
                    $persen_class = 'persen-rendah';
                }

                $sub_total   += $data['total_pasien'];
                $sub_standar += $data['ab_standar'];
                $sub_tidak   += $data['ab_tidak_standar'];
?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kd); ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($data['nm_penyakit']); ?></td>
                            <td><?php echo $data['total_pasien']; ?></td>
                            <td><span class="badge-standar"><?php echo $data['ab_standar']; ?></span></td>
                            <td><span class="badge-tidak-standar"><?php echo $data['ab_tidak_standar']; ?></span></td>
                            <td class="<?php echo $persen_class; ?>"><?php echo number_format($persen, 2); ?>%</td>
                        </tr>
<?php
            }

            // Subtotal per kelompok
            $sub_total_ab = $sub_standar + $sub_tidak;
            $sub_persen   = $sub_total_ab > 0 ? ($sub_standar / $sub_total_ab) * 100 : 0;

            $grand_total   += $sub_total;
            $grand_standar += $sub_standar;
            $grand_tidak   += $sub_tidak;
?>
                        <tr class="row-subtotal">
                            <td colspan="3">Subtotal <?php echo htmlspecialchars($kelompok); ?></td>
                            <td><?php echo $sub_total; ?></td>
                            <td><?php echo $sub_standar; ?></td>
                            <td><?php echo $sub_tidak; ?></td>
                            <td><?php echo number_format($sub_persen, 2); ?>%</td>
                        </tr>
<?php
        }

        // Baris grand total
        $grand_total_ab = $grand_standar + $grand_tidak;
        $grand_persen   = $grand_total_ab > 0 ? ($grand_standar / $grand_total_ab) * 100 : 0;
?>
                        <tr class="row-total">
                            <td colspan="3">GRAND TOTAL</td>
                            <td><?php echo $grand_total; ?></td>
                            <td><?php echo $grand_standar; ?></td>
                            <td><?php echo $grand_tidak; ?></td>
                            <td><?php echo number_format($grand_persen, 2); ?>%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="summary-box summary-info">
                <strong>📊 Periode Laporan:</strong> <?php echo date('d/m/Y', strtotime($tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($tanggal_akhir)); ?>
                <br>
                <strong>📁 Jumlah Kelompok Diagnosa:</strong> <?php echo count($rekap_per_kelompok); ?> kelompok
                <br>
                <strong>📋 Jumlah Diagnosa:</strong> <?php echo count($rekap); ?> diagnosa
                <br>
                <strong>👥 Total Pasien:</strong> <?php echo $grand_total; ?> pasien
                <br>
                <strong>✅ Kepatuhan Keseluruhan:</strong> <?php echo number_format($grand_persen, 2); ?>%
            </div>
<?php
    } else {
        echo '<div class="no-data">📭 Tidak ada data pasien rawat inap dengan diagnosa terdaftar untuk periode ini.<br><small>Silakan pilih periode lain atau periksa mapping antibiotik.</small></div>';
    }
}

mysqli_close($koneksi);
?>
        </div>
    </div>

    <script>
        function copyTableData() {
            let table = document.getElementById("tabel-kepatuhan");
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
    </script>
</body>
</html>
