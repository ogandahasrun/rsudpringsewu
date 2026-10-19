<?php
session_start();
include 'koneksi.php';

// Cek apakah request berupa AJAX detail
if (isset($_GET['action']) && $_GET['action'] === 'ajax_detail') {
    header('Content-Type: application/json; charset=utf-8');
    
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
    $jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
    
    // Validasi parameter
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !in_array($jenis, ['lasa', 'high_alert'])) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
        exit;
    }
    
    $tgl_esc = mysqli_real_escape_string($koneksi, $tanggal);
    $column_filter = ($jenis === 'lasa') ? "databarang_tambahan.lasa = '1'" : "databarang_tambahan.high_alert = '1'";
    
    $query_detail = "
        SELECT 
            detail_pemberian_obat.tgl_perawatan,
            detail_pemberian_obat.jam,
            detail_pemberian_obat.no_rawat,
            pasien.no_rkm_medis,
            pasien.nm_pasien,
            databarang.kode_brng,
            databarang.nama_brng,
            detail_pemberian_obat.jml,
            databarang.kode_sat
        FROM detail_pemberian_obat
        INNER JOIN reg_periksa ON detail_pemberian_obat.no_rawat = reg_periksa.no_rawat
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
        INNER JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
        WHERE detail_pemberian_obat.tgl_perawatan = '$tgl_esc'
          AND $column_filter
        ORDER BY detail_pemberian_obat.jam ASC, detail_pemberian_obat.no_rawat ASC
    ";
    
    $result_detail = mysqli_query($koneksi, $query_detail);
    if (!$result_detail) {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
        exit;
    }
    
    $items = [];
    $total_qty = 0;
    while ($row = mysqli_fetch_assoc($result_detail)) {
        $items[] = [
            'jam'         => substr($row['jam'], 0, 5),
            'no_rawat'    => $row['no_rawat'],
            'no_rkm_medis'=> $row['no_rkm_medis'],
            'nm_pasien'   => $row['nm_pasien'],
            'kode_brng'   => $row['kode_brng'],
            'nama_brng'   => $row['nama_brng'],
            'jml'         => (float)$row['jml'],
            'kode_sat'    => $row['kode_sat']
        ];
        $total_qty += (float)$row['jml'];
    }
    
    echo json_encode([
        'status'      => 'success',
        'tanggal'     => $tanggal,
        'jenis'       => $jenis,
        'label_jenis' => ($jenis === 'lasa') ? 'LASA (Look-Alike Sound-Alike)' : 'High Alert',
        'total_transaksi' => count($items),
        'total_qty'   => $total_qty,
        'data'        => $items
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

// Inisialisasi Periode Waktu
$default_awal = date('Y-m-01');
$default_akhir = date('Y-m-d');

$tanggal_awal = isset($_REQUEST['tanggal_awal']) && !empty($_REQUEST['tanggal_awal']) 
    ? $_REQUEST['tanggal_awal'] 
    : $default_awal;

$tanggal_akhir = isset($_REQUEST['tanggal_akhir']) && !empty($_REQUEST['tanggal_akhir']) 
    ? $_REQUEST['tanggal_akhir'] 
    : $default_akhir;

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_awal)) {
    $tanggal_awal = $default_awal;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir)) {
    $tanggal_akhir = $default_akhir;
}

// Pastikan tanggal awal <= tanggal akhir
if (strtotime($tanggal_awal) > strtotime($tanggal_akhir)) {
    $tmp = $tanggal_awal;
    $tanggal_awal = $tanggal_akhir;
    $tanggal_akhir = $tmp;
}

$tgl_awal_esc = mysqli_real_escape_string($koneksi, $tanggal_awal);
$tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tanggal_akhir);

// 1. QUERY JUMLAH OBAT LASA KELUAR PER TANGGAL
// Sesuai query pengguna dengan agregasi per tanggal perawatan
$query_lasa = "
    SELECT
        detail_pemberian_obat.tgl_perawatan,
        COUNT(detail_pemberian_obat.kode_brng) AS jumlah_lasa,
        SUM(detail_pemberian_obat.jml) AS qty_lasa
    FROM
        detail_pemberian_obat
    INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
    INNER JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
    WHERE
        detail_pemberian_obat.tgl_perawatan BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc' AND
        databarang_tambahan.lasa = '1'
    GROUP BY
        detail_pemberian_obat.tgl_perawatan
    ORDER BY
        detail_pemberian_obat.tgl_perawatan ASC
";

