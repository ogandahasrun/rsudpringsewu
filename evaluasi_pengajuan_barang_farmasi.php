<?php
include 'koneksi.php';

// Ambil semua no_pengajuan untuk dropdown
$query_pengajuan = "SELECT no_pengajuan, tanggal FROM pengajuan_barang_medis ORDER BY tanggal DESC, no_pengajuan DESC";
$result_pengajuan = mysqli_query($koneksi, $query_pengajuan);

// Ambil parameter filter
$no_pengajuan   = isset($_POST['no_pengajuan'])   ? mysqli_real_escape_string($koneksi, $_POST['no_pengajuan'])   : '';
$jenis_tanggal  = isset($_POST['jenis_tanggal'])  ? $_POST['jenis_tanggal']  : 'tgl_pesan';
$tgl_awal       = isset($_POST['tgl_awal'])       && !empty($_POST['tgl_awal'])       ? $_POST['tgl_awal']       : date('Y-m-01');
$tgl_akhir      = isset($_POST['tgl_akhir'])      && !empty($_POST['tgl_akhir'])      ? $_POST['tgl_akhir']      : date('Y-m-d');
$show_data      = isset($_POST['submit']) && !empty($no_pengajuan);

// --- Query Data Pengajuan ---
$data_pengajuan = [];
$rows_gabungan  = [];

