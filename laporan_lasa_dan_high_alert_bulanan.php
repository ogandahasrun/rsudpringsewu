<?php
session_start();
include 'koneksi.php';

// Cek apakah request berupa AJAX detail bulanan
if (isset($_GET['action']) && $_GET['action'] === 'ajax_detail_bulan') {
    header('Content-Type: application/json; charset=utf-8');
    
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
    $bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 1;
    $jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
    
    // Validasi parameter
    if ($tahun < 2000 || $tahun > 2099 || $bulan < 1 || $bulan > 12 || !in_array($jenis, ['lasa', 'high_alert'])) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
        exit;
    }
    
    $bulan_pad = sprintf('%02d', $bulan);
    $tgl_awal_bulan = "$tahun-$bulan_pad-01";
    $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));
    
    $column_filter = ($jenis === 'lasa') ? "databarang_tambahan.lasa = '1'" : "databarang_tambahan.high_alert = '1'";
    
    $query_detail = "
        SELECT 
            databarang.kode_brng,
            databarang.nama_brng,
            databarang.kode_sat,
            COUNT(detail_pemberian_obat.kode_brng) AS total_transaksi,
            SUM(detail_pemberian_obat.jml) AS total_qty
        FROM detail_pemberian_obat
        INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
        INNER JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
        WHERE detail_pemberian_obat.tgl_perawatan BETWEEN '$tgl_awal_bulan' AND '$tgl_akhir_bulan'
          AND $column_filter
        GROUP BY databarang.kode_brng, databarang.nama_brng, databarang.kode_sat
        ORDER BY total_transaksi DESC, total_qty DESC
    ";
    
    $result_detail = mysqli_query($koneksi, $query_detail);
    if (!$result_detail) {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
        exit;
    }
    
    $nama_bulan_indo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $items = [];
    $grand_transaksi = 0;
    $grand_qty = 0;
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $transaksi = (int)$row['total_transaksi'];
        $qty = (float)$row['total_qty'];
        $items[] = [
            'kode_brng'       => $row['kode_brng'],
            'nama_brng'       => $row['nama_brng'],
            'kode_sat'        => $row['kode_sat'],
            'total_transaksi' => $transaksi,
            'total_qty'       => $qty
        ];
        $grand_transaksi += $transaksi;
        $grand_qty += $qty;
    }
    
    echo json_encode([
        'status'          => 'success',
        'tahun'           => $tahun,
        'bulan'           => $bulan,
        'nama_bulan'      => $nama_bulan_indo[$bulan] ?? "Bulan $bulan",
        'tgl_awal'        => $tgl_awal_bulan,
        'tgl_akhir'       => $tgl_akhir_bulan,
        'jenis'           => $jenis,
        'label_jenis'     => ($jenis === 'lasa') ? 'LASA (Look-Alike Sound-Alike)' : 'High Alert',
        'total_item_obat' => count($items),
        'total_transaksi' => $grand_transaksi,
        'total_qty'       => $grand_qty,
        'data'            => $items
    ]);
    exit;
}

// Ambil data instansi & logo
$query_instansi = "SELECT nama_instansi, alamat_instansi, kabupaten, propinsi, kontak, email, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
$alamat_instansi = "";
$kabupaten_instansi = "";
$propinsi_instansi = "";
$kontak_instansi = "";
$email_instansi = "";
$logo_src = "images/logo.png";

if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'] ?? "RSUD PRINGSEWU";
    $alamat_instansi = $row_instansi['alamat_instansi'] ?? "";
    $kabupaten_instansi = $row_instansi['kabupaten'] ?? "";
    $propinsi_instansi = $row_instansi['propinsi'] ?? "";
    $kontak_instansi = $row_instansi['kontak'] ?? "";
    $email_instansi = $row_instansi['email'] ?? "";
    if (!empty($row_instansi['logo'])) {
        $logo_base64 = base64_encode($row_instansi['logo']);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}

// Inisialisasi Tahun Dipilih
$cur_year = (int)date('Y');
$tahun_dipilih = isset($_REQUEST['tahun']) && !empty($_REQUEST['tahun']) 
    ? (int)$_REQUEST['tahun'] 
    : $cur_year;

