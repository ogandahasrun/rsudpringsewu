<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Default periode: awal 3 bulan terakhir s/d hari ini agar tren per bulan terlihat jelas
$start         = isset($_GET['start']) ? $_GET['start'] : date('Y-m-01', strtotime('-2 months'));
$end           = isset($_GET['end'])   ? $_GET['end']   : date('Y-m-d');
$status_lanjut = isset($_GET['status_lanjut']) ? $_GET['status_lanjut'] : 'Semua';

$whereStatus = "";
if ($status_lanjut === 'Ranap' || $status_lanjut === 'Ralan') {
    $whereStatus = " AND r.status_lanjut = '" . mysqli_real_escape_string($koneksi, $status_lanjut) . "' ";
}

// =========================================================================
// 1. REKAPITULASI TOTAL PASIEN PENGGUNA ANTIBIOTIK PER BULAN
// =========================================================================
$sqlBulanan = "SELECT ym,
                      COUNT(DISTINCT no_rkm_medis) AS total_pasien,
                      COUNT(*) AS total_rawat,
                      SUM(CASE WHEN days < 3 THEN 1 ELSE 0 END) AS rawat_lt_3,
                      SUM(CASE WHEN days >= 3 THEN 1 ELSE 0 END) AS rawat_ge_3,
                      ROUND(AVG(days), 2) AS avg_days_all,
                      ROUND(AVG(CASE WHEN days >= 3 THEN days ELSE NULL END), 2) AS avg_days_ge_3
               FROM (
                   SELECT DATE_FORMAT(MIN(d.tgl_perawatan), '%Y-%m') AS ym,
                          d.no_rawat,
                          r.no_rkm_medis,
                          COUNT(DISTINCT d.tgl_perawatan) AS days
                   FROM detail_pemberian_obat d
                   INNER JOIN databarang b ON d.kode_brng = b.kode_brng
                   INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
                   WHERE b.kode_golongan = 'AB'
                     AND d.tgl_perawatan BETWEEN ? AND ?
                     $whereStatus
                   GROUP BY d.no_rawat, r.no_rkm_medis
               ) t
               GROUP BY ym
               ORDER BY ym ASC";

$stmtBulanan = mysqli_prepare($koneksi, $sqlBulanan);
mysqli_stmt_bind_param($stmtBulanan, 'ss', $start, $end);
mysqli_stmt_execute($stmtBulanan);
$resBulanan = mysqli_stmt_get_result($stmtBulanan);

$dataBulanan = [];
$grandTotalRawat = 0;
$grandTotalGe3 = 0;
$weightedDaysSum = 0;
while ($row = mysqli_fetch_assoc($resBulanan)) {
    $pctGe3 = $row['total_rawat'] > 0 ? round(($row['rawat_ge_3'] / $row['total_rawat']) * 100, 1) : 0;
    $row['pct_ge_3'] = $pctGe3;
    $dataBulanan[] = $row;
    $grandTotalRawat += intval($row['total_rawat']);
    $grandTotalGe3 += intval($row['rawat_ge_3']);
    $weightedDaysSum += (floatval($row['avg_days_all']) * intval($row['total_rawat']));
}
$overallAvgDays = $grandTotalRawat > 0 ? round($weightedDaysSum / $grandTotalRawat, 2) : 0;
$overallPctGe3  = $grandTotalRawat > 0 ? round(($grandTotalGe3 / $grandTotalRawat) * 100, 1) : 0;

