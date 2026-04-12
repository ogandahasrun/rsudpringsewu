
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
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($nama_instansi); ?> - Menu Farmasi</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f0f2f5;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 700px;
                margin: 80px auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                text-align: center;
            }
            h1 {
                color: #333;
            }
            .menu-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 20px;
                margin-top: 30px;
            }
            .menu-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                background: #f9f9f9;
                border-radius: 10px;
                padding: 15px;
                text-decoration: none;
                color: #333;
                box-shadow: 0 0 8px rgba(0,0,0,0.1);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .menu-item:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
            .menu-item img {
                width: 50px;
                height: 50px;
                margin-bottom: 10px;
            }
            .logout {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: red;
                text-decoration: none;
            }
            footer {
                margin-top: 40px;
                text-align: center;
                color: #777;
            }
        </style>
        </head>
        <body>

        <a href="index.php" style="display: block; text-align: center; margin-top: 30px;">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo" width="80" height="100">
        </a>

        <div class="container">
            <h1>MENU FARMASI <?php echo htmlspecialchars($nama_instansi); ?></h1>

            <!-- Font Awesome CDN for icons -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <div class="menu-grid">
                <a href="data_barang.php" class="menu-item">
                    <i class="fas fa-box fa-2x" style="margin-bottom:10px;color:#4f46e5;"></i>
                    <span style="color:#222;font-weight:bold;">Data Barang</span>
                </a>
                <a href="skpfarmasi.php" class="menu-item">
                    <i class="fas fa-capsules fa-2x" style="margin-bottom:10px;color:#059669;"></i>
                    <span style="color:#222;font-weight:bold;">SKP Farmasi</span>
                </a> 
                <a href="stokfarmasi.php" class="menu-item">
                    <i class="fas fa-warehouse fa-2x" style="margin-bottom:10px;color:#0ea5e9;"></i>
                    <span style="color:#222;font-weight:bold;">Stok Farmasi</span>
                </a>
                <a href="hutangmedis.php" class="menu-item">
                    <i class="fas fa-money-bill-wave fa-2x" style="margin-bottom:10px;color:#e11d48;"></i>
                    <span style="color:#222;font-weight:bold;">Hutang Barang Medis</span>
                </a>                
                <a href="reseppasienralan.php" class="menu-item">
                    <i class="fas fa-file-prescription fa-2x" style="margin-bottom:10px;color:#0d9488;"></i>
                    <span style="color:#222;font-weight:bold;">Resep Pasien Ralan</span>
                </a>
                <a href="riwayatbarangfarmasi.php" class="menu-item">
                    <i class="fas fa-history fa-2x" style="margin-bottom:10px;color:#f59e42;"></i>
                    <span style="color:#222;font-weight:bold;">Riwayat Barang</span>
                </a>
                <a href="mutasibarangmedis.php" class="menu-item">
                    <i class="fas fa-random fa-2x" style="margin-bottom:10px;color:#0ea5e9;"></i>
                    <span style="color:#222;font-weight:bold;">Mutasi Medis</span>
                </a>
                <a href="suratpesanan.php" class="menu-item">
                    <i class="fas fa-envelope-open-text fa-2x" style="margin-bottom:10px;color:#ea580c;"></i>
                    <span style="color:#222;font-weight:bold;">Surat Pesanan</span>
                </a>
                <a href="stokpertanggal.php" class="menu-item">
                    <i class="fas fa-calendar-alt fa-2x" style="margin-bottom:10px;color:#16a34a;"></i>
                    <span style="color:#222;font-weight:bold;">Stok per Tanggal</span>
                </a>
                <a href="sipnap.php" class="menu-item">
                    <i class="fas fa-clipboard-list fa-2x" style="margin-bottom:10px;color:#2563eb;"></i>
                    <span style="color:#222;font-weight:bold;">Laporan Sipnap</span>
                </a>
                <a href="kontrolpengeluarangudang.php" class="menu-item">
                    <i class="fas fa-truck-loading fa-2x" style="margin-bottom:10px;color:#0d9488;"></i>
                    <span style="color:#222;font-weight:bold;">Kontrol Pengeluaran Gudang</span>
                </a>
                <a href="kontrolpermintaanmutasi.php" class="menu-item">
                    <i class="fas fa-exchange-alt fa-2x" style="margin-bottom:10px;color:#fbbf24;"></i>
                    <span style="color:#222;font-weight:bold;">Kontrol Permintaan & Mutasi</span>
                </a>
                <a href="laporanbelanjafarmasi.php" class="menu-item">
                    <i class="fas fa-receipt fa-2x" style="margin-bottom:10px;color:#e11d48;"></i>
                    <span style="color:#222;font-weight:bold;">Laporan Belanja</span>
                </a>
                <a href="laporanformularium.php" class="menu-item">
                    <i class="fas fa-book-medical fa-2x" style="margin-bottom:10px;color:#a21caf;"></i>
                    <span style="color:#222;font-weight:bold;">Formularium</span>
                </a>
                <a href="kalkulatorfaktur.php" class="menu-item">
                    <i class="fas fa-square-root-alt fa-2x" style="margin-bottom:10px;color:#fbbf24;"></i>
                    <span style="color:#222;font-weight:bold;">Kalkulator Faktur</span>
                </a>
                <a href="dokumentasifaktur.php" class="menu-item">
                    <i class="fas fa-images fa-2x" style="margin-bottom:10px;color:#0d9488;"></i>
                    <span style="color:#222;font-weight:bold;">Dokumentasi Faktur</span>
                </a>
                <a href="lokasibarangmedis.php" class="menu-item">
                    <i class="fas fa-warehouse fa-2x" style="margin-bottom:10px;color:#7c3aed;"></i>
                    <span style="color:#222;font-weight:bold;">Lokasi Barang</span>
                </a>
                <a href="stok_depo_rawat_inap.php" class="menu-item">
                    <i class="fas fa-hospital-user fa-2x" style="margin-bottom:10px;color:#be185d;"></i>
                    <span style="color:#222;font-weight:bold;">Stok Depo RI</span>
                </a>
                <a href="stok_minimal_gudang.php" class="menu-item">
                    <i class="fas fa-balance-scale fa-2x" style="margin-bottom:10px;color:#f43f5e;"></i>
                    <span style="color:#222;font-weight:bold;">Stok Min Gudang</span>
                </a>
                <a href="stok_minimal_depo_rawat_jalan.php" class="menu-item">
                    <i class="fas fa-user-md fa-2x" style="margin-bottom:10px;color:#0ea5e9;"></i>
                    <span style="color:#222;font-weight:bold;">Stok Min Depo RJ</span>
                </a>
                <a href="stok_minimal_depo_rawat_inap.php" class="menu-item">
                    <i class="fas fa-procedures fa-2x" style="margin-bottom:10px;color:#f59e42;"></i>
                    <span style="color:#222;font-weight:bold;">Stok Min Depo RI</span>
                </a>
                <a href="rencanabelanja.php" class="menu-item">
                    <i class="fas fa-clipboard-check fa-2x" style="margin-bottom:10px;color:#0ea5e9;"></i>
                    <span style="color:#222;font-weight:bold;">Rencana Belanja</span>
                </a>
                <a href="rencanabelanjafarmasi.php" class="menu-item">
                    <i class="fas fa-notes-medical fa-2x" style="margin-bottom:10px;color:#a21caf;"></i>
                    <span style="color:#222;font-weight:bold;">Rencana Belanja Farmasi</span>
                </a>                                
            </div>

            <a href="logout.php" class="logout">Logout</a>
        </div>

        <footer>by IT RSUD Pringsewu</footer>

        </body>
        </html>