<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Query untuk mengambil nama instansi dan logo dari database
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU"; // default jika tidak ada di database
$logo_src = "images/logo.png"; // default jika tidak ada di database
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        // Konversi BLOB ke base64
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #065f46;
            --primary-light: #ecfdf5;
            --bg-body: #f1f5f9;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 14px rgba(0,0,0,0.07);
            --shadow-lg: 0 12px 28px rgba(0,0,0,0.1);
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px 40px;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text-main);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-nav:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .btn-logout {
            color: #dc2626;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .btn-logout:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* Header Card */
        .header-card {
            background: linear-gradient(135deg, #065f46 0%, #0d9488 100%);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            color: #fff;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .header-card::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -5%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-logo {
            background: #fff;
            padding: 8px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .header-logo img {
            width: 60px;
            height: auto;
            max-height: 70px;
            object-fit: contain;
            display: block;
        }

        .header-info {
            flex: 1;
            min-width: 200px;
        }

        .header-info h1 {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        .header-info p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 400;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.25);
            white-space: nowrap;
        }

        /* Controls Section (Search + Category Filter) */
        .controls-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 12px 44px 12px 46px;
            border-radius: 999px;
            border: 1.5px solid var(--border);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-main);
            background: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
        }

        .search-input:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .search-clear {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 4px;
            display: none;
            transition: color 0.2s;
        }

        .search-clear:hover {
            color: var(--text-main);
        }

        .category-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .category-pill {
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .category-pill:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .category-pill.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
        }

        /* Menu Grid (Widescreen Responsive: 6 to 8 columns on desktop) */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            gap: 16px;
        }

        .menu-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px 12px;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: var(--shadow-sm);
            min-height: 125px;
            justify-content: center;
        }

        .menu-item:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: var(--shadow-lg);
        }

        .menu-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: transform 0.2s ease;
        }

        .menu-item:hover .menu-icon-wrapper {
            transform: scale(1.1);
        }

        .menu-icon-wrapper i {
            font-size: 1.45rem;
        }

        .menu-title {
            font-size: 0.84rem;
            font-weight: 600;
            line-height: 1.35;
            color: #1e293b;
            word-break: break-word;
        }

        .menu-item.hidden {
            display: none !important;
        }

        /* Empty Search State */
        .empty-state {
            grid-column: 1 / -1;
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 48px 24px;
            text-align: center;
            display: none;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 16px;
        }

        .btn-reset-search {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid #a7f3d0;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-reset-search:hover {
            background: #d1fae5;
        }

        /* Footer */
        footer {
            margin-top: 36px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px 30px;
            }
            .header-card {
                flex-direction: column;
                text-align: center;
                padding: 24px 20px;
                gap: 16px;
            }
            .header-info h1 {
                font-size: 1.4rem;
            }
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 12px;
            }
            .category-pills {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 4px;
            }
            .category-pill {
                flex-shrink: 0;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Navigation -->
    <div class="top-bar">
        <a href="index.php" class="btn-nav">
            <i class="fas fa-arrow-left"></i> Kembali ke Menu Utama
        </a>
        <a href="logout.php" class="btn-nav btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Header Card -->
    <div class="header-card">
        <a href="index.php" class="header-logo" title="Kembali ke Beranda">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo">
        </a>
        <div class="header-info">
            <h1>Menu Farmasi</h1>
            <p><?php echo htmlspecialchars($nama_instansi); ?></p>
        </div>
        <div class="header-badge" id="menuCountBadge">
            <i class="fas fa-th-large"></i> <span id="visibleCount">31</span> Menu
        </div>
    </div>

    <!-- Controls: Instant Search & Category Filter -->
    <div class="controls-card">
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input 
                type="text" 
                id="searchInput" 
                class="search-input" 
                placeholder="Cari menu farmasi... (ketik 'stok', 'resep', 'faktur', dll atau tekan '/' untuk cari)" 
                autocomplete="off"
            >
            <button id="searchClear" class="search-clear" title="Hapus pencarian">&times;</button>
        </div>

        <div class="category-pills" id="categoryPills">
            <button class="category-pill active" data-category="all">Semua (31)</button>
            <button class="category-pill" data-category="stok">📦 Stok & Gudang</button>
            <button class="category-pill" data-category="pelayanan">💊 Pelayanan & Resep</button>
            <button class="category-pill" data-category="pengadaan">📋 Pengadaan & Faktur</button>
            <button class="category-pill" data-category="mutasi">🔄 Mutasi & Kontrol</button>
            <button class="category-pill" data-category="laporan">📊 Laporan & Mutu</button>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid" id="menuGrid">

        <!-- 1. Data Barang -->
        <a href="data_barang.php" class="menu-item" data-category="pelayanan" data-keywords="data barang master obat medis farmasi">
            <div class="menu-icon-wrapper" style="background: rgba(79, 70, 229, 0.1);">
                <i class="fas fa-box" style="color: #4f46e5;"></i>
            </div>
            <span class="menu-title">Data Barang</span>
        </a>

        <!-- 2. SKP Farmasi -->
        <a href="skpfarmasi.php" class="menu-item" data-category="pelayanan" data-keywords="skp farmasi sasaran keselamatan pasien telaah obat">
            <div class="menu-icon-wrapper" style="background: rgba(5, 150, 105, 0.1);">
                <i class="fas fa-capsules" style="color: #059669;"></i>
            </div>
            <span class="menu-title">SKP Farmasi</span>
        </a> 

        <!-- 3. Stok Farmasi -->
        <a href="stokfarmasi.php" class="menu-item" data-category="stok" data-keywords="stok farmasi gudang sisa jumlah obat">
            <div class="menu-icon-wrapper" style="background: rgba(14, 165, 233, 0.1);">
                <i class="fas fa-warehouse" style="color: #0ea5e9;"></i>
            </div>
            <span class="menu-title">Stok Farmasi</span>
        </a>

        <!-- 4. Hutang Barang Medis -->
        <a href="hutangmedis.php" class="menu-item" data-category="mutasi" data-keywords="hutang barang medis tagihan pbf bayar">
            <div class="menu-icon-wrapper" style="background: rgba(225, 29, 72, 0.1);">
                <i class="fas fa-money-bill-wave" style="color: #e11d48;"></i>
            </div>
            <span class="menu-title">Hutang Barang Medis</span>
        </a>                

        <!-- 5. Resep Pasien Ralan -->
        <a href="reseppasienralan.php" class="menu-item" data-category="pelayanan" data-keywords="resep pasien ralan rawat jalan dokter obat apotek">
            <div class="menu-icon-wrapper" style="background: rgba(13, 148, 136, 0.1);">
                <i class="fas fa-file-prescription" style="color: #0d9488;"></i>
            </div>
            <span class="menu-title">Resep Pasien Ralan</span>
        </a>

        <!-- 6. Riwayat Barang -->
        <a href="riwayatbarangfarmasi.php" class="menu-item" data-category="mutasi" data-keywords="riwayat barang farmasi kartu stok log histori obat">
            <div class="menu-icon-wrapper" style="background: rgba(245, 158, 66, 0.1);">
                <i class="fas fa-history" style="color: #f59e42;"></i>
            </div>
            <span class="menu-title">Riwayat Barang</span>
        </a>

        <!-- 7. Mutasi Medis -->
        <a href="mutasibarangmedis.php" class="menu-item" data-category="mutasi" data-keywords="mutasi medis barang obat pindah depo gudang">
            <div class="menu-icon-wrapper" style="background: rgba(14, 165, 233, 0.1);">
                <i class="fas fa-random" style="color: #0ea5e9;"></i>
            </div>
            <span class="menu-title">Mutasi Medis</span>
        </a>

        <!-- 8. Surat Pesanan -->
        <a href="suratpesanan.php" class="menu-item" data-category="pengadaan" data-keywords="surat pesanan sp order pbf pengadaan obat">
            <div class="menu-icon-wrapper" style="background: rgba(234, 88, 12, 0.1);">
                <i class="fas fa-envelope-open-text" style="color: #ea580c;"></i>
            </div>
            <span class="menu-title">Surat Pesanan</span>
        </a>

        <!-- 9. Stok per Tanggal -->
        <a href="stokpertanggal.php" class="menu-item" data-category="stok" data-keywords="stok per tanggal posisi opname tanggal tertentu">
            <div class="menu-icon-wrapper" style="background: rgba(22, 163, 74, 0.1);">
                <i class="fas fa-calendar-alt" style="color: #16a34a;"></i>
            </div>
            <span class="menu-title">Stok per Tanggal</span>
        </a>

        <!-- 10. Laporan Sipnap -->
        <a href="sipnap.php" class="menu-item" data-category="laporan" data-keywords="laporan sipnap narkotika psikotropika kemenkes dinas">
            <div class="menu-icon-wrapper" style="background: rgba(37, 99, 235, 0.1);">
                <i class="fas fa-clipboard-list" style="color: #2563eb;"></i>
            </div>
            <span class="menu-title">Laporan Sipnap</span>
        </a>

        <!-- 11. Kontrol Pengeluaran Gudang -->
        <a href="kontrolpengeluarangudang.php" class="menu-item" data-category="mutasi" data-keywords="kontrol pengeluaran gudang distribusi barang keluar unit">
            <div class="menu-icon-wrapper" style="background: rgba(13, 148, 136, 0.1);">
                <i class="fas fa-truck-loading" style="color: #0d9488;"></i>
            </div>
            <span class="menu-title">Kontrol Pengeluaran Gudang</span>
        </a>

        <!-- 12. Kontrol Permintaan & Mutasi -->
        <a href="kontrolpermintaanmutasi.php" class="menu-item" data-category="mutasi" data-keywords="kontrol permintaan mutasi depo ruangan barang">
            <div class="menu-icon-wrapper" style="background: rgba(251, 191, 36, 0.15);">
                <i class="fas fa-exchange-alt" style="color: #d97706;"></i>
            </div>
            <span class="menu-title">Kontrol Permintaan & Mutasi</span>
        </a>

        <!-- 13. Laporan Belanja -->
        <a href="laporanbelanjafarmasi.php" class="menu-item" data-category="pengadaan" data-keywords="laporan belanja farmasi pembelian pengadaan obat faktur">
            <div class="menu-icon-wrapper" style="background: rgba(225, 29, 72, 0.1);">
                <i class="fas fa-receipt" style="color: #e11d48;"></i>
            </div>
            <span class="menu-title">Laporan Belanja</span>
        </a>

        <!-- 14. Formularium -->
        <a href="laporanformularium.php" class="menu-item" data-category="pengadaan" data-keywords="formularium rs obat masuk standar daftar">
            <div class="menu-icon-wrapper" style="background: rgba(162, 28, 175, 0.1);">
                <i class="fas fa-book-medical" style="color: #a21caf;"></i>
            </div>
            <span class="menu-title">Formularium</span>
        </a>

        <!-- 15. LASA & HA Harian -->
        <a href="laporan_lasa_dan_high_alert.php" class="menu-item" data-category="laporan" data-keywords="lasa ha harian high alert look alike sound alike obat resiko tinggi">
            <div class="menu-icon-wrapper" style="background: rgba(217, 119, 6, 0.1);">
                <i class="fas fa-pills" style="color: #d97706;"></i>
            </div>
            <span class="menu-title">LASA & HA Harian</span>
        </a>

        <!-- 16. LASA & HA Bulanan -->
        <a href="laporan_lasa_dan_high_alert_bulanan.php" class="menu-item" data-category="laporan" data-keywords="lasa ha bulanan high alert look alike sound alike bulanan">
            <div class="menu-icon-wrapper" style="background: rgba(2, 132, 199, 0.1);">
                <i class="fas fa-calendar-alt" style="color: #0284c7;"></i>
            </div>
            <span class="menu-title">LASA & HA Bulanan</span>
        </a>

        <!-- 17. Kalkulator Faktur -->
        <a href="kalkulatorfaktur.php" class="menu-item" data-category="pengadaan" data-keywords="kalkulator faktur hitung diskon ppn harga obat">
            <div class="menu-icon-wrapper" style="background: rgba(251, 191, 36, 0.15);">
                <i class="fas fa-square-root-alt" style="color: #d97706;"></i>
            </div>
            <span class="menu-title">Kalkulator Faktur</span>
        </a>

        <!-- 18. Dokumentasi Faktur -->
        <a href="dokumentasifaktur.php" class="menu-item" data-category="pengadaan" data-keywords="dokumentasi faktur upload foto scan bukti faktur barang masuk">
            <div class="menu-icon-wrapper" style="background: rgba(13, 148, 136, 0.1);">
                <i class="fas fa-images" style="color: #0d9488;"></i>
            </div>
            <span class="menu-title">Dokumentasi Faktur</span>
        </a>

        <!-- 19. Lokasi Barang -->
        <a href="lokasibarangmedis.php" class="menu-item" data-category="stok" data-keywords="lokasi barang medis rak lemari gudang posisi">
            <div class="menu-icon-wrapper" style="background: rgba(124, 58, 237, 0.1);">
                <i class="fas fa-warehouse" style="color: #7c3aed;"></i>
            </div>
            <span class="menu-title">Lokasi Barang</span>
        </a>

        <!-- 20. Stok Depo RI -->
        <a href="stok_depo_rawat_inap.php" class="menu-item" data-category="stok" data-keywords="stok depo ri rawat inap sisa obat ruangan">
            <div class="menu-icon-wrapper" style="background: rgba(190, 24, 93, 0.1);">
                <i class="fas fa-hospital-user" style="color: #be185d;"></i>
            </div>
            <span class="menu-title">Stok Depo RI</span>
        </a>

        <!-- 21. Stok Min Gudang -->
        <a href="stok_minimal_gudang.php" class="menu-item" data-category="stok" data-keywords="stok min gudang minimal batas reorder peringatan habis">
            <div class="menu-icon-wrapper" style="background: rgba(244, 63, 94, 0.1);">
                <i class="fas fa-balance-scale" style="color: #f43f5e;"></i>
            </div>
            <span class="menu-title">Stok Min Gudang</span>
        </a>

        <!-- 22. Stok Min Depo RJ -->
        <a href="stok_minimal_depo_rawat_jalan.php" class="menu-item" data-category="stok" data-keywords="stok min depo rj rawat jalan minimal apotek poli">
            <div class="menu-icon-wrapper" style="background: rgba(14, 165, 233, 0.1);">
                <i class="fas fa-user-md" style="color: #0ea5e9;"></i>
            </div>
            <span class="menu-title">Stok Min Depo RJ</span>
        </a>

        <!-- 23. Stok Min Depo RI -->
        <a href="stok_minimal_depo_rawat_inap.php" class="menu-item" data-category="stok" data-keywords="stok min depo ri rawat inap batas minimal ranap">
            <div class="menu-icon-wrapper" style="background: rgba(245, 158, 66, 0.1);">
                <i class="fas fa-procedures" style="color: #f59e42;"></i>
            </div>
            <span class="menu-title">Stok Min Depo RI</span>
        </a>

        <!-- 24. Rencana Belanja -->
        <a href="rencanabelanja.php" class="menu-item" data-category="pengadaan" data-keywords="rencana belanja pengajuan kebutuhan obat">
            <div class="menu-icon-wrapper" style="background: rgba(14, 165, 233, 0.1);">
                <i class="fas fa-clipboard-check" style="color: #0ea5e9;"></i>
            </div>
            <span class="menu-title">Rencana Belanja</span>
        </a>

        <!-- 25. Rencana Belanja Detail -->
        <a href="rencanabelanja2.php" class="menu-item" data-category="pengadaan" data-keywords="rencana belanja detail rincian belanja anggaran">
            <div class="menu-icon-wrapper" style="background: rgba(16, 185, 129, 0.1);">
                <i class="fas fa-clipboard-list" style="color: #10b981;"></i>
            </div>
            <span class="menu-title">Rencana Belanja Detail</span>
        </a>

        <!-- 26. Rencana Belanja Farmasi -->
        <a href="rencanabelanjafarmasi.php" class="menu-item" data-category="pengadaan" data-keywords="rencana belanja farmasi usulan tahunan bulanan belanja obat">
            <div class="menu-icon-wrapper" style="background: rgba(162, 28, 175, 0.1);">
                <i class="fas fa-notes-medical" style="color: #a21caf;"></i>
            </div>
            <span class="menu-title">Rencana Belanja Farmasi</span>
        </a>

        <!-- 27. Obat Fast Moving -->
        <a href="obatfastmoving.php" class="menu-item" data-category="pelayanan" data-keywords="obat fast moving paling laku sering keluar laris">
            <div class="menu-icon-wrapper" style="background: rgba(249, 115, 22, 0.1);">
                <i class="fas fa-fire" style="color: #f97316;"></i>
            </div>
            <span class="menu-title">Obat Fast Moving</span>
        </a>

        <!-- 28. Verifikasi Resep Dokter -->
        <a href="verifikasi_resep_dokter.php" class="menu-item" data-category="pelayanan" data-keywords="verifikasi resep dokter telaah validasi farmasi klinis">
            <div class="menu-icon-wrapper" style="background: rgba(13, 148, 136, 0.1);">
                <i class="fas fa-check-double" style="color: #0d9488;"></i>
            </div>
            <span class="menu-title">Verifikasi Resep Dokter</span>
        </a>

        <!-- 29. Jadwal Jaga Farmasi -->
        <a href="jadwal_jaga_farmasi.php" class="menu-item" data-category="laporan" data-keywords="jadwal jaga farmasi shift apoteker asisten piket">
            <div class="menu-icon-wrapper" style="background: rgba(5, 150, 105, 0.1);">
                <i class="fas fa-calendar-check" style="color: #059669;"></i>
            </div>
            <span class="menu-title">Jadwal Jaga Farmasi</span>
        </a>

        <!-- 30. Evaluasi Pengajuan Barang -->
        <a href="evaluasi_pengajuan_barang_farmasi.php" class="menu-item" data-category="laporan" data-keywords="evaluasi pengajuan barang farmasi review usulan">
            <div class="menu-icon-wrapper" style="background: rgba(124, 58, 237, 0.1);">
                <i class="fas fa-balance-scale-right" style="color: #7c3aed;"></i>
            </div>
            <span class="menu-title">Evaluasi Pengajuan Barang</span>
        </a>

        <!-- 31. Kontrol Pinjam Barang -->
        <a href="kontrol_pinjam_barang_farmasi.php" class="menu-item" data-category="mutasi" data-keywords="kontrol pinjam barang farmasi peminjaman obat luar rs">
            <div class="menu-icon-wrapper" style="background: rgba(15, 118, 110, 0.1);">
                <i class="fas fa-hand-holding-medical" style="color: #0f766e;"></i>
            </div>
            <span class="menu-title">Kontrol Pinjam Barang</span>
        </a>

        <!-- Empty State Container -->
        <div class="empty-state" id="emptyState">
            <i class="fas fa-search"></i>
            <h3>Menu tidak ditemukan</h3>
            <p>Tidak ada menu yang sesuai dengan kata kunci "<span id="emptyKeyword"></span>".</p>
            <button class="btn-reset-search" onclick="resetFilters()">Tampilkan Semua Menu</button>
        </div>

    </div>

    <!-- Footer -->
    <footer>by IT RSUD Pringsewu</footer>
</div>

<script>
(function() {
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    const categoryPills = document.querySelectorAll('.category-pill');
    const menuItems = document.querySelectorAll('.menu-item');
    const emptyState = document.getElementById('emptyState');
    const emptyKeyword = document.getElementById('emptyKeyword');
    const visibleCountSpan = document.getElementById('visibleCount');

    let currentCategory = 'all';
    let currentSearch = '';

    function filterMenu() {
        let visibleCount = 0;
        const q = currentSearch.toLowerCase().trim();

        menuItems.forEach(item => {
            const title = (item.querySelector('.menu-title')?.textContent || '').toLowerCase();
            const keywords = (item.getAttribute('data-keywords') || '').toLowerCase();
            const category = item.getAttribute('data-category') || '';

            const matchCategory = (currentCategory === 'all' || category === currentCategory);
            const matchSearch = (!q || title.includes(q) || keywords.includes(q));

            if (matchCategory && matchSearch) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        visibleCountSpan.textContent = visibleCount;

        if (visibleCount === 0) {
            emptyState.style.display = 'block';
            emptyKeyword.textContent = q || currentCategory;
        } else {
            emptyState.style.display = 'none';
        }
    }

    // Search Input Event
    searchInput.addEventListener('input', function() {
        currentSearch = this.value;
        searchClear.style.display = this.value ? 'block' : 'none';
        filterMenu();
    });

    // Clear Search
    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        currentSearch = '';
        this.style.display = 'none';
        searchInput.focus();
        filterMenu();
    });

    // Category Filter Pills
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-category');
            filterMenu();
        });
    });

    // Reset Filters Function
    window.resetFilters = function() {
        searchInput.value = '';
        currentSearch = '';
        searchClear.style.display = 'none';
        currentCategory = 'all';
        categoryPills.forEach(p => {
            p.classList.toggle('active', p.getAttribute('data-category') === 'all');
        });
        filterMenu();
    };

    // Keyboard Shortcut: press '/' to focus search, 'Esc' to clear
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        } else if (e.key === 'Escape' && document.activeElement === searchInput) {
            resetFilters();
            searchInput.blur();
        }
    });

})();
</script>

</body>
</html>