// =========================================================================
// 2. PERSENTASE JENIS ANTIBIOTIK YANG PALING BANYAK DIRESEPKAN
// =========================================================================
$sqlAntibiotik = "SELECT b.kode_brng,
                         b.nama_brng,
                         COUNT(*) AS total_rawat,
                         SUM(CASE WHEN sub.days_per_obat < 3 THEN 1 ELSE 0 END) AS rawat_lt_3,
                         SUM(CASE WHEN sub.days_per_obat >= 3 THEN 1 ELSE 0 END) AS rawat_ge_3,
                         SUM(sub.total_jml) AS total_item,
                         ROUND(AVG(sub.days_per_obat), 2) AS avg_days
                  FROM (
                      SELECT d2.no_rawat,
                             d2.kode_brng,
                             COUNT(DISTINCT d2.tgl_perawatan) AS days_per_obat,
                             SUM(d2.jml) AS total_jml
                      FROM detail_pemberian_obat d2
                      INNER JOIN databarang b2 ON d2.kode_brng = b2.kode_brng
                      INNER JOIN reg_periksa r ON d2.no_rawat = r.no_rawat
                      WHERE b2.kode_golongan = 'AB'
                        AND d2.tgl_perawatan BETWEEN ? AND ?
                        $whereStatus
                      GROUP BY d2.no_rawat, d2.kode_brng
                  ) sub
                  INNER JOIN databarang b ON sub.kode_brng = b.kode_brng
                  GROUP BY b.kode_brng, b.nama_brng
                  ORDER BY total_rawat DESC";

$stmtAntibiotik = mysqli_prepare($koneksi, $sqlAntibiotik);
mysqli_stmt_bind_param($stmtAntibiotik, 'ss', $start, $end);
mysqli_stmt_execute($stmtAntibiotik);
$resAntibiotik = mysqli_stmt_get_result($stmtAntibiotik);

$dataAntibiotik = [];
$totalPeresepanAntibiotik = 0;
while ($row = mysqli_fetch_assoc($resAntibiotik)) {
    $totalPeresepanAntibiotik += intval($row['total_rawat']);
    $dataAntibiotik[] = $row;
}

foreach ($dataAntibiotik as &$ab) {
    $ab['pct_share'] = $totalPeresepanAntibiotik > 0
        ? round(($ab['total_rawat'] / $totalPeresepanAntibiotik) * 100, 2)
        : 0;
    $ab['pct_ge_3'] = $ab['total_rawat'] > 0
        ? round(($ab['rawat_ge_3'] / $ab['total_rawat']) * 100, 1)
        : 0;
}
unset($ab);

$topAntibiotikName = !empty($dataAntibiotik) ? $dataAntibiotik[0]['nama_brng'] : '-';
$topAntibiotikPct  = !empty($dataAntibiotik) ? $dataAntibiotik[0]['pct_share'] : 0;

// =========================================================================
// 3. RATA-RATA DURASI HARI PENGGUNAAN ANTIBIOTIK PER BAGIAN / BANGSAL
// =========================================================================
$sqlBangsal = "SELECT bagian,
                      status_lanjut,
                      COUNT(*) AS total_rawat,
                      SUM(CASE WHEN days < 3 THEN 1 ELSE 0 END) AS rawat_lt_3,
                      SUM(CASE WHEN days >= 3 THEN 1 ELSE 0 END) AS rawat_ge_3,
                      ROUND(AVG(days), 2) AS avg_days_all,
                      ROUND(AVG(CASE WHEN days >= 3 THEN days ELSE NULL END), 2) AS avg_days_ge_3,
                      MAX(days) AS max_days
               FROM (
                   SELECT d.no_rawat,
                          r.status_lanjut,
                          COALESCE(
                              (SELECT bg.nm_bangsal
                               FROM kamar_inap ki
                               INNER JOIN kamar km ON ki.kd_kamar = km.kd_kamar
                               INNER JOIN bangsal bg ON km.kd_bangsal = bg.kd_bangsal
                               WHERE ki.no_rawat = d.no_rawat
                               ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
                               LIMIT 1),
                              CONCAT('Ralan - ', pl.nm_poli)
                          ) AS bagian,
                          COUNT(DISTINCT d.tgl_perawatan) AS days
                   FROM detail_pemberian_obat d
                   INNER JOIN databarang b ON d.kode_brng = b.kode_brng
                   INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
                   LEFT JOIN poliklinik pl ON r.kd_poli = pl.kd_poli
                   WHERE b.kode_golongan = 'AB'
                     AND d.tgl_perawatan BETWEEN ? AND ?
                     $whereStatus
                   GROUP BY d.no_rawat, r.status_lanjut, pl.nm_poli
               ) t
               GROUP BY bagian, status_lanjut
               ORDER BY avg_days_all DESC, total_rawat DESC";