// Validasi tahun
if ($tahun_dipilih < 2000 || $tahun_dipilih > 2099) {
    $tahun_dipilih = $cur_year;
}

// Generate daftar pilihan tahun (dari tahun sekarang + 1 mundur ke 2020)
$daftar_tahun = [];
for ($y = $cur_year + 1; $y >= 2020; $y--) {
    $daftar_tahun[] = $y;
}
if (!in_array($tahun_dipilih, $daftar_tahun)) {
    $daftar_tahun[] = $tahun_dipilih;
    rsort($daftar_tahun);
}

$tgl_awal_tahun = "{$tahun_dipilih}-01-01";
$tgl_akhir_tahun = "{$tahun_dipilih}-12-31";

// QUERY AGREGASI BULANAN UNTUK TAHUN YANG DIPILIH
$query_bulanan = "
    SELECT 
        MONTH(detail_pemberian_obat.tgl_perawatan) AS bulan,
        SUM(CASE WHEN databarang_tambahan.lasa = '1' THEN 1 ELSE 0 END) AS jumlah_lasa,
        SUM(CASE WHEN databarang_tambahan.lasa = '1' THEN detail_pemberian_obat.jml ELSE 0 END) AS qty_lasa,
        SUM(CASE WHEN databarang_tambahan.high_alert = '1' THEN 1 ELSE 0 END) AS jumlah_ha,
        SUM(CASE WHEN databarang_tambahan.high_alert = '1' THEN detail_pemberian_obat.jml ELSE 0 END) AS qty_ha
    FROM detail_pemberian_obat
    INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
    INNER JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
    WHERE detail_pemberian_obat.tgl_perawatan BETWEEN '$tgl_awal_tahun' AND '$tgl_akhir_tahun'
      AND (databarang_tambahan.lasa = '1' OR databarang_tambahan.high_alert = '1')
    GROUP BY MONTH(detail_pemberian_obat.tgl_perawatan)
    ORDER BY bulan ASC
";

$res_bulanan = mysqli_query($koneksi, $query_bulanan);
$data_per_bulan = [];
if ($res_bulanan) {
    while ($row = mysqli_fetch_assoc($res_bulanan)) {
        $data_per_bulan[(int)$row['bulan']] = [
            'jumlah_lasa' => (int)$row['jumlah_lasa'],
            'qty_lasa'    => (float)$row['qty_lasa'],
            'jumlah_ha'   => (int)$row['jumlah_ha'],
            'qty_ha'      => (float)$row['qty_ha']
        ];
    }
}