$res_lasa = mysqli_query($koneksi, $query_lasa);
$data_lasa = [];
if ($res_lasa) {
    while ($row = mysqli_fetch_assoc($res_lasa)) {
        $data_lasa[$row['tgl_perawatan']] = [
            'jumlah' => (int)$row['jumlah_lasa'],
            'qty'    => (float)$row['qty_lasa']
        ];
    }
}

// 2. QUERY JUMLAH OBAT HIGH ALERT KELUAR PER TANGGAL
// Sesuai query pengguna dengan agregasi per tanggal perawatan
$query_high_alert = "
    SELECT
        detail_pemberian_obat.tgl_perawatan,
        COUNT(detail_pemberian_obat.kode_brng) AS jumlah_ha,
        SUM(detail_pemberian_obat.jml) AS qty_ha
    FROM
        detail_pemberian_obat
    INNER JOIN databarang ON detail_pemberian_obat.kode_brng = databarang.kode_brng
    INNER JOIN databarang_tambahan ON databarang_tambahan.kode_brng = databarang.kode_brng
    WHERE
        detail_pemberian_obat.tgl_perawatan BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc' AND
        databarang_tambahan.high_alert = '1'
    GROUP BY
        detail_pemberian_obat.tgl_perawatan
    ORDER BY
        detail_pemberian_obat.tgl_perawatan ASC
";

$res_ha = mysqli_query($koneksi, $query_high_alert);
$data_ha = [];
if ($res_ha) {
    while ($row = mysqli_fetch_assoc($res_ha)) {
        $data_ha[$row['tgl_perawatan']] = [
            'jumlah' => (int)$row['jumlah_ha'],
            'qty'    => (float)$row['qty_ha']
        ];
    }
}

// 3. GENERATE SEMUA DERET TANGGAL PADA PERIODE YANG DIPILIH
// Termasuk tanggal tanpa pengeluaran obat LASA atau High Alert (nilai 0)
$deret_tanggal = [];
$current_time = strtotime($tanggal_awal);
$end_time = strtotime($tanggal_akhir);

$total_semua_lasa = 0;
$total_qty_lasa = 0;
$total_semua_ha = 0;
$total_qty_ha = 0;
$hari_ada_transaksi = 0;

$hari_indo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$bulan_indo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',   '05' => 'Mei',      '06' => 'Juni',
    '07' => 'Juli',    '08' => 'Agustus',  '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

function format_tgl_indo($tgl_str, $hari_indo, $bulan_indo) {
    $time = strtotime($tgl_str);
    $hari = $hari_indo[date('l', $time)] ?? '';
    $tgl = date('d', $time);
    $bln = $bulan_indo[date('m', $time)] ?? date('m', $time);
    $thn = date('Y', $time);
    return "$hari, $tgl $bln $thn";
}

while ($current_time <= $end_time) {
    $tgl_str = date('Y-m-d', $current_time);
    
    $jml_l = isset($data_lasa[$tgl_str]) ? $data_lasa[$tgl_str]['jumlah'] : 0;
    $qty_l = isset($data_lasa[$tgl_str]) ? $data_lasa[$tgl_str]['qty'] : 0;
    
    $jml_h = isset($data_ha[$tgl_str]) ? $data_ha[$tgl_str]['jumlah'] : 0;
    $qty_h = isset($data_ha[$tgl_str]) ? $data_ha[$tgl_str]['qty'] : 0;
    
    $deret_tanggal[] = [
        'tanggal'     => $tgl_str,
        'tanggal_fmt' => format_tgl_indo($tgl_str, $hari_indo, $bulan_indo),
        'jumlah_lasa' => $jml_l,
        'qty_lasa'    => $qty_l,
        'jumlah_ha'   => $jml_h,
        'qty_ha'      => $qty_h,
        'total_hari'  => $jml_l + $jml_h
    ];
    
    $total_semua_lasa += $jml_l;
    $total_qty_lasa += $qty_l;
    $total_semua_ha += $jml_h;
    $total_qty_ha += $qty_h;
    
    if (($jml_l + $jml_h) > 0) {
        $hari_ada_transaksi++;
    }
    
    $current_time = strtotime('+1 day', $current_time);
}

