<?php
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// =========================================================================
// AJAX ENDPOINT: Rincian Sebaran Stok per Depo / Bangsal
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_stok_depo') {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $kode_brng = trim($_GET['kode_brng'] ?? '');
    if ($kode_brng === '') {
        echo json_encode(['success' => false, 'message' => 'Kode barang tidak valid.']);
        exit;
    }

    // Ambil info barang
    $sqlBarang = "SELECT d.kode_brng, d.nama_brng, d.kode_sat, d.h_beli, d.expire, d.status,
                         COALESCE(j.nama, '-') AS nama_jenis
                  FROM databarang d
                  LEFT JOIN jenis j ON d.kdjns = j.kdjns
                  WHERE d.kode_brng = ? LIMIT 1";
    $stmtB = mysqli_prepare($koneksi, $sqlBarang);
    mysqli_stmt_bind_param($stmtB, 's', $kode_brng);
    mysqli_stmt_execute($stmtB);
    $resB = mysqli_stmt_get_result($stmtB);
    $infoBarang = mysqli_fetch_assoc($resB);

    if (!$infoBarang) {
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan.']);
        exit;
    }

    // Ambil rincian stok per depo
    $sqlStok = "SELECT g.kd_bangsal, b.nm_bangsal, g.no_batch, g.no_faktur, SUM(g.stok) AS stok
                FROM gudangbarang g
                INNER JOIN bangsal b ON g.kd_bangsal = b.kd_bangsal
                WHERE g.kode_brng = ?
                GROUP BY g.kd_bangsal, g.no_batch, g.no_faktur
                HAVING stok > 0
                ORDER BY b.nm_bangsal ASC, stok DESC";
    $stmtS = mysqli_prepare($koneksi, $sqlStok);
    mysqli_stmt_bind_param($stmtS, 's', $kode_brng);
    mysqli_stmt_execute($stmtS);
    $resS = mysqli_stmt_get_result($stmtS);

    $depoList = [];
    $totalStok = 0;
    while ($r = mysqli_fetch_assoc($resS)) {
        $depoList[] = $r;
        $totalStok += floatval($r['stok']);
    }

    // Ambil info transaksi keluar terakhir jika ada
    $sqlLast = "SELECT r.tanggal, r.jam, r.posisi, r.keluar, r.keterangan, b.nm_bangsal
                FROM riwayat_barang_medis r
                LEFT JOIN bangsal b ON r.kd_bangsal = b.kd_bangsal
                WHERE r.kode_brng = ? AND r.keluar > 0
                ORDER BY r.tanggal DESC, r.jam DESC
                LIMIT 5";
    $stmtL = mysqli_prepare($koneksi, $sqlLast);
    mysqli_stmt_bind_param($stmtL, 's', $kode_brng);
    mysqli_stmt_execute($stmtL);
    $resL = mysqli_stmt_get_result($stmtL);
    $lastTransactions = [];
    while ($rl = mysqli_fetch_assoc($resL)) {
        $lastTransactions[] = $rl;
    }

    echo json_encode([
        'success'           => true,
        'info'              => $infoBarang,
        'total_stok'        => $totalStok,
        'stok_depo'         => $depoList,
        'last_transactions' => $lastTransactions
    ]);
    exit;
}

// =========================================================================
// PARAMETER FILTER
// =========================================================================
$tab = 'deadstock';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'nonaktif') {
        $tab = 'nonaktif';
    } elseif ($_GET['tab'] === 'pasif') {
        $tab = 'pasif';
    }
}

