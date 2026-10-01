<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan IGD - RSUD Pringsewu</title>
    <style>
        * { box-sizing: border-box; }
        body, table, th, td, input, select, button { font-family: Tahoma, Geneva, Verdana, sans-serif; }
        body { margin:0; padding:15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh; }
        .container { max-width:100%; background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.2); overflow:hidden; }
        .header { background: linear-gradient(45deg, #28a745, #20c997); color:#fff; padding:25px; text-align:center; }
        .header h1 { margin:0; font-size:1.8em; font-weight:bold; }
        .content { padding:25px; }
        .back-button { margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; }
        .back-button a { display:inline-block; padding:10px 20px; background:#6c757d; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold; transition:.3s; box-shadow:0 4px 15px rgba(108,117,125,.3); }
        .back-button a:hover { background:#5a6268; transform:translateY(-2px); }
        .filter-form { background:#f8f9fa; padding:25px; border-radius:12px; margin-bottom:25px; border:1px solid #e9ecef; }
        .filter-title { font-size:18px; font-weight:bold; color:#333; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .filter-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:20px; }
        .filter-group { display:flex; flex-direction:column; gap:8px; }
        .filter-group label { font-weight:bold; color:#495057; font-size:14px; }
        .filter-group input, .filter-group select { padding:12px; border:2px solid #e9ecef; border-radius:8px; font-size:14px; transition:.3s; background:#fff; }
        .filter-group input:focus, .filter-group select:focus { outline:none; border-color:#007bff; box-shadow:0 0 0 3px rgba(0,123,255,.1); }
        .filter-actions { display:flex; gap:15px; justify-content:center; flex-wrap:wrap; }
        .btn { padding:12px 25px; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; transition:.3s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary { background:linear-gradient(45deg,#007bff,#0056b3); color:#fff; box-shadow:0 4px 15px rgba(0,123,255,.3); }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,123,255,.4); }
        .btn-success { background:linear-gradient(45deg,#28a745,#20c997); color:#fff; box-shadow:0 4px 15px rgba(40,167,69,.3); }
        .btn-success:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(40,167,69,.4); }
        .btn-secondary { background:#6c757d; color:#fff; box-shadow:0 4px 15px rgba(108,117,125,.3); }
        .btn-secondary:hover { background:#5a6268; transform:translateY(-2px); }
        .table-responsive { overflow-x:auto; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,.1); margin-top:20px; -webkit-overflow-scrolling:touch; }
        table { width:100%; border-collapse:collapse; background:#fff; min-width:1300px; }
        th { background:linear-gradient(45deg,#343a40,#495057); color:#fff; padding:15px 12px; text-align:left; font-weight:bold; font-size:13px; white-space:nowrap; }
        td { padding:12px; border-bottom:1px solid #e9ecef; font-size:13px; }
        tr:nth-child(even) td { background:#f8f9fa; }
        tr:hover td { background:#e3f2fd; }
        .no-data { text-align:center; color:#666; font-style:italic; padding:40px; background:#f8f9fa; border-radius:8px; }
        .badge { display:inline-block; padding:4px 8px; border-radius:6px; font-weight:bold; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
        .badge-ralan { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .badge-ranap { background:#e3f2fd; color:#1565c0; border:1px solid #bbdefb; }
        .badge-utama { background:#fff3e0; color:#e65100; border:1px solid #ffe0b2; }
        .badge-sekunder { background:#f1f3f5; color:#495057; border:1px solid #dee2e6; }
        .kd-penyakit { font-family:monospace; font-weight:bold; color:#d63384; background:#f8f9fa; padding:3px 6px; border-radius:4px; border:1px solid #e9ecef; }
        @media (max-width:768px) { body{padding:10px;} .header{padding:20px 15px;} .header h1{font-size:1.5em;} .content{padding:15px;} .filter-form{padding:20px 15px;} .filter-grid{grid-template-columns:1fr;gap:15px;} .filter-actions{justify-content:stretch;} .btn{padding:10px 15px;font-size:13px;width:100%;justify-content:center;} th,td{padding:8px 6px;font-size:12px;} table{min-width:1200px;} }
        @media (max-width:480px) { .header h1{font-size:1.3em;} .filter-title{font-size:16px;} }
    </style>
    <script>
        function copyTableData(){
            let tbl=document.querySelector('.table-responsive');
            if(tbl){
                let range=document.createRange();
                range.selectNode(tbl);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try{document.execCommand('copy');alert('✅ Tabel disalin ke clipboard!');}
                catch(e){alert('❌ Gagal menyalin tabel');}
                window.getSelection().removeAllRanges();
            }
        }
        function resetForm(){
            document.getElementById('filter_tanggal_start').value='';
            document.getElementById('filter_tanggal_end').value='';
            document.getElementById('status_lanjut').value='';
            document.getElementById('keyword').value='';
        }
    </script>
</head>
<body>
<div class="container">
    <div class="header"><h1>🩺 Laporan Bulanan IGD</h1></div>
    <div class="content">
        <div class="back-button">
            <a href="index.php">← Kembali ke Menu Utama</a>
            <a href="surveilans.php" style="background:#17a2b8;">📊 Menu Surveilans</a>
        </div>
        <?php
        include 'koneksi.php';
        // Default filter values
        $filter_tanggal_start = isset($_POST['filter_tanggal_start']) ? $_POST['filter_tanggal_start'] : '';
        $filter_tanggal_end   = isset($_POST['filter_tanggal_end'])   ? $_POST['filter_tanggal_end']   : '';
        $status_lanjut        = isset($_POST['status_lanjut']) ? $_POST['status_lanjut'] : '';
        $keyword              = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        ?>
        <form method="POST" class="filter-form">
            <div class="filter-title">🔍 Filter Laporan IGD</div>
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="filter_tanggal_start">📅 Tanggal Registrasi Awal</label>
                    <input type="date" id="filter_tanggal_start" name="filter_tanggal_start" value="<?php echo htmlspecialchars($filter_tanggal_start); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="filter_tanggal_end">📅 Tanggal Registrasi Akhir</label>
                    <input type="date" id="filter_tanggal_end" name="filter_tanggal_end" value="<?php echo htmlspecialchars($filter_tanggal_end); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="status_lanjut">🔄 Status Lanjut</label>
                    <select id="status_lanjut" name="status_lanjut">
                        <option value="">-- Semua Status --</option>
                        <option value="Ralan" <?php if($status_lanjut=='Ralan') echo 'selected'; ?>>Rawat Jalan (Ralan)</option>
                        <option value="Ranap" <?php if($status_lanjut=='Ranap') echo 'selected'; ?>>Rawat Inap (Ranap)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="keyword">🔎 Kata Kunci / Diagnosa</label>
                    <input type="text" id="keyword" name="keyword" placeholder="Kode ICD, Penyakit, No. Rawat, RM, Nama..." value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">📊 Tampilkan Data</button>
                <button type="button" onclick="resetForm()" class="btn btn-secondary">🔄 Reset Filter</button>
            </div>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD']=='POST' && $filter_tanggal_start && $filter_tanggal_end) {
            $where = [];
            $where[] = "reg_periksa.tgl_registrasi BETWEEN '" . mysqli_real_escape_string($koneksi,$filter_tanggal_start) . "' AND '" . mysqli_real_escape_string($koneksi,$filter_tanggal_end) . "'";
            if($status_lanjut!=''){
                $where[] = "reg_periksa.status_lanjut = '" . mysqli_real_escape_string($koneksi,$status_lanjut) . "'";
            }
            if($keyword!=''){
                $kw = mysqli_real_escape_string($koneksi,$keyword);
                $where[] = "(
                    reg_periksa.no_rawat LIKE '%$kw%' OR
                    pasien.no_rkm_medis LIKE '%$kw%' OR
                    pasien.nm_pasien LIKE '%$kw%' OR
                    diagnosa_pasien.kd_penyakit LIKE '%$kw%' OR
                    penyakit.nm_penyakit LIKE '%$kw%'
                )";
            }
            $where_sql = 'WHERE ' . implode(' AND ', $where);
            $query = "SELECT
                reg_periksa.tgl_registrasi,
                reg_periksa.no_rawat,
                pasien.nm_pasien,
                pasien.no_rkm_medis,
                kelurahan.nm_kel,
                kecamatan.nm_kec,
                kabupaten.nm_kab,
                propinsi.nm_prop,
                reg_periksa.stts_daftar,
                penjab.png_jawab,
                poliklinik.nm_poli,
                reg_periksa.status_lanjut,
                bangsal.nm_bangsal,
                dokter.nm_dokter,
                diagnosa_pasien.kd_penyakit,
                diagnosa_pasien.prioritas,
                penyakit.nm_penyakit
            FROM reg_periksa
                INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
                INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
                INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
                INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
                INNER JOIN propinsi ON pasien.kd_prop = propinsi.kd_prop
                INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj AND pasien.kd_pj = penjab.kd_pj
                INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
                LEFT JOIN kamar_inap ON kamar_inap.no_rawat = reg_periksa.no_rawat
                LEFT JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
                LEFT JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
                LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
                INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
            $where_sql
            ORDER BY reg_periksa.tgl_registrasi ASC, reg_periksa.no_rawat ASC";
            $result = mysqli_query($koneksi,$query);
            if($result){
                $total = mysqli_num_rows($result);
                echo '<div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">';
                echo '<div style="font-weight:bold; color:#495057;">📊 Total Data: <span style="color:#007bff;">' . number_format($total,0,',','.') . '</span> catatan</div>';
                if($total>0) echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
                echo '</div>';
                if($total>0){
                    echo "<div class='table-responsive'><table><tr>
                        <th>No</th>
                        <th>Tanggal Registrasi</th>
                        <th>No Rawat</th>
                        <th>Nama Pasien</th>
                        <th>No RM</th>
                        <th>Status Lanjut</th>
                        <th>Poliklinik</th>
                        <th>Status Daftar</th>
                        <th>Penjamin</th>
                        <th>Bangsal</th>
                        <th>Dokter</th>
                        <th>Kode ICD</th>
                        <th>Nama Penyakit</th>
<th>Prioritas</th>
                        <th>Kelurahan</th>
                        <th>Kecamatan</th>
                        <th>Kabupaten</th>
                        <th>Propinsi</th>
                    </tr>";
                    $no=1;
                    while($row=mysqli_fetch_assoc($result)){
                        $status_raw = strtolower($row['status_lanjut'] ?? '');
                        $badge_status = $status_raw=='ralan'?"<span class='badge badge-ralan'>Ralan</span>"
                            :($status_raw=='ranap'?"<span class='badge badge-ranap'>Ranap</span>":"<span class='badge'>".htmlspecialchars($row['status_lanjut']??'-')."</span>");
        $prioritas_val = $row['prioritas'] ?? '';
        if ($prioritas_val == '1') {
            $badge_prioritas = "<span class='badge badge-utama'>1 - Utama</span>";
        } elseif (!empty($prioritas_val)) {
            $badge_prioritas = "<span class='badge badge-sekunder'>" . htmlspecialchars($prioritas_val) . " - Sekunder</span>";
        } else {
            $badge_prioritas = "-";
        }
                        echo "<tr>
                            <td>{$no}</td>
                            <td>".htmlspecialchars($row['tgl_registrasi'])."</td>
                            <td>".htmlspecialchars($row['no_rawat'])."</td>
                            <td>".htmlspecialchars($row['nm_pasien'])."</td>
                            <td>".htmlspecialchars($row['no_rkm_medis'])."</td>
                            <td>{$badge_status}</td>
                            <td>".htmlspecialchars($row['nm_poli'])."</td>
                            <td>".htmlspecialchars($row['stts_daftar'])."</td>
                            <td>".htmlspecialchars($row['png_jawab'])."</td>
                            <td>".htmlspecialchars($row['nm_bangsal'])."</td>
                            <td>".htmlspecialchars($row['nm_dokter'])."</td>
                            <td><span class='kd-penyakit'>".htmlspecialchars($row['kd_penyakit'])."</span></td>
                            <td>".htmlspecialchars($row['nm_penyakit'])."</td>
                            <td>{$badge_prioritas}</td>
                            <td>".htmlspecialchars($row['nm_kel'])."</td>
                            <td>".htmlspecialchars($row['nm_kec'])."</td>
                            <td>".htmlspecialchars($row['nm_kab'])."</td>
                            <td>".htmlspecialchars($row['nm_prop'])."</td>
                        </tr>";
                        $no++;
                    }
                    echo "</table></div>";
                } else {
                    echo '<div class="no-data">📊 Tidak ada data pada rentang tanggal/penyaringan yang dipilih.</div>';
                }
            } else {
                echo '<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;border:1px solid #f5c6cb;">❌ Query error: '.mysqli_error($koneksi).'</div>';
            }
        } else {
            echo '<div style="text-align:center;color:#6c757d;padding:40px;background:#f8f9fa;border-radius:8px;border:1px dashed #ced4da;">💡 Pilih rentang tanggal dan filter lainnya, lalu tekan <strong>"📊 Tampilkan Data"</strong> untuk melihat laporan.</div>';
        }
        mysqli_close($koneksi);
        ?>
    </div>
</div>
</body>
</html>