$jumlah_hari_total = count($deret_tanggal);
$grand_total_obat = $total_semua_lasa + $total_semua_ha;
$rata_per_hari = $jumlah_hari_total > 0 ? round($grand_total_obat / $jumlah_hari_total, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemakaian Obat LASA & High Alert - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
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
            width: 200px;
            height: 200px;
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
        }

        .btn-nav {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 10px 18px;
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
            min-width: 170px;
        }

        .form-group label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-control {
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            color: #1e293b;
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
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        .pill-label {
            font-size: 12px;
            color: var(--slate-light);
            font-weight: 600;
            margin-right: 4px;
        }

        .pill-btn {
            background: #f1f5f9;
            color: var(--slate);
            border: 1px solid #e2e8f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
            gap: 10px;
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
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        table.report-table tbody td {
            padding: 13px 16px;
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

        table.report-table tbody tr.weekend-row {
            background-color: #fafbfc;
        }

        table.report-table tfoot td {
            padding: 14px 16px;
            background: #f1f5f9;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #cbd5e1;
            font-size: 14px;
        }

        /* Number Badges */
        .badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            min-width: 44px;
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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .badge-qty {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-left: 6px;
        }

        .date-badge {
            display: inline-flex;
            flex-direction: column;
        }

        .date-day {
            font-weight: 700;
            color: #1e293b;
        }

        .date-raw {
            font-size: 11px;
            color: #64748b;
            font-family: monospace;
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
            font-size: 18px;
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
        }

        /* Print Styling */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                font-size: 11pt !important;
                color: #000000 !important;
            }
            .page-header, .filter-card, .table-toolbar, .btn-nav, .modal-overlay {
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
                padding: 6px 8px !important;
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
        }

        .print-header {
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
        <h4 style="margin-top: 10px; text-decoration: underline;">LAPORAN PENGELUARAN OBAT LASA DAN HIGH ALERT</h4>
        <p style="font-size: 10pt;">Periode: <?php echo date('d-m-Y', strtotime($tanggal_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tanggal_akhir)); ?></p>
    </div>

    <!-- Web Page Header -->
    <div class="page-header">
        <div class="header-left">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo Instansi" class="header-logo">
            <div class="header-title">
                <h1>
                    <i class="fas fa-pills" style="color: #38bdf8;"></i>
                    Laporan Obat LASA & High Alert
                </h1>
                <p><?php echo htmlspecialchars($nama_instansi); ?> • Pemantauan Pengeluaran Obat LASA dan High Alert Berdasarkan Detail Pemberian Obat</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="laporan_lasa_dan_high_alert.php" class="btn-nav active-link">
                <i class="fas fa-calendar-day"></i>
                Laporan Harian
            </a>
            <a href="laporan_lasa_dan_high_alert_bulanan.php" class="btn-nav">
                <i class="fas fa-calendar-alt"></i>
                Laporan Bulanan
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

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form" id="formFilter">
            <div class="form-group">
                <label for="tanggal_awal"><i class="far fa-calendar-alt"></i> Tanggal Awal</label>
                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" required value="<?php echo htmlspecialchars($tanggal_awal); ?>">
            </div>
            <div class="form-group">
                <label for="tanggal_akhir"><i class="far fa-calendar-alt"></i> Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" required value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-filter"></i>
                Tampilkan Data
            </button>
            <a href="laporan_lasa_dan_high_alert.php" class="btn-action" style="padding: 11px 16px;">
                <i class="fas fa-sync-alt"></i>
                Reset
            </a>
        </form>

        <!-- Quick Filter Pills -->
        <div class="quick-filter-pills">
            <span class="pill-label"><i class="fas fa-bolt"></i> Filter Cepat:</span>
            <?php 
                $today = date('Y-m-d');
                $start_month = date('Y-m-01');
                $end_month = date('Y-m-t');
                $start_last_month = date('Y-m-01', strtotime('-1 month'));
                $end_last_month = date('Y-m-t', strtotime('-1 month'));
                $seven_days_ago = date('Y-m-d', strtotime('-6 days'));
                $thirty_days_ago = date('Y-m-d', strtotime('-29 days'));
            ?>
            <button type="button" class="pill-btn <?php echo ($tanggal_awal == $today && $tanggal_akhir == $today) ? 'active' : ''; ?>" onclick="setQuickDate('<?php echo $today; ?>', '<?php echo $today; ?>')">Hari Ini</button>
            <button type="button" class="pill-btn <?php echo ($tanggal_awal == $seven_days_ago && $tanggal_akhir == $today) ? 'active' : ''; ?>" onclick="setQuickDate('<?php echo $seven_days_ago; ?>', '<?php echo $today; ?>')">7 Hari Terakhir</button>
            <button type="button" class="pill-btn <?php echo ($tanggal_awal == $thirty_days_ago && $tanggal_akhir == $today) ? 'active' : ''; ?>" onclick="setQuickDate('<?php echo $thirty_days_ago; ?>', '<?php echo $today; ?>')">30 Hari Terakhir</button>
            <button type="button" class="pill-btn <?php echo ($tanggal_awal == $start_month && $tanggal_akhir == $end_month) ? 'active' : ''; ?>" onclick="setQuickDate('<?php echo $start_month; ?>', '<?php echo $end_month; ?>')">Bulan Ini</button>
            <button type="button" class="pill-btn <?php echo ($tanggal_awal == $start_last_month && $tanggal_akhir == $end_last_month) ? 'active' : ''; ?>" onclick="setQuickDate('<?php echo $start_last_month; ?>', '<?php echo $end_last_month; ?>')">Bulan Lalu</button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="far fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Deret Tanggal</span>
                <span class="stat-value"><?php echo number_format($jumlah_hari_total, 0, ',', '.'); ?> Hari</span>
                <span class="stat-subtext"><?php echo $hari_ada_transaksi; ?> hari aktif pengeluaran</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Obat LASA</span>
                <span class="stat-value" style="color: var(--lasa-color);"><?php echo number_format($total_semua_lasa, 0, ',', '.'); ?></span>
                <span class="stat-subtext">Total Qty Fisik: <?php echo number_format($total_qty_lasa, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">
                <i class="fas fa-biohazard"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Obat High Alert</span>
                <span class="stat-value" style="color: var(--ha-color);"><?php echo number_format($total_semua_ha, 0, ',', '.'); ?></span>
                <span class="stat-subtext">Total Qty Fisik: <?php echo number_format($total_qty_ha, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Rata-rata per Hari</span>
                <span class="stat-value"><?php echo number_format($rata_per_hari, 1, ',', '.'); ?></span>
                <span class="stat-subtext">Grand Total: <?php echo number_format($grand_total_obat, 0, ',', '.'); ?> obat</span>
            </div>
        </div>
    </div>

    <!-- Toolbar: Search & Export -->
    <div class="table-toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearch" class="search-input" placeholder="Cari tanggal atau hari...">
            </div>
            <span style="font-size: 12.5px; color: var(--slate-light);">
                Menampilkan <strong><?php echo $jumlah_hari_total; ?></strong> hari
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

    <!-- Main Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="report-table" id="tableLasaHa">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">No</th>
                        <th style="width: 280px;">Tanggal</th>
                        <th style="text-align: center; width: 350px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: #b45309;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Jumlah Obat LASA
                            </span>
                        </th>
                        <th style="text-align: center; width: 350px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; color: #b91c1c;">
                                <i class="fas fa-biohazard"></i>
                                Jumlah Obat High Alert
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($deret_tanggal)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: var(--slate-light);">
                                <i class="far fa-folder-open fa-2x" style="margin-bottom: 8px; display: block;"></i>
                                Tidak ada data deret tanggal pada periode yang dipilih.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($deret_tanggal as $row): 
                            $is_weekend = (date('N', strtotime($row['tanggal'])) >= 6);
                        ?>
                            <tr class="<?php echo $is_weekend ? 'weekend-row' : ''; ?>" data-tgl="<?php echo $row['tanggal']; ?>">
                                <td style="text-align: center; font-weight: 600; color: #64748b;"><?php echo $no++; ?></td>
                                <td>
                                    <div class="date-badge">
                                        <span class="date-day"><?php echo $row['tanggal_fmt']; ?></span>
                                        <span class="date-raw"><?php echo $row['tanggal']; ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($row['jumlah_lasa'] > 0): ?>
                                        <span class="badge-count lasa clickable" 
                                              onclick="showDetail('<?php echo $row['tanggal']; ?>', 'lasa', '<?php echo addslashes($row['tanggal_fmt']); ?>')"
                                              title="Klik untuk melihat rincian obat LASA">
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
                                              onclick="showDetail('<?php echo $row['tanggal']; ?>', 'high_alert', '<?php echo addslashes($row['tanggal_fmt']); ?>')"
                                              title="Klik untuk melihat rincian obat High Alert">
                                            <i class="fas fa-shield-virus"></i>
                                            <?php echo number_format($row['jumlah_ha'], 0, ',', '.'); ?>
                                        </span>
                                        <span class="badge-qty">(Qty: <?php echo number_format($row['qty_ha'], 2, ',', '.'); ?>)</span>
                                    <?php else: ?>
                                        <span class="badge-count zero">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right; padding-right: 24px;">TOTAL PERIODE :</td>
                        <td style="text-align: center; color: var(--lasa-color);">
                            <span class="badge-count lasa" style="font-size: 14px; padding: 6px 16px;">
                                <?php echo number_format($total_semua_lasa, 0, ',', '.'); ?>
                            </span>
                            <span class="badge-qty" style="display: block; margin-top: 3px;">Total Qty: <?php echo number_format($total_qty_lasa, 2, ',', '.'); ?></span>
                        </td>
                        <td style="text-align: center; color: var(--ha-color);">
                            <span class="badge-count ha" style="font-size: 14px; padding: 6px 16px;">
                                <?php echo number_format($total_semua_ha, 0, ',', '.'); ?>
                            </span>
                            <span class="badge-qty" style="display: block; margin-top: 3px;">Total Qty: <?php echo number_format($total_qty_ha, 2, ',', '.'); ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<!-- Modal Rincian Detail Obat -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-window">
        <div class="modal-header">
            <h3 id="modalTitle">
                <i class="fas fa-list-alt" style="color: var(--primary);"></i>
                Rincian Obat Keluar
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
            <button type="button" class="btn-action" onclick="closeDetailModal()">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
// Fungsi Filter Cepat Tanggal
function setQuickDate(start, end) {
    document.getElementById('tanggal_awal').value = start;
    document.getElementById('tanggal_akhir').value = end;
    document.getElementById('formFilter').submit();
}

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
function showDetail(tanggal, jenis, tanggalFmt) {
    const modal = document.getElementById('detailModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalFooter = document.getElementById('modalFooterSummary');
    
    const jenisTitle = (jenis === 'lasa') ? 'LASA (Look-Alike Sound-Alike)' : 'High Alert';
    const iconClass = (jenis === 'lasa') ? 'fa-exclamation-triangle' : 'fa-biohazard';
    const colorClass = (jenis === 'lasa') ? '#d97706' : '#dc2626';

    modalTitle.innerHTML = `<i class="fas ${iconClass}" style="color: ${colorClass};"></i> Rincian Obat ${jenisTitle} • ${tanggalFmt}`;
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 10px;">Sedang memuat data...</p>
        </div>
    `;
    modalFooter.innerHTML = '';
    modal.classList.add('active');

    fetch(`laporan_lasa_dan_high_alert.php?action=ajax_detail&tanggal=${encodeURIComponent(tanggal)}&jenis=${encodeURIComponent(jenis)}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="far fa-folder-open fa-2x" style="margin-bottom: 8px;"></i>
                            <p>Tidak ada pemberian obat tercatat pada tanggal ini.</p>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <table class="report-table" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 75px;">Jam</th>
                                <th style="width: 140px;">No. Rawat</th>
                                <th style="width: 180px;">Pasien</th>
                                <th style="width: 110px;">Kode Barang</th>
                                <th>Nama Obat</th>
                                <th style="width: 90px; text-align: right;">Jumlah</th>
                                <th style="width: 70px;">Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                res.data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td style="text-align: center; color: #64748b;">${index + 1}</td>
                            <td><code>${item.jam}</code></td>
                            <td><code>${item.no_rawat}</code></td>
                            <td>
                                <strong>${item.nm_pasien}</strong>
                                <div style="font-size: 11px; color: #64748b;">RM: ${item.no_rkm_medis}</div>
                            </td>
                            <td><code>${item.kode_brng}</code></td>
                            <td><strong>${item.nama_brng}</strong></td>
                            <td style="text-align: right; font-weight: 700; color: #0284c7;">${item.jml}</td>
                            <td>${item.kode_sat}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table>`;
                modalBody.innerHTML = html;
                modalFooter.innerHTML = `Total Transaksi: <strong>${res.total_transaksi}</strong> | Total Qty: <strong>${res.total_qty}</strong>`;
            } else {
                modalBody.innerHTML = `<div style="color: #dc2626; padding: 20px;">Gagal mengambil data: ${res.message}</div>`;
            }
        })
        .catch(err => {
            modalBody.innerHTML = `<div style="color: #dc2626; padding: 20px;">Terjadi kesalahan koneksi saat memuat data.</div>`;
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
    const table = document.getElementById('tableLasaHa');
    // Salin tabel untuk dibersihkan sebelum diexport
    const cloneTable = table.cloneNode(true);
    
    // Hapus tombol atau badge click event dari salinan
    const badges = cloneTable.querySelectorAll('.badge-qty');
    badges.forEach(b => b.remove());

    let html = cloneTable.outerHTML;
    
    const uri = 'data:application/vnd.ms-excel;base64,';
    const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan LASA dan High Alert</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>' + html + '</body></html>';
    const base64 = (s) => window.btoa(unescape(encodeURIComponent(s)));

    const tglAwal = document.getElementById('tanggal_awal').value;
    const tglAkhir = document.getElementById('tanggal_akhir').value;

    const link = document.createElement('a');
    link.href = uri + base64(template);
    link.download = `Laporan_LASA_dan_High_Alert_${tglAwal}_sd_${tglAkhir}.xls`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

</body>
</html>