// Parameter Periode Dead Stock
$tgl_akhir = isset($_GET['tgl_akhir']) && !empty($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$tgl_awal  = isset($_GET['tgl_awal'])  && !empty($_GET['tgl_awal'])  ? $_GET['tgl_awal']  : date('Y-m-d', strtotime('-90 days'));

// Parameter Filter Lokasi / Depo
$kd_bangsal = isset($_GET['kd_bangsal']) ? trim($_GET['kd_bangsal']) : '';

// Parameter Filter Jenis Barang
$kdjns = isset($_GET['kdjns']) ? trim($_GET['kdjns']) : '';

// Parameter Pencarian Kata Kunci
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Parameter Filter Transaksi Keluar yang Dihitung (Checkbox)
// Jika pertama kali dibuka (belum disubmit), default semua transaksi operasional aktif
$tx_pemberian   = isset($_GET['submitted']) ? (isset($_GET['tx_pemberian']) ? 1 : 0) : 1;
$tx_pengeluaran = isset($_GET['submitted']) ? (isset($_GET['tx_pengeluaran']) ? 1 : 0) : 1;
$tx_resep_pulang= isset($_GET['submitted']) ? (isset($_GET['tx_resep_pulang']) ? 1 : 0) : 1;
$tx_penjualan   = isset($_GET['submitted']) ? (isset($_GET['tx_penjualan']) ? 1 : 0) : 1;
$tx_mutasi      = isset($_GET['submitted']) ? (isset($_GET['tx_mutasi']) ? 1 : 0) : 0;

// Filter Sub-kondisi Barang Non Aktif
$status_stok_nonaktif = isset($_GET['status_stok_nonaktif']) ? $_GET['status_stok_nonaktif'] : 'ada_stok';

// Urutan / Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nilai_desc';

// Bangun daftar posisi transaksi keluar yang diakui
$posisiKeluarList = [];
if ($tx_pemberian)    $posisiKeluarList[] = "'Pemberian Obat'";
if ($tx_pengeluaran)  $posisiKeluarList[] = "'Stok Keluar'";
if ($tx_resep_pulang) $posisiKeluarList[] = "'Resep Pulang'";
if ($tx_penjualan)    $posisiKeluarList[] = "'Penjualan'";
if ($tx_mutasi)       $posisiKeluarList[] = "'Mutasi'";

// Fallback jika tidak ada yang dicentang
if (empty($posisiKeluarList)) {
    $posisiKeluarList = ["'Pemberian Obat'", "'Stok Keluar'", "'Resep Pulang'", "'Penjualan'"];
}
$posisiInSql = implode(',', $posisiKeluarList);

// Ambil daftar Bangsal / Depo Farmasi untuk dropdown
$bangsalList = [];
$resBangsal = mysqli_query($koneksi, "SELECT kd_bangsal, nm_bangsal FROM bangsal WHERE nm_bangsal LIKE '%farmasi%' OR nm_bangsal LIKE '%gudang%' OR nm_bangsal LIKE '%depo%' OR nm_bangsal LIKE '%apotek%' ORDER BY nm_bangsal ASC");
if ($resBangsal) {
    while ($bg = mysqli_fetch_assoc($resBangsal)) {
        $bangsalList[] = $bg;
    }
}

// Ambil daftar Jenis Barang untuk dropdown
$jenisList = [];
$resJenis = mysqli_query($koneksi, "SELECT kdjns, nama FROM jenis ORDER BY nama ASC");
if ($resJenis) {
    while ($jn = mysqli_fetch_assoc($resJenis)) {
        $jenisList[] = $jn;
    }
}

// =========================================================================
// QUERY DATA TAB 1: DEAD STOCK (STOK MATI)
// =========================================================================
$rowsDeadStock = [];
$deadTotalItem  = 0;
$deadTotalNilai = 0;
$deadNearEdCount = 0;

if ($tab === 'deadstock') {
    $whereBangsalStok = "";
    if ($kd_bangsal !== '') {
        $whereBangsalStok = " AND g.kd_bangsal = '" . mysqli_real_escape_string($koneksi, $kd_bangsal) . "' ";
    }

    $whereExtra = "";
    if ($kdjns !== '') {
        $whereExtra .= " AND d.kdjns = '" . mysqli_real_escape_string($koneksi, $kdjns) . "' ";
    }
    if ($keyword !== '') {
        $kwClean = mysqli_real_escape_string($koneksi, $keyword);
        $whereExtra .= " AND (d.kode_brng LIKE '%$kwClean%' OR d.nama_brng LIKE '%$kwClean%') ";
    }

    $orderBySql = "total_nilai DESC";
    if ($sort === 'nilai_asc')  $orderBySql = "total_nilai ASC";
    if ($sort === 'stok_desc')  $orderBySql = "total_stok DESC";
    if ($sort === 'stok_asc')   $orderBySql = "total_stok ASC";
    if ($sort === 'nama_asc')   $orderBySql = "d.nama_brng ASC";
    if ($sort === 'ed_asc')     $orderBySql = "d.expire ASC";

    $tglAwalSql  = mysqli_real_escape_string($koneksi, $tgl_awal);
    $tglAkhirSql = mysqli_real_escape_string($koneksi, $tgl_akhir);

    $sqlDead = "SELECT
                    d.kode_brng,
                    d.nama_brng,
                    d.kode_sat,
                    d.h_beli,
                    d.expire,
                    COALESCE(j.nama, '-') AS nama_jenis,
                    SUM(g.stok) AS total_stok,
                    ROUND(SUM(g.stok * d.h_beli), 2) AS total_nilai
                FROM databarang d
                INNER JOIN gudangbarang g ON d.kode_brng = g.kode_brng $whereBangsalStok
                LEFT JOIN jenis j ON d.kdjns = j.kdjns
                WHERE d.status = '1'
                  $whereExtra
                  AND d.kode_brng NOT IN (
                      SELECT DISTINCT r.kode_brng
                      FROM riwayat_barang_medis r
                      WHERE r.tanggal BETWEEN '$tglAwalSql' AND '$tglAkhirSql'
                        AND r.keluar > 0
                        AND r.posisi IN ($posisiInSql)
                  )
                GROUP BY d.kode_brng
                HAVING total_stok > 0
                ORDER BY $orderBySql";

    $resDead = mysqli_query($koneksi, $sqlDead);
    if ($resDead) {
        $todayTs = strtotime(date('Y-m-d'));
        $sixMonthsTs = strtotime('+6 months', $todayTs);

        while ($r = mysqli_fetch_assoc($resDead)) {
            $rowsDeadStock[] = $r;
            $deadTotalItem++;
            $deadTotalNilai += floatval($r['total_nilai']);

            // Cek status Near-ED (kurang dari 6 bulan atau sudah lewat)
            if (!empty($r['expire']) && $r['expire'] !== '0000-00-00') {
                $expTs = strtotime($r['expire']);
                if ($expTs !== false && $expTs <= $sixMonthsTs) {
                    $deadNearEdCount++;
                }
            }
        }
    }
}

// =========================================================================
// QUERY DATA TAB 2: BARANG NON-AKTIF (MASTER INACTIVE)
// =========================================================================
$rowsNonAktif = [];
$nonaktifTotalItem = 0;
$nonaktifTotalNilai = 0;
$nonaktifWithStockCount = 0;

if ($tab === 'nonaktif') {
    $whereBangsalStok = "";
    if ($kd_bangsal !== '') {
        $whereBangsalStok = " AND g.kd_bangsal = '" . mysqli_real_escape_string($koneksi, $kd_bangsal) . "' ";
    }

    $whereExtra = "";
    if ($kdjns !== '') {
        $whereExtra .= " AND d.kdjns = '" . mysqli_real_escape_string($koneksi, $kdjns) . "' ";
    }
    if ($keyword !== '') {
        $kwClean = mysqli_real_escape_string($koneksi, $keyword);
        $whereExtra .= " AND (d.kode_brng LIKE '%$kwClean%' OR d.nama_brng LIKE '%$kwClean%') ";
    }

    $havingSql = "";
    if ($status_stok_nonaktif === 'ada_stok') {
        $havingSql = "HAVING total_stok > 0";
    } elseif ($status_stok_nonaktif === 'stok_nol') {
        $havingSql = "HAVING total_stok = 0";
    }

    $orderBySql = "total_stok DESC, total_nilai DESC, d.nama_brng ASC";
    if ($sort === 'nilai_desc') $orderBySql = "total_nilai DESC";
    if ($sort === 'nama_asc')   $orderBySql = "d.nama_brng ASC";
    if ($sort === 'ed_asc')     $orderBySql = "d.expire ASC";

    $sqlNonAktif = "SELECT
                        d.kode_brng,
                        d.nama_brng,
                        d.kode_sat,
                        d.h_beli,
                        d.expire,
                        d.status,
                        COALESCE(j.nama, '-') AS nama_jenis,
                        COALESCE(SUM(g.stok), 0) AS total_stok,
                        ROUND(COALESCE(SUM(g.stok * d.h_beli), 0), 2) AS total_nilai
                    FROM databarang d
                    LEFT JOIN gudangbarang g ON d.kode_brng = g.kode_brng $whereBangsalStok
                    LEFT JOIN jenis j ON d.kdjns = j.kdjns
                    WHERE d.status = '0'
                      $whereExtra
                    GROUP BY d.kode_brng
                    $havingSql
                    ORDER BY $orderBySql";

    $resNonAktif = mysqli_query($koneksi, $sqlNonAktif);
    if ($resNonAktif) {
        while ($r = mysqli_fetch_assoc($resNonAktif)) {
            $rowsNonAktif[] = $r;
            $nonaktifTotalItem++;
            $nonaktifTotalNilai += floatval($r['total_nilai']);
            if (floatval($r['total_stok']) > 0) {
                $nonaktifWithStockCount++;
            }
        }
    }
}

// =========================================================================
// QUERY DATA TAB 3: MASTER PASIF (KANDIDAT NON-AKTIF)
// =========================================================================
$rowsPasif = [];
$pasifTotalItem = 0;
$totalMasterAktif = 0;

if ($tab === 'pasif') {
    // Hitung total master aktif di SIMRS
    $qTotalAktif = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM databarang WHERE status = '1'");
    if ($rAktif = mysqli_fetch_assoc($qTotalAktif)) {
        $totalMasterAktif = intval($rAktif['total']);
    }

    $whereExtra = "";
    if ($kdjns !== '') {
        $whereExtra .= " AND d.kdjns = '" . mysqli_real_escape_string($koneksi, $kdjns) . "' ";
    }
    if ($keyword !== '') {
        $kwClean = mysqli_real_escape_string($koneksi, $keyword);
        $whereExtra .= " AND (d.kode_brng LIKE '%$kwClean%' OR d.nama_brng LIKE '%$kwClean%') ";
    }

    $orderBySql = "d.nama_brng ASC";
    if ($sort === 'nilai_desc') $orderBySql = "d.h_beli DESC";
    if ($sort === 'nilai_asc')  $orderBySql = "d.h_beli ASC";
    if ($sort === 'ed_asc')     $orderBySql = "d.expire ASC";

    $tglAwalSql  = mysqli_real_escape_string($koneksi, $tgl_awal);
    $tglAkhirSql = mysqli_real_escape_string($koneksi, $tgl_akhir);

    $sqlPasif = "SELECT
                    d.kode_brng,
                    d.nama_brng,
                    d.kode_sat,
                    d.h_beli,
                    d.expire,
                    COALESCE(j.nama, '-') AS nama_jenis,
                    COALESCE(g.total_stok, 0) AS total_stok
                FROM databarang d
                LEFT JOIN (
                    SELECT kode_brng, SUM(stok) AS total_stok
                    FROM gudangbarang
                    GROUP BY kode_brng
                ) g ON d.kode_brng = g.kode_brng
                LEFT JOIN jenis j ON d.kdjns = j.kdjns
                WHERE d.status = '1'
                  AND (g.total_stok IS NULL OR g.total_stok = 0)
                  $whereExtra
                  AND d.kode_brng NOT IN (
                      SELECT DISTINCT r.kode_brng
                      FROM riwayat_barang_medis r
                      WHERE r.tanggal BETWEEN '$tglAwalSql' AND '$tglAkhirSql'
                        AND r.keluar > 0
                        AND r.posisi IN ($posisiInSql)
                  )
                ORDER BY $orderBySql";

    $resPasif = mysqli_query($koneksi, $sqlPasif);
    if ($resPasif) {
        while ($r = mysqli_fetch_assoc($resPasif)) {
            $rowsPasif[] = $r;
            $pasifTotalItem++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Dead Stock &amp; Barang Non-Aktif - RSUD Pringsewu</title>
    <meta name="description" content="Dashboard monitoring stok mati (dead stock), slow moving, dan audit barang non-aktif farmasi RSUD Pringsewu">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary:       #0d9488;
            --primary-light: #2dd4bf;
            --primary-dark:  #115e59;
            --danger:        #dc2626;
            --danger-light:  #fee2e2;
            --warning:       #d97706;
            --warning-light: #fef3c7;
            --info:          #0284c7;
            --info-light:    #e0f2fe;
            --success:       #16a34a;
            --card-bg:       #ffffff;
            --text:          #0f172a;
            --text-muted:    #64748b;
            --border:        #e2e8f0;
            --shadow:        0 4px 20px rgba(13, 148, 136, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding-bottom: 60px;
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, #0f172a 0%, #115e59 55%, #0d9488 100%);
            padding: 30px 24px 26px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(13, 148, 136, 0.25);
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
            width: 260px; height: 260px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }
        .header-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(45, 212, 191, 0.2);
            border: 1px solid rgba(45, 212, 191, 0.4);
            color: #99f6e4;
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
            font-size: 1.8rem;
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

        .back-nav { margin-bottom: 16px; }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8.5px 16px;
            background: #fff;
            color: #0f766e;
            border: 1.5px solid #99f6e4;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.08);
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #0d9488;
            color: #fff;
            border-color: #0d9488;
            transform: translateX(-3px);
        }

        /* TABS NAVIGATION */
        .tabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 2px;
            flex-wrap: wrap;
        }
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px 10px 0 0;
            background: #e2e8f0;
            color: #475569;
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
            transition: all 0.2s;
            border: 1px solid transparent;
            border-bottom: none;
        }
        .tab-btn:hover { background: #cbd5e1; color: var(--text); }
        .tab-btn.active {
            background: #fff;
            color: var(--primary-dark);
            border-color: #cbd5e1;
            border-top: 3px solid var(--primary);
            position: relative;
            margin-bottom: -2px;
            box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.04);
        }
        .tab-count {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 800;
        }
        .tab-btn.active .tab-count { background: #ccfbf1; color: var(--primary-dark); }
        .tab-btn:not(.active) .tab-count { background: #cbd5e1; color: #475569; }

        /* FILTER CARD */
        .filter-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 22px;
        }
        .filter-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .filter-presets {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .preset-pill {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.15s;
        }
        .preset-pill:hover { background: #e2e8f0; color: #0f172a; }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
            background: #fff;
        }

        /* Checkbox Group untuk Transaksi Keluar */
        .tx-group-box {
            margin-top: 14px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1.5px solid var(--border);
            border-radius: 8px;
        }
        .tx-group-title {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tx-checkboxes {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .tx-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text);
            cursor: pointer;
            user-select: none;
        }
        .tx-checkbox-label input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .filter-actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8.5px 18px;
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
        .btn-sm { padding: 5px 10px; font-size: 0.78rem; border-radius: 6px; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35); }
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
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #fff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.22);
        }
        .btn-info:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.32); }

        /* STATS CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-card.teal::before   { background: linear-gradient(90deg, #0d9488, #2dd4bf); }
        .stat-card.amber::before  { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .stat-card.red::before    { background: linear-gradient(90deg, #dc2626, #f87171); }
        .stat-card.blue::before   { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .stat-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-value { font-size: 1.55rem; font-weight: 700; line-height: 1.15; }
        .stat-card.teal .stat-value  { color: #0d9488; }
        .stat-card.amber .stat-value { color: #d97706; }
        .stat-card.red .stat-value   { color: #dc2626; }
        .stat-card.blue .stat-value  { color: #0284c7; }

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

        table.data-table {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.84rem;
        }
        table.data-table thead th {
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
        table.data-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        table.data-table tbody tr:last-child { border-bottom: none; }
        table.data-table tbody tr:nth-child(even) { background: #f8fafc; }
        table.data-table tbody tr:hover { background: #f0fdfa; }
        table.data-table td {
            padding: 11px 12px;
            vertical-align: top;
            color: var(--text);
            border: none;
        }

        .td-num   { text-align: center; color: var(--text-muted); font-size: 0.78rem; width: 36px; }
        .td-mono  { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8rem; font-weight: 600; color: #0f766e; }
        .td-right { text-align: right; }
        .td-center { text-align: center; }

        .item-name { font-weight: 700; color: #0f172a; }
        .item-category { font-size: 0.74rem; color: var(--text-muted); margin-top: 2px; }
        
        /* Badges */
        .badge-ed {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.73rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-ed-expired { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-ed-near    { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-ed-safe    { background: #f1f5f9; color: #475569; }

        .badge-anomaly {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 3px 8px;
            border-radius: 99px;
            font-size: 0.73rem;
            font-weight: 700;
        }
        .badge-normal-archive {
            display: inline-block;
            background: #f1f5f9;
            color: #64748b;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: 0.73rem;
            font-weight: 600;
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
            background: linear-gradient(135deg, #115e59, #0d9488);
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
        .item-summary-box {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-left: 4px solid #0d9488;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .depo-table-wrap {
            max-height: 380px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .depo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.83rem;
        }
        .depo-table th,
        .depo-table td {
            border-bottom: 1px solid var(--border);
            padding: 8.5px 12px;
            text-align: left;
        }
        .depo-table th {
            background: #f1f5f9;
            color: var(--text);
            font-weight: 600;
            font-size: 0.76rem;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .depo-table tbody tr:hover { background: #f8fafc; }
        .depo-table tfoot td {
            background: #f0fdfa;
            color: #115e59;
            font-weight: 700;
            border-top: 2px solid #99f6e4;
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
            .page-header { background: #115e59 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 15px; }
            .back-nav, .tabs-nav, .filter-card, .btn, .no-copy { display: none !important; }
            .table-card { box-shadow: none; border: none; }
            table.data-table thead th { background: #334155 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.data-table th, table.data-table td { font-size: 9.5px; padding: 5px; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="header-tag"><i class="fas fa-boxes"></i> Pengendalian Stok Farmasi</div>
    <h1><i class="fas fa-box-archive"></i> Monitoring Dead Stock &amp; Barang Non-Aktif</h1>
    <p class="subtitle">Evaluasi dan pengendalian barang farmasi tidak bergerak serta audit kepatuhan master data</p>
</div>

<div class="container">
    <div class="back-nav">
        <a href="farmasi.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi
        </a>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="tabs-nav">
        <a href="?tab=deadstock" class="tab-btn <?php echo ($tab === 'deadstock') ? 'active' : ''; ?>">
            <i class="fas fa-ban"></i> Dead Stock (Stok Mati / Non-Moving)
            <span class="tab-count"><?php echo ($tab === 'deadstock') ? $deadTotalItem : 'Stok Mati'; ?></span>
        </a>
        <a href="?tab=nonaktif" class="tab-btn <?php echo ($tab === 'nonaktif') ? 'active' : ''; ?>">
            <i class="fas fa-toggle-off"></i> Audit Barang Non-Aktif (Master Inactive)
            <span class="tab-count"><?php echo ($tab === 'nonaktif') ? $nonaktifTotalItem : 'Non-Aktif'; ?></span>
        </a>
        <a href="?tab=pasif" class="tab-btn <?php echo ($tab === 'pasif') ? 'active' : ''; ?>">
            <i class="fas fa-ghost"></i> Master Pasif (Kandidat Non-Aktif)
            <span class="tab-count"><?php echo ($tab === 'pasif') ? $pasifTotalItem : 'Stok 0'; ?></span>
        </a>
    </div>

    <!-- TAB 1: DEAD STOCK -->
    <?php if ($tab === 'deadstock'): ?>
        <!-- FILTER FORM DEAD STOCK -->
        <div class="filter-card">
            <form method="GET" action="" id="filterForm">
                <input type="hidden" name="tab" value="deadstock">
                <input type="hidden" name="submitted" value="1">

                <div class="filter-title">
                    <div>
                        <i class="fas fa-filter" style="color:var(--primary);"></i> Parameter Periode &amp; Filter Dead Stock
                    </div>
                    <div class="filter-presets">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Preset Periode:</span>
                        <button type="button" class="preset-pill" onclick="setPresetDays(30)">30 Hari</button>
                        <button type="button" class="preset-pill" onclick="setPresetDays(60)">60 Hari</button>
                        <button type="button" class="preset-pill" onclick="setPresetDays(90)">90 Hari (3 Bulan)</button>
                        <button type="button" class="preset-pill" onclick="setPresetDays(180)">180 Hari (6 Bulan)</button>
                        <button type="button" class="preset-pill" onclick="setPresetDays(365)">1 Tahun (365 Hari)</button>
                        <button type="button" class="preset-pill" onclick="setPresetYear2026()">Tahun 2026</button>
                    </div>
                </div>

                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="tgl_awal"><i class="fas fa-calendar-alt"></i> Periode Mulai</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>" required>
                    </div>
                    <div class="filter-field">
                        <label for="tgl_akhir"><i class="fas fa-calendar-day"></i> Periode Akhir</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                    </div>
                    <div class="filter-field">
                        <label for="kd_bangsal"><i class="fas fa-warehouse"></i> Lokasi / Depo</label>
                        <select name="kd_bangsal" id="kd_bangsal">
                            <option value="">-- Semua Depo / Gudang --</option>
                            <?php foreach ($bangsalList as $bg): ?>
                                <option value="<?php echo htmlspecialchars($bg['kd_bangsal']); ?>" <?php echo ($kd_bangsal === $bg['kd_bangsal']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($bg['nm_bangsal'] . ' (' . $bg['kd_bangsal'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="kdjns"><i class="fas fa-tags"></i> Jenis Barang</label>
                        <select name="kdjns" id="kdjns">
                            <option value="">-- Semua Jenis Barang --</option>
                            <?php foreach ($jenisList as $jn): ?>
                                <option value="<?php echo htmlspecialchars($jn['kdjns']); ?>" <?php echo ($kdjns === $jn['kdjns']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jn['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="sort"><i class="fas fa-sort-amount-down"></i> Urutkan Berdasarkan</label>
                        <select name="sort" id="sort">
                            <option value="nilai_desc" <?php echo ($sort === 'nilai_desc') ? 'selected' : ''; ?>>Nilai Aset Terbesar (Rp)</option>
                            <option value="nilai_asc"  <?php echo ($sort === 'nilai_asc')  ? 'selected' : ''; ?>>Nilai Aset Terkecil (Rp)</option>
                            <option value="stok_desc"  <?php echo ($sort === 'stok_desc')  ? 'selected' : ''; ?>>Stok Terbanyak</option>
                            <option value="stok_asc"   <?php echo ($sort === 'stok_asc')   ? 'selected' : ''; ?>>Stok Paling Sedikit</option>
                            <option value="ed_asc"     <?php echo ($sort === 'ed_asc')     ? 'selected' : ''; ?>>Tanggal ED Terdekat</option>
                            <option value="nama_asc"   <?php echo ($sort === 'nama_asc')   ? 'selected' : ''; ?>>Nama Barang (A - Z)</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="keyword"><i class="fas fa-search"></i> Kata Kunci</label>
                        <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Nama barang / kode">
                    </div>
                </div>

                <!-- Checkbox Kanal Transaksi Keluar yang Dihitung -->
                <div class="tx-group-box">
                    <div class="tx-group-title">
                        <i class="fas fa-check-double" style="color:var(--primary);"></i> Kanal Transaksi Keluar yang Dihitung sebagai Pergerakan Barang:
                    </div>
                    <div class="tx-checkboxes">
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_pemberian" value="1" <?php echo $tx_pemberian ? 'checked' : ''; ?>>
                            <span>Pemberian Obat Pasien (Resep Ralan/Ranap/IGD)</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_pengeluaran" value="1" <?php echo $tx_pengeluaran ? 'checked' : ''; ?>>
                            <span>Pengeluaran Obat &amp; BHP ke Ruangan (Stok Keluar)</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_resep_pulang" value="1" <?php echo $tx_resep_pulang ? 'checked' : ''; ?>>
                            <span>Resep Pulang Pasien</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_penjualan" value="1" <?php echo $tx_penjualan ? 'checked' : ''; ?>>
                            <span>Penjualan Bebas Apotek</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_mutasi" value="1" <?php echo $tx_mutasi ? 'checked' : ''; ?>>
                            <span>Mutasi Antar Depo</span>
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Dead Stock
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetFormDeadStock()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- STATS CARDS DEAD STOCK -->
        <div class="stats-row">
            <div class="stat-card teal">
                <span class="stat-label">Total Item Dead Stock</span>
                <span class="stat-value"><?php echo number_format($deadTotalItem, 0, ',', '.'); ?> item</span>
            </div>
            <div class="stat-card red">
                <span class="stat-label">Total Nilai Aset Terkunci</span>
                <span class="stat-value">Rp <?php echo number_format($deadTotalNilai, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-card amber">
                <span class="stat-label">Dead Stock Near-ED (&le; 6 Bulan)</span>
                <span class="stat-value"><?php echo number_format($deadNearEdCount, 0, ',', '.'); ?> item</span>
            </div>
            <div class="stat-card blue">
                <span class="stat-label">Durasi Periode Audit</span>
                <?php
                $dStart = new DateTime($tgl_awal);
                $dEnd   = new DateTime($tgl_akhir);
                $dDiff  = $dStart->diff($dEnd)->days + 1;
                ?>
                <span class="stat-value"><?php echo $dDiff; ?> hari</span>
            </div>
        </div>

        <!-- TABLE CARD DEAD STOCK -->
        <div class="table-card">
            <div class="table-toolbar">
                <div>
                    <div class="table-title">
                        <i class="fas fa-triangle-exclamation" style="color:var(--danger);"></i>
                        Daftar Barang Farmasi Tidak Bergerak (Dead Stock)
                    </div>
                    <div class="table-subtitle">
                        Periode: <strong><?php echo date('d/m/Y', strtotime($tgl_awal)); ?></strong> s/d <strong><?php echo date('d/m/Y', strtotime($tgl_akhir)); ?></strong>
                        &bull; Filter Lokasi: <strong><?php echo ($kd_bangsal !== '') ? htmlspecialchars($kd_bangsal) : 'Semua Depo/Gudang'; ?></strong>
                        &bull; Ditemukan: <strong><?php echo $deadTotalItem; ?> item</strong>
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($deadTotalItem > 0): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="copyTable('mainTable')">
                        <i class="fas fa-copy"></i> Copy Tabel
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($deadTotalItem > 0): ?>
            <div class="table-scroll">
                <table class="data-table" id="mainTable">
                    <thead>
                        <tr>
                            <th class="td-num">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang &amp; Jenis</th>
                            <th class="td-center">Satuan</th>
                            <th class="td-right">Harga Beli</th>
                            <th class="td-right">Sisa Stok</th>
                            <th class="td-right">Total Nilai Aset</th>
                            <th class="td-center">Tanggal Expire</th>
                            <th class="no-copy td-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    $todayTs = strtotime(date('Y-m-d'));
                    $sixMonthsTs = strtotime('+6 months', $todayTs);

                    foreach ($rowsDeadStock as $row):
                        // Cek status ED
                        $badgeEd = '<span class="badge-ed badge-ed-safe">-</span>';
                        if (!empty($row['expire']) && $row['expire'] !== '0000-00-00') {
                            $expTs = strtotime($row['expire']);
                            if ($expTs < $todayTs) {
                                $badgeEd = '<span class="badge-ed badge-ed-expired"><i class="fas fa-skull-crossbones"></i> ' . date('d/m/Y', $expTs) . ' (Expired)</span>';
                            } elseif ($expTs <= $sixMonthsTs) {
                                $badgeEd = '<span class="badge-ed badge-ed-near"><i class="fas fa-hourglass-half"></i> ' . date('d/m/Y', $expTs) . ' (Near-ED)</span>';
                            } else {
                                $badgeEd = '<span class="badge-ed badge-ed-safe">' . date('d/m/Y', $expTs) . '</span>';
                            }
                        }
                    ?>
                        <tr>
                            <td class="td-num"><?php echo $no++; ?></td>
                            <td class="td-mono"><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                            <td>
                                <div class="item-name"><?php echo htmlspecialchars($row['nama_brng']); ?></div>
                                <div class="item-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['nama_jenis']); ?></div>
                            </td>
                            <td class="td-center"><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                            <td class="td-right">Rp <?php echo number_format($row['h_beli'], 0, ',', '.'); ?></td>
                            <td class="td-right" style="font-weight:700; color:var(--text);"><?php echo number_format($row['total_stok'], 0, ',', '.'); ?></td>
                            <td class="td-right" style="font-weight:800; color:#dc2626;">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                            <td class="td-center"><?php echo $badgeEd; ?></td>
                            <td class="no-copy td-center">
                                <button type="button" class="btn btn-sm btn-info" onclick="openDepoModal('<?php echo htmlspecialchars($row['kode_brng']); ?>')">
                                    <i class="fas fa-boxes-stacked"></i> Rincian Depo
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f0fdfa; font-weight:800; border-top:2px solid #99f6e4;">
                            <td colspan="5" class="td-right">TOTAL NILAI KESELURUHAN (<?php echo $deadTotalItem; ?> item):</td>
                            <td class="td-right"></td>
                            <td class="td-right" style="color:#dc2626; font-size:0.95rem;">Rp <?php echo number_format($deadTotalNilai, 0, ',', '.'); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-circle-check" style="color:#16a34a;"></i>
                <h3>Tidak Ditemukan Barang Dead Stock</h3>
                <p>Seluruh barang farmasi aktif dengan stok &gt; 0 tercatat memiliki transaksi keluar pada parameter periode ini.</p>
            </div>
            <?php endif; ?>
        </div>

    <!-- TAB 2: AUDIT BARANG NON-AKTIF -->
    <?php elseif ($tab === 'nonaktif'): ?>
        <!-- FILTER FORM BARANG NON AKTIF -->
        <div class="filter-card">
            <form method="GET" action="" id="filterFormNonAktif">
                <input type="hidden" name="tab" value="nonaktif">

                <div class="filter-title">
                    <div>
                        <i class="fas fa-filter" style="color:var(--primary);"></i> Parameter Filter Audit Barang Non-Aktif
                    </div>
                </div>

                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="status_stok_nonaktif"><i class="fas fa-layer-group"></i> Kondisi Sisa Stok</label>
                        <select name="status_stok_nonaktif" id="status_stok_nonaktif">
                            <option value="ada_stok" <?php echo ($status_stok_nonaktif === 'ada_stok') ? 'selected' : ''; ?>>
                                ⚠️ Hanya yang Masih Ada Sisa Stok (Anomali Kritis)
                            </option>
                            <option value="semua" <?php echo ($status_stok_nonaktif === 'semua') ? 'selected' : ''; ?>>
                                Semua Barang Non-Aktif
                            </option>
                            <option value="stok_nol" <?php echo ($status_stok_nonaktif === 'stok_nol') ? 'selected' : ''; ?>>
                                Stok Bersih / Kosong (0)
                            </option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="kd_bangsal_na"><i class="fas fa-warehouse"></i> Lokasi / Depo</label>
                        <select name="kd_bangsal" id="kd_bangsal_na">
                            <option value="">-- Semua Depo / Gudang --</option>
                            <?php foreach ($bangsalList as $bg): ?>
                                <option value="<?php echo htmlspecialchars($bg['kd_bangsal']); ?>" <?php echo ($kd_bangsal === $bg['kd_bangsal']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($bg['nm_bangsal'] . ' (' . $bg['kd_bangsal'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="kdjns_na"><i class="fas fa-tags"></i> Jenis Barang</label>
                        <select name="kdjns" id="kdjns_na">
                            <option value="">-- Semua Jenis Barang --</option>
                            <?php foreach ($jenisList as $jn): ?>
                                <option value="<?php echo htmlspecialchars($jn['kdjns']); ?>" <?php echo ($kdjns === $jn['kdjns']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jn['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="sort_na"><i class="fas fa-sort-amount-down"></i> Urutkan Berdasarkan</label>
                        <select name="sort" id="sort_na">
                            <option value="nilai_desc" <?php echo ($sort === 'nilai_desc') ? 'selected' : ''; ?>>Nilai Aset Terbesar (Rp)</option>
                            <option value="nama_asc"   <?php echo ($sort === 'nama_asc')   ? 'selected' : ''; ?>>Nama Barang (A - Z)</option>
                            <option value="ed_asc"     <?php echo ($sort === 'ed_asc')     ? 'selected' : ''; ?>>Tanggal ED Terdekat</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="keyword_na"><i class="fas fa-search"></i> Kata Kunci</label>
                        <input type="text" id="keyword_na" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Nama barang / kode">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Data
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?tab=nonaktif'">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- STATS CARDS BARANG NON AKTIF -->
        <div class="stats-row">
            <div class="stat-card red">
                <span class="stat-label">Barang Non-Aktif Bersaldo Stok</span>
                <span class="stat-value"><?php echo number_format($nonaktifWithStockCount, 0, ',', '.'); ?> item</span>
            </div>
            <div class="stat-card amber">
                <span class="stat-label">Total Nilai Aset Terkunci</span>
                <span class="stat-value">Rp <?php echo number_format($nonaktifTotalNilai, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-card blue">
                <span class="stat-label">Total Item Sesuai Filter</span>
                <span class="stat-value"><?php echo number_format($nonaktifTotalItem, 0, ',', '.'); ?> item</span>
            </div>
        </div>

        <!-- TABLE CARD BARANG NON AKTIF -->
        <div class="table-card">
            <div class="table-toolbar">
                <div>
                    <div class="table-title">
                        <i class="fas fa-toggle-off" style="color:var(--text-muted);"></i>
                        Daftar Master Barang Non-Aktif (Status = '0')
                    </div>
                    <div class="table-subtitle">
                        Kondisi: <strong><?php echo ($status_stok_nonaktif === 'ada_stok') ? 'Hanya yang Masih Ada Sisa Stok (Anomali)' : (($status_stok_nonaktif === 'stok_nol') ? 'Stok Kosong / Bersih' : 'Semua Non-Aktif'); ?></strong>
                        &bull; Ditemukan: <strong><?php echo $nonaktifTotalItem; ?> item</strong>
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($nonaktifTotalItem > 0): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="copyTable('tableNonAktif')">
                        <i class="fas fa-copy"></i> Copy Tabel
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($nonaktifTotalItem > 0): ?>
            <div class="table-scroll">
                <table class="data-table" id="tableNonAktif">
                    <thead>
                        <tr>
                            <th class="td-num">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang &amp; Jenis</th>
                            <th class="td-center">Satuan</th>
                            <th class="td-right">Harga Beli</th>
                            <th class="td-right">Sisa Stok Fisik</th>
                            <th class="td-right">Total Nilai Aset</th>
                            <th class="td-center">Status Anomali</th>
                            <th class="no-copy td-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    foreach ($rowsNonAktif as $row):
                        $stk = floatval($row['total_stok']);
                    ?>
                        <tr>
                            <td class="td-num"><?php echo $no++; ?></td>
                            <td class="td-mono"><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                            <td>
                                <div class="item-name"><?php echo htmlspecialchars($row['nama_brng']); ?></div>
                                <div class="item-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['nama_jenis']); ?></div>
                            </td>
                            <td class="td-center"><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                            <td class="td-right">Rp <?php echo number_format($row['h_beli'], 0, ',', '.'); ?></td>
                            <td class="td-right" style="font-weight:700; color:<?php echo ($stk > 0) ? '#dc2626' : 'var(--text-muted)'; ?>;">
                                <?php echo number_format($stk, 0, ',', '.'); ?>
                            </td>
                            <td class="td-right" style="font-weight:800; color:<?php echo ($stk > 0) ? '#dc2626' : 'var(--text-muted)'; ?>;">
                                Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?>
                            </td>
                            <td class="td-center">
                                <?php if ($stk > 0): ?>
                                    <span class="badge-anomaly"><i class="fas fa-triangle-exclamation"></i> Stok Terkunci!</span>
                                <?php else: ?>
                                    <span class="badge-normal-archive"><i class="fas fa-check"></i> Arsip Normal</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-copy td-center">
                                <button type="button" class="btn btn-sm btn-info" onclick="openDepoModal('<?php echo htmlspecialchars($row['kode_brng']); ?>')">
                                    <i class="fas fa-boxes-stacked"></i> Rincian Depo
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #cbd5e1;">
                            <td colspan="5" class="td-right">TOTAL NILAI (<?php echo $nonaktifTotalItem; ?> item):</td>
                            <td class="td-right"></td>
                            <td class="td-right" style="color:#dc2626; font-size:0.95rem;">Rp <?php echo number_format($nonaktifTotalNilai, 0, ',', '.'); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-circle-check" style="color:#16a34a;"></i>
                <h3>Tidak Ditemukan Data Barang Non-Aktif</h3>
                <p>Tidak ada data yang memenuhi kriteria filter barang non-aktif yang dipilih.</p>
            </div>
            <?php endif; ?>
        </div>

    <!-- TAB 3: MASTER PASIF (KANDIDAT NON-AKTIF) -->
    <?php else: ?>
        <!-- FILTER FORM MASTER PASIF -->
        <div class="filter-card">
            <form method="GET" action="" id="filterFormPasif">
                <input type="hidden" name="tab" value="pasif">

                <div class="filter-title">
                    <div>
                        <i class="fas fa-filter" style="color:var(--primary);"></i> Parameter Audit Master Pasif (Kandidat Non-Aktif)
                    </div>
                    <div class="filter-presets">
                        <span style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Preset Periode:</span>
                        <button type="button" class="preset-pill" onclick="setPresetDaysPasif(90)">90 Hari (3 Bulan)</button>
                        <button type="button" class="preset-pill" onclick="setPresetDaysPasif(180)">180 Hari (6 Bulan)</button>
                        <button type="button" class="preset-pill" onclick="setPresetDaysPasif(365)">1 Tahun (365 Hari)</button>
                        <button type="button" class="preset-pill" onclick="setPresetYear2026Pasif()">Tahun 2026</button>
                    </div>
                </div>

                <div class="filter-grid">
                    <div class="filter-field">
                        <label for="tgl_awal_pasif"><i class="fas fa-calendar-alt"></i> Periode Mulai Audit</label>
                        <input type="date" id="tgl_awal_pasif" name="tgl_awal" value="<?php echo htmlspecialchars($tgl_awal); ?>" required>
                    </div>
                    <div class="filter-field">
                        <label for="tgl_akhir_pasif"><i class="fas fa-calendar-day"></i> Periode Akhir Audit</label>
                        <input type="date" id="tgl_akhir_pasif" name="tgl_akhir" value="<?php echo htmlspecialchars($tgl_akhir); ?>" required>
                    </div>
                    <div class="filter-field">
                        <label for="kdjns_pasif"><i class="fas fa-tags"></i> Jenis Barang</label>
                        <select name="kdjns" id="kdjns_pasif">
                            <option value="">-- Semua Jenis Barang --</option>
                            <?php foreach ($jenisList as $jn): ?>
                                <option value="<?php echo htmlspecialchars($jn['kdjns']); ?>" <?php echo ($kdjns === $jn['kdjns']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jn['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="sort_pasif"><i class="fas fa-sort-amount-down"></i> Urutkan Berdasarkan</label>
                        <select name="sort" id="sort_pasif">
                            <option value="nama_asc"   <?php echo ($sort === 'nama_asc')   ? 'selected' : ''; ?>>Nama Barang (A - Z)</option>
                            <option value="nilai_desc" <?php echo ($sort === 'nilai_desc') ? 'selected' : ''; ?>>Harga Beli Terbesar (Rp)</option>
                            <option value="nilai_asc"  <?php echo ($sort === 'nilai_asc')  ? 'selected' : ''; ?>>Harga Beli Terkecil (Rp)</option>
                            <option value="ed_asc"     <?php echo ($sort === 'ed_asc')     ? 'selected' : ''; ?>>Tanggal ED Terdekat</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="keyword_pasif"><i class="fas fa-search"></i> Kata Kunci</label>
                        <input type="text" id="keyword_pasif" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Nama barang / kode">
                    </div>
                </div>

                <!-- Checkbox Kanal Transaksi Keluar yang Dihitung -->
                <div class="tx-group-box">
                    <div class="tx-group-title">
                        <i class="fas fa-check-double" style="color:var(--primary);"></i> Verifikasi Transaksi Keluar pada Periode Audit:
                    </div>
                    <div class="tx-checkboxes">
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_pemberian" value="1" <?php echo $tx_pemberian ? 'checked' : ''; ?>>
                            <span>Pemberian Obat Pasien</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_pengeluaran" value="1" <?php echo $tx_pengeluaran ? 'checked' : ''; ?>>
                            <span>Pengeluaran Obat &amp; BHP Ruangan</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_resep_pulang" value="1" <?php echo $tx_resep_pulang ? 'checked' : ''; ?>>
                            <span>Resep Pulang</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_penjualan" value="1" <?php echo $tx_penjualan ? 'checked' : ''; ?>>
                            <span>Penjualan Apotek</span>
                        </label>
                        <label class="tx-checkbox-label">
                            <input type="checkbox" name="tx_mutasi" value="1" <?php echo $tx_mutasi ? 'checked' : ''; ?>>
                            <span>Mutasi Antar Depo</span>
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan Master Pasif
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetFormPasif()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- STATS CARDS MASTER PASIF -->
        <div class="stats-row">
            <div class="stat-card amber">
                <span class="stat-label">Total Master Pasif (Kandidat Non-Aktif)</span>
                <span class="stat-value"><?php echo number_format($pasifTotalItem, 0, ',', '.'); ?> item</span>
            </div>
            <div class="stat-card teal">
                <span class="stat-label">Total Katalog Aktif di SIMRS</span>
                <span class="stat-value"><?php echo number_format($totalMasterAktif, 0, ',', '.'); ?> item</span>
            </div>
            <div class="stat-card red">
                <span class="stat-label">Rasio Beban Katalog Pasif</span>
                <?php
                $pctPasif = ($totalMasterAktif > 0) ? round(($pasifTotalItem / $totalMasterAktif) * 100, 1) : 0;
                ?>
                <span class="stat-value"><?php echo $pctPasif; ?>%</span>
            </div>
            <div class="stat-card blue">
                <span class="stat-label">Durasi Periode Audit</span>
                <?php
                $dStartP = new DateTime($tgl_awal);
                $dEndP   = new DateTime($tgl_akhir);
                $dDiffP  = $dStartP->diff($dEndP)->days + 1;
                ?>
                <span class="stat-value"><?php echo $dDiffP; ?> hari</span>
            </div>
        </div>

        <!-- TABLE CARD MASTER PASIF -->
        <div class="table-card">
            <div class="table-toolbar">
                <div>
                    <div class="table-title">
                        <i class="fas fa-ghost" style="color:var(--warning);"></i>
                        Daftar Master Aktif Tanpa Stok &amp; Tanpa Transaksi (Kandidat Non-Aktif)
                    </div>
                    <div class="table-subtitle">
                        Kriteria: <strong>Status = Aktif ('1')</strong> &bull; <strong>Sisa Stok = 0</strong> &bull; <strong>0 Transaksi Keluar</strong> periode: <strong><?php echo date('d/m/Y', strtotime($tgl_awal)); ?></strong> s/d <strong><?php echo date('d/m/Y', strtotime($tgl_akhir)); ?></strong>
                        &bull; Ditemukan: <strong><?php echo $pasifTotalItem; ?> item</strong>
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($pasifTotalItem > 0): ?>
                    <button type="button" class="btn btn-success btn-sm" onclick="copyTable('tablePasif')">
                        <i class="fas fa-copy"></i> Copy Tabel
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background:#fffbeb; border:1px solid #fef3c7; border-left:4px solid #f59e0b; padding:12px 16px; border-radius:6px; margin:0 24px 16px; font-size:0.83rem; color:#92400e;">
                <i class="fas fa-circle-info" style="margin-right:6px;"></i>
                <strong>Tujuan Rekomendasi:</strong> Barang-barang di bawah ini berstatus aktif namun stok fisiknya sudah 0 dan tidak pernah digunakan selama periode audit (<?php echo $dDiffP; ?> hari). Untuk meringankan pencarian saat dokter/farmasi meresepkan dan menjaga master data tetap rapi, barang-barang ini direkomendasikan untuk diubah statusnya menjadi <strong>Non-Aktif (Status = '0')</strong> pada Master Data Barang SIMRS Khanza.
            </div>

            <?php if ($pasifTotalItem > 0): ?>
            <div class="table-scroll">
                <table class="data-table" id="tablePasif">
                    <thead>
                        <tr>
                            <th class="td-num">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang &amp; Jenis</th>
                            <th class="td-center">Satuan</th>
                            <th class="td-right">Harga Beli Terakhir</th>
                            <th class="td-right">Sisa Stok Fisik</th>
                            <th class="td-center">Tanggal Expire Terakhir</th>
                            <th class="td-center">Rekomendasi</th>
                            <th class="no-copy td-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    foreach ($rowsPasif as $row):
                    ?>
                        <tr>
                            <td class="td-num"><?php echo $no++; ?></td>
                            <td class="td-mono"><?php echo htmlspecialchars($row['kode_brng']); ?></td>
                            <td>
                                <div class="item-name"><?php echo htmlspecialchars($row['nama_brng']); ?></div>
                                <div class="item-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['nama_jenis']); ?></div>
                            </td>
                            <td class="td-center"><?php echo htmlspecialchars($row['kode_sat']); ?></td>
                            <td class="td-right">Rp <?php echo number_format($row['h_beli'], 0, ',', '.'); ?></td>
                            <td class="td-right" style="font-weight:700; color:var(--text-muted);">
                                0
                            </td>
                            <td class="td-center">
                                <?php
                                if (!empty($row['expire']) && $row['expire'] !== '0000-00-00') {
                                    echo date('d/m/Y', strtotime($row['expire']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="td-center">
                                <span class="badge-ed badge-ed-near"><i class="fas fa-ghost"></i> Kandidat Non-Aktif</span>
                            </td>
                            <td class="no-copy td-center">
                                <button type="button" class="btn btn-sm btn-info" onclick="openDepoModal('<?php echo htmlspecialchars($row['kode_brng']); ?>')">
                                    <i class="fas fa-boxes-stacked"></i> Rincian &amp; Riwayat
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #cbd5e1;">
                            <td colspan="5" class="td-right">TOTAL BARANG MASTER PASIF:</td>
                            <td class="td-right" style="color:var(--text-muted);">0</td>
                            <td colspan="3" style="color:#64748b; font-size:0.85rem; font-weight:600;">
                                <?php echo number_format($pasifTotalItem, 0, ',', '.'); ?> item siap dievaluasi / di-non-aktifkan
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-circle-check" style="color:#16a34a;"></i>
                <h3>Tidak Ditemukan Barang Master Pasif</h3>
                <p>Seluruh master barang aktif dengan stok 0 tercatat memiliki transaksi keluar pada parameter periode ini atau tidak memenuhi filter.</p>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL POPUP RINCIAN SEBARAN DEPO & RIWAYAT -->
<div id="depoModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fas fa-boxes-stacked"></i> Rincian Sebaran Stok per Depo / Lokasi</h3>
            <button type="button" class="modal-close" onclick="closeDepoModal()" title="Tutup">&times;</button>
        </div>
        <div class="modal-body">
            <div id="itemSummary" class="item-summary-box"></div>

            <div style="font-weight:700; font-size:0.86rem; margin-bottom:6px; color:var(--text);">
                <i class="fas fa-warehouse" style="color:var(--primary);"></i> Lokasi &amp; Jumlah Stok:
            </div>
            <div id="depoContent"></div>

            <div style="font-weight:700; font-size:0.86rem; margin:14px 0 6px; color:var(--text);">
                <i class="fas fa-clock-rotate-left" style="color:var(--primary);"></i> 5 Transaksi Keluar Terakhir (Sebelumnya):
            </div>
            <div id="lastTxContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDepoModal()">Tutup</button>
        </div>
    </div>
</div>

<div id="toast"><i class="fas fa-check-circle"></i> Data berhasil disalin ke clipboard!</div>

<script>
    function formatDate(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    function setPresetDays(days) {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - days);
        document.getElementById('tgl_awal').value = formatDate(start);
        document.getElementById('tgl_akhir').value = formatDate(end);
        document.getElementById('filterForm').submit();
    }

    function setPresetYear2026() {
        document.getElementById('tgl_awal').value = '2026-01-01';
        document.getElementById('tgl_akhir').value = '2026-12-31';
        document.getElementById('filterForm').submit();
    }

    function resetFormDeadStock() {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - 90);
        document.getElementById('tgl_awal').value = formatDate(start);
        document.getElementById('tgl_akhir').value = formatDate(end);
        document.getElementById('kd_bangsal').value = '';
        document.getElementById('kdjns').value = '';
        document.getElementById('sort').value = 'nilai_desc';
        document.getElementById('keyword').value = '';
        document.getElementById('filterForm').submit();
    }

    function setPresetDaysPasif(days) {
        const end = new Date();
        const start = new Date();
        start.setDate(end.getDate() - days);
        document.getElementById('tgl_awal_pasif').value = formatDate(start);
        document.getElementById('tgl_akhir_pasif').value = formatDate(end);
        document.getElementById('filterFormPasif').submit();
    }

    function setPresetYear2026Pasif() {
        document.getElementById('tgl_awal_pasif').value = '2026-01-01';
        document.getElementById('tgl_akhir_pasif').value = '2026-12-31';
        document.getElementById('filterFormPasif').submit();
    }

    function resetFormPasif() {
        window.location.href = '?tab=pasif';
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

    const depoModal = document.getElementById('depoModal');
    const itemSummary = document.getElementById('itemSummary');
    const depoContent = document.getElementById('depoContent');
    const lastTxContent = document.getElementById('lastTxContent');

    function openDepoModal(kodeBrng) {
        itemSummary.innerHTML = 'Memuat data barang...';
        depoContent.innerHTML = '<p style="padding:15px; color:#64748b; text-align:center;"><i class="fas fa-spinner fa-spin"></i> Memuat sebaran stok depo...</p>';
        lastTxContent.innerHTML = '<p style="padding:10px; color:#64748b; text-align:center;">Memuat riwayat...</p>';
        depoModal.classList.add('active');
        depoModal.setAttribute('aria-hidden', 'false');

        fetch('monitoring_dead_stock.php?action=get_stok_depo&kode_brng=' + encodeURIComponent(kodeBrng) + '&_t=' + Date.now())
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    depoContent.innerHTML = '<p style="color:#dc2626; padding:15px;">' + escapeHtml(data.message || 'Gagal mengambil data.') + '</p>';
                    return;
                }
                const info = data.info;
                const statusText = info.status === '1' 
                    ? '<span style="background:#ccfbf1; color:#0f766e; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">Aktif</span>'
                    : '<span style="background:#fee2e2; color:#dc2626; padding:2px 6px; border-radius:4px; font-weight:700; font-size:11px;">Non-Aktif</span>';

                itemSummary.innerHTML =
                    '<strong>[' + escapeHtml(info.kode_brng) + '] ' + escapeHtml(info.nama_brng) + '</strong> &bull; ' + statusText + '<br>' +
                    'Jenis: <strong>' + escapeHtml(info.nama_jenis) + '</strong> &bull; ' +
                    'Harga Beli: <strong>Rp ' + formatNumber(info.h_beli) + '</strong> &bull; ' +
                    'Satuan: <strong>' + escapeHtml(info.kode_sat) + '</strong> &bull; ' +
                    'Total Stok Terkunci: <strong style="color:#dc2626; font-size:1rem;">' + formatNumber(data.total_stok) + ' ' + escapeHtml(info.kode_sat) + '</strong>';

                // Tabel Depo
                const depos = data.stok_depo || [];
                if (depos.length === 0) {
                    depoContent.innerHTML = '<p style="padding:12px; color:#64748b; font-style:italic;">Tidak ada sisa stok di depo/gudang.</p>';
                } else {
                    let htmlD = '<div class="depo-table-wrap"><table class="depo-table">';
                    htmlD += '<thead><tr><th>Kode</th><th>Nama Depo / Lokasi</th><th>No. Batch</th><th>No. Faktur</th><th style="text-align:right;">Stok</th></tr></thead><tbody>';
                    depos.forEach(dp => {
                        htmlD += '<tr>' +
                                 '<td style="font-family:monospace; font-size:11px;">' + escapeHtml(dp.kd_bangsal) + '</td>' +
                                 '<td><strong>' + escapeHtml(dp.nm_bangsal) + '</strong></td>' +
                                 '<td>' + escapeHtml(dp.no_batch || '-') + '</td>' +
                                 '<td>' + escapeHtml(dp.no_faktur || '-') + '</td>' +
                                 '<td style="text-align:right; font-weight:700; color:#0f766e;">' + formatNumber(dp.stok) + ' ' + escapeHtml(info.kode_sat) + '</td>' +
                                 '</tr>';
                    });
                    htmlD += '</tbody><tfoot><tr><td colspan="4" style="text-align:right;">TOTAL STOK KESELURUHAN:</td><td style="text-align:right; color:#dc2626;">' + formatNumber(data.total_stok) + ' ' + escapeHtml(info.kode_sat) + '</td></tr></tfoot></table></div>';
                    depoContent.innerHTML = htmlD;
                }

                // Tabel Riwayat Keluar Terakhir
                const txs = data.last_transactions || [];
                if (txs.length === 0) {
                    lastTxContent.innerHTML = '<p style="padding:10px; color:#64748b; font-style:italic;">Belum pernah ada catatan transaksi keluar pada riwayat barang medis.</p>';
                } else {
                    let htmlTx = '<div class="depo-table-wrap"><table class="depo-table">';
                    htmlTx += '<thead><tr><th>Tanggal &amp; Jam</th><th>Posisi / Kanal</th><th>Lokasi</th><th style="text-align:right;">Jumlah Keluar</th><th>Keterangan</th></tr></thead><tbody>';
                    txs.forEach(t => {
                        htmlTx += '<tr>' +
                                  '<td>' + escapeHtml(t.tanggal) + ' ' + escapeHtml(t.jam) + '</td>' +
                                  '<td><span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-weight:600; font-size:11px;">' + escapeHtml(t.posisi) + '</span></td>' +
                                  '<td>' + escapeHtml(t.nm_bangsal || '-') + '</td>' +
                                  '<td style="text-align:right; font-weight:700;">' + formatNumber(t.keluar) + '</td>' +
                                  '<td><span style="font-size:11px; color:#64748b;">' + escapeHtml(t.keterangan || '-') + '</span></td>' +
                                  '</tr>';
                    });
                    htmlTx += '</tbody></table></div>';
                    lastTxContent.innerHTML = htmlTx;
                }
            })
            .catch(err => {
                console.error(err);
                depoContent.innerHTML = '<p style="color:#dc2626; padding:15px;">Terjadi kesalahan saat memuat rincian.</p>';
            });
    }

    function closeDepoModal() {
        depoModal.classList.remove('active');
        depoModal.setAttribute('aria-hidden', 'true');
    }

    depoModal.addEventListener('click', function(e) {
        if (e.target === depoModal) {
            closeDepoModal();
        }
    });

    function copyTable(tableId) {
        const table = document.getElementById(tableId);
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