$stmtBangsal = mysqli_prepare($koneksi, $sqlBangsal);
mysqli_stmt_bind_param($stmtBangsal, 'ss', $start, $end);
mysqli_stmt_execute($stmtBangsal);
$resBangsal = mysqli_stmt_get_result($stmtBangsal);

$dataBangsal = [];
while ($row = mysqli_fetch_assoc($resBangsal)) {
    $row['pct_ge_3'] = $row['total_rawat'] > 0
        ? round(($row['rawat_ge_3'] / $row['total_rawat']) * 100, 1)
        : 0;
    $dataBangsal[] = $row;
}

// Prepare Chart.js datasets
$chartBulanLabels = array_column($dataBulanan, 'ym');
$chartBulanPasien = array_map('intval', array_column($dataBulanan, 'total_pasien'));
$chartBulanRawat  = array_map('intval', array_column($dataBulanan, 'total_rawat'));
$chartBulanGe3    = array_map('intval', array_column($dataBulanan, 'rawat_ge_3'));

// Top 10 Antibiotik + Lainnya for Pie/Bar chart
$topN = 10;
$chartAbLabels = [];
$chartAbCounts = [];
$chartAbGe3    = [];
$otherCount = 0;
$otherGe3   = 0;
foreach ($dataAntibiotik as $idx => $item) {
    if ($idx < $topN) {
        $chartAbLabels[] = $item['nama_brng'];
        $chartAbCounts[] = intval($item['total_rawat']);
        $chartAbGe3[]    = intval($item['rawat_ge_3']);
    } else {
        $otherCount += intval($item['total_rawat']);
        $otherGe3   += intval($item['rawat_ge_3']);
    }
}
if ($otherCount > 0) {
    $chartAbLabels[] = 'Lainnya (' . (count($dataAntibiotik) - $topN) . ' jenis)';
    $chartAbCounts[] = $otherCount;
    $chartAbGe3[]    = $otherGe3;
}

