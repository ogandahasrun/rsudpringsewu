<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Obat Pasien Rawat Jalan BPJS - RSUD Pringsewu</title>
    <meta name="description" content="Halaman evaluasi perbandingan biaya obat pasien rawat jalan dengan klaim yang disetujui BPJS di RSUD Pringsewu">
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
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(45deg, #1a5276, #148f77);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 1.8em;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.85;
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
            font-size: 13px;
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
            font-size: 17px;
            font-weight: bold;
            color: #1a5276;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #148f77;
            padding-bottom: 10px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .filter-group label {
            font-weight: bold;
            color: #495057;
            font-size: 13px;
        }
        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #148f77;
            box-shadow: 0 0 0 3px rgba(20, 143, 119, 0.15);
        }
        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 11px 24px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #1a5276, #2980b9);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 82, 118, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.45);
        }
        .btn-success {
            background: linear-gradient(45deg, #148f77, #27ae60);
            color: white;
            box-shadow: 0 4px 15px rgba(20, 143, 119, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 143, 119, 0.45);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.25);
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 16px 18px;
            text-align: center;
            border-left: 4px solid #148f77;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .summary-card.card-blue   { border-left-color: #2980b9; }
        .summary-card.card-orange { border-left-color: #e67e22; }
        .summary-card.card-red    { border-left-color: #e74c3c; }
        .summary-card.card-green  { border-left-color: #27ae60; }
        .summary-card.card-purple { border-left-color: #8e44ad; }
        .summary-card .label {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .summary-card .value {
            font-size: 17px;
            font-weight: bold;
            color: #1a5276;
        }
        /* Table */
        .table-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }
        .table-info {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
        }
        .table-responsive {
            overflow: auto;
            max-height: 70vh;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1400px;
        }
        th {
            background: linear-gradient(45deg, #1a5276, #1f618d);
            color: white;
            padding: 11px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            white-space: nowrap;
            border: 1px solid #154360;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        td {
            padding: 8px 7px;
            border: 1px solid #dee2e6;
            font-size: 11px;
            vertical-align: middle;
        }
        td.angka {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
        }
        td.center {
            text-align: center;
        }
        tr:nth-child(even) td {
            background: #f4f9fc;
        }
        tr:hover td {
            background: #e8f4f8 !important;
            transition: background 0.15s ease;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 50px 20px;
            background: #f8f9fa;
        }
        .total-row td {
            background: #d1ecf1 !important;
            font-weight: bold;
            color: #0c5460;
            border-top: 3px solid #1a5276;
        }
        /* Selisih color badges */
        .selisih-positif {
            color: #155724;
            background: #d4edda;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .selisih-negatif {
            color: #721c24;
            background: #f8d7da;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
        }
        .selisih-netral {
            color: #383d41;
            background: #e2e3e5;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
        }
        /* Mobile */
        @media (max-width: 768px) {
            body { padding: 8px; }
            .header { padding: 18px 15px; }
            .header h1 { font-size: 1.35em; }
            .content { padding: 15px; }
            .filter-form { padding: 18px 14px; }
            .filter-grid { grid-template-columns: 1fr; gap: 12px; }
            th, td { padding: 5px 4px; font-size: 10px; }
        }
        @media (max-width: 480px) {
            .header h1 { font-size: 1.15em; }
        }
    </style>
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
            document.getElementById('tanggal_awal').value    = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value   = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('kd_poli').value         = 'All';
            document.getElementById('kd_dokter').value       = 'All';
            document.getElementById('kd_pj').value           = 'All';
            document.getElementById('status_lanjut').value   = 'All';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 Evaluasi Obat Pasien Rawat Jalan BPJS</h1>
            <p>Perbandingan Biaya Obat Pasien vs Klaim yang Disetujui BPJS – RSUD Pringsewu</p>
        </div>

        <div class="content">
            <div class="back-button">
                <a href="casemix.php">← Kembali ke Menu Casemix</a>
            </div>

<?php
include 'koneksi.php';

// Default values
$tanggal_awal   = isset($_POST['tanggal_awal'])   ? $_POST['tanggal_awal']   : date('Y-m-d');
$tanggal_akhir  = isset($_POST['tanggal_akhir'])  ? $_POST['tanggal_akhir']  : date('Y-m-d');
$kd_poli        = isset($_POST['kd_poli'])        ? $_POST['kd_poli']        : 'All';
$kd_dokter      = isset($_POST['kd_dokter'])      ? $_POST['kd_dokter']      : 'All';
$kd_pj          = isset($_POST['kd_pj'])          ? $_POST['kd_pj']          : 'All';
$status_lanjut  = isset($_POST['status_lanjut'])  ? $_POST['status_lanjut']  : 'All';
?>

            <!-- ===== FILTER FORM ===== -->
            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Data
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="tanggal_awal">📅 Tanggal Awal</label>
                        <input type="date"
                               id="tanggal_awal"
                               name="tanggal_awal"
                               required
                               value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="tanggal_akhir">📅 Tanggal Akhir</label>
                        <input type="date"
                               id="tanggal_akhir"
                               name="tanggal_akhir"
                               required
                               value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="kd_poli">🏥 Poliklinik</label>
                        <select id="kd_poli" name="kd_poli">
                            <option value="All" <?php echo $kd_poli === 'All' ? 'selected' : ''; ?>>- Semua Poliklinik -</option>
                            <?php
                            $q_poli_opt = mysqli_query($koneksi, "SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
                            while ($r_poli = mysqli_fetch_assoc($q_poli_opt)) {
                                $sel = $kd_poli === $r_poli['kd_poli'] ? 'selected' : '';
                                echo "<option value='{$r_poli['kd_poli']}' {$sel}>{$r_poli['nm_poli']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kd_dokter">👨‍⚕️ Dokter</label>
                        <select id="kd_dokter" name="kd_dokter">
                            <option value="All" <?php echo $kd_dokter === 'All' ? 'selected' : ''; ?>>- Semua Dokter -</option>
                            <?php
                            $q_doc_opt = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
                            while ($r_doc = mysqli_fetch_assoc($q_doc_opt)) {
                                $sel = $kd_dokter === $r_doc['kd_dokter'] ? 'selected' : '';
                                echo "<option value='{$r_doc['kd_dokter']}' {$sel}>{$r_doc['nm_dokter']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kd_pj">💳 Cara Bayar (Penjab)</label>
                        <select id="kd_pj" name="kd_pj">
                            <option value="All" <?php echo $kd_pj === 'All' ? 'selected' : ''; ?>>- Semua Penjamin -</option>
                            <?php
                            $q_pj_opt = mysqli_query($koneksi, "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
                            while ($r_pj = mysqli_fetch_assoc($q_pj_opt)) {
                                $sel = $kd_pj === $r_pj['kd_pj'] ? 'selected' : '';
                                echo "<option value='{$r_pj['kd_pj']}' {$sel}>{$r_pj['png_jawab']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status_lanjut">🔄 Status Lanjut</label>
                        <select id="status_lanjut" name="status_lanjut">
                            <option value="All" <?php echo $status_lanjut === 'All' ? 'selected' : ''; ?>>- Semua Status -</option>
                            <?php
                            $q_sl = mysqli_query($koneksi, "SELECT DISTINCT status_lanjut FROM reg_periksa WHERE status_lanjut IS NOT NULL AND status_lanjut != '' ORDER BY status_lanjut");
                            while ($r_sl = mysqli_fetch_assoc($q_sl)) {
                                $sel = $status_lanjut === $r_sl['status_lanjut'] ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($r_sl['status_lanjut']) . "' {$sel}>" . htmlspecialchars($r_sl['status_lanjut']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary" id="btn-tampilkan">
                        📊 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary" id="btn-reset">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

<?php
if (isset($_POST['filter'])) {
    $tanggal_awal  = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $kd_poli       = isset($_POST['kd_poli'])       ? $_POST['kd_poli']       : 'All';
    $kd_dokter     = isset($_POST['kd_dokter'])     ? $_POST['kd_dokter']     : 'All';
    $kd_pj         = isset($_POST['kd_pj'])         ? $_POST['kd_pj']         : 'All';
    $status_lanjut = isset($_POST['status_lanjut']) ? $_POST['status_lanjut'] : 'All';

    // ============================================================
    // BUILD DYNAMIC WHERE CLAUSE
    // ============================================================
    $where_clauses = [
        "reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'",
        "reg_periksa.stts != 'Batal'"
    ];
    if ($kd_poli !== 'All') {
        $where_clauses[] = "reg_periksa.kd_poli = '" . mysqli_real_escape_string($koneksi, $kd_poli) . "'";
    }
    if ($kd_dokter !== 'All') {
        $where_clauses[] = "reg_periksa.kd_dokter = '" . mysqli_real_escape_string($koneksi, $kd_dokter) . "'";
    }
    if ($kd_pj !== 'All') {
        $where_clauses[] = "reg_periksa.kd_pj = '" . mysqli_real_escape_string($koneksi, $kd_pj) . "'";
    }
    if ($status_lanjut !== 'All') {
        $where_clauses[] = "reg_periksa.status_lanjut = '" . mysqli_real_escape_string($koneksi, $status_lanjut) . "'";
    }
    $where_sql = implode(" AND ", $where_clauses);

    // ============================================================
    // QUERY UTAMA: Data pasien rawat jalan
    // ============================================================
    $query_utama = "SELECT
        reg_periksa.tgl_registrasi,
        reg_periksa.no_rawat,
        pasien.no_rkm_medis,
        pasien.nm_pasien,
        poliklinik.nm_poli,
        dokter.nm_dokter
    FROM
        reg_periksa
        INNER JOIN pasien     ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN poliklinik ON reg_periksa.kd_poli      = poliklinik.kd_poli
        INNER JOIN dokter     ON reg_periksa.kd_dokter    = dokter.kd_dokter
    WHERE
        $where_sql
    ORDER BY reg_periksa.tgl_registrasi, reg_periksa.no_rawat";

    $result_utama = mysqli_query($koneksi, $query_utama);

    if (!$result_utama) {
        echo '<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;border:1px solid #f5c6cb;">';
        echo "❌ Error query utama: " . mysqli_error($koneksi);
        echo '</div>';
    } else {
        // Kumpulkan semua data & no_rawat
        $data_pasien  = [];
        $all_no_rawat = [];
        while ($row = mysqli_fetch_assoc($result_utama)) {
            $data_pasien[]   = $row;
            $all_no_rawat[]  = $row['no_rawat'];
        }
        $total_rows = count($data_pasien);

        if ($total_rows > 0) {
            // Buat IN clause (sanitized)
            $in_clause = "'" . implode("','", array_map(function($nr) use ($koneksi) {
                return mysqli_real_escape_string($koneksi, $nr);
            }, $all_no_rawat)) . "'";

            // ============================================================
            // QUERY BIAYA OBAT (billing status = 'obat')
            // Relasi: billing INNER JOIN rspsw_umbal ON billing.no_rawat = rspsw_umbal.no_rawat
            // ============================================================
            $biaya_obat = [];
            $q_biaya_obat = "SELECT
                billing.no_rawat,
                SUM(billing.totalbiaya) AS total_biaya_obat
            FROM billing
            INNER JOIN rspsw_umbal ON billing.no_rawat = rspsw_umbal.no_rawat
            WHERE
                billing.`status` = 'obat'
                AND billing.no_rawat IN ($in_clause)
            GROUP BY billing.no_rawat";
            $res_biaya_obat = mysqli_query($koneksi, $q_biaya_obat);
            if ($res_biaya_obat) {
                while ($r = mysqli_fetch_assoc($res_biaya_obat)) {
                    $biaya_obat[$r['no_rawat']] = floatval($r['total_biaya_obat']);
                }
            }

            // ============================================================
            // QUERY RETUR OBAT (billing status = 'Retur Obat')
            // Nilai retur sudah negatif, sehingga hasilnya sudah minus
            // ============================================================
            $retur_obat = [];
            $q_retur_obat = "SELECT
                billing.no_rawat,
                SUM(billing.totalbiaya) AS total_retur_obat
            FROM billing
            INNER JOIN rspsw_umbal ON billing.no_rawat = rspsw_umbal.no_rawat
            WHERE
                billing.`status` = 'Retur Obat'
                AND billing.no_rawat IN ($in_clause)
            GROUP BY billing.no_rawat";
            $res_retur_obat = mysqli_query($koneksi, $q_retur_obat);
            if ($res_retur_obat) {
                while ($r = mysqli_fetch_assoc($res_retur_obat)) {
                    $retur_obat[$r['no_rawat']] = floatval($r['total_retur_obat']);
                }
            }

            // ============================================================
            // QUERY DISETUJUI (rspsw_umbal.disetujui)
            // ============================================================
            $disetujui_data = [];
            $q_disetujui = "SELECT
                reg_periksa.no_rawat,
                rspsw_umbal.disetujui
            FROM reg_periksa
            INNER JOIN rspsw_umbal ON rspsw_umbal.no_rawat = reg_periksa.no_rawat
            WHERE reg_periksa.no_rawat IN ($in_clause)";
            $res_disetujui = mysqli_query($koneksi, $q_disetujui);
            if ($res_disetujui) {
                while ($r = mysqli_fetch_assoc($res_disetujui)) {
                    $disetujui_data[$r['no_rawat']] = floatval($r['disetujui']);
                }
            }

            // ============================================================
            // HITUNG GRAND TOTAL untuk Summary Cards
            // ============================================================
            $grand_biaya_obat   = 0;
            $grand_retur_obat   = 0;
            $grand_bersih_obat  = 0;
            $grand_disetujui    = 0;
            $grand_selisih      = 0;
            $cnt_untung         = 0; // disetujui >= obat bersih
            $cnt_rugi           = 0; // obat bersih > disetujui

            foreach ($data_pasien as $row) {
                $nr = $row['no_rawat'];
                $val_obat    = isset($biaya_obat[$nr])   ? $biaya_obat[$nr]   : 0;
                $val_retur   = isset($retur_obat[$nr])   ? $retur_obat[$nr]   : 0;
                // Retur sudah negatif, jadi: bersih = obat + retur (retur negatif)
                $val_bersih  = $val_obat + $val_retur;
                $val_dis     = isset($disetujui_data[$nr]) ? $disetujui_data[$nr] : 0;
                $val_selisih = $val_dis - $val_bersih;

                $grand_biaya_obat  += $val_obat;
                $grand_retur_obat  += $val_retur;
                $grand_bersih_obat += $val_bersih;
                $grand_disetujui   += $val_dis;
                $grand_selisih     += $val_selisih;

                if ($val_dis >= $val_bersih) $cnt_untung++;
                else $cnt_rugi++;
            }

            // ============================================================
            // SUMMARY CARDS
            // ============================================================
            echo '<div class="summary-cards">';
            echo '<div class="summary-card card-blue">'
               . '<div class="label">Total Pasien</div>'
               . '<div class="value">' . number_format($total_rows, 0, ',', '.') . '</div>'
               . '</div>';
            echo '<div class="summary-card">'
               . '<div class="label">Total Biaya Obat</div>'
               . '<div class="value">Rp ' . number_format($grand_biaya_obat, 0, ',', '.') . '</div>'
               . '</div>';
            echo '<div class="summary-card card-orange">'
               . '<div class="label">Total Retur Obat</div>'
               . '<div class="value">Rp ' . number_format($grand_retur_obat, 0, ',', '.') . '</div>'
               . '</div>';
            echo '<div class="summary-card card-purple">'
               . '<div class="label">Total Obat Bersih</div>'
               . '<div class="value">Rp ' . number_format($grand_bersih_obat, 0, ',', '.') . '</div>'
               . '</div>';
            echo '<div class="summary-card card-green">'
               . '<div class="label">Total Disetujui BPJS</div>'
               . '<div class="value">Rp ' . number_format($grand_disetujui, 0, ',', '.') . '</div>'
               . '</div>';

            $selisih_class = $grand_selisih >= 0 ? 'card-green' : 'card-red';
            echo '<div class="summary-card ' . $selisih_class . '">'
               . '<div class="label">Selisih Total</div>'
               . '<div class="value">Rp ' . number_format($grand_selisih, 0, ',', '.') . '</div>'
               . '</div>';
            echo '</div>'; // end summary-cards

            // ============================================================
            // TABEL DATA
            // ============================================================
            echo '<div class="table-header-row">';
            echo '<div class="table-info">📊 Total Data: <span style="color:#1a5276;">' . $total_rows . '</span> pasien '
               . '| ✅ Disetujui ≥ Obat: <span style="color:#27ae60;">' . $cnt_untung . '</span> '
               . '| ❌ Obat > Disetujui: <span style="color:#e74c3c;">' . $cnt_rugi . '</span>'
               . '</div>';
            echo '<button onclick="copyTableData()" class="btn btn-success" id="btn-copy">📋 Copy Tabel</button>';
            echo '</div>';

            echo "<div class='table-responsive'><table>
                <thead>
                <tr>
                    <th style='width:35px;'>No</th>
                    <th>Tgl Registrasi</th>
                    <th>No. Rawat</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Poliklinik</th>
                    <th>Dokter</th>
                    <th>Biaya Obat</th>
                    <th>Retur Obat</th>
                    <th>Biaya Bersih Obat</th>
                    <th>Disetujui BPJS</th>
                    <th>Selisih</th>
                </tr>
                </thead>
                <tbody>";

            $no = 1;

            foreach ($data_pasien as $row) {
                $nr = $row['no_rawat'];

                $val_obat   = isset($biaya_obat[$nr])   ? $biaya_obat[$nr]   : 0;
                $val_retur  = isset($retur_obat[$nr])   ? $retur_obat[$nr]   : 0;
                // Retur sudah negatif; biaya bersih = biaya obat + retur (nilai minus)
                $val_bersih = $val_obat + $val_retur;
                $val_dis    = isset($disetujui_data[$nr]) ? $disetujui_data[$nr] : 0;

                // Selisih = disetujui - obat bersih
                // Positif  → disetujui lebih besar  → BPJS cover lebih, HIJAU
                // Negatif  → obat bersih lebih besar → obat melebihi klaim, MERAH
                $val_selisih = $val_dis - $val_bersih;

                if ($val_selisih > 0) {
                    $selisih_html = "<span class='selisih-positif'>+" . number_format($val_selisih, 0, ',', '.') . "</span>";
                } elseif ($val_selisih < 0) {
                    $selisih_html = "<span class='selisih-negatif'>" . number_format($val_selisih, 0, ',', '.') . "</span>";
                } else {
                    $selisih_html = "<span class='selisih-netral'>0</span>";
                }

                echo "<tr>
                    <td class='center'>{$no}</td>
                    <td class='center'>" . date('d-m-Y', strtotime($row['tgl_registrasi'])) . "</td>
                    <td>{$row['no_rawat']}</td>
                    <td>{$row['no_rkm_medis']}</td>
                    <td>{$row['nm_pasien']}</td>
                    <td>{$row['nm_poli']}</td>
                    <td>{$row['nm_dokter']}</td>
                    <td class='angka'>" . number_format($val_obat, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($val_retur, 0, ',', '.') . "</td>
                    <td class='angka' style='font-weight:bold;'>" . number_format($val_bersih, 0, ',', '.') . "</td>
                    <td class='angka' style='font-weight:bold;'>" . number_format($val_dis, 0, ',', '.') . "</td>
                    <td class='center'>{$selisih_html}</td>
                </tr>";
                $no++;
            }

            // GRAND TOTAL ROW
            $grand_selisih_row = $grand_disetujui - $grand_bersih_obat;
            if ($grand_selisih_row > 0) {
                $grand_selisih_html = "<span class='selisih-positif'>+" . number_format($grand_selisih_row, 0, ',', '.') . "</span>";
            } elseif ($grand_selisih_row < 0) {
                $grand_selisih_html = "<span class='selisih-negatif'>" . number_format($grand_selisih_row, 0, ',', '.') . "</span>";
            } else {
                $grand_selisih_html = "<span class='selisih-netral'>0</span>";
            }

            echo "<tr class='total-row'>
                <td colspan='7' style='text-align:right;font-weight:bold;'>💰 GRAND TOTAL</td>
                <td class='angka'>" . number_format($grand_biaya_obat, 0, ',', '.') . "</td>
                <td class='angka'>" . number_format($grand_retur_obat, 0, ',', '.') . "</td>
                <td class='angka' style='font-size:12px;'>" . number_format($grand_bersih_obat, 0, ',', '.') . "</td>
                <td class='angka' style='font-size:12px;'>" . number_format($grand_disetujui, 0, ',', '.') . "</td>
                <td class='center'>{$grand_selisih_html}</td>
            </tr>";

            echo "</tbody></table></div>";

        } else {
            echo '<div class="no-data">💊 Tidak ada data obat pasien rawat jalan pada rentang tanggal yang dipilih.<br><small>Coba ubah filter atau periksa data di sistem.</small></div>';
        }
        mysqli_close($koneksi);
    }
}
?>

        </div><!-- end .content -->
    </div><!-- end .container -->
</body>
</html>
