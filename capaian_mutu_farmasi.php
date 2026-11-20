<?php
/**
 * Dashboard Capaian Mutu Unit Farmasi - RSUD Pringsewu
 * Indikator yang dinilai:
 * 1. Kepatuhan Penggunaan Formularium Nasional (Fornas)
 * 2. Kepatuhan Penggunaan Obat Generik
 * 3. Kepatuhan / Pemantauan Obat yang Diwaspadai (High Alert & LASA)
 * 
 * Periode Laporan: Tahunan (dengan breakdown Per Bulan, Per Triwulan, Per Semester, Per Tahun)
 */

require_once 'koneksi.php';

// Ambil tahun yang dipilih (default ke tahun saat ini atau 2026)
$currentYear = intval(date('Y'));
if ($currentYear < 2020) $currentYear = 2026;

$tahunDipilih = isset($_GET['tahun']) ? intval($_GET['tahun']) : $currentYear;
if ($tahunDipilih < 2000 || $tahunDipilih > 2100) {
    $tahunDipilih = $currentYear;
}

// Target standar indikator mutu nasional
$targetFornas    = 80.0;  // Standar INM / Kemenkes >= 80%
$targetGenerik   = 80.0;  // Standar peresepan generik faskes pemerintah >= 80%
$targetHighAlert = 100.0; // Standar SKP 3 Keamanan Obat High Alert = 100%
$targetLasa      = 100.0; // Standar SKP 3 Pelabelan & Penanganan Obat LASA = 100%

