<?php
include 'koneksi.php';
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU";
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
    <title>💊 Menu Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f9ff 50%, #f8fafc 100%);
            min-height: 100vh;
            padding: 20px 0 80px 0;
            color: #334155;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            animation: slideInDown 0.6s ease-out;
        }
        
        .logo-link {
            display: inline-block;
            padding: 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
            transition: all 0.3s ease;
            border: 2px solid rgba(34, 197, 94, 0.1);
        }
        
        .logo-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.2);
        }
        
        .logo-link img {
            border-radius: 8px;
            height: auto;
            max-width: 25vw;
            min-width: 72px;
            width: 80px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
            animation: slideInUp 0.6s ease-out 0.2s both;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 8px;
            position: relative;
        }
        
        .header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #10b981);
            border-radius: 2px;
        }
        
        .header .subtitle {
            font-size: 1.1rem;
            color: #059669;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .menu-card {
            background: #fff;
            border-radius: 16px;
            padding: 30px 20px;
            text-decoration: none;
            color: #334155;
            box-shadow: 0 2px 10px rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out both;
        }
        
        .menu-card:nth-child(1) { animation-delay: 0.1s; }
        .menu-card:nth-child(2) { animation-delay: 0.15s; }
        .menu-card:nth-child(3) { animation-delay: 0.2s; }
        .menu-card:nth-child(4) { animation-delay: 0.25s; }
        .menu-card:nth-child(5) { animation-delay: 0.3s; }
        .menu-card:nth-child(6) { animation-delay: 0.35s; }
        .menu-card:nth-child(7) { animation-delay: 0.4s; }
        .menu-card:nth-child(8) { animation-delay: 0.45s; }
        .menu-card:nth-child(9) { animation-delay: 0.5s; }
        .menu-card:nth-child(10) { animation-delay: 0.55s; }
        .menu-card:nth-child(11) { animation-delay: 0.6s; }
        .menu-card:nth-child(12) { animation-delay: 0.65s; }
        .menu-card:nth-child(13) { animation-delay: 0.7s; }
        .menu-card:nth-child(14) { animation-delay: 0.75s; }
        .menu-card:nth-child(15) { animation-delay: 0.8s; }
        .menu-card:nth-child(16) { animation-delay: 0.85s; }
        .menu-card:nth-child(17) { animation-delay: 0.9s; }
        .menu-card:nth-child(18) { animation-delay: 0.95s; }
        .menu-card:nth-child(19) { animation-delay: 1s; }
        
        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #10b981);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .menu-card:hover::before {
            transform: scaleX(1);
        }
        
        .menu-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.2);
        }
        
        .menu-card .icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #22c55e 0%, #10b981 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
        }
        
        .menu-card:hover .icon-wrapper {
            transform: scale(1.05) rotate(3deg);
            background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
        }
        
        .menu-card .icon {
            font-size: 1.8rem;
            color: #fff;
        }
        
        .menu-card .title {
            font-size: 1rem;
            font-weight: 600;
            color: #065f46;
            text-align: center;
            margin-bottom: 8px;
            line-height: 1.3;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            color: #fff;
            text-align: center;
            padding: 18px 0;
            font-size: 0.9rem;
            box-shadow: 0 -2px 10px rgba(34, 197, 94, 0.2);
            z-index: 1000;
            padding-bottom: calc(18px + env(safe-area-inset-bottom, 0px));
        }
        
        .footer .company {
            color: #86efac;
            font-weight: 500;
        }
        
        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px 0 80px 0;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header .subtitle {
                font-size: 1rem;
            }
            
            .menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 15px;
            }
            
            .menu-card {
                padding: 20px 12px;
            }
            
            .menu-card .icon-wrapper {
                width: 60px;
                height: 60px;
                margin-bottom: 15px;
            }
            
            .menu-card .icon {
                font-size: 1.5rem;
            }
            
            .menu-card .title {
                font-size: 0.9rem;
                min-height: 35px;
            }
            
            .footer {
                position: static;
            }
            
            body {
                padding-bottom: 0;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .menu-card {
                padding: 18px 10px;
            }
            
            .menu-card .icon-wrapper {
                width: 55px;
                height: 55px;
            }
            
            .menu-card .icon {
                font-size: 1.4rem;
            }
            
            .menu-card .title {
                font-size: 0.85rem;
            }
        }
        
        /* Hover effects for better interaction */
        .menu-card {
            cursor: pointer;
        }
        
        .menu-card:active {
            transform: translateY(-3px) scale(0.98);
        }
    </style>
