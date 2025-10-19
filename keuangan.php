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
    <title>💰 Menu Keuangan - <?php echo htmlspecialchars($nama_instansi); ?></title>
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
            background: #f8fafc;
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .logo-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .logo-link img {
            border-radius: 8px;
        }
        
        .container {
            max-width: 1000px;
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
            color: #1e293b;
            margin-bottom: 8px;
            position: relative;
        }
        
        .header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            border-radius: 2px;
        }
        
        .header .subtitle {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 400;
            margin-top: 20px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        .menu-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 30px;
            text-decoration: none;
            color: #334155;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out both;
        }
        
        .menu-card:nth-child(1) { animation-delay: 0.3s; }
        .menu-card:nth-child(2) { animation-delay: 0.4s; }
        .menu-card:nth-child(3) { animation-delay: 0.5s; }
        
        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .menu-card:hover::before {
            transform: scaleX(1);
        }
        
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        
        .menu-card .icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            transition: all 0.3s ease;
        }
        
        .menu-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);
        }
        
        .menu-card .icon {
            font-size: 2.2rem;
            color: #fff;
        }
        
        .menu-card .title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #1e293b;
            text-align: center;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .menu-card .description {
            font-size: 0.95rem;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            color: #64748b;
            text-align: center;
            padding: 20px 0;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }
        
        .footer .company {
            color: #3b82f6;
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
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .menu-card {
                padding: 30px 20px;
            }
            
            .menu-card .icon-wrapper {
                width: 70px;
                height: 70px;
                margin-bottom: 20px;
            }
            
            .menu-card .icon {
                font-size: 2rem;
            }
            
            .menu-card .title {
                font-size: 1.2rem;
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
                padding: 25px 15px;
            }
            
            .menu-card .icon-wrapper {
                width: 60px;
                height: 60px;
            }
            
            .menu-card .icon {
                font-size: 1.8rem;
            }
        }
        
        /* Hover effects for better interaction */
        .menu-card {
            cursor: pointer;
        }
        
        .menu-card:active {
            transform: translateY(-4px) scale(0.98);
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
            <h1>💰 Menu Keuangan</h1>
            <p class="subtitle"><?php echo htmlspecialchars($nama_instansi); ?></p>
        </div>
        
        <div class="menu-grid">
            <a href="detailtindakan.php" class="menu-card" title="Detail Tindakan Pasien">
                <div class="icon-wrapper">
                    <i class="fas fa-file-medical-alt icon"></i>
                </div>
                <div class="title">Detail Tindakan Pasien</div>
                <div class="description">Kelola dan lihat detail tindakan medis yang diberikan kepada pasien</div>
            </a>
            
            <a href="penagihanfaktur.php" class="menu-card" title="Penagihan Faktur Barang Medis">
                <div class="icon-wrapper">
                    <i class="fas fa-file-invoice-dollar icon"></i>
                </div>
                <div class="title">Penagihan Faktur</div>
                <div class="description">Kelola penagihan dan faktur untuk barang medis dan layanan kesehatan</div>
            </a>
            
            <a href="paymentpoint.php" class="menu-card" title="Payment Point">
                <div class="icon-wrapper">
                    <i class="fas fa-cash-register icon"></i>
                </div>
                <div class="title">Payment Point</div>
                <div class="description">Sistem pembayaran dan transaksi keuangan rumah sakit</div>
            </a>
        </div>
    </div>
    
    <div class="footer">
        Dikembangkan oleh <span class="company">IT RSUD Pringsewu</span>
    </div>
</body>
</html>