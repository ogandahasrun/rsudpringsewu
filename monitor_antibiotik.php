<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Helper function to generate no_permintaan with format PPRA + YYYYMMDD + 3-digit sequence (e.g. PPRA20260101001)
function generateNoPermintaan($koneksi, $tanggalStr) {
    $ts = strtotime($tanggalStr);
    if ($ts === false) {
        $ts = time();
    }
    $datePart = date('Ymd', $ts);
    $prefix = 'PPRA' . $datePart;

    $sql = "SELECT MAX(CAST(SUBSTRING(no_permintaan, 13) AS UNSIGNED)) AS max_urut
            FROM konsultasi_perawat
            WHERE no_permintaan LIKE ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    $likeParam = $prefix . '%';
    mysqli_stmt_bind_param($stmt, 's', $likeParam);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    $nextUrut = 1;
    if ($row && $row['max_urut'] !== null) {
        $nextUrut = intval($row['max_urut']) + 1;
    }

    return $prefix . sprintf('%03d', $nextUrut);
}

// AJAX endpoint: Generate nomor permintaan berdasarkan tanggal
if (isset($_GET['action']) && $_GET['action'] === 'get_no_permintaan') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d H:i:s');
    $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
    echo json_encode([
        'success' => true,
        'no_permintaan' => $no_permintaan
    ]);
    exit;
}

// AJAX endpoint: Ambil riwayat pemberian antibiotik per no_rawat
if (isset($_GET['action']) && $_GET['action'] === 'get_riwayat_antibiotik') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $no_rawat = trim($_GET['no_rawat'] ?? '');
    $start    = trim($_GET['start'] ?? '');
    $end      = trim($_GET['end'] ?? '');

    if ($no_rawat === '') {
        echo json_encode([
            'success' => false,
            'message' => 'No. Rawat tidak valid.'
        ]);
        exit;
    }

    $sqlRiwayat = "SELECT d.tgl_perawatan,
                          d.jam,
                          d.kode_brng,
                          b.nama_brng,
                          b.kode_sat,
                          d.jml,
                          d.status
                   FROM detail_pemberian_obat d
                   INNER JOIN databarang b ON d.kode_brng = b.kode_brng
                   WHERE d.no_rawat = ?
                     AND b.kode_golongan = 'AB'";

    if ($start !== '' && $end !== '') {
        $sqlRiwayat .= " AND d.tgl_perawatan BETWEEN ? AND ?";
    }
    $sqlRiwayat .= " ORDER BY d.tgl_perawatan ASC, d.jam ASC, b.nama_brng ASC";

    $stmtRw = mysqli_prepare($koneksi, $sqlRiwayat);
    if (!$stmtRw) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)
        ]);
        exit;
    }

    if ($start !== '' && $end !== '') {
        mysqli_stmt_bind_param($stmtRw, 'sss', $no_rawat, $start, $end);
    } else {
        mysqli_stmt_bind_param($stmtRw, 's', $no_rawat);
    }

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

// AJAX endpoint: Simpan data konsultasi_perawat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_konsultasi') {
    header('Content-Type: application/json');

    $no_permintaan       = trim($_POST['no_permintaan'] ?? '');
    $no_rawat            = trim($_POST['no_rawat'] ?? '');
    $tanggal_input       = trim($_POST['tanggal'] ?? '');
    $nip                 = trim($_POST['nip'] ?? '');
    $kd_dokter_dikonsuli = trim($_POST['kd_dokter_dikonsuli'] ?? '');
    $situation           = trim($_POST['situation'] ?? '');
    $background          = trim($_POST['background'] ?? '');
    $assessment          = trim($_POST['assessment'] ?? '');
    $recomendation       = trim($_POST['recomendation'] ?? '');

    if ($no_rawat === '' || $nip === '' || $kd_dokter_dikonsuli === '') {
        echo json_encode([
            'success' => false,
            'message' => 'No Rawat, NIP Petugas, dan Dokter Dikonsuli wajib diisi.'
        ]);
        exit;
    }

    // Normalize datetime format (YYYY-MM-DD HH:MM:SS)
    $ts = strtotime($tanggal_input);
    $tanggal = ($ts !== false) ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');

    // Generate or ensure valid no_permintaan
    if ($no_permintaan === '') {
        $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
    } else {
        // Check if no_permintaan already exists; if so, regenerate for that date
        $chk = mysqli_prepare($koneksi, "SELECT no_permintaan FROM konsultasi_perawat WHERE no_permintaan = ?");
        mysqli_stmt_bind_param($chk, 's', $no_permintaan);
        mysqli_stmt_execute($chk);
        $chkRes = mysqli_stmt_get_result($chk);
        if ($chkRes && mysqli_num_rows($chkRes) > 0) {
            $no_permintaan = generateNoPermintaan($koneksi, $tanggal);
        }
    }

    $insSql = "INSERT INTO konsultasi_perawat (
                    no_permintaan,
                    no_rawat,
                    tanggal,
                    nip,
                    kd_dokter_dikonsuli,
                    situation,
                    background,
                    assessment,
                    recomendation
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtIns = mysqli_prepare($koneksi, $insSql);
    if (!$stmtIns) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmtIns,
        'sssssssss',
        $no_permintaan,
        $no_rawat,
        $tanggal,
        $nip,
        $kd_dokter_dikonsuli,
        $situation,
        $background,
        $assessment,
        $recomendation
    );

    if (mysqli_stmt_execute($stmtIns)) {
        echo json_encode([
            'success' => true,
            'message' => 'Data konsultasi berhasil dikirim ke dokter dengan No. Permintaan: ' . $no_permintaan,
            'no_permintaan' => $no_permintaan
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data: ' . mysqli_stmt_error($stmtIns)
        ]);
    }
    exit;
}