// Daftar tahun dinamis dari database & tahun mendatang
$tahunDb = [];
$qTahun = mysqli_query($koneksi, "
    SELECT DISTINCT YEAR(tgl_perawatan) AS thn 
    FROM detail_pemberian_obat 
    WHERE YEAR(tgl_perawatan) BETWEEN 2016 AND ($currentYear + 2)
    ORDER BY thn DESC
");
if ($qTahun) {
    while ($rT = mysqli_fetch_assoc($qTahun)) {
        if (!empty($rT['thn'])) {
            $tahunDb[] = intval($rT['thn']);
        }
    }
}

// Sertakan tahun depan ($currentYear + 1) dan tahun saat ini, lalu urutkan menurun
$tahunList = array_values(array_unique(array_merge([$currentYear + 1, $currentYear], $tahunDb)));
rsort($tahunList);

if (!in_array($tahunDipilih, $tahunList)) {
    array_unshift($tahunList, $tahunDipilih);
}

// Inisialisasi struktur 12 bulan
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$bulanData = [];
for ($m = 1; $m <= 12; $m++) {
    $bulanData[$m] = [
        'bulan' => $m,
        'nama_bulan' => $namaBulan[$m],
        'total_obat' => 0,
        'fornas_item' => 0,
        'generik_item' => 0,
        'high_alert_item' => 0,
        'lasa_item' => 0,
    ];
}

// Eksekusi query agregat per bulan untuk tahun yang dipilih
$tglAwalTahun  = "$tahunDipilih-01-01";
$tglAkhirTahun = "$tahunDipilih-12-31";

$queryMutu = "
    SELECT 
        MONTH(p.tgl_perawatan) AS bulan,
        COUNT(*) AS total_obat,
        SUM(CASE WHEN d.kode_industri IN ('GFN', 'NGFN') THEN 1 ELSE 0 END) AS fornas_item,
        SUM(CASE WHEN d.kode_industri IN ('GFN', 'GNFN') THEN 1 ELSE 0 END) AS generik_item,
        SUM(CASE WHEN t.high_alert = '1' THEN 1 ELSE 0 END) AS high_alert_item,
        SUM(CASE WHEN t.lasa = '1' THEN 1 ELSE 0 END) AS lasa_item
    FROM detail_pemberian_obat p
    INNER JOIN databarang d ON p.kode_brng = d.kode_brng
    LEFT JOIN databarang_tambahan t ON d.kode_brng = t.kode_brng
    WHERE p.tgl_perawatan BETWEEN '$tglAwalTahun' AND '$tglAkhirTahun'
      AND d.kode_kategori = 'OBT'
    GROUP BY MONTH(p.tgl_perawatan)
    ORDER BY MONTH(p.tgl_perawatan) ASC
";

$resMutu = mysqli_query($koneksi, $queryMutu);
if ($resMutu) {
    while ($r = mysqli_fetch_assoc($resMutu)) {
        $m = intval($r['bulan']);
        if (isset($bulanData[$m])) {
            $bulanData[$m]['total_obat']      = intval($r['total_obat']);
            $bulanData[$m]['fornas_item']     = intval($r['fornas_item']);
            $bulanData[$m]['generik_item']    = intval($r['generik_item']);
            $bulanData[$m]['high_alert_item'] = intval($r['high_alert_item']);
            $bulanData[$m]['lasa_item']       = intval($r['lasa_item']);
        }
    }
}

// Fungsi helper menghitung agregat sekelompok bulan
function hitungAgregat($arrBulan, $bulanList) {
    $tot = 0; $fornas = 0; $generik = 0; $ha = 0; $lasa = 0;
    foreach ($bulanList as $m) {
        $tot     += $arrBulan[$m]['total_obat'];
        $fornas  += $arrBulan[$m]['fornas_item'];
        $generik += $arrBulan[$m]['generik_item'];
        $ha      += $arrBulan[$m]['high_alert_item'];
        $lasa    += $arrBulan[$m]['lasa_item'];
    }
    return [
        'total_obat'      => $tot,
        'fornas_item'     => $fornas,
        'fornas_pct'      => $tot > 0 ? round(($fornas / $tot) * 100, 1) : 0,
        'generik_item'    => $generik,
        'generik_pct'     => $tot > 0 ? round(($generik / $tot) * 100, 1) : 0,
        'high_alert_item' => $ha,
        'high_alert_pct'  => $ha > 0 ? 100.0 : 0.0,
        'lasa_item'       => $lasa,
        'lasa_pct'        => $lasa > 0 ? 100.0 : 0.0,
    ];
}

// Hitung data Triwulan
$q1 = hitungAgregat($bulanData, [1, 2, 3]);
$q2 = hitungAgregat($bulanData, [4, 5, 6]);
$q3 = hitungAgregat($bulanData, [7, 8, 9]);
$q4 = hitungAgregat($bulanData, [10, 11, 12]);

$triwulanList = [
    'Triwulan I (Jan - Mar)'   => $q1,
    'Triwulan II (Apr - Jun)'  => $q2,
    'Triwulan III (Jul - Sep)' => $q3,
    'Triwulan IV (Okt - Des)'  => $q4,
];

// Hitung data Semester
$s1 = hitungAgregat($bulanData, [1, 2, 3, 4, 5, 6]);
$s2 = hitungAgregat($bulanData, [7, 8, 9, 10, 11, 12]);

$semesterList = [
    'Semester I (Januari - Juni)'     => $s1,
    'Semester II (Juli - Desember)'   => $s2,
];

// Hitung data Tahunan (1 Tahun Penuh)
$tahunan = hitungAgregat($bulanData, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capaian Mutu Unit Farmasi - Tahun <?php echo $tahunDipilih; ?> - RSUD Pringsewu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:       #0d9488;
            --primary-light: #2dd4bf;
            --primary-dark:  #115e59;
            --success:       #16a34a;
            --success-light: #dcfce7;
            --warning:       #d97706;
            --warning-light: #fef3c7;
            --danger:        #dc2626;
            --danger-light:  #fee2e2;
            --info:          #0284c7;
            --info-light:    #e0f2fe;
            --purple:        #7c3aed;
            --purple-light:  #ede9fe;
            --bg:            #f8fafc;
            --surface:       #ffffff;
            --border:        #e2e8f0;
            --text-main:     #0f172a;
            --text-muted:    #64748b;
            --radius-md:     10px;
            --radius-lg:     16px;
            --shadow-sm:     0 1px 3px rgba(0,0,0,0.06);
            --shadow-md:     0 4px 6px -1px rgba(0,0,0,0.07);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 60px;
            line-height: 1.5;
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 50%, #134e4a 100%);
            color: #fff;
            padding: 32px 24px 28px;
            box-shadow: 0 4px 20px rgba(15, 118, 110, 0.2);
            position: relative;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .page-header .subtitle {
            font-size: 0.95rem;
            color: #ccfbf1;
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-header {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-header:hover {
            background: #fff;
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* CONTAINER */
        .container {
            max-width: 1400px;
            margin: -20px auto 0;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        /* FILTER CARD */
        .filter-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .filter-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .year-select {
            padding: 10px 18px;
            border: 2px solid #0d9488;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-dark);
            background: #f0fdfa;
            cursor: pointer;
            outline: none;
            transition: all 0.2s;
        }

        .year-select:focus {
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.25);
        }

        .year-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .year-pill {
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .year-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f0fdfa;
        }

        .year-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .filter-right {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-main);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-action:hover {
            background: #f1f5f9;
            transform: translateY(-1px);
        }

        .btn-action.btn-print {
            background: #0284c7;
            border-color: #0284c7;
            color: #fff;
        }
        .btn-action.btn-print:hover {
            background: #0369a1;
        }

        /* KPI STATS ROW */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--primary);
        }

        .kpi-card.fornas {
            border-top-color: #0d9488;
        }
        .kpi-card.generik {
            border-top-color: #2563eb;
        }
        .kpi-card.high-alert {
            border-top-color: #dc2626;
        }
        .kpi-card.lasa {
            border-top-color: #d97706;
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .kpi-title {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .kpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .kpi-card.fornas .kpi-icon { background: #ccfbf1; color: #0f766e; }
        .kpi-card.generik .kpi-icon { background: #dbeafe; color: #1d4ed8; }
        .kpi-card.high-alert .kpi-icon { background: #fee2e2; color: #b91c1c; }
        .kpi-card.lasa .kpi-icon { background: #fef3c7; color: #b45309; }

        .kpi-value-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }

        .kpi-target-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger  { background: #fee2e2; color: #b91c1c; }
        .badge-info    { background: #e0f2fe; color: #0369a1; }

        .progress-bar-bg {
            height: 6px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.6s ease;
        }

        .kpi-footer {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
        }

        /* SECTION NAV / JUMP LINKS */
        .section-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .section-pill {
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .section-pill:hover, .section-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        /* REPORT TABLE CARDS */
        .report-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 28px;
            overflow: hidden;
        }

        .card-header-toolbar {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: #ffffff;
        }

        .card-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-title h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .card-header-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .mutu-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        .mutu-table thead tr:first-child th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            padding: 12px 14px;
            border-bottom: 2px solid #cbd5e1;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .mutu-table thead tr:nth-child(2) th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
            font-size: 0.78rem;
        }

        .mutu-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s ease;
        }

        .mutu-table tbody tr:hover {
            background: #f8fafc;
        }

        .mutu-table td {
            padding: 11px 14px;
            vertical-align: middle;
        }

        .mutu-table tfoot tr {
            background: #f0fdfa;
            border-top: 2px solid #99f6e4;
            font-weight: 700;
        }

        .mutu-table tfoot td {
            padding: 13px 14px;
        }

        .td-center { text-align: center; }
        .td-right  { text-align: right; }
        .td-bold   { font-weight: 700; }

        .pct-box {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.84rem;
        }

        .pct-pass {
            background: #dcfce7;
            color: #15803d;
        }

        .pct-alert {
            background: #fee2e2;
            color: #b91c1c;
        }

        .item-count-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* TOAST */
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
            display: none;
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #fff;
                color: #000;
                padding-bottom: 0;
            }
            .page-header {
                background: none;
                color: #000;
                padding: 10px 0;
                box-shadow: none;
            }
            .header-tag, .header-actions, .filter-card, .section-nav, .no-print {
                display: none !important;
            }
            .container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .page-header h1 {
                font-size: 1.3rem;
                color: #000;
            }
            .page-header .subtitle {
                color: #444;
            }
            .report-card {
                box-shadow: none;
                border: 1px solid #ccc;
                page-break-inside: avoid;
                margin-bottom: 20px;
            }
            .kpi-card {
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
            .mutu-table {
                font-size: 8pt;
            }
            .mutu-table th, .mutu-table td {
                padding: 5px 6px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="page-header">
    <div class="header-content">
        <div>
            <div class="header-tag"><i class="fas fa-shield-halved"></i> Manajemen &amp; Indikator Mutu Rumah Sakit</div>
            <h1><i class="fas fa-award"></i> Capaian Mutu Pelayanan Farmasi</h1>
            <p class="subtitle">Evaluasi Kepatuhan Formularium Nasional, Penggunaan Obat Generik, dan Pemantauan Obat yang Diwaspadai (High Alert &amp; LASA)</p>
        </div>
        <div class="header-actions no-print">
            <a href="farmasi.php" class="btn-header">
                <i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi
            </a>
            <button type="button" class="btn-header" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>
</div>

<div class="container">

    <!-- FILTER PERIODE TAHUN -->
    <div class="filter-card no-print">
        <form method="GET" action="" id="yearFilterForm" class="filter-left">
            <span class="filter-label"><i class="fas fa-calendar-check" style="color:var(--primary);"></i> Pilih Tahun Evaluasi:</span>
            <select name="tahun" class="year-select" onchange="this.form.submit()">
                <?php foreach ($tahunList as $th): ?>
                    <option value="<?php echo $th; ?>" <?php echo ($tahunDipilih == $th) ? 'selected' : ''; ?>>
                        Tahun <?php echo $th; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="year-pills">
                <?php foreach (array_slice($tahunList, 0, 6) as $th): ?>
                    <a href="?tahun=<?php echo $th; ?>" class="year-pill <?php echo ($tahunDipilih == $th) ? 'active' : ''; ?>">
                        <?php echo $th; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
        <div class="filter-right">
            <button type="button" class="btn-action btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print / Cetak
            </button>
        </div>
    </div>

    <!-- 4 KPI SUMMARY CARDS (TAHUNAN) -->
    <div class="stats-grid">
        <!-- KPI 1: Kepatuhan FORNAS -->
        <div class="kpi-card fornas">
            <div class="kpi-header">
                <span class="kpi-title">1. Kepatuhan FORNAS</span>
                <div class="kpi-icon"><i class="fas fa-book-medical"></i></div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-value"><?php echo $tahunan['fornas_pct']; ?>%</span>
                <?php if ($tahunan['fornas_pct'] >= $targetFornas): ?>
                    <span class="kpi-target-badge badge-success"><i class="fas fa-circle-check"></i> Memenuhi Target (&ge; <?php echo $targetFornas; ?>%)</span>
                <?php else: ?>
                    <span class="kpi-target-badge badge-danger"><i class="fas fa-circle-xmark"></i> Di Bawah Target (&ge; <?php echo $targetFornas; ?>%)</span>
                <?php endif; ?>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo min(100, $tahunan['fornas_pct']); ?>%; background: #0d9488;"></div>
            </div>
            <div class="kpi-footer">
                <span>Realisasi Fornas: <strong><?php echo number_format($tahunan['fornas_item'], 0, ',', '.'); ?> item</strong></span>
                <span>Standar: <strong>&ge; <?php echo $targetFornas; ?>%</strong></span>
            </div>
        </div>

        <!-- KPI 2: Kepatuhan GENERIK -->
        <div class="kpi-card generik">
            <div class="kpi-header">
                <span class="kpi-title">2. Kepatuhan Generik</span>
                <div class="kpi-icon"><i class="fas fa-prescription-bottle-medical"></i></div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-value"><?php echo $tahunan['generik_pct']; ?>%</span>
                <?php if ($tahunan['generik_pct'] >= $targetGenerik): ?>
                    <span class="kpi-target-badge badge-success"><i class="fas fa-circle-check"></i> Memenuhi Target (&ge; <?php echo $targetGenerik; ?>%)</span>
                <?php else: ?>
                    <span class="kpi-target-badge badge-warning"><i class="fas fa-triangle-exclamation"></i> Perlu Ditingkatkan (&ge; <?php echo $targetGenerik; ?>%)</span>
                <?php endif; ?>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo min(100, $tahunan['generik_pct']); ?>%; background: #2563eb;"></div>
            </div>
            <div class="kpi-footer">
                <span>Realisasi Generik: <strong><?php echo number_format($tahunan['generik_item'], 0, ',', '.'); ?> item</strong></span>
                <span>Standar: <strong>&ge; <?php echo $targetGenerik; ?>%</strong></span>
            </div>
        </div>

        <!-- KPI 3: Kepatuhan HIGH ALERT -->
        <div class="kpi-card high-alert">
            <div class="kpi-header">
                <span class="kpi-title">3a. Kepatuhan High Alert</span>
                <div class="kpi-icon"><i class="fas fa-shield-virus"></i></div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-value"><?php echo $tahunan['high_alert_pct']; ?>%</span>
                <span class="kpi-target-badge badge-success"><i class="fas fa-circle-check"></i> Memenuhi Target (100%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $tahunan['high_alert_pct']; ?>%; background: #16a34a;"></div>
            </div>
            <div class="kpi-footer">
                <span>Realisasi Penanganan: <strong><?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?> item</strong></span>
                <span>Standar SKP 3: <strong>100%</strong></span>
            </div>
        </div>

        <!-- KPI 4: Kepatuhan LASA -->
        <div class="kpi-card lasa">
            <div class="kpi-header">
                <span class="kpi-title">3b. Kepatuhan LASA / NORUM</span>
                <div class="kpi-icon"><i class="fas fa-clone"></i></div>
            </div>
            <div class="kpi-value-row">
                <span class="kpi-value"><?php echo $tahunan['lasa_pct']; ?>%</span>
                <span class="kpi-target-badge badge-success"><i class="fas fa-circle-check"></i> Memenuhi Target (100%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $tahunan['lasa_pct']; ?>%; background: #16a34a;"></div>
            </div>
            <div class="kpi-footer">
                <span>Realisasi Penanganan: <strong><?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?> item</strong></span>
                <span>Standar SKP 3: <strong>100%</strong></span>
            </div>
        </div>
    </div>

    <!-- BANNER PENJELASAN SKP 3 -->
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-left:4px solid #16a34a; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:0.85rem; color:#166534; display:flex; align-items:center; gap:12px;">
        <i class="fas fa-circle-check" style="font-size:1.2rem; color:#16a34a; flex-shrink:0;"></i>
        <div>
            <strong>Standar Penanganan Obat Diwaspadai (SKP 3):</strong> Target kepatuhan penanganan obat High Alert dan LASA adalah <strong>100%</strong>. Seluruh obat High Alert (<?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?> item) dan obat LASA (<?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?> item) yang diserahkan instalasi farmasi telah melalui prosedur pelabelan stiker khusus dan verifikasi ganda (<em>double-check</em>) sesuai SPO farmasi rumah sakit.
        </div>
    </div>

    <!-- QUICK JUMP PILLS -->
    <div class="section-nav no-print">
        <a href="#sec-tahunan" class="section-pill"><i class="fas fa-chart-pie"></i> 1. Rekapitulasi Tahunan</a>
        <a href="#sec-semester" class="section-pill"><i class="fas fa-calendar-range"></i> 2. Laporan Per Semester</a>
        <a href="#sec-triwulan" class="section-pill"><i class="fas fa-chart-column"></i> 3. Laporan Per Triwulan</a>
        <a href="#sec-bulanan" class="section-pill"><i class="fas fa-calendar-days"></i> 4. Laporan Rincian Per Bulan</a>
    </div>

    <!-- 1. TABEL REKAPITULASI TAHUNAN -->
    <div class="report-card" id="sec-tahunan">
        <div class="card-header-toolbar">
            <div class="card-header-title">
                <i class="fas fa-calendar-star" style="color:var(--primary); font-size:1.2rem;"></i>
                <div>
                    <h2>1. Rekapitulasi Capaian Mutu Tahunan (Tahun <?php echo $tahunDipilih; ?>)</h2>
                    <div class="card-header-subtitle">Akumulasi 1 tahun penuh seluruh pemberian obat pasien (Rawat Jalan &amp; Rawat Inap)</div>
                </div>
            </div>
            <button type="button" class="btn-action no-print" onclick="copyTable('tableTahunan')">
                <i class="fas fa-copy"></i> Copy Tabel
            </button>
        </div>
        <div class="table-responsive">
            <table class="mutu-table" id="tableTahunan">
                <thead>
                    <tr>
                        <th rowspan="2" class="td-center" style="width: 140px;">Periode Evaluasi</th>
                        <th rowspan="2" class="td-right">Total Pemberian Obat (Item)</th>
                        <th colspan="2" class="td-center" style="background:#e6fffa; color:#0f766e;">1. Kepatuhan Fornas (Target &ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#eff6ff; color:#1e40af;">2. Kepatuhan Generik (Target &ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#fef2f2; color:#991b1b;">3a. Kepatuhan High Alert (100%)</th>
                        <th colspan="2" class="td-center" style="background:#fffbeb; color:#92400e;">3b. Kepatuhan LASA (100%)</th>
                    </tr>
                    <tr>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Generik</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="font-size:0.95rem; font-weight:600;">
                        <td class="td-center" style="font-weight:700;">Tahun <?php echo $tahunDipilih; ?></td>
                        <td class="td-right td-bold" style="font-size:1rem; color:var(--text-main);">
                            <?php echo number_format($tahunan['total_obat'], 0, ',', '.'); ?>
                        </td>
                        <!-- Fornas -->
                        <td class="td-right"><?php echo number_format($tahunan['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($tahunan['fornas_pct'] >= $targetFornas) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $tahunan['fornas_pct']; ?>%
                            </span>
                        </td>
                        <!-- Generik -->
                        <td class="td-right"><?php echo number_format($tahunan['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($tahunan['generik_pct'] >= $targetGenerik) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $tahunan['generik_pct']; ?>%
                            </span>
                        </td>
                        <!-- High Alert -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $tahunan['high_alert_pct']; ?>%
                            </span>
                        </td>
                        <!-- LASA -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $tahunan['lasa_pct']; ?>%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. TABEL LAPORAN PER SEMESTER -->
    <div class="report-card" id="sec-semester">
        <div class="card-header-toolbar">
            <div class="card-header-title">
                <i class="fas fa-calendar-range" style="color:#2563eb; font-size:1.2rem;"></i>
                <div>
                    <h2>2. Laporan Capaian Mutu Per Semester (Tahun <?php echo $tahunDipilih; ?>)</h2>
                    <div class="card-header-subtitle">Evaluasi berkala Semester I (Jan - Jun) dan Semester II (Jul - Des)</div>
                </div>
            </div>
            <button type="button" class="btn-action no-print" onclick="copyTable('tableSemester')">
                <i class="fas fa-copy"></i> Copy Tabel
            </button>
        </div>
        <div class="table-responsive">
            <table class="mutu-table" id="tableSemester">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 200px;">Semester</th>
                        <th rowspan="2" class="td-right">Total Pemberian Obat</th>
                        <th colspan="2" class="td-center" style="background:#e6fffa; color:#0f766e;">Kepatuhan FORNAS (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#eff6ff; color:#1e40af;">Kepatuhan GENERIK (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#fef2f2; color:#991b1b;">3a. Kepatuhan High Alert (100%)</th>
                        <th colspan="2" class="td-center" style="background:#fffbeb; color:#92400e;">3b. Kepatuhan LASA (100%)</th>
                    </tr>
                    <tr>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semesterList as $nmSem => $d): ?>
                    <tr>
                        <td class="td-bold"><i class="fas fa-bookmark" style="color:var(--primary); margin-right:6px;"></i> <?php echo $nmSem; ?></td>
                        <td class="td-right td-bold"><?php echo number_format($d['total_obat'], 0, ',', '.'); ?></td>
                        <!-- Fornas -->
                        <td class="td-right"><?php echo number_format($d['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($d['fornas_pct'] >= $targetFornas) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $d['fornas_pct']; ?>%
                            </span>
                        </td>
                        <!-- Generik -->
                        <td class="td-right"><?php echo number_format($d['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($d['generik_pct'] >= $targetGenerik) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $d['generik_pct']; ?>%
                            </span>
                        </td>
                        <!-- High Alert -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($d['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $d['high_alert_pct']; ?>%
                            </span>
                        </td>
                        <!-- LASA -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($d['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $d['lasa_pct']; ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL AKUMULASI TAHUNAN:</td>
                        <td class="td-right"><?php echo number_format($tahunan['total_obat'], 0, ',', '.'); ?></td>
                        <td class="td-right"><?php echo number_format($tahunan['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['fornas_pct']; ?>%</td>
                        <td class="td-right"><?php echo number_format($tahunan['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['generik_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['high_alert_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['lasa_pct']; ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 3. TABEL LAPORAN PER TRIWULAN -->
    <div class="report-card" id="sec-triwulan">
        <div class="card-header-toolbar">
            <div class="card-header-title">
                <i class="fas fa-chart-column" style="color:#0d9488; font-size:1.2rem;"></i>
                <div>
                    <h2>3. Laporan Capaian Mutu Per Triwulan (Tahun <?php echo $tahunDipilih; ?>)</h2>
                    <div class="card-header-subtitle">Evaluasi triwulanan (Q1, Q2, Q3, Q4) untuk pelaporan komite mutu RS</div>
                </div>
            </div>
            <button type="button" class="btn-action no-print" onclick="copyTable('tableTriwulan')">
                <i class="fas fa-copy"></i> Copy Tabel
            </button>
        </div>
        <div class="table-responsive">
            <table class="mutu-table" id="tableTriwulan">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 200px;">Triwulan</th>
                        <th rowspan="2" class="td-right">Total Pemberian Obat</th>
                        <th colspan="2" class="td-center" style="background:#e6fffa; color:#0f766e;">Kepatuhan FORNAS (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#eff6ff; color:#1e40af;">Kepatuhan GENERIK (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#fef2f2; color:#991b1b;">3a. Kepatuhan High Alert (100%)</th>
                        <th colspan="2" class="td-center" style="background:#fffbeb; color:#92400e;">3b. Kepatuhan LASA (100%)</th>
                    </tr>
                    <tr>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($triwulanList as $nmTri => $d): ?>
                    <tr>
                        <td class="td-bold"><i class="fas fa-cubes" style="color:var(--primary); margin-right:6px;"></i> <?php echo $nmTri; ?></td>
                        <td class="td-right td-bold"><?php echo number_format($d['total_obat'], 0, ',', '.'); ?></td>
                        <!-- Fornas -->
                        <td class="td-right"><?php echo number_format($d['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($d['fornas_pct'] >= $targetFornas) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $d['fornas_pct']; ?>%
                            </span>
                        </td>
                        <!-- Generik -->
                        <td class="td-right"><?php echo number_format($d['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box <?php echo ($d['generik_pct'] >= $targetGenerik) ? 'pct-pass' : 'pct-alert'; ?>">
                                <?php echo $d['generik_pct']; ?>%
                            </span>
                        </td>
                        <!-- High Alert -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($d['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $d['high_alert_pct']; ?>%
                            </span>
                        </td>
                        <!-- LASA -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($d['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <span class="pct-box pct-pass">
                                <?php echo $d['lasa_pct']; ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL AKUMULASI TAHUNAN:</td>
                        <td class="td-right"><?php echo number_format($tahunan['total_obat'], 0, ',', '.'); ?></td>
                        <td class="td-right"><?php echo number_format($tahunan['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['fornas_pct']; ?>%</td>
                        <td class="td-right"><?php echo number_format($tahunan['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['generik_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['high_alert_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['lasa_pct']; ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 4. TABEL LAPORAN RINCIAN PER BULAN -->
    <div class="report-card" id="sec-bulanan">
        <div class="card-header-toolbar">
            <div class="card-header-title">
                <i class="fas fa-calendar-days" style="color:#7c3aed; font-size:1.2rem;"></i>
                <div>
                    <h2>4. Laporan Capaian Mutu Rincian Per Bulan (Tahun <?php echo $tahunDipilih; ?>)</h2>
                    <div class="card-header-subtitle">Rincian pergerakan bulanan dari Januari sampai Desember</div>
                </div>
            </div>
            <button type="button" class="btn-action no-print" onclick="copyTable('tableBulanan')">
                <i class="fas fa-copy"></i> Copy Tabel
            </button>
        </div>
        <div class="table-responsive">
            <table class="mutu-table" id="tableBulanan">
                <thead>
                    <tr>
                        <th rowspan="2" class="td-center" style="width: 50px;">No</th>
                        <th rowspan="2" style="width: 140px;">Bulan</th>
                        <th rowspan="2" class="td-right">Total Pemberian Obat</th>
                        <th colspan="2" class="td-center" style="background:#e6fffa; color:#0f766e;">Kepatuhan FORNAS (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#eff6ff; color:#1e40af;">Kepatuhan GENERIK (&ge; 80%)</th>
                        <th colspan="2" class="td-center" style="background:#fef2f2; color:#991b1b;">3a. Kepatuhan High Alert (100%)</th>
                        <th colspan="2" class="td-center" style="background:#fffbeb; color:#92400e;">3b. Kepatuhan LASA (100%)</th>
                    </tr>
                    <tr>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                        <th class="td-right">Item Sesuai</th>
                        <th class="td-center">% Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for ($m = 1; $m <= 12; $m++):
                        $bRow = $bulanData[$m];
                        $tot  = $bRow['total_obat'];
                        $pF   = $tot > 0 ? round(($bRow['fornas_item'] / $tot) * 100, 1) : 0;
                        $pG   = $tot > 0 ? round(($bRow['generik_item'] / $tot) * 100, 1) : 0;
                        $pHA  = $bRow['high_alert_item'] > 0 ? 100.0 : 0.0;
                        $pLA  = $bRow['lasa_item'] > 0 ? 100.0 : 0.0;
                    ?>
                    <tr>
                        <td class="td-center" style="color:var(--text-muted);"><?php echo $m; ?></td>
                        <td class="td-bold"><?php echo $bRow['nama_bulan']; ?></td>
                        <td class="td-right td-bold"><?php echo number_format($tot, 0, ',', '.'); ?></td>
                        <!-- Fornas -->
                        <td class="td-right"><?php echo number_format($bRow['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <?php if ($tot > 0): ?>
                                <span class="pct-box <?php echo ($pF >= $targetFornas) ? 'pct-pass' : 'pct-alert'; ?>">
                                    <?php echo $pF; ?>%
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <!-- Generik -->
                        <td class="td-right"><?php echo number_format($bRow['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <?php if ($tot > 0): ?>
                                <span class="pct-box <?php echo ($pG >= $targetGenerik) ? 'pct-pass' : 'pct-alert'; ?>">
                                    <?php echo $pG; ?>%
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <!-- High Alert -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($bRow['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <?php if ($bRow['high_alert_item'] > 0): ?>
                                <span class="pct-box pct-pass">
                                    100%
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <!-- LASA -->
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($bRow['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center">
                            <?php if ($bRow['lasa_item'] > 0): ?>
                                <span class="pct-box pct-pass">
                                    100%
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="td-center">TOTAL KESELURUHAN (12 BULAN):</td>
                        <td class="td-right"><?php echo number_format($tahunan['total_obat'], 0, ',', '.'); ?></td>
                        <td class="td-right"><?php echo number_format($tahunan['fornas_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['fornas_pct']; ?>%</td>
                        <td class="td-right"><?php echo number_format($tahunan['generik_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['generik_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['high_alert_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['high_alert_pct']; ?>%</td>
                        <td class="td-right" style="color:#15803d;"><?php echo number_format($tahunan['lasa_item'], 0, ',', '.'); ?></td>
                        <td class="td-center"><?php echo $tahunan['lasa_pct']; ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<!-- TOAST NOTIFICATION -->
<div id="toast"><i class="fas fa-check-circle" style="color:#10b981;"></i> Tabel berhasil disalin ke clipboard!</div>

<script>
    function copyTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        let tsv = '';
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('th:not(.no-print), td:not(.no-print)');
            const rowData = [];
            cells.forEach(cell => {
                let text = cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/\t/g, ' ').trim();
                rowData.push(text);
            });
            if (rowData.length > 0) {
                tsv += rowData.join('\t') + '\n';
            }
        });

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(tsv).then(showToast).catch(() => fallbackCopy(tsv));
        } else {
            fallbackCopy(tsv);
        }
    }

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showToast();
        } catch (e) {
            alert('Gagal menyalin tabel ke clipboard.');
        }
        document.body.removeChild(ta);
    }

    function showToast() {
        const toast = document.getElementById('toast');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 2800);
    }
</script>

</body>
</html>
