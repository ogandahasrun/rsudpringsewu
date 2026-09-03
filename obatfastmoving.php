<?php
include 'koneksi.php';

// Ambil tanggal filter (POST atau GET), default tanggal 1 bulan ini s/d hari ini
$tanggal_awal = isset($_POST['tanggal_awal']) ? $_POST['tanggal_awal'] : (isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01'));
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : (isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d'));

// Filter hanya yang ada transaksi atau semua
$hanya_ada_transaksi = isset($_POST['hanya_ada_transaksi']) ? $_POST['hanya_ada_transaksi'] : (isset($_GET['hanya_ada_transaksi']) ? $_GET['hanya_ada_transaksi'] : '1');

// Filter Limit (Top N)
$limit_tampil = isset($_POST['limit_tampil']) ? $_POST['limit_tampil'] : (isset($_GET['limit_tampil']) ? $_GET['limit_tampil'] : 'semua');

// Sanitasi tanggal
$tgl_awal_esc = mysqli_real_escape_string($koneksi, $tanggal_awal);
$tgl_akhir_esc = mysqli_real_escape_string($koneksi, $tanggal_akhir);

// 1. Ambil data master barang aktif
$barang = [];
$q_barang = mysqli_query($koneksi, "SELECT kode_brng, nama_brng, h_beli, kode_sat FROM databarang WHERE status='1'");
if ($q_barang) {
    while ($row = mysqli_fetch_assoc($q_barang)) {
        $barang[$row['kode_brng']] = [
            'nama_brng' => $row['nama_brng'],
            'h_beli'    => (float)$row['h_beli'],
            'kode_sat'  => $row['kode_sat']
        ];
    }
}

// 2. Ambil stok real-time per lokasi bangsal/gudang
$stok_lokasi = [];
$q_stok = mysqli_query($koneksi, "SELECT kode_brng, kd_bangsal, stok FROM gudangbarang");
if ($q_stok) {
    while ($row = mysqli_fetch_assoc($q_stok)) {
        $kode = $row['kode_brng'];
        $bangsal = strtoupper(trim($row['kd_bangsal']));
        $stok_lokasi[$kode][$bangsal] = (float)$row['stok'];
    }
}

// 3. Stok Keluar GO (Frekuensi dihitung 1 per nomor keluar, dan total qty fisik)
$keluar_go = [];
$q_keluar = mysqli_query($koneksi, "
    SELECT detail_pengeluaran_obat_bhp.kode_brng, 
           COUNT(DISTINCT detail_pengeluaran_obat_bhp.no_keluar) AS frekuensi,
           SUM(detail_pengeluaran_obat_bhp.jumlah) AS total_qty
    FROM pengeluaran_obat_bhp
    INNER JOIN detail_pengeluaran_obat_bhp ON detail_pengeluaran_obat_bhp.no_keluar = pengeluaran_obat_bhp.no_keluar
    WHERE pengeluaran_obat_bhp.kd_bangsal = 'GO'
      AND pengeluaran_obat_bhp.tanggal BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
    GROUP BY detail_pengeluaran_obat_bhp.kode_brng
");
if ($q_keluar) {
    while ($row = mysqli_fetch_assoc($q_keluar)) {
        $keluar_go[$row['kode_brng']] = [
            'frekuensi' => (int)$row['frekuensi'],
            'total_qty' => (float)$row['total_qty']
        ];
    }
}

// 4. Pengeluaran obat ke pasien (detail_pemberian_obat)
// Dihitung 1 kali per pemberian pasien (bukan jumlah tablet)
$pengeluaran_obat = [];
$q_pengeluaran = mysqli_query($koneksi, "
    SELECT detail_pemberian_obat.kode_brng, 
           COUNT(*) AS frekuensi,
           SUM(detail_pemberian_obat.jml) AS total_qty
    FROM detail_pemberian_obat
    WHERE detail_pemberian_obat.tgl_perawatan BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
    GROUP BY detail_pemberian_obat.kode_brng
");
if ($q_pengeluaran) {
    while ($row = mysqli_fetch_assoc($q_pengeluaran)) {
        $pengeluaran_obat[$row['kode_brng']] = [
            'frekuensi' => (int)$row['frekuensi'],
            'total_qty' => (float)$row['total_qty']
        ];
    }
}

// 5. Resep pulang (resep_pulang)
// Dihitung 1 kali per resep keluar pasien
$resep_pulang = [];
$q_resep = mysqli_query($koneksi, "
    SELECT resep_pulang.kode_brng, 
           COUNT(*) AS frekuensi,
           SUM(resep_pulang.jml_barang) AS total_qty
    FROM resep_pulang
    WHERE resep_pulang.tanggal BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
    GROUP BY resep_pulang.kode_brng
");
if ($q_resep) {
    while ($row = mysqli_fetch_assoc($q_resep)) {
        $resep_pulang[$row['kode_brng']] = [
            'frekuensi' => (int)$row['frekuensi'],
            'total_qty' => (float)$row['total_qty']
        ];
    }
}

// 6. Penjualan bebas (penjualan & detailjual)
// Dihitung 1 kali per nota jual
$penjualan_bebas = [];
$q_jual = mysqli_query($koneksi, "
    SELECT detailjual.kode_brng, 
           COUNT(DISTINCT detailjual.nota_jual) AS frekuensi,
           SUM(detailjual.jumlah) AS total_qty
    FROM penjualan
    INNER JOIN detailjual ON detailjual.nota_jual = penjualan.nota_jual
    WHERE penjualan.tgl_jual BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
    GROUP BY detailjual.kode_brng
");
if ($q_jual) {
    while ($row = mysqli_fetch_assoc($q_jual)) {
        $penjualan_bebas[$row['kode_brng']] = [
            'frekuensi' => (int)$row['frekuensi'],
            'total_qty' => (float)$row['total_qty']
        ];
    }
}

// 7. Penggabungan dan Pemeringkatan Data Fast Moving
$daftar_obat = [];
$total_semua_frekuensi = 0;
$total_semua_qty = 0;
$jumlah_item_aktif = 0;

foreach ($barang as $kode_brng => $info) {
    $f_go     = isset($keluar_go[$kode_brng])        ? $keluar_go[$kode_brng]['frekuensi']        : 0;
    $q_go     = isset($keluar_go[$kode_brng])        ? $keluar_go[$kode_brng]['total_qty']        : 0;

    $f_pasien = isset($pengeluaran_obat[$kode_brng]) ? $pengeluaran_obat[$kode_brng]['frekuensi'] : 0;
    $q_pasien = isset($pengeluaran_obat[$kode_brng]) ? $pengeluaran_obat[$kode_brng]['total_qty'] : 0;

    $f_resep  = isset($resep_pulang[$kode_brng])     ? $resep_pulang[$kode_brng]['frekuensi']     : 0;
    $q_resep  = isset($resep_pulang[$kode_brng])     ? $resep_pulang[$kode_brng]['total_qty']     : 0;

    $f_jual   = isset($penjualan_bebas[$kode_brng])  ? $penjualan_bebas[$kode_brng]['frekuensi']  : 0;
    $q_jual   = isset($penjualan_bebas[$kode_brng])  ? $penjualan_bebas[$kode_brng]['total_qty']  : 0;

    $total_frekuensi = $f_go + $f_pasien + $f_resep + $f_jual;
    $total_qty       = $q_go + $q_pasien + $q_resep + $q_jual;

    if ($total_frekuensi > 0) {
        $jumlah_item_aktif++;
        $total_semua_frekuensi += $total_frekuensi;
        $total_semua_qty       += $total_qty;
    }

    if ($hanya_ada_transaksi === '1' && $total_frekuensi <= 0) {
        continue;
    }

    $stok_go  = isset($stok_lokasi[$kode_brng]['GO'])  ? $stok_lokasi[$kode_brng]['GO']  : 0;
    $stok_dri = isset($stok_lokasi[$kode_brng]['DRI']) ? $stok_lokasi[$kode_brng]['DRI'] : 0;
    $stok_ap  = isset($stok_lokasi[$kode_brng]['AP'])  ? $stok_lokasi[$kode_brng]['AP']  : 0;
    $stok_di  = isset($stok_lokasi[$kode_brng]['DI'])  ? $stok_lokasi[$kode_brng]['DI']  : 0;
    $stok_do  = isset($stok_lokasi[$kode_brng]['DO'])  ? $stok_lokasi[$kode_brng]['DO']  : 0;
    $total_stok = $stok_go + $stok_dri + $stok_ap + $stok_di + $stok_do;

    $daftar_obat[] = [
        'kode_brng'       => $kode_brng,
        'nama_brng'       => $info['nama_brng'],
        'h_beli'          => $info['h_beli'],
        'kode_sat'        => $info['kode_sat'],
        'stok_go'         => $stok_go,
        'stok_dri'        => $stok_dri,
        'stok_ap'         => $stok_ap,
        'stok_di'         => $stok_di,
        'stok_do'         => $stok_do,
        'total_stok'      => $total_stok,
        'f_go'            => $f_go,
        'f_pasien'        => $f_pasien,
        'f_resep'         => $f_resep,
        'f_jual'          => $f_jual,
        'total_frekuensi' => $total_frekuensi,
        'total_qty'       => $total_qty
    ];
}

// Urutkan berdasarkan Total Frekuensi Keluar terbanyak (Fast Moving), lalu Total Qty
usort($daftar_obat, function ($a, $b) {
    if ($b['total_frekuensi'] === $a['total_frekuensi']) {
        return ($b['total_qty'] <=> $a['total_qty']);
    }
    return ($b['total_frekuensi'] <=> $a['total_frekuensi']);
});

// Potong jika dibatasi limit
$total_tampil_sebelum_limit = count($daftar_obat);
if ($limit_tampil !== 'semua' && is_numeric($limit_tampil)) {
    $daftar_obat = array_slice($daftar_obat, 0, (int)$limit_tampil);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Data Obat Fast Moving - RSUD Pringsewu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-hover: #047857;
            --primary-light: #ecfdf5;
            --accent: #f97316;
            --dark: #0f172a;
            --slate: #475569;
            --border: #e2e8f0;
            --bg: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: #1e293b;
            padding: 20px;
            font-size: 13px;
        }

        .main-wrapper {
            max-width: 98%;
            margin: 0 auto;
        }

        /* Top Header */
        .page-header {
            background: linear-gradient(135deg, #065f46 0%, #059669 100%);
            color: #fff;
            padding: 24px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 4px;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            color: #fff;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-1px);
        }

        /* Metric Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #fff;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-fire { background: #ffedd5; color: #ea580c; }
        .icon-sync { background: #ecfdf5; color: #059669; }
        .icon-box  { background: #eff6ff; color: #2563eb; }
        .icon-star { background: #fef3c7; color: #d97706; }

        .stat-content {
            overflow: hidden;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Filter Panel */
        .card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 24px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .form-control {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .quick-presets {
            display: flex;
            gap: 6px;
            margin-top: 4px;
        }

        .btn-preset {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            color: #475569;
            transition: all 0.15s;
        }

        .btn-preset:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-submit {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            height: 38px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        /* Toolbar */
        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 14px;
        }

        .toolbar-left {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-tool {
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-copy { background: #0284c7; color: #fff; }
        .btn-copy:hover { background: #0369a1; }
        .btn-excel { background: #16a34a; color: #fff; }
        .btn-excel:hover { background: #15803d; }
        .btn-print { background: #475569; color: #fff; }
        .btn-print:hover { background: #334155; }

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
        }

        .search-box input {
            padding: 8px 12px 8px 34px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            width: 100%;
            outline: none;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        /* Table Design */
        .table-container {
            max-height: 72vh;
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #065f46;
            color: #fff;
            font-weight: 600;
            padding: 9px 8px;
            border-right: 1px solid rgba(255, 255, 255, 0.15);
            border-bottom: 2px solid #047857;
            text-align: center;
            white-space: nowrap;
            z-index: 10;
        }

        thead tr:nth-child(2) th {
            top: 34px;
            background: #047857;
            font-size: 11px;
            font-weight: 500;
        }

        tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tbody tr:hover {
            background-color: #ecfdf5 !important;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 11px;
        }

        .rank-1 { background: #fef08a; color: #854d0e; }
        .rank-2 { background: #e2e8f0; color: #334155; }
        .rank-3 { background: #fed7aa; color: #9a3412; }
        .rank-other { background: #f1f5f9; color: #64748b; }

        .badge-fast {
            background: #ffedd5;
            color: #c2410c;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid #fed7aa;
        }

        .badge-zero {
            color: #94a3b8;
        }

        .stok-total {
            font-weight: 600;
            color: #0f172a;
        }

        .stok-habis {
            color: #ef4444;
            font-weight: 600;
        }

        .info-note {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 18px;
            font-size: 12.5px;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .page-header, .filter-form, .table-toolbar, .btn-back, .stats-grid, .info-note { display: none !important; }
            .table-container { max-height: none; overflow: visible; border: none; }
            thead th { position: static; background: #333 !important; color: #fff !important; }
            th, td { border: 1px solid #333 !important; font-size: 10px !important; padding: 4px !important; }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Header -->
    <div class="page-header">
        <div class="header-title">
            <h1><i class="fas fa-fire"></i> Laporan Obat Fast Moving (Sering Keluar)</h1>
            <p>RSUD Pringsewu &bull; Perhitungan Berdasarkan Frekuensi Kejadian Transaksi Keluar (1 Pasien / Peresepan = 1 Hitungan)</p>
        </div>
        <div>
            <a href="farmasi.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Menu Farmasi
            </a>
        </div>
    </div>

    <!-- Info Catatan Metodologi -->
    <div class="info-note">
        <i class="fas fa-info-circle fa-lg"></i>
        <div>
            <strong>Metodologi Fast Moving:</strong> Setiap kejadian peresepan/pemberian ke pasien dihitung <strong>1 transaksi</strong> (misal pasien menerima 10 tablet dihitung 1x transaksi). Total Frekuensi adalah akumulasi frekuensi dari <em>Stok Keluar GO + Pengeluaran Pasien + Resep Pulang + Penjualan Bebas</em>.
        </div>
    </div>

    <!-- Statistik Ringkasan -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-fire">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Item Fast Moving Aktif</div>
                <div class="stat-value"><?php echo number_format($jumlah_item_aktif, 0, ',', '.'); ?> <small style="font-size:12px;color:#64748b;">Obat</small></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-sync">
                <i class="fas fa-repeat"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Frekuensi Transaksi</div>
                <div class="stat-value"><?php echo number_format($total_semua_frekuensi, 0, ',', '.'); ?> <small style="font-size:12px;color:#64748b;">Kali</small></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-box">
                <i class="fas fa-cubes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Qty Fisik Keluar</div>
                <div class="stat-value"><?php echo number_format($total_semua_qty, 0, ',', '.'); ?> <small style="font-size:12px;color:#64748b;">Pcs/Tab</small></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-star">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Obat Paling Sering Keluar (#1)</div>
                <div class="stat-value" title="<?php echo !empty($daftar_obat) ? htmlspecialchars($daftar_obat[0]['nama_brng']) : '-'; ?>">
                    <?php echo !empty($daftar_obat) ? htmlspecialchars($daftar_obat[0]['nama_brng']) : '-'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Filter Form -->
    <div class="card">
        <form method="POST" id="filterForm" class="filter-form">
            <div class="form-group">
                <label><i class="far fa-calendar-alt"></i> Tanggal Awal:</label>
                <input type="date" name="tanggal_awal" id="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="far fa-calendar-alt"></i> Tanggal Akhir:</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-filter"></i> Tampilkan Data:</label>
                <select name="hanya_ada_transaksi" class="form-control">
                    <option value="1" <?php echo $hanya_ada_transaksi === '1' ? 'selected' : ''; ?>>Hanya yang Memiliki Pengeluaran (> 0)</option>
                    <option value="0" <?php echo $hanya_ada_transaksi === '0' ? 'selected' : ''; ?>>Tampilkan Semua Obat Aktif</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-list-ol"></i> Batas Baris:</label>
                <select name="limit_tampil" class="form-control">
                    <option value="semua" <?php echo $limit_tampil === 'semua' ? 'selected' : ''; ?>>Semua Baris</option>
                    <option value="50" <?php echo $limit_tampil === '50' ? 'selected' : ''; ?>>Top 50 Obat</option>
                    <option value="100" <?php echo $limit_tampil === '100' ? 'selected' : ''; ?>>Top 100 Obat</option>
                    <option value="200" <?php echo $limit_tampil === '200' ? 'selected' : ''; ?>>Top 200 Obat</option>
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-search"></i> Tampilkan Data
                </button>
            </div>

            <div class="form-group" style="margin-left: auto;">
                <label>Pilihan Cepat Periode:</label>
                <div class="quick-presets">
                    <button type="button" class="btn-preset" onclick="setPeriode('hari_ini')">Hari Ini</button>
                    <button type="button" class="btn-preset" onclick="setPeriode('7_hari')">7 Hari</button>
                    <button type="button" class="btn-preset" onclick="setPeriode('bulan_ini')">Bulan Ini</button>
                    <button type="button" class="btn-preset" onclick="setPeriode('bulan_lalu')">Bulan Lalu</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Toolbar -->
    <div class="table-toolbar">
        <div class="toolbar-left">
            <button type="button" class="btn-tool btn-copy" id="btnCopyTable">
                <i class="far fa-copy"></i> Copy Tabel
            </button>
            <button type="button" class="btn-tool btn-excel" id="btnExportExcel">
                <i class="far fa-file-excel"></i> Export Excel
            </button>
            <button type="button" class="btn-tool btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <span style="font-size:12px;color:#64748b;align-self:center;margin-left:8px;">
                Menampilkan: <strong><?php echo count($daftar_obat); ?></strong> data
            </span>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="liveSearchInput" placeholder="Cari nama atau kode obat...">
        </div>
    </div>

    <!-- Table Content -->
    <div class="table-container" id="tableWrapper">
        <table id="tableFastMoving">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 45px;">Rank</th>
                    <th rowspan="2" style="width: 95px;">Kode Barang</th>
                    <th rowspan="2" style="min-width: 220px; text-align: left;">Nama Barang</th>
                    <th rowspan="2" style="width: 70px;">Satuan</th>
                    <th rowspan="2" style="width: 85px;">Harga Beli (Rp)</th>
                    <th colspan="6" style="background:#047857;border-bottom:1px solid rgba(255,255,255,0.2);">Sisa Stok Saat Ini</th>
                    <th colspan="4" style="background:#b45309;border-bottom:1px solid rgba(255,255,255,0.2);">Frekuensi Keluar (Transaksi)</th>
                    <th rowspan="2" style="background:#c2410c;width: 100px;">Total Frekuensi (Fast Moving)</th>
                    <th rowspan="2" style="background:#0369a1;width: 95px;">Total Qty Fisik</th>
                </tr>
                <tr>
                    <!-- Sub-kolom Stok -->
                    <th style="width: 50px;">GO</th>
                    <th style="width: 50px;">DRI</th>
                    <th style="width: 50px;">AP</th>
                    <th style="width: 50px;">DI</th>
                    <th style="width: 50px;">DO</th>
                    <th style="width: 65px; font-weight:700;">Total Stok</th>

                    <!-- Sub-kolom Frekuensi Keluar -->
                    <th style="background:#d97706;width: 70px;" title="Frekuensi pengeluaran dari gudang GO">Keluar GO</th>
                    <th style="background:#d97706;width: 85px;" title="Frekuensi pemberian ke pasien Ralan & Ranap">Pemberian Pasien</th>
                    <th style="background:#d97706;width: 75px;" title="Frekuensi resep pulang pasien">Resep Pulang</th>
                    <th style="background:#d97706;width: 80px;" title="Frekuensi nota penjualan bebas">Penjualan Bebas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (empty($daftar_obat)) {
                    ?>
                    <tr>
                        <td colspan="17" class="text-center" style="padding: 30px; color: #94a3b8;">
                            <i class="fas fa-inbox fa-3x" style="margin-bottom:10px;display:block;"></i>
                            Tidak ada data obat fast moving pada rentang tanggal yang dipilih.
                        </td>
                    </tr>
                    <?php
                } else {
                    $rank = 1;
                    foreach ($daftar_obat as $item) {
                        $rankClass = 'rank-other';
                        if ($rank === 1) $rankClass = 'rank-1';
                        elseif ($rank === 2) $rankClass = 'rank-2';
                        elseif ($rank === 3) $rankClass = 'rank-3';

                        $stokTotalClass = $item['total_stok'] <= 0 ? 'stok-habis' : 'stok-total';
                        ?>
                        <tr>
                            <td class="text-center">
                                <span class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank++; ?></span>
                            </td>
                            <td class="text-center" style="font-family:monospace;font-weight:600;"><?php echo htmlspecialchars($item['kode_brng']); ?></td>
                            <td style="font-weight:600; color:#0f172a;"><?php echo htmlspecialchars($item['nama_brng']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['kode_sat']); ?></td>
                            <td class="text-right"><?php echo number_format($item['h_beli'], 0, ',', '.'); ?></td>

                            <!-- Stok Real Time -->
                            <td class="text-right"><?php echo number_format($item['stok_go'], 0, ',', '.'); ?></td>
                            <td class="text-right"><?php echo number_format($item['stok_dri'], 0, ',', '.'); ?></td>
                            <td class="text-right"><?php echo number_format($item['stok_ap'], 0, ',', '.'); ?></td>
                            <td class="text-right"><?php echo number_format($item['stok_di'], 0, ',', '.'); ?></td>
                            <td class="text-right"><?php echo number_format($item['stok_do'], 0, ',', '.'); ?></td>
                            <td class="text-right <?php echo $stokTotalClass; ?>"><?php echo number_format($item['total_stok'], 0, ',', '.'); ?></td>

                            <!-- Frekuensi Transaksi Keluar (1 Pasien = 1) -->
                            <td class="text-right <?php echo $item['f_go'] > 0 ? '' : 'badge-zero'; ?>">
                                <?php echo $item['f_go'] > 0 ? number_format($item['f_go'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right <?php echo $item['f_pasien'] > 0 ? '' : 'badge-zero'; ?>">
                                <?php echo $item['f_pasien'] > 0 ? number_format($item['f_pasien'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right <?php echo $item['f_resep'] > 0 ? '' : 'badge-zero'; ?>">
                                <?php echo $item['f_resep'] > 0 ? number_format($item['f_resep'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right <?php echo $item['f_jual'] > 0 ? '' : 'badge-zero'; ?>">
                                <?php echo $item['f_jual'] > 0 ? number_format($item['f_jual'], 0, ',', '.') : '-'; ?>
                            </td>

                            <!-- Total Frekuensi (Rank Score) -->
                            <td class="text-right" style="background:#fff7ed;">
                                <span class="badge-fast">
                                    <i class="fas fa-bolt" style="font-size:10px;"></i>
                                    <?php echo number_format($item['total_frekuensi'], 0, ',', '.'); ?>
                                </span>
                            </td>

                            <!-- Total Kuantitas Fisik (Qty Item) -->
                            <td class="text-right" style="font-weight:600; color:#0369a1; background:#f0f9ff;">
                                <?php echo number_format($item['total_qty'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Preset Tanggal Cepat
function setPeriode(tipe) {
    const today = new Date();
    const tglAwalInput = document.getElementById('tanggal_awal');
    const tglAkhirInput = document.getElementById('tanggal_akhir');

    const formatYMD = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    if (tipe === 'hari_ini') {
        tglAwalInput.value = formatYMD(today);
        tglAkhirInput.value = formatYMD(today);
    } else if (tipe === '7_hari') {
        const d7 = new Date();
        d7.setDate(today.getDate() - 6);
        tglAwalInput.value = formatYMD(d7);
        tglAkhirInput.value = formatYMD(today);
    } else if (tipe === 'bulan_ini') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        tglAwalInput.value = formatYMD(firstDay);
        tglAkhirInput.value = formatYMD(today);
    } else if (tipe === 'bulan_lalu') {
        const firstDayPrev = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDayPrev = new Date(today.getFullYear(), today.getMonth(), 0);
        tglAwalInput.value = formatYMD(firstDayPrev);
        tglAkhirInput.value = formatYMD(lastDayPrev);
    }
    document.getElementById('filterForm').submit();
}

// Live Search Filter di Tabel
document.getElementById('liveSearchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tableFastMoving tbody tr');

    rows.forEach(row => {
        const kodeCell = row.children[1];
        const namaCell = row.children[2];
        if (!kodeCell || !namaCell) return;

        const kodeText = kodeCell.textContent.toLowerCase();
        const namaText = namaCell.textContent.toLowerCase();

        if (kodeText.includes(filter) || namaText.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Copy Tabel ke Clipboard
document.getElementById('btnCopyTable').addEventListener('click', function() {
    const table = document.getElementById('tableFastMoving');
    const range = document.createRange();
    range.selectNode(table);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('Tabel Fast Moving berhasil disalin ke clipboard!');
        } else {
            alert('Gagal menyalin tabel.');
        }
    } catch (err) {
        alert('Browser Anda tidak mendukung copy otomatis.');
    }
    window.getSelection().removeAllRanges();
});

// Export ke Excel
document.getElementById('btnExportExcel').addEventListener('click', function() {
    const table = document.getElementById('tableFastMoving');
    let html = table.outerHTML;
    
    // Format agar UTF-8 rapi di Microsoft Excel
    const uri = 'data:application/vnd.ms-excel;base64,';
    const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Obat Fast Moving</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>' + html + '</body></html>';
    const base64 = (s) => window.btoa(unescape(encodeURIComponent(s)));

    const link = document.createElement('a');
    link.href = uri + base64(template);
    link.download = 'Obat_Fast_Moving_' + document.getElementById('tanggal_awal').value + '_sd_' + document.getElementById('tanggal_akhir').value + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

</body>
</html>