// Top 15 Bangsal by total_rawat / avg_days for chart readability
$chartBangsalSlice  = array_slice($dataBangsal, 0, 15);
$chartBangsalLabels = array_column($chartBangsalSlice, 'bagian');
$chartBangsalAvgAll = array_map('floatval', array_column($chartBangsalSlice, 'avg_days_all'));
$chartBangsalAvgGe3 = array_map(function($v) { return $v !== null ? floatval($v) : 0; }, array_column($chartBangsalSlice, 'avg_days_ge_3'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Eksekutif PPRA - Rekapitulasi Penggunaan Antibiotik</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --success: #198754;
            --warning: #fd7e14;
            --border: #dee2e6;
            --bg-light: #f8f9fa;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px 28px;
            color: #212529;
            background-color: #f4f6f9;
        }
        .container {
            max-width: 1360px;
            margin: 0 auto;
        }
        /* Report Header */
        .report-header {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .report-title h1 {
            margin: 0 0 6px 0;
            font-size: 22px;
            color: #1a3b5d;
        }
        .report-title p {
            margin: 0;
            font-size: 13.5px;
            color: #555;
        }
        .toolbar-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        /* Filter Bar */
        .filter-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px 20px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0;
        }
        .filter-form label {
            font-size: 13px;
            font-weight: 600;
        }
        .filter-form input, .filter-form select {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
        }
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 5px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn-primary { background-color: var(--primary); color: #fff; }
        .btn-primary:hover { background-color: var(--primary-dark); }
        .btn-success { background-color: var(--success); color: #fff; }
        .btn-success:hover { background-color: #146c43; }
        .btn-outline { background-color: #fff; border-color: #6c757d; color: #333; }
        .btn-outline:hover { background-color: #e9ecef; }

        /* KPI Summary Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .kpi-card {
            background: #fff;
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 16px 18px;
        }
        .kpi-card.kpi-warning { border-left-color: var(--warning); }
        .kpi-card.kpi-success { border-left-color: var(--success); }
        .kpi-card .kpi-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .kpi-card .kpi-value {
            font-size: 24px;
            font-weight: 700;
            color: #1a3b5d;
            margin-bottom: 4px;
        }
        .kpi-card .kpi-sub {
            font-size: 12.5px;
            color: #555;
        }

        /* Section Cards */
        .section-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #f1f3f5;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .section-header h2 {
            margin: 0;
            font-size: 17px;
            color: #1a3b5d;
        }
        .chart-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-row.two-col {
            grid-template-columns: 1fr 1.3fr;
        }
        .chart-box {
            position: relative;
            min-height: 290px;
            border: 1px solid #f1f3f5;
            border-radius: 6px;
            padding: 12px;
            background: #fcfcfd;
        }
        /* Tables */
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
        }
        td.num, th.num {
            text-align: right;
        }
        tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        tfoot th, tfoot td {
            background-color: #e9ecef;
            font-weight: 700;
        }
        .badge-pct {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11.5px;
            font-weight: 600;
            background: #e7f1ff;
            color: #084298;
        }

        /* Signature block for print */
        .print-signature {
            display: none;
            margin-top: 36px;
            page-break-inside: avoid;
        }
        .sig-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 0 40px;
        }
        .sig-box {
            text-align: center;
            width: 260px;
        }
        .sig-space {
            height: 70px;
        }

        @media (max-width: 900px) {
            .chart-row.two-col {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 11.5px;
            }
            .no-print {
                display: none !important;
            }
            .section-card, .report-header, .kpi-card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }
            .print-signature {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Laporan -->
    <div class="report-header">
        <div class="report-title">
            <h1>LAPORAN EKSEKUTIF KOMITE PPRA - PENGGUNAAN ANTIBIOTIK</h1>
            <p>
                RSUD Pringsewu | Periode: <strong><?php echo htmlspecialchars($start); ?></strong> s/d <strong><?php echo htmlspecialchars($end); ?></strong>
                | Layanan: <strong><?php echo htmlspecialchars($status_lanjut); ?></strong>
            </p>
        </div>
        <div class="toolbar-actions no-print">
            <a href="monitor_antibiotik.php?start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>" class="btn btn-outline">
                &larr; Monitor Pasien (&ge; 3 Hari)
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary">
                Cetak / PDF Laporan Direktur
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="filter-card no-print">
        <form method="GET" class="filter-form">
            <label for="start">Periode:</label>
            <input type="date" id="start" name="start" value="<?php echo htmlspecialchars($start); ?>" required>
            <span>s/d</span>
            <input type="date" id="end" name="end" value="<?php echo htmlspecialchars($end); ?>" required>

            <label for="status_lanjut">Jenis Layanan:</label>
            <select id="status_lanjut" name="status_lanjut">
                <option value="Semua" <?php echo $status_lanjut === 'Semua' ? 'selected' : ''; ?>>Semua (Ranap &amp; Ralan)</option>
                <option value="Ranap" <?php echo $status_lanjut === 'Ranap' ? 'selected' : ''; ?>>Rawat Inap (Ranap)</option>
                <option value="Ralan" <?php echo $status_lanjut === 'Ralan' ? 'selected' : ''; ?>>Rawat Jalan (Ralan)</option>
            </select>

            <button type="submit" class="btn btn-primary">Tampilkan Rekapitulasi</button>
        </form>
    </div>

    <!-- Ringkasan Eksekutif (KPI Cards) -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Episode Rawat Antibiotik</div>
            <div class="kpi-value"><?php echo number_format($grandTotalRawat, 0, ',', '.'); ?></div>
            <div class="kpi-sub">Pasien yang mendapat terapi antibiotik</div>
        </div>
        <div class="kpi-card kpi-warning">
            <div class="kpi-label">Penggunaan Antibiotik &ge; 3 Hari</div>
            <div class="kpi-value"><?php echo number_format($grandTotalGe3, 0, ',', '.'); ?> (<?php echo $overallPctGe3; ?>%)</div>
            <div class="kpi-sub">Dari seluruh episode pemberian antibiotik</div>
        </div>
        <div class="kpi-card kpi-success">
            <div class="kpi-label">Antibiotik Paling Banyak Diresepkan</div>
            <div class="kpi-value" style="font-size: 18px;"><?php echo htmlspecialchars($topAntibiotikName); ?></div>
            <div class="kpi-sub">Proporsi: <strong><?php echo $topAntibiotikPct; ?>%</strong> dari total peresepan antibiotik</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Rata-rata Durasi Penggunaan</div>
            <div class="kpi-value"><?php echo $overallAvgDays; ?> Hari</div>
            <div class="kpi-sub">Rata-rata hari pemberian per episode rawat</div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 1. TOTAL PASIEN YANG MENGGUNAKAN ANTIBIOTIK PER BULAN               -->
    <!-- =================================================================== -->
    <div class="section-card">
        <div class="section-header">
            <h2>1. Rekapitulasi Total Pasien yang Menggunakan Antibiotik per Bulan</h2>
            <button type="button" class="btn btn-success no-print copy-table-btn" data-target="table-bulanan">
                Copy Tabel Bulanan
            </button>
        </div>

        <div class="chart-row">
            <div class="chart-box">
                <canvas id="chartBulanan" height="90"></canvas>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="table-bulanan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Bulan (YYYY-MM)</th>
                        <th class="num">Total Pasien Unik (RM)</th>
                        <th class="num">Total Episode Rawat</th>
                        <th class="num">Penggunaan &lt; 3 Hari</th>
                        <th class="num">Penggunaan &ge; 3 Hari</th>
                        <th class="num">% (&ge; 3 Hari)</th>
                        <th class="num">Rata-rata Durasi Semua (Hari)</th>
                        <th class="num">Rata-rata Durasi &ge; 3 Hari</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dataBulanan)): ?>
                        <?php foreach ($dataBulanan as $i => $b): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($b['ym']); ?></strong></td>
                            <td class="num"><?php echo number_format($b['total_pasien'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($b['total_rawat'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($b['rawat_lt_3'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($b['rawat_ge_3'], 0, ',', '.'); ?></td>
                            <td class="num"><span class="badge-pct"><?php echo $b['pct_ge_3']; ?>%</span></td>
                            <td class="num"><?php echo $b['avg_days_all']; ?></td>
                            <td class="num"><?php echo $b['avg_days_ge_3'] !== null ? $b['avg_days_ge_3'] : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;">Tidak ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($dataBulanan)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3">Total / Rata-rata Keseluruhan</td>
                        <td class="num"><?php echo number_format($grandTotalRawat, 0, ',', '.'); ?></td>
                        <td class="num"><?php echo number_format($grandTotalRawat - $grandTotalGe3, 0, ',', '.'); ?></td>
                        <td class="num"><?php echo number_format($grandTotalGe3, 0, ',', '.'); ?></td>
                        <td class="num"><?php echo $overallPctGe3; ?>%</td>
                        <td class="num"><?php echo $overallAvgDays; ?></td>
                        <td class="num">-</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. PERSENTASE JENIS ANTIBIOTIK YANG PALING BANYAK DIRESEPKAN        -->
    <!-- =================================================================== -->
    <div class="section-card">
        <div class="section-header">
            <h2>2. Persentase Jenis Antibiotik yang Paling Banyak Diresepkan</h2>
            <button type="button" class="btn btn-success no-print copy-table-btn" data-target="table-antibiotik">
                Copy Tabel Jenis Antibiotik
            </button>
        </div>

        <div class="chart-row two-col">
            <div class="chart-box">
                <canvas id="chartAntibiotikPie"></canvas>
            </div>
            <div class="chart-box">
                <canvas id="chartAntibiotikBar"></canvas>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="table-antibiotik">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Antibiotik</th>
                        <th class="num">Jumlah Episode Rawat</th>
                        <th class="num">Persentase Share (%)</th>
                        <th class="num">Episode &lt; 3 Hari</th>
                        <th class="num">Episode &ge; 3 Hari</th>
                        <th class="num">% (&ge; 3 Hari)</th>
                        <th class="num">Total Item Diberikan</th>
                        <th class="num">Rata-rata Durasi (Hari)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dataAntibiotik)): ?>
                        <?php foreach ($dataAntibiotik as $i => $ab): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($ab['kode_brng']); ?></td>
                            <td><strong><?php echo htmlspecialchars($ab['nama_brng']); ?></strong></td>
                            <td class="num"><?php echo number_format($ab['total_rawat'], 0, ',', '.'); ?></td>
                            <td class="num"><span class="badge-pct"><?php echo $ab['pct_share']; ?>%</span></td>
                            <td class="num"><?php echo number_format($ab['rawat_lt_3'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($ab['rawat_ge_3'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo $ab['pct_ge_3']; ?>%</td>
                            <td class="num"><?php echo number_format($ab['total_item'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo $ab['avg_days']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align:center;">Tidak ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($dataAntibiotik)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3">Total Peresepan Berdasarkan Jenis Antibiotik</td>
                        <td class="num"><?php echo number_format($totalPeresepanAntibiotik, 0, ',', '.'); ?></td>
                        <td class="num">100%</td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 3. RATA-RATA DURASI PENGGUNAAN ANTIBIOTIK PER BAGIAN / BANGSAL      -->
    <!-- =================================================================== -->
    <div class="section-card">
        <div class="section-header">
            <h2>3. Rata-rata Durasi Hari Penggunaan Antibiotik per Bagian / Bangsal</h2>
            <button type="button" class="btn btn-success no-print copy-table-btn" data-target="table-bangsal">
                Copy Tabel Bagian/Bangsal
            </button>
        </div>

        <div class="chart-row">
            <div class="chart-box">
                <canvas id="chartBangsal" height="100"></canvas>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="table-bangsal">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Bagian / Bangsal / Poliklinik</th>
                        <th>Layanan</th>
                        <th class="num">Total Episode Rawat</th>
                        <th class="num">Penggunaan &lt; 3 Hari</th>
                        <th class="num">Penggunaan &ge; 3 Hari</th>
                        <th class="num">% (&ge; 3 Hari)</th>
                        <th class="num">Rata-rata Durasi Semua (Hari)</th>
                        <th class="num">Rata-rata Durasi &ge; 3 Hari</th>
                        <th class="num">Durasi Maks (Hari)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dataBangsal)): ?>
                        <?php foreach ($dataBangsal as $i => $bg): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($bg['bagian']); ?></strong></td>
                            <td><?php echo htmlspecialchars($bg['status_lanjut']); ?></td>
                            <td class="num"><?php echo number_format($bg['total_rawat'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($bg['rawat_lt_3'], 0, ',', '.'); ?></td>
                            <td class="num"><?php echo number_format($bg['rawat_ge_3'], 0, ',', '.'); ?></td>
                            <td class="num"><span class="badge-pct"><?php echo $bg['pct_ge_3']; ?>%</span></td>
                            <td class="num"><strong><?php echo $bg['avg_days_all']; ?></strong></td>
                            <td class="num"><?php echo $bg['avg_days_ge_3'] !== null ? $bg['avg_days_ge_3'] : '-'; ?></td>
                            <td class="num"><?php echo $bg['max_days']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align:center;">Tidak ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Blok Tanda Tangan untuk Cetak Laporan Direktur -->
    <div class="print-signature">
        <div class="sig-grid">
            <div class="sig-box">
                <p>Mengetahui,<br><strong>Direktur RSUD Pringsewu</strong></p>
                <div class="sig-space"></div>
                <p>( ................................................. )</p>
            </div>
            <div class="sig-box">
                <p>Pringsewu, <?php echo date('d-m-Y'); ?><br><strong>Ketua Komite PPRA</strong></p>
                <div class="sig-space"></div>
                <p>( ................................................. )</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Helper Copy to Clipboard (works on HTTP & HTTPS)
    function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            return new Promise(function(resolve, reject) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try {
                    const ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    ok ? resolve() : reject(new Error('Copy command failed'));
                } catch (e) {
                    document.body.removeChild(ta);
                    reject(e);
                }
            });
        }
    }

    document.querySelectorAll('.copy-table-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const table = document.getElementById(targetId);
            if (!table) return;

            const lines = [];
            table.querySelectorAll('tr').forEach(function(row) {
                const cells = row.querySelectorAll('th, td');
                const vals = Array.from(cells).map(function(c) {
                    return c.innerText.replace(/\r?\n|\r/g, ' ').replace(/\t/g, ' ').trim();
                });
                if (vals.length > 0) {
                    lines.push(vals.join('\t'));
                }
            });

            copyTextToClipboard(lines.join('\n')).then(function() {
                alert('Tabel berhasil disalin ke clipboard (siap di-paste ke Excel/Word)!');
            }).catch(function(err) {
                console.error(err);
                alert('Gagal menyalin tabel.');
            });
        });
    });

    // =========================================================================
    // CHART.JS INITIALIZATION
    // =========================================================================
    const bulanLabels = <?php echo json_encode($chartBulanLabels); ?>;
    const bulanPasien = <?php echo json_encode($chartBulanPasien); ?>;
    const bulanRawat  = <?php echo json_encode($chartBulanRawat); ?>;
    const bulanGe3    = <?php echo json_encode($chartBulanGe3); ?>;

    if (document.getElementById('chartBulanan') && bulanLabels.length > 0) {
        new Chart(document.getElementById('chartBulanan'), {
            type: 'bar',
            data: {
                labels: bulanLabels,
                datasets: [
                    {
                        label: 'Total Pasien Unik (RM)',
                        data: bulanPasien,
                        backgroundColor: 'rgba(13, 110, 253, 0.75)'
                    },
                    {
                        label: 'Total Episode Rawat Antibiotik',
                        data: bulanRawat,
                        backgroundColor: 'rgba(25, 135, 84, 0.75)'
                    },
                    {
                        label: 'Episode Penggunaan >= 3 Hari',
                        data: bulanGe3,
                        backgroundColor: 'rgba(253, 126, 20, 0.85)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Tren Bulanan Penggunaan Antibiotik (Semua vs >= 3 Hari)'
                    },
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    const abLabels = <?php echo json_encode($chartAbLabels); ?>;
    const abCounts = <?php echo json_encode($chartAbCounts); ?>;
    const abGe3    = <?php echo json_encode($chartAbGe3); ?>;

    if (document.getElementById('chartAntibiotikPie') && abLabels.length > 0) {
        new Chart(document.getElementById('chartAntibiotikPie'), {
            type: 'doughnut',
            data: {
                labels: abLabels,
                datasets: [{
                    data: abCounts,
                    backgroundColor: [
                        '#0d6efd', '#198754', '#fd7e14', '#dc3545', '#6f42c1',
                        '#20c997', '#0dcaf0', '#ffc107', '#6610f2', '#d63384', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Proporsi Jenis Antibiotik Paling Banyak Diresepkan'
                    },
                    legend: { position: 'right' }
                }
            }
        });
    }

    if (document.getElementById('chartAntibiotikBar') && abLabels.length > 0) {
        new Chart(document.getElementById('chartAntibiotikBar'), {
            type: 'bar',
            data: {
                labels: abLabels,
                datasets: [
                    {
                        label: 'Total Episode Diresepkan',
                        data: abCounts,
                        backgroundColor: 'rgba(13, 110, 253, 0.75)'
                    },
                    {
                        label: 'Penggunaan >= 3 Hari',
                        data: abGe3,
                        backgroundColor: 'rgba(253, 126, 20, 0.85)'
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Perbandingan Total Peresepan vs Penggunaan >= 3 Hari per Jenis Antibiotik'
                    }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }

    const bangsalLabels = <?php echo json_encode($chartBangsalLabels); ?>;
    const bangsalAvgAll = <?php echo json_encode($chartBangsalAvgAll); ?>;
    const bangsalAvgGe3 = <?php echo json_encode($chartBangsalAvgGe3); ?>;

    if (document.getElementById('chartBangsal') && bangsalLabels.length > 0) {
        new Chart(document.getElementById('chartBangsal'), {
            type: 'bar',
            data: {
                labels: bangsalLabels,
                datasets: [
                    {
                        label: 'Rata-rata Durasi Semua Pasien (Hari)',
                        data: bangsalAvgAll,
                        backgroundColor: 'rgba(13, 110, 253, 0.8)'
                    },
                    {
                        label: 'Rata-rata Durasi Pasien >= 3 Hari',
                        data: bangsalAvgGe3,
                        backgroundColor: 'rgba(220, 53, 69, 0.75)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Rata-rata Durasi Penggunaan Antibiotik (Hari) per Bagian / Bangsal (Top 15)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Hari' }
                    }
                }
            }
        });
    }
</script>
</body>
</html>

