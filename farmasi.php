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
    <title>Menu Farmasi</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        a {
            text-decoration: none;
        }
        .logo-link {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .container {
            max-width: 800px;
            margin: 30px auto 60px auto;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }
        .icon-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 22px;
            margin-top: 10px;
        }
        .icon-menu a {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 22px 8px 16px 8px;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #007bff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            font-size: 15px;
            min-height: 120px;
        }
        .icon-menu a:hover {
            background: #e3f2fd;
            box-shadow: 0 4px 16px rgba(0,123,255,0.08);
            color: #0056b3;
        }
        .icon-menu i {
            font-size: 2.2em;
            margin-bottom: 12px;
            color: #007bff;
            transition: color 0.2s;
        }
        .icon-menu a:hover i {
            color: #0056b3;
        }
        .icon-menu span {
            margin-top: 2px;
            font-size: 1em;
            color: #333;
            text-align: center;
            word-break: break-word;
        }
        footer {
            text-align: center;
            padding: 18px 0;
            background-color: #333;
            color: #fff;
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 15px;
            letter-spacing: 1px;
        }
        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .icon-menu {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .icon-menu a {
                padding: 14px 4px 10px 4px;
                min-height: 90px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="logo-link">
        <img src="<?php echo $logo_src; ?>" alt="Logo" width="80" height="100">
    </a>
    <div class="container">
        <h1><?php echo htmlspecialchars($nama_instansi); ?></h1>
        <div class="icon-menu">
            <a href="data_barang.php" title="Data Barang"><i class="fas fa-box"></i><span>Data Barang</span></a>
            <a href="stokfarmasi.php" title="Stok Barang Farmasi"><i class="fas fa-pills"></i><span>Stok Farmasi</span></a>
            <a href="reseppasienralan.php" title="Resep Pasien Ralan"><i class="fas fa-file-prescription"></i><span>Resep Ralan</span></a>
            <a href="riwayatdri.php" title="Riwayat Barang Depo Rawat Inap"><i class="fas fa-procedures"></i><span>Riwayat Depo Inap</span></a>
            <a href="riwayatgo.php" title="Riwayat Barang Gudang"><i class="fas fa-warehouse"></i><span>Riwayat Gudang</span></a>
            <a href="mutasibarangmedis.php" title="Mutasi Barang Medis"><i class="fas fa-exchange-alt"></i><span>Mutasi Medis</span></a>
            <a href="suratpesanan.php" title="Surat Pesanan Barang Medis"><i class="fas fa-box-open"></i><span>Surat Pesanan Barang Medis</span></a>
            <a href="stok_gudang.php" title="Stok Lokasi Gudang Barang"><i class="fas fa-boxes"></i><span>Stok Gudang</span></a>
            <a href="stokpertanggal.php" title="Stok per Tanggal"><i class="fas fa-clinic-medical"></i><span>Stok per Tanggal</span></a>
            <a href="stok_depo_rawat_inap.php" title="Stok Lokasi Depo Rawat Inap"><i class="fas fa-hospital-user"></i><span>Stok Depo RI</span></a>
            <a href="stok_minimal_gudang.php" title="Stok Minimal Gudang Barang"><i class="fas fa-sort-amount-down"></i><span>Stok Min Gudang</span></a>
            <a href="stok_minimal_depo_rawat_jalan.php" title="Stok Minimal Depo Rawat Jalan"><i class="fas fa-sort-amount-down-alt"></i><span>Stok Min Depo RJ</span></a>
            <a href="stok_minimal_depo_rawat_inap.php" title="Stok Minimal Depo Rawat Inap"><i class="fas fa-sort-amount-down-alt"></i><span>Stok Min Depo RI</span></a>
            <a href="laporannarkotik.php" title="Menu Pembuatan Laporan Narkotik"><i class="fas fa-capsules"></i><span>Laporan Narkotik</span></a>
            <a href="laporanformularium.php" title="Laporan Formularium"><i class="fas fa-notes-medical"></i><span>Formularium</span></a>
            <a href="2025spjekatalog.php" title="SPJ E-Katalog 2025"><i class="fas fa-file-invoice"></i><span>SPJ E-Katalog</span></a>
            <a href="2025spjreguler.php" title="SPJ Reguler 2025"><i class="fas fa-file-invoice-dollar"></i><span>SPJ Reguler</span></a>
            <a href="2025spjregulergabungan.php" title="SPJ Reguler Gabungan 2025"><i class="fas fa-file-contract"></i><span>SPJ Gabungan</span></a>
        </div>
    </div>
    <footer>by IT rsudpringsewu</footer>
</body>
</html>