// Get date range and duration filter from GET parameters, default to last 30 days & >= 3 days
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$end   = isset($_GET['end'])   ? $_GET['end']   : date('Y-m-d');
$lama  = isset($_GET['lama'])  ? $_GET['lama']  : 'gte3';

$havingMap = [
    'gte3' => 'days >= 3',
    '1_2'  => 'days BETWEEN 1 AND 2',
    '3'    => 'days = 3',
    '4_6'  => 'days BETWEEN 4 AND 6',
    '7'    => 'days = 7',
    'gt7'  => 'days > 7',
    'all'  => 'days >= 1',
];
$lamaLabelMap = [
    'gte3' => '≥ 3 Hari',
    '1_2'  => '1 - 2 Hari',
    '3'    => '3 Hari',
    '4_6'  => '4 - 6 Hari',
    '7'    => '7 Hari',
    'gt7'  => '> 7 Hari',
    'all'  => 'Semua (≥ 1 Hari)',
];
if (!array_key_exists($lama, $havingMap)) {
    $lama = 'gte3';
}
$havingClause = $havingMap[$lama];

// Prepare statement for antibiotic monitoring
$query = "SELECT d.no_rawat,
                 r.no_rkm_medis,
                 p.nm_pasien,
                 COALESCE(
                     (SELECT dp.kd_dokter FROM dpjp_ranap dp WHERE dp.no_rawat = d.no_rawat LIMIT 1),
                     r.kd_dokter
                 ) AS kd_dokter_default,
                 MIN(d.tgl_perawatan) AS start_date,
                 MAX(d.tgl_perawatan) AS end_date,
                 COUNT(DISTINCT d.tgl_perawatan) AS days,
                 GROUP_CONCAT(DISTINCT d.kode_brng SEPARATOR ', ') AS kode_barang,
                 GROUP_CONCAT(DISTINCT b.nama_brng SEPARATOR ', ') AS nama_barang,
                 (SELECT kp.no_permintaan
                  FROM konsultasi_perawat kp
                  WHERE kp.no_rawat = d.no_rawat AND kp.no_permintaan LIKE 'PPRA%'
                  ORDER BY kp.tanggal DESC LIMIT 1) AS last_ppra_no
          FROM detail_pemberian_obat d
          INNER JOIN databarang b ON d.kode_brng = b.kode_brng
          INNER JOIN reg_periksa r ON d.no_rawat = r.no_rawat
          INNER JOIN pasien p ON r.no_rkm_medis = p.no_rkm_medis
          WHERE b.kode_golongan = 'AB'
            AND d.tgl_perawatan BETWEEN ? AND ?
          GROUP BY d.no_rawat, r.no_rkm_medis, p.nm_pasien, r.kd_dokter
          HAVING $havingClause
          ORDER BY days DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, 'ss', $start, $end);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
$totalTerkirim = 0;
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[] = $r;
        if (!empty($r['last_ppra_no'])) {
            $totalTerkirim++;
        }
    }
}
$totalRows = count($rows);
$totalBelumTerkirim = $totalRows - $totalTerkirim;

