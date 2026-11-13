<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// =========================================================================
// AJAX ENDPOINT: Ambil Riwayat Pemberian Obat Sebelumnya
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_riwayat_obat') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $no_rkm_medis = trim($_GET['no_rkm_medis'] ?? '');
    $kode_brng    = trim($_GET['kode_brng'] ?? '');

    if ($no_rkm_medis === '' || $kode_brng === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Parameter no_rkm_medis dan kode_brng wajib diisi.'
        ]);
        exit;
    }

    $sqlRiwayat = "SELECT
                        d.tgl_perawatan,
                        d.jam,
                        d.no_rawat,
                        d.kode_brng,
                        b.nama_brng,
                        b.kode_sat,
                        d.jml,
                        d.status,
                        CASE
                            WHEN d.status = 'Ranap' THEN COALESCE(bng.nm_bangsal, pol.nm_poli, 'Rawat Inap')
                            ELSE COALESCE(pol.nm_poli, 'Rawat Jalan')
                        END AS unit_layanan
                   FROM detail_pemberian_obat d
                   INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
                   INNER JOIN databarang b ON d.kode_brng = b.kode_brng
                   LEFT JOIN poliklinik pol ON r.kd_poli = pol.kd_poli
                   LEFT JOIN kamar_inap ki ON d.no_rawat = ki.no_rawat
                   LEFT JOIN kamar km ON ki.kd_kamar = km.kd_kamar
                   LEFT JOIN bangsal bng ON km.kd_bangsal = bng.kd_bangsal
                   WHERE r.no_rkm_medis = ?
                     AND d.kode_brng = ?
                   GROUP BY d.tgl_perawatan, d.jam, d.no_rawat, d.kode_brng
                   ORDER BY d.tgl_perawatan DESC, d.jam DESC
                   LIMIT 50";

    $stmtRw = mysqli_prepare($koneksi, $sqlRiwayat);
    if (!$stmtRw) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)
        ]);
        exit;
    }

    mysqli_stmt_bind_param($stmtRw, 'ss', $no_rkm_medis, $kode_brng);
    mysqli_stmt_execute($stmtRw);
    $resRw = mysqli_stmt_get_result($stmtRw);

    $items = [];
    while ($rw = mysqli_fetch_assoc($resRw)) {
        $items[] = $rw;
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    exit;
}

// =========================================================================
// PARAMETER FILTER
// =========================================================================
$tgl_awal    = isset($_GET['tgl_awal'])    && !empty($_GET['tgl_awal'])    ? $_GET['tgl_awal']    : date('Y-m-d');
$tgl_akhir   = isset($_GET['tgl_akhir'])   && !empty($_GET['tgl_akhir'])   ? $_GET['tgl_akhir']   : date('Y-m-d');
$filter_hari = isset($_GET['filter_hari']) && !empty($_GET['filter_hari']) ? $_GET['filter_hari'] : 'all';
$kd_poli     = isset($_GET['kd_poli'])     ? trim($_GET['kd_poli'])     : '';
$keyword     = isset($_GET['keyword'])     ? trim($_GET['keyword'])     : '';

// Label Map untuk Opsi Filter Hari
$hariLabelMap = [
    'all' => '< 7 Hari (0 - 6 Hari)',
    '0'   => 'Hari yang Sama (0 Hari)',
    '1_2' => '1 - 2 Hari',
    '3_4' => '3 - 4 Hari',
    '5_6' => '5 - 6 Hari',
];

$havingClause = 'selisih_hari BETWEEN 0 AND 6';
if ($filter_hari === '0') {
    $havingClause = 'selisih_hari = 0';
} elseif ($filter_hari === '1_2') {
    $havingClause = 'selisih_hari BETWEEN 1 AND 2';
} elseif ($filter_hari === '3_4') {
    $havingClause = 'selisih_hari BETWEEN 3 AND 4';
} elseif ($filter_hari === '5_6') {
    $havingClause = 'selisih_hari BETWEEN 5 AND 6';
} else {
    $filter_hari = 'all';
}

// Ambil daftar poliklinik untuk filter dropdown
$poliList = [];
$resPoli = mysqli_query($koneksi, "SELECT kd_poli, nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli ASC");
if ($resPoli) {
    while ($pl = mysqli_fetch_assoc($resPoli)) {
        $poliList[] = $pl;
    }
}

// =========================================================================
// QUERY DATA KONTROL OBAT BERULANG
// =========================================================================
$params = [];
$types  = '';