</head>
<body>
    <div class="logo-section">
        <a href="index.php" class="logo-link">
            <img src="<?php echo $logo_src; ?>" alt="<?php echo htmlspecialchars($nama_instansi); ?>" width="80" height="100">
        </a>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>💊 Menu Farmasi</h1>
            <p class="subtitle"><?php echo htmlspecialchars($nama_instansi); ?></p>
        </div>
        
        <div class="menu-grid">
            <a href="data_barang.php" class="menu-card" title="Data Barang">
                <div class="icon-wrapper">
                    <i class="fas fa-box icon"></i>
                </div>
                <div class="title">Data Barang</div>
            </a>
            
            <a href="skpfarmasi.php" class="menu-card" title="SKP Farmasi">
                <div class="icon-wrapper">
                    <i class="fas fa-clipboard-list icon"></i>
                </div>
                <div class="title">SKP Farmasi</div>
            </a>
            
            <a href="stokfarmasi.php" class="menu-card" title="Stok Barang Farmasi">
                <div class="icon-wrapper">
                    <i class="fas fa-pills icon"></i>
                </div>
                <div class="title">Stok Farmasi</div>
            </a>
            
            <a href="hutangmedis.php" class="menu-card" title="Hutang Barang Medis">
                <div class="icon-wrapper">
                    <i class="fas fa-file-invoice-dollar icon"></i>
                </div>
                <div class="title">Hutang Barang Medis</div>
            </a>
            
            <a href="kalkulatorfaktur.php" class="menu-card" title="Kalkulator Faktur">
                <div class="icon-wrapper">
                    <i class="fas fa-calculator icon"></i>
                </div>
                <div class="title">Kalkulator Faktur</div>
            </a>

            <a href="reseppasienralan.php" class="menu-card" title="Resep Pasien Ralan">
                <div class="icon-wrapper">
                    <i class="fas fa-file-prescription icon"></i>
                </div>
                <div class="title">Resep Ralan</div>
            </a>
            <a href="pasienresepganda.php" class="menu-card" title="Pasien Resep Ganda">
                <div class="icon-wrapper">
                    <i class="fas fa-file-prescription icon"></i>
                </div>
                <div class="title">Resep Ganda</div>
            </a>      
            <a href="riwayatbarangfarmasi.php" class="menu-card" title="Riwayat Barang Farmasi">
                <div class="icon-wrapper">
                    <i class="fas fa-procedures icon"></i>
                </div>
                <div class="title">Riwayat Barang Farmasi</div>
            </a>
            
            <a href="mutasibarangmedis.php" class="menu-card" title="Mutasi Barang Medis">
                <div class="icon-wrapper">
                    <i class="fas fa-exchange-alt icon"></i>
                </div>
                <div class="title">Mutasi Medis</div>
            </a>
            
            <a href="suratpesanan.php" class="menu-card" title="Surat Pesanan Barang Medis">
                <div class="icon-wrapper">
                    <i class="fas fa-box-open icon"></i>
                </div>
                <div class="title">Surat Pesanan Barang Medis</div>
            </a>
            
            <a href="lokasibarangmedis.php" class="menu-card" title="Lokasi Barang Medis">
                <div class="icon-wrapper">
                    <i class="fas fa-boxes icon"></i>
                </div>
                <div class="title">Lokasi Barang Medis</div>
            </a>
            
            <a href="stokpertanggal.php" class="menu-card" title="Stok per Tanggal">
                <div class="icon-wrapper">
                    <i class="fas fa-clinic-medical icon"></i>
                </div>
                <div class="title">Stok per Tanggal</div>
            </a>
            
            <a href="stok_depo_rawat_inap.php" class="menu-card" title="Stok Lokasi Depo Rawat Inap">
                <div class="icon-wrapper">
                    <i class="fas fa-hospital-user icon"></i>
                </div>
                <div class="title">Stok Depo RI</div>
            </a>
            
            <a href="stok_minimal_gudang.php" class="menu-card" title="Stok Minimal Gudang Barang">
                <div class="icon-wrapper">
                    <i class="fas fa-sort-amount-down icon"></i>
                </div>
                <div class="title">Stok Min Gudang</div>
            </a>
            
            <a href="stok_minimal_depo_rawat_jalan.php" class="menu-card" title="Stok Minimal Depo Rawat Jalan">
                <div class="icon-wrapper">
                    <i class="fas fa-sort-amount-down-alt icon"></i>
                </div>
                <div class="title">Stok Min Depo RJ</div>
            </a>
            
            <a href="stok_minimal_depo_rawat_inap.php" class="menu-card" title="Stok Minimal Depo Rawat Inap">
                <div class="icon-wrapper">
                    <i class="fas fa-sort-amount-down-alt icon"></i>
                </div>
                <div class="title">Stok Min Depo RI</div>
            </a>
            
            <a href="sipnap.php" class="menu-card" title="Menu Pembuatan Laporan Sipnap">
                <div class="icon-wrapper">
                    <i class="fas fa-capsules icon"></i>
                </div>
                <div class="title">Laporan Sipnap</div>
            </a>
            
            <a href="kontrolpermintaanmutasi.php" class="menu-card" title="Kontrol Permintaan dan Mutasi">
                <div class="icon-wrapper">
                    <i class="fas fa-tasks icon"></i>
                </div>
                <div class="title">Kontrol Permintaan dan Mutasi</div>
            </a>
            
            <a href="kontrolpengeluarangudang.php" class="menu-card" title="Kontrol Pengeluaran Gudang">
                <div class="icon-wrapper">
                    <i class="fas fa-tasks icon"></i>
                </div>
                <div class="title">Kontrol Pengeluaran Gudang</div>
            </a>

            <a href="laporanformularium.php" class="menu-card" title="Laporan Formularium">
                <div class="icon-wrapper">
                    <i class="fas fa-notes-medical icon"></i>
                </div>
                <div class="title">Formularium</div>
            </a>
            
            <a href="pemesanandokumentasi.php" class="menu-card" title="Dokumentasi Faktur">
                <div class="icon-wrapper">
                    <i class="fas fa-file-invoice icon"></i>
                </div>
                <div class="title">Dokumentasi Faktur</div>
            </a>
            
            <a href="rencanabelanjafarmasi.php" class="menu-card" title="Rencana Belanja Farmasi">
                <div class="icon-wrapper">
                    <i class="fas fa-shopping-cart icon"></i>
                </div>
                <div class="title">Rencana Belanja Farmasi</div>
            </a>

                        <a href="rencanabelanja.php" class="menu-card" title="Rencana Belanja">
                <div class="icon-wrapper">
                    <i class="fas fa-shopping-cart icon"></i>
                </div>
                <div class="title">Rencana Belanja</div>
            </a>

        </div>
    </div>
    
    <div class="footer">
        Dikembangkan oleh <span class="company">IT RSUD Pringsewu</span>
    </div>
</body>
</html>