// Fetch active petugas for dropdown
$petugasList = [];
$resPetugas = mysqli_query($koneksi, "SELECT nip, nama FROM petugas WHERE status = '1' ORDER BY nama ASC");
if ($resPetugas) {
    while ($p = mysqli_fetch_assoc($resPetugas)) {
        $petugasList[] = $p;
    }
}

// Fetch active dokter for dropdown
$dokterList = [];
$resDokter = mysqli_query($koneksi, "SELECT kd_dokter, nm_dokter FROM dokter WHERE status = '1' ORDER BY nm_dokter ASC");
if ($resDokter) {
    while ($dk = mysqli_fetch_assoc($resDokter)) {
        $dokterList[] = $dk;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Penggunaan Antibiotik (<?php echo htmlspecialchars($lamaLabelMap[$lama]); ?>)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:       #0f766e;
            --primary-light: #14b8a6;
            --primary-dark:  #0d5c55;
            --secondary:     #2563eb;
            --info:          #0891b2;
            --success:       #16a34a;
            --warning:       #d97706;
            --danger:        #dc2626;
            --card-bg:       #ffffff;
            --text:          #1e293b;
            --text-muted:    #64748b;
            --border:        #e2e8f0;
            --shadow:        0 4px 24px rgba(15, 118, 110, 0.10);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding-bottom: 50px;
            background: linear-gradient(135deg, #e6f7f5 0%, #f0f9ff 60%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            padding: 28px 24px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 118, 110, 0.28);
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
            bottom: -70px; left: -30px;
            width: 260px; height: 260px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        .page-header h1 {
            margin: 0;
            font-size: 1.7rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .page-header .subtitle {
            margin: 6px 0 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            position: relative;
            z-index: 1;
        }

        /* CONTAINER */
        .container {
            max-width: 1380px;
            margin: 22px auto 0;
            padding: 0 22px;
        }

        /* FILTER CARD */
        .filter-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 14px;
        }
        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
            margin: 0;
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
        .filter-field input[type="date"],
        .filter-field select {
            padding: 8px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text);
            background: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .filter-field input[type="date"]:focus,
        .filter-field select:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.15);
            background: #fff;
        }
        .toolbar-actions {
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
            padding: 8px 14px;
            font-size: 0.83rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-sm { padding: 6px 11px; font-size: 0.78rem; border-radius: 6px; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 2px 8px rgba(15, 118, 110, 0.25);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 118, 110, 0.35); }
        .btn-blue {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }
        .btn-blue:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); }
        .btn-info {
            background: linear-gradient(135deg, #0e7490, #06b6d4);
            color: #fff;
            box-shadow: 0 2px 8px rgba(8, 145, 178, 0.22);
        }
        .btn-info:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(8, 145, 178, 0.32); }
        .btn-success {
            background: linear-gradient(135deg, #15803d, #22c55e);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.22);
        }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22, 163, 74, 0.32); }
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: #e2e8f0; color: var(--text); }

        /* STATS ROW */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px 18px;
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
        .stat-card.teal::before  { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .stat-card.green::before { background: linear-gradient(90deg, #16a34a, #4ade80); }
        .stat-card.amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .stat-card.blue::before  { background: linear-gradient(90deg, #2563eb, #60a5fa); }
        .stat-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .stat-card.teal .stat-value  { color: var(--primary); }
        .stat-card.green .stat-value { color: var(--success); }
        .stat-card.amber .stat-value { color: var(--warning); }
        .stat-card.blue .stat-value  { color: var(--secondary); font-size: 1.15rem; padding-top: 4px; }

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
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 10px;
        }
        .table-title {
            font-size: 0.98rem;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-subtitle { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }
        .table-scroll { overflow-x: auto; }
        .table-scroll::-webkit-scrollbar { height: 7px; }
        .table-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        table#antibiotik-table {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.855rem;
        }
        table#antibiotik-table thead th {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: #fff;
            padding: 12px 13px;
            font-weight: 600;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
            white-space: nowrap;
            border: none;
        }
        table#antibiotik-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        table#antibiotik-table tbody tr:last-child { border-bottom: none; }
        table#antibiotik-table tbody tr:nth-child(even) { background: #f8fffe; }
        table#antibiotik-table tbody tr:hover { background: #e6f7f5; }
        table#antibiotik-table td {
            padding: 11px 13px;
            vertical-align: top;
            color: var(--text);
            border: none;
        }
        .td-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.81rem; color: var(--primary-dark); font-weight: 600; white-space: nowrap; }
        .td-days { text-align: center; white-space: nowrap; }
        .days-pill {
            display: inline-block;
            min-width: 34px;
            padding: 3px 9px;
            border-radius: 99px;
            font-weight: 700;
            font-size: 0.8rem;
            text-align: center;
        }
        .days-1-2 { background: #dbeafe; color: #1d4ed8; }
        .days-3   { background: #ccfbf1; color: #0f766e; }
        .days-4-6 { background: #fef3c7; color: #b45309; }
        .days-7p  { background: #fee2e2; color: #b91c1c; }

        .action-buttons { display: flex; gap: 6px; flex-wrap: nowrap; }
        .badge-sent {
            display: inline-block;
            margin-top: 5px;
            padding: 3px 8px;
            font-size: 0.72rem;
            font-weight: 600;
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 99px;
        }

        /* EMPTY STATE */
        .empty-state {
            padding: 55px 24px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 2.8rem; color: #cbd5e1; margin-bottom: 12px; }
        .empty-state p { margin: 4px 0; font-size: 0.95rem; }

        /* Modal Popup Styles */
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
            max-width: 650px;
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
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: #fff;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 1rem; font-weight: 600; }
        .modal-close {
            background: rgba(255, 255, 255, 0.15);
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
        .modal-close:hover { background: rgba(255, 255, 255, 0.28); }
        .modal-body { padding: 18px 20px; }
        .patient-summary {
            background: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 11px 14px;
            margin-bottom: 14px;
            font-size: 0.84rem;
            line-height: 1.55;
        }
        .form-group { margin-bottom: 12px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text);
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.84rem;
            font-family: inherit;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.14);
        }
        .form-group input[readonly] {
            background-color: #f1f5f9;
            color: var(--text-muted);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .modal-footer {
            padding: 12px 20px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .char-hint {
            font-size: 0.72rem;
            color: var(--text-muted);
            float: right;
            font-weight: normal;
        }
        .modal-card-wide { max-width: 800px; }
        .modal-header-info { background: linear-gradient(135deg, #0e7490, #0891b2); }
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
            padding: 8px 11px;
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
            background: #f0fdfa;
            color: var(--primary-dark);
            font-weight: 700;
            border-top: 2px solid #99f6e4;
        }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1><i class="fas fa-capsules"></i> Monitor Penggunaan Antibiotik</h1>
    <p class="subtitle">Pemantauan Lama Terapi Antibiotik PPRA (<?php echo htmlspecialchars($lamaLabelMap[$lama]); ?> per No. Rawat)</p>
</div>

<div class="container">
    <div class="filter-card">
        <div class="toolbar">
            <form method="GET" class="filter-form">
                <div class="filter-field">
                    <label for="filter-start"><i class="fas fa-calendar-alt"></i> Tanggal Mulai</label>
                    <input type="date" id="filter-start" name="start" value="<?php echo htmlspecialchars($start); ?>" required>
                </div>
                <div class="filter-field">
                    <label for="filter-end"><i class="fas fa-calendar-day"></i> Tanggal Akhir</label>
                    <input type="date" id="filter-end" name="end" value="<?php echo htmlspecialchars($end); ?>" required>
                </div>
                <div class="filter-field">
                    <label for="lama"><i class="fas fa-clock"></i> Lama Pemberian</label>
                    <select name="lama" id="lama">
                        <option value="gte3" <?php echo ($lama === 'gte3') ? 'selected' : ''; ?>>&ge; 3 Hari (Default)</option>
                        <option value="1_2" <?php echo ($lama === '1_2') ? 'selected' : ''; ?>>1 - 2 Hari</option>
                        <option value="3" <?php echo ($lama === '3') ? 'selected' : ''; ?>>3 Hari</option>
                        <option value="4_6" <?php echo ($lama === '4_6') ? 'selected' : ''; ?>>4 - 6 Hari</option>
                        <option value="7" <?php echo ($lama === '7') ? 'selected' : ''; ?>>7 Hari</option>
                        <option value="gt7" <?php echo ($lama === 'gt7') ? 'selected' : ''; ?>>&gt; 7 Hari</option>
                        <option value="all" <?php echo ($lama === 'all') ? 'selected' : ''; ?>>Semua (&ge; 1 Hari)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </form>

            <div class="toolbar-actions">
                <a href="laporan_ppra_penggunaan_antibiotik.php?start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>" class="btn btn-blue">
                    <i class="fas fa-chart-bar"></i> Rekapitulasi &amp; Grafik PPRA
                </a>
                <?php if ($totalRows > 0): ?>
                <button type="button" id="copy-table-btn" class="btn btn-success">
                    <i class="fas fa-copy"></i> Copy Tabel ke Clipboard
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="stats-row">
        <div class="stat-card teal">
            <span class="stat-label">Total No. Rawat</span>
            <span class="stat-value"><?php echo $totalRows; ?></span>
        </div>
        <div class="stat-card green">
            <span class="stat-label">Sudah Terkirim PPRA</span>
            <span class="stat-value"><?php echo $totalTerkirim; ?></span>
        </div>
        <div class="stat-card amber">
            <span class="stat-label">Belum Dikirim</span>
            <span class="stat-value"><?php echo $totalBelumTerkirim; ?></span>
        </div>
        <div class="stat-card blue">
            <span class="stat-label">Filter Lama Pemberian</span>
            <span class="stat-value"><?php echo htmlspecialchars($lamaLabelMap[$lama]); ?></span>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div>
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    Daftar Pasien Penggunaan Antibiotik
                </div>
                <div class="table-subtitle">
                    Periode: <?php echo date('d/m/Y', strtotime($start)); ?> &ndash; <?php echo date('d/m/Y', strtotime($end)); ?>
                    &bull; <?php echo $totalRows; ?> data ditemukan
                </div>
            </div>
        </div>

        <?php if ($totalRows > 0): ?>
        <div class="table-scroll">
            <table id="antibiotik-table">
                <thead>
                    <tr>
                        <th>No Rawat</th>
                        <th>No RKM Medis</th>
                        <th>Nama Pasien</th>
                        <th>Periode (Mulai - Selesai)</th>
                        <th style="text-align:center;">Jumlah Hari</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th class="no-copy">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $daysNum = intval($row['days']);
                    if ($daysNum <= 2) {
                        $daysClass = 'days-1-2';
                    } elseif ($daysNum === 3) {
                        $daysClass = 'days-3';
                    } elseif ($daysNum <= 6) {
                        $daysClass = 'days-4-6';
                    } else {
                        $daysClass = 'days-7p';
                    }
                ?>
                    <tr>
                        <td class="td-mono"><?php echo htmlspecialchars($row['no_rawat']); ?></td>
                        <td class="td-mono"><?php echo htmlspecialchars($row['no_rkm_medis']); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nm_pasien']); ?></strong></td>
                        <td style="white-space:nowrap;"><?php echo htmlspecialchars($row['start_date'] . ' s/d ' . $row['end_date']); ?></td>
                        <td class="td-days"><span class="days-pill <?php echo $daysClass; ?>"><?php echo htmlspecialchars($row['days']); ?></span></td>
                        <td class="td-mono"><?php echo htmlspecialchars($row['kode_barang']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                        <td class="no-copy">
                            <div class="action-buttons">
                                <button type="button"
                                        class="btn btn-sm btn-info riwayat-btn"
                                        data-row="<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
                                    <i class="fas fa-history"></i> Riwayat
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-blue kirim-dokter-btn"
                                        data-row="<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>">
                                    <i class="fas fa-paper-plane"></i> Kirim ke Dokter
                                </button>
                            </div>
                            <div class="sent-status-container">
                                <?php if (!empty($row['last_ppra_no'])): ?>
                                    <span class="badge-sent"><i class="fas fa-check-circle"></i> Terkirim: <?php echo htmlspecialchars($row['last_ppra_no']); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <p><strong>Tidak ada data yang memenuhi kriteria.</strong></p>
            <p style="font-size:0.84rem;">Silakan ubah rentang tanggal atau filter lama pemberian di atas.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

    <!-- Modal Popup Kirim Konsultasi Perawat (PPRA) ke Dokter -->
    <div id="konsultasi-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Kirim Konsultasi PPRA ke Dokter</h3>
                <button type="button" class="modal-close" id="close-modal-btn" title="Tutup">&times;</button>
            </div>
            <form id="form-konsultasi">
                <div class="modal-body">
                    <div id="patient-summary" class="patient-summary"></div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="no_permintaan">No. Permintaan (Format: PPRAYYYYMMDD001)</label>
                            <input type="text" id="no_permintaan" name="no_permintaan" maxlength="20" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="no_rawat">No. Rawat</label>
                            <input type="text" id="no_rawat" name="no_rawat" maxlength="17" readonly required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal &amp; Jam Laporan</label>
                        <input type="datetime-local" id="tanggal" name="tanggal" step="1" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nip">Petugas / Perawat (NIP)</label>
                            <select id="nip" name="nip" required>
                                <option value="">-- Pilih Petugas --</option>
                                <?php foreach ($petugasList as $ptg): ?>
                                    <option value="<?php echo htmlspecialchars($ptg['nip']); ?>">
                                        <?php echo htmlspecialchars($ptg['nip'] . ' - ' . $ptg['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kd_dokter_dikonsuli">Dokter Dikonsuli</label>
                            <select id="kd_dokter_dikonsuli" name="kd_dokter_dikonsuli" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php foreach ($dokterList as $dk): ?>
                                    <option value="<?php echo htmlspecialchars($dk['kd_dokter']); ?>">
                                        <?php echo htmlspecialchars($dk['kd_dokter'] . ' - ' . $dk['nm_dokter']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="situation">Situation <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="situation" name="situation" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="background">Background <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="background" name="background" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="assessment">Assessment <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="assessment" name="assessment" rows="2" maxlength="500" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="recomendation">Recommendation <span class="char-hint">Maks. 500 karakter</span></label>
                        <textarea id="recomendation" name="recomendation" rows="2" maxlength="500" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-modal-btn">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submit-konsultasi-btn">Kirim ke Dokter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Popup Riwayat Pemberian Antibiotik -->
    <div id="riwayat-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card modal-card-wide">
            <div class="modal-header modal-header-info">
                <h3>Riwayat Pemberian Antibiotik</h3>
                <button type="button" class="modal-close" id="close-riwayat-btn" title="Tutup">&times;</button>
            </div>
            <div class="modal-body">
                <div id="riwayat-patient-summary" class="patient-summary"></div>
                <div id="riwayat-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="tutup-riwayat-btn">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Helper: Copy text to clipboard with fallback for non-HTTPS
        function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                return new Promise(function(resolve, reject) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-9999px';
                    textArea.style.top = '0';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    try {
                        const successful = document.execCommand('copy');
                        document.body.removeChild(textArea);
                        if (successful) {
                            resolve();
                        } else {
                            reject(new Error('execCommand copy failed'));
                        }
                    } catch (err) {
                        document.body.removeChild(textArea);
                        reject(err);
                    }
                });
            }
        }

        // 1. Copy entire displayed table to clipboard (excluding the 'Aksi' column)
        const copyTableBtn = document.getElementById('copy-table-btn');
        if (copyTableBtn) {
            copyTableBtn.addEventListener('click', function() {
                const table = document.getElementById('antibiotik-table');
                if (!table) return;

                const lines = [];
                const rows = table.querySelectorAll('tr');
                rows.forEach(function(row) {
                    const cells = row.querySelectorAll('th:not(.no-copy), td:not(.no-copy)');
                    const rowValues = Array.from(cells).map(function(cell) {
                        return cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/\t/g, ' ').trim();
                    });
                    if (rowValues.length > 0) {
                        lines.push(rowValues.join('\t'));
                    }
                });

                const tsvContent = lines.join('\n');
                copyTextToClipboard(tsvContent).then(function() {
                    alert('Tabel berhasil disalin ke clipboard!');
                }).catch(function(err) {
                    console.error('Copy tabel gagal:', err);
                    alert('Gagal menyalin tabel ke clipboard.');
                });
            });
        }

        // 2. Modal Popup logic for sending data to konsultasi_perawat
        const modal = document.getElementById('konsultasi-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');
        const formKonsultasi = document.getElementById('form-konsultasi');
        const inputNoPermintaan = document.getElementById('no_permintaan');
        const inputNoRawat = document.getElementById('no_rawat');
        const inputTanggal = document.getElementById('tanggal');
        const selectNip = document.getElementById('nip');
        const selectDokter = document.getElementById('kd_dokter_dikonsuli');
        const inputSituation = document.getElementById('situation');
        const inputBackground = document.getElementById('background');
        const inputAssessment = document.getElementById('assessment');
        const inputRecomendation = document.getElementById('recomendation');
        const patientSummary = document.getElementById('patient-summary');
        let activeRowButton = null;

        function getLocalDateTimeString() {
            const now = new Date();
            const pad = function(n) { return String(n).padStart(2, '0'); };
            return now.getFullYear() + '-' +
                   pad(now.getMonth() + 1) + '-' +
                   pad(now.getDate()) + 'T' +
                   pad(now.getHours()) + ':' +
                   pad(now.getMinutes()) + ':' +
                   pad(now.getSeconds());
        }

        function updateNoPermintaan(tanggalStr) {
            inputNoPermintaan.value = 'Memuat...';
            fetch('monitor_antibiotik.php?action=get_no_permintaan&tanggal=' + encodeURIComponent(tanggalStr) + '&_t=' + Date.now(), {
                cache: 'no-store'
            })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.no_permintaan) {
                        inputNoPermintaan.value = data.no_permintaan;
                    }
                })
                .catch(function(err) {
                    console.error('Gagal mengambil no_permintaan:', err);
                });
        }

        // Refresh no_permintaan if date changes
        inputTanggal.addEventListener('change', function() {
            if (this.value) {
                updateNoPermintaan(this.value.replace('T', ' '));
            }
        });

        function openModalForRow(rowData, btnElement) {
            activeRowButton = btnElement;
            const nowLocal = getLocalDateTimeString();

            inputNoRawat.value = rowData.no_rawat || '';
            inputTanggal.value = nowLocal;
            updateNoPermintaan(nowLocal.replace('T', ' '));

            // Pre-select patient's default doctor if available
            if (rowData.kd_dokter_default) {
                selectDokter.value = rowData.kd_dokter_default;
            } else {
                selectDokter.value = '';
            }

            // Pre-fill SBAR with antibiotic monitoring details so user can review/edit
            inputSituation.value = 'Pasien ' + (rowData.nm_pasien || '') + ' (No. RM: ' + (rowData.no_rkm_medis || '') + ') mendapatkan terapi antibiotik ' + (rowData.nama_barang || '') + ' selama ' + (rowData.days || '') + ' hari.';
            inputBackground.value = 'Periode pemberian antibiotik: ' + (rowData.start_date || '') + ' s/d ' + (rowData.end_date || '') + ' (Kode: ' + (rowData.kode_barang || '') + ').';
            inputAssessment.value = 'Monitoring PPRA penggunaan antibiotik >= 3 hari (' + (rowData.days || '') + ' hari).';
            inputRecomendation.value = 'Mohon evaluasi/konfirmasi kelanjutan terapi antibiotik (lanjut / de-eskalasi / stop).';

            patientSummary.innerHTML =
                '<strong>Pasien:</strong> ' + (rowData.nm_pasien || '-') + ' (' + (rowData.no_rkm_medis || '-') + ')<br>' +
                '<strong>Antibiotik:</strong> ' + (rowData.nama_barang || '-') + ' (' + (rowData.days || '0') + ' hari: ' + (rowData.start_date || '-') + ' s/d ' + (rowData.end_date || '-') + ')';

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            activeRowButton = null;
        }

        document.querySelectorAll('.kirim-dokter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const rowData = JSON.parse(this.getAttribute('data-row'));
                openModalForRow(rowData, this);
            });
        });

        closeModalBtn.addEventListener('click', closeModal);
        cancelModalBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Submit form via AJAX
        formKonsultasi.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submit-konsultasi-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim...';

            const formData = new FormData(formKonsultasi);
            formData.append('action', 'simpan_konsultasi');
            formData.set('tanggal', inputTanggal.value.replace('T', ' '));

            fetch('monitor_antibiotik.php', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim ke Dokter';

                if (data.success) {
                    alert(data.message);
                    if (activeRowButton) {
                        const statusContainer = activeRowButton.closest('td').querySelector('.sent-status-container');
                        if (statusContainer) {
                            statusContainer.innerHTML = '<span class="badge-sent">Terkirim: ' + data.no_permintaan + '</span>';
                        }
                    }
                    closeModal();
                } else {
                    alert(data.message || 'Gagal mengirim data ke dokter.');
                }
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Kirim ke Dokter';
                console.error('Error simpan konsultasi:', err);
                alert('Terjadi kesalahan saat menyimpan data.');
            });
        });

        // 3. Modal Popup Riwayat Pemberian Antibiotik
        const filterStart = <?php echo json_encode($start); ?>;
        const filterEnd = <?php echo json_encode($end); ?>;
        const riwayatModal = document.getElementById('riwayat-modal');
        const closeRiwayatBtn = document.getElementById('close-riwayat-btn');
        const tutupRiwayatBtn = document.getElementById('tutup-riwayat-btn');
        const riwayatSummary = document.getElementById('riwayat-patient-summary');
        const riwayatContent = document.getElementById('riwayat-content');

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

        function closeRiwayatModal() {
            riwayatModal.classList.remove('active');
            riwayatModal.setAttribute('aria-hidden', 'true');
        }

        function openRiwayatModal(rowData) {
            riwayatSummary.innerHTML =
                '<strong>Pasien:</strong> ' + escapeHtml(rowData.nm_pasien || '-') + ' (' + escapeHtml(rowData.no_rkm_medis || '-') + ')' +
                ' &nbsp;|&nbsp; <strong>No. Rawat:</strong> ' + escapeHtml(rowData.no_rawat || '-') + '<br>' +
                '<strong>Periode Terapi:</strong> ' + escapeHtml(rowData.start_date || '-') + ' s/d ' + escapeHtml(rowData.end_date || '-') +
                ' (' + escapeHtml(rowData.days || '0') + ' hari)';

            riwayatContent.innerHTML = '<p style="margin:12px 0; color:#666;">Memuat riwayat pemberian antibiotik...</p>';
            riwayatModal.classList.add('active');
            riwayatModal.setAttribute('aria-hidden', 'false');

            const url = 'monitor_antibiotik.php?action=get_riwayat_antibiotik' +
                        '&no_rawat=' + encodeURIComponent(rowData.no_rawat || '') +
                        '&start=' + encodeURIComponent(filterStart) +
                        '&end=' + encodeURIComponent(filterEnd) +
                        '&_t=' + Date.now();

            fetch(url, { cache: 'no-store' })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.success) {
                        riwayatContent.innerHTML = '<p style="color:#dc3545;">' + escapeHtml(data.message || 'Gagal memuat riwayat.') + '</p>';
                        return;
                    }
                    const items = data.items || [];
                    if (items.length === 0) {
                        riwayatContent.innerHTML = '<p style="color:#666;">Tidak ada riwayat pemberian antibiotik ditemukan.</p>';
                        return;
                    }

                    let totalJml = 0;
                    let html = '<div class="riwayat-table-wrap"><table class="riwayat-table">';
                    html += '<thead><tr>' +
                            '<th class="text-center" style="width:40px;">No</th>' +
                            '<th>Tanggal</th>' +
                            '<th>Jam</th>' +
                            '<th>Kode Barang</th>' +
                            '<th>Nama Barang</th>' +
                            '<th class="text-right">Jumlah</th>' +
                            '<th class="text-center">Satuan</th>' +
                            '<th class="text-center">Status</th>' +
                            '</tr></thead><tbody>';

                    items.forEach(function(item, idx) {
                        const jmlNum = Number(item.jml) || 0;
                        totalJml += jmlNum;
                        html += '<tr>' +
                                '<td class="text-center">' + (idx + 1) + '</td>' +
                                '<td>' + escapeHtml(item.tgl_perawatan) + '</td>' +
                                '<td>' + escapeHtml(item.jam) + '</td>' +
                                '<td>' + escapeHtml(item.kode_brng) + '</td>' +
                                '<td>' + escapeHtml(item.nama_brng) + '</td>' +
                                '<td class="text-right">' + formatNumber(item.jml) + '</td>' +
                                '<td class="text-center">' + escapeHtml(item.kode_sat || '-') + '</td>' +
                                '<td class="text-center">' + escapeHtml(item.status || '-') + '</td>' +
                                '</tr>';
                    });

                    html += '</tbody><tfoot><tr>' +
                            '<td colspan="5" class="text-right">Total Pemberian (' + items.length + ' kali)</td>' +
                            '<td class="text-right">' + formatNumber(totalJml) + '</td>' +
                            '<td colspan="2"></td>' +
                            '</tr></tfoot></table></div>';

                    riwayatContent.innerHTML = html;
                })
                .catch(function(err) {
                    console.error('Error memuat riwayat:', err);
                    riwayatContent.innerHTML = '<p style="color:#dc3545;">Terjadi kesalahan saat mengambil data riwayat.</p>';
                });
        }

        document.querySelectorAll('.riwayat-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const rowData = JSON.parse(this.getAttribute('data-row'));
                openRiwayatModal(rowData);
            });
        });

        closeRiwayatBtn.addEventListener('click', closeRiwayatModal);
        tutupRiwayatBtn.addEventListener('click', closeRiwayatModal);
        riwayatModal.addEventListener('click', function(e) {
            if (e.target === riwayatModal) {
                closeRiwayatModal();
            }
        });
    </script>
</body>
</html>