// Nama 12 Bulan Bahasa Indonesia
$nama_bulan = [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$rekap_bulanan = [];
$total_tahun_lasa = 0;
$total_tahun_qty_lasa = 0;
$total_tahun_ha = 0;
$total_tahun_qty_ha = 0;
$bulan_aktif = 0;

$peak_lasa_val = 0;
$peak_lasa_bln = '-';
$peak_ha_val = 0;
$peak_ha_bln = '-';

for ($m = 1; $m <= 12; $m++) {
    $jml_l = isset($data_per_bulan[$m]) ? $data_per_bulan[$m]['jumlah_lasa'] : 0;
    $qty_l = isset($data_per_bulan[$m]) ? $data_per_bulan[$m]['qty_lasa'] : 0;
    $jml_h = isset($data_per_bulan[$m]) ? $data_per_bulan[$m]['jumlah_ha'] : 0;
    $qty_h = isset($data_per_bulan[$m]) ? $data_per_bulan[$m]['qty_ha'] : 0;
    
    $total_tahun_lasa += $jml_l;
    $total_tahun_qty_lasa += $qty_l;
    $total_tahun_ha += $jml_h;
    $total_tahun_qty_ha += $qty_h;
    
    if (($jml_l + $jml_h) > 0) {
        $bulan_aktif++;
    }
    
    if ($jml_l > $peak_lasa_val) {
        $peak_lasa_val = $jml_l;
        $peak_lasa_bln = $nama_bulan[$m];
    }
    if ($jml_h > $peak_ha_val) {
        $peak_ha_val = $jml_h;
        $peak_ha_bln = $nama_bulan[$m];
    }
    
    $rekap_bulanan[$m] = [
        'no'          => $m,
        'bulan_angka' => sprintf('%02d', $m),
        'nama_bulan'  => $nama_bulan[$m],
        'jumlah_lasa' => $jml_l,
        'qty_lasa'    => $qty_l,
        'jumlah_ha'   => $jml_h,
        'qty_ha'      => $qty_h,
        'total_bulan' => $jml_l + $jml_h
    ];
}

$grand_total_tahun = $total_tahun_lasa + $total_tahun_ha;
$rata_per_bulan = $bulan_aktif > 0 ? round($grand_total_tahun / $bulan_aktif, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan LASA & High Alert Tahun <?php echo $tahun_dipilih; ?> - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js CDN for visual trend -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --primary-light: #e0f2fe;
            --lasa-color: #d97706;
            --lasa-bg: #fef3c7;
            --lasa-border: #fde68a;
            --ha-color: #dc2626;
            --ha-bg: #fee2e2;
            --ha-border: #fecaca;
            --success: #16a34a;
            --dark: #0f172a;
            --slate: #475569;
            --slate-light: #64748b;
            --border: #e2e8f0;
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-page);
            color: #1e293b;
            padding: 24px 20px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* Top Header Card */
        .page-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0369a1 100%);
            color: #ffffff;
            padding: 26px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            right: -40px;
            bottom: -40px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.95);
            padding: 6px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            font-size: 13px;
            color: #cbd5e1;
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-nav {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 9px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
        }

        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: translateY(-1px);
        }

        .btn-nav.active-link {
            background: #ffffff;
            color: #0369a1;
            border-color: #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        /* Filter Section */
        .filter-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 22px 26px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .filter-form {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 220px;
        }

        .form-group label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-control {
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .btn-submit {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(2, 132, 199, 0.35);
        }

        .quick-filter-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        .pill-label {
            font-size: 12px;
            color: var(--slate-light);
            font-weight: 600;
            margin-right: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pill-btn {
            background: #f1f5f9;
            color: var(--slate);
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .pill-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .pill-btn.active {
            background: var(--primary-light);
            color: var(--primary-dark);
            border-color: #bae6fd;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(2, 132, 199, 0.15);
        }

        /* Metric KPI Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 18px 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: #e0f2fe; color: #0284c7; }
        .stat-icon.amber { background: var(--lasa-bg); color: var(--lasa-color); }
        .stat-icon.rose { background: var(--ha-bg); color: var(--ha-color); }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 12px;
            color: var(--slate-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.02em;
            margin-top: 2px;
        }

        .stat-subtext {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Chart Section Card */
        .chart-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 22px 26px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .chart-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }

        /* Action bar above table */
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 14px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-box {
            position: relative;
            min-width: 260px;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }

        .search-input {
            width: 100%;
            padding: 9px 12px 9px 34px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            background: #ffffff;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            background: #ffffff;
            color: var(--slate);
            border: 1px solid var(--border);
            padding: 9px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .btn-action.excel {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .btn-action.excel:hover {
            background: #d1fae5;
        }

        .btn-action.print {
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .btn-action.print:hover {
            background: #dbeafe;
        }

        /* Main Data Table */
        .table-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        table.report-table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 14px 18px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        table.report-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
            font-size: 13.5px;
        }

        table.report-table tbody tr {
            transition: background 0.15s ease;
        }

        table.report-table tbody tr:hover {
            background-color: #f8fafc;
        }

        table.report-table tfoot td {
            padding: 16px 18px;
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            font-size: 14px;
        }

        /* Month Badge */
        .month-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .month-badge {
            background: #e2e8f0;
            color: #475569;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }

        /* Number Badges */
        .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 700;
            min-width: 50px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .badge-count.lasa {
            background-color: var(--lasa-bg);
            color: var(--lasa-color);
            border: 1px solid var(--lasa-border);
        }

        .badge-count.ha {
            background-color: var(--ha-bg);
            color: var(--ha-color);
            border: 1px solid var(--ha-border);
        }

        .badge-count.zero {
            background-color: #f1f5f9;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
            font-weight: 500;
        }

        .badge-count.clickable {
            cursor: pointer;
        }

        .badge-count.clickable:hover {
            transform: scale(1.06);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }

        .badge-qty {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
            margin-left: 8px;
        }

        .btn-view-daily {
            color: var(--primary);
            background: var(--primary-light);
            border: 1px solid #bae6fd;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-view-daily:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        /* Modal Popup */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-window {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 950px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: zoomIn 0.25s ease-out;
        }

        .modal-header {
            padding: 20px 24px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 20px;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            line-height: 1;
            transition: all 0.15s;
        }

        .modal-close:hover {
            color: #0f172a;
            background: #e2e8f0;
        }

        .modal-body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Print Styling */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                font-size: 11pt !important;
                color: #000000 !important;
            }
            .page-header, .filter-card, .chart-card, .table-toolbar, .btn-nav, .modal-overlay, .btn-view-daily {
                display: none !important;
            }
            .container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .print-header {
                display: block !important;
                margin-bottom: 20px;
                text-align: center;
                border-bottom: 2px solid #000;
                padding-bottom: 12px;
            }
            .print-header img {
                height: 55px;
                margin-bottom: 6px;
            }
            .print-header h2 {
                font-size: 15pt;
                font-weight: bold;
                margin: 0;
            }
            .print-header h3 {
                font-size: 12pt;
                margin: 4px 0;
            }
            .print-header p {
                font-size: 9pt;
                margin: 2px 0;
            }
            .table-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
            table.report-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            table.report-table th, table.report-table td {
                border: 1px solid #333 !important;
                padding: 6px 10px !important;
                color: #000 !important;
            }
            table.report-table thead th {
                background: #f0f0f0 !important;
            }
            .badge-count {
                background: transparent !important;
                border: none !important;
                color: #000 !important;
                padding: 0 !important;
                font-weight: bold !important;
            }
            .badge-qty {
                display: none !important;
            }
            .print-signatures {
                display: flex !important;
                justify-content: space-between;
                margin-top: 40px;
                page-break-inside: avoid;
            }
            .sig-block {
                text-align: center;
                width: 250px;
            }
        }

        .print-header, .print-signatures {
            display: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Print Only Header -->
    <div class="print-header">
        <?php if (!empty($logo_src)): ?>
            <img src="<?php echo $logo_src; ?>" alt="Logo">
        <?php endif; ?>
        <h2>PEMERINTAH KABUPATEN PRINGSEWU</h2>
        <h3><strong><?php echo htmlspecialchars($nama_instansi); ?></strong></h3>
        <p><?php echo htmlspecialchars($alamat_instansi); ?> - <?php echo htmlspecialchars($kabupaten_instansi); ?>, <?php echo htmlspecialchars($propinsi_instansi); ?></p>
        <p>Telepon: <?php echo htmlspecialchars($kontak_instansi); ?> | Email: <?php echo htmlspecialchars($email_instansi); ?></p>
        <h4 style="margin-top: 10px; text-decoration: underline;">LAPORAN REKAPITULASI BULANAN OBAT LASA DAN HIGH ALERT</h4>
        <p style="font-size: 10pt; font-weight: bold; margin-top: 4px;">TAHUN <?php echo $tahun_dipilih; ?></p>
    </div>

    <!-- Web Page Header -->
    <div class="page-header">
        <div class="header-left">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo Instansi" class="header-logo">
            <div class="header-title">
                <h1>
                    <i class="fas fa-calendar-alt" style="color: #38bdf8;"></i>
                    Laporan Bulanan Obat LASA & High Alert
                </h1>
                <p><?php echo htmlspecialchars($nama_instansi); ?> • Rekapitulasi Pemakaian Obat LASA dan High Alert Per Bulan Berdasarkan Tahun</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="laporan_lasa_dan_high_alert_bulanan.php?tahun=<?php echo $tahun_dipilih; ?>" class="btn-nav active-link">
                <i class="fas fa-calendar-alt"></i>
                Laporan Bulanan
            </a>
            <a href="laporan_lasa_dan_high_alert.php" class="btn-nav">
                <i class="fas fa-calendar-day"></i>
                Laporan Harian
            </a>
            <a href="farmasi.php" class="btn-nav">
                <i class="fas fa-arrow-left"></i>
                Menu Farmasi
            </a>
            <a href="index.php" class="btn-nav">
                <i class="fas fa-home"></i>
                Home
            </a>
        </div>
    </div>

    <!-- Filter Card Berdasarkan Tahun -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form" id="formFilter">
            <div class="form-group">
                <label for="tahun"><i class="fas fa-calendar-check"></i> Pilih Tahun Laporan</label>
                <select name="tahun" id="tahun" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($daftar_tahun as $thn): ?>
                        <option value="<?php echo $thn; ?>" <?php echo ($thn == $tahun_dipilih) ? 'selected' : ''; ?>>
                            Tahun <?php echo $thn; ?> <?php echo ($thn == $cur_year) ? '(Tahun Berjalan)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-filter"></i>
                Tampilkan Data
            </button>
            <a href="laporan_lasa_dan_high_alert_bulanan.php?tahun=<?php echo $cur_year; ?>" class="btn-action" style="padding: 11px 16px;">
                <i class="fas fa-sync-alt"></i>
                Tahun Sekarang
            </a>
        </form>

        <!-- Quick Filter Pills Tahun -->
        <div class="quick-filter-pills">
            <span class="pill-label"><i class="fas fa-bolt"></i> Pilih Cepat Tahun:</span>
            <?php 
                // Tampilkan beberapa pill tahun utama
                $pills_tahun = [$cur_year, $cur_year - 1, $cur_year - 2, $cur_year - 3, $cur_year - 4];
                foreach ($pills_tahun as $p_thn): 
            ?>
                <a href="?tahun=<?php echo $p_thn; ?>" class="pill-btn <?php echo ($p_thn == $tahun_dipilih) ? 'active' : ''; ?>">
                    Tahun <?php echo $p_thn; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon amber">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total LASA (<?php echo $tahun_dipilih; ?>)</span>
                <span class="stat-value" style="color: var(--lasa-color);"><?php echo number_format($total_tahun_lasa, 0, ',', '.'); ?></span>
                <span class="stat-subtext">Total Qty Fisik: <?php echo number_format($total_tahun_qty_lasa, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">
                <i class="fas fa-biohazard"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total High Alert (<?php echo $tahun_dipilih; ?>)</span>
                <span class="stat-value" style="color: var(--ha-color);"><?php echo number_format($total_tahun_ha, 0, ',', '.'); ?></span>
                <span class="stat-subtext">Total Qty Fisik: <?php echo number_format($total_tahun_qty_ha, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-pills"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Grand Total Obat</span>
                <span class="stat-value" style="color: #0284c7;"><?php echo number_format($grand_total_tahun, 0, ',', '.'); ?></span>
                <span class="stat-subtext">Total Qty: <?php echo number_format($total_tahun_qty_lasa + $total_tahun_qty_ha, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Rata-rata per Bulan</span>
                <span class="stat-value"><?php echo number_format($rata_per_bulan, 1, ',', '.'); ?></span>
                <span class="stat-subtext"><?php echo $bulan_aktif; ?> bulan dengan transaksi</span>
            </div>
        </div>
    </div>

    <!-- Visual Trend Chart Card -->
    <div class="chart-card">
        <div class="chart-header">
            <h3>
                <i class="fas fa-chart-bar" style="color: var(--primary);"></i>
                Grafik Perbandingan Bulanan Obat LASA vs High Alert (Tahun <?php echo $tahun_dipilih; ?>)
            </h3>
            <span style="font-size: 12px; color: var(--slate-light);">
                <i class="fas fa-info-circle"></i> Sumbu tegak: Frekuensi Transaksi / Resep
            </span>
        </div>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Toolbar: Search & Export -->
    <div class="table-toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearch" class="search-input" placeholder="Cari nama bulan...">
            </div>
            <span style="font-size: 12.5px; color: var(--slate-light);">
                Rekap <strong>12 Bulan</strong> Tahun <strong><?php echo $tahun_dipilih; ?></strong>
            </span>
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-action excel" id="btnExportExcel">
                <i class="far fa-file-excel"></i>
                Export ke Excel
            </button>
            <button type="button" class="btn-action print" onclick="window.print()">
                <i class="fas fa-print"></i>
                Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Main Table Card: Format No | Bulan | LASA | High Alert -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="report-table" id="tableLasaHaBulanan">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">No</th>
                        <th style="width: 260px;">Bulan</th>
                        <th style="text-align: center; width: 340px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: #b45309;">
                                <i class="fas fa-exclamation-triangle"></i>
                                LASA
                            </span>
                        </th>
                        <th style="text-align: center; width: 340px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: #b91c1c;">
                                <i class="fas fa-biohazard"></i>
                                High Alert
                            </span>
                        </th>
                        <th style="text-align: center; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($rekap_bulanan as $row): ?>
                        <tr data-bulan="<?php echo strtolower($row['nama_bulan']); ?>">
                            <td style="text-align: center; font-weight: 600; color: #64748b;"><?php echo $row['no']; ?></td>
                            <td>
                                <div class="month-name">
                                    <span class="month-badge"><?php echo $row['bulan_angka']; ?></span>
                                    <span><?php echo $row['nama_bulan']; ?></span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['jumlah_lasa'] > 0): ?>
                                    <span class="badge-count lasa clickable" 
                                          onclick="showDetailBulan(<?php echo $tahun_dipilih; ?>, <?php echo $row['no']; ?>, 'lasa', '<?php echo addslashes($row['nama_bulan']); ?>')"
                                          title="Klik untuk rincian obat LASA bulan <?php echo $row['nama_bulan']; ?>">
                                        <i class="fas fa-pills"></i>
                                        <?php echo number_format($row['jumlah_lasa'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="badge-qty">(Qty: <?php echo number_format($row['qty_lasa'], 2, ',', '.'); ?>)</span>
                                <?php else: ?>
                                    <span class="badge-count zero">0</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['jumlah_ha'] > 0): ?>
                                    <span class="badge-count ha clickable" 
                                          onclick="showDetailBulan(<?php echo $tahun_dipilih; ?>, <?php echo $row['no']; ?>, 'high_alert', '<?php echo addslashes($row['nama_bulan']); ?>')"
                                          title="Klik untuk rincian obat High Alert bulan <?php echo $row['nama_bulan']; ?>">
                                        <i class="fas fa-shield-virus"></i>
                                        <?php echo number_format($row['jumlah_ha'], 0, ',', '.'); ?>
                                    </span>
                                    <span class="badge-qty">(Qty: <?php echo number_format($row['qty_ha'], 2, ',', '.'); ?>)</span>
                                <?php else: ?>
                                    <span class="badge-count zero">0</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                    $tgl_mulai_bln = "{$tahun_dipilih}-{$row['bulan_angka']}-01";
                                    $tgl_selesai_bln = date('Y-m-t', strtotime($tgl_mulai_bln));
                                ?>
                                <a href="laporan_lasa_dan_high_alert.php?tanggal_awal=<?php echo $tgl_mulai_bln; ?>&tanggal_akhir=<?php echo $tgl_selesai_bln; ?>" 
                                   class="btn-view-daily" 
                                   title="Lihat rincian harian di bulan <?php echo $row['nama_bulan']; ?>">
                                    <i class="fas fa-calendar-day"></i>
                                    Harian
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right; padding-right: 24px;">TOTAL TAHUN <?php echo $tahun_dipilih; ?> :</td>
                        <td style="text-align: center; color: var(--lasa-color);">
                            <span class="badge-count lasa" style="font-size: 14px; padding: 6px 18px;">
                                <?php echo number_format($total_tahun_lasa, 0, ',', '.'); ?>
                            </span>
                            <span class="badge-qty" style="display: block; margin-top: 3px;">Total Qty: <?php echo number_format($total_tahun_qty_lasa, 2, ',', '.'); ?></span>
                        </td>
                        <td style="text-align: center; color: var(--ha-color);">
                            <span class="badge-count ha" style="font-size: 14px; padding: 6px 18px;">
                                <?php echo number_format($total_tahun_ha, 0, ',', '.'); ?>
                            </span>
                            <span class="badge-qty" style="display: block; margin-top: 3px;">Total Qty: <?php echo number_format($total_tahun_qty_ha, 2, ',', '.'); ?></span>
                        </td>
                        <td style="text-align: center; font-size: 12px; color: var(--slate-light);">
                            Grand Total: <strong><?php echo number_format($grand_total_tahun, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tanda Tangan Khusus Print -->
    <div class="print-signatures">
        <div class="sig-block">
            <p>Mengetahui,</p>
            <p><strong>Kepala Bidang Pelayanan Penunjang</strong></p>
            <br><br><br><br>
            <p><u>( .................................................. )</u></p>
            <p>NIP. ..................................................</p>
        </div>
        <div class="sig-block">
            <p><?php echo htmlspecialchars($kabupaten_instansi ?: 'Pringsewu'); ?>, <?php echo date('d-m-Y'); ?></p>
            <p><strong>Kepala Instalasi Farmasi</strong></p>
            <br><br><br><br>
            <p><u>( .................................................. )</u></p>
            <p>NIP. ..................................................</p>
        </div>
    </div>

</div>

<!-- Modal Rincian Detail Obat Per Bulan -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-window">
        <div class="modal-header">
            <h3 id="modalTitle">
                <i class="fas fa-list-alt" style="color: var(--primary);"></i>
                Rincian Obat Bulanan
            </h3>
            <button type="button" class="modal-close" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Memuat rincian data...</p>
            </div>
        </div>
        <div class="modal-footer">
            <div style="font-size: 12.5px; color: var(--slate);" id="modalFooterSummary">
                <!-- Summary rincian -->
            </div>
            <div style="display: flex; gap: 10px;" id="modalFooterActions">
                <button type="button" class="btn-action" onclick="closeDetailModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Data untuk Grafik Chart.js
const labelsBulan = [
    <?php foreach ($rekap_bulanan as $row): ?>
        "<?php echo $row['nama_bulan']; ?>",
    <?php endforeach; ?>
];

const dataLasa = [
    <?php foreach ($rekap_bulanan as $row): ?>
        <?php echo $row['jumlah_lasa']; ?>,
    <?php endforeach; ?>
];

const dataHa = [
    <?php foreach ($rekap_bulanan as $row): ?>
        <?php echo $row['jumlah_ha']; ?>,
    <?php endforeach; ?>
];

// Inisialisasi Chart.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsBulan,
            datasets: [
                {
                    label: 'Obat LASA',
                    data: dataLasa,
                    backgroundColor: 'rgba(217, 119, 6, 0.75)',
                    borderColor: '#d97706',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: '#b45309'
                },
                {
                    label: 'Obat High Alert',
                    data: dataHa,
                    backgroundColor: 'rgba(220, 38, 38, 0.75)',
                    borderColor: '#dc2626',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: '#b91c1c'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { family: 'Inter', size: 12.5, weight: '600' },
                        padding: 16,
                        usePointStyle: true,
                        boxWidth: 9
                    }
                },
                tooltip: {
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y.toLocaleString('id-ID')} transaksi`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Inter', size: 11.5 },
                        callback: function(value) {
                            return value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
});

// Fitur Pencarian Real-Time pada Tabel
document.getElementById('tableSearch').addEventListener('input', function() {
    const keyword = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tableBody tr');
    
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(keyword)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Fitur Modal Detail Rincian via AJAX
function showDetailBulan(tahun, bulan, jenis, namaBulan) {
    const modal = document.getElementById('detailModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalFooter = document.getElementById('modalFooterSummary');
    const modalActions = document.getElementById('modalFooterActions');
    
    const jenisTitle = (jenis === 'lasa') ? 'LASA (Look-Alike Sound-Alike)' : 'High Alert';
    const iconClass = (jenis === 'lasa') ? 'fa-exclamation-triangle' : 'fa-biohazard';
    const colorClass = (jenis === 'lasa') ? '#d97706' : '#dc2626';

    modalTitle.innerHTML = `<i class="fas ${iconClass}" style="color: ${colorClass};"></i> Rincian Obat ${jenisTitle} • Bulan ${namaBulan} ${tahun}`;
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 10px;">Sedang memuat data...</p>
        </div>
    `;
    modalFooter.innerHTML = '';
    modal.classList.add('active');

    fetch(`laporan_lasa_dan_high_alert_bulanan.php?action=ajax_detail_bulan&tahun=${encodeURIComponent(tahun)}&bulan=${encodeURIComponent(bulan)}&jenis=${encodeURIComponent(jenis)}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="far fa-folder-open fa-2x" style="margin-bottom: 8px;"></i>
                            <p>Tidak ada pemberian obat ${jenisTitle} pada bulan ${namaBulan} ${tahun}.</p>
                        </div>
                    `;
                    modalActions.innerHTML = `<button type="button" class="btn-action" onclick="closeDetailModal()">Tutup</button>`;
                    return;
                }

                let html = `
                    <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <input type="text" id="modalSearchInput" class="search-input" placeholder="Cari nama obat atau kode..." style="max-width: 300px;" oninput="filterModalTable()">
                        <span style="font-size: 12px; color: var(--slate-light);">Menampilkan <strong>${res.data.length}</strong> jenis obat</span>
                    </div>
                    <table class="report-table" id="modalDataTable" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 120px;">Kode Obat</th>
                                <th>Nama Obat</th>
                                <th style="width: 100px; text-align: right;">Frekuensi</th>
                                <th style="width: 100px; text-align: right;">Total Qty</th>
                                <th style="width: 80px;">Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                res.data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td style="text-align: center; color: #64748b;">${index + 1}</td>
                            <td><code>${item.kode_brng}</code></td>
                            <td><strong>${item.nama_brng}</strong></td>
                            <td style="text-align: right; font-weight: 700; color: #0284c7;">${Number(item.total_transaksi).toLocaleString('id-ID')}</td>
                            <td style="text-align: right; font-weight: 700; color: #16a34a;">${Number(item.total_qty).toLocaleString('id-ID')}</td>
                            <td>${item.kode_sat}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table>`;
                modalBody.innerHTML = html;
                modalFooter.innerHTML = `Total Jenis Obat: <strong>${res.total_item_obat}</strong> | Total Frekuensi: <strong>${Number(res.total_transaksi).toLocaleString('id-ID')}</strong> | Total Qty: <strong>${Number(res.total_qty).toLocaleString('id-ID')}</strong>`;
                
                modalActions.innerHTML = `
                    <a href="laporan_lasa_dan_high_alert.php?tanggal_awal=${res.tgl_awal}&tanggal_akhir=${res.tgl_akhir}" target="_blank" class="btn-action" style="background: #eff6ff; color: #1e40af; border-color: #bfdbfe;">
                        <i class="fas fa-external-link-alt"></i> Buka Rincian Harian Bulan Ini
                    </a>
                    <button type="button" class="btn-action" onclick="closeDetailModal()">Tutup</button>
                `;
            } else {
                modalBody.innerHTML = `<div style="color: #dc2626; padding: 20px;">Gagal mengambil data: ${res.message}</div>`;
                modalActions.innerHTML = `<button type="button" class="btn-action" onclick="closeDetailModal()">Tutup</button>`;
            }
        })
        .catch(err => {
            modalBody.innerHTML = `<div style="color: #dc2626; padding: 20px;">Terjadi kesalahan koneksi saat memuat data.</div>`;
            modalActions.innerHTML = `<button type="button" class="btn-action" onclick="closeDetailModal()">Tutup</button>`;
        });
}

function filterModalTable() {
    const input = document.getElementById('modalSearchInput');
    if (!input) return;
    const keyword = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#modalDataTable tbody tr');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        r.style.display = text.includes(keyword) ? '' : 'none';
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
}

// Tutup modal jika klik di luar box
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});

// Fitur Export ke Excel
document.getElementById('btnExportExcel').addEventListener('click', function() {
    const table = document.getElementById('tableLasaHaBulanan');
    const cloneTable = table.cloneNode(true);
    
    // Hapus badge-qty atau tombol aksi kolom terakhir sebelum export
    const badges = cloneTable.querySelectorAll('.badge-qty');
    badges.forEach(b => b.remove());
    
    // Hapus kolom ke-5 (Aksi) dari thead, tbody, tfoot
    const allRows = cloneTable.querySelectorAll('tr');
    allRows.forEach(row => {
        if (row.cells.length >= 5) {
            row.deleteCell(4);
        }
    });

    let html = cloneTable.outerHTML;
    
    const uri = 'data:application/vnd.ms-excel;base64,';
    const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Bulanan LASA & HA</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>' + html + '</body></html>';
    const base64 = (s) => window.btoa(unescape(encodeURIComponent(s)));

    const tahun = "<?php echo $tahun_dipilih; ?>";

    const link = document.createElement('a');
    link.href = uri + base64(template);
    link.download = `Laporan_Bulanan_LASA_dan_High_Alert_Tahun_${tahun}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

</body>
</html>