if ($show_data) {
    $query_peng = "SELECT
        detail_pengajuan_barang_medis.kode_brng,
        databarang.nama_brng,
        detail_pengajuan_barang_medis.kode_sat,
        detail_pengajuan_barang_medis.jumlah
    FROM pengajuan_barang_medis
    INNER JOIN detail_pengajuan_barang_medis ON detail_pengajuan_barang_medis.no_pengajuan = pengajuan_barang_medis.no_pengajuan
    INNER JOIN databarang ON detail_pengajuan_barang_medis.kode_brng = databarang.kode_brng
    WHERE pengajuan_barang_medis.no_pengajuan = '$no_pengajuan'";

    $result_peng = mysqli_query($koneksi, $query_peng);
    if ($result_peng) {
        while ($row = mysqli_fetch_assoc($result_peng)) {
            $data_pengajuan[$row['kode_brng']] = [
                'nama_brng'     => $row['nama_brng'],
                'kode_sat'      => $row['kode_sat'],
                'jml_pengajuan' => $row['jumlah'],
            ];
        }
    }

    // --- Query Data Pemesanan (datang) ---
    $kolom_tgl = ($jenis_tanggal === 'tgl_faktur') ? 'tgl_faktur' : 'tgl_pesan';

    $query_pesan = "SELECT
        detailpesan.kode_brng,
        databarang.nama_brng,
        detailpesan.kode_sat,
        SUM(detailpesan.jumlah) AS jumlah
    FROM pemesanan
    INNER JOIN detailpesan ON detailpesan.no_faktur = pemesanan.no_faktur
    INNER JOIN databarang ON detailpesan.kode_brng = databarang.kode_brng
    WHERE pemesanan.$kolom_tgl BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY detailpesan.kode_brng, databarang.nama_brng, detailpesan.kode_sat";

    $result_pesan = mysqli_query($koneksi, $query_pesan);
    $data_pesan   = [];
    if ($result_pesan) {
        while ($row = mysqli_fetch_assoc($result_pesan)) {
            $kode = $row['kode_brng'];
            if (!isset($data_pesan[$kode])) {
                $data_pesan[$kode] = [
                    'nama_brng'  => $row['nama_brng'],
                    'kode_sat'   => $row['kode_sat'],
                    'jml_datang' => 0,
                ];
            }
            $data_pesan[$kode]['jml_datang'] += $row['jumlah'];
        }
    }

    // --- Gabungkan: FULL OUTER JOIN manual ---
    $semua_kode = array_unique(array_merge(array_keys($data_pengajuan), array_keys($data_pesan)));

    foreach ($semua_kode as $kode) {
        $nama_brng     = '';
        $kode_sat      = '';
        $jml_pengajuan = 0;
        $jml_datang    = 0;

        if (isset($data_pengajuan[$kode])) {
            $nama_brng     = $data_pengajuan[$kode]['nama_brng'];
            $kode_sat      = $data_pengajuan[$kode]['kode_sat'];
            $jml_pengajuan = $data_pengajuan[$kode]['jml_pengajuan'];
        }
        if (isset($data_pesan[$kode])) {
            if (empty($nama_brng)) $nama_brng = $data_pesan[$kode]['nama_brng'];
            if (empty($kode_sat))  $kode_sat  = $data_pesan[$kode]['kode_sat'];
            $jml_datang = $data_pesan[$kode]['jml_datang'];
        }

        $selisih_lebih  = ($jml_datang > $jml_pengajuan)  ? ($jml_datang - $jml_pengajuan)  : 0;
        $selisih_kurang = ($jml_pengajuan > $jml_datang)  ? ($jml_pengajuan - $jml_datang)  : 0;

        $rows_gabungan[] = [
            'kode_brng'      => $kode,
            'nama_brng'      => $nama_brng,
            'kode_sat'       => $kode_sat,
            'jml_pengajuan'  => $jml_pengajuan,
            'jml_datang'     => $jml_datang,
            'selisih_lebih'  => $selisih_lebih,
            'selisih_kurang' => $selisih_kurang,
        ];
    }

    usort($rows_gabungan, function($a, $b) {
        return strcmp($a['nama_brng'], $b['nama_brng']);
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Pengajuan Barang Farmasi</title>
    <meta name="description" content="Halaman evaluasi dan perbandingan pengajuan barang farmasi dengan barang yang datang dari purchasing RSUD Pringsewu">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:        #0f766e;
            --primary-light:  #14b8a6;
            --primary-dark:   #0d5c55;
            --secondary:      #3b82f6;
            --accent:         #f59e0b;
            --danger:         #ef4444;
            --success:        #22c55e;
            --warning:        #f97316;
            --bg:             #f0fdfa;
            --card-bg:        #ffffff;
            --text:           #1e293b;
            --text-muted:     #64748b;
            --border:         #e2e8f0;
            --shadow:         0 4px 24px rgba(15, 118, 110, 0.10);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e6f7f5 0%, #f0f9ff 60%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--text);
            padding-bottom: 60px;
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            padding: 32px 24px 28px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15,118,110,0.3);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -30px;
            width: 250px; height: 250px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .page-header h1 {
            font-size: 1.85rem; font-weight: 700;
            color: #fff; letter-spacing: 0.01em;
            position: relative; z-index: 1;
        }
        .page-header .subtitle {
            font-size: 0.92rem; color: rgba(255,255,255,0.82);
            margin-top: 6px; position: relative; z-index: 1;
        }
        .header-icon {
            font-size: 2.8rem; margin-bottom: 10px;
            display: block; position: relative; z-index: 1;
        }

        /* BACK */
        .back-wrap { max-width: 1300px; margin: 22px auto 0; padding: 0 24px; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 20px; background: var(--card-bg);
            color: var(--primary); border: 1.5px solid var(--primary-light);
            border-radius: 8px; text-decoration: none; font-size: 0.875rem;
            font-weight: 500; transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(15,118,110,0.08);
        }
        .btn-back:hover {
            background: var(--primary); color: #fff;
            transform: translateX(-3px);
            box-shadow: 0 4px 16px rgba(15,118,110,0.25);
        }

        /* CONTAINER */
        .container { max-width: 1300px; margin: 22px auto 0; padding: 0 24px; }

        /* FILTER CARD */
        .filter-card {
            background: var(--card-bg); border-radius: 16px;
            padding: 28px 30px; box-shadow: var(--shadow);
            border: 1px solid var(--border); margin-bottom: 26px;
            animation: slideDown 0.4s ease-out both;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .filter-title {
            font-size: 1rem; font-weight: 600; color: var(--primary);
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 20px; padding-bottom: 12px;
            border-bottom: 2px solid #e6f7f5;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px; align-items: end;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.8rem; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;
        }
        .form-group select,
        .form-group input[type="date"] {
            padding: 10px 14px; border: 1.5px solid var(--border);
            border-radius: 10px; font-size: 0.9rem;
            font-family: 'Inter', sans-serif; color: var(--text);
            background: #f8fafc; transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none; appearance: none;
        }
        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%230f766e' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 38px;
        }
        .form-group select:focus,
        .form-group input[type="date"]:focus {
            outline: none; border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(20,184,166,0.15); background: #fff;
        }

        /* RADIO GROUP */
        .radio-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .radio-label {
            display: flex; align-items: center; gap: 7px; cursor: pointer;
            padding: 9px 16px; border: 1.5px solid var(--border);
            border-radius: 10px; font-size: 0.88rem; font-weight: 500;
            color: var(--text-muted); background: #f8fafc; transition: all 0.2s;
            user-select: none;
        }
        .radio-label input[type="radio"] { accent-color: var(--primary); width: 16px; height: 16px; }
        .radio-label:hover { border-color: var(--primary-light); color: var(--primary); }

        /* BUTTONS */
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .btn {
            padding: 11px 22px; border: none; border-radius: 10px;
            font-size: 0.9rem; font-weight: 600; font-family: 'Inter', sans-serif;
            cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.25s;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; box-shadow: 0 4px 14px rgba(15,118,110,0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,118,110,0.38); }
        .btn-secondary {
            background: var(--card-bg); color: var(--text-muted);
            border: 1.5px solid var(--border);
        }
        .btn-secondary:hover { background: #f1f5f9; color: var(--text); border-color: #cbd5e1; }
        .btn-copy {
            background: linear-gradient(135deg, var(--secondary), #60a5fa);
            color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,0.25);
        }
        .btn-copy:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,130,246,0.35); }

        /* STATS */
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px; margin-bottom: 22px;
            animation: fadeIn 0.5s ease-out 0.1s both;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .stat-card {
            background: var(--card-bg); border-radius: 14px;
            padding: 18px 20px; box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex; flex-direction: column; gap: 4px;
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        }
        .stat-card.teal::before    { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .stat-card.blue::before    { background: linear-gradient(90deg, var(--secondary), #60a5fa); }
        .stat-card.orange::before  { background: linear-gradient(90deg, var(--warning), var(--accent)); }
        .stat-card.red::before     { background: linear-gradient(90deg, var(--danger), #f87171); }
        .stat-card.green::before   { background: linear-gradient(90deg, var(--success), #4ade80); }
        .stat-label {
            font-size: 0.75rem; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;
        }
        .stat-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
        .stat-card.teal .stat-value   { color: var(--primary); }
        .stat-card.blue .stat-value   { color: var(--secondary); }
        .stat-card.orange .stat-value { color: var(--warning); }
        .stat-card.red .stat-value    { color: var(--danger); }
        .stat-card.green .stat-value  { color: var(--success); }

        /* TABLE */
        .table-card {
            background: var(--card-bg); border-radius: 16px;
            box-shadow: var(--shadow); border: 1px solid var(--border);
            overflow: hidden;
            animation: slideUp 0.5s ease-out 0.15s both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .table-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px; border-bottom: 1px solid var(--border);
            flex-wrap: wrap; gap: 12px;
        }
        .table-title {
            font-size: 1rem; font-weight: 600; color: var(--text);
            display: flex; align-items: center; gap: 8px;
        }
        .table-subtitle { font-size: 0.82rem; color: var(--text-muted); margin-top: 2px; }
        .table-scroll { overflow-x: auto; }
        .table-scroll::-webkit-scrollbar { height: 6px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        thead th {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: #fff; padding: 13px 14px;
            font-weight: 600; font-size: 0.8rem;
            text-transform: uppercase; letter-spacing: 0.05em;
            white-space: nowrap; border: none;
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f8fffe; }
        tbody tr:hover { background: #e6f7f5; }
        td { padding: 11px 14px; color: var(--text); vertical-align: middle; }

        .td-num  { text-align: center; color: var(--text-muted); font-size: 0.8rem; }
        .td-code { font-family: monospace; font-size: 0.82rem; color: var(--primary); font-weight: 600; }
        .td-name { font-weight: 500; }
        .td-unit { text-align: center; }
        .td-qty  { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }

        .qty-zero       { color: #94a3b8; font-style: italic; font-weight: 400; }
        .qty-pengajuan  { color: var(--secondary); }
        .qty-datang     { color: var(--primary); }
        .selisih-lebih  { color: var(--warning); }
        .selisih-kurang { color: var(--danger); }

        tbody tr.row-only-diajukan             { background: #fffbeb !important; }
        tbody tr.row-only-diajukan:hover       { background: #fef3c7 !important; }
        tbody tr.row-only-datang               { background: #f0fdf4 !important; }
        tbody tr.row-only-datang:hover         { background: #dcfce7 !important; }

        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 99px;
            font-size: 0.68rem; font-weight: 600; margin-left: 5px; vertical-align: middle;
        }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-info    { background: #dbeafe; color: #1d4ed8; }

        /* LEGEND */
        .legend {
            display: flex; gap: 18px; flex-wrap: wrap;
            padding: 14px 22px; border-top: 1px solid var(--border);
            background: #f8fffe; font-size: 0.78rem; color: var(--text-muted);
        }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }

        /* EMPTY STATE */
        .state-card {
            background: var(--card-bg); border-radius: 16px;
            padding: 60px 30px; text-align: center;
            box-shadow: var(--shadow); border: 1px solid var(--border);
        }
        .state-icon { font-size: 4rem; margin-bottom: 16px; display: block; opacity: 0.45; }
        .state-title { font-size: 1.1rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
        .state-desc { font-size: 0.88rem; color: #94a3b8; }

        /* TOAST */
        #toast {
            position: fixed; bottom: 28px; right: 28px;
            background: var(--success); color: #fff;
            padding: 12px 22px; border-radius: 10px;
            font-size: 0.9rem; font-weight: 600;
            box-shadow: 0 8px 24px rgba(34,197,94,0.35);
            display: none; z-index: 9999;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .page-header h1 { font-size: 1.3rem; }
            .filter-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            table { font-size: 0.78rem; }
            th, td { padding: 8px 9px; }
        }
        @media (max-width: 480px) { .stats-row { grid-template-columns: 1fr; } }

        @media print {
            .page-header, .back-wrap, .filter-card, .table-toolbar .btn-group { display: none; }
            .table-card { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <span class="header-icon">&#x2696;&#xFE0F;</span>
    <h1>Evaluasi Pengajuan Barang Farmasi</h1>
    <p class="subtitle">Perbandingan barang yang diajukan dengan barang yang datang dari Purchasing</p>
</div>

<div class="back-wrap">
    <a href="farmasi.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi
    </a>
</div>

<div class="container">

    <!-- FILTER -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="fas fa-filter"></i> Filter &amp; Parameter
        </div>
        <form method="POST" action="" id="filterForm">
            <div class="filter-grid">

                <!-- No Pengajuan -->
                <div class="form-group">
                    <label for="no_pengajuan"><i class="fas fa-file-alt"></i> Nomor Pengajuan</label>
                    <select name="no_pengajuan" id="no_pengajuan" required>
                        <option value="">-- Pilih No Pengajuan --</option>
                        <?php
                        if ($result_pengajuan && mysqli_num_rows($result_pengajuan) > 0) {
                            while ($row_p = mysqli_fetch_assoc($result_pengajuan)) {
                                $sel = ($no_pengajuan === $row_p['no_pengajuan']) ? 'selected' : '';
                                $label_tgl = !empty($row_p['tanggal'])
                                    ? ' (' . date('d/m/Y', strtotime($row_p['tanggal'])) . ')' : '';
                                echo "<option value='" . htmlspecialchars($row_p['no_pengajuan']) . "' $sel>"
                                   . htmlspecialchars($row_p['no_pengajuan']) . $label_tgl . "</option>";
                            }
                        } else {
                            echo "<option value=''>Tidak ada data pengajuan</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Jenis Tanggal -->
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Bandingkan Berdasarkan</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="jenis_tanggal" value="tgl_pesan"
                                <?= ($jenis_tanggal === 'tgl_pesan') ? 'checked' : '' ?>>
                            <i class="fas fa-shopping-cart"></i> Tgl. Datang
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="jenis_tanggal" value="tgl_faktur"
                                <?= ($jenis_tanggal === 'tgl_faktur') ? 'checked' : '' ?>>
                            <i class="fas fa-file-invoice"></i> Tgl. Faktur
                        </label>
                    </div>
                </div>

                <!-- Tanggal Awal -->
                <div class="form-group">
                    <label for="tgl_awal"><i class="fas fa-calendar-day"></i> Tanggal Awal</label>
                    <input type="date" name="tgl_awal" id="tgl_awal"
                           value="<?= htmlspecialchars($tgl_awal) ?>" required>
                </div>

                <!-- Tanggal Akhir -->
                <div class="form-group">
                    <label for="tgl_akhir"><i class="fas fa-calendar-day"></i> Tanggal Akhir</label>
                    <input type="date" name="tgl_akhir" id="tgl_akhir"
                           value="<?= htmlspecialchars($tgl_akhir) ?>" required>
                </div>

                <!-- Tombol -->
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="btn-group">
                        <button type="submit" name="submit" value="1" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- RESULT AREA -->
    <?php if ($show_data): ?>

    <?php
    $total_item        = count($rows_gabungan);
    $item_sesuai       = 0;
    $item_lebih        = 0;
    $item_kurang       = 0;
    $item_tdk_datang   = 0;
    $item_tdk_diajukan = 0;
    foreach ($rows_gabungan as $r) {
        if ($r['jml_pengajuan'] == 0 && $r['jml_datang'] > 0)     $item_tdk_diajukan++;
        elseif ($r['jml_datang'] == 0 && $r['jml_pengajuan'] > 0) $item_tdk_datang++;
        elseif ($r['selisih_lebih'] > 0)                           $item_lebih++;
        elseif ($r['selisih_kurang'] > 0)                          $item_kurang++;
        else                                                        $item_sesuai++;
    }
    ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card teal">
            <span class="stat-label">Total Item</span>
            <span class="stat-value"><?= $total_item ?></span>
        </div>
        <div class="stat-card green">
            <span class="stat-label">Sesuai</span>
            <span class="stat-value"><?= $item_sesuai ?></span>
        </div>
        <div class="stat-card orange">
            <span class="stat-label">Selisih Lebih</span>
            <span class="stat-value"><?= $item_lebih ?></span>
        </div>
        <div class="stat-card red">
            <span class="stat-label">Selisih Kurang</span>
            <span class="stat-value"><?= $item_kurang ?></span>
        </div>
        <div class="stat-card blue">
            <span class="stat-label">Tidak Datang</span>
            <span class="stat-value"><?= $item_tdk_datang ?></span>
        </div>
        <div class="stat-card blue">
            <span class="stat-label">Tidak Diajukan</span>
            <span class="stat-value"><?= $item_tdk_diajukan ?></span>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    Hasil Perbandingan &mdash; No. Pengajuan:
                    <strong><?= htmlspecialchars($no_pengajuan) ?></strong>
                </div>
                <div class="table-subtitle">
                    Periode <?= ($jenis_tanggal === 'tgl_faktur') ? 'Tgl. Faktur' : 'Tgl. Datang' ?>:
                    <?= date('d/m/Y', strtotime($tgl_awal)) ?> &ndash;
                    <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
                    &bull; <?= $total_item ?> item
                </div>
            </div>
            <div class="btn-group">
                <button class="btn btn-copy" onclick="copyTable()">
                    <i class="fas fa-copy"></i> Copy ke Clipboard
                </button>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </div>

        <?php if (count($rows_gabungan) > 0): ?>
        <div class="table-scroll">
            <table id="mainTable">
                <thead>
                    <tr>
                        <th style="width:46px; text-align:center;">No</th>
                        <th style="min-width:100px;">Kode Barang</th>
                        <th style="min-width:240px;">Nama Barang</th>
                        <th style="width:70px; text-align:center;">Satuan</th>
                        <th style="width:120px; text-align:right;">Jml Diajukan</th>
                        <th style="width:120px; text-align:right;">Jml Datang</th>
                        <th style="width:120px; text-align:right;">Selisih Lebih</th>
                        <th style="width:120px; text-align:right;">Selisih Kurang</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                foreach ($rows_gabungan as $r):
                    $row_class = '';
                    if ($r['jml_pengajuan'] == 0 && $r['jml_datang'] > 0) {
                        $row_class = 'row-only-datang';
                    } elseif ($r['jml_datang'] == 0 && $r['jml_pengajuan'] > 0) {
                        $row_class = 'row-only-diajukan';
                    }
                    $qty_d_class = ($r['jml_pengajuan'] == 0) ? 'qty-zero'    : 'qty-pengajuan';
                    $qty_p_class = ($r['jml_datang']    == 0) ? 'qty-zero'    : 'qty-datang';
                    $sel_l_class = ($r['selisih_lebih']  > 0) ? 'selisih-lebih'  : 'qty-zero';
                    $sel_k_class = ($r['selisih_kurang'] > 0) ? 'selisih-kurang' : 'qty-zero';
                ?>
                <tr class="<?= $row_class ?>">
                    <td class="td-num"><?= $no++ ?></td>
                    <td class="td-code">
                        <?= htmlspecialchars($r['kode_brng']) ?>
                        <?php if ($r['jml_pengajuan'] == 0 && $r['jml_datang'] > 0): ?>
                            <span class="badge badge-info">Tdk Diajukan</span>
                        <?php elseif ($r['jml_datang'] == 0 && $r['jml_pengajuan'] > 0): ?>
                            <span class="badge badge-warning">Tdk Datang</span>
                        <?php endif; ?>
                    </td>
                    <td class="td-name"><?= htmlspecialchars($r['nama_brng']) ?></td>
                    <td class="td-unit"><?= htmlspecialchars($r['kode_sat']) ?></td>
                    <td class="td-qty <?= $qty_d_class ?>">
                        <?= ($r['jml_pengajuan'] == 0) ? '&mdash;' : number_format($r['jml_pengajuan'], 0, ',', '.') ?>
                    </td>
                    <td class="td-qty <?= $qty_p_class ?>">
                        <?= ($r['jml_datang'] == 0) ? '&mdash;' : number_format($r['jml_datang'], 0, ',', '.') ?>
                    </td>
                    <td class="td-qty <?= $sel_l_class ?>">
                        <?= ($r['selisih_lebih'] == 0) ? '&mdash;' : number_format($r['selisih_lebih'], 0, ',', '.') ?>
                    </td>
                    <td class="td-qty <?= $sel_k_class ?>">
                        <?= ($r['selisih_kurang'] == 0) ? '&mdash;' : number_format($r['selisih_kurang'], 0, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-dot" style="background:#fffbeb; border:1.5px solid #f59e0b;"></div>
                <span>Diajukan tapi tidak datang</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#f0fdf4; border:1.5px solid #22c55e;"></div>
                <span>Datang tapi tidak diajukan</span>
            </div>
            <div class="legend-item">
                <span style="color:var(--warning); font-weight:700;">&#9632;</span>
                <span>Selisih lebih: datang &gt; diajukan</span>
            </div>
            <div class="legend-item">
                <span style="color:var(--danger); font-weight:700;">&#9632;</span>
                <span>Selisih kurang: datang &lt; diajukan</span>
            </div>
        </div>

        <?php else: ?>
        <div style="padding:60px 30px; text-align:center;">
            <span style="font-size:3.5rem; opacity:0.35;">&#128269;</span>
            <div style="font-size:1.05rem; font-weight:600; color:var(--text-muted); margin-top:12px;">Tidak ada data ditemukan</div>
            <div style="font-size:0.875rem; color:#94a3b8; margin-top:6px;">
                Tidak ada barang pada pengajuan <strong><?= htmlspecialchars($no_pengajuan) ?></strong>
                atau tidak ada pemesanan pada periode yang dipilih.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif (isset($_POST['submit'])): ?>
    <div class="state-card">
        <span class="state-icon">&#9888;&#65039;</span>
        <div class="state-title">Nomor pengajuan belum dipilih</div>
        <div class="state-desc">Silakan pilih nomor pengajuan terlebih dahulu.</div>
    </div>

    <?php else: ?>
    <div class="state-card">
        <span class="state-icon">&#128203;</span>
        <div class="state-title">Pilih parameter untuk menampilkan data</div>
        <div class="state-desc">
            Pilih <strong>nomor pengajuan</strong>, <strong>jenis tanggal</strong>, dan <strong>rentang tanggal</strong>,
            kemudian klik tombol <strong>Tampilkan</strong>.
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.container -->

<div id="toast"><i class="fas fa-check-circle"></i> Data berhasil disalin ke clipboard!</div>

<script>
    function resetForm() {
        document.getElementById('no_pengajuan').value = '';
        document.querySelector('input[name="jenis_tanggal"][value="tgl_pesan"]').checked = true;
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm   = String(today.getMonth() + 1).padStart(2, '0');
        var dd   = String(today.getDate()).padStart(2, '0');
        document.getElementById('tgl_akhir').value = yyyy + '-' + mm + '-' + dd;
        document.getElementById('tgl_awal').value  = yyyy + '-' + mm + '-01';
    }

    function copyTable() {
        var table = document.getElementById('mainTable');
        if (!table) { alert('Tidak ada data untuk disalin!'); return; }
        var tsv = '';
        var rows = table.querySelectorAll('tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('th, td');
            var rowData = [];
            cells.forEach(function(cell) {
                var text = cell.innerText.replace(/\s+/g, ' ').trim();
                rowData.push(text);
            });
            tsv += rowData.join('\t') + '\n';
        });
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(tsv).then(showToast).catch(function() { fallbackCopy(tsv); });
        } else {
            fallbackCopy(tsv);
        }
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity  = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); showToast(); }
        catch(e) { alert('Gagal menyalin. Silakan salin manual.'); }
        document.body.removeChild(ta);
    }

    function showToast() {
        var toast = document.getElementById('toast');
        toast.style.display = 'block';
        setTimeout(function() { toast.style.display = 'none'; }, 3000);
    }
</script>
</body>
</html>
