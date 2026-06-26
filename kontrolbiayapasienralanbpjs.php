<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Biaya Pasien Rawat Jalan BPJS - RSUD Pringsewu</title>
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
            background: linear-gradient(45deg, #1e3a5f, #2980b9);
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
            overflow: auto;
            max-height: 70vh;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 1800px;
        }
        th {
            background: linear-gradient(45deg, #1e3a5f, #2c5282);
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            white-space: nowrap;
            border: 1px solid #1a3050;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        td {
            padding: 8px 6px;
            border: 1px solid #dee2e6;
            font-size: 11px;
            vertical-align: middle;
        }
        td.angka {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
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
        .total-row td {
            background: #d1ecf1 !important;
            font-weight: bold;
            color: #0c5460;
            border-top: 3px solid #1e3a5f;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .summary-card .label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
        }
        .negative { color: #dc3545 !important; }
        .positive { color: #28a745 !important; }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .header {
                padding: 20px 15px;
            }
            .header h1 {
                font-size: 1.4em;
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
                padding: 6px 4px;
                font-size: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.2em;
            }
            .filter-title {
                font-size: 16px;
            }
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
            document.getElementById('tanggal_awal').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('tanggal_akhir').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('kd_poli').value = 'All';
            document.getElementById('kd_dokter').value = 'All';
            document.getElementById('kd_pj').value = 'All';
        }

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Evaluasi Biaya Pasien Rawat Jalan BPJS</h1>
        </div>
        
        <div class="content">
            <div class="back-button">
                <a href="casemix.php">← Kembali ke Menu Casemix</a>
            </div>

    <?php
    include 'koneksi.php';

    // Default value
    $tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : date('Y-m-d');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-d');
    $kd_poli = isset($_POST['kd_poli']) ? $_POST['kd_poli'] : 'All';
    $kd_dokter = isset($_POST['kd_dokter']) ? $_POST['kd_dokter'] : 'All';
    $kd_pj = isset($_POST['kd_pj']) ? $_POST['kd_pj'] : 'All';
    ?>


            <form method="POST" class="filter-form">
                <div class="filter-title">
                    🔍 Filter Evaluasi Biaya Pasien Rawat Jalan
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
                            <option value="All" <?php echo $kd_poli == 'All' ? 'selected' : ''; ?>>- Semua Poliklinik -</option>
                            <?php
                            $q_poli_opt = mysqli_query($koneksi, "SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli");
                            while ($r_poli = mysqli_fetch_assoc($q_poli_opt)) {
                                $selected = $kd_poli == $r_poli['kd_poli'] ? 'selected' : '';
                                echo "<option value='{$r_poli['kd_poli']}' {$selected}>{$r_poli['nm_poli']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kd_dokter">👨‍⚕️ Dokter</label>
                        <select id="kd_dokter" name="kd_dokter">
                            <option value="All" <?php echo $kd_dokter == 'All' ? 'selected' : ''; ?>>- Semua Dokter -</option>
                            <?php
                            $q_doc_opt = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
                            while ($r_doc = mysqli_fetch_assoc($q_doc_opt)) {
                                $selected = $kd_dokter == $r_doc['kd_dokter'] ? 'selected' : '';
                                echo "<option value='{$r_doc['kd_dokter']}' {$selected}>{$r_doc['nm_dokter']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kd_pj">💳 Penjamin (Penjab)</label>
                        <select id="kd_pj" name="kd_pj">
                            <option value="All" <?php echo $kd_pj == 'All' ? 'selected' : ''; ?>>- Semua Penjamin -</option>
                            <?php
                            $q_pj_opt = mysqli_query($koneksi, "SELECT kd_pj, png_jawab FROM penjab ORDER BY png_jawab");
                            while ($r_pj = mysqli_fetch_assoc($q_pj_opt)) {
                                $selected = $kd_pj == $r_pj['kd_pj'] ? 'selected' : '';
                                echo "<option value='{$r_pj['kd_pj']}' {$selected}>{$r_pj['png_jawab']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" name="filter" class="btn btn-primary">
                        📊 Tampilkan Laporan
                    </button>
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                </div>
            </form>

    <?php
    if (isset($_POST['filter'])) {
        $tanggal_awal = $_POST['tanggal_awal'];
        $tanggal_akhir = $_POST['tanggal_akhir'];
        $kd_poli = isset($_POST['kd_poli']) ? $_POST['kd_poli'] : 'All';
        $kd_dokter = isset($_POST['kd_dokter']) ? $_POST['kd_dokter'] : 'All';
        $kd_pj = isset($_POST['kd_pj']) ? $_POST['kd_pj'] : 'All';

        // Build dynamic WHERE clause
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
        $where_sql = implode(" AND ", $where_clauses);

        // ============================================================
        // QUERY 1: Data utama pasien rawat jalan
        // ============================================================
        $query_utama = "SELECT
            reg_periksa.tgl_registrasi,
            reg_periksa.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            poliklinik.nm_poli,
            dokter.nm_dokter,
            penjab.png_jawab,
            reg_periksa.biaya_reg
        FROM
            reg_periksa
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
            INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
            INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
        WHERE
            $where_sql
        ORDER BY reg_periksa.no_rawat";

        $result_utama = mysqli_query($koneksi, $query_utama);

        if (!$result_utama) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;">';
            echo "❌ Error query utama: " . mysqli_error($koneksi);
            echo '</div>';
        } else {
            // Kumpulkan semua no_rawat untuk query batch
            $data_pasien = [];
            $all_no_rawat = [];
            while ($row = mysqli_fetch_assoc($result_utama)) {
                $data_pasien[] = $row;
                $all_no_rawat[] = $row['no_rawat'];
            }
            $total_rows = count($data_pasien);

            if ($total_rows > 0) {
                // Buat IN clause untuk query batch
                $in_clause = "'" . implode("','", array_map(function($nr) use ($koneksi) {
                    return mysqli_real_escape_string($koneksi, $nr);
                }, $all_no_rawat)) . "'";

                // ============================================================
                // QUERY 2: Tindakan (rawat_jl_dr, rawat_jl_pr, rawat_jl_drpr,
                //          rawat_inap_dr, rawat_inap_pr, rawat_inap_drpr)
                // ============================================================
                $tindakan = [];
                $q_tindakan = "SELECT
                    rp.no_rawat,
                    COALESCE(rjd.total_biaya, 0) AS rawat_jl_dr,
                    COALESCE(rjp.total_biaya, 0) AS rawat_jl_pr,
                    COALESCE(rjdp.total_biaya, 0) AS rawat_jl_drpr,
                    COALESCE(rid.total_biaya, 0) AS rawat_inap_dr,
                    COALESCE(rip.total_biaya, 0) AS rawat_inap_pr,
                    COALESCE(ridp.total_biaya, 0) AS rawat_inap_drpr
                FROM reg_periksa rp
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_jl_dr GROUP BY no_rawat
                ) rjd ON rjd.no_rawat = rp.no_rawat
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_jl_pr GROUP BY no_rawat
                ) rjp ON rjp.no_rawat = rp.no_rawat
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_jl_drpr GROUP BY no_rawat
                ) rjdp ON rjdp.no_rawat = rp.no_rawat
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_inap_dr GROUP BY no_rawat
                ) rid ON rid.no_rawat = rp.no_rawat
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_inap_pr GROUP BY no_rawat
                ) rip ON rip.no_rawat = rp.no_rawat
                LEFT JOIN (
                    SELECT no_rawat, SUM(biaya_rawat) total_biaya FROM rawat_inap_drpr GROUP BY no_rawat
                ) ridp ON ridp.no_rawat = rp.no_rawat
                WHERE rp.no_rawat IN ($in_clause)
                ORDER BY rp.no_rawat";
                $res_tindakan = mysqli_query($koneksi, $q_tindakan);
                if ($res_tindakan) {
                    while ($r = mysqli_fetch_assoc($res_tindakan)) {
                        $total_tindakan = $r['rawat_jl_dr'] + $r['rawat_jl_pr'] + $r['rawat_jl_drpr']
                                        + $r['rawat_inap_dr'] + $r['rawat_inap_pr'] + $r['rawat_inap_drpr'];
                        $tindakan[$r['no_rawat']] = $total_tindakan;
                    }
                }

                // ============================================================
                // QUERY 3: Obat (detail_pemberian_obat)
                // ============================================================
                $obat = [];
                $q_obat = "SELECT
                    detail_pemberian_obat.no_rawat,
                    SUM(detail_pemberian_obat.total) AS total_obat
                FROM detail_pemberian_obat
                WHERE detail_pemberian_obat.no_rawat IN ($in_clause)
                GROUP BY detail_pemberian_obat.no_rawat";
                $res_obat = mysqli_query($koneksi, $q_obat);
                if ($res_obat) {
                    while ($r = mysqli_fetch_assoc($res_obat)) {
                        $obat[$r['no_rawat']] = $r['total_obat'];
                    }
                }

                // ============================================================
                // QUERY 4a: Lab (periksa_lab.biaya)
                // ============================================================
                $lab_biaya = [];
                $q_lab1 = "SELECT
                    reg_periksa.no_rawat,
                    SUM(periksa_lab.biaya) AS total_lab
                FROM reg_periksa
                INNER JOIN periksa_lab ON periksa_lab.no_rawat = reg_periksa.no_rawat
                WHERE reg_periksa.no_rawat IN ($in_clause)
                GROUP BY reg_periksa.no_rawat";
                $res_lab1 = mysqli_query($koneksi, $q_lab1);
                if ($res_lab1) {
                    while ($r = mysqli_fetch_assoc($res_lab1)) {
                        $lab_biaya[$r['no_rawat']] = $r['total_lab'];
                    }
                }

                // ============================================================
                // QUERY 4b: Lab detail (detail_periksa_lab.biaya_item)
                // ============================================================
                $lab_detail = [];
                $q_lab2 = "SELECT
                    reg_periksa.no_rawat,
                    SUM(detail_periksa_lab.biaya_item) AS total_lab_detail
                FROM reg_periksa
                INNER JOIN detail_periksa_lab ON detail_periksa_lab.no_rawat = reg_periksa.no_rawat
                WHERE reg_periksa.no_rawat IN ($in_clause)
                GROUP BY reg_periksa.no_rawat";
                $res_lab2 = mysqli_query($koneksi, $q_lab2);
                if ($res_lab2) {
                    while ($r = mysqli_fetch_assoc($res_lab2)) {
                        $lab_detail[$r['no_rawat']] = $r['total_lab_detail'];
                    }
                }

                // ============================================================
                // QUERY 5: Radiologi (periksa_radiologi.biaya)
                // ============================================================
                $radiologi = [];
                $q_rad = "SELECT
                    reg_periksa.no_rawat,
                    SUM(periksa_radiologi.biaya) AS total_radiologi
                FROM reg_periksa
                INNER JOIN periksa_radiologi ON periksa_radiologi.no_rawat = reg_periksa.no_rawat
                WHERE reg_periksa.no_rawat IN ($in_clause)
                GROUP BY reg_periksa.no_rawat";
                $res_rad = mysqli_query($koneksi, $q_rad);
                if ($res_rad) {
                    while ($r = mysqli_fetch_assoc($res_rad)) {
                        $radiologi[$r['no_rawat']] = $r['total_radiologi'];
                    }
                }

                // ============================================================
                // QUERY 6: Kamar (Ditiadakan untuk Rawat Jalan)
                // ============================================================

                // ============================================================
                // QUERY 7: Operasi
                // ============================================================
                $operasi = [];
                $q_operasi = "SELECT
                    reg_periksa.no_rawat,
                    SUM(
                        COALESCE(operasi.biayaoperator1,0) +
                        COALESCE(operasi.biayaoperator2,0) +
                        COALESCE(operasi.biayaoperator3,0) +
                        COALESCE(operasi.biayaasisten_operator1,0) +
                        COALESCE(operasi.biayaasisten_operator2,0) +
                        COALESCE(operasi.biayaasisten_operator3,0) +
                        COALESCE(operasi.biayainstrumen,0) +
                        COALESCE(operasi.biayadokter_anak,0) +
                        COALESCE(operasi.biayaperawaat_resusitas,0) +
                        COALESCE(operasi.biayadokter_anestesi,0) +
                        COALESCE(operasi.biayaasisten_anestesi,0) +
                        COALESCE(operasi.biayaasisten_anestesi2,0) +
                        COALESCE(operasi.biayabidan,0) +
                        COALESCE(operasi.biayabidan2,0) +
                        COALESCE(operasi.biayabidan3,0) +
                        COALESCE(operasi.biayaperawat_luar,0) +
                        COALESCE(operasi.biayaalat,0) +
                        COALESCE(operasi.biayasewaok,0) +
                        COALESCE(operasi.akomodasi,0) +
                        COALESCE(operasi.bagian_rs,0) +
                        COALESCE(operasi.biaya_omloop,0) +
                        COALESCE(operasi.biaya_omloop2,0) +
                        COALESCE(operasi.biaya_omloop3,0) +
                        COALESCE(operasi.biaya_omloop4,0) +
                        COALESCE(operasi.biaya_omloop5,0) +
                        COALESCE(operasi.biayasarpras,0) +
                        COALESCE(operasi.biaya_dokter_pjanak,0) +
                        COALESCE(operasi.biaya_dokter_umum,0)
                    ) AS total_operasi
                FROM reg_periksa
                INNER JOIN operasi ON operasi.no_rawat = reg_periksa.no_rawat
                WHERE reg_periksa.no_rawat IN ($in_clause)
                GROUP BY reg_periksa.no_rawat";
                $res_operasi = mysqli_query($koneksi, $q_operasi);
                if ($res_operasi) {
                    while ($r = mysqli_fetch_assoc($res_operasi)) {
                        $operasi[$r['no_rawat']] = $r['total_operasi'];
                    }
                }

                // ============================================================
                // QUERY 8: Diagnosa (diagnosa_pasien.kd_penyakit)
                // ============================================================
                $diagnosa = [];
                $q_diagnosa = "SELECT
                    diagnosa_pasien.no_rawat,
                    GROUP_CONCAT(diagnosa_pasien.kd_penyakit ORDER BY diagnosa_pasien.prioritas ASC SEPARATOR ';') AS kd_penyakit_list
                FROM diagnosa_pasien
                WHERE diagnosa_pasien.no_rawat IN ($in_clause)
                GROUP BY diagnosa_pasien.no_rawat";
                $res_diagnosa = mysqli_query($koneksi, $q_diagnosa);
                if ($res_diagnosa) {
                    while ($r = mysqli_fetch_assoc($res_diagnosa)) {
                        $diagnosa[$r['no_rawat']] = $r['kd_penyakit_list'];
                    }
                }

                // ============================================================
                // QUERY 9: Prosedur (prosedur_pasien.kode)
                // ============================================================
                $prosedur = [];
                $q_prosedur = "SELECT
                    prosedur_pasien.no_rawat,
                    GROUP_CONCAT(prosedur_pasien.kode ORDER BY prosedur_pasien.prioritas ASC SEPARATOR ';') AS kode_list
                FROM prosedur_pasien
                WHERE prosedur_pasien.no_rawat IN ($in_clause)
                GROUP BY prosedur_pasien.no_rawat";
                $res_prosedur = mysqli_query($koneksi, $q_prosedur);
                if ($res_prosedur) {
                    while ($r = mysqli_fetch_assoc($res_prosedur)) {
                        $prosedur[$r['no_rawat']] = $r['kode_list'];
                    }
                }

                // ============================================================
                // QUERY 10: rspsw_umbal (no_sep & disetujui)
                // ============================================================
                $umbal = [];
                $q_umbal = "SELECT
                    no_rawat,
                    no_sep,
                    COALESCE(disetujui, 0) AS disetujui
                FROM rspsw_umbal
                WHERE no_rawat IN ($in_clause)";
                $res_umbal = mysqli_query($koneksi, $q_umbal);
                if ($res_umbal) {
                    while ($r = mysqli_fetch_assoc($res_umbal)) {
                        $umbal[$r['no_rawat']] = [
                            'no_sep' => $r['no_sep'],
                            'disetujui' => floatval($r['disetujui'])
                        ];
                    }
                }

                // ============================================================
                // TAMPILKAN TABEL
                // ============================================================
                echo '<div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">';
                echo '<div style="font-weight: bold; color: #495057;">📊 Total Data: <span style="color: #007bff;">' . $total_rows . '</span> pasien rawat jalan</div>';
                echo '<button onclick="copyTableData()" class="btn btn-success">📋 Copy Tabel</button>';
                echo '</div>';

                echo "<div class='table-responsive'><table>
                    <tr>
                        <th>No</th>
                        <th>No. Rawat</th>
                        <th>No. RM</th>
                        <th>Nama Pasien</th>
                        <th>Poliklinik</th>
                        <th>Dokter</th>
                        <th>Penjamin</th>
                        <th>Biaya Reg</th>
                        <th>Tindakan</th>
                        <th>Obat</th>
                        <th>Laboratorium</th>
                        <th>Radiologi</th>
                        <th>Potongan</th>
                        <th>Tambahan</th>
                        <th>Operasi</th>
                        <th>Diagnosa</th>
                        <th>Prosedur</th>
                        <th>Total</th>
                        <th>No. SEP</th>
                        <th>Disetujui</th>
                    </tr>";

                $no = 1;
                $grand_biaya_reg = 0;
                $grand_tindakan = 0;
                $grand_obat = 0;
                $grand_lab = 0;
                $grand_radiologi = 0;
                $grand_potongan = 0;
                $grand_tambahan = 0;
                $grand_operasi = 0;
                $grand_total = 0;
                $grand_disetujui = 0;

                foreach ($data_pasien as $row) {
                    $nr = $row['no_rawat'];

                    $val_biaya_reg = floatval($row['biaya_reg']);
                    $val_tindakan  = isset($tindakan[$nr]) ? floatval($tindakan[$nr]) : 0;
                    $val_obat      = isset($obat[$nr]) ? floatval($obat[$nr]) : 0;
                    $val_lab       = (isset($lab_biaya[$nr]) ? floatval($lab_biaya[$nr]) : 0)
                                   + (isset($lab_detail[$nr]) ? floatval($lab_detail[$nr]) : 0);
                    $val_radiologi = isset($radiologi[$nr]) ? floatval($radiologi[$nr]) : 0;
                    $val_potongan  = 0; // Menyusul
                    $val_tambahan  = 0; // Menyusul
                    $val_operasi   = isset($operasi[$nr]) ? floatval($operasi[$nr]) : 0;

                    $val_total = $val_biaya_reg + $val_tindakan + $val_obat + $val_lab
                               + $val_radiologi - $val_potongan + $val_tambahan
                               + $val_operasi;

                    $val_diagnosa  = isset($diagnosa[$nr]) ? $diagnosa[$nr] : '-';
                    $val_prosedur  = isset($prosedur[$nr]) ? $prosedur[$nr] : '-';
                    
                    $val_sep = isset($umbal[$nr]) ? $umbal[$nr]['no_sep'] : '-';
                    $val_disetujui = isset($umbal[$nr]) ? floatval($umbal[$nr]['disetujui']) : 0;

                    // Set warna kolom disetujui berdasarkan perbandingannya dengan total
                    $color_disetujui = '';
                    if ($val_disetujui < $val_total) {
                        $color_disetujui = 'color: #dc3545; font-weight: bold;';
                    } elseif ($val_disetujui > $val_total) {
                        $color_disetujui = 'color: #28a745; font-weight: bold;';
                    }

                    // Akumulasi grand total
                    $grand_biaya_reg += $val_biaya_reg;
                    $grand_tindakan  += $val_tindakan;
                    $grand_obat      += $val_obat;
                    $grand_lab       += $val_lab;
                    $grand_radiologi += $val_radiologi;
                    $grand_potongan  += $val_potongan;
                    $grand_tambahan  += $val_tambahan;
                    $grand_operasi   += $val_operasi;
                    $grand_total     += $val_total;
                    $grand_disetujui += $val_disetujui;

                    echo "<tr>
                        <td style='text-align:center;'>{$no}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['no_rkm_medis']}</td>
                        <td>{$row['nm_pasien']}</td>
                        <td>{$row['nm_poli']}</td>
                        <td>{$row['nm_dokter']}</td>
                        <td>{$row['png_jawab']}</td>
                        <td class='angka'>" . number_format($val_biaya_reg, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_tindakan, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_obat, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_lab, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_radiologi, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_potongan, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_tambahan, 0, ',', '.') . "</td>
                        <td class='angka'>" . number_format($val_operasi, 0, ',', '.') . "</td>
                        <td style='text-align:center;'>{$val_diagnosa}</td>
                        <td style='text-align:center;'>{$val_prosedur}</td>
                        <td class='angka' style='font-weight:bold;'>" . number_format($val_total, 0, ',', '.') . "</td>
                        <td>{$val_sep}</td>
                        <td class='angka' style='{$color_disetujui}'>" . number_format($val_disetujui, 0, ',', '.') . "</td>
                    </tr>";
                    $no++;
                }

                // Grand total row
                echo "<tr class='total-row'>
                    <td colspan='7' style='text-align:right;font-weight:bold;'>💰 GRAND TOTAL</td>
                    <td class='angka'>" . number_format($grand_biaya_reg, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_tindakan, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_obat, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_lab, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_radiologi, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_potongan, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_tambahan, 0, ',', '.') . "</td>
                    <td class='angka'>" . number_format($grand_operasi, 0, ',', '.') . "</td>
                    <td></td>
                    <td></td>
                    <td class='angka' style='font-weight:bold;font-size:13px;'>" . number_format($grand_total, 0, ',', '.') . "</td>
                    <td></td>
                    <td class='angka'>" . number_format($grand_disetujui, 0, ',', '.') . "</td>
                </tr>";

                echo "</table></div>";

            } else {
                echo '<div class="no-data">🏥 Tidak ada data pasien rawat jalan pada rentang tanggal yang dipilih</div>';
            }
        }
        mysqli_close($koneksi);
    }
    ?>
    
        </div> <!-- Tutup content -->
    </div> <!-- Tutup container -->
</body>
</html>