// Parameter untuk detail_pemberian_obat range dan resep_obat range
$params[] = $tgl_awal;
$params[] = $tgl_akhir;
$params[] = $tgl_awal;
$params[] = $tgl_akhir;
$types   .= 'ssss';

$whereExtra = '';
if ($kd_poli !== '') {
    $whereExtra .= " AND reg_cur.kd_poli = ?";
    $params[]    = $kd_poli;
    $types      .= 's';
}
if ($keyword !== '') {
    $whereExtra .= " AND (reg_cur.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR rd.kode_brng LIKE ? OR b.nama_brng LIKE ?)";
    $kw          = "%$keyword%";
    $params[]    = $kw;
    $params[]    = $kw;
    $params[]    = $kw;
    $params[]    = $kw;
    $types      .= 'ssss';
}

$sqlMain = "SELECT
                ro.no_resep,
                ro.tgl_peresepan,
                ro.jam_peresepan,
                ro.no_rawat AS no_rawat_resep,
                reg_cur.no_rkm_medis,
                p.nm_pasien,
                reg_cur.kd_poli,
                pol.nm_poli,
                ro.kd_dokter,
                dok.nm_dokter,
                rd.kode_brng,
                b.nama_brng,
                b.kode_sat,
                rd.jml AS jml_resep,
                rd.aturan_pakai,
                MAX(d.tgl_perawatan) AS tgl_beri_terakhir,
                DATEDIFF(ro.tgl_peresepan, MAX(d.tgl_perawatan)) AS selisih_hari,
                COUNT(DISTINCT d.tgl_perawatan) AS total_hari_sebelumnya
            FROM resep_obat ro
            INNER JOIN resep_dokter rd ON ro.no_resep = rd.no_resep
            INNER JOIN reg_periksa reg_cur ON ro.no_rawat = reg_cur.no_rawat
            INNER JOIN pasien p ON reg_cur.no_rkm_medis = p.no_rkm_medis
            INNER JOIN databarang b ON rd.kode_brng = b.kode_brng
            LEFT JOIN poliklinik pol ON reg_cur.kd_poli = pol.kd_poli
            LEFT JOIN dokter dok ON ro.kd_dokter = dok.kd_dokter
            INNER JOIN reg_periksa reg_prev ON reg_prev.no_rkm_medis = reg_cur.no_rkm_medis
            INNER JOIN detail_pemberian_obat d ON d.no_rawat = reg_prev.no_rawat
                                              AND d.kode_brng = rd.kode_brng
                                              AND d.tgl_perawatan BETWEEN DATE_SUB(?, INTERVAL 6 DAY) AND ?
            WHERE ro.status = 'ralan'
              AND ro.tgl_peresepan BETWEEN ? AND ?
              AND (d.no_rawat != ro.no_rawat OR d.tgl_perawatan < ro.tgl_peresepan)
              AND DATEDIFF(ro.tgl_peresepan, d.tgl_perawatan) >= 0
              AND DATEDIFF(ro.tgl_peresepan, d.tgl_perawatan) < 7
              $whereExtra
            GROUP BY ro.no_resep, rd.kode_brng
            HAVING $havingClause
            ORDER BY ro.tgl_peresepan DESC, ro.jam_peresepan DESC, selisih_hari ASC";

$stmtMain = mysqli_prepare($koneksi, $sqlMain);
mysqli_stmt_bind_param($stmtMain, $types, ...$params);
mysqli_stmt_execute($stmtMain);
$resMain = mysqli_stmt_get_result($stmtMain);

$rowsData = [];
$totalHari0 = 0;
$totalHari1_3 = 0;
$totalHari4_6 = 0;
$pasienUnik = [];

if ($resMain) {
    while ($r = mysqli_fetch_assoc($resMain)) {
        $rowsData[] = $r;
        $sh = intval($r['selisih_hari']);
        if ($sh === 0) {
            $totalHari0++;
        } elseif ($sh >= 1 && $sh <= 3) {
            $totalHari1_3++;
        } else {
            $totalHari4_6++;
        }
        $pasienUnik[$r['no_rkm_medis']] = true;
    }
}
$totalRows = count($rowsData);
$totalPasienUnik = count($pasienUnik);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Pemberian Obat Berulang &lt; 7 Hari - RSUD Pringsewu</title>
    <meta name="description" content="Kontrol preventif resep obat rawat jalan yang pernah diberikan kepada pasien dalam waktu kurang dari 7 hari">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:       #0284c7;
            --primary-light: #38bdf8;
            --primary-dark:  #0369a1;
            --danger:        #dc2626;
            --danger-bg:     #fee2e2;
            --danger-border: #fca5a5;
            --warning:       #d97706;
            --warning-bg:    #fef3c7;
            --amber:         #f59e0b;
            --success:       #16a34a;
            --card-bg:       #ffffff;
            --text:          #1e293b;
            --text-muted:    #64748b;
            --border:        #e2e8f0;
            --shadow:        0 4px 24px rgba(2, 132, 199, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding-bottom: 60px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, #0f172a 0%, #0369a1 50%, #0284c7 100%);
            padding: 30px 24px 26px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 22px rgba(2, 132, 199, 0.28);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50px; right: -40px;
            width: 220px; height: 220px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -30px;
            width: 250px; height: 250px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }
        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(220, 38, 38, 0.25);
            border: 1px solid rgba(248, 113, 113, 0.4);
            color: #fecaca;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .page-header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .page-header .subtitle {
            margin: 6px 0 0;
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.88);
            position: relative;
            z-index: 1;
        }

        /* CONTAINER */
        .container {
            max-width: 1440px;
            margin: 22px auto 0;
            padding: 0 24px;
        }

        .back-nav {
            margin-bottom: 14px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff;
            color: #0369a1;
            border: 1.5px solid #bae6fd;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.08);
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #0284c7;
            color: #fff;
            border-color: #0284c7;
            transform: translateX(-3px);
        }

        /* FILTER CARD */
        .filter-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 22px;
        }
        .filter-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            align-items: flex-end;
        }
        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-field label {
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .filter-field input,
        .filter-field select {
            padding: 8.5px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.86rem;
            font-family: inherit;
            color: var(--text);
            background: #f8fafc;
            transition: all 0.2s;
        }
        .filter-field input:focus,
        .filter-field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
            background: #fff;
        }
        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8.5px 16px;
            font-size: 0.84rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-sm { padding: 5.5px 10px; font-size: 0.78rem; border-radius: 6px; }
        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35); }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: #e2e8f0; color: var(--text); }
        .btn-success {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.22);
        }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22, 163, 74, 0.32); }
        .btn-info {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: #fff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
        }
        .btn-info:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); }

        /* STATS CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
        }
        .stat-card.blue::before   { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .stat-card.indigo::before { background: linear-gradient(90deg, #4f46e5, #818cf8); }
        .stat-card.red::before    { background: linear-gradient(90deg, #dc2626, #f87171); }
        .stat-card.amber::before  { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .stat-card.teal::before   { background: linear-gradient(90deg, #0f766e, #14b8a6); }
        .stat-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.15; }
        .stat-card.blue .stat-value   { color: #0284c7; }
        .stat-card.indigo .stat-value { color: #4f46e5; }
        .stat-card.red .stat-value    { color: #dc2626; }
        .stat-card.amber .stat-value  { color: #d97706; }
        .stat-card.teal .stat-value   { color: #0f766e; }

        /* TABLE CARD */
        .table-card {
            background: var(--card-bg);
            border-radius: 14px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 22px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }
        .table-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-subtitle { font-size: 0.81rem; color: var(--text-muted); margin-top: 2px; }
        .table-scroll { overflow-x: auto; }
        .table-scroll::-webkit-scrollbar { height: 7px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        table#mainTable {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.84rem;
        }
        table#mainTable thead th {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            padding: 12px 12px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
            white-space: nowrap;
            border: none;
        }
        table#mainTable tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        table#mainTable tbody tr:last-child { border-bottom: none; }
        table#mainTable tbody tr:nth-child(even) { background: #f8fafc; }
        table#mainTable tbody tr:hover { background: #f0f9ff; }
        table#mainTable td {
            padding: 11px 12px;
            vertical-align: top;
            color: var(--text);
            border: none;
        }

        .td-num  { text-align: center; color: var(--text-muted); font-size: 0.78rem; width: 38px; }
        .td-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8rem; font-weight: 600; color: #0369a1; }
        .patient-box strong { color: #0f172a; font-size: 0.88rem; }
        .patient-box .rm-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .obat-title { font-weight: 700; color: #0f172a; }
        .obat-sub   { font-size: 0.77rem; color: var(--text-muted); margin-top: 2px; }
        .aturan-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-style: italic;
            margin-top: 3px;
        }

        /* Pill Badge Selisih Hari */
        .pill-selisih {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 0.76rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .pill-hari-0 {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid var(--danger-border);
            animation: pulseWarning 2s infinite;
        }
        @keyframes pulseWarning {
            0%   { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
            70%  { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
        .pill-hari-1_2 {
            background-color: #ffedd5;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }
        .pill-hari-3_4 {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .pill-hari-5_6 {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* EMPTY STATE */
        .empty-state {
            padding: 60px 24px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; color: #94a3b8; margin-bottom: 12px; }
        .empty-state h3 { margin: 0 0 6px; font-size: 1.1rem; color: var(--text); }
        .empty-state p { margin: 0; font-size: 0.88rem; }

        /* MODAL POPUP */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(2px);
            overflow-y: auto;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .modal-card {
            background: #fff;
            border-radius: 12px;
            max-width: 820px;
            width: 100%;
            margin: 24px auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: modalPop 0.2s ease-out;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: translateY(-10px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-header {
            background: linear-gradient(135deg, #0369a1, #0284c7);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .modal-close {
            background: rgba(255, 255, 255, 0.18);
            border: none;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover { background: rgba(255, 255, 255, 0.3); }
        .modal-body { padding: 20px; }
        .patient-summary {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-left: 4px solid #0284c7;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .riwayat-table-wrap {
            max-height: 420px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
        }
        .riwayat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.83rem;
        }
        .riwayat-table th,
        .riwayat-table td {
            border-bottom: 1px solid var(--border);
            padding: 9px 12px;
            text-align: left;
        }
        .riwayat-table th {
            background: #f1f5f9;
            color: var(--text);
            font-weight: 600;
            font-size: 0.76rem;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .riwayat-table tbody tr:hover { background: #f8fafc; }
        .riwayat-table td.text-right,
        .riwayat-table th.text-right { text-align: right; }
        .riwayat-table td.text-center,
        .riwayat-table th.text-center { text-align: center; }
        .riwayat-table tfoot td {
            background: #f0f9ff;
            color: #0369a1;
            font-weight: 700;
            border-top: 2px solid #bae6fd;
        }
        .modal-footer {
            padding: 12px 20px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* TOAST */
        #toast {
            position: fixed;
            bottom: 28px; right: 28px;
            background: #16a34a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(22, 163, 74, 0.35);
            display: none;
            z-index: 9999;
        }

        /* PRINT STYLES */
        @media print {
            body { background: #fff; padding: 0; }
            .page-header { background: #0369a1 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 15px; }
            .back-nav, .filter-card, .btn, .no-copy { display: none !important; }
            .table-card { box-shadow: none; border: none; }
            table#mainTable thead th { background: #334155 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table#mainTable th, table#mainTable td { font-size: 9.5px; padding: 5px; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="header-badge"><i class="fas fa-shield-alt"></i> Pengendalian &amp; Pencegahan Farmasi</div>
    <h1><i class="fas fa-pills"></i> Kontrol Pemberian Obat Berulang</h1>
    <p class="subtitle">Memantau resep rawat jalan yang pernah diberikan kepada pasien dalam kurun waktu &lt; 7 hari terakhir</p>
</div>

<div class="container">
    <div class="back-nav">
        <a href="farmasi.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi
        </a>
    </div>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="fas fa-filter" style="color:var(--primary);"></i> Parameter Pencarian &amp; Filter
        </div>
        <form method="GET" action="" id="filterForm">
            <div class="filter-grid">
                <div class="filter-field">
                    <label for="tgl_awal"><i class="fas fa-calendar-alt"></i> Tanggal Mulai Resep</label>
                    <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>" required>
                </div>
                <div class="filter-field">
                    <label for="tgl_akhir"><i class="fas fa-calendar-day"></i> Tanggal Akhir Resep</label>
                    <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                </div>
                <div class="filter-field">
                    <label for="filter_hari"><i class="fas fa-history"></i> Rentang Selisih Hari</label>
                    <select name="filter_hari" id="filter_hari">
                        <option value="all" <?php echo ($filter_hari === 'all') ? 'selected' : ''; ?>>&lt; 7 Hari (Semua)</option>
                        <option value="0"   <?php echo ($filter_hari === '0')   ? 'selected' : ''; ?>>Hari yang Sama (0 Hari)</option>
                        <option value="1_2" <?php echo ($filter_hari === '1_2') ? 'selected' : ''; ?>>1 - 2 Hari</option>
                        <option value="3_4" <?php echo ($filter_hari === '3_4') ? 'selected' : ''; ?>>3 - 4 Hari</option>
                        <option value="5_6" <?php echo ($filter_hari === '5_6') ? 'selected' : ''; ?>>5 - 6 Hari</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="kd_poli"><i class="fas fa-clinic-medical"></i> Poliklinik</label>
                    <select name="kd_poli" id="kd_poli">
                        <option value="">-- Semua Poliklinik --</option>
                        <?php foreach ($poliList as $pl): ?>
                            <option value="<?php echo htmlspecialchars($pl['kd_poli']); ?>" <?php echo ($kd_poli === $pl['kd_poli']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pl['nm_poli']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="keyword"><i class="fas fa-search"></i> Cari Pasien / Obat</label>
                    <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="No RM / Nama / Kode / Obat">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-row">
        <div class="stat-card blue">
            <span class="stat-label">Total Resep Berulang</span>
            <span class="stat-value"><?php echo $totalRows; ?></span>
        </div>
        <div class="stat-card indigo">
            <span class="stat-label">Total Pasien Terdampak</span>
            <span class="stat-value"><?php echo $totalPasienUnik; ?></span>
        </div>
        <div class="stat-card red">
            <span class="stat-label">Selisih 0 Hari (Hari Sama)</span>
            <span class="stat-value"><?php echo $totalHari0; ?></span>
        </div>
        <div class="stat-card amber">
            <span class="stat-label">Selisih 1 &ndash; 3 Hari</span>
            <span class="stat-value"><?php echo $totalHari1_3; ?></span>
        </div>
        <div class="stat-card teal">
            <span class="stat-label">Selisih 4 &ndash; 6 Hari</span>
            <span class="stat-value"><?php echo $totalHari4_6; ?></span>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">
                    <i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i>
                    Daftar Resep Pasien yang Pernah Diberikan Obat Sama (&lt; 7 Hari)
                </div>
                <div class="table-subtitle">
                    Periode Resep: <?php echo date('d/m/Y', strtotime($tgl_awal)); ?> &ndash; <?php echo date('d/m/Y', strtotime($tgl_akhir)); ?>
                    &bull; Kriteria: <?php echo htmlspecialchars($hariLabelMap[$filter_hari] ?? '< 7 Hari'); ?>
                    &bull; <?php echo $totalRows; ?> item terdeteksi
                </div>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if ($totalRows > 0): ?>
                <button type="button" class="btn btn-success btn-sm" onclick="copyTable()">
                    <i class="fas fa-copy"></i> Copy Tabel
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($totalRows > 0): ?>
        <div class="table-scroll">
            <table id="mainTable">
                <thead>
                    <tr>
                        <th class="td-num">No</th>
                        <th>Tgl &amp; Jam Resep</th>
                        <th>No. Resep</th>
                        <th>No. Rawat</th>
                        <th>Pasien (No. RM)</th>
                        <th>Poliklinik</th>
                        <th>Dokter Penulis</th>
                        <th>Obat Diresepkan</th>
                        <th>Tgl Diberikan Terakhir</th>
                        <th style="text-align:center;">Selisih Hari</th>
                        <th class="no-copy" style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                foreach ($rowsData as $row):
                    $sh = intval($row['selisih_hari']);
                    if ($sh === 0) {
                        $pillClass = 'pill-hari-0';
                        $pillText  = 'Hari Ini (0 Hari)';
                        $pillIcon  = 'fa-fire';
                    } elseif ($sh <= 2) {
                        $pillClass = 'pill-hari-1_2';
                        $pillText  = $sh . ' Hari Lalu';
                        $pillIcon  = 'fa-exclamation-circle';
                    } elseif ($sh <= 4) {
                        $pillClass = 'pill-hari-3_4';
                        $pillText  = $sh . ' Hari Lalu';
                        $pillIcon  = 'fa-clock';
                    } else {
                        $pillClass = 'pill-hari-5_6';
                        $pillText  = $sh . ' Hari Lalu';
                        $pillIcon  = 'fa-history';
                    }
                ?>
                    <tr>
                        <td class="td-num"><?php echo $no++; ?></td>
                        <td>
                            <div><strong><?php echo date('d/m/Y', strtotime($row['tgl_peresepan'])); ?></strong></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($row['jam_peresepan']); ?></div>
                        </td>
                        <td class="td-mono"><?php echo htmlspecialchars($row['no_resep']); ?></td>
                        <td class="td-mono"><?php echo htmlspecialchars($row['no_rawat_resep']); ?></td>
                        <td>
                            <div class="patient-box">
                                <span class="rm-badge"><?php echo htmlspecialchars($row['no_rkm_medis']); ?></span>
                                <div><strong><?php echo htmlspecialchars($row['nm_pasien']); ?></strong></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($row['nm_poli'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['nm_dokter'] ?: '-'); ?></td>
                        <td>
                            <div class="obat-title"><?php echo htmlspecialchars($row['nama_brng']); ?></div>
                            <div class="obat-sub">
                                Kode: <code><?php echo htmlspecialchars($row['kode_brng']); ?></code> &bull;
                                Jml: <strong><?php echo htmlspecialchars($row['jml_resep'] . ' ' . $row['kode_sat']); ?></strong>
                            </div>
                            <?php if (!empty($row['aturan_pakai'])): ?>
                                <span class="aturan-pill">&int; <?php echo htmlspecialchars($row['aturan_pakai']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><strong><?php echo date('d/m/Y', strtotime($row['tgl_beri_terakhir'])); ?></strong></div>
                            <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($row['total_hari_sebelumnya']); ?>x tgl pemberian</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="pill-selisih <?php echo $pillClass; ?>">
                                <i class="fas <?php echo $pillIcon; ?>"></i> <?php echo $pillText; ?>
                            </span>
                        </td>
                        <td class="no-copy" style="text-align:center;">
                            <button type="button"
                                    class="btn btn-sm btn-info riwayat-btn"
                                    data-norm="<?php echo htmlspecialchars($row['no_rkm_medis']); ?>"
                                    data-nama="<?php echo htmlspecialchars($row['nm_pasien']); ?>"
                                    data-kode="<?php echo htmlspecialchars($row['kode_brng']); ?>"
                                    data-namabarang="<?php echo htmlspecialchars($row['nama_brng']); ?>"
                                    data-tglresep="<?php echo htmlspecialchars($row['tgl_peresepan']); ?>"
                                    data-selisih="<?php echo $row['selisih_hari']; ?>">
                                <i class="fas fa-list-alt"></i> Riwayat
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle" style="color:#22c55e;"></i>
            <h3>Tidak Ada Resep Berulang Terdeteksi</h3>
            <p>Tidak ditemukan resep rawat jalan yang mendapatkan obat yang sama dalam kurun waktu &lt; 7 hari pada parameter filter ini.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL POPUP RIWAYAT PEMBERIAN OBAT -->
<div id="riwayatModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> Riwayat Pemberian Obat Pasien</h3>
            <button type="button" class="modal-close" onclick="closeRiwayatModal()" title="Tutup">&times;</button>
        </div>
        <div class="modal-body">
            <div id="patientSummary" class="patient-summary"></div>
            <div id="riwayatContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeRiwayatModal()">Tutup</button>
        </div>
    </div>
</div>

<div id="toast"><i class="fas fa-check-circle"></i> Data berhasil disalin ke clipboard!</div>

<script>
    function resetForm() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = yyyy + '-' + mm + '-' + dd;

        document.getElementById('tgl_awal').value = todayStr;
        document.getElementById('tgl_akhir').value = todayStr;
        document.getElementById('filter_hari').value = 'all';
        document.getElementById('kd_poli').value = '';
        document.getElementById('keyword').value = '';
        document.getElementById('filterForm').submit();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(val) {
        const num = Number(val);
        if (isNaN(num)) return escapeHtml(val);
        return Number.isInteger(num)
            ? num.toLocaleString('id-ID')
            : num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    const riwayatModal = document.getElementById('riwayatModal');
    const patientSummary = document.getElementById('patientSummary');
    const riwayatContent = document.getElementById('riwayatContent');

    function openRiwayatModal(noRm, nmPasien, kodeBrng, namaBrng, tglResep, selisih) {
        patientSummary.innerHTML =
            '<strong>Pasien:</strong> ' + escapeHtml(nmPasien) + ' (' + escapeHtml(noRm) + ')<br>' +
            '<strong>Obat:</strong> ' + escapeHtml(namaBrng) + ' (<code>' + escapeHtml(kodeBrng) + '</code>)<br>' +
            '<strong>Tanggal Resep Ralan:</strong> ' + escapeHtml(tglResep) +
            ' &bull; <strong>Selisih Terakhir:</strong> <span style="color:#dc2626; font-weight:700;">' + escapeHtml(selisih) + ' hari</span>';

        riwayatContent.innerHTML = '<p style="text-align:center; padding:20px; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Memuat riwayat pemberian obat...</p>';
        riwayatModal.classList.add('active');
        riwayatModal.setAttribute('aria-hidden', 'false');

        const url = 'kontrol_obat_berulang.php?action=get_riwayat_obat' +
                    '&no_rkm_medis=' + encodeURIComponent(noRm) +
                    '&kode_brng=' + encodeURIComponent(kodeBrng) +
                    '&_t=' + Date.now();

        fetch(url, { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    riwayatContent.innerHTML = '<p style="color:#dc2626; padding:15px;">' + escapeHtml(data.message || 'Gagal mengambil riwayat.') + '</p>';
                    return;
                }
                const items = data.items || [];
                if (items.length === 0) {
                    riwayatContent.innerHTML = '<p style="text-align:center; padding:20px; color:#64748b;">Tidak ada catatan pemberian obat sebelumnya di detail pemberian.</p>';
                    return;
                }

                let totalJml = 0;
                let html = '<div class="riwayat-table-wrap"><table class="riwayat-table">';
                html += '<thead><tr>' +
                        '<th class="text-center" style="width:36px;">No</th>' +
                        '<th>Tgl Pemberian</th>' +
                        '<th>Jam</th>' +
                        '<th>No. Rawat</th>' +
                        '<th>Pelayanan</th>' +
                        '<th>Unit / Ruangan</th>' +
                        '<th class="text-right">Jumlah</th>' +
                        '<th class="text-center">Satuan</th>' +
                        '</tr></thead><tbody>';

                items.forEach((item, idx) => {
                    const jmlNum = Number(item.jml) || 0;
                    totalJml += jmlNum;
                    const statusBadge = item.status === 'Ranap'
                        ? '<span style="background:#fef3c7; color:#b45309; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">Ranap</span>'
                        : '<span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">Ralan</span>';

                    html += '<tr>' +
                            '<td class="text-center">' + (idx + 1) + '</td>' +
                            '<td><strong>' + escapeHtml(item.tgl_perawatan) + '</strong></td>' +
                            '<td>' + escapeHtml(item.jam) + '</td>' +
                            '<td style="font-family:monospace; font-size:11px;">' + escapeHtml(item.no_rawat) + '</td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + escapeHtml(item.unit_layanan || '-') + '</td>' +
                            '<td class="text-right font-weight:700;">' + formatNumber(item.jml) + '</td>' +
                            '<td class="text-center">' + escapeHtml(item.kode_sat || '-') + '</td>' +
                            '</tr>';
                });

                html += '</tbody><tfoot><tr>' +
                        '<td colspan="6" class="text-right">Total Pemberian (' + items.length + ' kali):</td>' +
                        '<td class="text-right">' + formatNumber(totalJml) + '</td>' +
                        '<td class="text-center">' + escapeHtml(items[0]?.kode_sat || '-') + '</td>' +
                        '</tr></tfoot></table></div>';

                riwayatContent.innerHTML = html;
            })
            .catch(err => {
                console.error('Error fetch riwayat:', err);
                riwayatContent.innerHTML = '<p style="color:#dc2626; padding:15px;">Terjadi kesalahan saat menghubungi server.</p>';
            });
    }

    function closeRiwayatModal() {
        riwayatModal.classList.remove('active');
        riwayatModal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.riwayat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const noRm = this.getAttribute('data-norm');
            const nmPasien = this.getAttribute('data-nama');
            const kodeBrng = this.getAttribute('data-kode');
            const namaBrng = this.getAttribute('data-namabarang');
            const tglResep = this.getAttribute('data-tglresep');
            const selisih = this.getAttribute('data-selisih');
            openRiwayatModal(noRm, nmPasien, kodeBrng, namaBrng, tglResep, selisih);
        });
    });

    riwayatModal.addEventListener('click', function(e) {
        if (e.target === riwayatModal) {
            closeRiwayatModal();
        }
    });

    function copyTable() {
        const table = document.getElementById('mainTable');
        if (!table) return;

        let tsv = '';
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('th:not(.no-copy), td:not(.no-copy)');
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
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }
</script>
</body>
</html>

